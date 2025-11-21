<?php



use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;

use App\Http\Middleware\EnsureProfileIsSetup;



use App\Http\Controllers\Customer\PesananController as CustomerPesananController;

use App\Http\Controllers\Customer\ScanController as CustomerScanController;

use App\Http\Controllers\Customer\TopUpController;

use App\Http\Controllers\Customer\LaporanKeuanganController;

use App\Http\Controllers\Customer\LacakController as CustomerLacakController;

use App\Http\Controllers\CustomerOrderController;

use App\Http\Controllers\Customer\ProfileController as CustomerProfileController;

use App\Http\Controllers\Toko\ProdukController; // Asumsi Anda akan membuat controller ini

use App\Http\Controllers\Toko\PesananController; // Asumsi Anda akan membuat controller ini

use App\Http\Controllers\CekOngkirController;

use App\Http\Controllers\KontakController;



// ... rute-rute Anda yang lain ...



Route::get('/cek-ongkir', [CekOngkirController::class, 'index'])->name('ongkir.index');

/*

|--------------------------------------------------------------------------

| Customer Routes

|--------------------------------------------------------------------------

|

| All routes in this file are automatically prefixed with '/customer' and

| named 'customer.' by the main web.php file.

|

*/







Route::get('/show-profile', [CustomerProfileController::class, 'show'])->name('profile.show');

Route::get('/profile', [CustomerProfileController::class, 'edit'])->name('profile.edit');

Route::put('/profile', [CustomerProfileController::class, 'update'])->name('profile.update');

Route::put('/profile/update-setup/{token}', [CustomerProfileController::class, 'updateSetup'])->name('profile.update.setup');



Route::middleware([EnsureProfileIsSetup::class])->group(function () {

    

    Route::get('/cek-ongkir', [CekOngkirController::class, 'index'])->name('ongkir.index');







    Route::get('/dashboard', [CustomerDashboardController::class, 'index'])->name('dashboard');



    Route::get('/pesanan/riwayat', [CustomerPesananController::class, 'riwayat'])->name('riwayat');



    Route::get('/balance', [CustomerDashboardController::class, 'getBalance'])->name('balance');



    // Pesanan

    Route::prefix('pesanan')->name('pesanan.')->group(function() {

        Route::get('/', [CustomerPesananController::class, 'index'])->name('index');

        Route::get('/create', [CustomerPesananController::class, 'create'])->name('create');

        Route::post('/', [CustomerPesananController::class, 'store'])->name('store');

        Route::get('/{order}', [CustomerPesananController::class, 'show'])->name('show');

        Route::get('/{resi}/cetak-thermal', [CustomerPesananController::class, 'cetakResiThermal'])->name('cetak_thermal');

        Route::get('/{invoice_number}', [CustomerPesananController::class, 'checkout'])->name('checkout');

         

    });

    Route::get('/pesanan/status', [CustomerOrderController::class, 'status'])->name('pesanan.status');



    // Top Up

    Route::resource('topup', TopUpController::class);



    // Scan & Laporan

    Route::get('/riwayat-scan', [CustomerScanController::class, 'index'])->name('scan.index');

    Route::get('/laporan-keuangan', [LaporanKeuanganController::class, 'index'])->name('laporan.index');

    Route::get('/lacak-paket', [CustomerLacakController::class, 'index'])->name('lacak.index');

    Route::get('/scan-history', [CustomerScanController::class, 'getHistory'])->name('scan.history');



    // SPX Scan

    Route::prefix('scan-spx')->group(function() {

        Route::get('/', [CustomerScanController::class, 'showSpxScanner'])->name('scan.spx');

        Route::post('/store', [CustomerScanController::class, 'storeSpxScan'])->name('scan.spx.store');

    });



    // Surat Jalan

    Route::prefix('surat-jalan')->group(function() {

        Route::post('/create', [CustomerScanController::class, 'createSuratJalan'])->name('suratjalan.create');

        Route::get('/download/{kode_surat_jalan}', [CustomerScanController::class, 'downloadSuratJalan'])->name('suratjalan.download');

    });



    // Riwayat Scan

    Route::prefix('riwayat-scan')->name('scan.')->group(function() {

        Route::get('/', [CustomerScanController::class, 'index'])->name('index');

        Route::get('/{resi_number}/edit', [CustomerScanController::class, 'edit'])->name('edit');

        Route::put('/{resi_number}', [CustomerScanController::class, 'update'])->name('update');

        Route::delete('/{resi_number}', [CustomerScanController::class, 'destroy'])->name('destroy');

        Route::get('/export/pdf', [CustomerScanController::class, 'exportPdf'])->name('export.pdf');

        Route::get('/export/excel', [CustomerScanController::class, 'exportExcel'])->name('export.excel');

    });



    // Route untuk pendaftaran seller

    Route::get('/seller/register', [CustomerDashboardController::class, 'showSellerRegistrationForm'])->name('seller.register.form');

    Route::post('/seller/register', [CustomerDashboardController::class, 'registerSeller'])->name('seller.register.submit');



    Route::get('/customer/seller/register', [SellerRegisterController::class, 'create'])

        ->name('customer.seller.register.form');



    Route::post('/customer/seller/register', [SellerRegisterController::class, 'store'])

        ->name('customer.seller.register.submit');



});

