<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

// --- Controller Imports ---
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\MemberAuthController;
use App\Http\Controllers\DanaWidgetController;
use App\Http\Controllers\DanaDashboardController;
use App\Http\Controllers\DanaResponseCodeController;
use App\Http\Controllers\LogViewerController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ScannerController;
use App\Http\Controllers\MemberOrderController;
use App\Http\Controllers\MemberProfileController;

/*
|--------------------------------------------------------------------------
| 1. GLOBAL & PUBLIC ROUTES (Tidak Butuh Login)
|--------------------------------------------------------------------------
*/

// Halaman Depan
Route::get('/', function () {
    return view('welcome');
});

// Halaman Panduan
Route::get('/cara', function () {
    return view('cara');
});

// Logging Client Error (Untuk Debugging JS)
Route::post('/log-client-error', function (Request $request) {
    Log::error("CLIENT-SIDE ERROR: " . $request->input('message'), $request->input('context', []));
    return response()->json(['status' => 'logged']);
})->name('log.client.error');

// --- AFFILIATE / PARTNER (PUBLIC JOIN) ---
Route::prefix('join-partner')->name('affiliate.')->group(function () {
    Route::get('/', [AffiliateController::class, 'create'])->name('create');
    Route::post('/', [AffiliateController::class, 'store'])->name('store');

    // Cek & Update Akun Mandiri
    Route::post('/check-account', [AffiliateController::class, 'checkAccountPublic'])->name('check_account');
    Route::put('/update-public', [AffiliateController::class, 'updateAccountPublic'])->name('update_public');
    Route::post('/forgot-pin', [AffiliateController::class, 'forgotPin'])->name('forgot_pin');
});

// --- SCANNER MOBILE (PUBLIC / KHUSUS HP) ---
Route::get('/mobile-scanner', [ScannerController::class, 'index'])->name('scanner.index');
Route::post('/scan-process', [ScannerController::class, 'handleScan'])->name('scanner.process');

// --- INVOICE PUBLIC ---
Route::get('/invoice/{orderNumber}', [OrderController::class, 'invoice'])->name('orders.invoice');

// --- DANA PUBLIC RETURN & NOTIFY ---
Route::get('/dana/return', function () { return view('dana_success'); })->name('dana.return');
Route::post('/dana/notify', [OrderController::class, 'handleDanaWebhook'])->name('dana.notify'); // Prioritas Webhook


/*
|--------------------------------------------------------------------------
| 2. MEMBER AREA (AUTH: MEMBER)
|--------------------------------------------------------------------------
*/
Route::prefix('member')->name('member.')->group(function () {

    // Login & Logout
    Route::middleware('guest:member')->group(function () {
        Route::get('/login', [MemberAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [MemberAuthController::class, 'login'])->name('login.post');
    });

    // Fitur Setelah Login
    Route::middleware('auth:member')->group(function () {
        Route::get('/dashboard', [MemberAuthController::class, 'dashboard'])->name('dashboard');
        Route::post('/logout', [MemberAuthController::class, 'logout'])->name('logout');

        // Riwayat Pesanan
        Route::get('/orders', [MemberOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{id}', [MemberOrderController::class, 'show'])->name('orders.show');

        // Pengaturan Akun
        Route::prefix('settings')->name('settings.')->group(function(){
            Route::get('/', [MemberProfileController::class, 'index'])->name('index');
            Route::put('/update', [MemberProfileController::class, 'update'])->name('update');
            Route::put('/pin', [MemberProfileController::class, 'updatePin'])->name('update-pin');
        });

        // Fitur DANA Member (Binding, Topup, Transfer)
        Route::prefix('dana')->name('dana.')->group(function () {
            // Binding OAuth
            Route::post('/bind', [MemberAuthController::class, 'startBinding'])->name('startBinding');
            Route::get('/callback', [MemberAuthController::class, 'handleCallback'])->name('callback');

            // Transaksi & Cek Saldo
            Route::post('/balance', [MemberAuthController::class, 'checkBalance'])->name('checkBalance');
            Route::post('/inquiry', [MemberAuthController::class, 'accountInquiry'])->name('accountInquiry');
            Route::post('/topup', [MemberAuthController::class, 'customerTopup'])->name('customerTopup');

            // Bank Transfer
            Route::post('/bank-inquiry', [MemberAuthController::class, 'bankAccountInquiry'])->name('bankInquiry');
            Route::post('/transfer-bank', [MemberAuthController::class, 'transferToBank'])->name('transferBank');
            Route::post('/check-status', [MemberAuthController::class, 'checkTopupStatus'])->name('checkStatus');
        });
    });
});


/*
|--------------------------------------------------------------------------
| 3. ADMIN / DASHBOARD AREA (AUTH: WEB)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // --- DASHBOARD ---
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // --- PROFILE ADMIN ---
    Route::prefix('profile')->name('profile.')->group(function(){
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    // --- LOG VIEWER (ADMIN ONLY) ---
    Route::prefix('admin/logs')->name('admin.logs.')->group(function () {
        Route::get('/', [LogViewerController::class, 'index'])->name('index');
        Route::post('/clear', [LogViewerController::class, 'clear'])->name('clear');
    });

    // --- DANA DASHBOARD (ADMIN PANEL) ---
    Route::prefix('dana')->name('dana.')->group(function () {
        Route::get('/dashboard', [DanaDashboardController::class, 'index'])->name('dashboard');
        Route::post('/do-bind', [DanaDashboardController::class, 'startBinding'])->name('do_bind');
        Route::get('/callback', [DanaDashboardController::class, 'handleCallback'])->name('callback');
        Route::post('/check-balance', [DanaDashboardController::class, 'checkBalance'])->name('check_balance');
        Route::post('/check-merchant-balance', [DanaDashboardController::class, 'checkMerchantBalance'])->name('check_merchant_balance');

        // Disbursement Admin
        Route::post('/topup', [DanaDashboardController::class, 'topupSaldo'])->name('topup');
        Route::post('/execute-disbursement', [DanaDashboardController::class, 'customerTopup'])->name('execute_disbursement');
        Route::post('/account-inquiry', [DanaDashboardController::class, 'accountInquiry'])->name('account_inquiry');
    });
    // Resource Dana Response Code
    Route::resource('dana_response_codes', DanaResponseCodeController::class)->except(['create', 'edit', 'show']);


    // --- PRODUK & VARIAN ---
    Route::get('/products/download-pdf', [ProductController::class, 'downloadPdf'])->name('products.downloadPdf');
    // AJAX Varian
    Route::get('/products/{product}/variants', [ProductController::class, 'getVariants'])->name('products.variants.get');
    Route::post('/products/{product}/variants', [ProductController::class, 'updateVariants'])->name('products.variants.update');
    // Resource Produk
    Route::resource('products', ProductController::class);


    // --- ORDERS (PESANAN) ---
    Route::prefix('orders')->name('orders.')->group(function(){
        // 1. Helper & JSON API (Harus Paling Atas)
        Route::get('/scan-product', [OrderController::class, 'scanProduct'])->name('scan-product'); // <-- ROUTE SCAN
        Route::get('/search-location', [OrderController::class, 'searchLocation'])->name('search-location');
        Route::get('/tripay-channels', [OrderController::class, 'getPaymentChannels'])->name('tripay-channels');

        // 2. Export
        Route::get('/export-pdf', [OrderController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/export-excel', [OrderController::class, 'exportExcel'])->name('export.excel');

        // 3. Aksi Cek Ongkir & Kupon
        Route::post('/check-ongkir', [OrderController::class, 'checkShippingRates'])->name('check-ongkir');
        Route::post('/check-coupon', [OrderController::class, 'checkCoupon'])->name('check-coupon');

        // 4. Bulk Actions
        Route::delete('/bulk-delete', [OrderController::class, 'bulkDestroy'])->name('bulkDestroy');
    });
    // Resource Order (Menangani index, create, store, show, edit, update, destroy)
    Route::resource('orders', OrderController::class);


    // --- CUSTOMERS (PELANGGAN) ---
    Route::prefix('customers')->name('customers.')->group(function () {
        // AJAX Save & Search (Wajib di atas resource)
        Route::post('/store-ajax', [CustomerController::class, 'storeAjax'])->name('storeAjax');
        Route::get('/search-api', [CustomerController::class, 'searchApi'])->name('searchApi');
    });
    Route::resource('customers', CustomerController::class);


    // --- LAPORAN & KATEGORI & KUPON ---
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::resource('reports', ReportController::class)->except(['create', 'store']);
    Route::resource('categories', CategoryController::class);
    Route::resource('coupons', CouponController::class);


    // --- AFFILIATE ADMIN MANAGEMENT ---
    Route::prefix('affiliate')->name('affiliate.')->group(function(){
        Route::get('/', [AffiliateController::class, 'index'])->name('index');
        Route::post('/sync-balance', [AffiliateController::class, 'syncBalance'])->name('sync');
        Route::get('/print-qr/{id}', [AffiliateController::class, 'printQr'])->name('print_qr');

        // Edit & Update Manual Admin
        Route::get('/edit/{id}', [AffiliateController::class, 'edit'])->name('edit');
        Route::put('/update/{id}', [AffiliateController::class, 'update'])->name('update');
        Route::delete('/delete/{id}', [AffiliateController::class, 'destroy'])->name('destroy');
    });

});


// Auth Bawaan Laravel (Breeze/Jetstream)
require __DIR__.'/auth.php';
