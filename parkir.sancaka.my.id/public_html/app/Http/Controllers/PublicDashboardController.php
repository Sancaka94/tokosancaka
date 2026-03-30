<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\FinancialReport;
use App\Models\User;
use App\Models\DashboardWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PublicDashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $today = Carbon::today();

        $rumusParkirMurni = DB::raw('(CASE WHEN fee IS NOT NULL AND fee > 0 THEN fee WHEN vehicle_type = "mobil" THEN 5000 ELSE 3000 END)');
        $rumusToilet = DB::raw('IFNULL(toilet_fee, 0)');

        // =========================================================
        // 1. ENGINE DASHBOARD BUILDER (ALL IN ONE WIDGETS)
        // =========================================================
        $widgets = DashboardWidget::where('is_active', true)->orderBy('order_index', 'asc')->get();

        foreach ($widgets as $w) {

            // JIKA WIDGET = KARTU ANGKA ATAU KARTU GAJI PEGAWAI
            if ($w->display_type == 'card' || $w->display_type == 'employee_salary') {
                $startDate = $today;
                $endDate = $today->copy()->endOfDay();

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
                    $startDate = Carbon::now()->startOfMonth()->subMonth()->startOfDay();
                    $endDate = Carbon::now()->startOfMonth()->subMonth()->endOfMonth();
                }

                // Hitung Data Mentah sesuai Tanggal
                $p = Transaction::whereBetween('entry_time', [$startDate, $endDate])->sum($rumusParkirMurni) + FinancialReport::whereBetween('tanggal', [$startDate, $endDate])->where('jenis', 'pemasukan')->where('kategori', 'Parkiran')->sum('nominal');
                $n = FinancialReport::whereBetween('tanggal', [$startDate, $endDate])->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%nginap%')->sum('nominal');
                $t = Transaction::whereBetween('entry_time', [$startDate, $endDate])->sum('toilet_fee') + FinancialReport::whereBetween('tanggal', [$startDate, $endDate])->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%toilet%')->sum('nominal');
                $l = FinancialReport::whereBetween('tanggal', [$startDate, $endDate])->where('jenis', 'pemasukan')->where('kategori', '!=', 'Parkiran')->where('kategori', 'NOT LIKE', '%toilet%')->where('kategori', 'NOT LIKE', '%nginap%')->sum('nominal');

                // Hitung Nilai berdasarkan Rumus Widget
                $calculated_base_value = ($p * ($w->pct_parkir / 100)) + ($n * ($w->pct_nginap / 100)) + ($t * ($w->pct_toilet / 100)) + ($l * ($w->pct_kas_lain / 100));

                $w->calculated_value = $calculated_base_value;

                // KHUSUS JIKA WIDGET ADALAH GAJI PEGAWAI (KARTU TUNGGAL):
                if ($w->display_type == 'employee_salary') {
                    $operator = User::find($w->user_id);
                    if ($operator) {
                        $gajiManual = FinancialReport::whereBetween('tanggal', [$startDate, $endDate])
                            ->where('kategori', 'Gaji Pegawai')
                            ->where('keterangan', 'LIKE', '%' . $operator->name . '%')
                            ->sum('nominal');

                        if ($gajiManual > 0) {
                            $earned = $gajiManual;
                            $statusGaji = 'Sudah Dibayar (Manual)';
                        } else {
                            // Mengalikan omzet dasar widget dengan persentase pegawai di tabel users
                            $earned = $operator->salary_type == 'percentage' ? ($operator->salary_amount / 100) * $calculated_base_value : $operator->salary_amount;
                            $statusGaji = 'Estimasi Otomatis';
                        }

                        $w->employee_data = (object)[
                            'name' => $operator->name,
                            'type' => $operator->salary_type,
                            'amount' => $operator->salary_amount,
                            'earned' => $earned,
                            'status' => $statusGaji
                        ];
                    } else {
                        $w->employee_data = null; // Pegawai tidak ditemukan / dihapus
                    }
                }
            }

            // JIKA WIDGET = GRAFIK GARIS (7 HARI TERAKHIR)
            elseif ($w->display_type == 'chart_line') {
                $labels = []; $data = [];
                for ($i = 6; $i >= 0; $i--) {
                    $date = Carbon::today()->subDays($i);
                    $labels[] = $date->translatedFormat('d M');

                    $p = Transaction::whereDate('entry_time', $date)->sum($rumusParkirMurni) + FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pemasukan')->where('kategori', 'Parkiran')->sum('nominal');
                    $n = FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%nginap%')->sum('nominal');
                    $t = Transaction::whereDate('entry_time', $date)->sum('toilet_fee') + FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%toilet%')->sum('nominal');
                    $l = FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pemasukan')->where('kategori', '!=', 'Parkiran')->where('kategori', 'NOT LIKE', '%toilet%')->where('kategori', 'NOT LIKE', '%nginap%')->sum('nominal');

                    $data[] = ($p * ($w->pct_parkir / 100)) + ($n * ($w->pct_nginap / 100)) + ($t * ($w->pct_toilet / 100)) + ($l * ($w->pct_kas_lain / 100));
                }
                $w->chart_labels = $labels;
                $w->chart_data = $data;
            }

            // JIKA WIDGET = GRAFIK BATANG (6 BULAN TERAKHIR)
            elseif ($w->display_type == 'chart_bar') {
                $labels = []; $data = [];
                for ($i = 5; $i >= 0; $i--) {
                    $date = Carbon::now()->startOfMonth()->subMonths($i);
                    $labels[] = $date->translatedFormat('M Y');
                    $m = $date->month; $y = $date->year;

                    $p = Transaction::whereMonth('entry_time', $m)->whereYear('entry_time', $y)->sum($rumusParkirMurni) + FinancialReport::whereMonth('tanggal', $m)->whereYear('tanggal', $y)->where('jenis', 'pemasukan')->where('kategori', 'Parkiran')->sum('nominal');
                    $n = FinancialReport::whereMonth('tanggal', $m)->whereYear('tanggal', $y)->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%nginap%')->sum('nominal');
                    $t = Transaction::whereMonth('entry_time', $m)->whereYear('entry_time', $y)->sum('toilet_fee') + FinancialReport::whereMonth('tanggal', $m)->whereYear('tanggal', $y)->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%toilet%')->sum('nominal');
                    $l = FinancialReport::whereMonth('tanggal', $m)->whereYear('tanggal', $y)->where('jenis', 'pemasukan')->where('kategori', '!=', 'Parkiran')->where('kategori', 'NOT LIKE', '%toilet%')->where('kategori', 'NOT LIKE', '%nginap%')->sum('nominal');

                    $data[] = ($p * ($w->pct_parkir / 100)) + ($n * ($w->pct_nginap / 100)) + ($t * ($w->pct_toilet / 100)) + ($l * ($w->pct_kas_lain / 100));
                }
                $w->chart_labels = $labels;
                $w->chart_data = $data;
            }
        }

        // =========================================================
        // 2. DATA KENDARAAN (Statis)
        // =========================================================
        $motorHariIni = Transaction::where('vehicle_type', 'motor')->whereDate('entry_time', $today)->count();
        $mobilHariIni = Transaction::where('vehicle_type', 'mobil')->whereDate('entry_time', $today)->count();
        $sepedaBiasaHariIni = Transaction::whereDate('entry_time', $today)->where('plate_number', 'LIKE', 'SPD-%')->count();
        $sepedaListrikHariIni = Transaction::whereDate('entry_time', $today)->where('plate_number', 'LIKE', 'SPL-%')->count();
        $pegawaiRsudHariIni = Transaction::whereDate('entry_time', $today)->where('plate_number', 'LIKE', 'RSUD-%')->count();

        // =========================================================
        // 3. TABEL AKTIVITAS & KAS (Statis)
        // =========================================================
        $recent_transactions = Transaction::latest('entry_time')->paginate(6, ['*'], 'parkir_page');
        $recent_financials = FinancialReport::latest('tanggal')->paginate(6, ['*'], 'kas_page');

        $totalPemasukanKas = FinancialReport::where('jenis', 'pemasukan')->sum('nominal');
        $totalPengeluaranKas = FinancialReport::where('jenis', 'pengeluaran')->sum('nominal');
        $saldoKas = $totalPemasukanKas - $totalPengeluaranKas;

        $totalPemasukanToilet = Transaction::sum('toilet_fee') + FinancialReport::where('kategori', 'LIKE', '%toilet%')->where('jenis', 'pemasukan')->sum('nominal');
        $totalPemasukanNginap = FinancialReport::where('kategori', 'LIKE', '%nginap%')->where('jenis', 'pemasukan')->sum('nominal');

        return view('public_dashboard', compact(
            'widgets', 'recent_transactions', 'recent_financials', 'totalPemasukanKas', 'totalPengeluaranKas', 'saldoKas',
            'totalPemasukanToilet', 'totalPemasukanNginap',
            'motorHariIni', 'mobilHariIni', 'sepedaBiasaHariIni', 'sepedaListrikHariIni', 'pegawaiRsudHariIni'
        ));
    }
}
