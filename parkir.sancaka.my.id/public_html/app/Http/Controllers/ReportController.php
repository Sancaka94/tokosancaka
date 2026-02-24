<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function harian(Request $request)
    {
        $laporan = Transaction::select(
                DB::raw('DATE(exit_time) as tanggal'),
                DB::raw('SUM(CASE WHEN vehicle_type = "motor" THEN 1 ELSE 0 END) as total_motor'),
                DB::raw('SUM(CASE WHEN vehicle_type = "mobil" THEN 1 ELSE 0 END) as total_mobil'),
                DB::raw('SUM(fee) as pendapatan_parkir'),
                DB::raw('SUM(IFNULL(toilet_fee, 0)) as total_toilet'),
                DB::raw('SUM(fee + IFNULL(toilet_fee, 0)) as total_pendapatan')
            )
            ->whereNotNull('exit_time')
            ->where('status', 'keluar')
            ->groupBy(DB::raw('DATE(exit_time)'))
            ->orderBy('tanggal', 'desc')
            ->paginate(15);

        return view('laporan.harian', compact('laporan'));
    }
}
