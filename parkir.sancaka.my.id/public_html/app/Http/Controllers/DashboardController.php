<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // 1. Statistik Hari Ini
        $data = [
            'motor_masuk' => \App\Models\Transaction::where('vehicle_type', 'motor')->where('status', 'masuk')->count(),
            'mobil_masuk' => \App\Models\Transaction::where('vehicle_type', 'mobil')->where('status', 'masuk')->count(),
            'total_pendapatan' => \App\Models\Transaction::whereDate('exit_time', today())->sum('fee') ?? 0,
        ];

        // 2. Perbandingan Pendapatan Bulan Ini vs Bulan Lalu
        $pendapatanBulanIni = \App\Models\Transaction::whereMonth('exit_time', date('m'))
                                ->whereYear('exit_time', date('Y'))->sum('fee');
        $pendapatanBulanLalu = \App\Models\Transaction::whereMonth('exit_time', \Carbon\Carbon::now()->subMonth()->month)
                                ->whereYear('exit_time', \Carbon\Carbon::now()->subMonth()->year)->sum('fee');

        $persentase = 0;
        $trend = 'tetap'; // naik, turun, atau tetap
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

        // 3. Data Grafik Harian (7 Hari Terakhir)
        $labelHarian = [];
        $dataHarian = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::now()->subDays($i);
            $labelHarian[] = $date->translatedFormat('d M');
            $dataHarian[] = \App\Models\Transaction::whereDate('exit_time', $date)->sum('fee');
        }

        // 4. Data Grafik Bulanan (6 Bulan Terakhir)
        $labelBulanan = [];
        $dataBulanan = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = \Carbon\Carbon::now()->subMonths($i);
            $labelBulanan[] = $date->translatedFormat('M Y');
            $dataBulanan[] = \App\Models\Transaction::whereMonth('exit_time', $date->month)
                                ->whereYear('exit_time', $date->year)->sum('fee');
        }

        $chartData = [
            'harian' => ['labels' => $labelHarian, 'data' => $dataHarian],
            'bulanan' => ['labels' => $labelBulanan, 'data' => $dataBulanan],
        ];

        // 5. Tabel Aktivitas Terbaru
        $recent_transactions = \App\Models\Transaction::with('operator')->latest()->take(5)->get();

        return view('dashboard.index', compact('data', 'user', 'recent_transactions', 'chartData'));
    }

    public function harian(Request $request)
    {
        $tanggal = $request->tanggal ?? today()->toDateString();

        // Menarik data transaksi dengan paginasi (sudah ada di kode Anda)
        $transactions = Transaction::whereDate('entry_time', $tanggal)->latest()->paginate(20);

        // TAMBAHKAN BARIS INI: Menghitung total pendapatan keseluruhan pada tanggal tersebut
        $total = Transaction::whereDate('entry_time', $tanggal)->sum('fee');

        // PERBARUI BARIS INI: Tambahkan 'total' ke dalam compact()
        return view('laporan.harian', compact('transactions', 'tanggal', 'total'));
    }

    public function bulanan(Request $request)
    {
        $bulan = $request->bulan ?? date('m');
        $tahun = $request->tahun ?? date('Y');

        $transactions = Transaction::whereMonth('entry_time', $bulan)
                                   ->whereYear('entry_time', $tahun)
                                   ->latest()
                                   ->paginate(50);

        // Tambahkan baris ini untuk menghitung total pendapatan bulanan
        $total = Transaction::whereMonth('entry_time', $bulan)
                            ->whereYear('entry_time', $tahun)
                            ->sum('fee');

        // Pastikan $total dimasukkan ke dalam compact()
        return view('laporan.bulanan', compact('transactions', 'bulan', 'tahun', 'total'));
    }

    public function triwulan()
    {
        // Logika laporan 3 bulan
        return view('laporan.triwulan');
    }
}
