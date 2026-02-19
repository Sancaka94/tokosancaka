<?php

/*
|--------------------------------------------------------------------------
| 1. LOAD ROUTE BACKUP (PRIORITAS UTAMA)
|--------------------------------------------------------------------------
| Laravel akan cek file ini dulu. Jika URL ada di sana, kode di bawah
| (file ini) akan diabaikan.
*/
require __DIR__ . '/backup.php';


/*
|--------------------------------------------------------------------------
| 2. ROUTE UTAMA (LEGACY / LAMA)
|--------------------------------------------------------------------------
| Jika URL tidak ketemu di backup.php, Laravel akan mencari di sini.
*/
// use App\Livewire\PosSystem; // <--- Jangan lupa import di paling atas


use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\Services\FonnteService;
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
use App\Http\Controllers\RegisterTenantController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\TenantPaymentController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\TenantDashboardController;
use App\Http\Controllers\DanaWebhookController;
use App\Http\Controllers\DanaGatewayController;
use App\Http\Controllers\HppController;


use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;
// Pastikan kamu meng-import class DanaService kamu, sesuaikan namespace-nya jika berbeda
use App\Services\DanaService;

    // Route Invoice (Publik)
    // Route::get('/invoice/{orderNumber}', [OrderController::class, 'invoice'])->name('invoice.show');

Route::get('/invoice/{orderNumber}', [OrderController::class, 'invoice'])->name('orders.invoice');


Route::get('/orders/scan-product', [OrderController::class, 'scanProduct'])->name('orders.scan-product');

// Fonte Kirim Admin Ketika Suspend
// Route untuk mengirim pesan bantuan ke Admin
// File: routes/web.php
Route::post('/hubungi-admin-manual', [App\Http\Controllers\TenantController::class, 'hubungiAdmin'])
    ->name('tenant.hubungi.admin'); // Nama ini harus sama dengan yang di Middleware

Route::post('/log-client-error', function (Request $request) {
    // Ambil data dari JS
    $message = $request->input('message');
    $context = $request->input('context', []);

    // Simpan ke laravel.log
    Log::error("CLIENT-SIDE GPS ERROR: " . $message, $context);

    return response()->json(['status' => 'logged']);
})->name('log.client.error');

// Sesuaikan middleware dengan kebutuhan admin panel Anda (misal: 'auth', 'admin')
Route::middleware(['auth'])->prefix('admin/logs')->name('admin.logs.')->group(function () {

    // Halaman Viewer (GET)
    Route::get('/', [LogViewerController::class, 'index'])->name('index');

    // Proses Hapus (POST) - Sesuai fetch di Javascript Anda
    Route::post('/clear', [LogViewerController::class, 'clear'])->name('clear');

});


/*
|--------------------------------------------------------------------------
| DANA DASHBOARD INTEGRATION (PRIORITY)
|--------------------------------------------------------------------------
*/


Route::resource('dana_response_codes', DanaResponseCodeController::class);


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
// Halaman Notify (Webhook Endpoint)
Route::post('/dana/notify', [App\Http\Controllers\DanaWebhookController::class, 'handleNotify'])->name('dana.notify');

// Halaman Return Sukses
Route::get('/dana/return', function () {return view('dana_success');})->name('dana.return');

Route::get('/dana/check-status/{reference_no}', [MemberAuthController::class, 'checkAcquiringStatus']);

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
    // Route::get('/products', [ProductController::class, 'index'])->name('products.index');
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

// Group Middleware 'guest' artinya: "Hanya untuk tamu yang BELUM login"
Route::middleware('guest')->group(function () {

    // Halaman Register
    Route::get('register', [RegisteredUserController::class, 'create'])
                ->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    // Halaman Login
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);
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
        Route::post('/bank-inquiry', [DanaGatewayController::class, 'bankAccountInquiry'])->name('bankInquiry');

        // --- FITUR PENGECEKAN STATUS ---
        // Rute untuk sinkronisasi status transaksi secara manual
        Route::post('/check-status', [DanaGatewayController::class, 'checkTopupStatus'])->name('checkStatus');

        Route::post('/transfer-bank', [DanaGatewayController::class, 'transferToBank'])->name('transferBank');

        // ✅ KODE BARU (ARAHKAN KE CONTROLLER PUSAT)
        Route::get('/connect', [DanaGatewayController::class, 'startMemberBinding'])->name('start');

    });

        });

});

// Diletakkan di dalam middleware auth:member agar aman
Route::middleware('auth:member')->prefix('dana')->name('dana.')->group(function () {
    // Binding OAuth
    Route::post('/bind', [DanaGatewayController::class, 'startBinding'])->name('startBinding');
    Route::get('/callback', [DanaGatewayController::class, 'handleCallback'])->name('callback');

    // Inquiry & Topup
    Route::post('/balance', [DanaGatewayController::class, 'checkBalance'])->name('checkBalance');
    Route::post('/inquiry', [DanaGatewayController::class, 'accountInquiry'])->name('accountInquiry');
    Route::post('/topup', [DanaGatewayController::class, 'customerTopup'])->name('customerTopup');
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
// Route::get('/orders/create', PosSystem::class)->name('orders.create');

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
// Route::resource('categories', CategoryController::class);

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


// --------------------------------------- Multi Tenant------------------------------------------- //
// --------------------------------------- Multi Tenant------------------------------------------- //
// --------------------------------------- Multi Tenant------------------------------------------- //

// URL Hasilnya: tokosancaka.com/percetakan/daftar-percetakan
Route::get('/daftar-pos', [RegisterTenantController::class, 'showForm'])->name('daftar.pos');
Route::post('/daftar-pos', [RegisterTenantController::class, 'register'])->name('daftar.pos.store');

Route::get('/admin/list-customer', [RegisterTenantController::class, 'listTenants'])
    ->middleware(['auth']) // <--- Tambahkan ini agar wajib login
    ->name('admin.list.customer');

// Route untuk mengambil data varian (GET)
Route::get('/products/{id}/variants', [App\Http\Controllers\ProductController::class, 'getVariants']);

// Route untuk menyimpan data varian (POST)
Route::post('/products/{id}/variants', [App\Http\Controllers\ProductController::class, 'saveVariants']);


// ----------------------------------- LAPORAN KEUANGAN --------------------------------------//

Route::prefix('finance')->name('finance.')->middleware(['auth'])->group(function () {

    // 1. Halaman Utama (Jurnal, Filter, Export Jurnal)
    Route::get('/', [FinanceController::class, 'index'])->name('index');

    // 2. CRUD Jurnal Manual
    Route::post('/store', [FinanceController::class, 'store'])->name('store');
    Route::get('/delete/{id}', [FinanceController::class, 'destroy'])->name('destroy');

    // 3. Tombol Sync (Tarik Data Penjualan)
    Route::get('/sync', [FinanceController::class, 'syncData'])->name('sync');

    // Reset Data
    Route::get('/reset-sync', [App\Http\Controllers\FinanceController::class, 'resetAndSync'])->name('reset_sync');

    // 4. Laporan Laba Rugi (+ Export PDF/Excel otomatis didalamnya)
    Route::get('/laba-rugi', [FinanceController::class, 'labaRugi'])->name('laba_rugi');

    // 5. Laporan Neraca (+ Export PDF/Excel otomatis didalamnya)
    Route::get('/neraca', [FinanceController::class, 'neraca'])->name('neraca');

    // 6. Laporan Tahunan (+ Export PDF/Excel otomatis didalamnya)
    Route::get('/tahunan', [FinanceController::class, 'labaRugiTahunan'])->name('tahunan');

});


// routes/web.php

Route::middleware(['web', 'auth', 'tenant'])->group(function () {
    // Route untuk menampilkan halaman suspended
    Route::get('/account-suspended', [TenantController::class, 'suspended'])->name('tenant.suspended');

    // Route AJAX untuk ambil URL DOKU bar
});


// --- GRUP 2: ZONA TERKUNCI (Ada Middleware 'tenant') ---
// Route di sini WAJIB status ACTIVE. Kalau inactive, akan ditendang ke GRUP 1.
Route::middleware(['web', 'auth'])->group(function () {

    // 1. AREA UMUM (Semua user login bisa akses)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 2. AREA KHUSUS OWNER/ADMIN
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/settings', [DashboardController::class, 'settings']);
        Route::get('/users-management', [DashboardController::class, 'users']);
    });

    // 3. AREA KEUANGAN (Bisa diakses Admin & Orang Keuangan)
    Route::middleware(['role:admin|keuangan'])->group(function () {
        Route::get('/laporan-laba', [FinanceController::class, 'index']);
        Route::get('/gaji-karyawan', [FinanceController::class, 'salary']);
    });

    // 4. AREA OPERATOR / KASIR (Admin, Staff, Operator)
    Route::middleware(['role:admin|staff|operator'])->group(function () {
        Route::get('/transaksi-baru', [OrderController::class, 'create']);
        Route::get('/data-produk', [OrderController::class, 'products']);
    });

});


/*
|--------------------------------------------------------------------------
| DANA DASHBOARD INTEGRATION (FLAT ROUTES)
|--------------------------------------------------------------------------
*/

Route::post('/member/deposit-dana', [App\Http\Controllers\MemberAuthController::class, 'depositViaDana'])
    ->name('deposit.dana')
    ->middleware('auth:member');

Route::post('/member/deposit', [MemberAuthController::class, 'storeDeposit'])->name('deposit.store');

// 1. Dashboard & Utama
Route::get('/dana-dashboard', [DanaDashboardController::class, 'index'])->name('dana.dashboard');
Route::post('/dana-topup', [MemberAuthController::class, 'topupSaldo'])->name('dana.topup');

// 2. Binding & Callback
Route::post('/dana-do-bind', [DanaDashboardController::class, 'startBinding'])->name('dana.do_bind');
Route::get('/dana-callback', [DanaDashboardController::class, 'handleCallback'])->name('dana.callback');

// 3. Monitoring Saldo
Route::post('/dana-check-balance', [DanaDashboardController::class, 'checkBalance'])->name('dana.check_balance');
Route::post('/dana-check-merchant-balance', [DanaDashboardController::class, 'checkMerchantBalance'])->name('dana.check_merchant_balance');

// 4. Disbursement / Inquiry
Route::post('/dana-execute-disbursement', [MemberAuthController::class, 'customerTopup'])->name('dana.execute_disbursement');
Route::post('/dana-account-inquiry', [DanaDashboardController::class, 'accountInquiry'])->name('dana.account_inquiry');


Route::get('/orders/{id}/print-struk', [App\Http\Controllers\OrderController::class, 'printStruk'])
    ->name('orders.print_struk');

Route::post('/orders/check-coupon', [OrderController::class, 'checkCoupon'])->name('orders.check-coupon');

Route::middleware(['auth'])->group(function () {
    Route::get('/tenant-area', [TenantDashboardController::class, 'index'])->name('tenant.dashboard');
    Route::get('/settings', [TenantDashboardController::class, 'edit'])->name('tenant.settings');
    Route::put('/settings', [TenantDashboardController::class, 'update'])->name('tenant.update');
});


// Route Group untuk Tenant (biasanya di dalam Route::domain atau middleware auth)
Route::group(['middleware' => ['web', 'auth']], function () {

    // 1. Route Proses Topup (POST dari Modal Header)
    Route::post('/topup/process', [TenantPaymentController::class, 'generateUrl'])
        ->name('topup.process');

    // 2. Route Cek Status (Untuk Polling JS - Opsional tapi disarankan)
    Route::get('/topup/status', [TenantPaymentController::class, 'checkStatus'])
        ->name('topup.status');

    Route::post('/tenant/payment/url', [TenantPaymentController::class, 'generateUrl'])
    ->name('tenant.payment.url');

        // Route untuk Memulai Binding DANA (Khusus Tenant)
    Route::get('/tenant/dana/connect', [TenantPaymentController::class, 'startBinding'])
        ->name('tenant.dana.start');


    Route::get('/dana/sync', [DanaGatewayController::class, 'syncBalance'])
        ->name('tenant.dana.sync');
});




Route::middleware('auth')->group(function () {
    // Route Profile Bawaan
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update'); // Update Foto/Nama/HP
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // [BARU] Route Update Alamat Lengkap
    Route::patch('/profile/address', [ProfileController::class, 'updateAddress'])->name('profile.address.update');
    // [FIX] Route API Search Alamat sekarang mengarah ke ProfileController
    Route::get('/profile/search-address', [ProfileController::class, 'searchAddressApi'])
        ->name('profile.search_address');

});

Route::get('/orders/tripay-channels', [OrderController::class, 'tripayChannels'])->name('orders.tripay-channels');

// Route untuk Cek Status Transaksi DANA
Route::post('/member/dana/check-status', [DanaGatewayController::class, 'checkTopupStatus'])
    ->name('dana.checkTopupStatus');

// Hitung HPP
Route::middleware(['auth'])->group(function () {
    // 1. Halaman List Produk untuk dipilih (Menu Utama HPP)
    Route::get('/hpp-calculator', [HppController::class, 'index'])->name('hpp.index');

    // 2. Halaman Detail Kalkulator (Tampilan Tabel Input yg tadi dibuat)
    Route::get('/hpp-calculator/{id}/analysis', [HppController::class, 'analysis'])->name('hpp.analysis');

    // 3. Proses Simpan Resep/BOM ke Database
    Route::post('/hpp-calculator/{id}/update', [HppController::class, 'updateRecipe'])->name('hpp.updateRecipe');
    // Simpan Settingan Resep HPP
    Route::post('/products/{id}/hpp', [HppController::class, 'updateRecipe']);

    // Tombol "Produksi / Rakit Barang"
    Route::post('/products/manufacture', [HppController::class, 'manufacture']);
});


Route::get('/test-dana-inconsistent', function (DanaService $danaService) {
    Log::info("[DANA MANUAL TEST] Requesting Payment URL...");

    // 1. Persiapan Variabel Tiruan (Mocking) untuk Testing
    // Dalam implementasi asli, kamu mungkin akan mengambil data dari database, misal: $order = Order::find(1);
    $order = (object) [
        'order_number' => 'TEST-DANA-' . time(),
    ];
    $finalPrice = 15000; // Contoh harga Rp 15.000

    try {
        $timestamp = Carbon::now('Asia/Jakarta')->toIso8601String();
        $expiryTime = Carbon::now('Asia/Jakarta')->addMinutes(30)->format('Y-m-d\TH:i:sP');

        $bodyArray = [
            "partnerReferenceNo" => $order->order_number,
            "merchantId" => config('services.dana.merchant_id'),
            "externalStoreId" => "toko-pelanggan",
            "amount" => ["value" => number_format($finalPrice, 2, '.', ''), "currency" => "IDR"],
            "validUpTo" => $expiryTime,
            "urlParams" => [
                ["url" => route('dana.return'), "type" => "PAY_RETURN", "isDeeplink" => "Y"],
                ["url" => route('dana.notify'), "type" => "NOTIFICATION", "isDeeplink" => "Y"]
            ],
            "additionalInfo" => [
                "mcc" => "5732",
                "order" => [
                    "orderTitle" => "Invoice " . $order->order_number,
                    "merchantTransType" => "01",
                    "scenario" => "REDIRECT"
                ],
                "envInfo" => [
                    "sourcePlatform" => "IPG",
                    "terminalType" => "SYSTEM",
                    "orderTerminalType" => "WEB"
                ]
            ]
        ];

        $jsonBody = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $relativePath = '/payment-gateway/v1.0/debit/payment-host-to-host.htm';

        $accessToken = $danaService->getAccessToken();
        $signature = $danaService->generateSignature('POST', $relativePath, $jsonBody, $timestamp);
        $baseUrl = config('services.dana.dana_env') === 'PRODUCTION' ? 'https://api.dana.id' : 'https://api.sandbox.dana.id';

        $response = Http::withHeaders([
            'Authorization'  => 'Bearer ' . $accessToken,
            'X-PARTNER-ID'   => config('services.dana.x_partner_id'),
            'X-EXTERNAL-ID'  => Str::random(32),
            'X-TIMESTAMP'    => $timestamp,
            'X-SIGNATURE'    => $signature,
            'Content-Type'   => 'application/json',
            'CHANNEL-ID'     => '95221',
            'ORIGIN'         => config('services.dana.origin'),
        ])->withBody($jsonBody, 'application/json')->post($baseUrl . $relativePath);

        $result = $response->json();

        // 2. Evaluasi Hasil Response DANA
        if (isset($result['responseCode']) && $result['responseCode'] == '2005400') {
            $paymentUrl = $result['webRedirectUrl'] ?? null;

            // Catatan: Jika menggunakan object tiruan (bukan Model Eloquent), comment baris update ini
            // $order->update(['payment_url' => $paymentUrl]);
            $triggerWaType = 'unpaid';

            // Mengembalikan response JSON agar mudah dilihat di browser / Postman
            return response()->json([
                'status' => 'success',
                'message' => 'Payment URL berhasil dibuat.',
                'payment_url' => $paymentUrl,
                'data' => $result
            ]);

        } else {
            return response()->json([
                'status' => 'failed',
                'message' => "DANA API Error: " . ($result['responseMessage'] ?? 'General Error'),
                'data' => $result
            ], 400);
        }

    } catch (\Exception $e) {
        Log::error("DANA MANUAL Error: " . $e->getMessage());

        return response()->json([
            'status' => 'error',
            'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
        ], 500);
    }
});
