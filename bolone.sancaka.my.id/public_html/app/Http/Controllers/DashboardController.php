<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // dd('CONTROLLER BERHASIL TERPANGGIL!');
        // LOG LOG - Mengambil data untuk grafik (menghitung jumlah kota berdasarkan keterangan)
        $chartData = City::selectRaw('nama_kota, COUNT(*) as total')
                         ->groupBy('nama_kota')
                         ->get();

        $totalData = City::count();

        return view('dashboard', compact('chartData', 'totalData'));
    }

}