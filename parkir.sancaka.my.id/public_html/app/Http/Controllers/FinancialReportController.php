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
            'keterangan' => 'nullable|string',
        ]);

        FinancialReport::create($request->all());

        return redirect()->route('financial.index')->with('success', 'Catatan keuangan berhasil ditambahkan.');
    }

    public function destroy(FinancialReport $financial)
    {
        $financial->delete();
        return redirect()->route('financial.index')->with('success', 'Catatan keuangan berhasil dihapus.');
    }
}
