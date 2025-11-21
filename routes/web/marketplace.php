<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;

/*
|--------------------------------------------------------------------------
| Marketplace Routes
|--------------------------------------------------------------------------
|
| File ieu ngandung sadaya route pikeun bagian hareup (frontend)
| toko online anjeun anu tiasa diakses ku sadaya pengunjung.
|
*/

// Halaman Beranda Utama
Route::get('/', function () {
    return view('home');
})->name('home');

// Halaman Etalase & Detail Produk
Route::get('/etalase', [ProductController::class, 'index'])->name('products.index');
Route::get('/toko/profile/{name}', [ProductController::class, 'profileToko'])->name('products.profileToko');
Route::get('/produk/{product:slug}', [ProductController::class, 'show'])->name('products.show');


// --- ROUTE UNTUK KERANJANG BELANJA (Peryogi Login) ---
// Grup ieu mastikeun yén ngan ukur pangguna anu tos login anu tiasa ngakses keranjangna.
Route::middleware(['auth'])->group(function () {
    Route::get('/keranjang', [CartController::class, 'index'])->name('cart.index');
    Route::post('/keranjang/tambah/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::delete('/keranjang/hapus/{cartItem}', [CartController::class, 'remove'])->name('cart.remove');
});
