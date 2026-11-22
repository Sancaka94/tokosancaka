<?php

use App\Http\Controllers\Api\Mobile\EmailController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile API Routes - Sancaka Mail (Expo App)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('mobile/email')->name('api.mobile.email.')->group(function () {
    // 1. Cek status konfigurasi webmail (@tokosancaka.com) milik user login
    Route::get('/status', [EmailController::class, 'status'])->name('status');

    // 2. Ambil daftar email (inbox, starred, sent) + pagination & search
    Route::get('/fetch', [EmailController::class, 'fetch'])->name('fetch');

    // 3. Auto-complete pencarian pengguna untuk tujuan email
    Route::get('/search-users', [EmailController::class, 'searchUsers'])->name('search-users');

    // 4. Kirim email baru beserta lampiran via SMTP user login
    Route::post('/send', [EmailController::class, 'send'])->name('send');

    // 5. Hapus email satuan maupun masal (IMAP & Lokal DB)
    Route::post('/delete', [EmailController::class, 'destroy'])->name('delete');

    // 6. Buka detail isi email (dibatasi angka agar tidak bentrok dengan route lain)
    Route::get('/{id}', [EmailController::class, 'show'])->whereNumber('id')->name('show');

    // 7. Update status bintang (is_starred) atau pindah folder
    Route::put('/{id}', [EmailController::class, 'update'])->whereNumber('id')->name('update');
});

require __DIR__ . '/sancaka.app.php';
