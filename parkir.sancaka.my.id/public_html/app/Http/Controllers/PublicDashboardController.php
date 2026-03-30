<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\FinancialReport;
use App\Models\User;
use App\Models\DashboardWidget; // <-- Panggil Model Widget Anda
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class PublicDashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $today = Carbon::today();

        // RUMUS DB UTAMA
        $rumusParkirMurni = DB::raw('(CASE WHEN fee IS NOT NULL AND fee > 0 THEN fee WHEN vehicle_type = "mobil" THEN 5000 ELSE 3000 END)');
        $rumusToilet = DB::raw('IFNULL(toilet_fee, 0)');

        // =========================================================
        // 1. ENGINE DASHBOARD BUILDER (KARTU DINAMIS)
        // =========================================================
        // Ambil semua kartu yang statusnya Aktif (1)
        $widgets = DashboardWidget::where('is_active', true)->orderBy('order_index', 'asc')->get();

        foreach ($widgets as $w) {
            $startDate = $today;
            $endDate = $today->copy()->endOfDay();

            // Atur rentang waktu otomatis berdasarkan pilihan di UI Admin
            if ($w->time_range == 'yesterday') {
                $startDate = Carbon::yesterday();
                $endDate = Carbon::yesterday()->endOfDay();
            } elseif ($w->time_range == 'last_7_days') {
                $startDate = Carbon::today()->subDays(6);
                $endDate = Carbon::today()->endOfDay();
            } elseif ($w->time_range == 'this_month') {
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
            } elseif ($w->time_range == 'last_month') {
                $startDate = Carbon::now()->subMonth()->startOfMonth();
                $endDate = Carbon::now()->subMonth()->endOfMonth();
            }

            // TARIK DATA DARI DATABASE BERDASARKAN TANGGAL KARTU
            $trx_p = Transaction::whereBetween('entry_time', [$startDate, $endDate])->sum($rumusParkirMurni);
            $kas_p = FinancialReport::whereBetween('tanggal', [$startDate, $endDate])->where('jenis', 'pemasukan')->where('kategori', 'Parkiran')->sum('nominal');
            $total_parkir = $trx_p + $kas_p;

            $total_nginap = FinancialReport::whereBetween('tanggal', [$startDate, $endDate])->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%nginap%')->sum('nominal');

            $trx_t = Transaction::whereBetween('entry_time', [$startDate, $endDate])->sum('toilet_fee');
            $kas_t = FinancialReport::whereBetween('tanggal', [$startDate, $endDate])->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%toilet%')->sum('nominal');
            $total_toilet = $trx_t + $kas_t;

            $total_kas_lain = FinancialReport::whereBetween('tanggal', [$startDate, $endDate])->where('jenis', 'pemasukan')
                ->where('kategori', '!=', 'Parkiran')->where('kategori', 'NOT LIKE', '%toilet%')->where('kategori', 'NOT LIKE', '%nginap%')->sum('nominal');

            // EKSEKUSI RUMUS PERSENTASE (Inilah keajaibannya!)
            $w->calculated_value =
                ($total_parkir * ($w->pct_parkir / 100)) +
                ($total_nginap * ($w->pct_nginap / 100)) +
                ($total_toilet * ($w->pct_toilet / 100)) +
                ($total_kas_lain * ($w->pct_kas_lain / 100));
        }

        // =========================================================
        // 2. DATA KENDARAAN TERATAS (Tetap Statis)
        // =========================================================
        $motorHariIni = Transaction::where('vehicle_type', 'motor')->whereDate('entry_time', $today)->count();
        $mobilHariIni = Transaction::where('vehicle_type', 'mobil')->whereDate('entry_time', $today)->count();
        $sepedaBiasaHariIni = Transaction::whereDate('entry_time', $today)->where('plate_number', 'LIKE', 'SPD-%')->count();
        $sepedaListrikHariIni = Transaction::whereDate('entry_time', $today)->where('plate_number', 'LIKE', 'SPL-%')->count();
        $pegawaiRsudHariIni = Transaction::whereDate('entry_time', $today)->where('plate_number', 'LIKE', 'RSUD-%')->count();

        // =========================================================
        // 3. AKTIVITAS TABEL BAWAH (Tetap Statis)
        // =========================================================
        $recent_transactions = Transaction::latest('entry_time')->paginate(6, ['*'], 'parkir_page');
        $recent_financials = FinancialReport::latest('tanggal')->paginate(6, ['*'], 'kas_page');

        $totalPemasukanKas = FinancialReport::where('jenis', 'pemasukan')->sum('nominal');
        $totalPengeluaranKas = FinancialReport::where('jenis', 'pengeluaran')->sum('nominal');
        $saldoKas = $totalPemasukanKas - $totalPengeluaranKas;

        // Kirim semua data ke Blade
        return view('public_dashboard', compact(
            'widgets', // <-- Variabel Kartu Dinamis
            'recent_transactions', 'recent_financials', 'totalPemasukanKas', 'totalPengeluaranKas', 'saldoKas',
            'motorHariIni', 'mobilHariIni', 'sepedaBiasaHariIni', 'sepedaListrikHariIni', 'pegawaiRsudHariIni'
        ));
    }
}
