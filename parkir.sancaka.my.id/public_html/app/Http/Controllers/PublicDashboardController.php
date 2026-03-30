<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\FinancialReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class PublicDashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $lastMonth = $now->copy()->subMonth();

        // 1. DATA KENDARAAN HARI INI
        $motorHariIni = Transaction::where('vehicle_type', 'motor')->whereDate('entry_time', $today)->count();
        $mobilHariIni = Transaction::where('vehicle_type', 'mobil')->whereDate('entry_time', $today)->count();
        $sepedaBiasaHariIni = Transaction::whereDate('entry_time', $today)->where('plate_number', 'LIKE', 'SPD-%')->count();
        $sepedaListrikHariIni = Transaction::whereDate('entry_time', $today)->where('plate_number', 'LIKE', 'SPL-%')->count();
        $pegawaiRsudHariIni = Transaction::whereDate('entry_time', $today)->where('plate_number', 'LIKE', 'RSUD-%')->count();

        // 2. RUMUS DB
        $rumusParkirMurni = DB::raw('(CASE WHEN fee IS NOT NULL AND fee > 0 THEN fee WHEN vehicle_type = "mobil" THEN 5000 ELSE 3000 END)');
        $rumusToilet = DB::raw('IFNULL(toilet_fee, 0)');

        // =========================================================
        // 3. LOGIKA MASTER: PARKIR (DIBAGI 2) vs UTUH (TOILET/NGINAP)
        // =========================================================

        // --- HARI INI ---
        $p_today = Transaction::whereDate('entry_time', $today)->sum($rumusParkirMurni);
        $t_today = Transaction::whereDate('entry_time', $today)->sum($rumusToilet);

        $kasParkir_today = FinancialReport::whereDate('tanggal', $today)->where('jenis', 'pemasukan')->where('kategori', 'Parkiran')->sum('nominal');
        $kasUtuh_today = FinancialReport::whereDate('tanggal', $today)->where('jenis', 'pemasukan')->where('kategori', '!=', 'Parkiran')->sum('nominal');
        $kk_today = FinancialReport::whereDate('tanggal', $today)->where('jenis', 'pengeluaran')->sum('nominal');

        $omzetHariIni = $p_today + $t_today + $kasParkir_today + $kasUtuh_today;
        // RUMUS FIX: (Parkir Tiket + Kas Parkiran) dibagi 2. (Toilet + Kas Nginap dll) ditambah 100%.
        $profitHariIni = (($p_today + $kasParkir_today) / 2) + $t_today + $kasUtuh_today - $kk_today;

        // --- KEMARIN ---
        $p_yest = Transaction::whereDate('entry_time', $yesterday)->sum($rumusParkirMurni);
        $t_yest = Transaction::whereDate('entry_time', $yesterday)->sum($rumusToilet);

        $kasParkir_yest = FinancialReport::whereDate('tanggal', $yesterday)->where('jenis', 'pemasukan')->where('kategori', 'Parkiran')->sum('nominal');
        $kasUtuh_yest = FinancialReport::whereDate('tanggal', $yesterday)->where('jenis', 'pemasukan')->where('kategori', '!=', 'Parkiran')->sum('nominal');
        $kk_yest = FinancialReport::whereDate('tanggal', $yesterday)->where('jenis', 'pengeluaran')->sum('nominal');

        $omzetKemarin = $p_yest + $t_yest + $kasParkir_yest + $kasUtuh_yest;
        $profitKemarin = (($p_yest + $kasParkir_yest) / 2) + $t_yest + $kasUtuh_yest - $kk_yest;

        // --- BULAN INI ---
        $p_month = Transaction::whereMonth('entry_time', $now->month)->whereYear('entry_time', $now->year)->sum($rumusParkirMurni);
        $t_month = Transaction::whereMonth('entry_time', $now->month)->whereYear('entry_time', $now->year)->sum($rumusToilet);

        $kasParkir_month = FinancialReport::whereMonth('tanggal', $now->month)->whereYear('tanggal', $now->year)->where('jenis', 'pemasukan')->where('kategori', 'Parkiran')->sum('nominal');
        $kasUtuh_month = FinancialReport::whereMonth('tanggal', $now->month)->whereYear('tanggal', $now->year)->where('jenis', 'pemasukan')->where('kategori', '!=', 'Parkiran')->sum('nominal');
        $kk_month = FinancialReport::whereMonth('tanggal', $now->month)->whereYear('tanggal', $now->year)->where('jenis', 'pengeluaran')->sum('nominal');

        $omzetBulanIni = $p_month + $t_month + $kasParkir_month + $kasUtuh_month;
        $profitBulanIni = (($p_month + $kasParkir_month) / 2) + $t_month + $kasUtuh_month - $kk_month;

        // --- BULAN LALU ---
        $p_lastM = Transaction::whereMonth('entry_time', $lastMonth->month)->whereYear('entry_time', $lastMonth->year)->sum($rumusParkirMurni);
        $t_lastM = Transaction::whereMonth('entry_time', $lastMonth->month)->whereYear('entry_time', $lastMonth->year)->sum($rumusToilet);

        $kasParkir_lastM = FinancialReport::whereMonth('tanggal', $lastMonth->month)->whereYear('tanggal', $lastMonth->year)->where('jenis', 'pemasukan')->where('kategori', 'Parkiran')->sum('nominal');
        $kasUtuh_lastM = FinancialReport::whereMonth('tanggal', $lastMonth->month)->whereYear('tanggal', $lastMonth->year)->where('jenis', 'pemasukan')->where('kategori', '!=', 'Parkiran')->sum('nominal');
        $kk_lastM = FinancialReport::whereMonth('tanggal', $lastMonth->month)->whereYear('tanggal', $lastMonth->year)->where('jenis', 'pengeluaran')->sum('nominal');

        $omzetBulanLalu = $p_lastM + $t_lastM + $kasParkir_lastM + $kasUtuh_lastM;
        $profitBulanLalu = (($p_lastM + $kasParkir_lastM) / 2) + $t_lastM + $kasUtuh_lastM - $kk_lastM;

        $p_7hari = Transaction::whereDate('entry_time', '>=', Carbon::today()->subDays(6))->sum($rumusParkirMurni);
        $kasParkir_7hari = FinancialReport::whereDate('tanggal', '>=', Carbon::today()->subDays(6))->where('jenis', 'pemasukan')->where('kategori', 'Parkiran')->sum('nominal');

        $data = [
            'motor_masuk' => $motorHariIni,
            'mobil_masuk' => $mobilHariIni,
            'total_pendapatan' => $profitHariIni,
            'pendapatan_kemarin' => $profitKemarin,
            'pendapatan_bulan_ini' => $profitBulanIni,
            'pendapatan_bulan_kemarin' => $profitBulanLalu,
            'parkir_hari_ini' => $p_today + $kasParkir_today,
            'parkir_kemarin' => $p_yest + $kasParkir_yest,
            'parkir_7_hari' => $p_7hari + $kasParkir_7hari,
            'parkir_bulan_ini' => $p_month + $kasParkir_month,
        ];

        // =========================================================
        // 4. GRAFIK (LOGIKA DISAMAKAN)
        // =========================================================
        $labelHarian = []; $dataHarian = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labelHarian[] = $date->translatedFormat('d M');
            $p = Transaction::whereDate('entry_time', $date)->sum($rumusParkirMurni);
            $t = Transaction::whereDate('entry_time', $date)->sum($rumusToilet);
            $kp = FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pemasukan')->where('kategori', 'Parkiran')->sum('nominal');
            $ku = FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pemasukan')->where('kategori', '!=', 'Parkiran')->sum('nominal');
            $kk = FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pengeluaran')->sum('nominal');

            $dataHarian[] = (($p + $kp) / 2) + $t + $ku - $kk;
        }

        $labelBulanan = []; $dataBulanan = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $labelBulanan[] = $date->translatedFormat('M Y');
            $p = Transaction::whereMonth('entry_time', $date->month)->whereYear('entry_time', $date->year)->sum($rumusParkirMurni);
            $t = Transaction::whereMonth('entry_time', $date->month)->whereYear('entry_time', $date->year)->sum($rumusToilet);
            $kp = FinancialReport::whereMonth('tanggal', $date->month)->whereYear('tanggal', $date->year)->where('jenis', 'pemasukan')->where('kategori', 'Parkiran')->sum('nominal');
            $ku = FinancialReport::whereMonth('tanggal', $date->month)->whereYear('tanggal', $date->year)->where('jenis', 'pemasukan')->where('kategori', '!=', 'Parkiran')->sum('nominal');
            $kk = FinancialReport::whereMonth('tanggal', $date->month)->whereYear('tanggal', $date->year)->where('jenis', 'pengeluaran')->sum('nominal');

            $dataBulanan[] = (($p + $kp) / 2) + $t + $ku - $kk;
        }
        $chartData = ['harian' => ['labels' => $labelHarian, 'data' => $dataHarian], 'bulanan' => ['labels' => $labelBulanan, 'data' => $dataBulanan]];

        // =========================================================
        // 5. AKTIVITAS & KAS
        // =========================================================
        $recent_transactions = Transaction::latest('entry_time')->paginate(6, ['*'], 'parkir_page');
        $recent_financials = FinancialReport::latest('tanggal')->paginate(6, ['*'], 'kas_page');
        $totalPemasukanKas = FinancialReport::where('jenis', 'pemasukan')->sum('nominal');
        $totalPengeluaranKas = FinancialReport::where('jenis', 'pengeluaran')->sum('nominal');
        $saldoKas = $totalPemasukanKas - $totalPengeluaranKas;

        // Total akumulasi Toilet (Gabungan form kas manual & form parkir)
        $toiletDariKas = FinancialReport::where('kategori', 'LIKE', '%toilet%')->where('jenis', 'pemasukan')->sum('nominal');
        $toiletDariParkir = Transaction::sum('toilet_fee');
        $totalPemasukanToilet = $toiletDariKas + $toiletDariParkir;

        // Total akumulasi Nginap
        $totalPemasukanNginap = FinancialReport::where('kategori', 'LIKE', '%nginap%')->where('jenis', 'pemasukan')->sum('nominal');

        // =========================================================
        // 6. ESTIMASI GAJI PEGAWAI (CARD ATAS)
        // =========================================================

        // Menghitung omzet khusus untuk dasar gaji (tanpa toilet)
        $kasNginap_today = FinancialReport::whereDate('tanggal', $today)
            ->where('jenis', 'pemasukan')
            ->where('kategori', 'LIKE', '%nginap%')
            ->sum('nominal');

        $omzetGajiHariIni = $p_today + $kasParkir_today + $kasNginap_today;

        $operators = User::where('role', 'operator')->get();
        $laporanGajiHariIni = FinancialReport::whereDate('tanggal', $today)->where('kategori', 'Gaji Pegawai')->get();

        $employeeSalaries = $operators->map(function ($operator) use ($omzetGajiHariIni, $laporanGajiHariIni) {
            $gajiManual = $laporanGajiHariIni->filter(function ($report) use ($operator) {
                return stripos($report->keterangan, $operator->name) !== false;
            })->sum('nominal');

            if ($gajiManual > 0) {
                $earned = $gajiManual;
                $statusGaji = 'Sudah Dibayar (Manual)';
            } else {
                // Kalkulasi memakai $omzetGajiHariIni murni tanpa toilet
                $earned = $operator->salary_type == 'percentage' ? ($operator->salary_amount / 100) * $omzetGajiHariIni : $operator->salary_amount;
                $statusGaji = 'Estimasi Otomatis';
            }
            return (object)['name' => $operator->name, 'type' => $operator->salary_type, 'amount' => $operator->salary_amount, 'earned' => $earned, 'status' => $statusGaji];
        });

        // =========================================================
        // 7. TABEL REVENUE & GAJI (LOGIKA DISAMAKAN)
        // =========================================================
        $datesTrx = Transaction::selectRaw('DATE(entry_time) as date')->pluck('date')->toArray();
        $datesFin = FinancialReport::selectRaw('DATE(tanggal) as date')->pluck('date')->toArray();
        $allDates = array_unique(array_merge($datesTrx, $datesFin));
        rsort($allDates);

        $page = request()->get('revenue_page', 1);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $pagedDates = array_slice($allDates, $offset, $perPage);

        $revenue_data = [];
        $riwayat_gaji = [];

        foreach($pagedDates as $date) {
            $p = Transaction::whereDate('entry_time', $date)->sum($rumusParkirMurni);
            $t = Transaction::whereDate('entry_time', $date)->sum($rumusToilet);
            $c = Transaction::whereDate('entry_time', $date)->count();

            $kp = FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pemasukan')->where('kategori', 'Parkiran')->sum('nominal');
            $ku = FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pemasukan')->where('kategori', '!=', 'Parkiran')->sum('nominal');
            $kk = FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pengeluaran')->sum('nominal');

            // Ambil kas nginap spesifik per tanggal loop untuk perhitungan gaji historis
            $kasNginapDate = FinancialReport::whereDate('tanggal', $date)
                ->where('jenis', 'pemasukan')
                ->where('kategori', 'LIKE', '%nginap%')
                ->sum('nominal');

            $omzetDate = $p + $t + $kp + $ku;
            $profitDate = (($p + $kp) / 2) + $t + $ku - $kk;

            // Omzet gaji tanpa toilet
            $omzetGajiDate = $p + $kp + $kasNginapDate;

            $revenue_data[] = (object)[
                'tanggal' => $date,
                'total_kendaraan' => $c,
                'total_omzet' => $omzetDate,
                'total_profit_bersih' => $profitDate,
            ];

            $gaji_per_pegawai = [];
            foreach ($operators as $op) {
                $gajiManual = FinancialReport::whereDate('tanggal', $date)->where('kategori', 'Gaji Pegawai')->where('keterangan', 'LIKE', '%' . $op->name . '%')->sum('nominal');
                if ($gajiManual > 0) {
                    $earned = $gajiManual; $status = 'Manual';
                } else {
                    // Kalkulasi memakai $omzetGajiDate murni tanpa toilet
                    $earned = $op->salary_type == 'percentage' ? ($op->salary_amount / 100) * $omzetGajiDate : $op->salary_amount;
                    $status = 'Otomatis';
                }
                $gaji_per_pegawai[$op->name] = ['earned' => $earned, 'status' => $status];
            }

            $riwayat_gaji[] = (object)[
                'tanggal' => $date,
                'pendapatan_kotor' => $omzetDate,
                'gaji_pegawai' => $gaji_per_pegawai
            ];
        }

        $revenue_transactions = new LengthAwarePaginator(
            $revenue_data, count($allDates), $perPage, $page, ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('public_dashboard', compact(
            'data', 'chartData', 'recent_transactions', 'revenue_transactions',
            'totalPemasukanKas', 'totalPengeluaranKas', 'saldoKas', 'recent_financials', 'employeeSalaries',
            'sepedaBiasaHariIni', 'sepedaListrikHariIni', 'pegawaiRsudHariIni', 'riwayat_gaji', 'operators',
            'omzetHariIni', 'omzetKemarin', 'omzetBulanIni',
            'totalPemasukanToilet', 'totalPemasukanNginap'
        ));
    }
}
