<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // <--- Pastikan ada ini
use App\Http\Controllers\Pondok\Admin\SettingController;
use App\Http\Controllers\ProfileController;


/*
|--------------------------------------------------------------------------
| Web Routes (FILE UTAMA)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    // 1. Ambil data dari tabel 'paket'
    // Kita ambil semua paket, atau difilter yang aktif saja jika ada kolom status
    $packages = DB::table('paket')->get(); 

    // 2. Kirim data '$packages' ke file view (misal: welcome.blade.php)
    return view('home', compact('packages')); 
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
      // --- ROUTE PENGATURAN (SETTINGS) ---
    Route::get('/admin/settings', [SettingController::class, 'index'])->name('admin.settings.index');
    Route::post('/admin/settings', [SettingController::class, 'update'])->name('admin.settings.update');
    
});

require __DIR__.'/auth.php';