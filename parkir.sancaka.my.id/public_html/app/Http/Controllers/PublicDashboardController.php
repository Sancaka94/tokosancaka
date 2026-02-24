<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\FinancialReport;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PublicDashboardController extends Controller
{
   public function index()
    {
        $user = auth()->user();

        // =========================================================
        // 1. STATISTIK HARI INI (Parkir + Toilet + Kas)
        // =========================================================
        $parkirToiletHariIni = \App\Models\Transaction::whereDate('exit_time', today())
                                     ->sum(\Illuminate\Support\Facades\DB::raw('fee + IFNULL(toilet_fee, 0)'));
        $kasMasukHariIni = \App\Models\FinancialReport::whereDate('tanggal', today())
                                   ->where('jenis', 'pemasukan')->sum('nominal');
        $kasKeluarHariIni = \App\Models\FinancialReport::whereDate('tanggal', today())
                                    ->where('jenis', 'pengeluaran')->sum('nominal');

        $data = [
            'motor_masuk' => \App\Models\Transaction::where('vehicle_type', 'motor')->whereDate('entry_time', today())->count(),
            'mobil_masuk' => \App\Models\Transaction::where('vehicle_type', 'mobil')->whereDate('entry_time', today())->count(),
            'total_pendapatan' => $parkirToiletHariIni + $kasMasukHariIni - $kasKeluarHariIni,
        ];

        // =========================================================
        // 2. PENDAPATAN BULAN INI VS BULAN LALU
        // =========================================================
        $parkirBulanIni = \App\Models\Transaction::whereMonth('exit_time', date('m'))
                                ->whereYear('exit_time', date('Y'))
                                ->sum(\Illuminate\Support\Facades\DB::raw('fee + IFNULL(toilet_fee, 0)'));
        $kasMasukBulanIni = \App\Models\FinancialReport::whereMonth('tanggal', date('m'))
                                ->whereYear('tanggal', date('Y'))->where('jenis', 'pemasukan')->sum('nominal');
        $kasKeluarBulanIni = \App\Models\FinancialReport::whereMonth('tanggal', date('m'))
                                ->whereYear('tanggal', date('Y'))->where('jenis', 'pengeluaran')->sum('nominal');
        $pendapatanBulanIni = $parkirBulanIni + $kasMasukBulanIni - $kasKeluarBulanIni;

        $bulanLalu = \Carbon\Carbon::now()->subMonth();
        $parkirBulanLalu = \App\Models\Transaction::whereMonth('exit_time', $bulanLalu->month)
                                ->whereYear('exit_time', $bulanLalu->year)
                                ->sum(\Illuminate\Support\Facades\DB::raw('fee + IFNULL(toilet_fee, 0)'));
        $kasMasukBulanLalu = \App\Models\FinancialReport::whereMonth('tanggal', $bulanLalu->month)
                                ->whereYear('tanggal', $bulanLalu->year)->where('jenis', 'pemasukan')->sum('nominal');
        $kasKeluarBulanLalu = \App\Models\FinancialReport::whereMonth('tanggal', $bulanLalu->month)
                                ->whereYear('tanggal', $bulanLalu->year)->where('jenis', 'pengeluaran')->sum('nominal');
        $pendapatanBulanLalu = $parkirBulanLalu + $kasMasukBulanLalu - $kasKeluarBulanLalu;

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

        // =========================================================
        // 3. GRAFIK HARIAN (7 HARI TERAKHIR)
        // =========================================================
        $labelHarian = [];
        $dataHarian = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::now()->subDays($i);
            $labelHarian[] = $date->translatedFormat('d M');

            $parkirToilet = \App\Models\Transaction::whereDate('exit_time', $date)
                                       ->sum(\Illuminate\Support\Facades\DB::raw('fee + IFNULL(toilet_fee, 0)'));
            $kasMasuk = \App\Models\FinancialReport::whereDate('tanggal', $date)
                                       ->where('jenis', 'pemasukan')->sum('nominal');
            $kasKeluar = \App\Models\FinancialReport::whereDate('tanggal', $date)
                                       ->where('jenis', 'pengeluaran')->sum('nominal');

            $dataHarian[] = $parkirToilet + $kasMasuk - $kasKeluar;
        }

        // =========================================================
        // 4. GRAFIK BULANAN (6 BULAN TERAKHIR)
        // =========================================================
        $labelBulanan = [];
        $dataBulanan = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = \Carbon\Carbon::now()->subMonths($i);
            $labelBulanan[] = $date->translatedFormat('M Y');

            $parkirToilet = \App\Models\Transaction::whereMonth('exit_time', $date->month)
                                ->whereYear('exit_time', $date->year)
                                ->sum(\Illuminate\Support\Facades\DB::raw('fee + IFNULL(toilet_fee, 0)'));
            $kasMasuk = \App\Models\FinancialReport::whereMonth('tanggal', $date->month)
                                ->whereYear('tanggal', $date->year)->where('jenis', 'pemasukan')->sum('nominal');
            $kasKeluar = \App\Models\FinancialReport::whereMonth('tanggal', $date->month)
                                ->whereYear('tanggal', $date->year)->where('jenis', 'pengeluaran')->sum('nominal');

            $dataBulanan[] = $parkirToilet + $kasMasuk - $kasKeluar;
        }

        $chartData = [
            'harian' => ['labels' => $labelHarian, 'data' => $dataHarian],
            'bulanan' => ['labels' => $labelBulanan, 'data' => $dataBulanan],
        ];

        // =========================================================
        // 5. DATA AKTIVITAS TERBARU
        // =========================================================
        $recent_transactions = \App\Models\Transaction::with('operator')->latest()->take(5)->get();

        // =========================================================
        // 6. RINGKASAN BUKU KAS
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
}
