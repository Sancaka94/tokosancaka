<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\OrderController;

/*
|--------------------------------------------------------------------------
| ROUTE HALAMAN TOKO ONLINE (ETALASE PEMBELI - SAAS)
|--------------------------------------------------------------------------
| Rute ini bersifat PUBLIK (tanpa middleware auth).
| Menggunakan Regex 'where' agar subdomain 'apps', 'admin', dan 'www'
| tidak dianggap sebagai toko/etalase.
*/

Route::domain('{subdomain}.tokosancaka.com')
    ->where('subdomain', '^(?!apps|admin|www).*$') // <--- FILTER ANTI-BENTROK
    ->middleware(['web']) // Tanpa 'auth' karena ini untuk pembeli umum
    ->group(function () {

        // 1. Halaman Utama Toko (Katalog Produk)
        Route::get('/', [StorefrontController::class, 'index'])->name('storefront.index');

        // 2. Halaman Keranjang & Checkout
        Route::get('/checkout', [StorefrontController::class, 'checkout'])->name('storefront.checkout');

        // 3. Proses Pembayaran (Tembak langsung ke fungsi store yang sudah canggih)
        Route::post('/checkout/process', [OrderController::class, 'store'])->name('storefront.process');

        // 4. (Opsional) Halaman Detail Produk
        Route::get('/produk/{id}', [StorefrontController::class, 'show'])->name('storefront.product');

    });
