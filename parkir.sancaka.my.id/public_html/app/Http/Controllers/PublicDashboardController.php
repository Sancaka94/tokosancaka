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
        // RUMUS TARIF OTOMATIS (BAYAR DI DEPAN / OMZET POTENSIAL)
        // Menghitung tarif 3000 untuk motor & 5000 untuk mobil otomatis saat tiket dicetak
        // =========================================================
        $rumusTarif = DB::raw('(CASE WHEN fee IS NOT NULL AND fee > 0 THEN fee WHEN vehicle_type = "mobil" THEN 5000 ELSE 3000 END) + IFNULL(toilet_fee, 0)');

        // =========================================================
        // 1. STATISTIK HARI INI & KEMARIN (Menggunakan Waktu Masuk)
        // =========================================================

        // Hari Ini
        $parkirHariIni = Transaction::whereDate('entry_time', $today)->sum($rumusTarif);
        $kasMasukHariIni = FinancialReport::whereDate('tanggal', $today)
                                    ->where('jenis', 'pemasukan')->sum('nominal');
        $kasKeluarHariIni = FinancialReport::whereDate('tanggal', $today)
                                    ->where('jenis', 'pengeluaran')->sum('nominal');

        $pendapatanKotorHariIni = $parkirHariIni + $kasMasukHariIni;
        $pendapatanBersihHariIni = $pendapatanKotorHariIni - $kasKeluarHariIni;

        // Kemarin
        $parkirKemarin = Transaction::whereDate('entry_time', $yesterday)->sum($rumusTarif);
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
        // TAMBAHAN: HITUNG SEPEDA & PEGAWAI RSUD HARI INI
        // =========================================================
        $sepedaBiasaHariIni = Transaction::whereDate('entry_time', $today)
                                ->where('plate_number', 'LIKE', 'SPD-%')
                                ->count();

        $sepedaListrikHariIni = Transaction::whereDate('entry_time', $today)
                                ->where('plate_number', 'LIKE', 'SPL-%')
                                ->count();

        $pegawaiRsudHariIni = Transaction::whereDate('entry_time', $today)
                                ->where('plate_number', 'LIKE', 'RSUD-%')
                                ->count();

        // =========================================================
        // 2. PENDAPATAN BULAN INI & BULAN KEMARIN
        // =========================================================

        // Bulan Ini
        $parkirBulanIni = Transaction::whereMonth('entry_time', $now->month)
                                ->whereYear('entry_time', $now->year)
                                ->sum($rumusTarif);
        $kasMasukBulanIni = FinancialReport::whereMonth('tanggal', $now->month)
                                ->whereYear('tanggal', $now->year)->where('jenis', 'pemasukan')->sum('nominal');
        $kasKeluarBulanIni = FinancialReport::whereMonth('tanggal', $now->month)
                                ->whereYear('tanggal', $now->year)->where('jenis', 'pengeluaran')->sum('nominal');

        $data['pendapatan_bulan_ini'] = ($parkirBulanIni + $kasMasukBulanIni) - $kasKeluarBulanIni;

        // Bulan Kemarin (Ditambahkan agar UI Dashboard bisa menampilkan trend Naik/Turun)
        $parkirBulanLalu = Transaction::whereMonth('entry_time', $lastMonth->month)
                                ->whereYear('entry_time', $lastMonth->year)
                                ->sum($rumusTarif);
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

            $parkirToilet = Transaction::whereDate('entry_time', $date)->sum($rumusTarif);
            $kasMasuk = FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pemasukan')->sum('nominal');
            $kasKeluar = FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pengeluaran')->sum('nominal');

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

            $parkirToilet = Transaction::whereMonth('entry_time', $date->month)->whereYear('entry_time', $date->year)->sum($rumusTarif);
            $kasMasuk = FinancialReport::whereMonth('tanggal', $date->month)->whereYear('tanggal', $date->year)->where('jenis', 'pemasukan')->sum('nominal');
            $kasKeluar = FinancialReport::whereMonth('tanggal', $date->month)->whereYear('tanggal', $date->year)->where('jenis', 'pengeluaran')->sum('nominal');

            $dataBulanan[] = $parkirToilet + $kasMasuk - $kasKeluar;
        }

        $chartData = [
            'harian' => ['labels' => $labelHarian, 'data' => $dataHarian],
            'bulanan' => ['labels' => $labelBulanan, 'data' => $dataBulanan],
        ];

        // =========================================================
        // 5. DATA AKTIVITAS TERBARU & KAS
        // =========================================================
        $recent_transactions = Transaction::latest('entry_time')->paginate(6, ['*'], 'parkir_page');

        $totalPemasukanKas = FinancialReport::where('jenis', 'pemasukan')->sum('nominal');
        $totalPengeluaranKas = FinancialReport::where('jenis', 'pengeluaran')->sum('nominal');
        $saldoKas = $totalPemasukanKas - $totalPengeluaranKas;

        $recent_financials = FinancialReport::latest('tanggal')->paginate(6, ['*'], 'kas_page');

        // =========================================================
        // 6. LOGIKA GAJI: CEK MANUAL VS OTOMATIS
        // =========================================================
        $operators = User::where('role', 'operator')->get();

        $laporanGajiHariIni = FinancialReport::whereDate('tanggal', $today)
            ->where('kategori', 'Gaji Pegawai')
            ->get();

        $employeeSalaries = $operators->map(function ($operator) use ($pendapatanKotorHariIni, $laporanGajiHariIni) {

            $gajiManual = $laporanGajiHariIni->filter(function ($report) use ($operator) {
                return stripos($report->keterangan, $operator->name) !== false;
            })->sum('nominal');

            if ($gajiManual > 0) {
                $earned = $gajiManual;
                $statusGaji = 'Sudah Dibayar (Manual)';
            } else {
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

        // =========================================================
        // 7. DATA TAMBAHAN: PENDAPATAN PARKIR & KENDARAAN MURNI
        // =========================================================
        $tujuhHariLalu = Carbon::today()->subDays(6);
        $awalBulan = Carbon::now()->startOfMonth();

        $data['parkir_hari_ini'] = $parkirHariIni;
        $data['parkir_kemarin'] = $parkirKemarin;

        $data['parkir_7_hari'] = Transaction::whereDate('entry_time', '>=', $tujuhHariLalu)->sum($rumusTarif);
        $data['parkir_bulan_ini'] = Transaction::whereDate('entry_time', '>=', $awalBulan)->sum($rumusTarif);

        // TABEL REKAPITULASI PENDAPATAN PER HARI
        $revenue_transactions = Transaction::select(
                DB::raw('DATE(entry_time) as tanggal'),
                DB::raw('COUNT(id) as total_kendaraan'),
                DB::raw('SUM((CASE WHEN fee IS NOT NULL AND fee > 0 THEN fee WHEN vehicle_type = "mobil" THEN 5000 ELSE 3000 END) + IFNULL(toilet_fee, 0)) as total_omzet')
            )
            ->groupBy(DB::raw('DATE(entry_time)'))
            ->orderBy(DB::raw('DATE(entry_time)'), 'desc')
            ->paginate(10, ['*'], 'revenue_page');

        // =========================================================
        // TAMBAHAN: REKAPITULASI GAJI HARIAN PER PEGAWAI
        // =========================================================

        // Ambil data pendapatan kotor per hari (Parkir + Kas Masuk)
        $daily_revenues = Transaction::select(
                DB::raw('DATE(entry_time) as tanggal'),
                DB::raw('SUM((CASE WHEN fee IS NOT NULL AND fee > 0 THEN fee WHEN vehicle_type = "mobil" THEN 5000 ELSE 3000 END) + IFNULL(toilet_fee, 0)) as total_parkir')
            )
            ->groupBy(DB::raw('DATE(entry_time)'))
            ->orderBy(DB::raw('DATE(entry_time)'), 'desc')
            ->limit(10) // Tampilkan 10 hari terakhir
            ->get();

        $riwayat_gaji = [];

        foreach ($daily_revenues as $rev) {
            $tgl = $rev->tanggal;

            // Tambahkan Kas Masuk manual di hari itu (jika ada)
            $kasMasukHarian = FinancialReport::whereDate('tanggal', $tgl)
                                ->where('jenis', 'pemasukan')
                                ->sum('nominal');

            $pendapatanKotorHarian = $rev->total_parkir + $kasMasukHarian;

            $gaji_per_pegawai = [];
            foreach ($operators as $op) {
                // Cek apakah hari itu sudah diinput manual di Kas?
                $gajiManual = FinancialReport::whereDate('tanggal', $tgl)
                    ->where('kategori', 'Gaji Pegawai')
                    ->where('keterangan', 'like', '%' . $op->name . '%')
                    ->sum('nominal');

                if ($gajiManual > 0) {
                    $earned = $gajiManual;
                    $status = 'Manual';
                } else {
                    if ($op->salary_type == 'percentage') {
                        $earned = ($op->salary_amount / 100) * $pendapatanKotorHarian;
                        $status = 'Otomatis';
                    } else {
                        $earned = $op->salary_amount;
                        $status = 'Otomatis';
                    }
                }

                $gaji_per_pegawai[$op->name] = [
                    'earned' => $earned,
                    'status' => $status
                ];
            }

            $riwayat_gaji[] = (object)[
                'tanggal' => $tgl,
                'pendapatan_kotor' => $pendapatanKotorHarian,
                'gaji_pegawai' => $gaji_per_pegawai
            ];
        }

        // =========================================================
        // 8. RETURN VIEW
        // =========================================================
        return view('public_dashboard', compact(
            'data', 'chartData', 'recent_transactions', 'revenue_transactions',
            'totalPemasukanKas', 'totalPengeluaranKas', 'saldoKas', 'recent_financials', 'employeeSalaries',
            'sepedaBiasaHariIni', 'sepedaListrikHariIni', 'pegawaiRsudHariIni', 'riwayat_gaji' // <-- Variabel baru ditambahkan di sini
        ));
    }
}
