<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\FinancialReport;
use App\Models\User;
use App\Models\DashboardSetting; // PANGGIL MODEL SETTING BARU
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

        // 1. AMBIL PENGATURAN DARI DATABASE (DYNAMIC SETTINGS)
        // Jika tabel masih kosong, kita beri nilai default (true) agar tidak error
        $setting = DashboardSetting::first() ?? (object)[
            'parkir_dibagi_dua' => true,
            'nginap_dibagi_dua' => true,
            'toilet_masuk_profit' => true,
            'gaji_hanya_dari_parkir' => true,
            'tampil_card_harian' => true,
            'tampil_card_mingguan' => true,
            'tampil_card_bulanan' => true,
            'tampil_grafik_harian' => true,
            'tampil_grafik_bulanan' => true,
        ];

        // --- FUNGSI HELPER UNTUK MENGHITUNG PROFIT DINAMIS ---
        $hitungProfit = function($parkir, $nginap, $toilet, $lainnya) use ($setting) {
            $p = $setting->parkir_dibagi_dua ? ($parkir / 2) : $parkir;
            $n = $setting->nginap_dibagi_dua ? ($nginap / 2) : $nginap;
            $t = $setting->toilet_masuk_profit ? $toilet : 0;
            return $p + $n + $t + $lainnya;
        };

        // --- FUNGSI HELPER UNTUK MENGHITUNG DASAR GAJI DINAMIS ---
        $hitungDasarGaji = function($murniParkir, $totalOmzet) use ($setting) {
            return $setting->gaji_hanya_dari_parkir ? $murniParkir : $totalOmzet;
        };

        // 2. DATA KENDARAAN HARI INI
        $motorHariIni = Transaction::where('vehicle_type', 'motor')->whereDate('entry_time', $today)->count();
        $mobilHariIni = Transaction::where('vehicle_type', 'mobil')->whereDate('entry_time', $today)->count();
        $sepedaBiasaHariIni = Transaction::whereDate('entry_time', $today)->where('plate_number', 'LIKE', 'SPD-%')->count();
        $sepedaListrikHariIni = Transaction::whereDate('entry_time', $today)->where('plate_number', 'LIKE', 'SPL-%')->count();
        $pegawaiRsudHariIni = Transaction::whereDate('entry_time', $today)->where('plate_number', 'LIKE', 'RSUD-%')->count();

        // 3. RUMUS DB TRANSAKSI
        $rumusParkirMurni = DB::raw('(CASE WHEN fee IS NOT NULL AND fee > 0 THEN fee WHEN vehicle_type = "mobil" THEN 5000 ELSE 3000 END)');
        $rumusToilet = DB::raw('IFNULL(toilet_fee, 0)');

        // =========================================================
        // 4. LOGIKA PENGAMBILAN DATA (DIPISAH SECARA PRESISI)
        // =========================================================

        // --- HARI INI ---
        $trx_p_today = Transaction::whereDate('entry_time', $today)->sum($rumusParkirMurni);
        $trx_t_today = Transaction::whereDate('entry_time', $today)->sum($rumusToilet);
        $kas_p_today = FinancialReport::whereDate('tanggal', $today)->where('jenis', 'pemasukan')->where('kategori', 'Parkiran')->sum('nominal');
        $kas_t_today = FinancialReport::whereDate('tanggal', $today)->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%toilet%')->sum('nominal');
        $kas_n_today = FinancialReport::whereDate('tanggal', $today)->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%nginap%')->sum('nominal');
        $kas_lain_today = FinancialReport::whereDate('tanggal', $today)->where('jenis', 'pemasukan')
            ->where('kategori', '!=', 'Parkiran')->where('kategori', 'NOT LIKE', '%toilet%')->where('kategori', 'NOT LIKE', '%nginap%')->sum('nominal');
        $kk_today = FinancialReport::whereDate('tanggal', $today)->where('jenis', 'pengeluaran')->sum('nominal');

        $parkir_today = $trx_p_today + $kas_p_today;
        $omzetHariIni = $parkir_today + ($trx_t_today + $kas_t_today) + $kas_n_today + $kas_lain_today;
        $profitHariIni = $hitungProfit($parkir_today, $kas_n_today, ($trx_t_today + $kas_t_today), $kas_lain_today) - $kk_today;
        $omzetGajiHariIni = $hitungDasarGaji($parkir_today, $omzetHariIni);

        // --- KEMARIN ---
        $trx_p_yest = Transaction::whereDate('entry_time', $yesterday)->sum($rumusParkirMurni);
        $trx_t_yest = Transaction::whereDate('entry_time', $yesterday)->sum($rumusToilet);
        $kas_p_yest = FinancialReport::whereDate('tanggal', $yesterday)->where('jenis', 'pemasukan')->where('kategori', 'Parkiran')->sum('nominal');
        $kas_t_yest = FinancialReport::whereDate('tanggal', $yesterday)->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%toilet%')->sum('nominal');
        $kas_n_yest = FinancialReport::whereDate('tanggal', $yesterday)->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%nginap%')->sum('nominal');
        $kas_lain_yest = FinancialReport::whereDate('tanggal', $yesterday)->where('jenis', 'pemasukan')
            ->where('kategori', '!=', 'Parkiran')->where('kategori', 'NOT LIKE', '%toilet%')->where('kategori', 'NOT LIKE', '%nginap%')->sum('nominal');
        $kk_yest = FinancialReport::whereDate('tanggal', $yesterday)->where('jenis', 'pengeluaran')->sum('nominal');

        $parkir_yest = $trx_p_yest + $kas_p_yest;
        $omzetKemarin = $parkir_yest + ($trx_t_yest + $kas_t_yest) + $kas_n_yest + $kas_lain_yest;
        $profitKemarin = $hitungProfit($parkir_yest, $kas_n_yest, ($trx_t_yest + $kas_t_yest), $kas_lain_yest) - $kk_yest;

        // --- BULAN INI ---
        $trx_p_month = Transaction::whereMonth('entry_time', $now->month)->whereYear('entry_time', $now->year)->sum($rumusParkirMurni);
        $trx_t_month = Transaction::whereMonth('entry_time', $now->month)->whereYear('entry_time', $now->year)->sum($rumusToilet);
        $kas_p_month = FinancialReport::whereMonth('tanggal', $now->month)->whereYear('tanggal', $now->year)->where('jenis', 'pemasukan')->where('kategori', 'Parkiran')->sum('nominal');
        $kas_t_month = FinancialReport::whereMonth('tanggal', $now->month)->whereYear('tanggal', $now->year)->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%toilet%')->sum('nominal');
        $kas_n_month = FinancialReport::whereMonth('tanggal', $now->month)->whereYear('tanggal', $now->year)->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%nginap%')->sum('nominal');
        $kas_lain_month = FinancialReport::whereMonth('tanggal', $now->month)->whereYear('tanggal', $now->year)->where('jenis', 'pemasukan')
            ->where('kategori', '!=', 'Parkiran')->where('kategori', 'NOT LIKE', '%toilet%')->where('kategori', 'NOT LIKE', '%nginap%')->sum('nominal');
        $kk_month = FinancialReport::whereMonth('tanggal', $now->month)->whereYear('tanggal', $now->year)->where('jenis', 'pengeluaran')->sum('nominal');

        $parkir_month = $trx_p_month + $kas_p_month;
        $omzetBulanIni = $parkir_month + ($trx_t_month + $kas_t_month) + $kas_n_month + $kas_lain_month;
        $profitBulanIni = $hitungProfit($parkir_month, $kas_n_month, ($trx_t_month + $kas_t_month), $kas_lain_month) - $kk_month;

        // --- BULAN LALU ---
        $trx_p_lastM = Transaction::whereMonth('entry_time', $lastMonth->month)->whereYear('entry_time', $lastMonth->year)->sum($rumusParkirMurni);
        $trx_t_lastM = Transaction::whereMonth('entry_time', $lastMonth->month)->whereYear('entry_time', $lastMonth->year)->sum($rumusToilet);
        $kas_p_lastM = FinancialReport::whereMonth('tanggal', $lastMonth->month)->whereYear('tanggal', $lastMonth->year)->where('jenis', 'pemasukan')->where('kategori', 'Parkiran')->sum('nominal');
        $kas_t_lastM = FinancialReport::whereMonth('tanggal', $lastMonth->month)->whereYear('tanggal', $lastMonth->year)->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%toilet%')->sum('nominal');
        $kas_n_lastM = FinancialReport::whereMonth('tanggal', $lastMonth->month)->whereYear('tanggal', $lastMonth->year)->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%nginap%')->sum('nominal');
        $kas_lain_lastM = FinancialReport::whereMonth('tanggal', $lastMonth->month)->whereYear('tanggal', $lastMonth->year)->where('jenis', 'pemasukan')
            ->where('kategori', '!=', 'Parkiran')->where('kategori', 'NOT LIKE', '%toilet%')->where('kategori', 'NOT LIKE', '%nginap%')->sum('nominal');
        $kk_lastM = FinancialReport::whereMonth('tanggal', $lastMonth->month)->whereYear('tanggal', $lastMonth->year)->where('jenis', 'pengeluaran')->sum('nominal');

        $parkir_lastM = $trx_p_lastM + $kas_p_lastM;
        $profitBulanLalu = $hitungProfit($parkir_lastM, $kas_n_lastM, ($trx_t_lastM + $kas_t_lastM), $kas_lain_lastM) - $kk_lastM;

        // --- 7 HARI ---
        $p_7hari = Transaction::whereDate('entry_time', '>=', Carbon::today()->subDays(6))->sum($rumusParkirMurni);
        $kasParkir_7hari = FinancialReport::whereDate('tanggal', '>=', Carbon::today()->subDays(6))->where('jenis', 'pemasukan')->where('kategori', 'Parkiran')->sum('nominal');

        $data = [
            'motor_masuk' => $motorHariIni,
            'mobil_masuk' => $mobilHariIni,
            'total_pendapatan' => $profitHariIni,
            'pendapatan_kemarin' => $profitKemarin,
            'pendapatan_bulan_ini' => $profitBulanIni,
            'pendapatan_bulan_kemarin' => $profitBulanLalu,
            'parkir_hari_ini' => $parkir_today,
            'parkir_kemarin' => $parkir_yest,
            'parkir_7_hari' => $p_7hari + $kasParkir_7hari,
            'parkir_bulan_ini' => $parkir_month,
        ];

        // =========================================================
        // 5. GRAFIK DINAMIS
        // =========================================================
        $labelHarian = []; $dataHarian = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labelHarian[] = $date->translatedFormat('d M');

            $p = Transaction::whereDate('entry_time', $date)->sum($rumusParkirMurni);
            $t = Transaction::whereDate('entry_time', $date)->sum($rumusToilet);
            $kp = FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pemasukan')->where('kategori', 'Parkiran')->sum('nominal');
            $kt = FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%toilet%')->sum('nominal');
            $kn = FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%nginap%')->sum('nominal');
            $ku = FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pemasukan')
                ->where('kategori', '!=', 'Parkiran')->where('kategori', 'NOT LIKE', '%toilet%')->where('kategori', 'NOT LIKE', '%nginap%')->sum('nominal');
            $kk = FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pengeluaran')->sum('nominal');

            $dataHarian[] = $hitungProfit(($p + $kp), $kn, ($t + $kt), $ku) - $kk;
        }

        $labelBulanan = []; $dataBulanan = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->startOfMonth()->subMonths($i); // Menggunakan perbaikan startOfMonth()
            $labelBulanan[] = $date->translatedFormat('M Y');

            $p = Transaction::whereMonth('entry_time', $date->month)->whereYear('entry_time', $date->year)->sum($rumusParkirMurni);
            $t = Transaction::whereMonth('entry_time', $date->month)->whereYear('entry_time', $date->year)->sum($rumusToilet);
            $kp = FinancialReport::whereMonth('tanggal', $date->month)->whereYear('tanggal', $date->year)->where('jenis', 'pemasukan')->where('kategori', 'Parkiran')->sum('nominal');
            $kt = FinancialReport::whereMonth('tanggal', $date->month)->whereYear('tanggal', $date->year)->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%toilet%')->sum('nominal');
            $kn = FinancialReport::whereMonth('tanggal', $date->month)->whereYear('tanggal', $date->year)->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%nginap%')->sum('nominal');
            $ku = FinancialReport::whereMonth('tanggal', $date->month)->whereYear('tanggal', $date->year)->where('jenis', 'pemasukan')
                ->where('kategori', '!=', 'Parkiran')->where('kategori', 'NOT LIKE', '%toilet%')->where('kategori', 'NOT LIKE', '%nginap%')->sum('nominal');
            $kk = FinancialReport::whereMonth('tanggal', $date->month)->whereYear('tanggal', $date->year)->where('jenis', 'pengeluaran')->sum('nominal');

            $dataBulanan[] = $hitungProfit(($p + $kp), $kn, ($t + $kt), $ku) - $kk;
        }
        $chartData = ['harian' => ['labels' => $labelHarian, 'data' => $dataHarian], 'bulanan' => ['labels' => $labelBulanan, 'data' => $dataBulanan]];

        // =========================================================
        // 6. AKTIVITAS & KAS
        // =========================================================
        $recent_transactions = Transaction::latest('entry_time')->paginate(6, ['*'], 'parkir_page');
        $recent_financials = FinancialReport::latest('tanggal')->paginate(6, ['*'], 'kas_page');
        $totalPemasukanKas = FinancialReport::where('jenis', 'pemasukan')->sum('nominal');
        $totalPengeluaranKas = FinancialReport::where('jenis', 'pengeluaran')->sum('nominal');
        $saldoKas = $totalPemasukanKas - $totalPengeluaranKas;

        $toiletDariKas = FinancialReport::where('kategori', 'LIKE', '%toilet%')->where('jenis', 'pemasukan')->sum('nominal');
        $toiletDariParkir = Transaction::sum('toilet_fee');
        $totalPemasukanToilet = $toiletDariKas + $toiletDariParkir;
        $totalPemasukanNginap = FinancialReport::where('kategori', 'LIKE', '%nginap%')->where('jenis', 'pemasukan')->sum('nominal');

        // =========================================================
        // 7. ESTIMASI GAJI PEGAWAI (MENGGUNAKAN VARIABEL DINAMIS)
        // =========================================================
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
                $earned = $operator->salary_type == 'percentage' ? ($operator->salary_amount / 100) * $omzetGajiHariIni : $operator->salary_amount;
                $statusGaji = 'Estimasi Otomatis';
            }
            return (object)['name' => $operator->name, 'type' => $operator->salary_type, 'amount' => $operator->salary_amount, 'earned' => $earned, 'status' => $statusGaji];
        });

        // =========================================================
        // 8. TABEL REVENUE & GAJI HISTORIS
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
            $kt = FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%toilet%')->sum('nominal');
            $kn = FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%nginap%')->sum('nominal');
            $ku = FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pemasukan')
                ->where('kategori', '!=', 'Parkiran')->where('kategori', 'NOT LIKE', '%toilet%')->where('kategori', 'NOT LIKE', '%nginap%')->sum('nominal');
            $kk = FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pengeluaran')->sum('nominal');

            $totalParkirDate = $p + $kp;
            $omzetDate = $totalParkirDate + ($t + $kt) + $kn + $ku;
            $profitDate = $hitungProfit($totalParkirDate, $kn, ($t + $kt), $ku) - $kk;

            // Terapkan pengaturan: apakah gaji dihitung dari parkir saja atau total omzet
            $omzetGajiDate = $hitungDasarGaji($totalParkirDate, $omzetDate);

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

        // KIRIM VARIABEL $setting KE BLADE JUGA AGAR BLADE BISA MENYEMBUNYIKAN/MENAMPILKAN CARD
        return view('public_dashboard', compact(
            'setting', // Variabel sakti baru
            'data', 'chartData', 'recent_transactions', 'revenue_transactions',
            'totalPemasukanKas', 'totalPengeluaranKas', 'saldoKas', 'recent_financials', 'employeeSalaries',
            'sepedaBiasaHariIni', 'sepedaListrikHariIni', 'pegawaiRsudHariIni', 'riwayat_gaji', 'operators',
            'omzetHariIni', 'omzetKemarin', 'omzetBulanIni',
            'totalPemasukanToilet', 'totalPemasukanNginap'
        ));
    }
}
