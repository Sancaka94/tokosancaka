<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\FinancialReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PublicDashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $lastMonth = $now->copy()->subMonth();

        // =========================================================
        // 1. STATISTIK HARI INI & KEMARIN (Parkir + Toilet + Kas)
        // =========================================================

        // Hari Ini
        $parkirHariIni = Transaction::whereDate('exit_time', $today)
                                     ->sum(DB::raw('fee + IFNULL(toilet_fee, 0)'));
        $kasMasukHariIni = FinancialReport::whereDate('tanggal', $today)
                                   ->where('jenis', 'pemasukan')->sum('nominal');
        $kasKeluarHariIni = FinancialReport::whereDate('tanggal', $today)
                                    ->where('jenis', 'pengeluaran')->sum('nominal');

        $pendapatanKotorHariIni = $parkirHariIni + $kasMasukHariIni;
        $pendapatanBersihHariIni = $pendapatanKotorHariIni - $kasKeluarHariIni;

        // Kemarin
        $parkirKemarin = Transaction::whereDate('exit_time', $yesterday)
                                     ->sum(DB::raw('fee + IFNULL(toilet_fee, 0)'));
        $kasMasukKemarin = FinancialReport::whereDate('tanggal', $yesterday)
                                   ->where('jenis', 'pemasukan')->sum('nominal');
        $kasKeluarKemarin = FinancialReport::whereDate('tanggal', $yesterday)
                                    ->where('jenis', 'pengeluaran')->sum('nominal');

        $pendapatanBersihKemarin = ($parkirKemarin + $kasMasukKemarin) - $kasKeluarKemarin;

        $data = [
            'motor_masuk' => Transaction::where('vehicle_type', 'motor')->whereDate('entry_time', $today)->count(),
            'mobil_masuk' => Transaction::where('vehicle_type', 'mobil')->whereDate('entry_time', $today)->count(),
            'total_pendapatan' => $pendapatanBersihHariIni,
            'pendapatan_kemarin' => $pendapatanBersihKemarin,
        ];

        // =========================================================
        // 2. PENDAPATAN BULAN INI & BULAN KEMARIN
        // =========================================================

        // Bulan Ini
        $parkirBulanIni = Transaction::whereMonth('exit_time', $now->month)
                                ->whereYear('exit_time', $now->year)
                                ->sum(DB::raw('fee + IFNULL(toilet_fee, 0)'));
        $kasMasukBulanIni = FinancialReport::whereMonth('tanggal', $now->month)
                                ->whereYear('tanggal', $now->year)->where('jenis', 'pemasukan')->sum('nominal');
        $kasKeluarBulanIni = FinancialReport::whereMonth('tanggal', $now->month)
                                ->whereYear('tanggal', $now->year)->where('jenis', 'pengeluaran')->sum('nominal');

        $data['pendapatan_bulan_ini'] = ($parkirBulanIni + $kasMasukBulanIni) - $kasKeluarBulanIni;

        // Bulan Kemarin (Ditambahkan agar UI Dashboard bisa menampilkan trend Naik/Turun)
        $parkirBulanLalu = Transaction::whereMonth('exit_time', $lastMonth->month)
                                ->whereYear('exit_time', $lastMonth->year)
                                ->sum(DB::raw('fee + IFNULL(toilet_fee, 0)'));
        $kasMasukBulanLalu = FinancialReport::whereMonth('tanggal', $lastMonth->month)
                                ->whereYear('tanggal', $lastMonth->year)->where('jenis', 'pemasukan')->sum('nominal');
        $kasKeluarBulanLalu = FinancialReport::whereMonth('tanggal', $lastMonth->month)
                                ->whereYear('tanggal', $lastMonth->year)->where('jenis', 'pengeluaran')->sum('nominal');

        $data['pendapatan_bulan_kemarin'] = ($parkirBulanLalu + $kasMasukBulanLalu) - $kasKeluarBulanLalu;

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
        $recent_transactions = Transaction::latest()->paginate(6, ['*'], 'parkir_page');

        $totalPemasukanKas = FinancialReport::where('jenis', 'pemasukan')->sum('nominal');
        $totalPengeluaranKas = FinancialReport::where('jenis', 'pengeluaran')->sum('nominal');
        $saldoKas = $totalPemasukanKas - $totalPengeluaranKas;

        $recent_financials = FinancialReport::latest('tanggal')->paginate(6, ['*'], 'kas_page');

        // =========================================================
        // 6. LOGIKA GAJI: CEK MANUAL VS OTOMATIS
        // =========================================================
        $operators = User::where('role', 'operator')->get();

        // Optimasi: Ambil semua laporan gaji hari ini di awal untuk mencegah N+1 Query
        $laporanGajiHariIni = FinancialReport::whereDate('tanggal', $today)
            ->where('kategori', 'Gaji Pegawai')
            ->get();

        $employeeSalaries = $operators->map(function ($operator) use ($pendapatanKotorHariIni, $laporanGajiHariIni) {

            // A. Cari apakah nama pegawai ada di keterangan laporan gaji hari ini
            $gajiManual = $laporanGajiHariIni->filter(function ($report) use ($operator) {
                return stripos($report->keterangan, $operator->name) !== false;
            })->sum('nominal');

            if ($gajiManual > 0) {
                // Jika ditemukan input manual, gunakan angka tersebut
                $earned = $gajiManual;
                $statusGaji = 'Sudah Dibayar (Manual)';
            } else {
                // B. Jika belum ada, hitung otomatis dari PENDAPATAN KOTOR (Gross)
                if ($operator->salary_type == 'percentage') {
                    $earned = ($operator->salary_amount / 100) * $pendapatanKotorHariIni;
                    $statusGaji = 'Estimasi (' . (float)$operator->salary_amount . '%)';
                } else {
                    $earned = $operator->salary_amount;
                    $statusGaji = 'Estimasi (Flat)';
                }
            }

            return (object)[
                'name'   => $operator->name,
                'type'   => $operator->salary_type,
                'amount' => $operator->salary_amount,
                'earned' => $earned,
                'status' => $statusGaji
            ];
        });

        return view('public_dashboard', compact(
            'data', 'chartData', 'recent_transactions',
            'totalPemasukanKas', 'totalPengeluaranKas', 'saldoKas', 'recent_financials', 'employeeSalaries'
        ));
    }
}
