<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Http\Middleware\RoleMiddleware;

// --- DAFTAR CONTROLLER ---
use App\Http\Controllers\Admin\ChatController as AdminChatController;
use App\Http\Controllers\Admin\EmailController;
use App\Http\Controllers\Admin\SpxScanController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CourierController;
use App\Http\Controllers\CustomerChatController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\DanaController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Customer\ProfileController as CustomerProfileController;
use App\Http\Controllers\Admin\LaporanKeuanganController;
use App\Http\Controllers\Admin\CoaController;
use App\Http\Controllers\PondokController;
use App\Http\Controllers\WilayahController;
use App\Http\Controllers\SellerRegisterController;
use App\Http\Controllers\CustomerOrderController;
use App\Services\KiriminAjaService;
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
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CheckoutController as CustomerCheckoutController;
use App\Http\Controllers\Customer\CategoryController;
use App\Http\Controllers\EtalaseController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Admin\CategoryAttributeController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\MarketplaceController;
use App\Http\Controllers\DokuPaymentController;
use App\Http\Controllers\TestOrderController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\NotifikasiCustomerController;
use App\Http\Controllers\Admin\BarcodeController;
use App\Http\Controllers\Auth\Customer\CustomerLoginController; 
use App\Http\Controllers\Admin\Customers\DataPenggunaController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Customer\KoliController;
use App\Http\Controllers\Admin\ApiSettingsController;
use App\Http\Controllers\Admin\KoliController as AdminKoliController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\SellerReviewController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\PpobController;
use App\Http\Controllers\DigiflazzWebhookController;
use App\Http\Controllers\PpobProductController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\Customer\PpobCheckoutController;
use App\Http\Controllers\Customer\PpobHistoryController;
use App\Http\Controllers\Customer\AgentProductController;
use App\Http\Controllers\Customer\AgentRegistrationController;
use App\Http\Controllers\Customer\TopUpController;
use App\Http\Controllers\Customer\AgentTransactionController;
use App\Http\Controllers\Admin\AdminPpobController;
use App\Http\Controllers\Admin\AdminLogController;

/*
|--------------------------------------------------------------------------
| 1. PUBLIC & AUTH ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/', function () { return view('home'); })->name('home');
Route::get('/privacy-policy', function () { return view('privacy-policy'); })->name('privacy.policy');
Route::get('/terms-and-conditions', function () { return view('terms'); })->name('terms.conditions');

require __DIR__.'/auth.php';
require __DIR__.'/web/auth.php';
require __DIR__.'/web/public.php';
require __DIR__.'/web/pondok.php';

// Route Login & Setup
Route::get('/register/success/{no_wa}', function ($no_wa) {
    return view('auth.register-success', compact('no_wa'));
})->name('register.success');
Route::get('customer/profile/setup/{token}', [CustomerProfileController::class, 'setup'])->name('customer.profile.setup');

// Dashboard Redirects
Route::get('/dashboard', function () {
    $user = auth()->user();
    if ($user->role === 'Admin') {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('customer.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/customer/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', RoleMiddleware::class . ':Pelanggan'])->name('customer.dashboard');

Route::get('/admin/dashboard', function () {
    return view('admin.dashboard');
})->middleware(['auth', 'verified', RoleMiddleware::class . ':Admin'])->name('admin.dashboard');

Route::get('/seller/dashboard', function () {
    return view('seller.dashboard');
})->middleware(['auth', 'verified', RoleMiddleware::class . ':Seller'])->name('seller.dashboard');


/*
|--------------------------------------------------------------------------
| 2. CUSTOMER ROUTES (FULL RESTORED)
|--------------------------------------------------------------------------
*/

// GROUP 1: Route Customer dengan Prefix 'customer' TAPI TANPA 'name' prefix
// Ini untuk mengatasi error "Route [katalog.index] not defined"
Route::middleware(['auth', RoleMiddleware::class . ':Pelanggan|Seller'])->prefix('customer')->group(function () {
    
    // ===> INI FIX UNTUK KATALOG INDEX <===
    // View memanggil 'katalog.index', bukan 'customer.katalog.index'
    Route::get('/marketplace', [CustomerMarketplaceController::class, 'index'])->name('katalog.index');

    // Load file customer.php jika ada (opsional, tapi saya tulis route manualnya di bawah biar aman)
    require __DIR__.'/web/customer.php'; 
});

// GROUP 2: Route Customer dengan Prefix 'customer' DAN Name 'customer.'
Route::middleware(['auth', RoleMiddleware::class . ':Pelanggan|Seller'])->prefix('customer')->name('customer.')->group(function () {
    
    // Pesanan Multi Koli
    Route::get('/pesanan/multi/create', [KoliController::class, 'create'])->name('koli.create');
    Route::post('/pesanan/multi/store', [KoliController::class, 'store'])->name('koli.store');
    Route::post('/koli/cek-ongkir', [KoliController::class, 'cek_Ongkir'])->name('koli.cek_ongkir');
    Route::post('/koli/store-single', [KoliController::class, 'storeSingle'])->name('koli.store_single');

    // Marketplace (Versi Customer Name)
    Route::get('/marketplace/index', [CustomerMarketplaceController::class, 'index'])->name('marketplace.index');
    
    // Chat
    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/', [CustomerChatController::class, 'index'])->name('index');
        Route::get('/messages', [CustomerChatController::class, 'fetchMessages'])->name('fetchMessages');
        Route::post('/messages', [CustomerChatController::class, 'sendMessage'])->name('sendMessage');
    });

    // Kontak Customer (FIX Route [customer.kontak.index])
    Route::resource('kontak', CustomerKontakController::class);
    Route::get('/kontak/search-api', [CustomerKontakController::class, 'search'])->name('kontak.search'); 
    
    // Seller Register
    Route::get('/seller/register', [SellerRegisterController::class, 'create'])->name('seller.register.form');
    Route::post('/seller/register', [SellerRegisterController::class, 'store'])->name('seller.register.submit');
    
    // Checkout & Invoice
    Route::get('/checkout', [CustomerCheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CustomerCheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/invoice/{invoice}', [CustomerCheckoutController::class, 'invoice'])->name('checkout.invoice');
    
    // Cart
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/update', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');

    // Notifikasi
    Route::get('/notifications/unread', [NotifikasiCustomerController::class, 'getUnread'])->name('notifications.unread');
    Route::get('/notifications', [NotifikasiCustomerController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotifikasiCustomerController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/read-all', [NotifikasiCustomerController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');

    // ===> TAMBAHKAN BARIS INI <===
    // Ini untuk halaman "Pesanan Saya" di dashboard customer
    Route::get('/pesanan', [PesananController::class, 'index'])->name('pesanan.index');
    
    // Opsional: Tambahkan detail pesanan juga agar tidak error saat diklik
    Route::get('/pesanan/{pesanan}', [PesananController::class, 'show'])->name('pesanan.show');
    
    // PPOB Customer
    Route::prefix('ppob')->name('ppob.')->group(function () {
        Route::get('/history', [PpobHistoryController::class, 'index'])->name('history');
        Route::get('/export/excel', [PpobHistoryController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [PpobHistoryController::class, 'exportPdf'])->name('export.pdf');
        Route::delete('/transaction/{id}', [AdminPpobController::class, 'destroy'])->name('transaction.destroy');
    });


});


/*
|--------------------------------------------------------------------------
| 3. ADMIN ROUTES (FIXED ALL ERRORS)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', RoleMiddleware::class . ':Admin'])->prefix('admin')->name('admin.')->group(function () {
    
    require __DIR__.'/web/admin.php';

    // 1. ROUTE ORDERS, PRINT THERMAL & INVOICE PDF (FIX Route [admin.orders...])
    Route::get('/orders/{invoice_number}/print-thermal', [AdminOrderController::class, 'printThermal'])->name('orders.print.thermal');
    Route::get('/orders/{invoice_number}/invoice-pdf', [AdminOrderController::class, 'exportInvoice'])->name('orders.invoice.pdf');
    Route::resource('orders', AdminOrderController::class);

    // 2. ROUTE SPX SCANS
    Route::resource('spx-scans', SpxScanController::class)->names('spx_scans');

    // 3. ROUTE REVIEWS
    Route::resource('reviews', AdminReviewController::class);
    Route::post('reviews/{review}/reply', [AdminReviewController::class, 'reply'])->name('reviews.reply');

    // 4. ROUTE STORES & MARKETPLACE (FIX View Not Found)
    // Gunakan StoreController untuk stores jika ada, jika tidak pakai AdminMarketplaceController
    Route::resource('stores', AdminMarketplaceController::class)->names('stores');
    Route::resource('marketplace', AdminMarketplaceController::class); 

    // 5. ROUTE KODE POS (FIX Route [admin.kodepos.index])
    Route::get('/kode-pos', [KodePosController::class, 'index'])->name('kodepos.index');
    Route::post('/kode-pos/import', [KodePosController::class, 'import'])->name('kodepos.import');

    // 6. ROUTE PENGGUNA & EXPORT (FIX Route [admin.customers.pengguna.export])
    Route::get('/customers/data/pengguna/export', [DataPenggunaController::class, 'export'])->name('customers.pengguna.export'); 
    Route::resource('customers/data/pengguna', DataPenggunaController::class)->names('customers.data.pengguna');

    // 7. ROUTE PELANGGAN (FIX Route [admin.pelanggan.store])
    Route::resource('pelanggan', PelangganController::class);
    Route::prefix('pelanggan')->name('pelanggan.')->group(function () {
        Route::post('/import-excel', [PelangganController::class, 'importExcel'])->name('import.excel');
        Route::get('/export-excel', [PelangganController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export-pdf', [PelangganController::class, 'exportPdf'])->name('export.pdf');
    });

    // 8. ROUTE WILAYAH (FIX Route [admin.wilayah.kabupaten])
    Route::prefix('wilayah')->name('wilayah.')->group(function() {
        Route::get('/', [WilayahController::class, 'index'])->name('index');
        Route::post('/', [WilayahController::class, 'store'])->name('store');
        Route::put('/{id}', [WilayahController::class, 'update'])->name('update');
        Route::delete('/{id}', [WilayahController::class, 'destroy'])->name('destroy');
        Route::get('/province/{province}/regencies', [WilayahController::class, 'getKabupaten'])->name('kabupaten');
        Route::get('/regency/{regency}/districts', [WilayahController::class, 'getKecamatan'])->name('kecamatan');
        Route::get('/district/{district}/villages', [WilayahController::class, 'getDesa'])->name('desa');
        Route::get('/api/provinces', [WilayahController::class, 'getProvinces'])->name('api.provinces');
    });

    // 9. ROUTE POST DETAIL (FIX Route [admin.posts.post-detail])
    Route::get('/post/{post:slug}', [PostController::class, 'show'])->name('posts.post-detail');
    Route::get('/posts/{id}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');

    // 10. PESANAN MULTI (FIX 404 /admin/pesanan/buat-multi)
    Route::get('/pesanan/buat-multi', [AdminKoliController::class, 'create'])->name('pesanan.create_multi');
    Route::post('/pesanan/store-multi', [AdminKoliController::class, 'store'])->name('koli.store');
    Route::post('/pesanan/store-single', [AdminKoliController::class, 'storeSingle'])->name('koli.store_single');
    Route::post('/cek-ongkir', [AdminKoliController::class, 'cek_Ongkir'])->name('koli.cek_ongkir');

    // --- LAINNYA ---
    Route::resource('couriers', CourierController::class);
    Route::resource('banners', BannerController::class);
    Route::resource('coa', CoaController::class)->except(['show']);

    // Admin Chat
    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/', [AdminChatController::class, 'index'])->name('index');
        Route::get('/search-users', [AdminChatController::class, 'searchUsers'])->name('searchUsers');
        Route::get('/messages/{user}', [AdminChatController::class, 'fetchMessages'])->name('messages');
        Route::post('/messages/{user}', [AdminChatController::class, 'sendMessage'])->name('send');
    });

    // PPOB Admin (FIX [admin.ppob.export.excel])
    Route::get('/digital', [AdminPpobController::class, 'index'])->name('ppob.index'); 
    Route::get('/digital/{slug}', [AdminPpobController::class, 'category'])->name('ppob.category');
    Route::post('/categories/ajax-store', [App\Http\Controllers\Admin\CategoryController::class, 'storeAjax'])->name('categories.storeAjax');
    Route::delete('/categories/ajax-delete/{id}', [App\Http\Controllers\Admin\CategoryController::class, 'destroyAjax'])->name('categories.destroyAjax');

    Route::prefix('ppob')->name('ppob.')->group(function () {
        Route::get('/data', [AdminPpobController::class, 'index'])->name('data.index');
        // Export Route
        Route::get('/export/excel', [AdminPpobController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [AdminPpobController::class, 'exportPdf'])->name('export.pdf');
        
        // Produk PPOB
        Route::get('/product/export-excel', [PpobProductController::class, 'exportExcel'])->name('product.export-excel');
        Route::get('/product/export-pdf', [PpobProductController::class, 'exportPdf'])->name('product.export-pdf');
        Route::post('/bulk-update', [PpobProductController::class, 'bulkUpdate'])->name('bulk-update');
        Route::get('/{id}', [PpobProductController::class, 'show'])->name('show');
        Route::put('/update-price/{id}', [PpobProductController::class, 'updatePrice'])->name('update-price');
        Route::delete('/destroy/{id}', [PpobProductController::class, 'destroy'])->name('destroy');
    });
    
    Route::post('/deposit', [AdminPpobController::class, 'requestDeposit'])->name('ppob.deposit');
    Route::post('/topup', [AdminPpobController::class, 'topup'])->name('ppob.topup');

    // Admin Settings & Tools
    Route::get('/settings/api', [ApiSettingsController::class, 'index'])->name('settings.api.index');
    Route::put('/settings/api', [ApiSettingsController::class, 'update'])->name('settings.api.update');
    Route::post('/settings/api', [ApiSettingsController::class, 'toggle'])->name('settings.api.toggle');
    Route::view('/setting', 'admin.setting')->name('settings');
    Route::get('/logs', [AdminLogController::class, 'showLogs'])->name('logs.show');
    Route::post('/logs/clear', [AdminLogController::class, 'clearLogs'])->name('logs.clear');
    Route::get('/generate-barcode-zoom', [BarcodeController::class, 'generateBarcode'])->name('barcode.generate');
    Route::get('/imap', [EmailController::class, 'index'])->name('imap.index');
    Route::get('/imap/{id}', [EmailController::class, 'show'])->name('imap.show');
    Route::delete('/imap/{id}', [EmailController::class, 'destroy'])->name('imap.destroy');

    // Laporan Keuangan
    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('pemasukan', [LaporanKeuanganController::class, 'pemasukan'])->name('pemasukan');
        Route::post('pemasukan', [LaporanKeuanganController::class, 'storePemasukan'])->name('pemasukan.store');
        Route::get('pengeluaran', [LaporanKeuanganController::class, 'pengeluaran'])->name('pengeluaran');
        Route::post('pengeluaran', [LaporanKeuanganController::class, 'storePengeluaran'])->name('pengeluaran.store');
        Route::get('laba-rugi', [LaporanKeuanganController::class, 'labaRugi'])->name('labaRugi');
        Route::get('neraca-saldo', [LaporanKeuanganController::class, 'neracaSaldo'])->name('neracaSaldo');
        Route::get('neraca', [LaporanKeuanganController::class, 'neraca'])->name('neraca');
    });

    // Sliders
    Route::get('/sliders', [SliderController::class, 'index'])->name('sliders.index');
    Route::post('/sliders', [SliderController::class, 'store'])->name('sliders.store');
    Route::delete('/sliders/{slide}', [SliderController::class, 'destroy'])->name('sliders.destroy');

    // User Management
    Route::post('/users/{user}/toggle-freeze', [UserController::class, 'toggleFreeze'])->name('users.toggle-freeze');

    // Info Pesanan
    Route::get('/setting-info-pesanan', [AdminController::class, 'editInfoPesanan'])->name('info.edit');
    Route::post('/setting-info-pesanan', [AdminController::class, 'updateInfoPesanan'])->name('info.update');

    // Wallet Admin
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/topup', [WalletController::class, 'topup'])->name('wallet.topup');
    Route::get('/wallet/search', [WalletController::class, 'search'])->name('wallet.search');

    // Category Attributes
    Route::prefix('category-attributes')->name('category-attributes.')->group(function () {
        Route::get('/', [CategoryAttributeController::class, 'index'])->name('index');
        Route::post('/{category}', [CategoryAttributeController::class, 'store'])->name('store');
        Route::get('/{attribute}/edit', [CategoryAttributeController::class, 'edit'])->name('edit');
        Route::put('/{attribute}', [CategoryAttributeController::class, 'update'])->name('update');
        Route::delete('/{attribute}', [CategoryAttributeController::class, 'destroy'])->name('destroy');
    });
    
    // Product Specs
    Route::get('products/{slug}/specifications', [ProductController::class, 'editSpecifications'])->name('products.edit.specifications');
    Route::put('products/{slug}/specifications', [ProductController::class, 'updateSpecifications'])->name('products.update.specifications');
    Route::get('categories/{category}/attributes', [ProductController::class, 'getAttributes'])->name('categories.attributes');
    
    // Import WP
    Route::get('/import/wordpress', [ImportController::class, 'showForm'])->name('import.wordpress.form');
    Route::post('/import/wordpress', [ImportController::class, 'handleImport'])->name('import.wordpress.handle');

    // Courier Specific
    Route::get('/couriers/search', [CourierController::class, 'search'])->name('couriers.search');
    Route::get('couriers/{id}/scan', [CourierController::class, 'showScanPage'])->name('couriers.scan');
    Route::get('couriers/{id}/track', [CourierController::class, 'trackLocation'])->name('couriers.track');
    Route::get('couriers/{id}/print', [CourierController::class, 'printDeliveryOrder'])->name('couriers.print');
    
    // SPX Monitor
    Route::get('/surat-jalan/monitor', [SpxScanController::class, 'showMonitorPage'])->name('suratjalan.monitor.index');
    Route::get('/surat-jalan/monitor/export-pdf', [SpxScanController::class, 'exportMonitorPdf'])->name('suratjalan.monitor.export_pdf');
    Route::get('/surat-jalan/{kode_surat_jalan}/download', [SpxScanController::class, 'downloadSuratJalan'])->name('suratjalan.download');
});


/*
|--------------------------------------------------------------------------
| 4. SELLER & GENERAL ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', RoleMiddleware::class . ':Seller|Admin'])->prefix('seller')->name('seller.')->group(function () {
    require __DIR__.'/web/seller.php';
});

// Route Public Etalase & PPOB
Route::prefix('etalase/ppob')->name('ppob.')->group(function () {
    Route::get('/digital/{slug}', [PpobController::class, 'index'])->name('category');
    Route::post('/check-bill', [PpobController::class, 'checkBill'])->name('check.bill');
    Route::post('/check-pln-prabayar', [PpobController::class, 'checkPlnPrabayar'])->name('check.pln.prabayar');
    Route::post('/transaction', [PpobController::class, 'store'])->name('store');
});

Route::middleware(['auth', 'verified'])->prefix('digital')->name('ppob.')->group(function () {
    Route::post('/checkout', [PpobController::class, 'store'])->name('store');
    Route::get('/status/{ref_id}', [PpobController::class, 'checkStatus'])->name('status');
    Route::get('/cek-saldo', [PpobController::class, 'cekSaldo'])->name('cek-saldo');
    Route::post('/ajax/check-pln-prabayar', [PpobController::class, 'checkPlnPrabayar'])->name('check.pln.prabayar');
    Route::post('/ajax/check-bill', [PpobController::class, 'checkBill'])->name('check.bill');
    Route::get('/ajax/pdam-products', [PpobController::class, 'getPdamProducts'])->name('ajax.pdam-products');
    Route::get('/kategori/{slug}', [PpobController::class, 'category'])->name('category');
});

Route::middleware(['auth'])->group(function () {
    // PPOB Checkout
    Route::post('/checkout-ppob/prepare', [PpobCheckoutController::class, 'prepare'])->name('ppob.prepare');
    Route::get('/checkout-ppob', [PpobCheckoutController::class, 'index'])->name('ppob.checkout.index');
    Route::post('/checkout-ppob/process', [PpobCheckoutController::class, 'store'])->name('ppob.checkout.store');
    Route::get('/checkout-ppob/remove/{id}', [PpobCheckoutController::class, 'removeItem'])->name('ppob.cart.remove');
    Route::post('/checkout-ppob/clear', [PpobCheckoutController::class, 'clearCart'])->name('ppob.cart.clear');
    Route::get('/invoice/{invoice}', [PpobCheckoutController::class, 'invoice'])->name('ppob.invoice');
    
    // Agent
    Route::get('/agent/register', [AgentRegistrationController::class, 'index'])->name('agent.register.index');
    Route::post('/agent/register/process', [AgentRegistrationController::class, 'register'])->name('agent.register.process');
    Route::get('/topup', [TopUpController::class, 'index'])->name('topup.index');
    Route::post('/topup', [TopUpController::class, 'store'])->name('topup.store');
    Route::get('/topup/{topup}', [TopUpController::class, 'show'])->name('customer.topup.show');
    Route::post('/topup/{reference_id}/upload', [TopUpController::class, 'uploadProof'])->name('topup.upload_proof');

    // Agent Products (Middleware)
    Route::middleware(['is_agent'])->prefix('agent/products')->name('agent.products.')->group(function () {
        Route::get('/', [AgentProductController::class, 'index'])->name('index');
        Route::put('/update', [AgentProductController::class, 'update'])->name('update');
        Route::post('/bulk-update', [AgentProductController::class, 'bulkUpdate'])->name('bulk_update');
    });
    
    Route::middleware(['is_agent'])->prefix('agent')->name('agent.')->group(function () {
        Route::get('/transaksi/create', [AgentTransactionController::class, 'create'])->name('transaction.create');
        Route::post('/transaksi/store', [AgentTransactionController::class, 'store'])->name('transaction.store');
    });

    // PPOB Sync
    Route::prefix('ppob')->name('ppob.')->group(function () {
        Route::get('/sync/prepaid', [PpobProductController::class, 'syncPrepaid'])->name('sync.prepaid'); 
        Route::get('/sync/postpaid', [PpobProductController::class, 'syncPostpaid'])->name('sync.postpaid'); 
    });
});

// Reviews
Route::middleware(['auth'])->group(function() {
    Route::get('/seller/reviews', [SellerReviewController::class, 'index'])->name('seller.reviews.index');
    Route::post('/seller/reviews/{review}/reply', [SellerReviewController::class, 'reply'])->name('seller.reviews.reply');
    Route::put('/seller/reviews/{review}/reply', [SellerReviewController::class, 'updateReply'])->name('seller.reviews.reply.update');
    Route::delete('/seller/reviews/{review}/reply', [SellerReviewController::class, 'deleteReply'])->name('seller.reviews.reply.delete');
});
Route::post('/reviews', [ProductReviewController::class, 'store'])->name('reviews.store')->middleware('auth');

// Public Utils
Route::get('/agent/ppob/cities', [AgentTransactionController::class, 'getPbbCities'])->name('admin.ppob.get-pbb-cities');
Route::get('/debug-digi', [PpobController::class, 'debugDirect']);
Route::get('/daftar-harga', [PublicController::class, 'pricelist'])->name('public.pricelist');
Route::get('/layanan/{slug}', [PublicController::class, 'showCategory'])->name('public.category');
Route::post('/ppob/check-bill', [PpobController::class, 'checkBill'])->name('ppob.check.bill');
Route::post('/ppob/check-pln-prabayar', [PpobController::class, 'checkPlnPrabayar'])->name('ppob.check.pln.prabayar');
Route::get('/cek-ip-hosting', function () {
    try {
        $response = Http::withoutVerifying()->get('https://api.ipify.org?format=json');
        return response()->json([
            'message' => 'Copy IP di bawah ini dan masukkan ke Whitelist Digiflazz',
            'real_ip_hosting' => $response->json()['ip'],
        ]);
    } catch (\Exception $e) { return "Gagal cek IP: " . $e->getMessage(); }
});

Route::post('/digiflazz/webhook', [DigiflazzWebhookController::class, 'handle'])->name('digiflazz.webhook');
Route::post('/koli/cek-ongkir', [KoliController::class, 'cekOngkirMulti'])->name('koli.cekOngkirMulti');
Route::get('/search-address', [CekOngkirController::class, 'searchAddress'])->middleware('auth')->name('customer.search.address');
Route::post('/check-cost', [CekOngkirController::class, 'checkCost'])->middleware('auth')->name('customer.check.cost');

Route::get('/api/kiriminaja/address-search', [CustomerProfileController::class, 'searchKiriminAjaAddress'])->name('kiriminaja.address_search');
Route::get('/api/cari-alamat', [CustomerOrderController::class, 'searchAddressApi'])->name('api.address.search');
Route::get('/{resi}/cetak_thermal', [AdminPesananController::class, 'cetakResiThermal'])->name('cetak_thermal');

Route::get('/kirimaja/set_callback', [\App\Http\Controllers\KirimAjaController::class, 'setCallback']);
Route::get('/kirimaja/cek-ongkir', [CustomerOrderController::class, 'cek_Ongkir'])->name('kirimaja.cekongkir');
Route::get('/kiriminaja/search-address', function (Request $request, KiriminAjaService $kiriminAja) {
    $query = $request->get('q');
    if (!$query) return response()->json(['status' => false, 'text' => 'Query kosong', 'data' => []]);
    return response()->json($kiriminAja->searchAddress($query));
});

Route::get('/tracking', [TrackingController::class, 'showTrackingPage'])->name('tracking.index');
Route::get('/tracking/search', [TrackingController::class, 'showTrackingPage'])->name('tracking.search');
Route::get('/tracking/cetak-thermal/{resi}', [TrackingController::class, 'cetakThermal'])->name('tracking.cetak_thermal');
Route::get('/tracking/refresh', [TrackingController::class, 'refresh'])->name('tracking.refresh');

Route::get('/etalase', [EtalaseController::class, 'index'])->name('etalase.index');
Route::get('/etalase/category/{category:slug}', [EtalaseController::class, 'showCategory'])->name('public.categories.show');
Route::get('/products/{product:slug}', [EtalaseController::class, 'show'])->name('products.show');
Route::get('/toko/{name}', [EtalaseController::class, 'profileToko'])->name('toko.profile');
Route::get('/etalase/kategori/{slug}', [EtalaseController::class, 'showCategory'])->name('etalase.category-show');
Route::get('/marketplace/category/{category:slug}', [CategoryController::class, 'show'])->name('marketplace.categories.show');
Route::get('/pelanggan', [PublicPelangganController::class, 'index'])->name('pelanggan.public.index');
Route::get('/feed', [BlogController::class, 'generateFeed'])->name('feed');
Route::get('/blog/posts/{post}', [BlogController::class, 'show']);
Route::get('/post/{post:slug}', [PostController::class, 'show'])->name('posts.post-detail');
Route::get('/api/contacts/search', [KontakController::class, 'search'])->name('api.contacts.search');
Route::get('/kontak/search', [KontakController::class, 'search'])->name('kontak.search');

// Payment Webhooks
Route::post('/dana/notification', [DanaController::class, 'handleNotification'])->name('dana.payment.notify');
Route::post('/callback/tripay', [CheckoutController::class, 'TripayCallback'])->name('payment.callback.tripay');
Route::prefix('payment')->group(function () {
    Route::post('/callback', [PaymentController::class, 'handleCallback'])->name('payment.callback');
    Route::post('/callback/refund', [PaymentController::class, 'handleRefundCallback'])->name('payment.callback.refund');
    Route::post('/callback/code', [PaymentController::class, 'handleCodeCallback'])->name('payment.callback.code');
});
Route::post('/cart/add-ppob', [CartController::class, 'addPpob'])->name('cart.addPpob');

Route::get('/kode-pos', [KodePosController::class, 'index'])->name('kodepos.index');
Route::post('/kode-pos/import', [KodePosController::class, 'import'])->name('kodepos.import');

// Seller Register AJAX
Route::get('seller/address/search', [SellerRegisterController::class, 'searchAddressKiriminAja'])->name('seller.address.search');
Route::post('seller/address/geocode', [SellerRegisterController::class, 'geocodeAddress'])->name('seller.address.geocode');