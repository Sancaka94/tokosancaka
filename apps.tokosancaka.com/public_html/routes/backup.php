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

/*
|--------------------------------------------------------------------------
| 1. PUBLIC ROUTES (Akses Tanpa Login)
|--------------------------------------------------------------------------
*/

Route::get('/', function () { return view('welcome'); });
Route::get('/cara', function () { return view('cara'); });

// --- Tenant / Pendaftaran Toko ---
Route::get('/daftar-pos', [RegisterTenantController::class, 'showForm'])->name('daftar.pos');
Route::post('/daftar-pos', [RegisterTenantController::class, 'register'])->name('daftar.pos.store');
Route::get('/account-suspended', [TenantController::class, 'suspended'])->middleware(['web'])->name('tenant.suspended');
Route::post('/hubungi-admin-manual', [TenantController::class, 'hubungiAdmin'])->name('tenant.hubungi.admin');

// --- Affiliate Public ---
Route::get('/join-partner', [AffiliateController::class, 'create'])->name('affiliate.create');
Route::post('/join-partner', [AffiliateController::class, 'store'])->name('affiliate.store');
Route::post('/join-partner/check-account', [AffiliateController::class, 'checkAccountPublic'])->name('affiliate.check_account');
Route::put('/join-partner/update-public', [AffiliateController::class, 'updateAccountPublic'])->name('affiliate.update_public');
Route::post('/join-partner/forgot-pin', [AffiliateController::class, 'forgotPin'])->name('affiliate.forgot_pin');

// --- Tools & Logging (Client Side) ---
Route::post('/log-client-error', function (Request $request) {
    Log::error("CLIENT-SIDE GPS ERROR: " . $request->input('message'), $request->input('context', []));
    return response()->json(['status' => 'logged']);
})->name('log.client.error');

// --- DANA Webhook & Callback ---
Route::post('/dana/notify', [OrderController::class, 'handleDanaWebhook'])->name('dana.notify');
Route::get('/dana/return', function () { return view('dana_success'); })->name('dana.return');

// --- Mobile Scanner (Tanpa Login untuk HP Scanner) ---
Route::get('/mobile-scanner', [ScannerController::class, 'index'])->name('scanner.index');
Route::post('/scan-process', [ScannerController::class, 'handleScan'])->name('scanner.process');


/*
|--------------------------------------------------------------------------
| 2. MEMBER AREA (Guard: member)
|--------------------------------------------------------------------------
*/
Route::prefix('member')->name('member.')->group(function () {
    // Guest Member (Login)
    Route::middleware('guest:member')->group(function () {
        Route::get('/login', [MemberAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [MemberAuthController::class, 'login'])->name('login.post');
    });

    // Authenticated Member
    Route::middleware('auth:member')->group(function () {
        Route::get('/dashboard', [MemberAuthController::class, 'dashboard'])->name('dashboard');
        Route::post('/logout', [MemberAuthController::class, 'logout'])->name('logout');

        // Order History
        Route::get('/orders', [MemberOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{id}', [MemberOrderController::class, 'show'])->name('orders.show');

        // Settings
        Route::get('/settings', [MemberProfileController::class, 'index'])->name('settings.index');
        Route::put('/settings/update', [MemberProfileController::class, 'update'])->name('settings.update');
        Route::put('/settings/pin', [MemberProfileController::class, 'updatePin'])->name('settings.update-pin');

        // DANA Integration (Member Side)
        Route::prefix('dana')->name('dana.')->group(function () {
            Route::post('/bind', [MemberAuthController::class, 'startBinding'])->name('startBinding');
            Route::get('/callback', [MemberAuthController::class, 'handleCallback'])->name('callback');
            Route::post('/balance', [MemberAuthController::class, 'checkBalance'])->name('checkBalance');
            Route::post('/topup', [MemberAuthController::class, 'customerTopup'])->name('customerTopup');
            Route::post('/bank-inquiry', [MemberAuthController::class, 'bankAccountInquiry'])->name('bankInquiry');
            Route::post('/check-status', [MemberAuthController::class, 'checkTopupStatus'])->name('checkStatus');
            Route::post('/transfer-bank', [MemberAuthController::class, 'transferToBank'])->name('transferBank');
            Route::post('/topup', [DanaDashboardController::class, 'topupSaldo'])->name('topup');
        });
    });
});


/*
|--------------------------------------------------------------------------
| 3. ADMIN / DASHBOARD ROUTES (Middleware: Auth)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // --- DASHBOARD & PROFILE (Semua User Login) ---
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


    // =========================================================================
    //  ROLE: ADMIN & SUPER ADMIN
    // =========================================================================
    Route::middleware(['role:admin|finance|super_admin'])->group(function () {
        // System Logs
        Route::prefix('admin/logs')->name('admin.logs.')->group(function () {
            Route::get('/', [LogViewerController::class, 'index'])->name('index');
            Route::post('/clear', [LogViewerController::class, 'clear'])->name('clear');
        });

        // Menu Manajemen Pegawai
        Route::get('/employees/create', [App\Http\Controllers\EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [App\Http\Controllers\EmployeeController::class, 'store'])->name('employees.store');
        Route::resource('employees', App\Http\Controllers\EmployeeController::class);
        // Settings & Users Management
        Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');
        Route::get('/users-management', [DashboardController::class, 'users'])->name('users.index');

        // Affiliate Management
        Route::get('/affiliates', [AffiliateController::class, 'index'])->name('affiliate.index');
        Route::post('/affiliate/sync-balance', [AffiliateController::class, 'syncBalance'])->name('affiliate.sync');
        Route::get('/affiliate/print-qr/{id}', [AffiliateController::class, 'printQr'])->name('affiliate.print_qr');
        Route::get('/affiliate/edit/{id}', [AffiliateController::class, 'edit'])->name('affiliate.edit');
        Route::put('/affiliate/update/{id}', [AffiliateController::class, 'update'])->name('affiliate.update');

         // --- DANA DASHBOARD ---
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

        // DANA Response Codes
        Route::resource('dana_response_codes', DanaResponseCodeController::class)->except(['create', 'edit', 'show']);
    });


    // =========================================================================
    //  ROLE: FINANCE & ADMIN
    // =========================================================================
    Route::middleware(['role:admin|finance'])->prefix('finance')->name('finance.')->group(function () {
        Route::get('/', [FinanceController::class, 'index'])->name('index');
        Route::post('/store', [FinanceController::class, 'store'])->name('store');
        Route::get('/delete/{id}', [FinanceController::class, 'destroy'])->name('destroy');
        Route::get('/sync', [FinanceController::class, 'syncData'])->name('sync');
        Route::get('/reset-sync', [FinanceController::class, 'resetAndSync'])->name('reset_sync');

        // Reports
        Route::get('/laba-rugi', [FinanceController::class, 'labaRugi'])->name('laba_rugi');
        Route::get('/neraca', [FinanceController::class, 'neraca'])->name('neraca');
        Route::get('/tahunan', [FinanceController::class, 'labaRugiTahunan'])->name('tahunan');
        Route::get('/gaji-karyawan', [FinanceController::class, 'salary'])->name('salary');
    });


    // =========================================================================
    //  ROLE: OPERATIONAL (Admin, Staff, Operator)
    // =========================================================================
    Route::middleware(['role:admin|staff|operator|finance'])->group(function () {

        // --- PRODUCTS ---
        // Route spesifik produk (Harus diatas resource)
        Route::get('/products/download-pdf', [ProductController::class, 'downloadPdf'])->name('products.downloadPdf');
        Route::get('/products/{product}/variants', [ProductController::class, 'getVariants'])->name('products.variants.get');
        Route::post('/products/{product}/variants', [ProductController::class, 'updateVariants'])->name('products.variants.update');
        // Fix typo Http\Http yang ada di kode lama:
        Route::post('/products/{id}/variants-save', [ProductController::class, 'saveVariants']);

        // Resource
        Route::resource('products', ProductController::class);
        Route::resource('categories', CategoryController::class);
        Route::resource('coupons', CouponController::class);

        // --- CUSTOMERS ---
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

        // --- ORDERS ---
        // Specific Order Routes (Harus diatas resource)
        Route::get('/orders/scan-product', [OrderController::class, 'scanProduct'])->name('orders.scan-product');
        Route::get('/orders/search-location', [OrderController::class, 'searchLocation'])->name('orders.search-location');
        Route::get('/orders/tripay-channels', [OrderController::class, 'getPaymentChannels'])->name('orders.tripay-channels');
        Route::get('/orders/export-pdf', [OrderController::class, 'exportPdf'])->name('orders.export.pdf');
        Route::get('/orders/export-excel', [OrderController::class, 'exportExcel'])->name('orders.export.excel');
        Route::post('/orders/check-ongkir', [OrderController::class, 'checkShippingRates'])->name('orders.check-ongkir');
        Route::post('/orders/check-coupon', [OrderController::class, 'checkCoupon'])->name('orders.check-coupon');
        Route::delete('/orders/bulk-delete', [OrderController::class, 'bulkDestroy'])->name('orders.bulkDestroy');
        Route::get('/invoice/{orderNumber}', [OrderController::class, 'invoice'])->name('orders.invoice');

        // Resource Order
        Route::resource('orders', OrderController::class);

        // Alias Route untuk Menu Sidebar
        Route::get('/transaksi-baru', [OrderController::class, 'create'])->name('orders.create.alias');
        Route::get('/data-produk', [ProductController::class, 'index'])->name('products.index.alias');

        // --- REPORTS ---
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
        Route::resource('reports', ReportController::class)->except(['create', 'store']);

    }); // End Role Operational

}); // End Auth Middleware

// NOTE: Kami tidak menambahkan `require auth.php` disini karena
// file web.php utama Anda akan memuatnya di bagian bawah jika backup.php selesai dibaca.
// Ini mencegah konflik load ganda.
