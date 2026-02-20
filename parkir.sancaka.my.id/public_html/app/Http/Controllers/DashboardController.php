<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Statistik dinamis hari ini
        $data = [
            'motor_masuk' => Transaction::where('vehicle_type', 'motor')->where('status', 'masuk')->count(),
            'mobil_masuk' => Transaction::where('vehicle_type', 'mobil')->where('status', 'masuk')->count(),
            'total_pendapatan' => Transaction::whereDate('exit_time', today())->sum('fee') ?? 0,
        ];

        // Ambil 5 transaksi terakhir secara dinamis
        $recent_transactions = Transaction::with('operator')
                                ->latest()
                                ->take(5)
                                ->get();

        return view('dashboard.index', compact('data', 'user', 'recent_transactions'));
    }

    public function harian(Request $request)
    {
        $tanggal = $request->tanggal ?? today()->toDateString();
        $transactions = Transaction::whereDate('entry_time', $tanggal)->latest()->paginate(20);
        return view('laporan.harian', compact('transactions', 'tanggal'));
    }

    public function bulanan(Request $request)
    {
        $bulan = $request->bulan ?? date('m');
        $tahun = $request->tahun ?? date('Y');
        $transactions = Transaction::whereMonth('entry_time', $bulan)
                                   ->whereYear('entry_time', $tahun)
                                   ->latest()
                                   ->paginate(50);
        return view('laporan.bulanan', compact('transactions', 'bulan', 'tahun'));
    }

    public function triwulan()
    {
        // Logika laporan 3 bulan
        return view('laporan.triwulan');
    }
}
