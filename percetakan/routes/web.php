<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OrderController; // <-- BARIS INI WAJIB ADA
use App\Http\Controllers\ProductController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\CouponController;


Route::middleware(['auth'])->group(function () {
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');


// Halaman POS
Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');

// Halaman Admin (Posting Produk)
Route::post('/products/store', [ProductController::class, 'store'])->name('products.store');

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Laporan Penjualan
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // Rute tambahan untuk produk

    // Daftar Produk & Form Tambah
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    // Proses Simpan Produk Baru
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    // Hapus Produk
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    Route::resource('products', ProductController::class);
    
});

Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');

// Proses Simpan Pesanan (API Endpoint untuk AJAX)
Route::post('/orders/store', [OrderController::class, 'store'])->name('orders.store');

// PENTING: Taruh di ATAS Route::resource 'orders'
Route::post('/orders/check-coupon', [OrderController::class, 'checkCoupon'])->name('orders.check-coupon');

Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');


// Route untuk Halaman Depan (Publik)
Route::get('/join-partner', [AffiliateController::class, 'create'])->name('affiliate.create');
Route::post('/join-partner', [AffiliateController::class, 'store'])->name('affiliate.store');
// Route untuk Admin melihat daftar Afiliasi
Route::get('/affiliates', [AffiliateController::class, 'index'])->name('affiliate.index');

Route::get('/affiliate/print-qr/{id}', [AffiliateController::class, 'printQr'])->name('affiliate.print_qr');

Route::post('/affiliate/sync-balance', [AffiliateController::class, 'syncBalance'])->name('affiliate.sync');

// Group Route untuk Affiliate
Route::prefix('affiliate')->name('affiliate.')->group(function () {
    
    // 1. Halaman Pengaturan (Menampilkan Form)
    Route::get('/settings', [AffiliateController::class, 'settings'])->name('settings');

    // 2. Proses Update Profil (Nama, WA, Alamat, Bank)
    Route::put('/update-profile', [AffiliateController::class, 'updateProfile'])->name('update_profile');

    // 3. Proses Update PIN (Buat Baru / Ganti PIN)
    Route::put('/update-pin', [AffiliateController::class, 'updatePin'])->name('update_pin');

});

// Jangan arahkan langsung ke view, tapi ke Controller
Route::get('/affiliate/settings', [App\Http\Controllers\AffiliateController::class, 'settings'])->name('affiliate.settings');

// Resourceful Routes untuk Order
Route::resource('reports', ReportController::class)->except(['create', 'store']);


// Kembalikan jadi resource biasa (tanpa except)
Route::resource('coupons', CouponController::class);

// Pastikan baris ini ada di paling bawah untuk memuat rute Login/Register
require __DIR__.'/auth.php';