<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Pondok\Admin\SettingController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes (FILE UTAMA)
|--------------------------------------------------------------------------
*/

// --- LANDING PAGE DENGAN AUTO-REDIRECT ---
Route::get('/', function () {
    // Jika user sudah login, langsung arahkan ke dashboard masing-masing
    if (Auth::check()) {
        return redirect()->intended('/dashboard');
    }

    // 1. Ambil data dari tabel 'paket'
    $packages = DB::table('paket')->get(); 

    // 2. Kirim data ke view
    return view('home', compact('packages')); 
})->name('home');

// --- DASHBOARD USER BIASA & ADMIN ---
Route::get('/dashboard', function () {
    // Logic Redirect berdasarkan Role
    if (Auth::user()->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }
    return view('pondok.user.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


// --- GRUP ROUTE TERPROTEKSI (AUTH) ---
Route::middleware(['auth', 'verified'])->group(function () {
    
    // --- PROFILE SETTINGS ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // --- MEMANGGIL ROUTE ADMIN (PONDOK.PHP) ---
    if (file_exists(base_path('routes/web/pondok.php'))) {
        require base_path('routes/web/pondok.php');
    }
});

Route::middleware('auth')->group(function () {
    // ... route lainnya
    
    Route::get('/admin/settings', [SettingController::class, 'index'])->name('admin.settings.index');
    
    // PASTIKAN BARIS INI ADA DAN MENGGUNAKAN METHOD POST/PATCH
    Route::post('/admin/settings', [SettingController::class, 'update'])->name('admin.settings.update');
});

// --- AUTHENTICATION ROUTES (LOGIN, REGISTER, DLL) ---
// Di dalam file ini, Laravel secara default menggunakan middleware 'guest' 
// yang akan me-redirect user yang sudah login ke RouteServiceProvider::HOME
require __DIR__.'/auth.php';