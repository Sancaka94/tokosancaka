<?php

namespace App\Http\Controllers;

use App\Models\Cashflow;
use App\Models\CashflowContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Tenant;

class CashflowController extends Controller
{
    // 1. Siapkan variabel penampung ID Tenant
    protected $tenantId;

    public function __construct(Request $request)
    {
        // 2. Deteksi Tenant dari Subdomain URL
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];

        // 3. Cari data Tenant-nya
        $tenant = Tenant::where('subdomain', $subdomain)->first();

        // 4. Simpan ID-nya. Jika tidak ketemu, default ke 1 (Pusat)
        $this->tenantId = $tenant ? $tenant->id : 1;
    }

    /**
     * Halaman Public untuk Input Data
     */
    public function create()
    {
        // PERBAIKAN: Hanya panggil kontak milik tenant ini saja
        $contacts = CashflowContact::where('tenant_id', $this->tenantId)
                                   ->orderBy('name', 'asc')
                                   ->get();

        return view('cashflow.public-create', compact('contacts'));
    }

    /**
     * Proses Simpan Data (Public & Admin)
     */
    public function store(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'type' => 'required|in:income,expense',
            'category' => 'required',
            'contact_id' => 'nullable|exists:cashflow_contacts,id',
            'name' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $finalName = $request->name;
            if ($request->contact_id) {
                // PERBAIKAN: Pastikan kontak yang dipilih benar milik tenant ini
                $contact = CashflowContact::where('tenant_id', $this->tenantId)->find($request->contact_id);
                if($contact) {
                    $finalName = $contact->name;
                }
            }

            // 2. Simpan ke Tabel Cashflows
            $cashflow = Cashflow::create([
                'tenant_id'   => $this->tenantId, // PERBAIKAN WAJIB: Simpan ID Tenant
                'contact_id'  => $request->contact_id,
                'name'        => $finalName,
                'description' => $request->description,
                'type'        => $request->type,
                'category'    => $request->category,
                'amount'      => $request->amount,
                'date'        => $request->date,
            ]);

            // 3. Logika Update Saldo Kontak
            if ($request->contact_id && isset($contact)) {
                $this->updateContactBalance($contact, $request->category, $request->amount, 'create');
            }

            DB::commit();

            // 4. Kirim Notifikasi WA
            try {
                $this->sendFonnteNotification($cashflow);
            } catch (\Exception $e) {
                \Log::error("Fonnte Error: " . $e->getMessage());
            }

            return redirect()->back()->with('success', 'Transaksi berhasil disimpan & saldo diperbarui!');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    /**
     * Halaman Dashboard Admin (Filter & List)
     */
    public function index(Request $request)
    {
        // PERBAIKAN: Filter data HANYA untuk tenant ini
        $query = Cashflow::where('tenant_id', $this->tenantId)
                         ->orderBy('date', 'desc')
                         ->orderBy('created_at', 'desc');

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->category) {
            $query->where('category', $request->category);
        }

        $data = $query->paginate(20);

        // PERBAIKAN: Hitung Summary HANYA untuk tenant ini
        $totalIncome = Cashflow::where('tenant_id', $this->tenantId)->where('type', 'income')->sum('amount');
        $totalExpense = Cashflow::where('tenant_id', $this->tenantId)->where('type', 'expense')->sum('amount');
        $saldo = $totalIncome - $totalExpense;

        return view('cashflow.index', compact('data', 'totalIncome', 'totalExpense', 'saldo'));
    }

    /**
     * Hapus Data (Soft Delete / Hard Delete)
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            // PERBAIKAN: Pastikan tenant cuma bisa hapus datanya sendiri
            $cashflow = Cashflow::where('tenant_id', $this->tenantId)->findOrFail($id);

            if ($cashflow->contact_id) {
                $contact = CashflowContact::where('tenant_id', $this->tenantId)->find($cashflow->contact_id);
                if ($contact) {
                    $this->updateContactBalance($contact, $cashflow->category, $cashflow->amount, 'delete');
                }
            }

            $cashflow->delete();
            DB::commit();

            return redirect()->back()->with('success', 'Data dihapus dan saldo telah dikembalikan (rollback).');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }

    private function updateContactBalance($contact, $category, $amount, $action = 'create')
    {
        $multiplier = ($action === 'create') ? 1 : -1;

        switch ($category) {
            case 'piutang_new':
                $contact->balance += ($amount * $multiplier);
                break;
            case 'piutang_pay':
                $contact->balance -= ($amount * $multiplier);
                break;
            case 'hutang_new':
                $contact->balance -= ($amount * $multiplier);
                break;
            case 'hutang_pay':
                $contact->balance += ($amount * $multiplier);
                break;
        }
        $contact->save();
    }

    /**
     * Export ke PDF
     */
    public function exportPdf(Request $request)
    {
        // PERBAIKAN: Filter tenant_id
        $data = Cashflow::where('tenant_id', $this->tenantId)->orderBy('date', 'desc')->get();

        $totalIncome = $data->where('type', 'income')->sum('amount');
        $totalExpense = $data->where('type', 'expense')->sum('amount');
        $saldo = $totalIncome - $totalExpense;

        $pdf = Pdf::loadView('cashflow.pdf', compact('data', 'totalIncome', 'totalExpense', 'saldo'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('laporan-keuangan-' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export ke Excel
     */
    public function exportExcel()
    {
        $fileName = 'laporan-keuangan-' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['No', 'Tanggal', 'Kategori', 'Nama', 'Keterangan', 'Tipe', 'Masuk', 'Keluar']);

            // PERBAIKAN: Filter tenant_id
            $cashflows = Cashflow::where('tenant_id', $this->tenantId)->orderBy('date', 'desc')->get();
            $no = 1;

            foreach ($cashflows as $row) {
                $masuk = $row->type == 'income' ? $row->amount : 0;
                $keluar = $row->type == 'expense' ? $row->amount : 0;

                fputcsv($handle, [
                    $no++,
                    $row->date,
                    strtoupper(str_replace('_', ' ', $row->category)),
                    $row->name,
                    $row->description,
                    ($row->type == 'income' ? 'Pemasukan' : 'Pengeluaran'),
                    $masuk,
                    $keluar
                ]);
            }
            fclose($handle);
        }, $fileName);
    }

    private function sendFonnteNotification($data)
    {
        $jenis = $data->type == 'income' ? 'PEMASUKAN (+)' : 'PENGELUARAN (-)';
        $nominal = number_format($data->amount, 0, ',', '.');
        $kategori = strtoupper(str_replace('_', ' ', $data->category));

        $message = "*LAPORAN KEUANGAN BARU*\n" .
                   "---------------------------\n" .
                   "Jenis: $jenis\n" .
                   "Kategori: $kategori\n" .
                   "Nominal: Rp $nominal\n" .
                   "Nama: $data->name\n" .
                   "Ket: $data->description\n" .
                   "Tgl: $data->date\n\n" .
                   "Data telah tersimpan di sistem.";

        Http::withHeaders([
            'Authorization' => 'TOKEN_FONNTE_ANDA_DISINI',
        ])->post('https://api.fonnte.com/send', [
            'target' => '085745808809',
            'message' => $message,
        ]);
    }
}
