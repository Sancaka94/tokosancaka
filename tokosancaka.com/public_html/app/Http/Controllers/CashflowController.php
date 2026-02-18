<?php

namespace App\Http\Controllers;

use App\Models\Cashflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CashflowExport; // Anda perlu membuat class Export terpisah atau gunakan simple logic

class CashflowController extends Controller
{
    // 1. Halaman Public Form
    public function create()
    {
        return view('cashflow.public-create');
    }

    // 2. Simpan Data & Kirim WA
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'amount' => 'required|numeric',
            'type' => 'required|in:income,expense',
            'date' => 'required|date',
            'description' => 'nullable'
        ]);

        $cashflow = Cashflow::create($request->all());

        // Logika Fonnte (Notifikasi WA)
        $this->sendFonnteNotification($cashflow);

        return redirect()->back()->with('success', 'Data berhasil disimpan dan notifikasi terkirim!');
    }

    // 3. Halaman Admin Dashboard (Filter & List)
    public function index(Request $request)
    {
        $query = Cashflow::orderBy('date', 'desc');

        // Filter Tanggal
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        // Filter Tipe
        if ($request->type) {
            $query->where('type', $request->type);
        }

        $data = $query->paginate(20);

        // Hitung Total untuk Summary di atas tabel
        $totalIncome = Cashflow::where('type', 'income')->sum('amount');
        $totalExpense = Cashflow::where('type', 'expense')->sum('amount');
        $saldo = $totalIncome - $totalExpense;

        return view('cashflow.index', compact('data', 'totalIncome', 'totalExpense', 'saldo'));
    }

    public function update(Request $request, $id)
    {
        $cashflow = Cashflow::findOrFail($id);
        $cashflow->update($request->all());
        return redirect()->back()->with('success', 'Data berhasil diperbarui');
    }

    public function destroy($id)
    {
        Cashflow::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Data dihapus');
    }

    // Integrasi Fonnte
    private function sendFonnteNotification($data)
    {
        $jenis = $data->type == 'income' ? 'PEMASUKAN (+)' : 'PENGELUARAN (-)';
        $nominal = number_format($data->amount, 0, ',', '.');

        $message = "*LAPORAN KEUANGAN BARU*\n\n" .
                   "Jenis: $jenis\n" .
                   "Nominal: Rp $nominal\n" .
                   "Nama: $data->name\n" .
                   "Ket: $data->description\n" .
                   "Tgl: $data->date\n\n" .
                   "Data telah tersimpan di sistem.";

        try {
            Http::withHeaders([
                'Authorization' => 'TOKEN_FONNTE_ANDA_DISINI', // Ganti dengan Token Fonnte Anda
            ])->post('https://api.fonnte.com/send', [
                'target' => '085745808809',
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            // Log error jika WA gagal, tapi jangan stop proses aplikasi
            \Log::error("Fonnte Error: " . $e->getMessage());
        }
    }

    // Export PDF
    public function exportPdf(Request $request)
    {
        $data = Cashflow::all(); // Sesuaikan query jika ingin memfilter export juga
        $pdf = Pdf::loadView('cashflow.pdf', compact('data'));
        return $pdf->download('laporan-keuangan.pdf');
    }

    // Export Excel (Simple CSV version for brevity)
    public function exportExcel()
    {
        // Untuk excel yang proper gunakan Maatwebsite Export Class, ini versi cepat:
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['No', 'Tanggal', 'Nama', 'Tipe', 'Jumlah', 'Keterangan']);
            foreach (Cashflow::all() as $row) {
                fputcsv($handle, [$row->id, $row->date, $row->name, $row->type, $row->amount, $row->description]);
            }
            fclose($handle);
        }, 'laporan-keuangan.csv');
    }
}
