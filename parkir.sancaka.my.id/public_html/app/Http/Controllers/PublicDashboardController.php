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

            if ($w->display_type == 'card' || $w->display_type == 'employee_salary') {
                // TENTUKAN WAKTU SAAT INI
                $startDate = $today; $endDate = $today->copy()->endOfDay();

                // TENTUKAN WAKTU SEBELUMNYA (UNTUK PERBANDINGAN NAIK/TURUN)
                $prevStartDate = $today->copy()->subDay(); $prevEndDate = $today->copy()->subDay()->endOfDay();

                if ($w->time_range == 'yesterday') {
                    $startDate = Carbon::yesterday(); $endDate = Carbon::yesterday()->endOfDay();
                    $prevStartDate = Carbon::today()->subDays(2); $prevEndDate = Carbon::today()->subDays(2)->endOfDay();
                } elseif ($w->time_range == 'last_7_days') {
                    $startDate = Carbon::today()->subDays(6); $endDate = Carbon::today()->endOfDay();
                    $prevStartDate = Carbon::today()->subDays(13); $prevEndDate = Carbon::today()->subDays(7)->endOfDay();
                } elseif ($w->time_range == 'this_month') {
                    $startDate = Carbon::now()->startOfMonth(); $endDate = Carbon::now()->endOfMonth();
                    $prevStartDate = Carbon::now()->startOfMonth()->subMonth()->startOfDay(); $prevEndDate = Carbon::now()->startOfMonth()->subMonth()->endOfMonth();
                } elseif ($w->time_range == 'last_month') {
                    $startDate = Carbon::now()->startOfMonth()->subMonth()->startOfDay(); $endDate = Carbon::now()->startOfMonth()->subMonth()->endOfMonth();
                    $prevStartDate = Carbon::now()->startOfMonth()->subMonths(2)->startOfDay(); $prevEndDate = Carbon::now()->startOfMonth()->subMonths(2)->endOfMonth();
                }

                // 1. HITUNG OMZET SAAT INI
                $p = Transaction::whereBetween('entry_time', [$startDate, $endDate])->sum($rumusParkirMurni) + FinancialReport::whereBetween('tanggal', [$startDate, $endDate])->where('jenis', 'pemasukan')->where('kategori', 'Parkiran')->sum('nominal');
                $n = FinancialReport::whereBetween('tanggal', [$startDate, $endDate])->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%nginap%')->sum('nominal');
                $t = Transaction::whereBetween('entry_time', [$startDate, $endDate])->sum('toilet_fee') + FinancialReport::whereBetween('tanggal', [$startDate, $endDate])->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%toilet%')->sum('nominal');
                $l = FinancialReport::whereBetween('tanggal', [$startDate, $endDate])->where('jenis', 'pemasukan')->where('kategori', '!=', 'Parkiran')->where('kategori', 'NOT LIKE', '%toilet%')->where('kategori', 'NOT LIKE', '%nginap%')->sum('nominal');

                $calculated_base_value = ($p * ($w->pct_parkir / 100)) + ($n * ($w->pct_nginap / 100)) + ($t * ($w->pct_toilet / 100)) + ($l * ($w->pct_kas_lain / 100));
                $w->calculated_value = $calculated_base_value;

                // 2. HITUNG OMZET MASA LALU (PERBANDINGAN)
                $pp = Transaction::whereBetween('entry_time', [$prevStartDate, $prevEndDate])->sum($rumusParkirMurni) + FinancialReport::whereBetween('tanggal', [$prevStartDate, $prevEndDate])->where('jenis', 'pemasukan')->where('kategori', 'Parkiran')->sum('nominal');
                $pn = FinancialReport::whereBetween('tanggal', [$prevStartDate, $prevEndDate])->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%nginap%')->sum('nominal');
                $pt = Transaction::whereBetween('entry_time', [$prevStartDate, $prevEndDate])->sum('toilet_fee') + FinancialReport::whereBetween('tanggal', [$prevStartDate, $prevEndDate])->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%toilet%')->sum('nominal');
                $pl = FinancialReport::whereBetween('tanggal', [$prevStartDate, $prevEndDate])->where('jenis', 'pemasukan')->where('kategori', '!=', 'Parkiran')->where('kategori', 'NOT LIKE', '%toilet%')->where('kategori', 'NOT LIKE', '%nginap%')->sum('nominal');

                $prev_base_value = ($pp * ($w->pct_parkir / 100)) + ($pn * ($w->pct_nginap / 100)) + ($pt * ($w->pct_toilet / 100)) + ($pl * ($w->pct_kas_lain / 100));

                // 3. KALKULASI SELISIH & PERSENTASE KARTU
                $selisih = $calculated_base_value - $prev_base_value;
                $persen = $prev_base_value > 0 ? ($selisih / $prev_base_value) * 100 : ($calculated_base_value > 0 ? 100 : 0);
                $w->comparison = (object)['selisih' => $selisih, 'persen' => $persen, 'is_naik' => $selisih >= 0];

                // 4. KHUSUS GAJI PEGAWAI
                if ($w->display_type == 'employee_salary') {
                    $operator = User::find($w->user_id);
                    if ($operator) {
                        // Gaji Saat Ini
                        $gajiManual = FinancialReport::whereBetween('tanggal', [$startDate, $endDate])->where('kategori', 'Gaji Pegawai')->where('keterangan', 'LIKE', '%' . $operator->name . '%')->sum('nominal');
                        $earned = $gajiManual > 0 ? $gajiManual : ($operator->salary_type == 'percentage' ? ($operator->salary_amount / 100) * $calculated_base_value : $operator->salary_amount);

                        // Gaji Masa Lalu (Perbandingan)
                        $prevGajiManual = FinancialReport::whereBetween('tanggal', [$prevStartDate, $prevEndDate])->where('kategori', 'Gaji Pegawai')->where('keterangan', 'LIKE', '%' . $operator->name . '%')->sum('nominal');
                        $prev_earned = $prevGajiManual > 0 ? $prevGajiManual : ($operator->salary_type == 'percentage' ? ($operator->salary_amount / 100) * $prev_base_value : $operator->salary_amount);

                        $emp_selisih = $earned - $prev_earned;
                        $emp_persen = $prev_earned > 0 ? ($emp_selisih / $prev_earned) * 100 : ($earned > 0 ? 100 : 0);

                        $w->employee_data = (object)[
                            'name' => $operator->name, 'type' => $operator->salary_type, 'amount' => $operator->salary_amount,
                            'earned' => $earned, 'status' => ($gajiManual > 0 ? 'Sudah Dibayar (Manual)' : 'Estimasi Otomatis'),
                            'comparison' => (object)['selisih' => $emp_selisih, 'persen' => $emp_persen, 'is_naik' => $emp_selisih >= 0]
                        ];
                    } else {
                        $w->employee_data = null;
                    }
                }
            }

            // GRAFIK TETAP SAMA
            elseif ($w->display_type == 'chart_line' || $w->display_type == 'chart_bar') {
                $labels = []; $data = [];
                $limit = $w->display_type == 'chart_line' ? 6 : 5;
                for ($i = $limit; $i >= 0; $i--) {
                    if($w->display_type == 'chart_line') {
                        $date = Carbon::today()->subDays($i); $labels[] = $date->translatedFormat('d M');
                        $p = Transaction::whereDate('entry_time', $date)->sum($rumusParkirMurni) + FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pemasukan')->where('kategori', 'Parkiran')->sum('nominal');
                        $n = FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%nginap%')->sum('nominal');
                        $t = Transaction::whereDate('entry_time', $date)->sum('toilet_fee') + FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%toilet%')->sum('nominal');
                        $l = FinancialReport::whereDate('tanggal', $date)->where('jenis', 'pemasukan')->where('kategori', '!=', 'Parkiran')->where('kategori', 'NOT LIKE', '%toilet%')->where('kategori', 'NOT LIKE', '%nginap%')->sum('nominal');
                    } else {
                        $date = Carbon::now()->startOfMonth()->subMonths($i); $labels[] = $date->translatedFormat('M Y'); $m = $date->month; $y = $date->year;
                        $p = Transaction::whereMonth('entry_time', $m)->whereYear('entry_time', $y)->sum($rumusParkirMurni) + FinancialReport::whereMonth('tanggal', $m)->whereYear('tanggal', $y)->where('jenis', 'pemasukan')->where('kategori', 'Parkiran')->sum('nominal');
                        $n = FinancialReport::whereMonth('tanggal', $m)->whereYear('tanggal', $y)->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%nginap%')->sum('nominal');
                        $t = Transaction::whereMonth('entry_time', $m)->whereYear('entry_time', $y)->sum('toilet_fee') + FinancialReport::whereMonth('tanggal', $m)->whereYear('tanggal', $y)->where('jenis', 'pemasukan')->where('kategori', 'LIKE', '%toilet%')->sum('nominal');
                        $l = FinancialReport::whereMonth('tanggal', $m)->whereYear('tanggal', $y)->where('jenis', 'pemasukan')->where('kategori', '!=', 'Parkiran')->where('kategori', 'NOT LIKE', '%toilet%')->where('kategori', 'NOT LIKE', '%nginap%')->sum('nominal');
                    }
                    $data[] = ($p * ($w->pct_parkir / 100)) + ($n * ($w->pct_nginap / 100)) + ($t * ($w->pct_toilet / 100)) + ($l * ($w->pct_kas_lain / 100));
                }
                $w->chart_labels = $labels; $w->chart_data = $data;
            }
        }

        // =========================================================
        // DATA STATIS LAINNYA
        // =========================================================
        $motorHariIni = Transaction::where('vehicle_type', 'motor')->whereDate('entry_time', $today)->count();
        $mobilHariIni = Transaction::where('vehicle_type', 'mobil')->whereDate('entry_time', $today)->count();
        $sepedaBiasaHariIni = Transaction::whereDate('entry_time', $today)->where('plate_number', 'LIKE', 'SPD-%')->count();
        $sepedaListrikHariIni = Transaction::whereDate('entry_time', $today)->where('plate_number', 'LIKE', 'SPL-%')->count();
        $pegawaiRsudHariIni = Transaction::whereDate('entry_time', $today)->where('plate_number', 'LIKE', 'RSUD-%')->count();

        $recent_transactions = Transaction::latest('entry_time')->paginate(6, ['*'], 'parkir_page');
        $recent_financials = FinancialReport::latest('tanggal')->paginate(6, ['*'], 'kas_page');
        $totalPemasukanKas = FinancialReport::where('jenis', 'pemasukan')->sum('nominal');
        $totalPengeluaranKas = FinancialReport::where('jenis', 'pengeluaran')->sum('nominal');
        $saldoKas = $totalPemasukanKas - $totalPengeluaranKas;
        $totalPemasukanToilet = Transaction::sum('toilet_fee') + FinancialReport::where('kategori', 'LIKE', '%toilet%')->where('jenis', 'pemasukan')->sum('nominal');
        $totalPemasukanNginap = FinancialReport::where('kategori', 'LIKE', '%nginap%')->where('jenis', 'pemasukan')->sum('nominal');

        return view('public_dashboard', compact(
            'widgets', 'recent_transactions', 'recent_financials', 'totalPemasukanKas', 'totalPengeluaranKas', 'saldoKas',
            'totalPemasukanToilet', 'totalPemasukanNginap', 'motorHariIni', 'mobilHariIni', 'sepedaBiasaHariIni', 'sepedaListrikHariIni', 'pegawaiRsudHariIni'
        ));
    }
}
