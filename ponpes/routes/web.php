<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes (FILE UTAMA)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('home');
});

// --- DASHBOARD USER BIASA ---
Route::get('/dashboard', function () {
    if (Auth::user()->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


// --- MEMANGGIL ROUTE ADMIN (PONDOK.PHP) ---
// Kita hanya pasang Middleware 'auth' di sini.
// Prefix 'admin' TIDAK DITULIS di sini, karena sudah ada di dalam file pondok.php
Route::middleware(['auth', 'verified'])->group(function () {
    
    if (file_exists(base_path('routes/web/pondok.php'))) {
        require base_path('routes/web/pondok.php');
    }

});


// --- PROFILE SETTINGS ---
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';