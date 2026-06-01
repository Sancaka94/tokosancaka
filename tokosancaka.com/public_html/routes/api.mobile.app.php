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
use App\Http\Controllers\Api\Mobile\ChatController; // <-- Tambahkan import ini
use App\Http\Controllers\Admin\DashboardController; // <-- Tambahkan import ini untuk Broadcast
use App\Http\Controllers\Api\Mobile\DaftarMemberController;
use App\Http\Controllers\Api\Mobile\PpobDigiflazController;
use App\Http\Controllers\Api\Mobile\SettingPrivacyController;
use App\Http\Controllers\Api\Mobile\EditPenggunaController;
use App\Http\Controllers\Api\Mobile\PesananController;
use App\Http\Controllers\Api\Mobile\TicketingController;
use App\Http\Controllers\Customer\TopUpController;
use App\Http\Controllers\Api\Mobile\TrainTicketingController;

/*
|--------------------------------------------------------------------------
| API ROUTES KHUSUS APLIKASI MOBILE SANCAKA EXPRESS (EXPO)
|--------------------------------------------------------------------------
| Catatan Penting:
| Semua rute di bawah ini akan otomatis memiliki prefix '/api/mobile/'
| Pastikan Controller yang dipanggil me-return response()->json(), bukan view()
*/

Route::post('auth/dharmawisata/login', [TicketingController::class, 'handleDharmawisataLogin']);


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
Route::post('/login-pin', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'loginPin']); // <--- TARUH DI SINI
Route::post('/register', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'register']);
Route::post('/forgot-password', [CustomerForgotPasswordController::class, 'sendResetLinkApi']);

Route::post('/verify-token', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'verifyToken']);
Route::post('/resend-token', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'resendToken']);

Route::get('/verifikasi-email', [App\Http\Controllers\Api\Mobile\AuthController::class, 'verifyEmailFromLink']);

Route::get('/system-status', [\App\Http\Controllers\Admin\DashboardController::class, 'getSystemMode']);


// =========================================================================
// ROUTE WEBHOOK & CALLBACK DANA GATEWAY (TIDAK BUTUH LOGIN)
// =========================================================================
Route::post('/dana/notify', [\App\Http\Controllers\DanaWebhookController::class, 'handleNotify'])->name('dana.notify');
Route::get('/dana/return', [\App\Http\Controllers\DanaWebhookController::class, 'returnPage'])->name('dana.return');
Route::get('/dana/callback', [\App\Http\Controllers\Api\Mobile\DanaGatewayMobileController::class, 'handleCallback'])->name('dana.callback');


// --- ENDPOINT UPDATE APLIKASI (GOOGLE PLAY STORE) ---
Route::get('/check-update', function(Request $request) {
    // 1. Tentukan versi rilis terbaru di server
    $latestVersion = '1.1.12'; // Ubah ini setiap kali ada rilis versi baru di Play Store

    // 2. Tangkap versi aplikasi yang sedang dipakai user dari parameter URL
    $appVersion = $request->query('app_version');

    // 3. Logika perbandingan versi
    $needsUpdate = false;
    if ($appVersion) {
        $needsUpdate = version_compare($appVersion, $latestVersion, '<');
    } else {
        $needsUpdate = true;
    }

    return response()->json([
        'success'        => true,
        'received_version' => $appVersion,
        'needs_update'   => $needsUpdate,
        'latest_version' => $latestVersion,
        'download_url'   => 'https://play.google.com/store/apps/details?id=com.sancaka.express.app',
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

Route::get('/customer/dana/balance', [\App\Http\Controllers\Api\Mobile\DanaGatewayMobileController::class, 'checkMyDanaBalance']);

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


    Route::get('/customer/pesanan/riwayat', [\App\Http\Controllers\Api\Mobile\PesananController::class, 'riwayat']);

    // --- A. GENERAL AUTH & USER ---
    Route::get('/user/profile', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'user']);
    Route::post('/user/profile/update', [\App\Http\Controllers\Api\Mobile\ProfileController::class, 'update']);
    Route::post('/auth/logout', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'logout']);

    // --- B. DASHBOARD (Otomatis menyesuaikan Role) ---
    Route::get('/dashboard', [\App\Http\Controllers\Api\Mobile\DashboardController::class, 'index']);

    Route::post('/save-push-token', [\App\Http\Controllers\Api\Mobile\ChatController::class, 'savePushToken']);


    // --- C. CUSTOMER ROUTES (PELANGGAN) ---
    Route::prefix('customer')->group(function () {

        // ==========================================
        // BUKU ALAMAT / KONTAK (SUDAH LENGKAP & SINKRON)
        // ==========================================
        Route::get('/kontak', [\App\Http\Controllers\Api\Mobile\KontakController::class, 'index']);
        Route::post('/kontak', [\App\Http\Controllers\Api\Mobile\KontakController::class, 'store']);
        Route::put('/kontak/{id}', [\App\Http\Controllers\Api\Mobile\KontakController::class, 'update']); // <--- INI PENTING UNTUK EDIT
        Route::delete('/kontak/{id}', [\App\Http\Controllers\Api\Mobile\KontakController::class, 'destroy']);
        Route::get('/kontak/{id}/history', [\App\Http\Controllers\Api\Mobile\KontakController::class, 'history']);

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

        Route::get('/topup/methods', [\App\Http\Controllers\Api\Mobile\ApiTopUpController::class, 'getMethods']);

        Route::post('/topup/request', [\App\Http\Controllers\Api\Mobile\TopUpController::class, 'store']);

        Route::get('/topup/history', [\App\Http\Controllers\Api\Mobile\TopUpController::class, 'history']);

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

        Route::get('/chat/search-users', [\App\Http\Controllers\Api\Mobile\ChatController::class, 'searchUsers']);

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

        Route::post('/broadcast/send', [DashboardController::class, 'sendBroadcast']);
        Route::get('/broadcast/replies', [DashboardController::class, 'getBroadcastReplies']); // <--- TARUH DI SINI

        Route::get('/get-system-mode', [DashboardController::class, 'getSystemMode']);
        Route::post('/toggle-system-mode', [DashboardController::class, 'toggleSystemMode']);
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

// Rute untuk mendapatkan semua notifikasi milik user di HP
    Route::get('/notifications', [ChatController::class, 'getUnreadCount']); // Sesuaikan jika ada method list

    // Rute untuk menandai notifikasi sebagai sudah dibaca (Centang 2)
    Route::post('/notifications/mark-read', [ChatController::class, 'deleteSelectedMessages']);


    // Pastikan ini berada di dalam group middleware auth (login required)
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/customer/member/status', [DaftarMemberController::class, 'getStatus']);
    Route::post('/customer/member/register-agent', [DaftarMemberController::class, 'registerAgent']);
});

// Pastikan semua dibungkus di dalam auth:sanctum agar $request->user() terbaca
Route::middleware('auth:sanctum')->group(function () {

    // ==========================================
    // PPOB & Produk Digital Mobile API
    // ==========================================
    Route::prefix('ppob')->group(function () {
        // Mengambil daftar produk berdasarkan kategori (pulsa, data, pln, dll)
        Route::get('/products/{category}', [PpobDigiflazController::class, 'getProductsByCategory']);

        // Pengecekan (Inquiry)
        Route::post('/inquiry/pasca', [PpobDigiflazController::class, 'checkBill']);
        Route::post('/inquiry/pln', [PpobDigiflazController::class, 'checkPlnPrabayar']);

        // Transaksi / Pembayaran
        Route::post('/transaction', [PpobDigiflazController::class, 'processTransaction']);

        Route::get('/history-digi', [PpobDigiflazController::class, 'getHistory']);
    });

    // ==========================================
    // KHUSUS ADMIN
    // ==========================================
    // Route ini sekarang aman di dalam pelukan Sanctum
    Route::get('/admin/ppob/cek-saldo', [PpobDigiflazController::class, 'cekSaldo']);

});

Route::middleware(['auth:sanctum'])->group(function () {
    // Route Pengaturan Privasi & Keamanan (Sancaka)
    Route::post('/user/update-email', [SettingPrivacyController::class, 'updateEmail']);
    Route::post('/user/update-password', [SettingPrivacyController::class, 'updatePassword']);
    Route::post('/user/update-pin', [SettingPrivacyController::class, 'updatePin']);

        // --- KHUSUS ADMIN (ID 4) ---
    Route::get('/admin/pengguna/{id}', [EditPenggunaController::class, 'show']);
    Route::put('/admin/pengguna/{id}', [EditPenggunaController::class, 'update']);

    Route::get('/admin/pengguna', [EditPenggunaController::class, 'index']); // Ambil semua
    Route::delete('/admin/pengguna/{id}', [EditPenggunaController::class, 'destroy']); // Hapus

    Route::get('/customer/pesanan/detail/{resi}', [\App\Http\Controllers\Api\Mobile\PesananController::class, 'getDetailPesanan']);

    Route::get('/dashboard/dana-balance', [\App\Http\Controllers\Customer\TopUpController::class, 'checkMyDanaBalance']);
    // ==========================================
    // MODULE: TICKETING / DARMAWISATA
    // ==========================================
    Route::prefix('ticketing')->group(function () {

        // Session & Agent (Asumsi fungsi ini sudah Anda buat sebelumnya)
        Route::post('/session/login', [TicketingController::class, 'sessionLogin']);
        Route::post('/agent/balance', [TicketingController::class, 'agentBalance']);
        Route::post('/init-session', [TicketingController::class, 'generateNewToken']);

        // ==========================================
        // MODULE: AIRLINE (TIKET PESAWAT)
        // ==========================================
        Route::prefix('airline')->group(function () {

            // Pencarian Utama (Schedule All Airline)
            Route::post('/search', [TicketingController::class, 'airlineSearch']);

            // Data Master / Referensi
            Route::post('/list', [TicketingController::class, 'airlineList']);
            Route::post('/route', [TicketingController::class, 'airlineRoute']);
            Route::post('/nationality', [TicketingController::class, 'airlineNationality']);
            Route::post('/lowfare-route', [TicketingController::class, 'airlineLowFareRoute']);
            Route::post('/city', [TicketingController::class, 'airlineCity']);

            // Flow Pencarian Spesifik (Single Airline) & Low Fare
            Route::post('/schedule', [TicketingController::class, 'airlineScheduleSingle']); // Diperbaiki
            Route::post('/lowfare-schedule', [TicketingController::class, 'airlineLowFareSchedule']);

            // Flow Harga
            Route::post('/price', [TicketingController::class, 'airlinePriceSingle']); // Diperbaiki
            Route::post('/price-all', [TicketingController::class, 'airlinePriceAllAirline']); // Dirapikan (Hapus yang duplikat)

            // Flow Addons
            Route::post('/baggageAndMeal', [TicketingController::class, 'baggageAndMeal']);
            Route::post('/seat', [TicketingController::class, 'airlineSeat']);

            // Rute untuk simpan data ke database
            Route::post('/save-db', [TicketingController::class, 'saveToDatabase']);
            Route::get('/local-orders', [TicketingController::class, 'getLocalOrders']);

            Route::post('/local-orders/delete', [TicketingController::class, 'deleteLocalOrders']);

            // Rute untuk eksekusi booking ke Darmawisata
            Route::post('/process-booking', [TicketingController::class, 'processBooking']);

            // Flow Transaksi & Manajemen
            Route::post('/booking', [TicketingController::class, 'airlineBooking']);
            Route::post('/booking-list', [TicketingController::class, 'airlineBookingList']);
            Route::post('/booking-detail', [TicketingController::class, 'airlineBookingDetail']);
            Route::post('/issued', [TicketingController::class, 'airlineIssued']);
            Route::post('/local-detail', [TicketingController::class, 'getLocalBookingDetail']);

            // Flow Sistem Khusus
            Route::post('/timer-elapsed', [TicketingController::class, 'airlineTimerElapsed']);
        });


        // ==========================================
        // MODULE: TRAIN (TIKET KERETA API) -> PINDAHKAN KE SINI!
        // ==========================================
        Route::prefix('train')->group(function () {
            // Endpoint List & Pencarian Jadwal
            Route::post('/list', [TrainTicketingController::class, 'trainList']);
            Route::post('/route', [TrainTicketingController::class, 'trainRoute']);
            Route::post('/schedule', [TrainTicketingController::class, 'trainSchedule']);

            // Endpoint Flow Transaksi
            Route::post('/booking', [TrainTicketingController::class, 'trainBooking']);
            Route::post('/seatmap', [TrainTicketingController::class, 'trainSeatMap']);
            Route::post('/takeseat', [TrainTicketingController::class, 'trainTakeSeat']);
            Route::post('/issued', [TrainTicketingController::class, 'trainIssued']);

            // Endpoint Riwayat & Manajemen Tiket
            Route::post('/booking-list', [TrainTicketingController::class, 'trainBookingList']);
            Route::post('/booking-detail', [TrainTicketingController::class, 'trainBookingDetail']);
            Route::post('/cancel', [TrainTicketingController::class, 'trainCancel']);

        });

            // Hotel
            Route::prefix('hotel')->group(function () {
                Route::post('/search', [TicketingController::class, 'hotelSearch']);
                Route::post('/available-rooms', [TicketingController::class, 'hotelAvailableRooms']);
                Route::post('/booking', [TicketingController::class, 'hotelBooking']);
            });

            // PPOB & TopUp
            Route::prefix('ppob')->group(function () {
                Route::post('/inquiry', [TicketingController::class, 'ppobInquiry']);
                Route::post('/payment', [TicketingController::class, 'ppobPayment']);
            });

            Route::prefix('topup')->group(function () {
                Route::post('/product', [TicketingController::class, 'topupProduct']);
                Route::post('/order', [TicketingController::class, 'topupOrder']);
            });

        }); // END MODULE TICKETING

    });
