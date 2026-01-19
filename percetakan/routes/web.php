<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OrderController; // <-- BARIS INI WAJIB ADA
use App\Http\Controllers\ProductController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\MemberAuthController;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\DanaWidgetController;
use App\Http\Controllers\DanaDashboardController;
use App\Http\Controllers\DanaResponseCodeController;
use App\Http\Controllers\LogViewerController;
use App\Http\Controllers\CategoryController; // <--- Jangan lupa import ini di paling atas
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ScannerController; // <--- Import Controller Wajib Ada



Route::get('/orders/scan-product', [OrderController::class, 'scanProduct'])->name('orders.scan-product');


// Sesuaikan middleware dengan kebutuhan admin panel Anda (misal: 'auth', 'admin')
Route::middleware(['auth'])->prefix('admin/logs')->name('admin.logs.')->group(function () {

    // Halaman Viewer (GET)
    Route::get('/', [LogViewerController::class, 'index'])->name('index');

    // Proses Hapus (POST) - Sesuai fetch di Javascript Anda
    Route::post('/clear', [LogViewerController::class, 'clear'])->name('clear');

});

Route::resource('dana_response_codes', DanaResponseCodeController::class);

/*
|--------------------------------------------------------------------------
| DANA DASHBOARD INTEGRATION (PRIORITY)
|--------------------------------------------------------------------------
*/

// Grouping untuk fitur DANA agar lebih rapi
Route::prefix('dana')->name('dana.')->group(function () {

    // 1. Route Dashboard Utama
    Route::get('/dashboard', [DanaDashboardController::class, 'index'])->name('dashboard');

    // 2. Route Account Binding (Logika Awal)
    Route::post('/do-bind', [DanaDashboardController::class, 'startBinding'])->name('do_bind');
    // 3. Callback (Wajib DanaDashboardController agar Redirect sukses)
    Route::get('/callback', [DanaDashboardController::class, 'handleCallback'])->name('callback');
    // 3. Route Monitoring Saldo (SNAP)
    Route::post('/check-balance', [DanaDashboardController::class, 'checkBalance'])->name('check_balance');

    // 4. Route Disbursement: Check Merchant Balance (Open API v2.0)
    // Digunakan untuk verifikasi kecukupan dana sebelum top up
    Route::post('/check-merchant-balance', [DanaDashboardController::class, 'checkMerchantBalance'])->name('check_merchant_balance');

    // 5. Route Disbursement: Customer Top Up
    // Digunakan untuk eksekusi kirim uang ke user
    Route::post('/topup', [DanaDashboardController::class, 'topupSaldo'])->name('topup');

    Route::post('/execute-disbursement', [DanaDashboardController::class, 'customerTopup'])->name('execute_disbursement');

    Route::post('/account-inquiry', [DanaDashboardController::class, 'accountInquiry'])->name('account_inquiry');
});


// --- ROUTE KHUSUS VARIAN (AJAX / MODAL) ---
    // 1. Untuk mengambil data varian (saat tombol modal diklik)
    Route::get('/products/{product}/variants', [ProductController::class, 'getVariants'])
        ->name('products.variants.get');

    // 2. Untuk menyimpan perubahan varian (saat tombol simpan di modal diklik)
    Route::post('/products/{product}/variants', [ProductController::class, 'updateVariants'])
        ->name('products.variants.update');


/*
|--------------------------------------------------------------------------
| DANA WIDGET / API / TESTING (SECONDARY)
|--------------------------------------------------------------------------
*/

//Route::post('/dana/notify', [OrderController::class, 'handleDanaCallback'])->name('dana.notify');

// Pastikan baris ini yang aktif di routes
Route::post('/dana/notify', [OrderController::class, 'handleDanaWebhook'])->name('dana.notify');
// Webhook Notification
//Route::post('/dana/notify', [DanaWidgetController::class, 'handleNotify'])->name('dana.notify');

// Halaman Return Sukses
Route::get('/dana/return', function () {return view('dana_success');})->name('dana.return');

/*

// Payment & Transaction
Route::any('/dana/pay', [DanaWidgetController::class, 'createPayment'])->name('dana.pay');
Route::get('/dana/status/{orderId}', [DanaWidgetController::class, 'checkStatus'])->name('dana.status');

// Testing Routes (Manual Trigger lewat URL)
Route::get('/dana/test-disburse', [DanaWidgetController::class, 'disburseTopUp']);
Route::get('/dana/test-inquiry', [DanaWidgetController::class, 'disburseAccountInquiry']);
Route::get('/dana/test-topup', [DanaWidgetController::class, 'disburseTopUp']);
Route::get('/dana/test-status', [DanaWidgetController::class, 'disburseCheckStatus']);

// Cek Saldo Manual (API Test)
Route::get('/dana/test-balance', [DanaWidgetController::class, 'balanceInquiry']);

Route::get('/dana/debug-force', [App\Http\Controllers\DanaDashboardController::class, 'debugForce']);

Route::get('/dana/test-key', [DanaDashboardController::class, 'testKeyData']);

Route::get('/dana/test-inquiry', [App\Http\Controllers\DanaDashboardController::class, 'accountInquiry']);

*/

Route::middleware(['auth'])->group(function () {
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

// Halaman Admin (Posting Produk)
Route::post('/products/store', [ProductController::class, 'store'])->name('products.store');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/invoice/{orderNumber}', [OrderController::class, 'invoice'])->name('orders.invoice');

Route::middleware('auth')->group(function () {

    // Tambahkan baris ini
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');

    // Route profile bawaan (edit, update, destroy)
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // Laporan Penjualan
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // Route untuk Admin melihat daftar Afiliasi
    Route::get('/affiliates', [AffiliateController::class, 'index'])->name('affiliate.index');
    Route::post('/affiliate/sync-balance', [AffiliateController::class, 'syncBalance'])->name('affiliate.sync');

    // Rute CURL ORDER

    Route::delete('/orders/bulk-delete', [OrderController::class, 'bulkDestroy'])->name('orders.bulkDestroy');

    Route::delete('/orders/{id}', [OrderController::class, 'destroy'])->name('orders.destroy');

    Route::post('/check-merchant-balance', [DanaDashboardController::class, 'checkMerchantBalance'])->name('dana.checkMerchantBalance');


    // Daftar Produk & Form Tambah
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    // Proses Simpan Produk Baru
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    // Hapus Produk
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    Route::get('/products/download-pdf', [ProductController::class, 'downloadPdf'])->name('products.downloadPdf');

    Route::resource('products', ProductController::class);

    // Resourceful Routes untuk Order
Route::resource('reports', ReportController::class)->except(['create', 'store']);


// Kembalikan jadi resource biasa (tanpa except)
Route::resource('coupons', CouponController::class);

Route::resource('dana_response_codes', DanaResponseCodeController::class)->except(['create', 'edit', 'show']);

});

Route::prefix('member')->name('member.')->group(function () {
    Route::middleware('guest:member')->group(function () {
        Route::get('/login', [MemberAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [MemberAuthController::class, 'login'])->name('login.post');
    });

    Route::middleware('auth:member')->group(function () {
        Route::get('/dashboard', [MemberAuthController::class, 'dashboard'])->name('dashboard');
        Route::post('/logout', [MemberAuthController::class, 'logout'])->name('logout');


        // --- TAMBAHAN BARU: RIWAYAT PESANAN ---
        // Kita pakai controller baru biar rapi
        Route::get('/orders', [\App\Http\Controllers\MemberOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{id}', [\App\Http\Controllers\MemberOrderController::class, 'show'])->name('orders.show'); // Detail

        // --- TAMBAHAN BARU: PENGATURAN AKUN ---
        Route::get('/settings', [\App\Http\Controllers\MemberProfileController::class, 'index'])->name('settings.index');
        Route::put('/settings/update', [\App\Http\Controllers\MemberProfileController::class, 'update'])->name('settings.update');
        Route::put('/settings/pin', [\App\Http\Controllers\MemberProfileController::class, 'updatePin'])->name('settings.update-pin');

        //Route::post('/dana/check-status', [MemberAuthController::class, 'checkTopupStatus'])->name('dana.checkStatus');

        //Route::post('/dana/bank-inquiry', [MemberAuthController::class, 'bankAccountInquiry'])->name('dana.bankInquiry');

        // Grup Khusus Fitur DANA Disbursement
    Route::prefix('dana')->name('dana.')->group(function () {

        // --- FITUR DISBURSEMENT TO BANK ---
        // Rute untuk validasi rekening bank (Inquiry)
        Route::post('/bank-inquiry', [MemberAuthController::class, 'bankAccountInquiry'])->name('bankInquiry');

        // --- FITUR PENGECEKAN STATUS ---
        // Rute untuk sinkronisasi status transaksi secara manual
        Route::post('/check-status', [MemberAuthController::class, 'checkTopupStatus'])->name('checkStatus');

        Route::post('/transfer-bank', [MemberAuthController::class, 'transferToBank'])->name('transferBank');

    });

        });

});

// Diletakkan di dalam middleware auth:member agar aman
Route::middleware('auth:member')->prefix('dana')->name('dana.')->group(function () {
    // Binding OAuth
    Route::post('/bind', [MemberAuthController::class, 'startBinding'])->name('startBinding');
    Route::get('/callback', [MemberAuthController::class, 'handleCallback'])->name('callback');

    // Inquiry & Topup
    Route::post('/balance', [MemberAuthController::class, 'checkBalance'])->name('checkBalance');
    Route::post('/inquiry', [MemberAuthController::class, 'accountInquiry'])->name('accountInquiry');
    Route::post('/topup', [MemberAuthController::class, 'customerTopup'])->name('customerTopup');
});

// Route untuk halaman Cara / Panduan
Route::get('/cara', function () {
    return view('cara');
});

/*
|--------------------------------------------------------------------------
| ROUTE ORDERS (Diurutkan berdasarkan Spesifik -> Umum)
|--------------------------------------------------------------------------
*/

// --- 1. GET ROUTES (Halaman & Data Spesifik) ---
// [PENTING] Route spesifik WAJIB diletakkan di atas route {id}



// Halaman POS (Create)
Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');

// Helpers (Pencarian Lokasi & Payment Channel)
Route::get('/orders/search-location', [OrderController::class, 'searchLocation'])->name('orders.search-location');
Route::get('/orders/tripay-channels', [OrderController::class, 'getPaymentChannels'])->name('orders.tripay-channels');

// Export Data (PDF & Excel)
Route::get('/orders/export-pdf', [OrderController::class, 'exportPdf'])->name('orders.export.pdf');
Route::get('/orders/export-excel', [OrderController::class, 'exportExcel'])->name('orders.export.excel');

// Halaman Index (Daftar Pesanan)
Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');


// --- 2. POST ROUTES (Aksi Simpan & Validasi) ---
Route::post('/orders/check-ongkir', [OrderController::class, 'checkShippingRates'])->name('orders.check-ongkir');
Route::post('/orders/store', [OrderController::class, 'store'])->name('orders.store');
Route::post('/orders/check-coupon', [OrderController::class, 'checkCoupon'])->name('orders.check-coupon');


// --- 3. DELETE ROUTES (Aksi Hapus) ---
// Bulk delete harus di atas delete {id} biasa (walaupun methodnya sama, lebih baik spesifik dulu)
Route::delete('/orders/bulk-delete', [OrderController::class, 'bulkDestroy'])->name('orders.bulkDestroy');


// --- 4. WILDCARD & RESOURCE (Route Umum / Dinamis) ---
// [PENTING] Bagian ini harus diletakkan PALING BAWAH agar tidak "memakan" route di atasnya

// Route Detail (Show) - Menggunakan parameter {id}
Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');

// Fitur Scan Barcode (Prioritas Utama)
// Resource Controller (Menangani seluruh sisa CRUD standar)
Route::resource('orders', OrderController::class);

Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');


// Route untuk Halaman Depan (Publik)
Route::get('/join-partner', [AffiliateController::class, 'create'])->name('affiliate.create');
Route::post('/join-partner', [AffiliateController::class, 'store'])->name('affiliate.store');


Route::get('/affiliate/print-qr/{id}', [AffiliateController::class, 'printQr'])->name('affiliate.print_qr');



// --- ROUTE BARU YANG PERLU DITAMBAHKAN ---
    Route::get('/edit/{id}', [AffiliateController::class, 'edit'])->name('edit');
    Route::put('/update/{id}', [AffiliateController::class, 'update'])->name('update');
    Route::delete('/delete/{id}', [AffiliateController::class, 'destroy'])->name('destroy');

    // TAMBAHKAN 2 BARIS INI:
Route::get('/affiliate/edit/{id}', [AffiliateController::class, 'edit'])->name('affiliate.edit');
Route::put('/affiliate/update/{id}', [AffiliateController::class, 'update'])->name('affiliate.update');

// API Public untuk Edit Data (Cek PIN & Update)
Route::post('/join-partner/check-account', [AffiliateController::class, 'checkAccountPublic'])->name('affiliate.check_account');
Route::put('/join-partner/update-public', [AffiliateController::class, 'updateAccountPublic'])->name('affiliate.update_public');
// Tambahkan Route Khusus Reset PIN ini:
Route::post('/join-partner/forgot-pin', [AffiliateController::class, 'forgotPin'])->name('affiliate.forgot_pin');

// Pastikan baris ini ada di paling bawah untuk memuat rute Login/Register
require __DIR__.'/auth.php';

// Route untuk Kategori
Route::resource('categories', CategoryController::class);

/*
|--------------------------------------------------------------------------
| Customer Routes
|--------------------------------------------------------------------------
|
| URL Prefix: /customers
| Name Prefix: customers.
| Middleware: auth (Wajib Login)
|
*/

Route::middleware(['auth'])->prefix('customers')->name('customers.')->group(function () {

    // -----------------------------------------------------------
    // 1. ROUTE KHUSUS (AJAX / API INTERNAL)
    // -----------------------------------------------------------
    // Wajib ditaruh DI ATAS route resource/parameter {id}
    // agar 'store-ajax' tidak dianggap sebagai ID customer.

    // Simpan Data Cepat via POS (AJAX)
    Route::post('/store-ajax', [CustomerController::class, 'storeAjax'])->name('storeAjax');

    // Pencarian Autocomplete via JSON
    Route::get('/search-api', [CustomerController::class, 'searchApi'])->name('searchApi');


    // -----------------------------------------------------------
    // 2. ROUTE CRUD STANDAR (WEB UI)
    // -----------------------------------------------------------

    // Menampilkan daftar customer (Index)
    Route::get('/', [CustomerController::class, 'index'])->name('index');

    // Menampilkan Form Tambah (Create)
    Route::get('/create', [CustomerController::class, 'create'])->name('create');

    // Proses Simpan Data dari Form (Store)
    Route::post('/', [CustomerController::class, 'store'])->name('store');

    // Menampilkan Detail Customer (Show)
    Route::get('/{id}', [CustomerController::class, 'show'])->name('show');

    // Menampilkan Form Edit (Edit)
    Route::get('/{id}/edit', [CustomerController::class, 'edit'])->name('edit');

    // Proses Update Data (Update)
    Route::put('/{id}', [CustomerController::class, 'update'])->name('update');

    // Proses Hapus Data (Destroy)
    Route::delete('/{id}', [CustomerController::class, 'destroy'])->name('destroy');

});


// === ROUTE KHUSUS REMOTE SCANNER ===
// Halaman Scanner HP
Route::get('/mobile-scanner', [ScannerController::class, 'index'])->name('scanner.index');

// Proses Terima Data (INI YANG PENTING)
Route::post('/scan-process', [ScannerController::class, 'handleScan'])->name('scanner.process');


// Fitur Scan Barcode (Prioritas Utama)
// Route::get('/orders/scan-product', [OrderController::class, 'scanProduct'])->name('orders.scan-product');

// === [TARUH INI DI PALING ATAS FILE, BARIS PERTAMA SETELAH USE] ===
Route::get('/orders/scan-product', function () {
    Log::info("CEK JALUR: Route Test Berhasil ditembus!");
    return response()->json([
        'status' => 'success',
        'message' => 'JALUR SERVER AMAN BOS!',
        'unit' => 'kg' // Dummy unit
    ]);
});
// ==================================================================


