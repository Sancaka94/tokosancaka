<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf; // Tambahkan baris ini

class DashboardController extends Controller
{
    public function index()
    {
        // Mengambil data kota dan jumlahnya
        $chartData = City::selectRaw('nama_kota, COUNT(*) as total')
                         ->groupBy('nama_kota')
                         ->orderBy('total', 'desc')
                         ->get();

        // Menghitung total keseluruhan baris di tabel cities
        $totalData = City::count();

        // Mengirim data ke view dashboard.blade.php
        return view('dashboard', compact('chartData', 'totalData'));
    }

    // Function baru untuk download PDF
    public function exportPdf()
    {
        // Ambil data yang sama seperti di method index
        $chartData = City::selectRaw('nama_kota, COUNT(*) as total')
                         ->groupBy('nama_kota')
                         ->orderBy('total', 'desc')
                         ->get();

        $totalData = City::count();

        // Load view khusus untuk PDF dan passing datanya
        // 'dashboard-pdf' adalah nama file blade yang akan kita buat nanti
        $pdf = Pdf::loadView('dashboard-pdf', compact('chartData', 'totalData'));

        // Mengunduh file PDF dengan nama 'laporan-data-kota.pdf'
        return $pdf->stream('laporan-data-kota.pdf');
        
        // Catatan: Jika ingin melihatnya di browser (tanpa langsung download), 
        // gunakan return $pdf->stream('laporan-data-kota.pdf');
    }
}