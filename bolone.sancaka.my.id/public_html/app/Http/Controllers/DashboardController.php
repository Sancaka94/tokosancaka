<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Mengambil data kota dan jumlahnya
        // Pastikan kolom di database Anda benar (nama_kota)
        $chartData = City::selectRaw('nama_kota, COUNT(*) as total')
                         ->groupBy('nama_kota')
                         ->orderBy('total', 'desc') // Opsional: Urutkan dari yang terbanyak
                         ->get();

        // Menghitung total keseluruhan baris di tabel cities
        $totalData = City::count();

        // Mengirim data ke view dashboard.blade.php
        return view('dashboard', compact('chartData', 'totalData'));
    }

}