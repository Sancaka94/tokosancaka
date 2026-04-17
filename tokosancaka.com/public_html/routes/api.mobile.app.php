<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request; // Pastikan ini ada di bagian atas file jika belum ada
use App\Http\Controllers\Api\Mobile\KontakController;
use App\Http\Controllers\Api\Mobile\ScanSpxController; // <-- Tambahkan ini untuk kerapian
use App\Http\Controllers\Api\Mobile\OngkirController; // 1. Pastikan Import ini ada
use App\Http\Controllers\Api\Mobile\CustomerForgotPasswordController; // <-- TAMBAHAN UNTUK FORGOT PASSWORD
use App\Http\Controllers\Api\Mobile\ProfileController;
use App\Http\Controllers\Api\Mobile\PpobMobileController;
use App\Http\Controllers\Api\Mobile\KoliController;
use App\Http\Controllers\Api\Mobile\AuthController; // 2. Pastikan Import ini ada
use App\Http\Controllers\Api\Mobile\MarketplaceMobileController;
use App\Http\Controllers\Api\Mobile\LaporanKeuanganMobileController;
use App\Http\Controllers\Api\Mobile\ProdukSellerMobileController;
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

// ==========================================
// A. ROUTE MARKETPLACE PUBLIK (TIDAK BUTUH LOGIN)
// ==========================================
Route::prefix('marketplace')->group(function () {
    Route::get('/home', [MarketplaceMobileController::class, 'home']);
    Route::get('/category/{id}', [MarketplaceMobileController::class, 'showCategory']);
    Route::get('/product/{id}', [MarketplaceMobileController::class, 'showProduct']);
    Route::get('/store/{slug}', [MarketplaceMobileController::class, 'showStore']);
});

Route::post('/login', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'login']);
Route::post('/register', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'register']);
Route::post('/forgot-password', [CustomerForgotPasswordController::class, 'sendResetLinkApi']);

Route::post('/verify-token', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'verifyToken']);
Route::post('/resend-token', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'resendToken']);

// --- ENDPOINT UPDATE APLIKASI (SELF-HOSTED PLAYSTORE) ---
Route::get('/check-update', function(Request $request) {
    // 1. Tentukan versi rilis terbaru di server
    $latestVersion = '1.0.0'; // Ubah ini setiap kali ada update baru, bisa juga diambil dari database jika ingin lebih dinamis

    // 2. Tangkap versi aplikasi yang sedang dipakai user dari parameter URL
    $appVersion = $request->query('app_version');

    // 3. Logika perbandingan versi (PHP otomatis tahu 1.0.5 itu lebih besar dari 1.0.3)
    $needsUpdate = false;
    if ($appVersion) {
        // Jika versi HP (<) lebih kecil dari versi Server, maka butuh update
        $needsUpdate = version_compare($appVersion, $latestVersion, '<');
    } else {
        // Jika HP tidak mengirim versi (misal versi lama), paksa suruh cek update
        $needsUpdate = true;
    }

    return response()->json([
        'success'        => true,
        'received_version' => $appVersion,
        'needs_update'   => $needsUpdate, // <-- Kunci utamanya di sini
        'latest_version' => $latestVersion,
        'download_url'   => 'https://tokosancaka.com/public/assets/app/SancakaExpress.apk',
        'force_update'   => true,
        'notes'          => 'Fix bug dan peningkatan performa. Tambah Fitur Kirim Paket Banyak!'
    ]);
});

Route::prefix('public')->group(function () {
    // Tracking & Ekspedisi
    Route::get('/tracking/{resi}', [\App\Http\Controllers\Api\Mobile\TrackingController::class, 'track']);
    Route::post('/cek-ongkir', [\App\Http\Controllers\Api\Mobile\OngkirController::class, 'checkCost']);

    // --- UBAH/PASTIKAN DUA BARIS INI MENGARAH KE ONGKIRCONTROLLER ---
    Route::post('/cek-ongkir', [OngkirController::class, 'checkCost']);
    Route::get('/search-address', [OngkirController::class, 'searchAddress']);
    // ----------------------------------------------------------------

    // KiriminAja & Helper Alamat
    Route::get('/search-address', [\App\Http\Controllers\Api\Mobile\AddressController::class, 'search']);

    // Marketplace & PPOB Publik (Katalog)
    Route::get('/etalase', [\App\Http\Controllers\Api\Mobile\MarketplaceController::class, 'katalog']);
    Route::get('/ppob/pricelist', [\App\Http\Controllers\Api\Mobile\PpobController::class, 'pricelist']);
});

// -------------------------------------------------------------------------
// ROUTE DOWNLOAD PDF SURAT JALAN
// (Ditaruh di luar auth agar bisa dibuka oleh Chrome/Safari di HP)
// -------------------------------------------------------------------------
Route::get('/suratjalan/download/{kode_surat_jalan}', [ScanSpxController::class, 'downloadSuratJalan'])->name('api.suratjalan.download');


// =========================================================================
// 2. PROTECTED ROUTES (WAJIB BAWA TOKEN DARI HP - SANCTUM)
// =========================================================================
Route::middleware('auth:sanctum')->group(function () {

Route::get('/seller/dashboard', [\App\Http\Controllers\Api\Mobile\DashboardSellerController::class, 'index']);


Route::prefix('seller')->group(function () {

// CRUD Produk Seller Mobile
        Route::get('/produk', [\App\Http\Controllers\Api\Mobile\ProdukSellerMobileController::class, 'index']);
        Route::get('/produk/categories', [\App\Http\Controllers\Api\Mobile\ProdukSellerMobileController::class, 'getCategories']); // Untuk dropdown form
        Route::post('/produk', [\App\Http\Controllers\Api\Mobile\ProdukSellerMobileController::class, 'store']);
        Route::get('/produk/{slug}', [\App\Http\Controllers\Api\Mobile\ProdukSellerMobileController::class, 'show']);
        Route::post('/produk/{slug}/update', [\App\Http\Controllers\Api\Mobile\ProdukSellerMobileController::class, 'update']); // Pakai POST karena bawa file gambar (multipart/form-data)
        Route::delete('/produk/{slug}', [\App\Http\Controllers\Api\Mobile\ProdukSellerMobileController::class, 'destroy']);
    });



/// ========================================== RUTE MOBILE UNTUK PELANGGAN (CUSTOMER) ==========================================

    // BUNGKUS KHUSUS UNTUK PPOB
    Route::prefix('ppob')->group(function () {
        // INI DIA TERSANGKANYA! Pastikan rute ini tertulis BENAR.
        Route::get('/get_products', [PpobMobileController::class, 'getProductsPra']);

        Route::get('/pasca-pricelist', [PpobMobileController::class, 'getPricelistPasca']);
        Route::post('/pay-pasca', [PpobMobileController::class, 'payPostpaid']);

        Route::post('/inquiry_pln', [PpobMobileController::class, 'inquiryPln']);
        Route::post('/inquiry_ovo', [PpobMobileController::class, 'inquiryOvo']);
        Route::post('/gamelist', [PpobMobileController::class, 'getGameList']);
        Route::post('/inquiry_game_format', [PpobMobileController::class, 'inquiryGameFormat']);
        Route::post('/inquiry_game_server', [PpobMobileController::class, 'inquiryGameServer']);

        Route::post('/store', [PpobMobileController::class, 'store']);

        // Rute History
        Route::get('/history', [PpobMobileController::class, 'history']);
    });

// AKHIR DARI RUTE MOBILE UNTUK PELANGGAN (CUSTOMER)

    // ==========================================
    // MODULE: MANAJEMEN PESANAN & KOLI
    // ==========================================
    Route::prefix('pesanan')->group(function () {

        // 1. Endpoint untuk Cek Ongkir (Meneruskan ke KiriminAja)
        Route::post('/koli/cek-ongkir', [KoliController::class, 'cek_Ongkir']);

        // 2. Endpoint untuk Simpan Pesanan Satuan
        // (Dipanggil ketika user klik tombol "SIMPAN & BAYAR PAKET #1" di React Native)
        Route::post('/store-single', [KoliController::class, 'storeSingle']);

        // 3. Endpoint untuk Simpan Pesanan Massal
        // (Dipanggil ketika user klik tombol "Buat Pesanan" di akhir step jika ingin menyimpan semuanya sekaligus)
        Route::post('/store-multi', [KoliController::class, 'store']);

    });

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile/update', [ProfileController::class, 'update']);

    // ==========================================
    // RUTE MANAJEMEN KONTAK MOBILE
    // ==========================================
    Route::get('/customer/kontak', [\App\Http\Controllers\Api\Mobile\KontakController::class, 'index']);
    Route::post('/customer/kontak', [\App\Http\Controllers\Api\Mobile\KontakController::class, 'store']);
    Route::delete('/customer/kontak/{id}', [\App\Http\Controllers\Api\Mobile\KontakController::class, 'destroy']);
    Route::get('/customer/kontak/{id}/history', [\App\Http\Controllers\Api\Mobile\KontakController::class, 'history']);

    Route::get('/customer/pesanan/riwayat', [\App\Http\Controllers\Api\Mobile\PesananController::class, 'riwayat']);

    // Rute Pencarian Kontak Mobile
    Route::get('/customer/kontak', [KontakController::class, 'index']);

    // --- A. GENERAL AUTH & USER ---
    Route::get('/user/profile', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'user']);
    Route::post('/user/profile/update', [\App\Http\Controllers\Api\Mobile\ProfileController::class, 'update']);
    Route::post('/auth/logout', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'logout']);

    // --- B. DASHBOARD (Otomatis menyesuaikan Role) ---
    Route::get('/dashboard', [\App\Http\Controllers\Api\Mobile\DashboardController::class, 'index']);

    // --- C. CUSTOMER ROUTES (PELANGGAN) ---
    Route::prefix('customer')->group(function () {

        // ==========================================
        // SCAN SPX & SURAT JALAN (FITUR BARU)
        // ==========================================
        Route::get('/scan-spx/init', [ScanSpxController::class, 'initMobile']);
        Route::get('/scan-spx/history', [ScanSpxController::class, 'index']); // Paginasi data riwayat
        Route::get('/scan-spx/filter', [ScanSpxController::class, 'getHistory']); // Filter by periode
        Route::post('/scan-spx/store', [ScanSpxController::class, 'storeSpxScan']); // Proses scan & potong saldo
        Route::put('/scan-spx/{resi}', [ScanSpxController::class, 'update']); // Edit status
        Route::delete('/scan-spx/{resi}', [ScanSpxController::class, 'destroy']); // Hapus resi

        Route::post('/suratjalan/create', [ScanSpxController::class, 'createSuratJalan']); // Cetak SJ

        Route::get('/suratjalan/history', [ScanSpxController::class, 'historySuratJalan']);

        Route::get('/laporan-keuangan', [LaporanKeuanganMobileController::class, 'index']);

        // ==========================================

        // Manajemen Pengiriman (Kirim Satuan & Massal/Koli)
        Route::post('/pesanan/store-single', [\App\Http\Controllers\Api\Mobile\PesananController::class, 'storeSingle']);
        Route::post('/pesanan/store-multi', [\App\Http\Controllers\Api\Mobile\PesananController::class, 'storeMulti']);

        // Buku Alamat / Kontak
        Route::post('/kontak', [KontakController::class, 'store']);

        // Top Up & Saldo
        Route::get('/wallet/balance', [\App\Http\Controllers\Api\Mobile\WalletController::class, 'balance']);
        Route::post('/wallet/topup', [\App\Http\Controllers\Api\Mobile\WalletController::class, 'topup']);

        // --- API BARU: TOPUP (Dari ApiTopUpController) ---
        Route::get('/topup/methods', [\App\Http\Controllers\Api\Mobile\ApiTopUpController::class, 'getMethods']);
        Route::post('/topup/request', [\App\Http\Controllers\Api\Mobile\ApiTopUpController::class, 'requestTopUp']);
        Route::get('/topup/history', [\App\Http\Controllers\Api\Mobile\ApiTopUpController::class, 'history']);

        // --- API BARU: TOPUP (Dari ApiTopUpController) ---
        Route::get('/topup/methods', [\App\Http\Controllers\Api\Mobile\ApiTopUpController::class, 'getMethods']);
        Route::post('/topup/request', [\App\Http\Controllers\Api\Mobile\ApiTopUpController::class, 'requestTopUp']);
        Route::get('/topup/history', [\App\Http\Controllers\Api\Mobile\ApiTopUpController::class, 'history']);
        // --------------------------------------------------

        // ==========================================
        // MANAJEMEN PIN KEAMANAN & OTP WHATSAPP
        // ==========================================
        Route::post('/pin/register', [\App\Http\Controllers\Api\Mobile\ApiTopUpController::class, 'registerPin']);
        Route::post('/pin/edit', [\App\Http\Controllers\Api\Mobile\ApiTopUpController::class, 'editPin']);
        Route::post('/pin/verify', [\App\Http\Controllers\Api\Mobile\ApiTopUpController::class, 'verifyPin']);

        // Lupa PIN via OTP Fonnte
        Route::post('/pin/forgot/request-otp', [\App\Http\Controllers\Api\Mobile\ApiTopUpController::class, 'requestOtpResetPin']);
        Route::post('/pin/forgot/reset', [\App\Http\Controllers\Api\Mobile\ApiTopUpController::class, 'resetPinWithOtp']);
        // ==========================================

        // PPOB & Pembayaran Digital
        Route::post('/ppob/inquiry', [\App\Http\Controllers\Api\Mobile\PpobController::class, 'inquiry']); // Cek Tagihan
        Route::post('/ppob/pay', [\App\Http\Controllers\Api\Mobile\PpobController::class, 'pay']); // Bayar
        Route::get('/ppob/history', [\App\Http\Controllers\Api\Mobile\PpobController::class, 'history']);
        // --------------------------------------------------


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

        // Rute untuk List Chat & Badge Count
        Route::get('/chat/conversations', [\App\Http\Controllers\Api\Mobile\ChatController::class, 'getConversations']);
        Route::get('/chat/unread-count', [\App\Http\Controllers\Api\Mobile\ChatController::class, 'getUnreadCount']);

        //Delete Chat
        Route::post('/chat/delete', [\App\Http\Controllers\Api\Mobile\ChatController::class, 'deleteChat']);

        Route::post('/chat/delete-messages', [\App\Http\Controllers\Api\Mobile\ChatController::class, 'deleteSelectedMessages']);
    });

    // --- D. SELLER ROUTES (TOKO) ---
    Route::prefix('seller')->group(function () {

        // (Kalau ada rute /toko/info atau /pesanan-masuk, biarkan saja)

        // 👇 INI RUTE PRODUK YANG BENAR 👇
        Route::get('/produk', [\App\Http\Controllers\Api\Mobile\ProdukSellerMobileController::class, 'index']);
        Route::get('/produk/categories', [\App\Http\Controllers\Api\Mobile\ProdukSellerMobileController::class, 'getCategories']);
        Route::post('/produk', [\App\Http\Controllers\Api\Mobile\ProdukSellerMobileController::class, 'store']);
        Route::get('/produk/{slug}', [\App\Http\Controllers\Api\Mobile\ProdukSellerMobileController::class, 'show']);
        Route::post('/produk/{slug}/update', [\App\Http\Controllers\Api\Mobile\ProdukSellerMobileController::class, 'update']);
        Route::delete('/produk/{slug}', [\App\Http\Controllers\Api\Mobile\ProdukSellerMobileController::class, 'destroy']);
        Route::get('/produk/attributes/{categoryId}', [\App\Http\Controllers\Api\Mobile\ProdukSellerMobileController::class, 'getAttributes']);
        // 👆 ---------------------------- 👆

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


// ==========================================
// B. ROUTE MARKETPLACE PROTECTED (WAJIB LOGIN)
// ==========================================
Route::middleware('auth:sanctum')->prefix('marketplace')->group(function () {

    // --- Keranjang (Cart) ---
    Route::get('/cart', [MarketplaceMobileController::class, 'getCart']);
    Route::post('/cart/add', [MarketplaceMobileController::class, 'addToCart']);
    Route::post('/cart/update', [MarketplaceMobileController::class, 'updateCart']);
    Route::post('/cart/remove', [MarketplaceMobileController::class, 'removeFromCart']);
    Route::post('/cart/clear', [MarketplaceMobileController::class, 'clearCart']);

    // --- Checkout (Sistem Baru Database) ---
    Route::post('/checkout/init', [MarketplaceMobileController::class, 'initCheckout']);
    Route::get('/checkout/data', [MarketplaceMobileController::class, 'getCheckoutData']);
    Route::post('/checkout/process', [MarketplaceMobileController::class, 'processCheckout']);

    // --- Lain-lain ---
    Route::get('/orders', [MarketplaceMobileController::class, 'myOrders']);
    Route::post('/orders/{invoice}/cancel', [MarketplaceMobileController::class, 'cancelOrder']);
    Route::get('/store/{id}', [MarketplaceMobileController::class, 'showStore']);

});

Route::post('/customer/pesanan/cancel', [App\Http\Controllers\Api\Mobile\PesananController::class, 'cancelOrder']);
