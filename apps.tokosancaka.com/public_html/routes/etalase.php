<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\OrderController;

Route::domain('{subdomain}.tokosancaka.com')
    ->where(['subdomain' => '^(?!apps|admin|www).*$'])
    ->middleware(['web'])
    ->group(function () {

        // Halaman Utama & Pencarian
        Route::get('/', [StorefrontController::class, 'index'])->name('storefront.index');

        // Halaman Filter Kategori
        Route::get('/kategori/{slug}', [StorefrontController::class, 'category'])->name('storefront.category');

        // Halaman Keranjang
        Route::get('/cart', [StorefrontController::class, 'cart'])->name('storefront.cart');

        // Halaman Checkout
        Route::get('/checkout', [StorefrontController::class, 'checkout'])->name('storefront.checkout');

        // Proses Pembayaran (Langsung terhubung ke OrderController Backend)
        Route::post('/checkout/process', [OrderController::class, 'store'])->name('storefront.process');

        // Halaman Sukses Transaksi
        Route::get('/checkout/success/{orderNumber}', [StorefrontController::class, 'success'])->name('storefront.success');

    });
