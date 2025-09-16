<?php

namespace App\Http\Controllers\Toko;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman dashboard untuk seller.
     */
    public function index()
    {
        $user = Auth::user();
        $store = $user->store; // Mengambil data toko dari relasi

        // Di sini Anda bisa menambahkan logika untuk mengambil data statistik toko
        // Contoh: jumlah produk, pesanan baru, total pendapatan, dll.

        // Kirim data ke view
        return view('seller.dashboard', [
            'store' => $store,
            // 'jumlahProduk' => $jumlahProduk,
            // 'pesananBaru' => $pesananBaru,
        ]);
    }
}