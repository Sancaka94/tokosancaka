<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductDanaPpob;
use Illuminate\Support\Facades\Log;

class MarketplacePpobController extends Controller
{
    /**
     * Menampilkan halaman utama Marketplace PPOB (Form Pembelian Pulsa/Data)
     */
    public function index()
    {
        Log::info('LOG LOG - User accessing PPOB Marketplace page');

        // Ambil produk yang tersedia (aktif) dan urutkan dari harga termurah
        $products = ProductDanaPpob::where('is_available', true)
                        ->orderBy('price_value', 'asc')
                        ->get();

        // Mengarahkan tampilan ke resources/views/ppob/dana/index.blade.php
        return view('ppob.dana.index', compact('products'));
    }

    /*
    |--------------------------------------------------------------------------
    | CATATAN ARSITEKTUR UNTUK DEVELOPER:
    |--------------------------------------------------------------------------
    | Fungsi checkout() yang sebelumnya ada di sini TELAH DIHAPUS.
    | Seluruh pemrosesan transaksi (Validasi form, Potong Saldo, 
    | pembuatan Invoice, dan Hit API Tripay) sekarang ditangani secara 
    | terpusat oleh:
    | 
    | Class  : App\Http\Controllers\CheckoutController
    | Method : storePpobDanaPayment
    | Route  : POST /ppob/pay
    | 
    | Hal ini dilakukan agar kode tidak tumpang tindih dan PPOB bisa
    | menggunakan fasilitas Payment Gateway (Tripay/Doku) yang sudah
    | ada di sistem TokoSancaka.
    */
}