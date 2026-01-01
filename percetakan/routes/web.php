<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OrderController; // <-- BARIS INI WAJIB ADA
use App\Http\Controllers\ProductController;



// Halaman POS
Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');

// Halaman Admin (Posting Produk)
Route::get('/dashboard', [ProductController::class, 'index'])->name('dashboard');
Route::post('/products/store', [ProductController::class, 'store'])->name('products.store');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');

// Pastikan baris ini ada di paling bawah untuk memuat rute Login/Register
require __DIR__.'/auth.php';