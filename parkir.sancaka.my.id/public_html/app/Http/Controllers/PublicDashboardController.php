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
        // =========================================================
        // 1. STATISTIK HARI INI & KEMARIN (Parkir + Toilet + Kas)
        // =========================================================
        // Hari Ini
        $parkirToiletHariIni = Transaction::whereDate('exit_time', today())
                                     ->sum(DB::raw('fee + IFNULL(toilet_fee, 0)'));
        $kasMasukHariIni = FinancialReport::whereDate('tanggal', today())
                                   ->where('jenis', 'pemasukan')->sum('nominal');
        $kasKeluarHariIni = FinancialReport::whereDate('tanggal', today())
                                    ->where('jenis', 'pengeluaran')->sum('nominal');

        // Kemarin
        $parkirKemarin = Transaction::whereDate('exit_time', Carbon::yesterday())
                                     ->sum(DB::raw('fee + IFNULL(toilet_fee, 0)'));
        $kasMasukKemarin = FinancialReport::whereDate('tanggal', Carbon::yesterday())
                                   ->where('jenis', 'pemasukan')->sum('nominal');
        $kasKeluarKemarin = FinancialReport::whereDate('tanggal', Carbon::yesterday())
                                    ->where('jenis', 'pengeluaran')->sum('nominal');

        $data = [
            'motor_masuk' => Transaction::where('vehicle_type', 'motor')->whereDate('entry_time', today())->count(),
            'mobil_masuk' => Transaction::where('vehicle_type', 'mobil')->whereDate('entry_time', today())->count(),
            'total_pendapatan' => $parkirToiletHariIni + $kasMasukHariIni - $kasKeluarHariIni,
            'pendapatan_kemarin' => $parkirKemarin + $kasMasukKemarin - $kasKeluarKemarin,
        ];

        // =========================================================
        // 2. PENDAPATAN BULAN INI
        // =========================================================
        $parkirBulanIni = Transaction::whereMonth('exit_time', date('m'))
                                ->whereYear('exit_time', date('Y'))
                                ->sum(DB::raw('fee + IFNULL(toilet_fee, 0)'));
        $kasMasukBulanIni = FinancialReport::whereMonth('tanggal', date('m'))
                                ->whereYear('tanggal', date('Y'))->where('jenis', 'pemasukan')->sum('nominal');
        $kasKeluarBulanIni = FinancialReport::whereMonth('tanggal', date('m'))
                                ->whereYear('tanggal', date('Y'))->where('jenis', 'pengeluaran')->sum('nominal');

        $data['pendapatan_bulan_ini'] = $parkirBulanIni + $kasMasukBulanIni - $kasKeluarBulanIni;

        // =========================================================
        // 3. GRAFIK HARIAN (7 HARI TERAKHIR)
        // =========================================================
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

        // =========================================================
        // 4. GRAFIK BULANAN (6 BULAN TERAKHIR)
        // =========================================================
        $labelBulanan = [];
        $dataBulanan = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $labelBulanan[] = $date->translatedFormat('M Y');

            $parkirToilet = Transaction::whereMonth('exit_time', $date->month)
                                ->whereYear('exit_time', $date->year)
                                ->sum(DB::raw('fee + IFNULL(toilet_fee, 0)'));
            $kasMasuk = FinancialReport::whereMonth('tanggal', $date->month)
                                ->whereYear('tanggal', $date->year)->where('jenis', 'pemasukan')->sum('nominal');
            $kasKeluar = FinancialReport::whereMonth('tanggal', $date->month)
                                ->whereYear('tanggal', $date->year)->where('jenis', 'pengeluaran')->sum('nominal');

            $dataBulanan[] = $parkirToilet + $kasMasuk - $kasKeluar;
        }

        $chartData = [
            'harian' => ['labels' => $labelHarian, 'data' => $dataHarian],
            'bulanan' => ['labels' => $labelBulanan, 'data' => $dataBulanan],
        ];

        // =========================================================
        // 5. DATA AKTIVITAS TERBARU & KAS (DENGAN PAGINATION)
        // =========================================================

        // Paginasi untuk tabel parkir (5 baris per halaman, parameter URL: ?parkir_page=...)
        $recent_transactions = Transaction::latest()->paginate(5, ['*'], 'parkir_page');

        // Ringkasan Kas Manual
        $totalPemasukanKas = FinancialReport::where('jenis', 'pemasukan')->sum('nominal');
        $totalPengeluaranKas = FinancialReport::where('jenis', 'pengeluaran')->sum('nominal');
        $saldoKas = $totalPemasukanKas - $totalPengeluaranKas;

        // Paginasi untuk tabel kas manual (5 baris per halaman, parameter URL: ?kas_page=...)
        $recent_financials = FinancialReport::latest('tanggal')->paginate(5, ['*'], 'kas_page');

        return view('public_dashboard', compact(
            'data', 'chartData', 'recent_transactions',
            'totalPemasukanKas', 'totalPengeluaranKas', 'saldoKas', 'recent_financials'
        ));
    }
}
