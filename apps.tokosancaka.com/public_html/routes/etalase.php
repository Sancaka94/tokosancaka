<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\OrderController;

/*
|--------------------------------------------------------------------------
| ROUTE HALAMAN TOKO ONLINE (ETALASE PEMBELI - SAAS)
|--------------------------------------------------------------------------
*/

Route::domain('{subdomain}.tokosancaka.com')
    ->where(['subdomain' => '^(?!apps|admin|www).*$']) // <--- FIX: Bungkus menggunakan Array []
    ->middleware(['web'])
    ->group(function () {

        // 1. Halaman Utama Toko (Katalog Produk)
        Route::get('/', [StorefrontController::class, 'index'])->name('storefront.index');

        // 2. Halaman Keranjang & Checkout
        Route::get('/checkout', [StorefrontController::class, 'checkout'])->name('storefront.checkout');

        // 3. Proses Pembayaran
        Route::post('/checkout/process', [OrderController::class, 'store'])->name('storefront.process');

        // 4. (Opsional) Halaman Detail Produk
        Route::get('/produk/{id}', [StorefrontController::class, 'show'])->name('storefront.product');

        // 5. Halaman Sukses
        Route::get('/checkout/success/{orderNumber}', [StorefrontController::class, 'success'])->name('storefront.success');

    });
