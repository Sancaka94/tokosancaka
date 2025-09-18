<?php



use Illuminate\Http\Request;

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;

use App\Http\Controllers\Api\DashboardController;

use App\Http\Controllers\Api\LocationController;

use App\Http\Controllers\Admin\EmailController;

use App\Http\Controllers\ChatController;

use App\Http\Controllers\CourierController;

use App\Http\Controllers\CheckoutController;

use App\Http\Controllers\CekOngkirController; // Pastikan ini ada

use App\Http\Controllers\KirimAjaController; 

use App\Http\Controllers\Api\PublicApiController; // Pastikan Anda menambahkan ini

use App\Http\Controllers\Api\KontakController;




/*

|--------------------------------------------------------------------------

| API Routes

|--------------------------------------------------------------------------

|

| Di sinilah Anda mendaftarkan rute API untuk aplikasi Anda. Semua rute ini

| akan memiliki prefix /api/ secara otomatis.

|

*/

Route::post('/webhook/kiriminaja', [KirimAjaController::class, 'handle'])->name('api.callback.kirimaja');

Route::post('/callback/tripay', [CheckoutController::class, 'TripayCallback'])->name('api.callback.tripay');


Route::get('/kontak/search', [KontakController::class, 'search'])->name('api.search.kontak');


// Endpoint untuk update status paket dari halaman scan

Route::post('/packages/update-status', [CourierController::class, 'updatePackageStatus'])->name('api.packages.update_status');



// Endpoint untuk kurir mengirimkan update lokasi GPS

Route::post('/couriers/{id}/location', [CourierController::class, 'updateLocation'])->name('api.couriers.location');



// == RUTE OTENTIKASI (PUBLIK) ==

// Rute ini bisa diakses tanpa perlu login.

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);



// == RUTE DATA WILAYAH (PUBLIK) ==

// Rute ini digunakan untuk mengambil data provinsi, kabupaten, dll.

Route::prefix('locations')->group(function () {

    Route::get('/provinces', [LocationController::class, 'getProvinces']);

    Route::get('/regencies/{province_id}', [LocationController::class, 'getRegencies']);

    Route::get('/districts/{regency_id}', [LocationController::class, 'getDistricts']);

    Route::get('/villages/{district_id}', [LocationController::class, 'getVillages']);

});



// === RUTE PENCARIAN ALAMAT (PUBLIK) ===

// Ditempatkan di sini agar bisa diakses secara publik oleh JavaScript

// tanpa perlu login. Namanya disesuaikan dengan panggilan di view.

Route::get('/ongkir/address/search', [CekOngkirController::class, 'searchAddress'])

       ->name('api.ongkir.address.search');



// === RUTE UNTUK CEK ONGKIR (PUBLIK) ===

// Menerima data dari form untuk menghitung biaya pengiriman.

Route::post('/ongkir/cost', [CekOngkirController::class, 'checkCost'])

       ->name('api.ongkir.cost.check');





// == RUTE YANG DILINDUNGI (BUTUH LOGIN/TOKEN) ==

// Semua rute di dalam grup ini memerlukan token otentikasi dari Sanctum.

Route::middleware('auth:sanctum')->group(function () {

    // Mengambil data pengguna yang sedang login

    Route::get('/user', function (Request $request) {

        return $request->user();

    });

    





    // Melakukan logout

    Route::post('/logout', [AuthController::class, 'logout']);



    // Mengambil data statistik untuk dashboard aplikasi.

    Route::get('/dashboard', [DashboardController::class, 'index']);



    // Grup untuk Rute API Email Admin

    // URL akan menjadi: /api/admin/email/...

    Route::prefix('admin')->name('api.admin.')->group(function () {

        Route::get('/email', [EmailController::class, 'fetchEmails'])->name('email.fetch');

        Route::post('/email/send', [EmailController::class, 'send'])->name('email.send');

        Route::get('/email/{id}', [EmailController::class, 'show'])->name('email.show');

        Route::patch('/email/{id}', [EmailController::class, 'update'])->name('email.update');

        Route::delete('/email/{id}', [EmailController::class, 'destroy'])->name('email.destroy');

    });

});



// ==========================================================

// == RUTE UNTUK FITUR PENCARIAN ALAMAT & KODE POS PUBLIK ==

// ==========================================================

Route::get('/wilayah/provinces', [PublicApiController::class, 'getProvinces'])->name('api.wilayah.provinces');

Route::get('/wilayah/kabupaten/{province}', [PublicApiController::class, 'getKabupaten'])->name('api.wilayah.kabupaten');

Route::get('/wilayah/kecamatan/{regency}', [PublicApiController::class, 'getKecamatan'])->name('api.wilayah.kecamatan');

Route::get('/kodepos/by-district/{district}', [PublicApiController::class, 'getDesaByDistrict'])->name('api.kodepos.by-district');

Route::get('/kodepos/public-search', [PublicApiController::class, 'searchKodePos'])->name('api.kodepos.public.search');



