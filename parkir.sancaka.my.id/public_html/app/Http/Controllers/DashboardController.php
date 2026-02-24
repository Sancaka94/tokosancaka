<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB; // Tambahkan ini untuk menggunakan DB::raw

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // 1. Statistik Hari Ini
        $data = [
            'motor_masuk' => \App\Models\Transaction::where('vehicle_type', 'motor')
                                        ->whereDate('entry_time', today())
                                        ->count(),

            'mobil_masuk' => \App\Models\Transaction::where('vehicle_type', 'mobil')
                                        ->whereDate('entry_time', today())
                                        ->count(),

            'total_pendapatan' => \App\Models\Transaction::whereDate('exit_time', today())
                                             ->sum(\Illuminate\Support\Facades\DB::raw('fee + IFNULL(toilet_fee, 0)')) ?? 0,
        ];

        // 2. Perbandingan Pendapatan Bulan Ini vs Bulan Lalu
        $pendapatanBulanIni = \App\Models\Transaction::whereMonth('exit_time', date('m'))
                                ->whereYear('exit_time', date('Y'))
                                ->sum(\Illuminate\Support\Facades\DB::raw('fee + IFNULL(toilet_fee, 0)'));

        $pendapatanBulanLalu = \App\Models\Transaction::whereMonth('exit_time', \Carbon\Carbon::now()->subMonth()->month)
                                ->whereYear('exit_time', \Carbon\Carbon::now()->subMonth()->year)
                                ->sum(\Illuminate\Support\Facades\DB::raw('fee + IFNULL(toilet_fee, 0)'));

        $persentase = 0;
        $trend = 'tetap';
        if ($pendapatanBulanLalu > 0) {
            $persentase = (($pendapatanBulanIni - $pendapatanBulanLalu) / $pendapatanBulanLalu) * 100;
            $trend = $persentase > 0 ? 'naik' : ($persentase < 0 ? 'turun' : 'tetap');
        } elseif ($pendapatanBulanIni > 0) {
            $persentase = 100;
            $trend = 'naik';
        }
        $data['perbandingan'] = [
            'persentase' => abs(round($persentase, 1)),
            'trend' => $trend,
            'bulan_ini' => $pendapatanBulanIni
        ];

        // 3. Data Grafik Harian (7 Hari Terakhir)
        $labelHarian = [];
        $dataHarian = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::now()->subDays($i);
            $labelHarian[] = $date->translatedFormat('d M');
            $dataHarian[] = \App\Models\Transaction::whereDate('exit_time', $date)
                                       ->sum(\Illuminate\Support\Facades\DB::raw('fee + IFNULL(toilet_fee, 0)'));
        }

        // 4. Data Grafik Bulanan (6 Bulan Terakhir)
        $labelBulanan = [];
        $dataBulanan = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = \Carbon\Carbon::now()->subMonths($i);
            $labelBulanan[] = $date->translatedFormat('M Y');
            $dataBulanan[] = \App\Models\Transaction::whereMonth('exit_time', $date->month)
                                ->whereYear('exit_time', $date->year)
                                ->sum(\Illuminate\Support\Facades\DB::raw('fee + IFNULL(toilet_fee, 0)'));
        }

        $chartData = [
            'harian' => ['labels' => $labelHarian, 'data' => $dataHarian],
            'bulanan' => ['labels' => $labelBulanan, 'data' => $dataBulanan],
        ];

        // 5. Tabel Aktivitas Terbaru
        $recent_transactions = \App\Models\Transaction::with('operator')->latest()->take(5)->get();

        // =========================================================
        // 6. TAMBAHAN: DATA BUKU KAS MANUAL UNTUK DASHBOARD
        // =========================================================
        $totalPemasukanKas = \App\Models\FinancialReport::where('jenis', 'pemasukan')->sum('nominal');
        $totalPengeluaranKas = \App\Models\FinancialReport::where('jenis', 'pengeluaran')->sum('nominal');
        $saldoKas = $totalPemasukanKas - $totalPengeluaranKas;

        $recent_financials = \App\Models\FinancialReport::latest('tanggal')->take(5)->get();

        return view('dashboard.index', compact(
            'data', 'user', 'recent_transactions', 'chartData',
            'totalPemasukanKas', 'totalPengeluaranKas', 'saldoKas', 'recent_financials'
        ));
    }

    public function harian(Request $request)
    {
        $tanggal = $request->tanggal ?? today()->toDateString();

        $transactions = Transaction::whereDate('entry_time', $tanggal)->latest()->paginate(20);

        // PERBARUI BARIS INI: Mengambil rekap khusus total kendaraan dan pendapatan parkir/toilet
        $rekap = Transaction::select(
            DB::raw('SUM(CASE WHEN vehicle_type = "motor" THEN 1 ELSE 0 END) as total_motor'),
            DB::raw('SUM(CASE WHEN vehicle_type = "mobil" THEN 1 ELSE 0 END) as total_mobil'),
            DB::raw('SUM(fee) as total_parkir'),
            DB::raw('SUM(IFNULL(toilet_fee, 0)) as total_toilet'),
            DB::raw('SUM(fee + IFNULL(toilet_fee, 0)) as total_semua')
        )->whereDate('entry_time', $tanggal)->first();

        // Pass 'rekap' ke view
        return view('laporan.harian', compact('transactions', 'tanggal', 'rekap'));
    }

    public function bulanan(Request $request)
    {
        $bulan = $request->bulan ?? date('m');
        $tahun = $request->tahun ?? date('Y');

        $transactions = Transaction::whereMonth('entry_time', $bulan)
                                   ->whereYear('entry_time', $tahun)
                                   ->latest()
                                   ->paginate(50);

        // PERBARUI BARIS INI: Menghitung rekap bulanan (kendaraan & uang)
        $rekap = Transaction::select(
            DB::raw('SUM(CASE WHEN vehicle_type = "motor" THEN 1 ELSE 0 END) as total_motor'),
            DB::raw('SUM(CASE WHEN vehicle_type = "mobil" THEN 1 ELSE 0 END) as total_mobil'),
            DB::raw('SUM(fee) as total_parkir'),
            DB::raw('SUM(IFNULL(toilet_fee, 0)) as total_toilet'),
            DB::raw('SUM(fee + IFNULL(toilet_fee, 0)) as total_semua')
        )->whereMonth('entry_time', $bulan)
         ->whereYear('entry_time', $tahun)->first();

        return view('laporan.bulanan', compact('transactions', 'bulan', 'tahun', 'rekap'));
    }

   public function triwulan(Request $request)
    {
        $kuartal = $request->kuartal ?? 1;
        $tahun = $request->tahun ?? date('Y');

        $startMonth = ($kuartal - 1) * 3 + 1;
        $endMonth = $startMonth + 2;

        $transactions = Transaction::whereMonth('entry_time', '>=', $startMonth)
                                   ->whereMonth('entry_time', '<=', $endMonth)
                                   ->whereYear('entry_time', $tahun)
                                   ->latest()
                                   ->paginate(50);

        // PERBARUI BARIS INI: Menghitung rekap triwulan (kendaraan & uang)
        $rekap = Transaction::select(
            DB::raw('SUM(CASE WHEN vehicle_type = "motor" THEN 1 ELSE 0 END) as total_motor'),
            DB::raw('SUM(CASE WHEN vehicle_type = "mobil" THEN 1 ELSE 0 END) as total_mobil'),
            DB::raw('SUM(fee) as total_parkir'),
            DB::raw('SUM(IFNULL(toilet_fee, 0)) as total_toilet'),
            DB::raw('SUM(fee + IFNULL(toilet_fee, 0)) as total_semua')
        )->whereMonth('entry_time', '>=', $startMonth)
         ->whereMonth('entry_time', '<=', $endMonth)
         ->whereYear('entry_time', $tahun)->first();

        return view('laporan.triwulan', compact('transactions', 'kuartal', 'tahun', 'rekap'));
    }
}
