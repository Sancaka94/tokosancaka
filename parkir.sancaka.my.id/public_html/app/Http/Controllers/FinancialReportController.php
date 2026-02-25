<?php

namespace App\Http\Controllers;

use App\Models\FinancialReport;
use Illuminate\Http\Request;

class FinancialReportController extends Controller
{
    public function index(Request $request)
    {
        $query = FinancialReport::orderBy('tanggal', 'desc')->latest();

        // Filter berdasarkan bulan jika ada
        if ($request->filled('bulan')) {
            $query->whereMonth('tanggal', $request->bulan);
        }

        if ($request->filled('tahun')) {
            $query->whereYear('tanggal', $request->tahun);
        }

        $reports = $query->paginate(20)->withQueryString();

        // Hitung total untuk summary di atas tabel
        $totalPemasukan = FinancialReport::where('jenis', 'pemasukan')->sum('nominal');
        $totalPengeluaran = FinancialReport::where('jenis', 'pengeluaran')->sum('nominal');
        $saldo = $totalPemasukan - $totalPengeluaran;

        return view('financial_reports.index', compact('reports', 'totalPemasukan', 'totalPengeluaran', 'saldo'));
    }

    public function store(Request $request)
{
    $request->validate([
        'tanggal' => 'required|date',
        'jenis' => 'required|in:pemasukan,pengeluaran',
        'kategori' => 'required|string|max:255',
        'nominal' => 'required|numeric|min:1',
    ]);

    // 1. Simpan Transaksi Utama (Misal: Setoran Parkir)
    \App\Models\FinancialReport::create([
        'tanggal' => $request->tanggal,
        'jenis' => $request->jenis,
        'kategori' => $request->kategori,
        'nominal' => $request->nominal,
        'keterangan' => $request->keterangan,
    ]);

    // 2. Simpan Gaji Pegawai (Jika diisi)
    if ($request->has('salaries')) {
        foreach ($request->salaries as $employeeId => $amount) {
            if ($amount > 0) {
                $employee = \App\Models\User::find($employeeId);
                \App\Models\FinancialReport::create([
                    'tanggal' => $request->tanggal,
                    'jenis' => 'pengeluaran',
                    'kategori' => 'Gaji Pegawai',
                    'nominal' => $amount,
                    'keterangan' => 'Pembayaran gaji harian: ' . $employee->name,
                ]);
            }
        }
    }

    return redirect()->route('financial.index')->with('success', 'Data transaksi dan gaji berhasil dicatat.');
}

    // ==========================================
    // TAMBAHAN: FUNGSI EDIT & UPDATE
    // ==========================================

    public function edit(FinancialReport $financial)
    {
        // Pastikan file edit.blade.php berada di dalam folder resources/views/financial_reports/
        // Kita passing data $financial dengan nama variabel 'kas' agar sesuai dengan form di Blade
        return view('financial_reports.edit', ['kas' => $financial]);
    }

    public function update(Request $request, FinancialReport $financial)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'jenis' => 'required|in:pemasukan,pengeluaran',
            'kategori' => 'required|string|max:255',
            'nominal' => 'required|numeric|min:1',
            'keterangan' => 'nullable|string',
        ]);

        $financial->update($request->all());

        return redirect()->route('financial.index')->with('success', 'Catatan keuangan berhasil diperbarui.');
    }

    // ==========================================

    public function destroy(FinancialReport $financial)
    {
        $financial->delete();
        return redirect()->route('financial.index')->with('success', 'Catatan keuangan berhasil dihapus.');
    }
}
