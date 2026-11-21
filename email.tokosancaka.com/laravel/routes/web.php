<?php

use App\Http\Controllers\Admin\EmailController;
use Illuminate\Support\Facades\Route;

// Fallback route login jika middleware 'auth' mendeteksi user belum login
Route::get('/login', function () {
    return redirect()->away('https://tokosancaka.com/login');
})->name('login');

// Halaman utama subdomain langsung membuka webmail
Route::middleware(['auth'])->group(function () {
    Route::get('/', [EmailController::class, 'index'])->name('home');

    Route::prefix('admin/email')->name('admin.email.')->group(function () {
        Route::get('/', [EmailController::class, 'index'])->name('index');
        Route::get('/fetch', [EmailController::class, 'fetch'])->name('fetch');
        Route::get('/search-users', [EmailController::class, 'searchUsers'])->name('searchUsers');
        Route::get('/{id}', [EmailController::class, 'show'])->name('show');
        Route::post('/send', [EmailController::class, 'send'])->name('send');
        Route::put('/{id}', [EmailController::class, 'update'])->name('update');
        Route::delete('/destroy', [EmailController::class, 'destroy'])->name('destroy');
    });
});
