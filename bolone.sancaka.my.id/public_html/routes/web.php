<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\TransactionController;

Route::get('/cities/create', [App\Http\Controllers\CityController::class, 'create'])->name('cities.create');
Route::get('/transaksi/input', [TransactionController::class, 'create'])->name('transactions.create');
Route::post('/transaksi/store', [TransactionController::class, 'store'])->name('transactions.store');

// ROUTE BARU UNTUK UPLOAD EXCEL TRANSAKSI
Route::get('/transaksi/example', [TransactionController::class, 'downloadExample'])->name('transactions.example');
Route::post('/transaksi/import', [TransactionController::class, 'import'])->name('transactions.import');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/dashboard/export-pdf', [DashboardController::class, 'exportPdf'])->name('dashboard.export-pdf');

Route::get('/cities/example-csv', [CityController::class, 'downloadExample'])->name('cities.example');

// Route CRUD & Upload City
Route::post('/cities/import', [CityController::class, 'import'])->name('cities.import');
Route::resource('cities', CityController::class);

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
