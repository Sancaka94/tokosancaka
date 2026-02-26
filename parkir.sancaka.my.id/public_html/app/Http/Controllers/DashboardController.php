<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\User;
use App\Models\FinancialReport; // Tambahkan ini
use Illuminate\Support\Facades\DB;
use Carbon\Carbon; // Tambahkan ini

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $today = Carbon::today();
        $now = Carbon::now();

        // =========================================================
        // 1. STATISTIK HARI INI (Parkir + Toilet + Kas)
        // =========================================================
        $parkirToiletHariIni = Transaction::whereDate('exit_time', $today)
                                     ->sum(DB::raw('fee + IFNULL(toilet_fee, 0)'));
        $kasMasukHariIni = FinancialReport::whereDate('tanggal', $today)
                                    ->where('jenis', 'pemasukan')->sum('nominal');
        $kasKeluarHariIni = FinancialReport::whereDate('tanggal', $today)
                                    ->where('jenis', 'pengeluaran')->sum('nominal');

        // Pisahkan kotor dan bersih agar perhitungan gaji tepat
        $pendapatanKotorHariIni = $parkirToiletHariIni + $kasMasukHariIni;
        $pendapatanBersihHariIni = $pendapatanKotorHariIni - $kasKeluarHariIni;

        $data = [
            'motor_masuk' => Transaction::where('vehicle_type', 'motor')->whereDate('entry_time', $today)->count(),
            'mobil_masuk' => Transaction::where('vehicle_type', 'mobil')->whereDate('entry_time', $today)->count(),
            'total_pendapatan' => $pendapatanBersihHariIni,
        ];

        // =========================================================
        // 2. PENDAPATAN BULAN INI VS BULAN LALU
        // =========================================================
        $parkirBulanIni = Transaction::whereMonth('exit_time', $now->month)
                                ->whereYear('exit_time', $now->year)
                                ->sum(DB::raw('fee + IFNULL(toilet_fee, 0)'));
        $kasMasukBulanIni = FinancialReport::whereMonth('tanggal', $now->month)
                                ->whereYear('tanggal', $now->year)->where('jenis', 'pemasukan')->sum('nominal');
        $kasKeluarBulanIni = FinancialReport::whereMonth('tanggal', $now->month)
                                ->whereYear('tanggal', $now->year)->where('jenis', 'pengeluaran')->sum('nominal');
        $pendapatanBulanIni = $parkirBulanIni + $kasMasukBulanIni - $kasKeluarBulanIni;

        $bulanLalu = $now->copy()->subMonth();
        $parkirBulanLalu = Transaction::whereMonth('exit_time', $bulanLalu->month)
                                ->whereYear('exit_time', $bulanLalu->year)
                                ->sum(DB::raw('fee + IFNULL(toilet_fee, 0)'));
        $kasMasukBulanLalu = FinancialReport::whereMonth('tanggal', $bulanLalu->month)
                                ->whereYear('tanggal', $bulanLalu->year)->where('jenis', 'pemasukan')->sum('nominal');
        $kasKeluarBulanLalu = FinancialReport::whereMonth('tanggal', $bulanLalu->month)
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
        // 5. DATA AKTIVITAS TERBARU
        // =========================================================
        $recent_transactions = Transaction::with('operator')->latest()->take(6)->get();

        // =========================================================
        // 6. RINGKASAN BUKU KAS
        // =========================================================
        $totalPemasukanKas = FinancialReport::where('jenis', 'pemasukan')->sum('nominal');
        $totalPengeluaranKas = FinancialReport::where('jenis', 'pengeluaran')->sum('nominal');
        $saldoKas = $totalPemasukanKas - $totalPengeluaranKas;

        $recent_financials = FinancialReport::latest('tanggal')->take(6)->get();

        // =========================================================
        // 7. ESTIMASI GAJI PEGAWAI (HARI INI)
        // =========================================================
        $operators = User::where('role', 'operator')->get();

        // Ambil data gaji hari ini agar tidak berulang kali query di dalam loop
        $laporanGajiHariIni = FinancialReport::whereDate('tanggal', $today)
                                ->where('kategori', 'Gaji Pegawai')->get();

        $employeeSalaries = $operators->map(function ($operator) use ($pendapatanKotorHariIni, $laporanGajiHariIni) {

            // Cek apakah sudah dibayar manual hari ini
            $gajiManual = $laporanGajiHariIni->filter(function ($report) use ($operator) {
                return stripos($report->keterangan, $operator->name) !== false;
            })->sum('nominal');

            if ($gajiManual > 0) {
                $earned = $gajiManual;
                $statusGaji = 'Sudah Dibayar (Manual)';
            } else {
                // Jika belum, hitung dari PENDAPATAN KOTOR
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
                'status' => $statusGaji // Jika ingin ditampilkan statusnya
            ];
        });

        return view('dashboard.index', compact(
            'data', 'user', 'recent_transactions', 'chartData',
            'totalPemasukanKas', 'totalPengeluaranKas', 'saldoKas', 'recent_financials', 'employeeSalaries'
        ));
    }

   public function harian(Request $request)
    {
        $tanggal = $request->tanggal ?? today()->toDateString();

        // 1. AMBIL DATA TRANSAKSI PARKIR (SISTEM TIKET)
        $transactions = Transaction::with('operator')->whereDate('exit_time', $tanggal)->latest()->paginate(20);

        $rekap = Transaction::select(
            DB::raw('SUM(fee + IFNULL(toilet_fee, 0)) as total_semua')
        )->whereDate('exit_time', $tanggal)->first();

        $total = $rekap->total_semua ?? 0;

        // 2. AMBIL DATA KAS MANUAL (INPUTAN GELONDONGAN)
        $kasManual = \App\Models\FinancialReport::whereDate('tanggal', $tanggal)->latest()->get();
        $totalPemasukanManual = $kasManual->where('jenis', 'pemasukan')->sum('nominal');
        $totalPengeluaranManual = $kasManual->where('jenis', 'pengeluaran')->sum('nominal');

        return view('laporan.harian', compact(
            'transactions', 'tanggal', 'total',
            'kasManual', 'totalPemasukanManual', 'totalPengeluaranManual'
        ));
    }

   public function bulanan(Request $request)
    {
        $bulan = $request->bulan ?? date('m');
        $tahun = $request->tahun ?? date('Y');

        // 1. AMBIL DATA TRANSAKSI PARKIR (SISTEM TIKET)
        $transactions = Transaction::with('operator')
                                   ->whereMonth('exit_time', $bulan)
                                   ->whereYear('exit_time', $tahun)
                                   ->latest('exit_time')
                                   ->paginate(50);

        $rekap = Transaction::select(
            DB::raw('SUM(fee + IFNULL(toilet_fee, 0)) as total_semua')
        )->whereMonth('exit_time', $bulan)
         ->whereYear('exit_time', $tahun)->first();

        $total = $rekap->total_semua ?? 0;

        // 2. AMBIL DATA KAS MANUAL (INPUTAN GELONDONGAN)
        $kasManual = \App\Models\FinancialReport::whereMonth('tanggal', $bulan)
                                                ->whereYear('tanggal', $tahun)
                                                ->orderBy('tanggal', 'desc')
                                                ->get();
        $totalPemasukanManual = $kasManual->where('jenis', 'pemasukan')->sum('nominal');
        $totalPengeluaranManual = $kasManual->where('jenis', 'pengeluaran')->sum('nominal');

        return view('laporan.bulanan', compact(
            'transactions', 'bulan', 'tahun', 'total',
            'kasManual', 'totalPemasukanManual', 'totalPengeluaranManual'
        ));
    }

    public function triwulan(Request $request)
    {
        $kuartal = $request->kuartal ?? 1;
        $tahun = $request->tahun ?? date('Y');

        $startMonth = ($kuartal - 1) * 3 + 1;
        $endMonth = $startMonth + 2;

        // 1. AMBIL DATA TRANSAKSI PARKIR (SISTEM TIKET)
        $transactions = Transaction::with('operator')
                                   ->whereMonth('exit_time', '>=', $startMonth)
                                   ->whereMonth('exit_time', '<=', $endMonth)
                                   ->whereYear('exit_time', $tahun)
                                   ->latest('exit_time')
                                   ->paginate(50);

        $rekap = Transaction::select(
            DB::raw('SUM(fee + IFNULL(toilet_fee, 0)) as total_semua')
        )->whereMonth('exit_time', '>=', $startMonth)
         ->whereMonth('exit_time', '<=', $endMonth)
         ->whereYear('exit_time', $tahun)->first();

        $total = $rekap->total_semua ?? 0;

        // 2. AMBIL DATA KAS MANUAL (INPUTAN GELONDONGAN)
        $kasManual = \App\Models\FinancialReport::whereMonth('tanggal', '>=', $startMonth)
                                                ->whereMonth('tanggal', '<=', $endMonth)
                                                ->whereYear('tanggal', $tahun)
                                                ->orderBy('tanggal', 'desc')
                                                ->get();
        $totalPemasukanManual = $kasManual->where('jenis', 'pemasukan')->sum('nominal');
        $totalPengeluaranManual = $kasManual->where('jenis', 'pengeluaran')->sum('nominal');

        return view('laporan.triwulan', compact(
            'transactions', 'kuartal', 'tahun', 'total',
            'kasManual', 'totalPemasukanManual', 'totalPengeluaranManual'
        ));
    }
}
