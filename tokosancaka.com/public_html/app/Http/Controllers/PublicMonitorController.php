<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ScannedPackage;
use App\Models\SuratJalan; // <--- PASTIKAN MODEL INI DI-IMPORT
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PublicMonitorController extends Controller
{
    public function index()
    {
        Log::info("LOG LOG: Halaman public monitor diakses.");

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $dayBeforeYesterday = Carbon::today()->subDays(2);
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        // 1. Hari Ini vs Kemarin
        $countToday = ScannedPackage::whereDate('created_at', $today)->count();
        $countYesterday = ScannedPackage::whereDate('created_at', $yesterday)->count();
        $diffToday = $countToday - $countYesterday;
        $pctToday = $countYesterday > 0 ? round(($diffToday / $countYesterday) * 100, 1) : ($countToday > 0 ? 100 : 0);

        // 2. Kemarin vs H-2
        $countDayBefore = ScannedPackage::whereDate('created_at', $dayBeforeYesterday)->count();
        $diffYesterday = $countYesterday - $countDayBefore;
        $pctYesterday = $countDayBefore > 0 ? round(($diffYesterday / $countDayBefore) * 100, 1) : ($countYesterday > 0 ? 100 : 0);

        // 3. Bulan Ini vs Bulan Lalu
        $countThisMonth = ScannedPackage::where('created_at', '>=', $thisMonth)->count();
        $countLastMonth = ScannedPackage::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();
        $diffMonth = $countThisMonth - $countLastMonth;
        $pctMonth = $countLastMonth > 0 ? round(($diffMonth / $countLastMonth) * 100, 1) : ($countThisMonth > 0 ? 100 : 0);

        // 4. Total Copied vs Belum Copied
        $countCopied = ScannedPackage::where('is_copied', true)->count();
        $countNotCopied = ScannedPackage::where('is_copied', false)->count();

        // ---------------------------------------------------------
        // KODE BARU: Ambil data Surat Jalan terbaru (misal 50 data terakhir)
        // ---------------------------------------------------------
        $suratJalans = SuratJalan::with(['user', 'kontak', 'packages'])
                            ->latest()
                            ->paginate(10);

        return view('public.monitor', compact(
            'countToday', 'diffToday', 'pctToday',
            'countYesterday', 'diffYesterday', 'pctYesterday',
            'countThisMonth', 'diffMonth', 'pctMonth',
            'countCopied', 'countNotCopied',
            'suratJalans' // <--- PASTIKAN INI DIKIRIM KE VIEW
        ));
    }
}
