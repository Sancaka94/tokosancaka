<?php


use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\Admin\ChatController as AdminChatController;
use App\Http\Controllers\Admin\EmailController;
use App\Http\Controllers\Admin\SpxScanController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CourierController;
use App\Http\Controllers\CustomerChatController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\DanaController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileSetupController;
use App\Http\Controllers\Customer\ProfileController as CustomerProfileController;
use App\Http\Controllers\Admin\ImapController; // Pastikan path controller ini benar
use App\Http\Controllers\Admin\LaporanKeuanganController;
use App\Http\Controllers\Admin\CoaController; // Pastikan use statement ini ada
use App\Http\Controllers\PondokController;
use App\Http\Controllers\WilayahController;
use App\Http\Controllers\SellerRegisterController;
use App\Http\Controllers\CustomerOrderController;
use App\Services\KiriminAjaService;
use Illuminate\Http\Request;
use App\Http\Controllers\CekOngkirController;
use App\Http\Controllers\TrackingController; 
use App\Http\Controllers\Admin\PesananController as AdminPesananController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\KodePosController;
use App\Http\Controllers\Admin\PesananController;
use App\Http\Controllers\Api\KontakController;
use App\Http\Controllers\Admin\WalletController;
use App\Http\Controllers\Customer\KontakController as CustomerKontakController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\PublicPelangganController;
use App\Http\Controllers\Admin\MarketplaceController as AdminMarketplaceController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Customer\MarketplaceController as CustomerMarketplaceController;
use App\Http\Controllers\Customer\CartController; // Pastikan path ini benar
use App\Http\Controllers\Customer\CheckoutController as CustomerCheckoutController;
use App\Http\Controllers\Customer\CategoryController; // Tambahkan ini di atas
use App\Http\Controllers\EtalaseController;
use App\Http\Controllers\ProductController; // Pastikan controller ini ada
use App\Http\Controllers\Admin\CategoryAttributeController; // Import controller baru
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\ChatController;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Admin\MarketplaceController; // <-- TAMBAHKAN BARIS INI
use App\Http\Controllers\DokuPaymentController;
use App\Http\Controllers\TestOrderController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\AdminSaldoTransferController;
use App\Http\Controllers\Customer\KontakController as CustomerOldKontakController;
use App\Http\Controllers\NotifikasiCustomerController;
use App\Http\Controllers\Admin\BarcodeController;
// Impor Controller Login yang benar
use App\Http\Controllers\Auth\Customer\CustomerLoginController; 
// Anda perlu mengimpor RegisteredUserController jika ada, saya asumsikan ia ada di sini.
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Admin\Customers\DataPenggunaController; // HARUS ADA DAN BENAR
use App\Http\Controllers\Admin\CustomerController; // PENTING: Tambahkan ini
use App\Http\Controllers\PublicScanController;


// 1. Halaman Depan (Home)
Route::get('/', function () {
    // Pastikan Anda punya file 'resources/views/home.blade.php'
    // Jika belum ada, bisa ganti 'home' menjadi 'welcome' sementara
    return view('home'); 
})->name('home');

// Route untuk Halaman Katalog / Marketplace
Route::get('/marketplace/katalog', [CustomerMarketplaceController::class, 'index'])
    ->name('katalog.index'); // <--- Nama ini yang dicari oleh sidebar Anda
    
Route::get('/katalog/kategori/{category:slug}', [CustomerMarketplaceController::class, 'showCategory'])
    ->name('marketplace.categories.show');
    
Route::get('/{resi}/cetak_thermal', [AdminPesananController::class, 'cetakResiThermal'])
    ->name('pesanan.cetak_thermal');

// 2. Privacy Policy
Route::get('/privacy-policy', function () {
    // Pastikan Anda punya file 'resources/views/privacy-policy.blade.php'
    // Jika tidak punya, buat file kosong dulu agar tidak error
    return view('privacy-policy'); 
})->name('privacy.policy');

// 3. Terms & Conditions
Route::get('/terms-and-conditions', function () {
    // Pastikan Anda punya file 'resources/views/terms.blade.php'
    return view('terms'); 
})->name('terms.conditions');

// 1. Dashboard Pelanggan (Default View Breeze)
Route::get('/customer/dashboard', function () {
    return view('dashboard'); // Menggunakan view bawaan Breeze (resources/views/dashboard.blade.php)
})->middleware(['auth', 'verified', RoleMiddleware::class . ':Pelanggan'])->name('customer.dashboard');

// 2. Dashboard Admin
Route::get('/admin/dashboard', function () {
    return view('admin.dashboard'); // Pastikan Anda buat file: resources/views/admin/dashboard.blade.php
})->middleware(['auth', 'verified', RoleMiddleware::class . ':Admin'])->name('admin.dashboard');

// 3. Dashboard Seller
Route::get('/seller/dashboard', function () {
    return view('seller.dashboard'); // Pastikan Anda buat file: resources/views/seller/dashboard.blade.php
})->middleware(['auth', 'verified', RoleMiddleware::class . ':Seller'])->name('seller.dashboard');

// 4. Rute Fallback '/dashboard' (PENTING)
// Jika ada user yang mengetik manual "/dashboard" di browser,
// kita lempar mereka ke dashboard yang benar sesuai role-nya.
Route::get('/dashboard', function () {
    $user = auth()->user();
    if ($user->role === 'Admin') {
        return redirect()->route('admin.dashboard');
    } elseif ($user->role === 'Seller') {
        return redirect()->route('seller.dashboard');
    } else {
        return redirect()->route('customer.dashboard');
    }
})->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/admin/api/contacts/search', [App\Http\Controllers\Api\KontakController::class, 'search'])
        ->name('api.contacts.search');
        
    // Route untuk Halaman Keranjang (Cart)
    Route::get('/customer/cart', [CartController::class, 'index'])
        ->name('customer.cart.index'); // <--- Ini nama route yang dicari sistem
        
    Route::get('/api/contacts/search', [PesananController::class, 'searchKontak'])
        ->name('api.contacts.search');
        
    Route::get('/admin/api/contacts/search', [App\Http\Controllers\Admin\KontakController::class, 'search'])
        ->name('api.contacts.search');
        
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});


require __DIR__.'/auth.php';

// Route Utama (Tracking Index)
Route::get('/tracking', [TrackingController::class, 'showTrackingPage'])->name('tracking.index');

// Route Pencarian (Search Form Action)
Route::get('/tracking/search', [TrackingController::class, 'showTrackingPage'])->name('tracking.search');

// Route Cetak Thermal
Route::get('/tracking/cetak/{resi}', [TrackingController::class, 'cetakThermal'])->name('cetak_thermal');

// Route Refresh Timeline
Route::get('/tracking/refresh', [TrackingController::class, 'refreshTimeline'])->name('tracking.refresh');

// ====================================================
// RUTE PESANAN PUBLIK (CUSTOMER ORDER)
// ====================================================

// 1. Halaman Form Pemesanan (Gantikan rute dummy sebelumnya)
Route::get('/buat-pesanan', [CustomerOrderController::class, 'create'])
    ->name('pesanan.public.create');

// 2. Proses Simpan Pesanan
Route::post('/buat-pesanan', [CustomerOrderController::class, 'store'])
    ->name('pesanan.public.store');

// 3. Halaman Sukses (Setelah Order/Bayar)
Route::get('/pesanan-sukses', [CustomerOrderController::class, 'success'])
    ->name('pesanan.public.success');

// ====================================================
// API PENDUKUNG (CEK ONGKIR & ALAMAT)
// ====================================================

/*
|--------------------------------------------------------------------------
| API Internal (AJAX Helper untuk Form)
|--------------------------------------------------------------------------
| Route ini menangani request AJAX dari JavaScript di halaman buat pesanan
*/

Route::prefix('ajax')->group(function () {
    
    // API: Pencarian Alamat (Kecamatan/Kelurahan) via KiriminAja/Database
    // Digunakan di JS: route('api.address.search')
    Route::get('/address/search', [CustomerOrderController::class, 'searchAddressApi'])
        ->name('api.address.search');

    // API: Pencarian Kontak (Pengirim/Penerima) auto-complete
    // Digunakan di JS: route('api.search.kontak')
    Route::get('/contact/search', [CustomerOrderController::class, 'searchKontak'])
        ->name('api.search.kontak');

    // API: Cek Ongkir Real-time
    // Digunakan di JS: route('kirimaja.cekongkir')
    Route::get('/shipping/check', [CustomerOrderController::class, 'cek_Ongkir'])
        ->name('kirimaja.cekongkir');
        
});

// 2. Rute untuk "Input Resi SPX Express"
//Route::get('/scan-spx', function () {
    //return "Halaman Scan SPX (Sedang dalam pengembangan)";
//})->name('scan.spx.show');

// ====================================================
// RUTE FITUR SCAN SPX (PUBLIK) - VERSI LENGKAP
// ====================================================

// 1. Halaman Utama Scan
Route::get('/scan-spx', [PublicScanController::class, 'show'])->name('scan.spx.show');

// 2. Proses Simpan Scan (AJAX)
Route::post('/scan-spx/handle', [PublicScanController::class, 'handleScan'])->name('scan.spx.handle');

// 3. Cari Kontak (Autocomplete) - Di Javascript dipanggil 'api.search.kontak'
Route::get('/scan-spx/search-kontak', [PublicScanController::class, 'searchKontak'])->name('api.search.kontak');

// 4. Daftar Kontak Baru (Modal) - Di Javascript dipanggil 'kontak.register'
Route::post('/scan-spx/register-kontak', [PublicScanController::class, 'registerKontak'])->name('kontak.register');

// 5. Buat Surat Jalan - Di Javascript dipanggil 'scan.spx.suratjalan.create'
Route::post('/scan-spx/create-surat-jalan', [PublicScanController::class, 'createSuratJalan'])->name('scan.spx.suratjalan.create');

// 6. Cetak PDF Surat Jalan
Route::get('/scan-spx/print-surat-jalan', [PublicScanController::class, 'generateSuratJalan'])->name('surat.jalan.pdf');

Route::prefix('admin')->name('admin.')->group(function () {
    

    // ================================================================
    // == PERBAIKAN: RUTE CUSTOMERS (MENGGANTIKAN /ADMIN/CUSTOMERS/DATA) ==
    // ================================================================
    // PENTING: Gunakan 'customers.index' untuk URL /customers
    Route::get('customers/data', [CustomerController::class, 'index'])->name('customers.data'); // Tambahkan rute ini untuk URL /data
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index'); // Rute utama /customers
// <<< PERBAIKAN DI SINI >>>
// Sesuaikan nama rute agar cocok dengan pemanggilan di Blade 'admin.pengguna.exportExcel'
Route::get('/export-excel', [PenggunaController::class, 'exportExcel'])->name('pengguna.exportExcel');
Route::post('/import-excel', [PenggunaController::class, 'importExcel'])->name('pengguna.importExcel');
// <<< END PERBAIKAN >>>


    Route::get('/post/{post:slug}', [PostController::class, 'show'])->name('posts.post-detail');

    Route::get('/wilayah', [WilayahController::class, 'index'])->name('wilayah.index');

    Route::get('/api/contacts/search', [AdminKontakController::class, 'search'])->name('api.contacts.search');

    Route::get('/wilayah/province/{province}/regencies', [WilayahController::class, 'getKabupaten'])->name('wilayah.kabupaten');

    Route::get('/wilayah/regency/{regency}/districts', [WilayahController::class, 'getKecamatan'])->name('wilayah.kecamatan');

    Route::get('/wilayah/district/{district}/villages', [WilayahController::class, 'getDesa'])->name('wilayah.desa');

    Route::resource('pelanggan', PelangganController::class);
    
    

    
    // --- [BARU] RUTE UNTUK MANAJEMEN SLIDER ---
    Route::get('/sliders', [SliderController::class, 'index'])->name('sliders.index');
    Route::post('/sliders', [SliderController::class, 'store'])->name('sliders.store');
    Route::delete('/sliders/{slide}', [SliderController::class, 'destroy'])->name('sliders.destroy');
    // ------------------------------------------

     // Rute spesifik untuk fungsionalitas Import & Export
    Route::prefix('pelanggan')->name('pelanggan.')->group(function () {
        Route::post('/import-excel', [PelangganController::class, 'importExcel'])->name('import.excel');
        Route::get('/export-excel', [PelangganController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export-pdf', [PelangganController::class, 'exportPdf'])->name('export.pdf');

    });
    

   // 1. (GET) Menampilkan halaman daftar produk
        Route::get('/marketplace', [MarketplaceController::class, 'index'])
             ->name('marketplace.index'); // Akan menjadi 'admin.marketplace.index'

        // 2. (POST) Menyimpan produk baru
        Route::post('/marketplace', [MarketplaceController::class, 'store'])
             ->name('marketplace.store'); // Akan menjadi 'admin.marketplace.store'

        // 3. (GET) Mengambil data 1 produk untuk modal edit
        Route::get('/marketplace/{product}', [MarketplaceController::class, 'show'])
             ->name('marketplace.show'); // Akan menjadi 'admin.marketplace.show'

        // 4. (PUT) Memperbarui produk
        Route::put('/marketplace/{product}', [MarketplaceController::class, 'update'])
             ->name('marketplace.update'); // Akan menjadi 'admin.marketplace.update'

        // 5. (DELETE) Menghapus produk
        Route::delete('/marketplace/{product}', [MarketplaceController::class, 'destroy'])
             ->name('marketplace.destroy'); // Akan menjadi 'admin.marketplace.destroy'

    // Rute untuk Manajemen Banner (CRUD)
    Route::resource('banners', BannerController::class);

// ================================================================
    // == PERBAIKAN: STRUKTUR RUTE UNTUK ATRIBUT KATEGORI ==
    // ================================================================
    // Grup ini memastikan semua URL diawali dengan '/category-attributes'
    // dan semua nama rute diawali dengan 'category-attributes.'
    Route::prefix('category-attributes')->name('category-attributes.')->group(function () {
        
        // Menampilkan halaman utama (daftar atribut)
        // URL: /admin/category-attributes
        // Nama Rute: admin.category-attributes.index
        Route::get('/', [CategoryAttributeController::class, 'index'])->name('index');
        
        // Menyimpan atribut baru untuk kategori tertentu
        // URL: /admin/category-attributes/{category}
        // Nama Rute: admin.category-attributes.store
        Route::post('/{category}', [CategoryAttributeController::class, 'store'])->name('store');
        
        // Menampilkan form untuk mengedit atribut
        // URL: /admin/category-attributes/{attribute}/edit
        // Nama Rute: admin.category-attributes.edit
        Route::get('/{attribute}/edit', [CategoryAttributeController::class, 'edit'])->name('edit');
        
        // Menyimpan perubahan pada atribut yang diedit
        // URL: /admin/category-attributes/{attribute}
        // Nama Rute: admin.category-attributes.update
        Route::put('/{attribute}', [CategoryAttributeController::class, 'update'])->name('update');
        
        // Menghapus atribut
        // URL: /admin/category-attributes/{attribute}
        // Nama Rute: admin.category-attributes.destroy
        Route::delete('/{attribute}', [CategoryAttributeController::class, 'destroy'])->name('destroy');
    });

        Route::get('categories/{category}/attributes', [ProductController::class, 'getAttributes'])->name('categories.attributes');

   

});



Route::middleware(['auth', RoleMiddleware::class . ':Admin'])

    ->prefix('admin')->name('admin.')

    ->group(function () {

        // All general admin routes should go in this file

        require __DIR__.'/web/admin.php';

       
          // TAMBAHKAN ROUTE INI UNTUK HALAMAN PENGATURAN
          
          Route::resource('stores', AdminMarketplaceController::class)->names('stores');
          
  Route::post('/users/{user}/toggle-freeze', [App\Http\Controllers\Admin\UserController::class, 'toggleFreeze'])->name('admin.users.toggle-freeze');        
    
    Route::resource('customers/pengguna', DataPenggunaController::class)->names('customers.pengguna');
    Route::resource('customers/data/pengguna', DataPenggunaController::class)->names('customers.data.pengguna');
    Route::get('customers/data/pengguna/export/{type}', [DataPenggunaController::class, 'export'])->name('customers.pengguna.export');
    
    // TAMBAHKAN ROUTE INI JIKA BELUM ADA
        Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
        
        // Anda mungkin juga memerlukan route lain untuk wallet
        Route::post('/wallet/topup', [WalletController::class, 'topup'])->name('wallet.topup');
        Route::get('/wallet/search', [WalletController::class, 'search'])->name('wallet.search');

    Route::get('/chat/start', [ChatController::class, 'start'])->name('chat.start');



    // Route untuk halaman profil. Ganti view() dengan controller jika perlu.
    Route::get('/user/profile', function () {
        return view('profile.show'); // Arahkan ke view profil Anda
    })->name('profile.show');

     // TAMBAHKAN ROUTE INI
    Route::get('/wallet/search', [WalletController::class, 'search'])->name('wallet.search');

    Route::view('/setting', 'admin.setting')->name('settings');

     // --- MANAJEMEN WILAYAH TERINTEGRASI ---

        Route::prefix('wilayah')->name('wilayah.')->group(function() {

            Route::get('/', [WilayahController::class, 'index'])->name('index');

            

            // API untuk data dinamis

            Route::get('/api/provinces', [WilayahController::class, 'getProvinces'])->name('api.provinces');

            Route::get('/api/regencies/{province}', [WilayahController::class, 'getRegencies'])->name('api.regencies');

            Route::get('/api/districts/{regency}', [WilayahController::class, 'getDistricts'])->name('api.districts');

            Route::get('/api/villages/{district}', [WilayahController::class, 'getVillages'])->name('api.villages');



            // Rute untuk CRUD

            Route::post('/', [WilayahController::class, 'store'])->name('store');

            Route::put('/{id}', [WilayahController::class, 'update'])->name('update');

            Route::delete('/{id}', [WilayahController::class, 'destroy'])->name('destroy');

 

        });

        

        

// =========================================================================

// == FITUR PENCARIAN KODE POS

// =========================================================================



        

        // ✅ RUTE KODE POS YANG SUDAH DIPERBAIKI

        Route::get('/kode-pos', [KodePosController::class, 'index'])->name('kodepos.index');

        Route::post('/kode-pos/import', [KodePosController::class, 'import'])->name('kodepos.import');





        // Example: Route for admin dashboard (likely in admin.php)

        // Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');



        // Courier Management (Resource route + custom routes)

        Route::get('/couriers/search', [CourierController::class, 'search'])->name('api.couriers.search');

        Route::get('couriers/{id}/scan', [CourierController::class, 'showScanPage'])->name('couriers.scan');

        Route::get('couriers/{id}/track', [CourierController::class, 'trackLocation'])->name('couriers.track');

        Route::get('couriers/{id}/print', [CourierController::class, 'printDeliveryOrder'])->name('couriers.print');

        Route::resource('couriers', CourierController::class); // This handles index, create, store, show, edit, update, destroy



        // SPX Scan Management

        Route::resource('spx-scans', SpxScanController::class)->names('spx_scans');

        Route::get('/surat-jalan/monitor', [SpxScanController::class, 'showMonitorPage'])->name('suratjalan.monitor.index');

        Route::get('/surat-jalan/monitor/export-pdf', [SpxScanController::class, 'exportMonitorPdf'])->name('suratjalan.monitor.export_pdf');

        Route::get('/surat-jalan/{kode_surat_jalan}/download', [SpxScanController::class, 'downloadSuratJalan'])->name('suratjalan.download');

        Route::get('/spx-scans/create', [SpxScanController::class, 'create']) // Sesuaikan middleware jika perlu

        ->name('spx_scans.create');;



        // Admin Chat

        Route::prefix('chat')->name('chat.')->group(function () {

            Route::get('/', [AdminChatController::class, 'index'])->name('index');

            Route::get('/search-users', [AdminChatController::class, 'searchUsers'])->name('searchUsers');

            Route::get('/messages/{user}', [AdminChatController::class, 'fetchMessages'])->name('messages');

            Route::post('/messages/{user}', [AdminChatController::class, 'sendMessage'])->name('send');

        });



        // ======================================================
        // == ✅ INCLUDE ADMIN ORDER ROUTES HERE ==
        // ======================================================
        // This will define admin.orders.* routes like admin.orders.index, admin.orders.data, etc.
        require __DIR__.'/admin/orders.php';
        // ======================================================


        

        

    // Route untuk halaman daftar email (inbox)

    Route::get('/imap', [EmailController::class, 'index'])->name('imap.index');

    

    // Route untuk menampilkan detail satu email

    Route::get('/imap/{id}', [EmailController::class, 'show'])->name('imap.show');



    // Route untuk menghapus email

    Route::delete('/imap/{id}', [EmailController::class, 'destroy'])->name('imap.destroy');

    

     // RUTE UNTUK LAPORAN KEUANGAN

        Route::prefix('laporan')->name('laporan.')->group(function () {

            Route::get('pemasukan', [LaporanKeuanganController::class, 'pemasukan'])->name('pemasukan');

            Route::post('pemasukan', [LaporanKeuanganController::class, 'storePemasukan'])->name('pemasukan.store');

            

            Route::get('pengeluaran', [LaporanKeuanganController::class, 'pengeluaran'])->name('pengeluaran');

            Route::post('pengeluaran', [LaporanKeuanganController::class, 'storePengeluaran'])->name('pengeluaran.store');



            Route::get('laba-rugi', [LaporanKeuanganController::class, 'labaRugi'])->name('labaRugi');

            Route::get('neraca-saldo', [LaporanKeuanganController::class, 'neracaSaldo'])->name('neracaSaldo');

            Route::get('neraca', [LaporanKeuanganController::class, 'neraca'])->name('neraca');

        });



        // RUTE UNTUK MANAJEMEN KODE AKUN (COA)

        Route::resource('coa', CoaController::class)->except(['show']);

        Route::get('coa/export/excel', [CoaController::class, 'exportExcel'])->name('coa.export.excel');

        Route::get('coa/export/pdf', [CoaController::class, 'exportPdf'])->name('coa.export.pdf');

        Route::get('coa/import', [CoaController::class, 'showImportForm'])->name('coa.import.form');

        Route::post('coa/import', [CoaController::class, 'importExcel'])->name('coa.import.excel');

        Route::get('coa/import/template', [CoaController::class, 'downloadTemplate'])->name('coa.import.template');

        

   

    

});

    



// =========================================================================

// == PAYMENT & CHECKOUT ROUTES (AUTH-PROTECTED)

// =========================================================================



Route::middleware('auth')->group(function () {
    
        // Rute untuk mengambil data notifikasi (via JavaScript/AJAX)
    // Ini yang akan kita pakai di layout Anda
    Route::get('/customer/notifications/unread', [NotifikasiCustomerController::class, 'getUnread'])
         ->name('customer.notifications.unread');

    // Rute untuk halaman "Semua Notifikasi" (jika Anda membuatnya)
    Route::get('/customer/notifications', [NotifikasiCustomerController::class, 'index'])
         ->name('customer.notifications.index');

    // Rute untuk menandai 1 notifikasi sebagai "dibaca"
    Route::post('/customer/notifications/{id}/read', [NotifikasiCustomerController::class, 'markAsRead'])
         ->name('customer.notifications.markAsRead');

    // Rute untuk menandai SEMUA notifikasi sebagai "dibaca"
    Route::post('/customer/notifications/read-all', [NotifikasiCustomerController::class, 'markAllAsRead'])
         ->name('customer.notifications.markAllAsRead');

    // Checkout Process

    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');

    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/checkout/{invoice}', [CheckoutController::class, 'invoice'])->name('checkout.invoice');

    

    // Generic Payment Creation (Example)

    Route::get('/bayar/{orderId}', [PaymentController::class, 'createPayment'])->name('payment.create');



    // DANA Payment Gateway

    Route::prefix('dana')->name('dana.')->group(function () {

       

        Route::get('/create-payment/{order}', [DanaController::class, 'createPayment'])->name('payment.create');



        Route::get('/payment-finish', [DanaController::class, 'handleFinishRedirect'])->name('payment.finish');

    });



    // Other Payment Gateway (Redirect page for user)

    Route::get('/payment/finish', [PaymentController::class, 'finishPage'])->name('payment.finish');
    
    
    // Grup untuk Buku Alamat Customer
    Route::prefix('customer/kontak')->name('customer.kontak.')->group(function () {
        Route::get('/', [App\Http\Controllers\CustomerKontakController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\CustomerKontakController::class, 'store'])->name('store');
        Route::get('/{kontak}/edit', [App\Http\Controllers\CustomerKontakController::class, 'edit'])->name('edit');
        Route::put('/{kontak}', [App\Http\Controllers\CustomerKontakController::class, 'update'])->name('update');
        Route::delete('/{kontak}', [App\Http\Controllers\CustomerKontakController::class, 'destroy'])->name('destroy');
    });



});





// =========================================================================

// == PAYMENT WEBHOOKS / CALLBACKS (NO AUTH MIDDLEWARE)

// =========================================================================

// These routes are hit by external servers, so they must not be in a group

// that requires a user to be logged in. They should also be excluded from

// CSRF protection.



// DANA Notification Webhook

Route::post('/dana/notification', [DanaController::class, 'handleNotification'])->name('dana.payment.notify');

Route::post('/callback/tripay', [CheckoutController::class, 'TripayCallback'])->name('payment.callback.tripay');



// Other Payment Gateway Webhooks

Route::prefix('payment')->group(function () {

    Route::post('/callback', [PaymentController::class, 'handleCallback'])->name('payment.callback');

    Route::post('/callback/refund', [PaymentController::class, 'handleRefundCallback'])->name('payment.callback.refund');

    Route::post('/callback/code', [PaymentController::class, 'handleCodeCallback'])->name('payment.callback.code');

});





// =========================================================================

// == RUTE KHUSUS PELANGGAN & SELLER

// =========================================================================

Route::middleware(['auth', RoleMiddleware::class . ':Pelanggan|Seller'])

    ->prefix('customer')->name('customer.')

    ->group(function () {

        

        require __DIR__.'/web/customer.php';

        

        //Route::get('/ongkir/address/search', [CekOngkirController::class, 'searchAddress'])->name('ongkir.address.search');



        Route::get('/kontak/search', [KontakController::class, 'search'])->name('kontak.search'); 

        // Chat Pelanggan

        Route::prefix('chat')->name('chat.')->group(function () {

            Route::get('/', [CustomerChatController::class, 'index'])->name('index');

            Route::get('/messages', [CustomerChatController::class, 'fetchMessages'])->name('fetchMessages');

            Route::post('/messages', [CustomerChatController::class, 'sendMessage'])->name('sendMessage');

        });



        // Form pendaftaran seller (diakses oleh pelanggan yang sudah login)

        Route::get('/seller/register', [SellerRegisterController::class, 'create'])->name('seller.register.form');

        Route::post('/seller/register', [SellerRegisterController::class, 'store'])->name('seller.register.submit');

    });



// =========================================================================

// == RUTE KHUSUS SELLER (DAN ADMIN)

// =========================================================================

Route::middleware(['auth', RoleMiddleware::class . ':Seller|Admin'])

    ->prefix('seller')->name('seller.')

    ->group(function () {

        require __DIR__.'/web/seller.php';

    });

Route::get('/kontak/search', [KontakController::class, 'search'])->name('api.search.kontak');





Route::get('/controllers-list', function () {
    $files = File::allFiles(app_path('Http/Controllers'));

    $controllers = collect($files)->map(function ($file) {
        // Ambil path relatif dari folder Controllers
        $relativePath = str_replace(app_path('Http/Controllers') . '/', '', $file->getPathname());

        // Ubah menjadi namespace Laravel (App\Http\Controllers\...)
        $class = 'App\\Http\\Controllers\\' . str_replace(
            ['/', '.php'],
            ['\\', ''],
            $relativePath
        );

        return $class;
    });

    return view('controllers-list', compact('controllers'));
});

     
     
     // ==========================================================
    // ROUTE UNTUK REGISTRASI SELLER (YANG BARU)
    // ==========================================================

    // 1. Rute untuk MENAMPILKAN form (yang Anda lihat)
    Route::get('customer/seller/register', [SellerRegisterController::class, 'create'])
         ->name('customer.seller.register.form'); // Ganti nama route ini jika view Anda memanggil nama lain

    // 2. Rute untuk MENYIMPAN form (submit)
    Route::post('customer/seller/register', [SellerRegisterController::class, 'store'])
         ->name('seller.register.submit'); // View Anda memanggil nama ini

    // 3. Rute untuk PENCARIAN ALAMAT (AJAX) <-- INI YANG HILANG
    Route::get('seller/address/search', [SellerRegisterController::class, 'searchAddressKiriminAja'])
         ->name('seller.address.search');

    // 4. Rute untuk GEOCODE ALAMAT (AJAX) <-- INI JUGA HILANG
    Route::post('seller/address/geocode', [SellerRegisterController::class, 'geocodeAddress'])
         ->name('seller.address.geocode');
         
    // ==========================================================
    
    
  
