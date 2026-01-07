<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
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


// 1. Buat Pembayaran
//Route::get('/dana/pay', [DanaWidgetController::class, 'createPayment'])->name('dana.pay');
Route::any('/dana/pay', [DanaWidgetController::class, 'createPayment'])->name('dana.pay');

// 2. Halaman Balik (Return)
Route::get('/dana/return', function () {
    return view('dana_success');
})->name('dana.return');

// 3. Cek Status Manual (Baru)
Route::get('/dana/status/{orderId}', [DanaWidgetController::class, 'checkStatus'])->name('dana.status');

// Route untuk Test Disbursement (Kirim Uang ke User)
Route::get('/dana/test-disburse', [App\Http\Controllers\DanaWidgetController::class, 'disburseTopUp']);

// 1. Cek Akun User DANA (Account Inquiry)
Route::get('/dana/test-inquiry', [App\Http\Controllers\DanaWidgetController::class, 'disburseAccountInquiry']);

// 2. Kirim Uang (TopUp)
Route::get('/dana/test-topup', [App\Http\Controllers\DanaWidgetController::class, 'disburseTopUp']);

// 3. Cek Status Transaksi (Status Inquiry)
// Nanti aksesnya: /dana/test-status?order_id=TOPUP-123456...
Route::get('/dana/test-status', [App\Http\Controllers\DanaWidgetController::class, 'disburseCheckStatus']);

Route::post('/dana/notify', [App\Http\Controllers\DanaWidgetController::class, 'handleNotify'])->name('dana.notify');


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

    // Rute tambahan untuk produk

    // Daftar Produk & Form Tambah
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    // Proses Simpan Produk Baru
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    // Hapus Produk
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    Route::resource('products', ProductController::class);

    // Resourceful Routes untuk Order
Route::resource('reports', ReportController::class)->except(['create', 'store']);


// Kembalikan jadi resource biasa (tanpa except)
Route::resource('coupons', CouponController::class);
    
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
    
    
    });
});

// Route untuk halaman Cara / Panduan
Route::get('/cara', function () {
    return view('cara');
});


// Route untuk Halaman Riwayat Pesanan

// --- TARUH ROUTE SPESIFIK DI ATAS ---

// 1. Halaman POS (Create) - HARUS DI ATAS {id}
Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');

// 2. Route Autocomplete & Helper - HARUS DI ATAS {id}
Route::get('/orders/search-location', [OrderController::class, 'searchLocation'])->name('orders.search-location');
Route::get('/orders/tripay-channels', [OrderController::class, 'getPaymentChannels'])->name('orders.tripay-channels');

// 3. Halaman Index
Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');

// 4. Route Wildcard {id} (Show) - TARUH PALING BAWAH
// Agar tidak "memakan" route lain yang punya prefix /orders/
Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');

// --- Route POST (Urutannya tidak terlalu berpengaruh karena methodnya beda, tapi dirapikan saja) ---
Route::post('/orders/check-ongkir', [OrderController::class, 'checkShippingRates'])->name('orders.check-ongkir');
Route::post('/orders/store', [OrderController::class, 'store'])->name('orders.store');
Route::post('/orders/check-coupon', [OrderController::class, 'checkCoupon'])->name('orders.check-coupon');


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