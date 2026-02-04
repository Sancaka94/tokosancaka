<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Toko\DashboardController;
use App\Http\Controllers\Toko\ProdukController;
use App\Http\Controllers\Toko\PesananController;
use App\Http\Controllers\Toko\ProfileTokoController; // Pastikan ini di-import
use App\Http\Controllers\Toko\ProfileController;
use App\Http\Controllers\Toko\CategoryController;
use App\Http\Controllers\Toko\OrderController; // <-- TAMBAHKAN INI
use App\Http\Controllers\Toko\DokuRegistrationController;

/*
|--------------------------------------------------------------------------
| Seller Routes
|--------------------------------------------------------------------------
|
| Rute ini khusus untuk pengguna dengan role 'Seller'.
| Prefix '/seller' dan nama 'seller.' sudah diatur di web.php.
|
*/

// =====================================================================
// PERBAIKAN: Bungkus SEMUA rute seller dengan middleware 'auth'
// =====================================================================
Route::middleware(['auth'])->group(function () {

    // Dashboard utama untuk seller
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Manajemen Produk
    Route::resource('produk', ProdukController::class);

    // Rute Pendaftaran DOKU (sekarang di dalam auth)
    Route::get('/doku/registration', [DokuRegistrationController::class, 'index'])->name('doku.index');
    Route::post('/doku/registration', [DokuRegistrationController::class, 'register'])->name('doku.register');

// ==========================================================
// === PERBAIKAN: Hapus prefix 'seller/' dan 'seller.' ===
// === Nama route 'seller.' sudah ditambahkan oleh web.php ===
// ==========================================================
// (BARU) Tombol Refresh Saldo
Route::post('/doku/refresh', [DokuRegistrationController::class, 'refreshBalance'])
->name('doku.refreshBalance'); // Nama lengkap: seller.doku.refreshBalance
// (BARU) Form Payout / Withdrawal
Route::post('/doku/payout', [DokuRegistrationController::class, 'handlePayout'])
->name('doku.payout'); // Nama lengkap: seller.doku.payout
// (BARU) Form Transfer
Route::post('/doku/transfer', [DokuRegistrationController::class, 'handleTransfer'])
->name('doku.transfer'); // Nama lengkap: seller.doku.transfer
// ==========================================================

// ==========================================================
// === PERBAIKAN: Rute baru untuk Pencairan Saldo Utama ke Dompet ===
// ==========================================================
Route::post('/doku/cairkan-saldo-utama', [DokuRegistrationController::class, 'cairkanSaldoUtama'])
->name('doku.cairkanSaldoUtama');
// ==========================================================

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

    // Route untuk mengambil atribut kategori secara dinamis (AJAX)
    Route::get('categories/{category}/attributes', [CategoryController::class, 'getAttributes'])
         ->name('categories.attributes');
         
    Route::get('produk/export/excel', [ProdukController::class, 'exportExcel'])->name('produk.export.excel');
    Route::get('produk/export/pdf', [ProdukController::class, 'exportPdf'])->name('produk.export.pdf');

    Route::get('pesanan-marketplace', [OrderController::class, 'index'])->name('pesanan.marketplace.index');

}); // <-- Tutup grup middleware 'auth' di sini
// =====================================================================