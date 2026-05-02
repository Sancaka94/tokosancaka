<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\CityTransaction; // Tambahkan baris ini untuk memanggil model transaksi
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class DashboardController extends Controller
{
    public function index()
    {
        // ==========================================
        // 1. DATA MASTER KOTA (Berdasarkan frekuensi)
        // ==========================================
        $chartData = City::selectRaw('nama_kota, COUNT(*) as total')
                         ->groupBy('nama_kota')
                         ->orderBy('total', 'desc')
                         ->get();

        $totalData = City::count();

        // ==========================================
        // 2. DATA TRANSAKSI (Berdasarkan jumlah input)
        // ==========================================
        // Menjumlahkan seluruh angka di kolom 'jumlah' pada tabel city_transactions
        $totalTransaksi = CityTransaction::sum('jumlah');

        // Melakukan Join tabel agar mendapatkan nama_kota berdasarkan city_id
        $chartDataTransaksi = CityTransaction::selectRaw('cities.nama_kota, SUM(city_transactions.jumlah) as total_jumlah')
            ->join('cities', 'city_transactions.city_id', '=', 'cities.id')
            ->groupBy('cities.nama_kota')
            ->orderBy('total_jumlah', 'desc')
            ->get();

        // Mengirim semua data ke view dashboard.blade.php
        return view('dashboard', compact('chartData', 'totalData', 'totalTransaksi', 'chartDataTransaksi'));
    }

    // Function untuk download/stream PDF
    public function exportPdf()
    {
        // Ambil data yang sama persis seperti di method index agar isi PDF sinkron
        $chartData = City::selectRaw('nama_kota, COUNT(*) as total')
                         ->groupBy('nama_kota')
                         ->orderBy('total', 'desc')
                         ->get();

        $totalData = City::count();

        $totalTransaksi = CityTransaction::sum('jumlah');

        $chartDataTransaksi = CityTransaction::selectRaw('cities.nama_kota, SUM(city_transactions.jumlah) as total_jumlah')
            ->join('cities', 'city_transactions.city_id', '=', 'cities.id')
            ->groupBy('cities.nama_kota')
            ->orderBy('total_jumlah', 'desc')
            ->get();

        // Load view khusus untuk PDF dan passing datanya
        $pdf = Pdf::loadView('dashboard-pdf', compact('chartData', 'totalData', 'totalTransaksi', 'chartDataTransaksi'));

        // Menggunakan stream agar terbuka di tab baru browser (tidak langsung terdownload otomatis)
        return $pdf->stream('laporan-data-analitik.pdf');
    }
}