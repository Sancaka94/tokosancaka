<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Toko\DashboardController;
use App\Http\Controllers\Toko\ProdukController;
use App\Http\Controllers\Toko\PesananController;
use App\Http\Controllers\Toko\ProfileTokoController; // Pastikan ini di-import
use App\Http\Controllers\Toko\ProfileController;

/*
|--------------------------------------------------------------------------
| Seller Routes
|--------------------------------------------------------------------------
|
| Rute ini khusus untuk pengguna dengan role 'Seller'.
| Prefix '/seller' dan nama 'seller.' sudah diatur di web.php.
|
*/

// Dashboard utama untuk seller
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Manajemen Produk (Contoh menggunakan resource controller)
// Ini akan membuat route untuk: index, create, store, show, edit, update, destroy
Route::resource('produk', ProdukController::class);

// Manajemen Pesanan
Route::prefix('pesanan')->name('pesanan.')->group(function () {
    Route::get('/', [PesananController::class, 'index'])->name('index'); // Daftar pesanan masuk
    Route::get('/{order}', [PesananController::class, 'show'])->name('show'); // Detail pesanan
    Route::post('/{order}/update-status', [PesananController::class, 'updateStatus'])->name('updateStatus'); // Update status pesanan (misal: dikirim)
});


// Route untuk menampilkan halaman edit profil toko
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

// Route untuk memproses update profil toko
Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
