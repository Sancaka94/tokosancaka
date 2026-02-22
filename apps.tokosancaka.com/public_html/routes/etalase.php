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

        Route::get('/product/{slug}', [App\Http\Controllers\StorefrontController::class, 'productDetail'])->name('storefront.product.detail');

    });

  // [BARU] API KHUSUS ETALASE (PUBLIK)
        Route::get('/api/search-location', [App\Http\Controllers\OrderController::class, 'searchLocation'])->name('storefront.api.location');
        Route::post('/api/check-ongkir', [App\Http\Controllers\OrderController::class, 'checkShippingRates'])->name('storefront.api.ongkir');
        Route::post('/api/check-coupon', [App\Http\Controllers\OrderController::class, 'checkCoupon'])->name('storefront.api.coupon');


// Pastikan ini berada di dalam grup route khusus subdomain apps/admin dan role superadmin
Route::middleware(['auth', 'role:superadmin'])->group(function () {
    Route::get('/escrow/disbursement', [\App\Http\Controllers\EscrowController::class, 'index'])->name('escrow.index');
    Route::post('/escrow/release/{order_id}', [\App\Http\Controllers\EscrowController::class, 'release'])->name('escrow.release');
});
