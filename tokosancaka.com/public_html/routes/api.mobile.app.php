<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API ROUTES KHUSUS APLIKASI MOBILE SANCAKA EXPRESS (EXPO)
|--------------------------------------------------------------------------
| Catatan Penting:
| Semua rute di bawah ini akan otomatis memiliki prefix '/api/mobile/'
| Pastikan Controller yang dipanggil me-return response()->json(), bukan view()
*/

// =========================================================================
// 1. PUBLIC ROUTES (TIDAK BUTUH LOGIN / TOKEN)
// =========================================================================
Route::prefix('auth')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'login']);
    Route::post('/register', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'register']);
});

Route::prefix('public')->group(function () {
    // Tracking & Ekspedisi
    Route::get('/tracking/{resi}', [\App\Http\Controllers\Api\Mobile\TrackingController::class, 'track']);
    Route::post('/cek-ongkir', [\App\Http\Controllers\Api\Mobile\OngkirController::class, 'checkCost']);

    // KiriminAja & Helper Alamat
    Route::get('/search-address', [\App\Http\Controllers\Api\Mobile\AddressController::class, 'search']);

    // Marketplace & PPOB Publik (Katalog)
    Route::get('/etalase', [\App\Http\Controllers\Api\Mobile\MarketplaceController::class, 'katalog']);
    Route::get('/ppob/pricelist', [\App\Http\Controllers\Api\Mobile\PpobController::class, 'pricelist']);
});

// =========================================================================
// 2. PROTECTED ROUTES (WAJIB BAWA TOKEN DARI HP - SANCTUM)
// =========================================================================
Route::middleware('auth:sanctum')->group(function () {

    // --- A. GENERAL AUTH & USER ---
    Route::get('/user/profile', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'user']);
    Route::post('/user/profile/update', [\App\Http\Controllers\Api\Mobile\ProfileController::class, 'update']);
    Route::post('/auth/logout', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'logout']);

    // --- B. DASHBOARD (Otomatis menyesuaikan Role) ---
    Route::get('/dashboard', [\App\Http\Controllers\Api\Mobile\DashboardController::class, 'index']);

    // --- C. CUSTOMER ROUTES (PELANGGAN) ---
    Route::prefix('customer')->group(function () {

        // Manajemen Pengiriman (Kirim Satuan & Massal/Koli)
        Route::post('/pesanan/store-single', [\App\Http\Controllers\Api\Mobile\PesananController::class, 'storeSingle']);
        Route::post('/pesanan/store-multi', [\App\Http\Controllers\Api\Mobile\PesananController::class, 'storeMulti']);
        Route::get('/pesanan/riwayat', [\App\Http\Controllers\Api\Mobile\PesananController::class, 'riwayat']);

        // Buku Alamat / Kontak
        Route::get('/kontak', [\App\Http\Controllers\Api\Mobile\KontakController::class, 'index']);
        Route::post('/kontak', [\App\Http\Controllers\Api\Mobile\KontakController::class, 'store']);

        // Top Up & Saldo
        Route::get('/wallet/balance', [\App\Http\Controllers\Api\Mobile\WalletController::class, 'balance']);
        Route::post('/wallet/topup', [\App\Http\Controllers\Api\Mobile\WalletController::class, 'topup']);

        // PPOB & Pembayaran Digital
        Route::post('/ppob/inquiry', [\App\Http\Controllers\Api\Mobile\PpobController::class, 'inquiry']); // Cek Tagihan
        Route::post('/ppob/pay', [\App\Http\Controllers\Api\Mobile\PpobController::class, 'pay']); // Bayar
        Route::get('/ppob/history', [\App\Http\Controllers\Api\Mobile\PpobController::class, 'history']);

        // Marketplace Cart & Checkout
        Route::get('/cart', [\App\Http\Controllers\Api\Mobile\CartController::class, 'index']);
        Route::post('/cart/add', [\App\Http\Controllers\Api\Mobile\CartController::class, 'add']);
        Route::post('/checkout', [\App\Http\Controllers\Api\Mobile\CheckoutController::class, 'process']);

        // Chat CS
        Route::get('/chat/messages', [\App\Http\Controllers\Api\Mobile\ChatController::class, 'fetchMessages']);
        Route::post('/chat/send', [\App\Http\Controllers\Api\Mobile\ChatController::class, 'sendMessage']);

        // Notifikasi
        Route::get('/notifications', [\App\Http\Controllers\Api\Mobile\NotificationController::class, 'index']);
        Route::post('/notifications/read-all', [\App\Http\Controllers\Api\Mobile\NotificationController::class, 'markAllRead']);
    });

    // --- D. SELLER ROUTES (TOKO) ---
    // Pastikan middleware Role berjalan jika role = Seller
    Route::prefix('seller')->group(function () {
        Route::get('/toko/info', [\App\Http\Controllers\Api\Mobile\SellerController::class, 'info']);
        Route::get('/produk', [\App\Http\Controllers\Api\Mobile\SellerProductController::class, 'index']);
        Route::post('/produk', [\App\Http\Controllers\Api\Mobile\SellerProductController::class, 'store']);
        Route::get('/pesanan-masuk', [\App\Http\Controllers\Api\Mobile\SellerOrderController::class, 'incoming']);
        Route::post('/pesanan/{id}/terima', [\App\Http\Controllers\Api\Mobile\SellerOrderController::class, 'accept']);
    });

    // --- E. ADMIN ROUTES (KHUSUS ADMIN) ---
    Route::prefix('admin')->group(function () {
        // Laporan Keuangan Mobile
        Route::get('/laporan/ringkasan', [\App\Http\Controllers\Api\Mobile\AdminFinanceController::class, 'summary']);

        // Operasional & Scan SPX
        Route::post('/scan-spx', [\App\Http\Controllers\Api\Mobile\AdminScanController::class, 'scan']);
        Route::get('/scan-spx/monitor', [\App\Http\Controllers\Api\Mobile\AdminScanController::class, 'monitor']);

        // Manajemen Pengguna
        Route::get('/users', [\App\Http\Controllers\Api\Mobile\AdminUserController::class, 'index']);

        // Pencairan Dana / Escrow
        Route::get('/escrow/pending', [\App\Http\Controllers\Api\Mobile\AdminEscrowController::class, 'pending']);
        Route::post('/escrow/{id}/cairkan', [\App\Http\Controllers\Api\Mobile\AdminEscrowController::class, 'cairkan']);
    });

});
