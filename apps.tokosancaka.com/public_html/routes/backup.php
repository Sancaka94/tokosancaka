<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

// --- IMPORT CONTROLLERS ---
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ScannerController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\RegisterTenantController;
use App\Http\Controllers\MemberAuthController;
use App\Http\Controllers\MemberOrderController;
use App\Http\Controllers\MemberProfileController;
use App\Http\Controllers\DanaDashboardController;
use App\Http\Controllers\DanaResponseCodeController;
use App\Http\Controllers\LogViewerController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AuthController; // Tambahkan Controller Login Toko disini

/*
|==========================================================================
| KELOMPOK 1: LANDING PAGE & SISTEM PUSAT
| Domain: apps.tokosancaka.com
|==========================================================================
*/
Route::domain('apps.tokosancaka.com')->group(function () {

    // Halaman Depan (Landing Page)
    Route::get('/', function () { return view('welcome'); })->name('home');
    Route::get('/cara', function () { return view('cara'); });

    // Pendaftaran Toko Baru
    Route::get('/daftar-pos', [RegisterTenantController::class, 'showForm'])->name('daftar.pos');
    Route::post('/daftar-pos', [RegisterTenantController::class, 'register'])->name('daftar.pos.store');

    // Affiliate (Partner) - Masuk ranah pusat
    Route::get('/join-partner', [AffiliateController::class, 'create'])->name('affiliate.create');
    Route::post('/join-partner', [AffiliateController::class, 'store'])->name('affiliate.store');
    Route::post('/join-partner/check-account', [AffiliateController::class, 'checkAccountPublic'])->name('affiliate.check_account');
    Route::put('/join-partner/update-public', [AffiliateController::class, 'updateAccountPublic'])->name('affiliate.update_public');
    Route::post('/join-partner/forgot-pin', [AffiliateController::class, 'forgotPin'])->name('affiliate.forgot_pin');

    // Webhook Global (DANA/Payment Gateway) - Wajib di domain utama
    Route::post('/dana/notify', [OrderController::class, 'handleDanaWebhook'])->name('dana.notify');

    // Log Error Client
    Route::post('/log-client-error', function (Request $request) {
        Log::error("CLIENT-SIDE GPS ERROR: " . $request->input('message'), $request->input('context', []));
        return response()->json(['status' => 'logged']);
    })->name('log.client.error');
});


/*
|==========================================================================
| KELOMPOK 2: APLIKASI TOKO (TENANT)
| Domain: *.tokosancaka.com (Dinamis)
|==========================================================================
*/
Route::domain('{subdomain}.tokosancaka.com')
    ->middleware(['tenant']) // <--- Middleware TenantMiddleware WAJIB DISINI
    ->group(function () {

    require __DIR__.'/auth.php'; // <--- INI WAJIB DIMATIKAN!

    // --- HALAMAN AUTH TENANT (LOGIN KHUSUS TOKO) ---
    // Override route '/' agar subdomain langsung masuk ke halaman login/dashboard
    Route::get('/', function() {
        return redirect()->route('login');
    });

    // Halaman Error Toko
    Route::get('/account-suspended', [TenantController::class, 'suspended'])->name('tenant.suspended');
    Route::post('/hubungi-admin-manual', [TenantController::class, 'hubungiAdmin'])->name('tenant.hubungi.admin');

    // Halaman Callback DANA (Return URL)
    Route::get('/dana/return', function () { return view('dana_success'); })->name('dana.return');

    // --- MOBILE SCANNER (Tanpa Login) ---
    Route::get('/mobile-scanner', [ScannerController::class, 'index'])->name('scanner.index');
    Route::post('/scan-process', [ScannerController::class, 'handleScan'])->name('scanner.process');

    // =================================================================
    //  MEMBER AREA (Pelanggan Toko)
    // =================================================================
    Route::prefix('member')->name('member.')->group(function () {
        Route::middleware('guest:member')->group(function () {
            Route::get('/login', [MemberAuthController::class, 'showLoginForm'])->name('login');
            Route::post('/login', [MemberAuthController::class, 'login'])->name('login.post');
        });

        Route::middleware('auth:member')->group(function () {
            Route::get('/dashboard', [MemberAuthController::class, 'dashboard'])->name('dashboard');
            Route::post('/logout', [MemberAuthController::class, 'logout'])->name('logout');

            // Order History & Settings
            Route::get('/orders', [MemberOrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/{id}', [MemberOrderController::class, 'show'])->name('orders.show');
            Route::get('/settings', [MemberProfileController::class, 'index'])->name('settings.index');
            Route::put('/settings/update', [MemberProfileController::class, 'update'])->name('settings.update');
            Route::put('/settings/pin', [MemberProfileController::class, 'updatePin'])->name('settings.update-pin');

            // DANA Integration Member
            Route::prefix('dana')->name('dana.')->group(function () {
                Route::post('/bind', [MemberAuthController::class, 'startBinding'])->name('startBinding');
                Route::get('/callback', [MemberAuthController::class, 'handleCallback'])->name('callback');
                Route::post('/balance', [MemberAuthController::class, 'checkBalance'])->name('checkBalance');
                Route::post('/bank-inquiry', [MemberAuthController::class, 'bankAccountInquiry'])->name('bankInquiry');
                Route::post('/check-status', [MemberAuthController::class, 'checkTopupStatus'])->name('checkStatus');
                Route::post('/transfer-bank', [MemberAuthController::class, 'transferToBank'])->name('transferBank');
                Route::post('/customer-topup', [MemberAuthController::class, 'customerTopup'])->name('customerTopup');
                Route::post('/topup', [DanaDashboardController::class, 'topupSaldo'])->name('topup');
            });
        });
    });

    // =================================================================
    //  DASHBOARD PEMILIK TOKO (ADMIN/STAFF)
    // =================================================================
    Route::middleware(['auth'])->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Profile
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
        Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        // --- ROLE: ADMIN & SUPER ADMIN ---
        Route::middleware(['role:admin|finance|super_admin'])->group(function () {
            // Logs
            Route::prefix('admin/logs')->name('admin.logs.')->group(function () {
                Route::get('/', [LogViewerController::class, 'index'])->name('index');
                Route::post('/clear', [LogViewerController::class, 'clear'])->name('clear');
            });

            // Employees (Resource sudah mencakup create/store/dll)
            Route::resource('employees', EmployeeController::class);

            // Settings & Users
            Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');
            Route::get('/users-management', [DashboardController::class, 'users'])->name('users.index');

            // Affiliate Management
            Route::get('/affiliates', [AffiliateController::class, 'index'])->name('affiliate.index');
            Route::post('/affiliate/sync-balance', [AffiliateController::class, 'syncBalance'])->name('affiliate.sync');
            Route::get('/affiliate/print-qr/{id}', [AffiliateController::class, 'printQr'])->name('affiliate.print_qr');
            Route::get('/affiliate/edit/{id}', [AffiliateController::class, 'edit'])->name('affiliate.edit');
            Route::put('/affiliate/update/{id}', [AffiliateController::class, 'update'])->name('affiliate.update');

            // Dana Dashboard Admin
            Route::prefix('dana')->name('dana.')->group(function () {
                Route::get('/dashboard', [DanaDashboardController::class, 'index'])->name('dashboard');
                Route::post('/do-bind', [DanaDashboardController::class, 'startBinding'])->name('do_bind');
                Route::get('/callback', [DanaDashboardController::class, 'handleCallback'])->name('callback');
                Route::post('/check-balance', [DanaDashboardController::class, 'checkBalance'])->name('check_balance');
                Route::post('/check-merchant-balance', [DanaDashboardController::class, 'checkMerchantBalance'])->name('check_merchant_balance');
                Route::post('/topup', [DanaDashboardController::class, 'topupSaldo'])->name('topup');
                Route::post('/execute-disbursement', [DanaDashboardController::class, 'customerTopup'])->name('execute_disbursement');
                Route::post('/account-inquiry', [DanaDashboardController::class, 'accountInquiry'])->name('account_inquiry');
            });
            Route::resource('dana_response_codes', DanaResponseCodeController::class)->except(['create', 'edit', 'show']);
        });

        // --- ROLE: FINANCE & ADMIN ---
        Route::middleware(['role:admin|finance'])->prefix('finance')->name('finance.')->group(function () {
            Route::get('/', [FinanceController::class, 'index'])->name('index');
            Route::post('/store', [FinanceController::class, 'store'])->name('store');
            Route::get('/delete/{id}', [FinanceController::class, 'destroy'])->name('destroy');
            Route::get('/sync', [FinanceController::class, 'syncData'])->name('sync');
            Route::get('/reset-sync', [FinanceController::class, 'resetAndSync'])->name('reset_sync');
            Route::get('/laba-rugi', [FinanceController::class, 'labaRugi'])->name('laba_rugi');
            Route::get('/neraca', [FinanceController::class, 'neraca'])->name('neraca');
            Route::get('/tahunan', [FinanceController::class, 'labaRugiTahunan'])->name('tahunan');
            Route::get('/gaji-karyawan', [FinanceController::class, 'salary'])->name('salary');
        });

        // --- ROLE: OPERATIONAL ---
        Route::middleware(['role:admin|staff|operator|finance'])->group(function () {
            // Products
            Route::get('/products/download-pdf', [ProductController::class, 'downloadPdf'])->name('products.downloadPdf');
            Route::get('/products/{product}/variants', [ProductController::class, 'getVariants'])->name('products.variants.get');
            Route::post('/products/{product}/variants', [ProductController::class, 'updateVariants'])->name('products.variants.update');
            Route::post('/products/{id}/variants-save', [ProductController::class, 'saveVariants']);
            Route::resource('products', ProductController::class);
            Route::resource('categories', CategoryController::class);
            Route::resource('coupons', CouponController::class);

            // Customers
            Route::prefix('customers')->name('customers.')->group(function () {
                Route::post('/store-ajax', [CustomerController::class, 'storeAjax'])->name('storeAjax');
                Route::get('/search-api', [CustomerController::class, 'searchApi'])->name('searchApi');
                Route::get('/', [CustomerController::class, 'index'])->name('index');
                Route::get('/create', [CustomerController::class, 'create'])->name('create');
                Route::post('/', [CustomerController::class, 'store'])->name('store');
                Route::get('/{id}', [CustomerController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [CustomerController::class, 'edit'])->name('edit');
                Route::put('/{id}', [CustomerController::class, 'update'])->name('update');
                Route::delete('/{id}', [CustomerController::class, 'destroy'])->name('destroy');
            });

            // Orders
            Route::get('/orders/scan-product', [OrderController::class, 'scanProduct'])->name('orders.scan-product');
            Route::get('/orders/search-location', [OrderController::class, 'searchLocation'])->name('orders.search-location');
            Route::get('/orders/tripay-channels', [OrderController::class, 'getPaymentChannels'])->name('orders.tripay-channels');
            Route::get('/orders/export-pdf', [OrderController::class, 'exportPdf'])->name('orders.export.pdf');
            Route::get('/orders/export-excel', [OrderController::class, 'exportExcel'])->name('orders.export.excel');
            Route::post('/orders/check-ongkir', [OrderController::class, 'checkShippingRates'])->name('orders.check-ongkir');
            Route::post('/orders/check-coupon', [OrderController::class, 'checkCoupon'])->name('orders.check-coupon');
            Route::delete('/orders/bulk-delete', [OrderController::class, 'bulkDestroy'])->name('orders.bulkDestroy');
            Route::get('/invoice/{orderNumber}', [OrderController::class, 'invoice'])->name('orders.invoice');
            Route::resource('orders', OrderController::class);

            // Reports
            Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
            Route::resource('reports', ReportController::class)->except(['create', 'store']);

            // Aliases
            Route::get('/transaksi-baru', [OrderController::class, 'create'])->name('orders.create.alias');
            Route::get('/data-produk', [ProductController::class, 'index'])->name('products.index.alias');
        });
    });
});
