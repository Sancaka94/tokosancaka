<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\TenantRegistrationController;
use App\Http\Middleware\IdentifyTenant;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Bungkus semua route dalam middleware IdentifyTenant agar subdomain selalu terbaca
Route::middleware([IdentifyTenant::class])->group(function () {

    // ==========================================
    // AREA PUBLIK (Landing Page & Pendaftaran)
    // ==========================================
    Route::get('/', function () {
        return view('welcome');
    });

    // Route Pendaftaran Tenant Baru
    Route::get('/daftar-parkir', [TenantRegistrationController::class, 'create'])->name('daftar.parkir');
    Route::post('/daftar-parkir', [TenantRegistrationController::class, 'store'])->name('daftar.parkir.store');

    // Route Affiliate (Placeholder)
    Route::get('/join-affiliate', function () {
        return "Halaman Affiliate Belum Tersedia";
    })->name('affiliate.create');


    // ==========================================
    // AREA MEMBER (Harus Login)
    // ==========================================
    // PERBAIKAN: Middleware 'verified' dihapus dari sini agar langsung masuk dashboard
    Route::middleware('auth')->group(function () {

        // 1. Dashboard Utama (Menggunakan Controller, bukan Closure bawaan)
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // 2. Operasional Transaksi Parkir
        Route::resource('transactions', TransactionController::class);

        // 3. Master Data Pegawai
        Route::resource('employees', EmployeeController::class);

        // 4. Laporan Keuangan
        Route::prefix('laporan')->name('laporan.')->group(function () {
            Route::get('/harian', [DashboardController::class, 'harian'])->name('harian');
            Route::get('/bulanan', [DashboardController::class, 'bulanan'])->name('bulanan');
            Route::get('/triwulan', [DashboardController::class, 'triwulan'])->name('triwulan');
        });

        // 5. Pengaturan Profil Bawaan Breeze
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

});

// Memuat rute otentikasi bawaan Breeze (Login, Register, Logout)
require __DIR__.'/auth.php';
