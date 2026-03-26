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
        // 3. LOGIKA MASTER: (PARKIR + KAS MASUK + TOILET / 2) - OPERASIONAL
        // =========================================================

        // HARI INI
        $parkirHariIni = Transaction::whereDate('entry_time', $today)->sum($rumusParkirMurni);
        $toiletHariIni = Transaction::whereDate('entry_time', $today)->sum($rumusToilet);
        $kasMasukHariIni = FinancialReport::whereDate('tanggal', $today)->where('jenis', 'pemasukan')->sum('nominal');
        $kasKeluarHariIni = FinancialReport::whereDate('tanggal', $today)->where('jenis', 'pengeluaran')->sum('nominal');

        $omzetHariIni = $parkirHariIni + $toiletHariIni + $kasMasukHariIni;
        $profitHariIni = ($omzetHariIni / 2) - $kasKeluarHariIni;

        // KEMARIN
        $parkirKemarin = Transaction::whereDate('entry_time', $yesterday)->sum($rumusParkirMurni);
        $toiletKemarin = Transaction::whereDate('entry_time', $yesterday)->sum($rumusToilet);
        $kasMasukKemarin = FinancialReport::whereDate('tanggal', $yesterday)->where('jenis', 'pemasukan')->sum('nominal');
        $kasKeluarKemarin = FinancialReport::whereDate('tanggal', $yesterday)->where('jenis', 'pengeluaran')->sum('nominal');

        $omzetKemarin = $parkirKemarin + $toiletKemarin + $kasMasukKemarin;
        $profitKemarin = ($omzetKemarin / 2) - $kasKeluarKemarin;

        // BULAN INI
        $parkirBulanIni = Transaction::whereMonth('entry_time', $now->month)->whereYear('entry_time', $now->year)->sum($rumusParkirMurni);
        $toiletBulanIni = Transaction::whereMonth('entry_time', $now->month)->whereYear('entry_time', $now->year)->sum($rumusToilet);
        $kasMasukBulanIni = FinancialReport::whereMonth('tanggal', $now->month)->whereYear('tanggal', $now->year)->where('jenis', 'pemasukan')->sum('nominal');
        $kasKeluarBulanIni = FinancialReport::whereMonth('tanggal', $now->month)->whereYear('tanggal', $now->year)->where('jenis', 'pengeluaran')->sum('nominal');

        $omzetBulanIni = $parkirBulanIni + $toiletBulanIni + $kasMasukBulanIni;
        $profitBulanIni = ($omzetBulanIni / 2) - $kasKeluarBulanIni;

        // BULAN LALU
        $parkirBulanLalu = Transaction::whereMonth('entry_time', $lastMonth->month)->whereYear('entry_time', $lastMonth->year)->sum($rumusParkirMurni);
        $toiletBulanLalu = Transaction::whereMonth('entry_time', $lastMonth->month)->whereYear('entry_time', $lastMonth->year)->sum($rumusToilet);
        $kasMasukBulanLalu = FinancialReport::whereMonth('tanggal', $lastMonth->month)->whereYear('tanggal', $lastMonth->year)->where('jenis', 'pemasukan')->sum('nominal');
        $kasKeluarBulanLalu = FinancialReport::whereMonth('tanggal', $lastMonth->month)->whereYear('tanggal', $lastMonth->year)->where('jenis', 'pengeluaran')->sum('nominal');

        $omzetBulanLalu = $parkirBulanLalu + $toiletBulanLalu + $kasMasukBulanLalu;
        $profitBulanLalu = ($omzetBulanLalu / 2) - $kasKeluarBulanLalu;

        $data = [
            'motor_masuk' => $motorHariIni,
            'mobil_masuk' => $mobilHariIni,
            'total_pendapatan' => $profitHariIni,
            'pendapatan_kemarin' => $profitKemarin,
            'pendapatan_bulan_ini' => $profitBulanIni,
            'pendapatan_bulan_kemarin' => $profitBulanLalu,
            'parkir_hari_ini' => $parkirHariIni,
            'parkir_kemarin' => $parkirKemarin,
            'parkir_7_hari' => Transaction::whereDate('entry_time', '>=', Carbon::today()->subDays(6))->sum($rumusParkirMurni),
            'parkir_bulan_ini' => $parkirBulanIni,
        ];

        // 4. GRAFIK
        $labelHarian = []; $dataHarian = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labelHarian[] = $date->translatedFormat('d M');
            $p = Transaction::whereDate('entry_time', $date)->sum($rumusParkirMurni);
            $t = Transaction::whereDate('entry_time', $date)->sum($rumusToilet);
            $km = FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pemasukan')->sum('nominal');
            $kk = FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pengeluaran')->sum('nominal');

            $omz = $p + $t + $km;
            $dataHarian[] = ($omz / 2) - $kk;
        }

        $labelBulanan = []; $dataBulanan = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $labelBulanan[] = $date->translatedFormat('M Y');
            $p = Transaction::whereMonth('entry_time', $date->month)->whereYear('entry_time', $date->year)->sum($rumusParkirMurni);
            $t = Transaction::whereMonth('entry_time', $date->month)->whereYear('entry_time', $date->year)->sum($rumusToilet);
            $km = FinancialReport::whereMonth('tanggal', $date->month)->whereYear('tanggal', $date->year)->where('jenis', 'pemasukan')->sum('nominal');
            $kk = FinancialReport::whereMonth('tanggal', $date->month)->whereYear('tanggal', $date->year)->where('jenis', 'pengeluaran')->sum('nominal');

            $omz = $p + $t + $km;
            $dataBulanan[] = ($omz / 2) - $kk;
        }
        $chartData = ['harian' => ['labels' => $labelHarian, 'data' => $dataHarian], 'bulanan' => ['labels' => $labelBulanan, 'data' => $dataBulanan]];

        // 5. AKTIVITAS & KAS TOTAL KESELURUHAN (BUKAN PER HARI)
        $recent_transactions = Transaction::latest('entry_time')->paginate(6, ['*'], 'parkir_page');
        $recent_financials = FinancialReport::latest('tanggal')->paginate(6, ['*'], 'kas_page');
        $totalPemasukanKas = FinancialReport::where('jenis', 'pemasukan')->sum('nominal');
        $totalPengeluaranKas = FinancialReport::where('jenis', 'pengeluaran')->sum('nominal');
        $saldoKas = $totalPemasukanKas - $totalPengeluaranKas;
        // 1. Ambil toilet dari inputan Kas Manual
        $toiletDariKas = FinancialReport::where('kategori', 'Toilet')->where('jenis', 'pemasukan')->sum('nominal');
        // 2. Ambil toilet dari form catat parkir manual (riwayat lama)
        $toiletDariParkir = Transaction::sum('toilet_fee');

        // 3. Gabungkan keduanya & hitung nginap
        $totalPemasukanToilet = $toiletDariKas + $toiletDariParkir;
        $totalPemasukanNginap = FinancialReport::where('kategori', 'Nginap')->where('jenis', 'pemasukan')->sum('nominal');

        // 6. ESTIMASI GAJI PEGAWAI (CARD ATAS)
        $operators = User::where('role', 'operator')->get();
        $laporanGajiHariIni = FinancialReport::whereDate('tanggal', $today)->where('kategori', 'Gaji Pegawai')->get();

        $employeeSalaries = $operators->map(function ($operator) use ($omzetHariIni, $laporanGajiHariIni) {
            $gajiManual = $laporanGajiHariIni->filter(function ($report) use ($operator) {
                return stripos($report->keterangan, $operator->name) !== false;
            })->sum('nominal');

            if ($gajiManual > 0) {
                $earned = $gajiManual;
                $statusGaji = 'Sudah Dibayar (Manual)';
            } else {
                $earned = $operator->salary_type == 'percentage' ? ($operator->salary_amount / 100) * $omzetHariIni : $operator->salary_amount;
                $statusGaji = 'Estimasi Otomatis';
            }
            return (object)['name' => $operator->name, 'type' => $operator->salary_type, 'amount' => $operator->salary_amount, 'earned' => $earned, 'status' => $statusGaji];
        });

        // =========================================================
        // 7. TABEL REVENUE & GAJI (PENGGABUNGAN DATA MUTLAK)
        // =========================================================
        // Kumpulkan semua tanggal dari Transaksi DAN Kas agar TANGGAL KAS TUNGGAL tidak hilang
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

            $km = FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pemasukan')->sum('nominal');
            $kk = FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pengeluaran')->sum('nominal');

            $omzetDate = $p + $t + $km;
            $profitDate = ($omzetDate / 2) - $kk;

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
                    $earned = $op->salary_type == 'percentage' ? ($op->salary_amount / 100) * $omzetDate : $op->salary_amount;
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
            'omzetHariIni', 'omzetKemarin', 'omzetBulanIni'
        ));
    }
}
