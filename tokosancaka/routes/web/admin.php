<?php



use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\File;

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;

use App\Http\Controllers\Admin\ProfileController as AdminProfileController;

use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;

use App\Http\Controllers\Admin\RegistrationController as AdminRegistrationController;

use App\Http\Controllers\Admin\CustomerController;

use App\Http\Controllers\Admin\EkspedisiController;

use App\Http\Controllers\Admin\PesananController as AdminPesananController;

use App\Http\Controllers\Admin\SpxScanController as AdminSpxScanController;

use App\Http\Controllers\Admin\ProductController as AdminProductController;

use App\Http\Controllers\Admin\RoleController;

use App\Http\Controllers\Admin\SaldoRequestController;

use App\Http\Controllers\Admin\ActivityLogController;

use App\Http\Controllers\Admin\AppSettingsController;

use App\Http\Controllers\Admin\PostController;

use App\Http\Controllers\Admin\CategoryController;

use App\Http\Controllers\Admin\TagController;

use App\Http\Controllers\KontakController;

use App\Http\Controllers\Admin\EmailController;

use App\Http\Controllers\Admin\ChatController;

use App\Http\Controllers\Admin\SettingController;

use App\Http\Controllers\Admin\StoreController;

use App\Http\Controllers\Admin\ImapController; // Pastikan path controller ini benar

use App\Http\Controllers\Admin\LoginController;

use App\Http\Controllers\Admin\KontakController as AdminKontakController;

use App\Http\Controllers\Admin\ProductController;

use App\Http\Controllers\Admin\AdminOrderController;

use App\Http\Controllers\Admin\UserController; // <-- Ini untuk tombol aksi tabel

use App\Models\BannerEtalase; // [BARU] Import model Banner

use App\Http\Controllers\Admin\DokuBalanceController; // ✅ DITAMBAHKAN

use App\Http\Controllers\Admin\AdminPpobController;
use App\Http\Controllers\Admin\PpobProductController;

/*

|--------------------------------------------------------------------------

| Admin Routes

|--------------------------------------------------------------------------

|

| File ini berisi semua route untuk panel admin.

| Middleware, prefix, dan name sudah diterapkan di routes/web.php.

|

*/

// ===================================================================
// RUTE-RUTE YANG HILANG (DITAMBAHKAN DI SINI)
// ===================================================================
// Ini diperlukan oleh JavaScript di admin.blade.php untuk mengisi dropdown lonceng

Route::get('/notifications/unread', [AdminNotificationController::class, 'getUnread'])
     ->name('notifications.getUnread'); // [FIX] Diubah dari 'notifications.unread' agar cocok dengan JS


Route::get('/notifications', [AdminNotificationController::class, 'index'])
     ->name('notifications.index'); // 'admin.notifications.index'

Route::post('/notifications/{id}/read', [AdminNotificationController::class, 'markAsRead'])
     ->name('notifications.markAsRead'); // 'admin.notifications.markAsRead'

Route::post('/notifications/read-all', [AdminNotificationController::class, 'markAllAsRead'])
     ->name('notifications.markAllAsRead'); // 'admin.notifications.markAllAsRead'

// ===================================================================
// AKHIR RUTE YANG HILANG
// ===================================================================



Route::post('/admin/users/{user}/toggle-freeze', [App\Http\Controllers\Admin\UserController::class, 'toggleFreeze'])
     ->name('admin.users.toggle-freeze');


// route custom harus ditulis sebelum resource
Route::post('admin/posts/generate-content', [PostController::class, 'generateContent'])
    ->name('posts.generateContent');




Route::get('/email', [ImapController::class, 'index'])->name('imap.index');

Route::get('/settings-markerplace', [SettingController::class, 'index'])->name('settings.banners.index');

Route::post('/settings-markerplace', [SettingController::class, 'store'])->name('settings.banners.store');

Route::post('/settings-markerplaces/{banner}', [SettingController::class, 'update'])->name('settings.banners.update');

Route::delete('/settings-markerplace/{banner}', [SettingController::class, 'destroy'])->name('settings.banners.destroy');


 /*
    |--------------------------------------------------------------------------
    | 1. RUTE PENGATURAN LAMA (settings.blade.php)
    |--------------------------------------------------------------------------
    */
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [AppSettingsController::class, 'index'])->name('index');
        Route::put('/profile', [AppSettingsController::class, 'updateProfile'])->name('profile.update');
        Route::put('/password', [AppSettingsController::class, 'updatePassword'])->name('password.update');
        Route::put('/slider', [AppSettingsController::class, 'updateSlider'])->name('slider.update');
        Route::put('/general', [AppSettingsController::class, 'updateGeneral'])->name('general.update');

        /*
        |--------------------------------------------------------------------------
        | 2. RUTE BARU (dari SettingController)
        |--------------------------------------------------------------------------
        | Ini adalah rute untuk fungsi-fungsi baru yang Anda tambahkan
        */
        
        // Rute untuk update Logo, Banner 2, Banner 3
        Route::put('/main', [AppSettingsController::class, 'updateSettings'])
             ->name('main.update');

        // Rute untuk update Alamat Admin/Toko (yang sedang login)
        Route::put('/address', [AppSettingsController::class, 'updateAddress'])
             ->name('address.update'); // <-- INI ADALAH RUTE YANG ERROR

        // Rute untuk Banner Etalase (Slider Baru)
        Route::post('/banners', [AppSettingsController::class, 'storeBanner'])
             ->name('banners.store');
        
        Route::put('/banners/{banner}', [AppSettingsController::class, 'updateBanner'])
             ->name('banners.update'); 
        
        Route::delete('/banners/{banner}', [AppSettingsController::class, 'destroyBanner'])
               ->name('banners.destroy');
    });


    /*
    |--------------------------------------------------------------------------
    | 3. RUTE MANAJEMEN PENGGUNA (UserController)
    |--------------------------------------------------------------------------
    */
    


    /*
    |--------------------------------------------------------------------------
    | 4. RUTE API (UNTUK KIRIMINAJA & GEOCODING)
    |--------------------------------------------------------------------------
    | Ini adalah rute yang menyebabkan error Anda.
    */
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/search-address', [AppSettingsController::class, 'searchAddressKiriminAja'])
             ->name('search.address'); // <-- Ini akan membuat nama rute: admin.api.search.address
        Route::get('/geocode-address', [AppSettingsController::class, 'geocodeAddress'])
             ->name('geocode.address'); // <-- Ini akan membuat nama rute: admin.api.geocode.address
    });
    
    /*
    |--------------------------------------------------------------------------
    | 2. RUTE UNTUK AKSI TABEL PENGGUNA
    |--------------------------------------------------------------------------
    | Ini menggunakan UserController baru (Anda perlu membuatnya).
    | Rute-rute ini SANGAT PENTING untuk tombol Lihat, Edit, Hapus.
    */
    
    // Rute 'resource' ini secara otomatis membuat semua rute di bawah:
    // • Tombol LIHAT   -> GET    /admin/users/{user}      -> UserController@show
    // • Tombol EDIT    -> GET    /admin/users/{user}/edit -> UserController@edit
    // • Tombol HAPUS   -> DELETE /admin/users/{user}      -> UserController@destroy
    // (Juga membuat 'create', 'store', dan 'update' untuk manajemen user penuh)
    
   



Route::post('/update-settings-markerplace', [SettingController::class, 'updateSettings'])->name('settings.update');



Route::get('/surat-jalan/{kode_surat_jalan}/download', [SpxScanController::class, 'downloadSuratJalan'])->name('suratjalan.download');


Route::resource('categories', CategoryController::class);


Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

Route::resource('roles', RoleController::class)->except(['show']);

// Rute baru untuk menampilkan halaman kategori etalase
Route::get('categories/etalase', [CategoryController::class, 'etalaseIndex'])->name('admin.categories.etalase.index');


// Profile & Notifications

Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile.edit');

Route::patch('/profile', [AdminProfileController::class, 'update'])->name('profile.update');

Route::get('/notifications/count', [AdminNotificationController::class, 'count'])->name('notifications.count');

Route::get('/notifications/registrations-count', [AdminNotificationController::class, 'registrationsCount'])->name('notifications.registrations.count');

Route::get('/notifications/pesanan-count', [AdminNotificationController::class, 'pesananCount'])->name('notifications.pesanan.count');

Route::get('/notifications/spx-scans-count', [AdminNotificationController::class, 'spxScansCount'])->name('notifications.spx-scans.count');

Route::get('/notifications/riwayat-scan-count', [AdminNotificationController::class, 'riwayatScanCount'])->name('notifications.riwayat-scan.count');

Route::get('/notifications/saldo-requests-count', [AdminNotificationController::class, 'saldoRequestsCount'])->name('notifications.saldo-requests.count');



// Management



// Rute untuk Registrasi

Route::get('registrations', [AdminRegistrationController::class, 'index'])->name('registrations.index');

Route::get('/registrations/count', [AdminRegistrationController::class, 'count'])->name('registrations.count');

Route::post('registrations/{id}/approve', [AdminRegistrationController::class, 'approve'])->name('registrations.approve');

Route::post('registrations/{id}/reject', [AdminRegistrationController::class, 'reject'])->name('registrations.reject');

Route::delete('registrations/{id}', [AdminRegistrationController::class, 'destroy'])->name('registrations.destroy');

    

// Rute Kustom untuk Customer (ditempatkan sebelum resource)

Route::post('customers/{customer}/send-setup-link', [CustomerController::class, 'sendSetupLink'])->name('customers.send-setup-link');

Route::post('customers/{customer}/add-saldo', [CustomerController::class, 'addSaldo'])->name('customers.addSaldo');



// Rute Resource untuk Customer (ditempatkan setelah rute kustom)

Route::resource('customers', CustomerController::class);

   // Rute untuk DataTables
    Route::get('products/data', [ProductController::class, 'getData'])
         ->name('products.data');

    // Rute kustom untuk restock
    Route::post('products/{product}/restock', [ProductController::class, 'restock'])
         ->name('products.restock');

    // Rute kustom untuk tandai habis
    Route::patch('products/{product}/mark-as-out-of-stock', [ProductController::class, 'markAsOutOfStock'])
         ->name('products.outOfStock');

    Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');


    Route::resource('products', ProductController::class);

    // Route untuk Halaman Edit Spesifikasi & Kategori
Route::get('/products/{product}/specifications', [ProductController::class, 'editSpecifications'])
    ->name('products.edit.specifications');

Route::put('/products/{product}/specifications', [ProductController::class, 'updateSpecifications'])
    ->name('products.update.specifications');

// Pesanan

Route::prefix('pesanan')->name('pesanan.')->group(function () {

    Route::get('/', [AdminPesananController::class, 'index'])->name('index');

    Route::get('/count', [AdminPesananController::class, 'count'])->name('count');

    Route::get('/create', [AdminPesananController::class, 'create'])->name('create');

    Route::post('/', [AdminPesananController::class, 'store'])->name('store');

    Route::get('/riwayat-scan', [AdminPesananController::class, 'riwayatScan'])->name('riwayat.scan');

    Route::get('/riwayat-scan/count', [AdminPesananController::class, 'riwayatScanCount'])->name('riwayat.scan.count');

    Route::get('/export/excel', [AdminPesananController::class, 'exportExcel'])->name('export.excel');

    Route::get('/export/pdf', [AdminPesananController::class, 'exportPdf'])->name('export.pdf');

    Route::get('/riwayat-scan/export/excel', [AdminPesananController::class, 'exportExcelRiwayat'])->name('riwayat.export.excel');

    Route::get('/riwayat-scan/export/pdf', [AdminPesananController::class, 'exportPdfRiwayat'])->name('riwayat.export.pdf');

    Route::get('/{resi}', [AdminPesananController::class, 'show'])->name('show');

    Route::get('/{resi}/edit', [AdminPesananController::class, 'edit'])->name('edit');

    Route::put('/{resi}', [AdminPesananController::class, 'update'])->name('update');

    Route::delete('/{resi}', [AdminPesananController::class, 'destroy'])->name('destroy');

    Route::put('/{resi}/update-resi', [AdminPesananController::class, 'updateResiAktual'])->name('update.resi');

    Route::put('/{resi}/update-status', [AdminPesananController::class, 'updateStatus'])->name('update.status');

    Route::get('/{resi}/cetak', [AdminPesananController::class, 'cetakResi'])->name('cetak');

    Route::get('/{resi}/cetak_thermal', [AdminPesananController::class, 'cetakResiThermal'])->name('cetak_thermal');

    Route::get('/{resi}/scan', [AdminPesananController::class, 'showScanForm'])->name('scan.form');

});



// // SPX Scans (SUDAH DIPERBAIKI)

// Route::resource('spx-scans', AdminSpxScanController::class)

//     ->parameters(['spx-scans' => 'spx_scan'])

//     ->names('spx_scans')

//     ->except(['show']); // <-- Ini akan mengabaikan method 'show' yang tidak ada





Route::prefix('spx-scans')->name('spx_scans.')->group(function() {

    Route::get('/count', [AdminSpxScanController::class, 'count'])->name('count');

    Route::get('/export/excel', [AdminSpxScanController::class, 'exportExcel'])->name('export.excel');

    Route::get('/export/pdf', [AdminSpxScanController::class, 'exportPdf'])->name('export.pdf');

    Route::patch('/{spx_scan}/update-status', [AdminSpxScanController::class, 'updateStatus'])->name('updateStatus');

    Route::get('/get-scans/{customer}', [AdminSpxScanController::class, 'getTodaysScansForCustomer'])->name('getTodaysScans');

    Route::post('/create-surat-jalan', [AdminSpxScanController::class, 'createSuratJalan'])->name('createSuratJalan');

    

    // Rute monitor dipindahkan ke sini agar lebih terorganisir

    // Path dan method sudah diperbaiki

    Route::get('/monitor', [AdminSpxScanController::class, 'showMonitorPage'])->name('monitor.index');

    Route::get('/monitor/export-pdf', [AdminSpxScanController::class, 'exportMonitorPdf'])->name('monitor.export_pdf');

    

    Route::get('/todays-data', [AdminSpxScanController::class, 'getTodaysSuratJalanData'])->name('todays_data');



});



// Kontak Management

Route::get('kontak/export/excel', [KontakController::class, 'exportExcel'])->name('kontak.export.excel');

Route::get('kontak/export/pdf', [KontakController::class, 'exportPdf'])->name('kontak.export.pdf');

Route::post('kontak/import/excel', [KontakController::class, 'importExcel'])->name('kontak.import.excel');

Route::resource('kontak', KontakController::class);



// Saldo Management

Route::prefix('saldo-requests')->name('saldo.requests.')->group(function () {

    Route::get('/', [SaldoRequestController::class, 'index'])->name('index');

    Route::post('/{transaction}/approve', [SaldoRequestController::class, 'approve'])->name('approve');

    Route::post('/{transaction}/reject', [SaldoRequestController::class, 'reject'])->name('reject');
    
    // ✅ ROUTE BARU: [GET] /admin/saldo-requests/history
    Route::get('/history', [SaldoRequestController::class, 'showHistory'])->name('history'); // Halaman riwayat semua top-up
    

});



// Activity Log

Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');



// Settings

Route::get('/settings', [AppSettingsController::class, 'index'])->name('settings.index');

Route::put('/settings/profile', [AppSettingsController::class, 'updateProfile'])->name('settings.profile.update');

Route::put('/settings/password', [AppSettingsController::class, 'updatePassword'])->name('settings.password.update');

Route::put('/settings/slider', [AppSettingsController::class, 'updateSlider'])->name('settings.slider.update');

Route::put('/settings/general', [AppSettingsController::class, 'updateGeneral'])->name('settings.general.update');



// Blog Content Management

Route::resource('posts', PostController::class);

Route::resource('categories', CategoryController::class)->except(['show']);

Route::resource('tags', TagController::class)->except(['show']);

Route::post('posts/generate-content', [PostController::class, 'generateContent'])->name('posts.generateContent');



// --- PERBAIKAN PADA PRODUCT MANAGEMENT ---

// Rute kustom didefinisikan sebelum resource route


// Fallback for uploaded post images

Route::get('/uploads/posts/{filename}', function ($filename) {

    $path = storage_path('uploads/posts/' . $filename);

    if (!File::exists($path)) {

        abort(404);

    }

    $file = File::get($path);

    $type = File::mimeType($path);

    return response($file)->header('Content-Type', $type);

});





// Rute untuk menampilkan halaman email (sudah ada)

// == RUTE UNTUK API (AJAX/FETCH) ==

Route::prefix('api')->name('api.')->group(function () {

    Route::get('/email', [EmailController::class, 'fetchEmails'])->name('email.fetch');

    Route::post('/email/send', [EmailController::class, 'send'])->name('email.send');

    Route::get('/email/{id}', [EmailController::class, 'show'])->name('email.show');

    Route::patch('/email/{id}', [EmailController::class, 'update'])->name('email.update');

    Route::delete('/email/{id}', [EmailController::class, 'destroy'])->name('email.destroy');

});





// == RUTE UNTUK API (AJAX/FETCH) ==

// Ditempatkan di sini agar Auth::user() berfungsi dengan benar.

// URL akan menjadi /admin/api/email/...

// --- RUTE UNTUK MANAJEMEN EMAIL ---

Route::prefix('email')->name('email.')->group(function () {

    // URL: /admin/email (GET) -> Menampilkan inbox

    Route::get('/', [EmailController::class, 'index'])->name('index');

    

    // URL: /admin/email/create (GET) -> Menampilkan form tulis email

    Route::get('/create', [EmailController::class, 'create'])->name('create');

    

    // URL: /admin/email/send (POST) -> Mengirim email dari form

    Route::post('/send', [EmailController::class, 'send'])->name('send');

    

    // URL: /admin/email/{messageId} (GET) -> Menampilkan detail email

    Route::get('/{messageId}', [EmailController::class, 'show'])->name('show');

    

    // URL: /admin/email/{messageId} (DELETE) -> Menghapus email

    Route::delete('/{messageId}', [EmailController::class, 'delete'])->name('delete');

});



   // --- RUTE UNTUK FITUR CHAT ---

        Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');

        Route::get('/chat/messages/{user}', [ChatController::class, 'fetchMessages'])->name('chat.fetchMessages');

        Route::post('/chat/messages/{user}', [ChatController::class, 'sendMessage'])->name('chat.sendMessage');

        

        

// ✅ DIPERBAIKI: Menggunakan resource controller untuk manajemen toko

// Ini akan secara otomatis membuat route untuk index, create, store, edit, update, destroy



// ✅ RUTE BARU YANG HILANG: Untuk memproses form pencairan dana
Route::post('/stores/{store}/payout', [App\Http\Controllers\Admin\StoreController::class, 'payout'])->name('stores.payout');


// ✅ RUTE BARU UNTUK MELIHAT SALDO DOMPET UTAMA ADMIN
Route::get('/doku-balance', [App\Http\Controllers\Admin\DokuBalanceController::class, 'index'])->name('doku_balance.index');



// ✅ DIPERBAIKI: Menggunakan prefix 'customer-to-seller' untuk menghindari konflik

Route::prefix('customer-to-seller')->name('customer-to-seller.')->group(function () {

    Route::get('/', [CustomerController::class, 'indexForStores'])->name('index');

    Route::get('/{user}/create', [CustomerController::class, 'createStore'])->name('create');

    Route::post('/{user}', [CustomerController::class, 'storeStore'])->name('store');

});
    

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');







Route::resource('users', UserController::class);
