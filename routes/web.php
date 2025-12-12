<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use App\Services\KiriminAjaService;
use App\Http\Middleware\RoleMiddleware;

// --- Imports Auth & Core ---
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\PublicScanController;
use App\Http\Controllers\PublicPelangganController;
use App\Http\Controllers\ProfileSetupController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\Customer\CustomerLoginController;

// --- Imports Payment & Webhooks ---
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DanaController;
use App\Http\Controllers\DokuPaymentController;
use App\Http\Controllers\DigiflazzWebhookController;
use App\Http\Controllers\CheckoutController; // General Checkout
use App\Http\Controllers\CekOngkirController;

// --- Imports General / Shared ---
use App\Http\Controllers\WilayahController;
use App\Http\Controllers\KodePosController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\EtalaseController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\PondokController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\Api\KontakController;

// --- Imports Admin Controllers ---
use App\Http\Controllers\Admin\ChatController as AdminChatController;
use App\Http\Controllers\Admin\EmailController;
use App\Http\Controllers\Admin\SpxScanController;
use App\Http\Controllers\Admin\ImapController;
use App\Http\Controllers\Admin\LaporanKeuanganController;
use App\Http\Controllers\Admin\CoaController;
use App\Http\Controllers\Admin\PesananController as AdminPesananController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\PesananController;
use App\Http\Controllers\Admin\WalletController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\MarketplaceController as AdminMarketplaceController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CategoryAttributeController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\AdminSaldoTransferController;
use App\Http\Controllers\Admin\BarcodeController;
use App\Http\Controllers\Admin\Customers\DataPenggunaController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\ApiSettingsController;
use App\Http\Controllers\Admin\KoliController as AdminKoliController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\Admin\AdminPpobController;
use App\Http\Controllers\Admin\AdminLogController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;

// --- Imports Customer Controllers ---
use App\Http\Controllers\Customer\ProfileController as CustomerProfileController;
use App\Http\Controllers\CustomerChatController;
use App\Http\Controllers\Customer\KontakController as CustomerKontakController;
use App\Http\Controllers\Customer\MarketplaceController as CustomerMarketplaceController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CheckoutController as CustomerCheckoutController;
use App\Http\Controllers\Customer\CategoryController;
use App\Http\Controllers\Customer\KoliController;
use App\Http\Controllers\Customer\PpobCheckoutController;
use App\Http\Controllers\Customer\PpobHistoryController;
use App\Http\Controllers\Customer\AgentProductController;
use App\Http\Controllers\Customer\AgentRegistrationController;
use App\Http\Controllers\Customer\TopUpController;
use App\Http\Controllers\Customer\AgentTransactionController;
use App\Http\Controllers\NotifikasiCustomerController;
use App\Http\Controllers\CustomerOrderController;

// --- Imports Seller Controllers ---
use App\Http\Controllers\SellerRegisterController;
use App\Http\Controllers\SellerReviewController;

// --- Imports PPOB Logic ---
use App\Http\Controllers\PpobController;
use App\Http\Controllers\PpobProductController;
use App\Http\Controllers\TestOrderController;
use App\Http\Controllers\CourierController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\ProductController;

/*
|--------------------------------------------------------------------------
| WEBHOOKS & CALLBACKS (NO AUTH REQUIRED)
|--------------------------------------------------------------------------
| Route ini harus diakses oleh pihak ketiga (Payment Gateway / Server Pulsa)
| Tanpa Login & Tanpa CSRF.
*/

Route::post('/digiflazz/webhook', [DigiflazzWebhookController::class, 'handle'])->name('digiflazz.webhook');
Route::post('/dana/notification', [DanaController::class, 'handleNotification'])->name('dana.payment.notify');
Route::post('/callback/tripay', [CheckoutController::class, 'TripayCallback'])->name('payment.callback.tripay');

Route::prefix('payment')->group(function () {
    Route::post('/callback', [PaymentController::class, 'handleCallback'])->name('payment.callback');
    Route::post('/callback/refund', [PaymentController::class, 'handleRefundCallback'])->name('payment.callback.refund');
    Route::post('/callback/code', [PaymentController::class, 'handleCodeCallback'])->name('payment.callback.code');
});

Route::get('/kirimaja/set_callback', [\App\Http\Controllers\KirimAjaController::class, 'setCallback']);

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES (GUEST / ALL USERS)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('home');
})->name('home');

// Auth Routes (Login, Register, etc)
require __DIR__.'/auth.php';
require __DIR__.'/web/auth.php';

// Static Pages
Route::get('/privacy-policy', function () { return view('privacy-policy'); })->name('privacy.policy');
Route::get('/terms-and-conditions', function () { return view('terms'); })->name('terms.conditions');

// Public Data / API (Tracking, Cek Ongkir, Wilayah, dll)
Route::get('/tracking', [TrackingController::class, 'showTrackingPage'])->name('tracking.index');
Route::get('/tracking/search', [TrackingController::class, 'showTrackingPage'])->name('tracking.search');
Route::get('/tracking/refresh', [TrackingController::class, 'refresh'])->name('tracking.refresh');
Route::get('/tracking/cetak-thermal/{resi}', [TrackingController::class, 'cetakThermal'])->name('tracking.cetak_thermal');

Route::get('/kirimaja/cek-ongkir', [CustomerOrderController::class, 'cek_Ongkir'])->name('kirimaja.cekongkir');
Route::get('/koli/cek-ongkir', [KoliController::class, 'cekOngkirMulti'])->name('public.koli.cekOngkirMulti'); // Public access if needed
Route::post('/koli/cek-ongkir', [KoliController::class, 'cekOngkirMulti'])->name('koli.cekOngkirMulti');

// Cek IP Hosting (Utility)
Route::get('/cek-ip-hosting', function () {
    try {
        $response = \Illuminate\Support\Facades\Http::withoutVerifying()->get('https://api.ipify.org?format=json');
        return response()->json([
            'message' => 'Copy IP di bawah ini dan masukkan ke Whitelist Digiflazz',
            'real_ip_hosting' => $response->json()['ip'],
        ]);
    } catch (\Exception $e) {
        return "Gagal cek IP: " . $e->getMessage();
    }
});

// Pencarian Alamat KiriminAja (Public/Guest context)
Route::get('/kiriminaja/search-address', function (Request $request, KiriminAjaService $kiriminAja) {
    $query = $request->get('q');
    if (!$query) {
        return response()->json(['status' => false, 'text' => 'Query tidak boleh kosong', 'data' => []]);
    }
    return response()->json($kiriminAja->searchAddress($query));
});

// Etalase & Marketplace Public
Route::get('/etalase', [EtalaseController::class, 'index'])->name('etalase.index');
Route::get('/etalase/category/{category:slug}', [EtalaseController::class, 'showCategory'])->name('public.categories.show');
Route::get('/etalase/kategori/{slug}', [EtalaseController::class, 'showCategory'])->name('etalase.category-show');
Route::get('/products/{product:slug}', [EtalaseController::class, 'show'])->name('products.show');
Route::get('/toko/{name}', [EtalaseController::class, 'profileToko'])->name('toko.profile');
Route::get('/marketplace/category/{category:slug}', [CategoryController::class, 'show'])->name('marketplace.categories.show');
Route::get('/pelanggan', [PublicPelangganController::class, 'index'])->name('pelanggan.public.index');

// PPOB Public (Pricelist & Layout)
Route::get('/daftar-harga', [PublicController::class, 'pricelist'])->name('public.pricelist');
Route::get('/layanan/{slug}', [PublicController::class, 'showCategory'])->name('public.category');

// PPOB Public AJAX Checks
Route::post('/ppob/check-bill', [PpobController::class, 'checkBill'])->name('ppob.check.bill');
Route::post('/ppob/check-pln-prabayar', [PpobController::class, 'checkPlnPrabayar'])->name('ppob.check.pln.prabayar');

// Group PPOB Etalase
Route::prefix('etalase/ppob')->name('ppob.')->group(function () {
    Route::get('/digital/{slug}', [PpobController::class, 'index'])->name('category');
    Route::post('/check-bill', [PpobController::class, 'checkBill'])->name('check.bill');
    Route::post('/check-pln-prabayar', [PpobController::class, 'checkPlnPrabayar'])->name('check.pln.prabayar');
    Route::post('/transaction', [PpobController::class, 'store'])->name('store');
});

// Blog / Feed
Route::get('/feed', [BlogController::class, 'generateFeed'])->name('feed');
Route::get('/blog/posts/{post}', [BlogController::class, 'show']);

// Pondok & Landing Pages lain
Route::get('/pondok', [PondokController::class, 'index'])->name('pondok.index');
require __DIR__.'/web/public.php';
require __DIR__.'/web/pondok.php';

// Testing Routes (Doku)
Route::get('/test/doku/simple', [TestOrderController::class, 'testSimplePayment'])->name('test.doku.simple');
Route::get('/test/doku/marketplace', [TestOrderController::class, 'testMarketplacePayment'])->name('test.doku.marketplace');

// Controller List (Dev)
Route::get('/controllers-list', function () {
    $files = \Illuminate\Support\Facades\File::allFiles(app_path('Http/Controllers'));
    $controllers = collect($files)->map(function ($file) {
        $relativePath = str_replace(app_path('Http/Controllers') . '/', '', $file->getPathname());
        return 'App\\Http\\Controllers\\' . str_replace(['/', '.php'], ['\\', ''], $relativePath);
    });
    return view('controllers-list', compact('controllers'));
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES (GENERAL)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // --- Dashboard Redirection Strategy ---
    Route::get('/dashboard', function () {
        $user = auth()->user();
        if ($user->role === 'Admin') {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('customer.dashboard');
    })->name('dashboard');

    // Customer Setup & Success
    Route::get('/register/success/{no_wa}', function ($no_wa) {
        return view('auth.register-success', compact('no_wa'));
    })->name('register.success');
    Route::get('customer/profile/setup/{token}', [CustomerProfileController::class, 'setup'])->name('customer.profile.setup');

    // Generic Payment Routes
    Route::get('/bayar/{orderId}', [PaymentController::class, 'createPayment'])->name('payment.create');
    Route::get('/payment/finish', [PaymentController::class, 'finishPage'])->name('payment.finish');
    Route::post('/payment/create-example', [DokuPaymentController::class, 'createPayment'])->name('doku.create.example');

    // Dana Payment
    Route::prefix('dana')->name('dana.')->group(function () {
        Route::get('/create-payment/{order}', [DanaController::class, 'createPayment'])->name('payment.create');
        Route::get('/payment-finish', [DanaController::class, 'handleFinishRedirect'])->name('payment.finish');
    });

    // Reviews
    Route::post('/reviews', [ProductReviewController::class, 'store'])->name('reviews.store');

    // PPOB Cart / Checkout Logic (Shared Auth)
    Route::post('/checkout-ppob/prepare', [PpobCheckoutController::class, 'prepare'])->name('ppob.prepare');
    Route::get('/checkout-ppob', [PpobCheckoutController::class, 'index'])->name('ppob.checkout.index');
    Route::post('/checkout-ppob/process', [PpobCheckoutController::class, 'store'])->name('ppob.checkout.store');
    Route::get('/checkout-ppob/remove/{id}', [PpobCheckoutController::class, 'removeItem'])->name('ppob.cart.remove');
    Route::post('/checkout-ppob/clear', [PpobCheckoutController::class, 'clearCart'])->name('ppob.cart.clear');
    Route::get('/invoice/{invoice}', [PpobCheckoutController::class, 'invoice'])->name('ppob.invoice');

    // --- AGENT / MITRA AREA ---
    Route::get('/agent/register', [AgentRegistrationController::class, 'index'])->name('agent.register.index');
    Route::post('/agent/register/process', [AgentRegistrationController::class, 'register'])->name('agent.register.process');
    
    // TopUp
    Route::get('/topup', [TopUpController::class, 'index'])->name('topup.index');
    Route::post('/topup', [TopUpController::class, 'store'])->name('topup.store');
    Route::get('/topup/{topup}', [TopUpController::class, 'show'])->name('customer.topup.show');
    Route::post('/topup/{reference_id}/upload', [TopUpController::class, 'uploadProof'])->name('topup.upload_proof');

    // KHUSUS AGEN (Middleware is_agent)
    Route::middleware(['is_agent'])->prefix('agent')->name('agent.')->group(function () {
        Route::prefix('products')->name('products.')->group(function(){
            Route::get('/', [AgentProductController::class, 'index'])->name('index');
            Route::put('/update', [AgentProductController::class, 'update'])->name('update');
            Route::post('/bulk-update', [AgentProductController::class, 'bulkUpdate'])->name('bulk_update');
        });
        // Transaksi Offline / Kasir
        Route::get('/transaksi/create', [AgentTransactionController::class, 'create'])->name('transaction.create');
        Route::post('/transaksi/store', [AgentTransactionController::class, 'store'])->name('transaction.store');
    });

    // --- PPOB DIGITAL CUSTOMER (Authenticated) ---
    Route::prefix('digital')->name('ppob.')->group(function () {
         Route::post('/checkout', [PpobController::class, 'store'])->name('store');
         Route::get('/status/{ref_id}', [PpobController::class, 'checkStatus'])->name('status');
         Route::get('/cek-saldo', [PpobController::class, 'cekSaldo'])->name('cek-saldo');
         
         // AJAX Requests
         Route::post('/ajax/check-pln-prabayar', [PpobController::class, 'checkPlnPrabayar'])->name('check.pln.prabayar');
         Route::post('/ajax/check-bill', [PpobController::class, 'checkBill'])->name('check.bill');
         Route::get('/ajax/pdam-products', [PpobController::class, 'getPdamProducts'])->name('ajax.pdam-products');
         
         // Dynamic Category (Taruh bawah)
         Route::get('/kategori/{slug}', [PpobController::class, 'category'])->name('category');
    });
});

/*
|--------------------------------------------------------------------------
| CUSTOMER ROUTES (ROLE: PELANGGAN)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', RoleMiddleware::class . ':Pelanggan'])->prefix('customer')->name('customer.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    require __DIR__.'/web/customer.php';

    // Profile & Address
    Route::get('/api/kiriminaja/address-search', [CustomerProfileController::class, 'searchKiriminAjaAddress'])->name('kiriminaja.address_search');
    
    // Search API (Ongkir/Address)
    Route::get('/search-address', [CekOngkirController::class, 'searchAddress'])->name('search.address');
    Route::post('/check-cost', [CekOngkirController::class, 'checkCost'])->name('check.cost');

    // Marketplace Customer View
    Route::get('/marketplace', [CustomerMarketplaceController::class, 'index'])->name('marketplace.index');

    // Cart & Checkout Barang
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/add-ppob', [CartController::class, 'addPpob'])->name('cart.addPpob');
    Route::patch('/cart/update', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');

    // Checkout
    Route::get('/checkout', [CustomerCheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CustomerCheckoutController::class, 'store'])->name('checkout.store');
    
    // Invoice (Outside prefix usually, but routed here per request)
    Route::get('/invoice/{invoice}', [CustomerCheckoutController::class, 'invoice'])->name('checkout.invoice');
    
    // Pesanan / Order Exports
    Route::get('/pesanan/export-pdf', [PesananController::class, 'exportPdf'])->name('pesanan.export_pdf');

    // PPOB History
    Route::prefix('ppob')->name('ppob.')->group(function () {
        Route::get('/history', [PpobHistoryController::class, 'index'])->name('history');
        Route::get('/export/excel', [PpobHistoryController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [PpobHistoryController::class, 'exportPdf'])->name('export.pdf');
    });

    // Chat
    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/', [CustomerChatController::class, 'index'])->name('index');
        Route::get('/messages', [CustomerChatController::class, 'fetchMessages'])->name('fetchMessages');
        Route::post('/messages', [CustomerChatController::class, 'sendMessage'])->name('sendMessage');
    });

    // Kontak / Buku Alamat
    Route::get('/kontak/search', [CustomerKontakController::class, 'search'])->name('kontak.search');
    Route::prefix('kontak')->name('kontak.')->group(function () {
        Route::get('/', [CustomerKontakController::class, 'index'])->name('index');
        Route::post('/', [CustomerKontakController::class, 'store'])->name('store');
        Route::get('/{kontak}/edit', [CustomerKontakController::class, 'edit'])->name('edit');
        Route::put('/{kontak}', [CustomerKontakController::class, 'update'])->name('update');
        Route::delete('/{kontak}', [CustomerKontakController::class, 'destroy'])->name('destroy');
    });

    // Notifications
    Route::get('/notifications/unread', [NotifikasiCustomerController::class, 'getUnread'])->name('notifications.unread');
    Route::get('/notifications', [NotifikasiCustomerController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotifikasiCustomerController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/read-all', [NotifikasiCustomerController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');

    // Seller Registration for Customer
    Route::get('/seller/register', [SellerRegisterController::class, 'create'])->name('seller.register.form');
    Route::post('/seller/register', [SellerRegisterController::class, 'store'])->name('seller.register.submit');
});

// --- ROUTE KHUSUS MULTI KOLI (Pelanggan/Seller) ---
// Dikeluarkan atau dipastikan aksesnya untuk Pelanggan/Seller
Route::middleware(['auth', RoleMiddleware::class . ':Pelanggan|Seller'])->prefix('customer')->name('customer.')->group(function () {
    // Pesanan Multi Koli
    Route::get('/pesanan/multi/create', [KoliController::class, 'create'])->name('koli.create');
    // Fix untuk error 404 pada URL: /customer/pesanan/create/multi-koli
    Route::get('/pesanan/create/multi-koli', [KoliController::class, 'create'])->name('koli.create_legacy'); 
    
    Route::post('/pesanan/multi/store', [KoliController::class, 'store'])->name('koli.store');
    Route::post('/koli/store', [KoliController::class, 'store'])->name('koli.store_alias'); // Alias
    Route::post('/koli/store-single', [KoliController::class, 'storeSingle'])->name('koli.store_single');
    Route::post('/koli/cek-ongkir', [KoliController::class, 'cek_Ongkir'])->name('koli.cek_ongkir');
});

/*
|--------------------------------------------------------------------------
| SELLER ROUTES (ROLE: SELLER)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', RoleMiddleware::class . ':Seller'])->prefix('seller')->name('seller.')->group(function () {
    
    Route::get('/dashboard', function () {
        return view('seller.dashboard');
    })->name('dashboard');

    require __DIR__.'/web/seller.php';

    // Address Search for Seller Registration/Profile
    Route::get('/address/search', [SellerRegisterController::class, 'searchAddressKiriminAja'])->name('address.search');
    Route::post('/address/geocode', [SellerRegisterController::class, 'geocodeAddress'])->name('address.geocode');
});

// Seller Reviews (Accessible by Auth)
Route::middleware(['auth'])->group(function() {
    Route::get('/seller/reviews', [SellerReviewController::class, 'index'])->name('seller.reviews.index');
    Route::post('/seller/reviews/{review}/reply', [SellerReviewController::class, 'reply'])->name('seller.reviews.reply');
    Route::put('/seller/reviews/{review}/reply', [SellerReviewController::class, 'updateReply'])->name('seller.reviews.reply.update');
    Route::delete('/seller/reviews/{review}/reply', [SellerReviewController::class, 'deleteReply'])->name('seller.reviews.reply.delete');
});

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES (ROLE: ADMIN)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', RoleMiddleware::class . ':Admin'])->prefix('admin')->name('admin.')->group(function () {
    
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    require __DIR__.'/web/admin.php';
    require __DIR__.'/admin/orders.php'; // Include Admin Order Routes

    // Settings
    Route::view('/setting', 'admin.setting')->name('settings');
    Route::get('/setting-info-pesanan', [AdminController::class, 'editInfoPesanan'])->name('info.edit');
    Route::post('/setting-info-pesanan', [AdminController::class, 'updateInfoPesanan'])->name('info.update');
    
    // API Settings
    Route::get('/settings/api', [ApiSettingsController::class, 'index'])->name('settings.api.index');
    Route::put('/settings/api', [ApiSettingsController::class, 'update'])->name('settings.api.update');
    Route::post('/settings/api', [ApiSettingsController::class, 'toggle'])->name('settings.api.toggle');

    // Users & Customers
    Route::resource('pelanggan', PelangganController::class);
    Route::prefix('pelanggan')->name('pelanggan.')->group(function () {
        Route::post('/import-excel', [PelangganController::class, 'importExcel'])->name('import.excel');
        Route::get('/export-excel', [PelangganController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export-pdf', [PelangganController::class, 'exportPdf'])->name('export.pdf');
    });
    Route::resource('customers/data/pengguna', DataPenggunaController::class)->names('customers.data.pengguna');
    Route::post('/users/{user}/toggle-freeze', [UserController::class, 'toggleFreeze'])->name('users.toggle-freeze');
    Route::get('/pengguna/export', [PelangganController::class, 'export'])->name('customers.pengguna.export'); // Perbaikan Controller

    // Products & Marketplace
    Route::resource('stores', AdminMarketplaceController::class)->names('stores');
    Route::get('/marketplace', [AdminMarketplaceController::class, 'index'])->name('marketplace.index');
    Route::post('/marketplace', [AdminMarketplaceController::class, 'store'])->name('marketplace.store');
    Route::get('/marketplace/{product}', [AdminMarketplaceController::class, 'show'])->name('marketplace.show');
    Route::put('/marketplace/{product}', [AdminMarketplaceController::class, 'update'])->name('marketplace.update');
    Route::delete('/marketplace/{product}', [AdminMarketplaceController::class, 'destroy'])->name('marketplace.destroy');
    
    // Specifications
    Route::get('products/{slug}/specifications', [AdminProductController::class, 'editSpecifications'])->name('products.edit.specifications');
    Route::put('products/{slug}/specifications', [AdminProductController::class, 'updateSpecifications'])->name('products.update.specifications');
    Route::get('categories/{category}/attributes', [ProductController::class, 'getAttributes'])->name('categories.attributes');

    // Category Attributes
    Route::prefix('category-attributes')->name('category-attributes.')->group(function () {
        Route::get('/', [CategoryAttributeController::class, 'index'])->name('index');
        Route::post('/{category}', [CategoryAttributeController::class, 'store'])->name('store');
        Route::get('/{attribute}/edit', [CategoryAttributeController::class, 'edit'])->name('edit');
        Route::put('/{attribute}', [CategoryAttributeController::class, 'update'])->name('update');
        Route::delete('/{attribute}', [CategoryAttributeController::class, 'destroy'])->name('destroy');
    });
    // Ajax Categories
    Route::post('/categories/ajax-store', [AdminCategoryController::class, 'storeAjax'])->name('categories.storeAjax'); // removed prefix name
    Route::delete('/categories/ajax-delete/{id}', [AdminCategoryController::class, 'destroyAjax'])->name('categories.destroyAjax');

    // Sliders & Banners
    Route::get('/sliders', [SliderController::class, 'index'])->name('sliders.index');
    Route::post('/sliders', [SliderController::class, 'store'])->name('sliders.store');
    Route::delete('/sliders/{slide}', [SliderController::class, 'destroy'])->name('sliders.destroy');
    Route::resource('banners', BannerController::class);

    // Posts / Blog
    Route::get('/post/{post:slug}', [PostController::class, 'show'])->name('posts.post-detail');
    Route::get('/posts/{id}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::put('posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::get('/import/wordpress', [ImportController::class, 'showForm'])->name('import.wordpress.form');
    Route::post('/import/wordpress', [ImportController::class, 'handleImport'])->name('import.wordpress.handle');

    // Wilayah & Kode Pos
    Route::get('/wilayah', [WilayahController::class, 'index'])->name('wilayah.index');
    Route::get('/kode-pos', [KodePosController::class, 'index'])->name('kodepos.index');
    Route::post('/kode-pos/import', [KodePosController::class, 'import'])->name('kodepos.import');
    
    Route::prefix('wilayah')->name('wilayah.')->group(function() {
        // API Dinamis
        Route::get('/api/provinces', [WilayahController::class, 'getProvinces'])->name('api.provinces');
        Route::get('/api/regencies/{province}', [WilayahController::class, 'getRegencies'])->name('api.regencies');
        Route::get('/api/districts/{regency}', [WilayahController::class, 'getDistricts'])->name('api.districts');
        Route::get('/api/villages/{district}', [WilayahController::class, 'getVillages'])->name('api.villages');
        // CRUD
        Route::post('/', [WilayahController::class, 'store'])->name('store');
        Route::put('/{id}', [WilayahController::class, 'update'])->name('update');
        Route::delete('/{id}', [WilayahController::class, 'destroy'])->name('destroy');
        // Chain
        Route::get('/province/{province}/regencies', [WilayahController::class, 'getKabupaten'])->name('kabupaten');
        Route::get('/regency/{regency}/districts', [WilayahController::class, 'getKecamatan'])->name('kecamatan');
        Route::get('/district/{district}/villages', [WilayahController::class, 'getDesa'])->name('desa');
    });

    // PPOB ADMIN MANAGEMENT
    Route::prefix('ppob')->name('ppob.')->group(function () {
        // Dashboard / Data Transaksi
        Route::get('/data', [AdminPpobController::class, 'index'])->name('data.index');
        Route::get('/digital', [AdminPpobController::class, 'index'])->name('index'); // Alias
        
        // Export Transaksi
        Route::get('/data/export/excel', [AdminPpobController::class, 'exportExcel'])->name('data.export.excel');
        Route::get('/data/export/pdf', [AdminPpobController::class, 'exportPdf'])->name('data.export.pdf');
        // Legacy export names (to fix your 404s)
        Route::get('/export/excel', [AdminPpobController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [AdminPpobController::class, 'exportPdf'])->name('export.pdf');

        // Manage Transaction (CRUD)
        Route::get('/transaction/{id}', [AdminPpobController::class, 'show'])->name('transaction.show');
        Route::put('/transaction/{id}', [AdminPpobController::class, 'update'])->name('transaction.update');
        Route::delete('/transaction/{id}', [AdminPpobController::class, 'destroy'])->name('transaction.destroy');
        Route::get('/destroy/{id}', [AdminPpobController::class, 'destroy'])->name('transaction.destroy.get');

        // Action
        Route::post('/deposit', [AdminPpobController::class, 'requestDeposit'])->name('deposit');
        Route::post('/topup', [AdminPpobController::class, 'topup'])->name('topup');
        Route::get('/cek-saldo', [AdminPpobController::class, 'cekSaldo'])->name('cek-saldo');
        Route::get('/digital/{slug}', [AdminPpobController::class, 'category'])->name('category');

        // PPOB Product Management (PpobProductController)
        Route::post('/bulk-update', [PpobProductController::class, 'bulkUpdate'])->name('bulk-update');
        Route::get('/product-export/excel', [PpobProductController::class, 'exportExcel'])->name('product.export.excel');
        
        // Fix duplicate export names for products
        Route::get('/product/export-excel', [PpobProductController::class, 'exportExcel'])->name('product.export-excel');
        Route::get('/product/export-pdf', [PpobProductController::class, 'exportPdf'])->name('product.export-pdf');

        Route::get('/product/{id}', [PpobProductController::class, 'show'])->name('product.show');
        Route::put('/update-price/{id}', [PpobProductController::class, 'updatePrice'])->name('update-price');
        Route::delete('/destroy/{id}', [PpobProductController::class, 'destroy'])->name('product.destroy');

        // Sync
        Route::get('/sync/prepaid', [PpobProductController::class, 'syncPrepaid'])->name('sync.prepaid'); 
        Route::get('/sync/postpaid', [PpobProductController::class, 'syncPostpaid'])->name('sync.postpaid'); 

        // Cities
        Route::get('/agent/cities', [AgentTransactionController::class, 'getPbbCities'])->name('get-pbb-cities');
    });
    
    // Finance / Wallet / Laporan
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/topup', [WalletController::class, 'topup'])->name('wallet.topup');
    Route::get('/wallet/search', [WalletController::class, 'search'])->name('wallet.search');
    
    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('pemasukan', [LaporanKeuanganController::class, 'pemasukan'])->name('pemasukan');
        Route::post('pemasukan', [LaporanKeuanganController::class, 'storePemasukan'])->name('pemasukan.store');
        Route::get('pengeluaran', [LaporanKeuanganController::class, 'pengeluaran'])->name('pengeluaran');
        Route::post('pengeluaran', [LaporanKeuanganController::class, 'storePengeluaran'])->name('pengeluaran.store');
        Route::get('laba-rugi', [LaporanKeuanganController::class, 'labaRugi'])->name('labaRugi');
        Route::get('neraca-saldo', [LaporanKeuanganController::class, 'neracaSaldo'])->name('neracaSaldo');
        Route::get('neraca', [LaporanKeuanganController::class, 'neraca'])->name('neraca');
    });

    // COA (Chart of Accounts)
    Route::get('coa/export/excel', [CoaController::class, 'exportExcel'])->name('coa.export.excel');
    Route::get('coa/export/pdf', [CoaController::class, 'exportPdf'])->name('coa.export.pdf');
    Route::get('coa/import', [CoaController::class, 'showImportForm'])->name('coa.import.form');
    Route::post('coa/import', [CoaController::class, 'importExcel'])->name('coa.import.excel');
    Route::get('coa/import/template', [CoaController::class, 'downloadTemplate'])->name('coa.import.template');
    Route::resource('coa', CoaController::class)->except(['show']);

    // Shipping / Logistics (Koli & Courier)
    Route::get('/pesanan/buat-multi', [AdminKoliController::class, 'create'])->name('pesanan.create_multi');
    Route::post('/pesanan/store-multi', [AdminKoliController::class, 'store'])->name('koli.store');
    Route::post('/pesanan/store-single', [AdminKoliController::class, 'storeSingle'])->name('koli.store_single');
    Route::post('/cek-ongkir', [AdminKoliController::class, 'cek_Ongkir'])->name('koli.cek_ongkir');

    Route::resource('couriers', CourierController::class);
    Route::get('/couriers/search', [CourierController::class, 'search'])->name('api.couriers.search');
    Route::get('couriers/{id}/scan', [CourierController::class, 'showScanPage'])->name('couriers.scan');
    Route::get('couriers/{id}/track', [CourierController::class, 'trackLocation'])->name('couriers.track');
    Route::get('couriers/{id}/print', [CourierController::class, 'printDeliveryOrder'])->name('couriers.print');

    // SPX Scans
    Route::resource('spx-scans', SpxScanController::class)->names('spx_scans');
    Route::get('/surat-jalan/monitor', [SpxScanController::class, 'showMonitorPage'])->name('suratjalan.monitor.index');
    Route::get('/surat-jalan/monitor/export-pdf', [SpxScanController::class, 'exportMonitorPdf'])->name('suratjalan.monitor.export_pdf');
    Route::get('/surat-jalan/{kode_surat_jalan}/download', [SpxScanController::class, 'downloadSuratJalan'])->name('suratjalan.download');
    Route::get('/spx-scans/create', [SpxScanController::class, 'create'])->name('spx_scans.create.custom');
    
    // Barcode
    Route::get('/generate-barcode-zoom', [BarcodeController::class, 'generateBarcode'])->name('barcode.generate');
    
    // Email / Inbox
    Route::get('/imap', [EmailController::class, 'index'])->name('imap.index');
    Route::get('/imap/{id}', [EmailController::class, 'show'])->name('imap.show');
    Route::delete('/imap/{id}', [EmailController::class, 'destroy'])->name('imap.destroy');

    // Admin Chat
    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/', [AdminChatController::class, 'index'])->name('index');
        Route::get('/search-users', [AdminChatController::class, 'searchUsers'])->name('searchUsers');
        Route::get('/messages/{user}', [AdminChatController::class, 'fetchMessages'])->name('messages');
        Route::post('/messages/{user}', [AdminChatController::class, 'sendMessage'])->name('send');
        Route::get('/start', [AdminChatController::class, 'start'])->name('start');
    });

    // Reviews & Logs
    Route::resource('reviews', AdminReviewController::class);
    Route::post('reviews/{review}/reply', [AdminReviewController::class, 'reply'])->name('reviews.reply');
    Route::get('/logs', [AdminLogController::class, 'showLogs'])->name('logs.show');
    Route::post('/logs/clear', [AdminLogController::class, 'clearLogs'])->name('logs.clear');

    // Contacts Search API (Internal Admin)
    Route::get('/api/contacts/search', [AdminPesananController::class, 'searchKontak'])->name('api.contacts.search');
    Route::get('/{resi}/cetak_thermal', [AdminPesananController::class, 'cetakResiThermal'])->name('cetak_thermal');
});