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
        // 1. Statistik Hari Ini
        $data = [
            'motor_masuk' => Transaction::where('vehicle_type', 'motor')
                                        ->whereDate('entry_time', today())
                                        ->count(),

            'mobil_masuk' => Transaction::where('vehicle_type', 'mobil')
                                        ->whereDate('entry_time', today())
                                        ->count(),
        ];

        // Total Pendapatan Bersih Hari Ini (Parkir + Toilet + Kas Masuk - Kas Keluar)
        $parkirToiletHariIni = Transaction::whereDate('exit_time', today())
                                    ->sum(DB::raw('fee + IFNULL(toilet_fee, 0)'));
        $kasMasukHariIni = FinancialReport::whereDate('tanggal', today())
                                    ->where('jenis', 'pemasukan')->sum('nominal');
        $kasKeluarHariIni = FinancialReport::whereDate('tanggal', today())
                                    ->where('jenis', 'pengeluaran')->sum('nominal');

        $data['total_pendapatan'] = $parkirToiletHariIni + $kasMasukHariIni - $kasKeluarHariIni;

        // 2. Data Grafik Harian (7 Hari Terakhir)
        $labelHarian = [];
        $dataHarian = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labelHarian[] = $date->translatedFormat('d M');

            $parkirToilet = Transaction::whereDate('exit_time', $date)
                                       ->sum(DB::raw('fee + IFNULL(toilet_fee, 0)'));
            $kasMasuk = FinancialReport::whereDate('tanggal', $date)
                                       ->where('jenis', 'pemasukan')->sum('nominal');
            $kasKeluar = FinancialReport::whereDate('tanggal', $date)
                                       ->where('jenis', 'pengeluaran')->sum('nominal');

            $dataHarian[] = $parkirToilet + $kasMasuk - $kasKeluar;
        }

        // 3. Data Grafik Bulanan (6 Bulan Terakhir)
        $labelBulanan = [];
        $dataBulanan = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $labelBulanan[] = $date->translatedFormat('M Y');

            $parkirToilet = Transaction::whereMonth('exit_time', $date->month)
                                ->whereYear('exit_time', $date->year)
                                ->sum(DB::raw('fee + IFNULL(toilet_fee, 0)'));
            $kasMasuk = FinancialReport::whereMonth('tanggal', $date->month)
                                ->whereYear('tanggal', $date->year)
                                ->where('jenis', 'pemasukan')->sum('nominal');
            $kasKeluar = FinancialReport::whereMonth('tanggal', $date->month)
                                ->whereYear('tanggal', $date->year)
                                ->where('jenis', 'pengeluaran')->sum('nominal');

            $dataBulanan[] = $parkirToilet + $kasMasuk - $kasKeluar;
        }

        $chartData = [
            'harian' => ['labels' => $labelHarian, 'data' => $dataHarian],
            'bulanan' => ['labels' => $labelBulanan, 'data' => $dataBulanan],
        ];

        // 4. Tabel Aktivitas Terbaru (Dibatasi 5 saja untuk publik)
        $recent_transactions = Transaction::latest()->take(5)->get();

        return view('public_dashboard', compact('data', 'chartData', 'recent_transactions'));
    }
}
