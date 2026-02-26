<?php

namespace App\Http\Controllers;

use App\Models\FinancialReport;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon; // <-- Tambahkan ini untuk mempermudah manipulasi tanggal

use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FinancialExport;

class FinancialReportController extends Controller
{
    public function index(Request $request)
    {
        $query = FinancialReport::orderBy('tanggal', 'desc')->latest();

        // Filter tabel berdasarkan bulan/tahun tetap dipertahankan
        if ($request->filled('bulan')) {
            $query->whereMonth('tanggal', $request->bulan);
        }
        if ($request->filled('tahun')) {
            $query->whereYear('tanggal', $request->tahun);
        }

        $reports = $query->paginate(20)->withQueryString();

        // Perhitungan summary global (berdasarkan filter tabel)
        $totalPemasukan = FinancialReport::where('jenis', 'pemasukan')->sum('nominal');
        $totalPengeluaran = FinancialReport::where('jenis', 'pengeluaran')->sum('nominal');
        $saldo = $totalPemasukan - $totalPengeluaran;

        // ==========================================
        // PERHITUNGAN STATISTIK BULAN INI VS KEMARIN
        // ==========================================
        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        $lastMonthDate = $now->copy()->subMonth();
        $lastMonth = $lastMonthDate->month;
        $lastYear = $lastMonthDate->year;

        // 1. Pemasukan Bulan Ini & Kemarin
        $pemasukanBulanIni = FinancialReport::where('jenis', 'pemasukan')
            ->whereMonth('tanggal', $currentMonth)
            ->whereYear('tanggal', $currentYear)
            ->sum('nominal');

        $pemasukanBulanLalu = FinancialReport::where('jenis', 'pemasukan')
            ->whereMonth('tanggal', $lastMonth)
            ->whereYear('tanggal', $lastYear)
            ->sum('nominal');

        // 2. Rata-rata Harian Bulan Ini & Kemarin
        $rataBulanIni = $pemasukanBulanIni / $now->daysInMonth;
        $rataBulanLalu = $pemasukanBulanLalu / $lastMonthDate->daysInMonth;

        // 3. Selisih & Persentase
        $selisihNominal = $pemasukanBulanIni - $pemasukanBulanLalu;

        // Mencegah error division by zero (pembagian dengan nol)
        if ($pemasukanBulanLalu > 0) {
            $selisihPersentase = ($selisihNominal / $pemasukanBulanLalu) * 100;
        } else {
            $selisihPersentase = $pemasukanBulanIni > 0 ? 100 : 0;
        }

        // AMBIL DATA PEGAWAI UNTUK MODAL GAJI
        $employees = User::whereIn('role', ['operator', 'admin'])->get();

        // Kirim semua variabel ke view
        return view('financial_reports.index', compact(
            'reports',
            'totalPemasukan',
            'totalPengeluaran',
            'saldo',
            'employees',
            'pemasukanBulanIni',
            'pemasukanBulanLalu',
            'rataBulanIni',
            'rataBulanLalu',
            'selisihNominal',
            'selisihPersentase'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'transactions' => 'required|array',
            'transactions.*.tanggal' => 'required|date',
            'transactions.*.jenis' => 'required|in:pemasukan,pengeluaran',
            'transactions.*.kategori' => 'required|string|max:255',
            'transactions.*.nominal' => 'required|numeric|min:1',
            'transactions.*.salaries' => 'nullable|array',
        ]);

        foreach ($request->transactions as $trx) {
            FinancialReport::create([
                'tanggal' => $trx['tanggal'],
                'jenis' => $trx['jenis'],
                'kategori' => $trx['kategori'],
                'nominal' => $trx['nominal'],
                'keterangan' => $trx['keterangan'] ?? null,
            ]);

            if (isset($trx['salaries']) && is_array($trx['salaries'])) {
                foreach ($trx['salaries'] as $employeeId => $amount) {
                    if ($amount > 0) {
                        $employee = User::find($employeeId);
                        if ($employee) {
                            FinancialReport::create([
                                'tanggal' => $trx['tanggal'],
                                'jenis' => 'pengeluaran',
                                'kategori' => 'Gaji Pegawai',
                                'nominal' => $amount,
                                'keterangan' => 'Pembayaran gaji harian: ' . $employee->name,
                            ]);
                        }
                    }
                }
            }
        }

        return redirect()->route('financial.index')->with('success', 'Semua data transaksi dan gaji berhasil dicatat.');
    }

    public function edit(FinancialReport $financial)
    {
        $employees = User::whereIn('role', ['operator', 'admin'])->get();

        return view('financial_reports.edit', [
            'kas' => $financial,
            'employees' => $employees
        ]);
    }

    public function update(Request $request, FinancialReport $financial)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'jenis' => 'required|in:pemasukan,pengeluaran',
            'kategori' => 'required|string|max:255',
            'nominal' => 'required|numeric|min:1',
        ]);

        $financial->update([
            'tanggal' => $request->tanggal,
            'jenis' => $request->jenis,
            'kategori' => $request->kategori,
            'nominal' => $request->nominal,
            'keterangan' => $request->keterangan ?? $financial->keterangan,
        ]);

        if ($request->has('salaries')) {
            foreach ($request->salaries as $employeeId => $amount) {
                if ($amount > 0) {
                    $employee = User::find($employeeId);

                    FinancialReport::create([
                        'tanggal' => $request->tanggal,
                        'jenis' => 'pengeluaran',
                        'kategori' => 'Gaji Pegawai',
                        'nominal' => $amount,
                        'keterangan' => 'Koreksi gaji: ' . ($employee->name ?? 'Pegawai'),
                    ]);
                }
            }
        }

        return redirect()->route('financial.index')->with('success', 'Catatan keuangan berhasil diperbarui.');
    }

    public function destroy(FinancialReport $financial)
    {
        $financial->delete();
        return redirect()->route('financial.index')->with('success', 'Catatan keuangan berhasil dihapus.');
    }

    public function exportPdf(Request $request)
    {
        $query = FinancialReport::orderBy('tanggal', 'desc');

        if ($request->filled('bulan')) {
            $query->whereMonth('tanggal', $request->bulan);
        }
        if ($request->filled('tahun')) {
            $query->whereYear('tanggal', $request->tahun);
        }

        $reports = $query->get();
        $totalPemasukan = $reports->where('jenis', 'pemasukan')->sum('nominal');
        $totalPengeluaran = $reports->where('jenis', 'pengeluaran')->sum('nominal');
        $saldo = $totalPemasukan - $totalPengeluaran;

        $pdf = Pdf::loadView('financial_reports.pdf', compact('reports', 'totalPemasukan', 'totalPengeluaran', 'saldo'));

        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('Laporan_Keuangan_' . date('Y-m-d_H-i-s') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        return Excel::download(new FinancialExport($request->bulan, $request->tahun), 'Laporan_Keuangan_' . date('Y-m-d_H-i-s') . '.xlsx');
    }
}
