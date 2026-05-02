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
Route::get('/transactions/example', [TransactionController::class, 'downloadExample'])->name('transactions.example');
Route::post('/transactions/import', [TransactionController::class, 'import'])->name('transactions.import');

// ROUTE CRUD TRANSAKSI MANUAL & TABEL
Route::get('/transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
Route::get('/transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.edit');
Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
Route::delete('/transactions-bulk-delete', [TransactionController::class, 'bulkDelete'])->name('transactions.bulk-delete');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/dashboard/export-pdf', [DashboardController::class, 'exportPdf'])->name('dashboard.export-pdf');

Route::get('/cities/example-csv', [CityController::class, 'downloadExample'])->name('cities.example');
Route::delete('/cities/bulk-delete', [CityController::class, 'bulkDelete'])->name('cities.bulk-delete');

// Route CRUD & Upload City
Route::post('/cities/import', [CityController::class, 'import'])->name('cities.import');
Route::resource('cities', CityController::class);

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/cities/update-coordinates', [App\Http\Controllers\DashboardController::class, 'updateCoordinates'])->name('cities.update-coords');

require __DIR__.'/auth.php';
