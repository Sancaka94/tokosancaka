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
        // 2. Deteksi Tenant dari Subdomain URL (Berlaku untuk semua fungsi)
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];

        // 3. Cari data Tenant-nya
        $tenant = \App\Models\Tenant::where('subdomain', $subdomain)->first();

        // 4. Simpan ID-nya. Jika tidak ketemu, default ke 1 (Pusat)
        $this->tenantId = $tenant ? $tenant->id : 1;
    }
    /**
     * Halaman Public untuk Input Data
     */
    public function create()
    {
        // Ambil data kontak untuk dropdown pilihan
        $contacts = CashflowContact::orderBy('name', 'asc')->get();
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
            'category' => 'required', // general, piutang_new, dll
            'contact_id' => 'nullable|exists:cashflow_contacts,id',
            'name' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        // Gunakan Database Transaction agar data aman
        DB::beginTransaction();

        try {
            // Tentukan Nama: Jika pilih kontak, pakai nama kontak. Jika tidak, pakai input manual.
            $finalName = $request->name;
            if ($request->contact_id) {
                $contact = CashflowContact::find($request->contact_id);
                $finalName = $contact->name;
            }

            // 2. Simpan ke Tabel Cashflows
            $cashflow = Cashflow::create([
                'contact_id'  => $request->contact_id,
                'name'        => $finalName,
                'description' => $request->description,
                'type'        => $request->type,     // income / expense
                'category'    => $request->category, // general / hutang / piutang
                'amount'      => $request->amount,
                'date'        => $request->date,
            ]);

            // 3. Logika Update Saldo Kontak (Hutang Piutang)
            if ($request->contact_id) {
                $this->updateContactBalance($contact, $request->category, $request->amount, 'create');
            }

            // Commit transaksi jika semua sukses
            DB::commit();

            // 4. Kirim Notifikasi WA (Jalankan di background atau queue jika memungkinkan)
            try {
                $this->sendFonnteNotification($cashflow);
            } catch (\Exception $e) {
                // Jangan gagalkan transaksi hanya karena WA error
                \Log::error("Fonnte Error: " . $e->getMessage());
            }

            return redirect()->back()->with('success', 'Transaksi berhasil disimpan & saldo diperbarui!');

        } catch (\Exception $e) {
            DB::rollback(); // Batalkan semua perubahan jika ada error
            return redirect()->back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    /**
     * Halaman Dashboard Admin (Filter & List)
     */
    public function index(Request $request)
    {
        $query = Cashflow::orderBy('date', 'desc')->orderBy('created_at', 'desc');

        // Filter Tanggal
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        // Filter Tipe
        if ($request->type) {
            $query->where('type', $request->type);
        }

        // Filter Kategori (Hutang/Piutang/General)
        if ($request->category) {
            $query->where('category', $request->category);
        }

        $data = $query->paginate(20);

        // Hitung Summary (Berdasarkan filter saat ini)
        $totalIncome = Cashflow::where('type', 'income')->sum('amount');
        $totalExpense = Cashflow::where('type', 'expense')->sum('amount');
        $saldo = $totalIncome - $totalExpense;

        return view('cashflow.index', compact('data', 'totalIncome', 'totalExpense', 'saldo'));
    }

    /**
     * Hapus Data (Soft Delete / Hard Delete)
     * PENTING: Saldo hutang harus dikembalikan jika transaksi dihapus
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $cashflow = Cashflow::findOrFail($id);

            // Jika transaksi ini terkait kontak (Hutang/Piutang), kembalikan saldonya
            if ($cashflow->contact_id) {
                $contact = CashflowContact::find($cashflow->contact_id);
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

    /**
     * Logika Pusat Update Saldo
     * @param $action 'create' (tambah transaksi) atau 'delete' (hapus transaksi)
     */
    private function updateContactBalance($contact, $category, $amount, $action = 'create')
    {
        // Jika action delete, kita balik logicnya (rollback)
        // Contoh: Kalau create menambah saldo, maka delete harus mengurangi saldo.
        $multiplier = ($action === 'create') ? 1 : -1;

        switch ($category) {
            case 'piutang_new':
                // Kita pinjamkan uang -> Saldo dia bertambah (+) (Dia punya utang ke kita)
                $contact->balance += ($amount * $multiplier);
                break;

            case 'piutang_pay':
                // Dia bayar utang -> Saldo dia berkurang (-) (Utangnya lunas)
                $contact->balance -= ($amount * $multiplier);
                break;

            case 'hutang_new':
                // Kita ngutang -> Saldo dia berkurang (-) (Kita punya utang, nilai minus)
                $contact->balance -= ($amount * $multiplier);
                break;

            case 'hutang_pay':
                // Kita bayar utang -> Saldo dia bertambah (+) (Mendekati 0)
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
        // Ambil data sesuai filter (bisa disesuaikan logic filternya sama dengan index)
        $data = Cashflow::orderBy('date', 'desc')->get();

        $totalIncome = $data->where('type', 'income')->sum('amount');
        $totalExpense = $data->where('type', 'expense')->sum('amount');
        $saldo = $totalIncome - $totalExpense;

        $pdf = Pdf::loadView('cashflow.pdf', compact('data', 'totalIncome', 'totalExpense', 'saldo'));

        // Setup kertas landscape agar muat tabel lebar
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('laporan-keuangan-' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export ke Excel (CSV Sederhana)
     */
    public function exportExcel()
    {
        $fileName = 'laporan-keuangan-' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            // Header CSV
            fputcsv($handle, ['No', 'Tanggal', 'Kategori', 'Nama', 'Keterangan', 'Tipe', 'Masuk', 'Keluar']);

            $cashflows = Cashflow::orderBy('date', 'desc')->get();
            $no = 1;

            foreach ($cashflows as $row) {
                $masuk = $row->type == 'income' ? $row->amount : 0;
                $keluar = $row->type == 'expense' ? $row->amount : 0;

                fputcsv($handle, [
                    $no++,
                    $row->date,
                    strtoupper(str_replace('_', ' ', $row->category)), // Format kategori
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

    /**
     * Kirim Notifikasi WA via Fonnte
     */
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
            'Authorization' => 'TOKEN_FONNTE_ANDA_DISINI', // Ganti dengan Token Asli
        ])->post('https://api.fonnte.com/send', [
            'target' => '085745808809', // Nomor Tujuan Admin
            'message' => $message,
        ]);
    }
}
