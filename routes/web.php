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
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Customer\KoliController;
use App\Http\Controllers\Admin\ApiSettingsController;
use App\Http\Controllers\Admin\KoliController as AdminKoliController;







Route::post('/koli/cek-ongkir', [KoliController::class, 'cekOngkirMulti'])->name('koli.cekOngkirMulti');


Route::middleware(['auth', 'verified'])->prefix('customer')->group(function () {



    // ----------------------------------------------------------------------
    // Catatan: Jika Anda ingin mengganti rute lama, Anda bisa menimpa:
    // Route::get('/pesanan/create', [KoliController::class, 'create'])->name('customer.pesanan.create'); 
    // Route::post('/pesanan/store', [KoliController::class, 'store'])->name('customer.pesanan.store');
    // ----------------------------------------------------------------------
    
    // Asumsi rute lama seperti /customer/pesanan/index dan lainnya tetap menunjuk ke PesananController.
});


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

    // Jika Admin, tetap ke Admin Dashboard
    if ($user->role === 'Admin') {
        return redirect()->route('admin.dashboard');
    }
    
    return redirect()->route('customer.dashboard');

})->middleware(['auth', 'verified'])->name('dashboard');


Route::get('/admin/generate-barcode-zoom', [App\Http\Controllers\Admin\BarcodeController::class, 'generateBarcode'])->name('admin.barcode.generate');


// 1. Rute untuk PENCARIAN ALAMAT (AJAX)
// Ini akan membuat URL: /search-address
Route::get('/search-address', [CekOngkirController::class, 'searchAddress'])
     ->middleware('auth') // Pastikan user login untuk mencari
     ->name('customer.search.address');

// 2. Rute untuk SUBMIT FORM CEK ONGKIR (AJAX)
// Ini akan membuat URL: /check-cost
Route::post('/check-cost', [CekOngkirController::class, 'checkCost']) // Anda perlu membuat method 'checkCost'
     ->middleware('auth') // Pastikan user login untuk cek ongkir
     ->name('customer.check.cost');


// URL untuk tes skenario form publik (simple)
Route::get('/test/doku/simple', [TestOrderController::class, 'testSimplePayment'])
     ->name('test.doku.simple');

// URL untuk tes skenario marketplace (dengan Sub-Account)
Route::get('/test/doku/marketplace', [TestOrderController::class, 'testMarketplacePayment'])
     ->name('test.doku.marketplace');



Route::get('/', function () {

    return view('home');

})->name('home'); // <-- TAMBAHKAN INI

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
})->name('privacy.policy');

Route::get('/terms-and-conditions', function () {
    return view('terms');
})->name('terms.conditions');


require __DIR__.'/auth.php';    
    
/*
|--------------------------------------------------------------------------
| Rute Pembayaran DOKU
|--------------------------------------------------------------------------
*/
// Rute ini HANYA CONTOH untuk memicu pembayaran
// Idealnya, tombol "Bayar" di keranjang Anda akan memanggil method
// di CustomerOrderController, yang KEMUDIAN memanggil DokuPaymentController.
Route::post('/payment/create-example', [DokuPaymentController::class, 'createPayment'])->name('doku.create.example');



// Rute untuk menampilkan halaman kategori di etalase publik
Route::get('/etalase/category/{category:slug}', [EtalaseController::class, 'showCategory'])->name('public.categories.show');

// == ETALASE & MARKETPLACE ROUTES ==
Route::get('/etalase', [EtalaseController::class, 'index'])->name('etalase.index');
Route::get('/etalase/category/{category:slug}', [EtalaseController::class, 'showCategory'])->name('public.categories.show');
Route::get('/products/{product:slug}', [EtalaseController::class, 'show'])->name('products.show'); // Menggunakan EtalaseController
Route::get('/toko/{name}', [EtalaseController::class, 'profileToko'])->name('toko.profile');
Route::get('/etalase/kategori/{slug}', [EtalaseController::class, 'showCategory'])->name('etalase.category-show');

Route::get('/tracking/refresh', [TrackingController::class, 'refresh'])->name('tracking.refresh');
    
// Rute untuk menampilkan daftar pelanggan ke publik
Route::get('/pelanggan', [PublicPelangganController::class, 'index'])->name('pelanggan.public.index');

// Rute untuk menampilkan produk berdasarkan kategori
Route::get('/marketplace/category/{category:slug}', [CategoryController::class, 'show'])->name('marketplace.categories.show');

Route::get('/admin/import/wordpress', [ImportController::class, 'showForm'])->name('admin.import.wordpress.form');

Route::post('/admin/import/wordpress', [ImportController::class, 'handleImport'])->name('admin.import.wordpress.handle');

Route::get('/feed', [BlogController::class, 'generateFeed'])->name('feed');

Route::get('/api/contacts/search', [KontakController::class, 'search'])->name('api.contacts.search');

Route::get('/api/contacts/search', [PesananController::class, 'searchKontak'])->name('api.contacts.search');

Route::get('/kontak/search', [KontakController::class, 'search'])->name('api.search.kontak');

Route::get('/kontak/search', [KontakController::class, 'search'])->name('kontak.search');

Route::get('/marketplace', [CustomerMarketplaceController::class, 'index'])->name('katalog.index');



Route::prefix('customer')->name('customer.')->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/update', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
       // INI ADALAH PERBAIKANNYA: Rute untuk halaman checkout
 
    Route::get('/marketplace', [CustomerMarketplaceController::class, 'index'])->name('marketplace.index');
    
    Route::get('/pesanan/export-pdf', [PesananController::class, 'exportPdf'])->name('pesanan.export_pdf');
    
    // --- RUTE CHECKOUT YANG BENAR ---
    // 1. Menampilkan halaman checkout (GET)
    // Nama route: customer.checkout.index
 Route::get('/checkout', [CustomerCheckoutController::class, 'index'])->name('checkout.index');
    
    // 2. Memproses form checkout (POST)
    // Nama route: customer.checkout.store
 Route::post('/checkout', [CustomerCheckoutController::class, 'store'])->name('checkout.store'); // <-- DIPERBAIKI

    // 3. Menampilkan halaman invoice (GET)
    // Nama route: customer.checkout.invoice (Kita buat di luar prefix 'customer.' agar lebih pendek)
 Route::get('/invoice/{invoice}', [CustomerCheckoutController::class, 'invoice'])->name('checkout.invoice');
 
// ===> INI FIX UNTUK ERROR 404 ANDA <===
        // URL di browser: /customer/pesanan/create/multi-koli
        Route::get('/pesanan/create/multi-koli', [KoliController::class, 'create'])->name('koli.create');
        
        // Rute Proses Simpan & Cek Ongkir Multi Koli
        Route::post('/koli/store', [KoliController::class, 'store'])->name('koli.store');
        Route::post('/koli/store-single', [KoliController::class, 'storeSingle'])->name('koli.store_single');
        Route::post('/koli/cek-ongkir', [KoliController::class, 'cekOngkirMulti'])->name('koli.cek_ongkir');
        // =========================================

});


// =========================================================================

// == PUBLIC & AUTHENTICATION ROUTES

// =========================================================================

           // 🔑 ROUTE BARU: Pencarian Alamat KiriminAja (AJAX)
        // [CustomerProfileController::class, 'searchKiriminAjaAddress'] adalah method yang dipanggil JS
        Route::get('/api/kiriminaja/address-search', [App\Http\Controllers\Customer\ProfileController::class, 'searchKiriminAjaAddress'])
            ->name('kiriminaja.address_search'); // Nama route: customer.kiriminaja.address_search
            



Route::get('/pondok', [PondokController::class, 'index'])->name('pondok.index');

Route::get('/api/cari-alamat', [CustomerOrderController::class, 'searchAddressApi'])->name('api.address.search');

Route::get('/{resi}/cetak_thermal', [AdminPesananController::class, 'cetakResiThermal'])->name('cetak_thermal');



// Routes that anyone can access (e.g., login page, registration)

require __DIR__.'/web/auth.php';



Route::get('/register/success/{no_wa}', function ($no_wa) {

    return view('auth.register-success', compact('no_wa'));

})->name('register.success');

Route::get('customer/profile/setup/{token}', [CustomerProfileController::class, 'setup'])->name('customer.profile.setup');



// Public-facing pages like landing page, about us, etc.

require __DIR__.'/web/public.php';



require __DIR__.'/web/pondok.php';



Route::get('/kirimaja/set_callback', [\App\Http\Controllers\KirimAjaController::class, 'setCallback']);

Route::get('/kirimaja/cek-ongkir', [CustomerOrderController::class, 'cek_Ongkir'])->name('kirimaja.cekongkir');

Route::get('/kiriminaja/search-address', function (Request $request, KiriminAjaService $kiriminAja) {

    $query = $request->get('q');



    if (!$query) {

        return response()->json([

            'status' => false,

            'text'   => 'Query tidak boleh kosong',

            'data'   => [],

        ]);

    }



    $result = $kiriminAja->searchAddress($query);



    return response()->json($result);

});







// =========================================================================

// == CUSTOMER-SPECIFIC ROUTES PUBLIC

// =========================================================================



// TAMBAHKAN KODE INI

// Kita gunakan {resi} agar cocok dengan parameter '$order->resi' dari view Anda

// Rute untuk menampilkan halaman tracking awal
Route::get('/tracking', [TrackingController::class, 'showTrackingPage'])->name('tracking.index');

// Rute untuk menangani pencarian dari form
Route::get('/tracking/search', [TrackingController::class, 'showTrackingPage'])->name('tracking.search');

Route::get('/tracking/cetak-thermal/{resi}', [App\Http\Controllers\TrackingController::class, 'cetakThermal'])->name('tracking.cetak_thermal');

// =========================================================================

// == RUTE KHUSUS PELANGGAN

// =========================================================================

Route::middleware(['auth', RoleMiddleware::class . ':Pelanggan|Seller'])

    ->prefix('customer')->name('customer.')

    ->group(function () {
        
        
        // 1. Menampilkan Halaman Form Multi-Koli (GET)
        // URL: /customer/pesanan/multi/create
        // Nama Rute: customer.koli.create
        Route::get('/pesanan/multi/create', [KoliController::class, 'create'])->name('koli.create');

        // 2. Memproses Data Multi-Koli (POST)
        // URL: /customer/pesanan/multi/store
        // Nama Rute: customer.koli.store
        Route::post('/pesanan/multi/store', [KoliController::class, 'store'])->name('koli.store');

        // 3. CEK ONGKIR (AJAX) 
        // URL: /customer/koli/cek-ongkir
        // Nama Rute: customer.koli.cek_ongkir
        Route::post('/koli/cek-ongkir', [KoliController::class, 'cek_Ongkir'])->name('koli.cek_ongkir'); 

        // 4. STORE SINGLE KOLI (AJAX) - Rute yang digunakan untuk menyimpan per paket
        // URL: /customer/koli/store-single
        // Nama Rute: customer.koli.store_single
        Route::post('/koli/store-single', [KoliController::class, 'storeSingle'])->name('koli.store_single');

        

        require __DIR__.'/web/customer.php';
        
        // 1. Menampilkan Halaman Form (GET)
        // URL: /customer/pesanan/multi/create
        Route::get('/pesanan/multi/create', [KoliController::class, 'create'])->name('koli.create');

        // 2. Memproses Data (POST)
        // URL: /customer/pesanan/multi/store
        Route::post('/pesanan/multi/store', [KoliController::class, 'store'])->name('koli.store');

        // ====================================================

       
        Route::get('/kontak/search', [CustomerKontakController::class, 'search'])->name('kontak.search');

        // Customer Chat

        Route::prefix('chat')->name('chat.')->group(function () {

            Route::get('/', [CustomerChatController::class, 'index'])->name('index');

            Route::get('/messages', [CustomerChatController::class, 'fetchMessages'])->name('fetchMessages');

            Route::post('/messages', [CustomerChatController::class, 'sendMessage'])->name('sendMessage');
            
 

        });

    });

    





// =========================================================================

// == ADMIN-SPECIFIC ROUTES

// =========================================================================





Route::prefix('admin')->name('admin.')->group(function () {
    
    Route::post('/categories/ajax-store', [App\Http\Controllers\Admin\CategoryController::class, 'storeAjax'])
    ->name('categories.storeAjax'); // HAPUS 'admin.' di sini

    Route::delete('/categories/ajax-delete/{id}', [App\Http\Controllers\Admin\CategoryController::class, 'destroyAjax'])
    ->name('categories.destroyAjax'); // HAPUS 'admin.' di sini

    // Fitur Multi Koli Admin (YANG BARU ANDA BUAT)
    Route::get('/pesanan/buat-multi', [AdminKoliController::class, 'create'])->name('pesanan.create_multi');
    Route::post('/pesanan/store-multi', [AdminKoliController::class, 'store'])->name('koli.store');
    Route::post('/pesanan/store-single', [AdminKoliController::class, 'storeSingle'])->name('koli.store_single');
    Route::post('/cek-ongkir', [AdminKoliController::class, 'cek_Ongkir'])->name('koli.cek_ongkir');
    


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
        
        Route::resource('customers/data/pengguna', DataPenggunaController::class)
    ->names('customers.data.pengguna');
    
// Halaman Pengaturan API All-in-One
    Route::get('/settings/api', [ApiSettingsController::class, 'index'])->name('settings.api.index');
    Route::put('/settings/api', [ApiSettingsController::class, 'update'])->name('settings.api.update');
    Route::post('/settings/api', [ApiSettingsController::class, 'toggle'])->name('settings.api.toggle');


Route::resource('stores', AdminMarketplaceController::class)->names('stores');
       
          // TAMBAHKAN ROUTE INI UNTUK HALAMAN PENGATURAN
          
  Route::post('/users/{user}/toggle-freeze', [App\Http\Controllers\Admin\UserController::class, 'toggleFreeze'])->name('admin.users.toggle-freeze');        

// Route untuk edit info
Route::get('/setting-info-pesanan', [AdminController::class, 'editInfoPesanan'])->name('info.edit');
Route::post('/setting-info-pesanan', [AdminController::class, 'updateInfoPesanan'])->name('info.update');

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