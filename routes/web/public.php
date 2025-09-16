<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\EtalaseController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\KontakController;
use App\Http\Controllers\PublicScanController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\ProfileSetupController;
use App\Http\Controllers\ScanController;

use App\Models\Regency;
use App\Models\District;
use App\Models\Village;

Route::get('/', function () {
    return view('home');
})->name('home'); // <-- TAMBAHKAN INI


// Homepage
Route::get('/', [BlogController::class, 'index'])->name('home');

// Routes for serving images and storage files
Route::get('/images/{filename}', function ($filename) {
    $path = storage_path('app/images/' . $filename);
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path);
})->where('filename', '.*')->name('images.show');

Route::get('/storage/{path}', function ($path) {
    $disk = Storage::disk('public');
    if (!$disk->exists($path)) {
        abort(404, 'File not found.');
    }
    $file = $disk->get($path);
    $mime = $disk->mimeType($path);
    return Response::make($file, 200, [
        'Content-Type' => $mime,
        'Content-Disposition' => 'inline; filename="' . basename($path) . '"'
    ]);
})->where('path', '.*')->name('storage.show');

// Blog Routes
Route::prefix('blog')->name('blog.')->group(function () {
    Route::get('/', [BlogController::class, 'blogIndex'])->name('home');
    Route::get('/posts/{slug}', [BlogController::class, 'show'])->name('posts.show');
    Route::get('/categories', [BlogController::class, 'categories'])->name('categories');
    Route::get('/about', [BlogController::class, 'about'])->name('about');
    Route::get('/services', [BlogController::class, 'services'])->name('services');
});

// Public Feature Routes
Route::get('/etalase', [EtalaseController::class, 'index'])->name('etalase.index');
Route::get('/toko/profile/{name}', [EtalaseController::class, 'profileToko'])->name('products.profileToko');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
Route::get('/tracking', [TrackingController::class, 'showTrackingPage'])->name('tracking.index');
Route::get('/tracking/search', [TrackingController::class, 'trackPackage'])->name('tracking.search');
Route::get('/kontak/search', [KontakController::class, 'search'])->name('kontak.search');

// Public Order Creation
Route::get('/buat-pesanan', [CustomerOrderController::class, 'create'])->name('pesanan.public.create');
Route::post('/buat-pesanan', [CustomerOrderController::class, 'store'])->name('pesanan.public.store');
Route::get('/pesanan-sukses', [CustomerOrderController::class, 'success'])->name('pesanan.public.success');

// Public SPX Scan
Route::get('/scan-spx', [PublicScanController::class, 'show'])->name('scan.spx.show');
Route::post('/kontak/register', [PublicScanController::class, 'registerKontak'])->name('kontak.register');
Route::post('/scan-spx/handle', [PublicScanController::class, 'handleScan'])->name('scan.spx.handle');
Route::post('/scan-spx/surat-jalan', [PublicScanController::class, 'createSuratJalan'])->name('scan.spx.suratjalan.create');
Route::get('/scan-spx/download-surat-jalan/{kode}', [PublicScanController::class, 'downloadSuratJalan'])->name('scan.spx.suratjalan.download');
Route::get('/surat-jalan/pdf', [ScanController::class, 'generateSuratJalan'])->name('surat.jalan.pdf');


// Cart & Checkout
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

// Profile Setup (for new users)
Route::get('/profile/setup/{user}', [ProfileSetupController::class, 'show'])
    ->middleware('signed')
    ->name('profile.setup');
Route::post('/profile/setup/{user}', [ProfileSetupController::class, 'update'])
    ->name('profile.setup.update');

// API routes for regions
Route::get('/api/regencies/{province_id}', function($province_id) {
    return Regency::where('province_id', $province_id)->get();
});

Route::get('/api/districts/{regency_id}', function($regency_id) {
    return District::where('regency_id', $regency_id)->get();
});

Route::get('/api/villages/{district_id}', function($district_id) {
    return Village::where('district_id', $district_id)->get();
});

