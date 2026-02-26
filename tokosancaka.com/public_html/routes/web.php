<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use App\Http\Middleware\RoleMiddleware;
use App\Services\KiriminAjaService;

// =========================================================================
// 1. IMPORT CONTROLLER (LENGKAP)
// =========================================================================

// Auth & Users
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ProfileSetupController;
use App\Http\Controllers\Customer\ProfileController as CustomerProfileController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\Customers\DataPenggunaController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\PublicPelangganController;
use App\Http\Controllers\Admin\CustomerController;

// Core Logic
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\WilayahController;
use App\Http\Controllers\KodePosController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\PondokController;
use App\Http\Controllers\Api\KontakController; // API
use App\Http\Controllers\Customer\KontakController as CustomerKontakController;
use App\Http\Controllers\SeminarController;

// Products & Marketplace
use App\Http\Controllers\EtalaseController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\Customer\MarketplaceController as CustomerMarketplaceController;
use App\Http\Controllers\Admin\MarketplaceController as AdminMarketplaceController;
use App\Http\Controllers\Admin\MarketplaceController; // Alias jika dipakai
use App\Http\Controllers\Customer\CategoryController;
use App\Http\Controllers\Admin\CategoryAttributeController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\CartController; // <--- TAMBAHKAN INI

// Orders & Shipping
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Customer\CheckoutController as CustomerCheckoutController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\CekOngkirController;
use App\Http\Controllers\CourierController;
use App\Http\Controllers\Admin\PesananController as AdminPesananController;
use App\Http\Controllers\Admin\PesananController; // Alias
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\SpxScanController;
use App\Http\Controllers\Admin\BarcodeController;
use App\Http\Controllers\Customer\KoliController;
use App\Http\Controllers\Admin\KoliController as AdminKoliController;
use App\Http\Controllers\KirimAjaController;

// Payment
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DanaController;
use App\Http\Controllers\DokuPaymentController;
use App\Http\Controllers\TestOrderController;
use App\Http\Controllers\Admin\WalletController;
use App\Http\Controllers\Admin\AdminSaldoTransferController;

// PPOB & Digital
use App\Http\Controllers\PpobController;
use App\Http\Controllers\PpobProductController;
use App\Http\Controllers\Customer\PpobCheckoutController;
use App\Http\Controllers\Customer\PpobHistoryController;
use App\Http\Controllers\Customer\AgentProductController;
use App\Http\Controllers\Customer\AgentRegistrationController;
use App\Http\Controllers\Customer\TopUpController;
use App\Http\Controllers\Customer\AgentTransactionController;
use App\Http\Controllers\Admin\AdminPpobController;
use App\Http\Controllers\DigiflazzWebhookController;

// Chat & Support
use App\Http\Controllers\Admin\ChatController as AdminChatController;
use App\Http\Controllers\CustomerChatController;
use App\Http\Controllers\Admin\EmailController;
use App\Http\Controllers\Admin\ImapController;
use App\Http\Controllers\NotifikasiCustomerController;


// Admin Tools
use App\Http\Controllers\Admin\LaporanKeuanganController;
use App\Http\Controllers\Admin\CoaController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ApiSettingsController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\Admin\AdminLogController;
use App\Http\Controllers\SellerRegisterController;
use App\Http\Controllers\SellerReviewController;
use App\Http\Controllers\Admin\PerizinanController;

use App\Http\Controllers\InvoiceController;

use App\Http\Controllers\CashflowController;

// Website fontend WA Integration
//use App\Http\Controllers\WhatsappController;

use App\Http\Controllers\Toko\DokuRegistrationController;

//Tools Broadcast Pesan WA
use App\Http\Controllers\BroadcastController;

//Aplikasi Python AI Detection
use App\Http\Controllers\DetectionController;

//Telegram Bot
use App\Http\Controllers\TelegramPpobController;

// Dashboard DANA Merchant
use App\Http\Controllers\Customer\DashboardController;

// DATA LAPORAN KEUANGAN
use App\Http\Controllers\Admin\KeuanganController;

use App\Http\Controllers\Admin\LabaRugiController;

use App\Http\Controllers\Admin\AkuntansiController;

use App\Http\Controllers\PushWaController;

Route::get('/cek-sistem-sancaka', function () {
    // 1. Cek Koneksi Database
    try {
        DB::connection()->getPdo();
        $dbStatus = "BERHASIL TERHUBUNG ✅";
        $dbName = DB::connection()->getDatabaseName();
    } catch (\Exception $e) {
        $dbStatus = "GAGAL TERHUBUNG ❌: " . $e->getMessage();
        $dbName = "-";
    }

    // 2. Ambil Data Sistem
    $data = [
        'Server IP (Shared/VPS)' => $_SERVER['SERVER_ADDR'] ?? 'Hidden',
        'Client IP (Anda)' => $_SERVER['REMOTE_ADDR'],
        'Hostname' => gethostname(),
        'PHP Version' => phpversion(),
        'Framework' => app()->version(),
        'Environment' => app()->environment(),
        '--- DATABASE INFO ---' => '----------------',
        'DB Connection Status' => $dbStatus,
        'DB Host (Config)' => Config::get('database.connections.mysql.host'),
        'DB Port' => Config::get('database.connections.mysql.port'),
        'DB Database Name' => $dbName,
        'DB Username' => Config::get('database.connections.mysql.username'),
        '--- PATH INFO ---' => '----------------',
        'Base Path' => base_path(),
        'Public Path' => public_path(),
    ];

    // Tampilkan
    echo "<pre style='background:#1a202c; color:#00ff00; padding:20px; font-family:monospace; font-size:14px;'>";
    echo "=== SANCAKA SYSTEM DIAGNOSTIC ===\n\n";
    foreach ($data as $key => $value) {
        echo str_pad($key, 25) . ": " . $value . "\n";
    }
    echo "</pre>";
});


Route::any('/telegram-webhook', [TelegramPpobController::class, 'handle']);

// 1. Jalur Utama AI (Menerima Gambar dari Kamera)
Route::post('/detect/process', [DetectionController::class, 'process'])->name('detection.process');

// 1. Route Halaman Utama Scanner
Route::get('/apps', function () {
    return view('apps');
})->name('apps.index');

Route::get('/about', function () {
    return view('about');
})->name('about');


// ROUTE UTAMA CETAK THERMAL (Top Level)
// Menggunakan parameter {resi} agar bisa menangkap ID Transaksi/Resi secara dinamis
Route::get('/{resi}/cetak_thermal', [PesananController::class, 'cetakThermal'])
    ->name('cetak.thermal.clean');

// Route::get('/blog', [BlogController::class, 'blogIndex'])->name('blog.index');

// =========================================================================
// 2. PUBLIC ROUTES (GUEST / AKSES UMUM)
// =========================================================================

Route::get('/', function () { return view('home'); })->name('home');
Route::get('/privacy-policy', function () { return view('privacy-policy'); })->name('privacy.policy');
Route::get('/terms-and-conditions', function () { return view('terms'); })->name('terms.conditions');

// Auth Files
require __DIR__.'/auth.php';
if(file_exists(__DIR__.'/web/auth.php')) require __DIR__.'/web/auth.php';
if(file_exists(__DIR__.'/web/public.php')) require __DIR__.'/web/public.php';
if(file_exists(__DIR__.'/web/pondok.php')) require __DIR__.'/web/pondok.php';

// Register Success
Route::get('/register/success/{no_wa}', function ($no_wa) {
    return view('auth.register-success', compact('no_wa'));
})->name('register.success');
Route::get('customer/profile/setup/{token}', [CustomerProfileController::class, 'setup'])->name('customer.profile.setup');


// BENAR
Route::post('/webhook/fonnte', [WhatsappController::class, 'webhook']);

// ==========================
// WHATSAPP INTEGRATION
// ==========================

Route::get('/whatsapp', [WhatsappController::class, 'index'])->name('whatsapp.index');
Route::post('/whatsapp/send', [WhatsappController::class, 'sendMessage'])->name('whatsapp.send');


// Tracking & Ongkir (Public)
Route::get('/tracking', [TrackingController::class, 'showTrackingPage'])->name('tracking.index');
Route::get('/tracking/search', [TrackingController::class, 'showTrackingPage'])->name('tracking.search');
Route::get('/tracking/refresh', [TrackingController::class, 'refresh'])->name('tracking.refresh');
Route::get('/tracking/cetak-thermal/{resi}', [TrackingController::class, 'cetakThermal'])->name('tracking.cetak_thermal');
Route::get('/tracking/cetak-resi/{resi}', [TrackingController::class, 'cetakThermal'])->name('cetak_thermal');
// Route untuk mem-proxy gambar bukti pengiriman
Route::get('/tracking/image-proxy', [App\Http\Controllers\TrackingController::class, 'imageProxy'])->name('tracking.image_proxy');


Route::get('/kirimaja/cek-ongkir', [CustomerOrderController::class, 'cek_Ongkir'])->name('kirimaja.cekongkir');
Route::get('/koli/cek-ongkir', [KoliController::class, 'cekOngkirMulti'])->name('public.koli.cekOngkirMulti'); // Akses publik
Route::post('/koli/cek-ongkir', [KoliController::class, 'cekOngkirMulti'])->name('koli.cekOngkirMulti');

// KiriminAja API
Route::get('/kirimaja/set_callback', [KirimAjaController::class, 'setCallback']);
Route::get('/kiriminaja/search-address', function (Request $request, KiriminAjaService $kiriminAja) {
    $query = $request->get('q');
    if (!$query) return response()->json(['status' => false, 'text' => 'Query kosong', 'data' => []]);
    return response()->json($kiriminAja->searchAddress($query));
});

Route::get('/api/cari-alamat', [CustomerOrderController::class, 'searchAddressApi'])->name('api.address.search');
// Route API Pencarian Alamat (Global Auth)
Route::get('/api/cari-alamat-kontak', [App\Http\Controllers\Customer\KontakController::class, 'searchAddressApi'])
    ->name('api.alamat.search');

// Marketplace Public
Route::get('/etalase', [EtalaseController::class, 'index'])->name('etalase.index');
Route::get('/etalase/category/{category:slug}', [EtalaseController::class, 'showCategory'])->name('public.categories.show');
Route::get('/etalase/kategori/{slug}', [EtalaseController::class, 'showCategory'])->name('etalase.category-show');
Route::get('/products/{product:slug}', [EtalaseController::class, 'show'])->name('products.show');
Route::get('/toko/{name}', [EtalaseController::class, 'profileToko'])->name('toko.profile');
Route::get('/marketplace', [CustomerMarketplaceController::class, 'index'])->name('katalog.index'); // Nama route ini penting
Route::get('/marketplace/category/{category:slug}', [CategoryController::class, 'show'])->name('marketplace.categories.show');
Route::get('/pelanggan', [PublicPelangganController::class, 'index'])->name('pelanggan.public.index');

    // Fitur Multi Koli Admin (YANG BARU ANDA BUAT)
    Route::get('/admin/pesanan/buat-multi', [KoliController::class, 'create'])->name('admin.pesanan.create_multi');
    Route::post('/admin/pesanan/store-multi', [KoliController::class, 'store'])->name('admin.koli.store');
    Route::post('/pesanan/store-single', [KoliController::class, 'storeSingle'])->name('admin.koli.store_single');
    Route::post('/cek-ongkir', [KoliController::class, 'cek_Ongkir'])->name('admin.koli.cek_ongkir');

// PPOB Public
Route::get('/daftar-harga', [PublicController::class, 'pricelist'])->name('public.pricelist');
Route::get('/layanan/{slug}', [PublicController::class, 'showCategory'])->name('public.category');
Route::get('/debug-digi', [PpobController::class, 'debugDirect']);

// Group Etalase PPOB
Route::prefix('etalase/ppob')->name('ppob.')->group(function () {
    Route::get('/digital/{slug}', [PpobController::class, 'index'])->name('category');
    Route::post('/check-bill', [PpobController::class, 'checkBill'])->name('check.bill');
    Route::post('/check-pln-prabayar', [PpobController::class, 'checkPlnPrabayar'])->name('check.pln.prabayar');
    Route::post('/transaction', [PpobController::class, 'store'])->name('store');
});

// AJAX PPOB Public
Route::post('/ppob/check-bill', [PpobController::class, 'checkBill'])->name('ppob.check.bill');
Route::post('/ppob/check-pln-prabayar', [PpobController::class, 'checkPlnPrabayar'])->name('ppob.check.pln.prabayar');

// Blog & Content
Route::get('/feed', [BlogController::class, 'generateFeed'])->name('feed');
Route::get('/blog/posts/{post}', [BlogController::class, 'show']);
Route::get('/load-more-posts', [BlogController::class, 'loadMore'])->name('blog.posts.loadMore');
Route::get('/pondok', [PondokController::class, 'index'])->name('pondok.index');

// Utilities
Route::get('/cek-ip-hosting', function () {
    try {
        $response = \Illuminate\Support\Facades\Http::withoutVerifying()->get('https://api.ipify.org?format=json');
        return response()->json(['real_ip_hosting' => $response->json()['ip']]);
    } catch (\Exception $e) { return "Gagal: " . $e->getMessage(); }
});
Route::get('/controllers-list', function () {
    $files = File::allFiles(app_path('Http/Controllers'));
    $controllers = collect($files)->map(function ($file) {
        $relativePath = str_replace(app_path('Http/Controllers') . '/', '', $file->getPathname());
        return 'App\\Http\\Controllers\\' . str_replace(['/', '.php'], ['\\', ''], $relativePath);
    });
    return view('controllers-list', compact('controllers'));
});

// Test Doku
Route::get('/test/doku/simple', [TestOrderController::class, 'testSimplePayment'])->name('test.doku.simple');
Route::get('/test/doku/marketplace', [TestOrderController::class, 'testMarketplacePayment'])->name('test.doku.marketplace');

// =========================================================================
// 3. WEBHOOKS & CALLBACKS (NO AUTH)
// =========================================================================
Route::post('/digiflazz/webhook', [DigiflazzWebhookController::class, 'handle'])->name('digiflazz.webhook');
Route::post('/dana/notification', [DanaController::class, 'handleNotification'])->name('dana.payment.notify');
Route::post('/callback/tripay', [CheckoutController::class, 'TripayCallback'])->name('payment.callback.tripay');
Route::get('/pesanan/get-tripay-channels', [App\Http\Controllers\Admin\PesananController::class, 'getTripayChannels'])->name('admin.pesanan.get_channels');
Route::get('/customer/pesanan/get-channels', [App\Http\Controllers\Customer\PesananController::class, 'getTripayChannels'])->name('customer.pesanan.get_channels');

Route::prefix('payment')->group(function () {
    Route::post('/callback', [PaymentController::class, 'handleCallback'])->name('payment.callback');
    Route::post('/callback/refund', [PaymentController::class, 'handleRefundCallback'])->name('payment.callback.refund');
    Route::post('/callback/code', [PaymentController::class, 'handleCodeCallback'])->name('payment.callback.code');
});


// =========================================================================
// 4. AUTHENTICATED ROUTES (GENERAL)
// =========================================================================
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard Redirect Logic
    Route::get('/dashboard', function () {
        $user = auth()->user();
        if ($user->role === 'Admin') return redirect()->route('admin.dashboard');
        return redirect()->route('customer.dashboard');
    })->name('dashboard');

    // ✅ 1. ROUTE KHUSUS (WAJIB DI ATAS)
    // URL: /customer/pesanan/riwayat-belanja
    Route::get('/customer/pesanan/riwayat-belanja', [App\Http\Controllers\Customer\PesananController::class, 'riwayatBelanja'])
        ->name('customer.pesanan.riwayat_belanja');

    Route::get('/customer/pesanan/riwayat', [App\Http\Controllers\Customer\PesananController::class, 'riwayat'])
        ->name('customer.pesanan.riwayat');

    // Route Refresh Status DOKU
    Route::post('/seller/doku/refresh-status', [DokuRegistrationController::class, 'refreshDokuStatus'])
        ->name('seller.doku.refresh_status');

    Route::get('/customer/dashboard', function () { return view('dashboard'); })
        ->middleware(RoleMiddleware::class . ':Pelanggan')->name('customer.dashboard');
    Route::get('/admin/dashboard', function () { return view('admin.dashboard'); })
        ->middleware(RoleMiddleware::class . ':Admin')->name('admin.dashboard');
    Route::get('/seller/dashboard', function () { return view('seller.dashboard'); })
        ->middleware(RoleMiddleware::class . ':Seller')->name('seller.dashboard');

    // Seller Register
    Route::get('/seller/register', [SellerRegisterController::class, 'create'])->name('seller.register.form');
    Route::post('/seller/register', [SellerRegisterController::class, 'store'])->name('seller.register.submit');
    Route::get('customer/seller/register', [SellerRegisterController::class, 'create'])->name('customer.seller.register.form'); // Alias

    // User Profile
    Route::get('/user/profile', function () { return view('profile.show'); })->name('profile.show');

    // Reviews
    Route::post('/reviews', [ProductReviewController::class, 'store'])->name('reviews.store');

    // Payment Auth
    Route::get('/bayar/{orderId}', [PaymentController::class, 'createPayment'])->name('payment.create');
    Route::get('/payment/finish', [PaymentController::class, 'finishPage'])->name('payment.finish');
    Route::post('/payment/create-example', [DokuPaymentController::class, 'createPayment'])->name('doku.create.example');

    Route::prefix('dana')->name('dana.')->group(function () {
        Route::get('/create-payment/{order}', [DanaController::class, 'createPayment'])->name('payment.create');
        Route::get('/payment-finish', [DanaController::class, 'handleFinishRedirect'])->name('payment.finish');
    });

    // PPOB Internal Checkout & Ajax
    Route::post('/checkout-ppob/prepare', [PpobCheckoutController::class, 'prepare'])->name('ppob.prepare');
    Route::get('/checkout-ppob', [PpobCheckoutController::class, 'index'])->name('ppob.checkout.index');
    Route::post('/checkout-ppob/process', [PpobCheckoutController::class, 'store'])->name('ppob.checkout.store');
    Route::get('/checkout-ppob/remove/{id}', [PpobCheckoutController::class, 'removeItem'])->name('ppob.cart.remove');
    Route::post('/checkout-ppob/clear', [PpobCheckoutController::class, 'clearCart'])->name('ppob.cart.clear');
    Route::get('/ppob/invoice/{invoice}', [PpobCheckoutController::class, 'invoice'])->name('ppob.invoice');

    Route::prefix('digital')->name('ppob.')->group(function () {
        Route::post('/checkout', [PpobController::class, 'store'])->name('store');
        Route::get('/status/{ref_id}', [PpobController::class, 'checkStatus'])->name('status');
        Route::get('/cek-saldo', [PpobController::class, 'cekSaldo'])->name('cek-saldo');
        // Ajax
        Route::post('/ajax/check-pln-prabayar', [PpobController::class, 'checkPlnPrabayar'])->name('check.pln.prabayar');
        Route::post('/ajax/check-bill', [PpobController::class, 'checkBill'])->name('check.bill');
        Route::get('/ajax/pdam-products', [PpobController::class, 'getPdamProducts'])->name('ajax.pdam-products');
        Route::get('/kategori/{slug}', [PpobController::class, 'category'])->name('category');
    });

    // Sync PPOB
    Route::prefix('ppob')->name('ppob.')->group(function () {
        Route::get('/sync/prepaid', [PpobProductController::class, 'syncPrepaid'])->name('sync.prepaid');
        Route::get('/sync/postpaid', [PpobProductController::class, 'syncPostpaid'])->name('sync.postpaid');
    });

    // Helper Address
    Route::get('/search-address', [CekOngkirController::class, 'searchAddress'])->name('customer.search.address');
    Route::post('/check-cost', [CekOngkirController::class, 'checkCost'])->name('customer.check.cost');

    // Seller Review Reply (Auth General)
    Route::get('/seller/reviews', [SellerReviewController::class, 'index'])->name('seller.reviews.index');
    Route::post('/seller/reviews/{review}/reply', [SellerReviewController::class, 'reply'])->name('seller.reviews.reply');
    Route::put('/seller/reviews/{review}/reply', [SellerReviewController::class, 'updateReply'])->name('seller.reviews.reply.update');
    Route::delete('/seller/reviews/{review}/reply', [SellerReviewController::class, 'deleteReply'])->name('seller.reviews.reply.delete');

    Route::get('/api/contacts/search', [App\Http\Controllers\Customer\KontakController::class, 'search'])->name('api.contacts.search');

    Route::resource('customer/pesanan', App\Http\Controllers\Customer\PesananController::class);
});


// =========================================================================
// 5. CUSTOMER ROUTES (ROLE: PELANGGAN & MIXED)
// =========================================================================

Route::middleware(['auth', RoleMiddleware::class . ':Pelanggan'])->prefix('customer')->name('customer.')->group(function () {
    if(file_exists(__DIR__.'/web/customer.php')) require __DIR__.'/web/customer.php';

    // Marketplace & Cart
    Route::get('/marketplace', [CustomerMarketplaceController::class, 'index'])->name('marketplace.index');
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/add-ppob', [CartController::class, 'addPpob'])->name('cart.addPpob');
    Route::patch('/cart/update', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');

    // Checkout Barang
    Route::get('/checkout', [CustomerCheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CustomerCheckoutController::class, 'store'])->name('checkout.store');

    // PPOB History
    Route::prefix('ppob')->name('ppob.')->group(function () {
        Route::get('/history', [PpobHistoryController::class, 'index'])->name('history');
        Route::get('/export/excel', [PpobHistoryController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [PpobHistoryController::class, 'exportPdf'])->name('export.pdf');
    });

    // Kontak
    Route::get('/kontak/search', [CustomerKontakController::class, 'search'])->name('kontak.search');
    Route::prefix('kontak')->name('kontak.')->group(function () {
        Route::get('/', [CustomerKontakController::class, 'index'])->name('index');
        Route::post('/', [CustomerKontakController::class, 'store'])->name('store');
        Route::get('/{kontak}/edit', [CustomerKontakController::class, 'edit'])->name('edit');
        Route::put('/{kontak}', [CustomerKontakController::class, 'update'])->name('update');
        Route::delete('/{kontak}', [CustomerKontakController::class, 'destroy'])->name('destroy');
    });

    // Chat
    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/', [CustomerChatController::class, 'index'])->name('index');
        Route::get('/messages', [CustomerChatController::class, 'fetchMessages'])->name('fetchMessages');
        Route::post('/messages', [CustomerChatController::class, 'sendMessage'])->name('sendMessage');
    });

    // Notifikasi
    Route::get('/notifications/unread', [NotifikasiCustomerController::class, 'getUnread'])->name('notifications.unread');
    Route::get('/notifications', [NotifikasiCustomerController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotifikasiCustomerController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/read-all', [NotifikasiCustomerController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');

    // Address Search
    Route::get('/api/kiriminaja/address-search', [CustomerProfileController::class, 'searchKiriminAjaAddress'])->name('kiriminaja.address_search');
});

// SHARED ROUTES (Pelanggan & Seller)
Route::middleware(['auth', RoleMiddleware::class . ':Pelanggan|Seller'])->prefix('customer')->name('customer.')->group(function () {

    // --- [FIX 404 UTAMA] PESANAN MULTI KOLI ---
    // Definisikan semua kemungkinan URL yang Anda pakai
    Route::get('/pesanan/multi/create', [KoliController::class, 'create'])->name('koli.create');
    Route::get('/pesanan/create/multi-koli', [KoliController::class, 'create'])->name('koli.create_legacy'); // Fix jika view panggil URL ini
    Route::post('/pesanan/multi/store', [KoliController::class, 'store'])->name('koli.store');

    Route::post('/koli/cek-ongkir', [KoliController::class, 'cek_Ongkir'])->name('koli.cek_ongkir');
    Route::post('/koli/store-single', [KoliController::class, 'storeSingle'])->name('koli.store_single');

    // API DANA

    // Routes Merchant DANA
    // === ROUTE KHUSUS MERCHANT DANA ===
    Route::prefix('merchant')->name('merchant.')->group(function() {
        // 1. Halaman List Toko (Index)
        Route::get('/', [DashboardController::class, 'indexShop'])->name('index');

        // 2. Halaman Form Tambah Toko (Create)
        Route::get('/create', [DashboardController::class, 'createShopForm'])->name('create');

        // 3. Proses Simpan ke API (Store)
        Route::post('/store', [DashboardController::class, 'storeShop'])->name('store');

        // 4. Data CRUD Lainnya (Edit, Update) DANA SHOP
        Route::get('/edit/{id}', [DashboardController::class, 'editShopForm'])->name('edit');
        Route::post('/update/{id}', [DashboardController::class, 'updateShop'])->name('update');
    });


}); // Penutup Prefix Customer Shared

// Invoice (Sering diakses lintas role)
Route::get('/invoice/{invoice}', [CustomerCheckoutController::class, 'invoice'])->middleware('auth')->name('checkout.invoice');
Route::get('/customer/pesanan/export-pdf', [AdminPesananController::class, 'exportPdf'])->middleware('auth')->name('customer.pesanan.export_pdf');

// Helper Seller Register
Route::middleware('auth')->group(function() {
    Route::get('seller/address/search', [SellerRegisterController::class, 'searchAddressKiriminAja'])->name('seller.address.search');
    Route::post('seller/address/geocode', [SellerRegisterController::class, 'geocodeAddress'])->name('seller.address.geocode');


});

// Checkout Invoice (Auth General) Marketplace Etalase
Route::middleware('auth')->group(function() {
     Route::get('/invoice/{invoice}', [CheckoutController::class, 'invoice'])->name('checkout.invoice');
});

// Agent Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/agent/register', [AgentRegistrationController::class, 'index'])->name('agent.register.index');
    Route::post('/agent/register/process', [AgentRegistrationController::class, 'register'])->name('agent.register.process');

    Route::get('/topup', [TopUpController::class, 'index'])->name('topup.index');
    Route::post('/topup', [TopUpController::class, 'store'])->name('topup.store');
    Route::get('/topup/{topup}', [TopUpController::class, 'show'])->name('customer.topup.show');
    Route::post('/topup/{reference_id}/upload', [TopUpController::class, 'uploadProof'])->name('topup.upload_proof');
    Route::get('/agent/ppob/cities', [AgentTransactionController::class, 'getPbbCities'])->name('admin.ppob.get-pbb-cities');

    Route::middleware(['is_agent'])->prefix('agent')->name('agent.')->group(function () {
        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', [AgentProductController::class, 'index'])->name('index');
            Route::put('/update', [AgentProductController::class, 'update'])->name('update');
            Route::post('/bulk-update', [AgentProductController::class, 'bulkUpdate'])->name('bulk_update');
        });
        Route::get('/transaksi/create', [AgentTransactionController::class, 'create'])->name('transaction.create');
        Route::post('/transaksi/store', [AgentTransactionController::class, 'store'])->name('transaction.store');
    });
});

// Seller Group
Route::middleware(['auth', RoleMiddleware::class . ':Seller|Admin'])->prefix('seller')->name('seller.')->group(function () {
    if(file_exists(__DIR__.'/web/seller.php')) require __DIR__.'/web/seller.php';
});


// =========================================================================
// 6. ADMIN ROUTES (ROLE: ADMIN)
// =========================================================================

Route::prefix('broadcast')->name('broadcast.')->group(function () {

    // 1. Halaman Utama (Form Kirim & Tabel Riwayat)
    Route::get('/', [BroadcastController::class, 'index'])->name('index');

    // 2. Proses Kirim Pesan (Ke Fonnte)
    Route::post('/send', [BroadcastController::class, 'send'])->name('send');

    Route::post('/broadcast/generate-ai', [BroadcastController::class, 'generateAi'])->name('broadcast.ai');

    // 3. Fitur Export Laporan
    Route::get('/export-excel', [BroadcastController::class, 'exportExcel'])->name('export.excel');
    Route::get('/export-pdf', [BroadcastController::class, 'exportPdf'])->name('export.pdf');

    // 4. Hapus Riwayat
    Route::delete('/{id}', [BroadcastController::class, 'destroy'])->name('destroy');

    Route::post('/generate-ai', [BroadcastController::class, 'generateAi'])->name('ai');

    Route::delete('/history/clear-all', [BroadcastController::class, 'destroyAll'])->name('destroy.all');

});

Route::prefix('admin/akuntansi')->name('admin.akuntansi.')->group(function () {
    Route::get('/', [AkuntansiController::class, 'index'])->name('index');
    Route::get('/create', [AkuntansiController::class, 'create'])->name('create');
    Route::post('/store', [AkuntansiController::class, 'store'])->name('store');

    // Route Khusus Sinkronisasi
    Route::post('/sync', [AkuntansiController::class, 'syncData'])->name('sync');

    // Edit & Delete
    Route::get('/edit/{id}', [AkuntansiController::class, 'edit'])->name('edit');
    Route::put('/update/{id}', [AkuntansiController::class, 'update'])->name('update');
    Route::delete('/delete/{id}', [AkuntansiController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth', RoleMiddleware::class . ':Admin'])->prefix('admin')->name('admin.')->group(function () {

    if(file_exists(__DIR__.'/web/admin.php')) require __DIR__.'/web/admin.php';
    if(file_exists(__DIR__.'/admin/orders.php')) require __DIR__.'/admin/orders.php';

    // Settings
    Route::view('/setting', 'admin.setting')->name('settings');
    Route::get('/setting-info-pesanan', [AdminController::class, 'editInfoPesanan'])->name('info.edit');
    Route::post('/setting-info-pesanan', [AdminController::class, 'updateInfoPesanan'])->name('info.update');
    Route::get('/settings/api', [ApiSettingsController::class, 'index'])->name('settings.api.index');
    Route::put('/settings/api', [ApiSettingsController::class, 'update'])->name('settings.api.update');
    Route::post('/settings/api', [ApiSettingsController::class, 'toggle'])->name('settings.api.toggle');

    Route::get('customers/data/pengguna/', [DataPenggunaController::class, 'index'])->name('customers.pengguna.index');
    Route::get('customers/data/pengguna/export', [DataPenggunaController::class, 'export'])->name('customers.pengguna.export');

    // Users
    Route::resource('customers/data/pengguna', DataPenggunaController::class)->names('customers.data.pengguna');
    Route::post('/users/{user}/toggle-freeze', [UserController::class, 'toggleFreeze'])->name('users.toggle-freeze');

    Route::resource('pelanggan', PelangganController::class);
    Route::prefix('pelanggan')->name('pelanggan.')->group(function () {
        Route::post('/import-excel', [PelangganController::class, 'importExcel'])->name('import.excel');
        Route::get('/export-excel', [PelangganController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export-pdf', [PelangganController::class, 'exportPdf'])->name('export.pdf');
    });
    // Route::get('/pengguna/export', [PelangganController::class, 'export'])->name('customers.pengguna.export');

    // Marketplace
    Route::resource('stores', AdminMarketplaceController::class)->names('stores');
    Route::get('/marketplace', [AdminMarketplaceController::class, 'index'])->name('marketplace.index');
    Route::post('/marketplace', [AdminMarketplaceController::class, 'store'])->name('marketplace.store');
    Route::get('/marketplace/{product}', [AdminMarketplaceController::class, 'show'])->name('marketplace.show');
    Route::put('/marketplace/{product}', [AdminMarketplaceController::class, 'update'])->name('marketplace.update');
    Route::delete('/marketplace/{product}', [AdminMarketplaceController::class, 'destroy'])->name('marketplace.destroy');

    // Product Specs
    Route::get('products/{slug}/specifications', [ProductController::class, 'editSpecifications'])->name('products.edit.specifications');
    Route::put('products/{slug}/specifications', [ProductController::class, 'updateSpecifications'])->name('products.update.specifications');
    Route::get('categories/{category}/attributes', [ProductController::class, 'getAttributes'])->name('categories.attributes');

    Route::prefix('category-attributes')->name('category-attributes.')->group(function () {
        Route::get('/', [CategoryAttributeController::class, 'index'])->name('index');
        Route::post('/{category}', [CategoryAttributeController::class, 'store'])->name('store');
        Route::get('/{attribute}/edit', [CategoryAttributeController::class, 'edit'])->name('edit');
        Route::put('/{attribute}', [CategoryAttributeController::class, 'update'])->name('update');
        Route::delete('/{attribute}', [CategoryAttributeController::class, 'destroy'])->name('destroy');
    });

    // Ajax Category
    Route::post('/categories/ajax-store', [App\Http\Controllers\Admin\CategoryController::class, 'storeAjax'])->name('categories.storeAjax');
    Route::delete('/categories/ajax-delete/{id}', [App\Http\Controllers\Admin\CategoryController::class, 'destroyAjax'])->name('categories.destroyAjax');

    // Orders & Koli Admin
    Route::get('/{resi}/cetak_thermal', [AdminPesananController::class, 'cetakResiThermal'])->name('cetak_thermal');
    Route::get('/pesanan/buat-multi', [AdminKoliController::class, 'create'])->name('pesanan.create_multi');
    Route::post('/pesanan/store-multi', [AdminKoliController::class, 'store'])->name('koli.store');
    Route::post('/pesanan/store-single', [AdminKoliController::class, 'storeSingle'])->name('koli.store_single');
    Route::post('/cek-ongkir', [AdminKoliController::class, 'cek_Ongkir'])->name('koli.cek_ongkir');

    // Couriers
    Route::get('/couriers/search', [CourierController::class, 'search'])->name('api.couriers.search');
    Route::get('couriers/{id}/scan', [CourierController::class, 'showScanPage'])->name('couriers.scan');
    Route::get('couriers/{id}/track', [CourierController::class, 'trackLocation'])->name('couriers.track');
    Route::get('couriers/{id}/print', [CourierController::class, 'printDeliveryOrder'])->name('couriers.print');
    Route::resource('couriers', CourierController::class);

    // SPX & Barcode
    Route::resource('spx-scans', SpxScanController::class)->names('spx_scans');
    Route::get('/surat-jalan/monitor', [SpxScanController::class, 'showMonitorPage'])->name('suratjalan.monitor.index');
    Route::get('/surat-jalan/monitor/export-pdf', [SpxScanController::class, 'exportMonitorPdf'])->name('suratjalan.monitor.export_pdf');
    Route::get('/surat-jalan/{kode_surat_jalan}/download', [SpxScanController::class, 'downloadSuratJalan'])->name('suratjalan.download');
    Route::get('/spx-scans/create', [SpxScanController::class, 'create'])->name('spx_scans.create');
    Route::get('/generate-barcode-zoom', [BarcodeController::class, 'generateBarcode'])->name('barcode.generate');

    // Finance
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/topup', [WalletController::class, 'topup'])->name('wallet.topup');
    Route::get('/wallet/search', [WalletController::class, 'search'])->name('wallet.search');

    // Laporan Keuangan Lengkap

    Route::prefix('laporan')->name('laporan.')->group(function () {
    // Pemasukan
    Route::get('pemasukan', [KeuanganController::class, 'pemasukan'])->name('pemasukan');
    Route::post('pemasukan', [KeuanganController::class, 'storePemasukan'])->name('pemasukan.store');

    // Pengeluaran
    Route::get('pengeluaran', [KeuanganController::class, 'pengeluaran'])->name('pengeluaran');
    Route::post('pengeluaran', [KeuanganController::class, 'storePengeluaran'])->name('pengeluaran.store');

    // Laporan Keuangan
    Route::get('laba-rugi', [KeuanganController::class, 'labaRugi'])->name('labaRugi');
    Route::get('neraca-saldo', [KeuanganController::class, 'neracaSaldo'])->name('neracaSaldo');
    Route::get('neraca', [KeuanganController::class, 'neraca'])->name('neraca');
    });

    Route::get('coa/export/excel', [CoaController::class, 'exportExcel'])->name('coa.export.excel');
    Route::get('coa/export/pdf', [CoaController::class, 'exportPdf'])->name('coa.export.pdf');
    Route::get('coa/import', [CoaController::class, 'showImportForm'])->name('coa.import.form');
    Route::post('coa/import', [CoaController::class, 'importExcel'])->name('coa.import.excel');
    Route::get('coa/import/template', [CoaController::class, 'downloadTemplate'])->name('coa.import.template');
    Route::resource('coa', CoaController::class)->except(['show']);

    // Content
    Route::get('/import/wordpress', [ImportController::class, 'showForm'])->name('import.wordpress.form');
    Route::post('/import/wordpress', [ImportController::class, 'handleImport'])->name('import.wordpress.handle');
    Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::get('/post/{post:slug}', [PostController::class, 'show'])->name('posts.post-detail');

    Route::resource('banners', BannerController::class);
    Route::get('/sliders', [SliderController::class, 'index'])->name('sliders.index');
    Route::post('/sliders', [SliderController::class, 'store'])->name('sliders.store');
    Route::delete('/sliders/{slide}', [SliderController::class, 'destroy'])->name('sliders.destroy');

    Route::resource('reviews', AdminReviewController::class);
    Route::post('reviews/{review}/reply', [AdminReviewController::class, 'reply'])->name('reviews.reply');

    Route::get('/logs', [AdminLogController::class, 'showLogs'])->name('logs.show');
    Route::post('/logs/clear', [AdminLogController::class, 'clearLogs'])->name('logs.clear');

    // 1. Route Export (Wajib ditaruh DI ATAS resource)
    Route::get('keuangan/laba-rugi', [App\Http\Controllers\Admin\LabaRugiController::class, 'index'])->name('keuangan.laba_rugi');
    Route::get('keuangan/laba-rugi/export-excel', [App\Http\Controllers\Admin\LabaRugiController::class, 'exportExcel'])->name('keuangan.laba_rugi.export_excel');
    Route::get('keuangan/laba-rugi/export-pdf', [App\Http\Controllers\Admin\LabaRugiController::class, 'exportPdf'])->name('keuangan.laba_rugi.export_pdf');
    Route::get('keuangan/export-excel', [KeuanganController::class, 'exportExcel'])->name('keuangan.export_excel');
    Route::get('keuangan/export-pdf', [KeuanganController::class, 'exportPdf'])->name('keuangan.export_pdf');
    Route::post('keuangan/sync-today', [App\Http\Controllers\Admin\KeuanganController::class, 'syncHariIni'])->name('keuangan.sync');
    Route::get('keuangan/neraca', [App\Http\Controllers\Admin\KeuanganController::class, 'neraca'])->name('keuangan.neraca');

    // DATA LAPORAN KEUANGAN
    Route::resource('keuangan', KeuanganController::class)->except(['create', 'show', 'edit']);

    Route::resource('coa', CoaController::class);

    Route::resource('ekspedisi', \App\Http\Controllers\Admin\EkspedisiController::class)->except(['create', 'show', 'edit']);

    // Wilayah & Kode Pos
    Route::get('/wilayah', [WilayahController::class, 'index'])->name('wilayah.index');
    Route::prefix('wilayah')->name('wilayah.')->group(function() {
        Route::get('/', [WilayahController::class, 'index'])->name('index');
        Route::get('/api/provinces', [WilayahController::class, 'getProvinces'])->name('api.provinces');
        Route::get('/api/regencies/{province}', [WilayahController::class, 'getRegencies'])->name('api.regencies');
        Route::get('/api/districts/{regency}', [WilayahController::class, 'getDistricts'])->name('api.districts');
        Route::get('/api/villages/{district}', [WilayahController::class, 'getVillages'])->name('api.villages');
        Route::post('/', [WilayahController::class, 'store'])->name('store');
        Route::put('/{id}', [WilayahController::class, 'update'])->name('update');
        Route::delete('/{id}', [WilayahController::class, 'destroy'])->name('destroy');
        Route::get('/province/{province}/regencies', [WilayahController::class, 'getKabupaten'])->name('kabupaten');
        Route::get('/regency/{regency}/districts', [WilayahController::class, 'getKecamatan'])->name('kecamatan');
        Route::get('/district/{district}/villages', [WilayahController::class, 'getDesa'])->name('desa');
    });

    Route::get('/kode-pos', [KodePosController::class, 'index'])->name('kodepos.index');
    Route::post('/kode-pos/import', [KodePosController::class, 'import'])->name('kodepos.import');

    // Email & Chat
    Route::get('/imap', [EmailController::class, 'index'])->name('imap.index');
    Route::get('/imap/{id}', [EmailController::class, 'show'])->name('imap.show');
    Route::delete('/imap/{id}', [EmailController::class, 'destroy'])->name('imap.destroy');

    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/', [AdminChatController::class, 'index'])->name('index');
        Route::get('/start', [AdminChatController::class, 'start'])->name('start');
        Route::get('/search-users', [AdminChatController::class, 'searchUsers'])->name('searchUsers');
        Route::get('/messages/{user}', [AdminChatController::class, 'fetchMessages'])->name('messages');
        Route::post('/messages/{user}', [AdminChatController::class, 'sendMessage'])->name('send');
    });
    Route::get('/api/contacts/search', [AdminChatController::class, 'searchKontak'])->name('api.contacts.search');

    // =====================================================================
    // PPOB ADMIN (FIX FINAL: EXCEL & PDF - SUPPORT TITIK & STRIP)
    // =====================================================================
    Route::prefix('ppob')->name('ppob.')->group(function () {

        // 1. HALAMAN UTAMA
        Route::get('/produk', [PpobProductController::class, 'index'])->name('product.index');
        Route::get('/digital', [PpobProductController::class, 'index'])->name('index');
        Route::get('/data', [AdminPpobController::class, 'index'])->name('data.index');

        // 2. EXPORT DATA TRANSAKSI (AdminPpobController)
        // Kita beri 2 nama route sekaligus agar view manapun yang panggil tetap jalan

        // Excel
        Route::get('/data/export/excel', [AdminPpobController::class, 'exportExcel'])->name('data.export.excel'); // Versi Titik
        Route::get('/data/export-excel', [AdminPpobController::class, 'exportExcel'])->name('export-excel');      // Versi Strip
        Route::get('/data/export-excel-alias', [AdminPpobController::class, 'exportExcel'])->name('export.excel'); // Alias Titik Pendek

        // PDF (INI YANG ERROR TADI)
        Route::get('/data/export/pdf', [AdminPpobController::class, 'exportPdf'])->name('data.export.pdf');   // Versi Titik
        Route::get('/data/export-pdf', [AdminPpobController::class, 'exportPdf'])->name('export-pdf');        // Versi Strip (YANG DICARI ERROR)
        Route::get('/data/export-pdf-alias', [AdminPpobController::class, 'exportPdf'])->name('export.pdf');  // Alias Titik Pendek

        // 3. EXPORT DATA PRODUK (PpobProductController)
        Route::get('/product-export/excel', [PpobProductController::class, 'exportExcel'])->name('product.export.excel');
        Route::get('/product-export/pdf', [PpobProductController::class, 'exportPdf'])->name('product.export.pdf');

        // 4. HELPER & ACTIONS
        Route::post('/bulk-update', [PpobProductController::class, 'bulkUpdate'])->name('bulk-update');
        Route::put('/update-price/{id}', [PpobProductController::class, 'updatePrice'])->name('update-price');

        Route::post('/deposit', [AdminPpobController::class, 'requestDeposit'])->name('deposit');
        Route::get('/cek-saldo', [AdminPpobController::class, 'cekSaldo'])->name('cek-saldo');
        Route::post('/topup', [AdminPpobController::class, 'topup'])->name('topup');

        Route::get('/transaction/{id}', [AdminPpobController::class, 'show'])->name('transaction.show');
        Route::put('/transaction/{id}', [AdminPpobController::class, 'update'])->name('transaction.update');
        Route::delete('/transaction/{id}', [AdminPpobController::class, 'destroy'])->name('transaction.destroy');
        Route::get('/transaction/destroy/{id}', [AdminPpobController::class, 'destroy'])->name('transaction.destroy.get');

        Route::delete('/destroy/{id}', [PpobProductController::class, 'destroy'])->name('destroy');

        // Ubah PpobProductController menjadi AdminPpobController
        Route::get('/{id}', [AdminPpobController::class, 'show'])->name('show');
    });
});


// Route untuk Admin menyetujui pengiriman data ID ke Customer
Route::get('/admin/approve-data-request/{no_wa}', [TelegramPpobController::class, 'approveDataRequest'])
    ->name('admin.approve.data');


// Group Route untuk Blog Publik
Route::controller(BlogController::class)->group(function () {

    // Halaman Index Blog (Tempat Slider & List Berita berada)
    // Nama route ini PENTING karena dipanggil di view blade Anda
    Route::get('/blog', 'blogIndex')->name('blog.posts.index');

    // Halaman Detail Postingan
    Route::get('/blog/posts/{post:slug}', 'show')->name('blog.posts.show');
    // ATAU jika pakai logic manual di controller show($slug):
    // Route::get('/blog/posts/{slug}', 'show')->name('blog.posts.show');

    // Halaman About
    Route::get('/about', 'about')->name('about');

    // Feed RSS (Opsional)
    Route::get('/feed', 'generateFeed')->name('feed');
});

Route::post('/topup/consult-methods', [TopUpController::class, 'consultPaymentMethods'])
    ->name('topup.consult')
    ->middleware('auth'); // Pastikan user login

// Route untuk Halaman Return DANA
Route::get('/dana/return', [TopUpController::class, 'returnPage'])->name('dana.return');

// Route untuk Cek Status Manual (Fix Error 'Route not defined')
Route::get('/dana/status/{orderId}', [TopUpController::class, 'checkDanaGatewayStatus'])->name('dana.status');

// Route Webhook (PENTING: Jangan lupa exclude dari CSRF di VerifyCsrfToken/app.php)
Route::post('/dana/notify', [TopUpController::class, 'handleNotify'])->name('dana.notify');

// Route untuk cek manual (misal oleh Admin)
Route::get('/dana/check-gateway/{orderId}', [TopUpController::class, 'checkDanaGatewayStatus'])
    ->name('dana.check_gateway');

// Route untuk halaman Pusat Bisnis
Route::get('/customer/business-center', function () {
    return view('customer.business.index');
})->middleware(['auth', 'verified'])->name('customer.business.index');

Route::get('/tembak-webhook-manual', function () {
    // Konfigurasi
    $token = 'ff78e56f84a91283cb2d46098f43677fc77a6470d13b510c502e1e2e4e927fd6';
    $urlWebhook = 'https://tokosancaka.com/api/webhook/kiriminaja';
    $endpointAPI = 'https://client.kiriminaja.com/api/mitra/set_callback';

    // Siapkan Data
    $data = ['url' => $urlWebhook];

    // Mulai CURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpointAPI);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);

    // Eksekusi
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Tampilkan Hasil
    echo "<h1>Status Code: $httpCode</h1>";
    echo "<pre>";
    print_r(json_decode($response, true));
    echo "</pre>";

    return "Selesai eksekusi script pancingan.";
});

// -----------------------------------API PUSH WA-----------------------------------//

// Masukkan ke dalam group admin auth jika perlu
Route::group(['prefix' => 'admin', 'middleware' => ['auth']], function () {

    // Route untuk Scan WA
    Route::get('/wa/scan', [PushWaController::class, 'scan'])->name('admin.wa.scan');
    Route::get('/fonnte/scan', [App\Http\Controllers\FonnteController::class, 'scan'])->name('admin.fonnte.scan');

});

// ----------------------------Pentup PUSH WA--------------------------------------//

// Halaman Public (Formulir)
Route::get('/formulir-perizinan', [PerizinanController::class, 'create'])->name('perizinan.form');
Route::post('/formulir-perizinan', [PerizinanController::class, 'store'])->name('perizinan.store');

// Halaman Admin (CRUD) - Pastikan sudah ada middleware auth/admin jika perlu
Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('perizinan', PerizinanController::class);
});


// ====================================================
// 1. BAGIAN PUBLIC (Bisa Diakses Siapa Saja)
// ====================================================

// Halaman Formulir Pendaftaran
Route::get('/seminar/daftar', [SeminarController::class, 'create'])->name('seminar.form');

// Proses Simpan Data Pendaftaran
Route::post('/seminar/daftar', [SeminarController::class, 'store'])->name('seminar.store');

// Halaman E-Tiket (Setelah Daftar)
Route::get('/seminar/tiket/{ticket_number}', [SeminarController::class, 'showTicket'])->name('seminar.ticket');


// ====================================================
// 2. BAGIAN ADMIN (Hanya Panitia)
// ====================================================
// Sebaiknya dibungkus middleware auth/admin jika aplikasi sudah live
Route::prefix('admin')->name('admin.')->group(function () {

    // Dashboard Data Peserta & Statistik
    Route::get('/seminar/peserta', [SeminarController::class, 'index'])->name('seminar.index');

    // Halaman Scanner Kamera (Untuk Absensi)
    Route::get('/seminar/scan', [SeminarController::class, 'scanPage'])->name('seminar.scan');

    // Proses Logic Absensi (Dipanggil AJAX dari Scanner)
    Route::post('/seminar/process-scan', [SeminarController::class, 'processScan'])->name('seminar.process_scan');

    // Export Data ke PDF
    Route::get('/seminar/export/pdf', [SeminarController::class, 'exportPdf'])->name('seminar.export.pdf');

    // Export Data ke Excel
    Route::get('/seminar/export/excel', [SeminarController::class, 'exportExcel'])->name('seminar.export.excel');

});


// Halaman Public untuk Input
Route::get('/input-keuangan', [CashflowController::class, 'create'])->name('cashflow.public');
Route::post('/input-keuangan', [CashflowController::class, 'store'])->name('cashflow.store');

// Halaman Admin (Pastikan sudah dibungkus middleware auth jika perlu)
Route::prefix('admin')->group(function () {
    Route::get('/cashflow', [CashflowController::class, 'index'])->name('cashflow.index');
    Route::put('/cashflow/{id}', [CashflowController::class, 'update'])->name('cashflow.update');
    Route::delete('/cashflow/{id}', [CashflowController::class, 'destroy'])->name('cashflow.destroy');

    // Export Routes
    Route::get('/cashflow/export/excel', [CashflowController::class, 'exportExcel'])->name('cashflow.export.excel');
    Route::get('/cashflow/export/pdf', [CashflowController::class, 'exportPdf'])->name('cashflow.export.pdf');

    // Laporan Keuangan
    // Route Manajemen Kontak (Hutang Piutang)
    Route::get('/contacts', [App\Http\Controllers\CashflowContactController::class, 'index'])->name('contacts.index');
    Route::post('/contacts', [App\Http\Controllers\CashflowContactController::class, 'store'])->name('contacts.store');
    Route::put('/contacts/{id}', [App\Http\Controllers\CashflowContactController::class, 'update'])->name('contacts.update');
    Route::delete('/contacts/{id}', [App\Http\Controllers\CashflowContactController::class, 'destroy'])->name('contacts.destroy');
});

Route::prefix('admin/invoice')->name('invoice.')->group(function () {

    // Menampilkan halaman riwayat invoice (Tabel) -> route('invoice.index')
    Route::get('/', [InvoiceController::class, 'index'])->name('index');

    // Menampilkan form buat invoice baru -> route('invoice.create')
    Route::get('/create', [InvoiceController::class, 'create'])->name('create');

    // Proses simpan data ke database -> route('invoice.store')
    Route::post('/store', [InvoiceController::class, 'store'])->name('store');

    // Menampilkan halaman edit invoice -> route('invoice.edit')
    Route::get('/{id}/edit', [InvoiceController::class, 'edit'])->name('edit');

    // Proses update data invoice -> route('invoice.update')
    Route::put('/{id}', [InvoiceController::class, 'update'])->name('update');

    // Proses hapus invoice -> route('invoice.destroy')
    Route::delete('/{id}', [InvoiceController::class, 'destroy'])->name('destroy');

    // Menampilkan dan mencetak PDF -> route('invoice.pdf')
    Route::get('/{id}/pdf', [InvoiceController::class, 'streamPDF'])->name('pdf');

});

Route::patch('/{id}/status', [InvoiceController::class, 'updateStatus'])->name('update_status');

// Rute Publik untuk Cek Invoice
Route::get('/cek-invoice', [App\Http\Controllers\InvoiceController::class, 'track'])->name('public.invoice.track');

// Rute Publik untuk Download PDF (Tanpa login Admin)
Route::get('/invoice/{invoice_no}/download', [App\Http\Controllers\InvoiceController::class, 'publicDownloadPDF'])->name('public.invoice.download');
