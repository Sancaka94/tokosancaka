<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // LOG LOG - Mengambil data untuk grafik (menghitung jumlah kota berdasarkan keterangan)
        $chartData = City::selectRaw('keterangan, COUNT(*) as total')
                         ->groupBy('keterangan')
                         ->get();

        $totalData = City::count();

        return view('dashboard', compact('chartData', 'totalData'));
    }
}