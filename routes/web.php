<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use App\Services\KiriminAjaService;
use App\Http\Middleware\RoleMiddleware;

// --- Auth & Profile Controllers ---
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ProfileSetupController;
use App\Http\Controllers\Customer\ProfileController as CustomerProfileController;
use App\Http\Controllers\Customer\ProfileController;

// --- Public / General Controllers ---
use App\Http\Controllers\PublicController;
use App\Http\Controllers\PublicScanController;
use App\Http\Controllers\PublicPelangganController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\PondokController;
use App\Http\Controllers\WilayahController;
use App\Http\Controllers\KodePosController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\CekOngkirController;
use App\Http\Controllers\EtalaseController;
use App\Http\Controllers\KirimAjaController;
use App\Http\Controllers\TestOrderController;

// --- Payment & Checkout Controllers ---
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DanaController;
use App\Http\Controllers\DokuPaymentController;
use App\Http\Controllers\DigiflazzWebhookController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CheckoutController as CustomerCheckoutController;
use App\Http\Controllers\CustomerOrderController;

// --- Customer Specific Controllers ---
use App\Http\Controllers\Customer\CategoryController;
use App\Http\Controllers\Customer\ChatController as CustomerChatController;
use App\Http\Controllers\Customer\KontakController as CustomerKontakController;
use App\Http\Controllers\Customer\MarketplaceController as CustomerMarketplaceController;
use App\Http\Controllers\Customer\KoliController;
use App\Http\Controllers\Customer\PpobCheckoutController;
use App\Http\Controllers\Customer\PpobHistoryController;
use App\Http\Controllers\Customer\AgentProductController;
use App\Http\Controllers\Customer\AgentRegistrationController;
use App\Http\Controllers\Customer\TopUpController;
use App\Http\Controllers\Customer\AgentTransactionController;
use App\Http\Controllers\NotifikasiCustomerController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\SellerReviewController;
use App\Http\Controllers\SellerRegisterController;

// --- Admin Controllers ---
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\ChatController as AdminChatController;
use App\Http\Controllers\Admin\EmailController;
use App\Http\Controllers\Admin\SpxScanController;
use App\Http\Controllers\Admin\ImapController;
use App\Http\Controllers\Admin\LaporanKeuanganController;
use App\Http\Controllers\Admin\CoaController;
use App\Http\Controllers\Admin\PesananController as AdminPesananController;
use App\Http\Controllers\Admin\PesananController; // Hati-hati duplikat nama class
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\WalletController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\MarketplaceController as AdminMarketplaceController;
use App\Http\Controllers\Admin\MarketplaceController; 
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CategoryAttributeController;
use App\Http\Controllers\Admin\ProductController;
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
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController; // Alias to avoid clash
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\CourierController;

// --- API & PPOB Controllers ---
use App\Http\Controllers\Api\KontakController;
use App\Http\Controllers\PpobController;
use App\Http\Controllers\PpobProductController;
use App\Http\Controllers\PenggunaController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// =========================================================================
// 1. WEBHOOKS & CALLBACKS (NO AUTH / NO CSRF usually)
// =========================================================================

// Digiflazz Webhook
Route::post('/digiflazz/webhook', [DigiflazzWebhookController::class, 'handle'])->name('digiflazz.webhook');

// Payment Callbacks
Route::prefix('payment')->group(function () {
    Route::post('/callback', [PaymentController::class, 'handleCallback'])->name('payment.callback');
    Route::post('/callback/refund', [PaymentController::class, 'handleRefundCallback'])->name('payment.callback.refund');
    Route::post('/callback/code', [PaymentController::class, 'handleCodeCallback'])->name('payment.callback.code');
});
Route::post('/dana/notification', [DanaController::class, 'handleNotification'])->name('dana.payment.notify');
Route::post('/callback/tripay', [CheckoutController::class, 'TripayCallback'])->name('payment.callback.tripay');

// KiriminAja Callback
Route::get('/kirimaja/set_callback', [KirimAjaController::class, 'setCallback']);


// =========================================================================
// 2. PUBLIC ROUTES (No Auth Required)
// =========================================================================

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
})->name('privacy.policy');

Route::get('/terms-and-conditions', function () {
    return view('terms');
})->name('terms.conditions');

// Authentication Routes (Login, Register, etc.)
require __DIR__.'/auth.php';
require __DIR__.'/web/auth.php'; // Jika ada file auth custom tambahan

Route::get('/register/success/{no_wa}', function ($no_wa) {
    return view('auth.register-success', compact('no_wa'));
})->name('register.success');

// Setup Profile Token
Route::get('customer/profile/setup/{token}', [CustomerProfileController::class, 'setup'])->name('customer.profile.setup');

// --- Etalase / Marketplace Public ---
Route::get('/etalase', [EtalaseController::class, 'index'])->name('etalase.index');
Route::get('/etalase/kategori/{slug}', [EtalaseController::class, 'showCategory'])->name('etalase.category-show');
Route::get('/etalase/category/{category:slug}', [EtalaseController::class, 'showCategory'])->name('public.categories.show');
Route::get('/products/{product:slug}', [EtalaseController::class, 'show'])->name('products.show');
Route::get('/toko/{name}', [EtalaseController::class, 'profileToko'])->name('toko.profile');
Route::get('/marketplace', [CustomerMarketplaceController::class, 'index'])->name('katalog.index'); // Duplicate name fixed

// --- Tracking ---
Route::get('/tracking', [TrackingController::class, 'showTrackingPage'])->name('tracking.index');
Route::get('/tracking/search', [TrackingController::class, 'showTrackingPage'])->name('tracking.search');
Route::get('/tracking/refresh', [TrackingController::class, 'refresh'])->name('tracking.refresh');
Route::get('/tracking/cetak-thermal/{resi}', [TrackingController::class, 'cetakThermal'])->name('tracking.cetak_thermal');

// --- Blog / Pondok / Pelanggan Public ---
Route::get('/feed', [BlogController::class, 'generateFeed'])->name('feed');
Route::get('/blog/posts/{post}', [BlogController::class, 'show']);
Route::get('/pondok', [PondokController::class, 'index'])->name('pondok.index');
Route::get('/pelanggan', [PublicPelangganController::class, 'index'])->name('pelanggan.public.index');
require __DIR__.'/web/public.php'; // Include external public routes
require __DIR__.'/web/pondok.php';

// --- PPOB Public (Price List & Inquiry) ---
Route::get('/daftar-harga', [PublicController::class, 'pricelist'])->name('public.pricelist');
Route::get('/layanan/{slug}', [PublicController::class, 'showCategory'])->name('public.category');
Route::post('/ppob/check-bill', [PpobController::class, 'checkBill'])->name('ppob.check.bill');
Route::post('/ppob/check-pln-prabayar', [PpobController::class, 'checkPlnPrabayar'])->name('ppob.check.pln.prabayar');
Route::get('/debug-digi', [PpobController::class, 'debugDirect']);

// Grouping URL PPOB Public Etalase
Route::prefix('etalase/ppob')->name('ppob.')->group(function () {
    Route::get('/digital/{slug}', [PpobController::class, 'index'])->name('category');
    Route::post('/check-bill', [PpobController::class, 'checkBill'])->name('check.bill');
    Route::post('/check-pln-prabayar', [PpobController::class, 'checkPlnPrabayar'])->name('check.pln.prabayar');
    Route::post('/transaction', [PpobController::class, 'store'])->name('store');
});

// --- API / AJAX Utils (Public or mixed) ---
Route::get('/kirimaja/cek-ongkir', [CustomerOrderController::class, 'cek_Ongkir'])->name('kirimaja.cekongkir');
Route::get('/api/cari-alamat', [CustomerOrderController::class, 'searchAddressApi'])->name('api.address.search');
Route::get('/kontak/search', [KontakController::class, 'search'])->name('kontak.search'); // API Search kontak umum
Route::get('/api/contacts/search', [KontakController::class, 'search'])->name('api.contacts.search');

Route::get('/kiriminaja/search-address', function (Request $request, KiriminAjaService $kiriminAja) {
    $query = $request->get('q');
    if (!$query) {
        return response()->json(['status' => false, 'text' => 'Query tidak boleh kosong', 'data' => []]);
    }
    return response()->json($kiriminAja->searchAddress($query));
});

Route::get('/cek-ip-hosting', function () {
    try {
        $response = \Illuminate\Support\Facades\Http::withoutVerifying()->get('https://api.ipify.org?format=json');
        return response()->json([
            'message' => 'Copy IP di bawah ini dan masukkan ke Whitelist Digiflazz',
            'real_ip_hosting' => $response->json()['ip'],
            'keterangan' => 'Ini adalah IP asli yang digunakan server Anda untuk keluar.'
        ]);
    } catch (\Exception $e) {
        return "Gagal cek IP: " . $e->getMessage();
    }
});

Route::get('/controllers-list', function () {
    $files = File::allFiles(app_path('Http/Controllers'));
    $controllers = collect($files)->map(function ($file) {
        $relativePath = str_replace(app_path('Http/Controllers') . '/', '', $file->getPathname());
        return 'App\\Http\\Controllers\\' . str_replace(['/', '.php'], ['\\', ''], $relativePath);
    });
    return view('controllers-list', compact('controllers'));
});

// Test Routes
Route::get('/test/doku/simple', [TestOrderController::class, 'testSimplePayment'])->name('test.doku.simple');
Route::get('/test/doku/marketplace', [TestOrderController::class, 'testMarketplacePayment'])->name('test.doku.marketplace');


// =========================================================================
// 3. AUTHENTICATED ROUTES (COMMON)
// =========================================================================

Route::middleware(['auth', 'verified'])->group(function () {
    
    // --- Dashboard Redirection ---
    Route::get('/dashboard', function () {
        $user = auth()->user();
        if ($user->role === 'Admin') return redirect()->route('admin.dashboard');
        return redirect()->route('customer.dashboard');
    })->name('dashboard');

    // Dashboard Views
    Route::get('/customer/dashboard', function () {
        return view('dashboard');
    })->middleware(RoleMiddleware::class . ':Pelanggan')->name('customer.dashboard');

    Route::get('/admin/dashboard', function () {
        return view('admin.dashboard');
    })->middleware(RoleMiddleware::class . ':Admin')->name('admin.dashboard');

    Route::get('/seller/dashboard', function () {
        return view('seller.dashboard');
    })->middleware(RoleMiddleware::class . ':Seller')->name('seller.dashboard');

    // --- User Profile (Generic) ---
    Route::get('/user/profile', function () {
        return view('profile.show');
    })->name('profile.show');

    // --- Reviews ---
    Route::post('/reviews', [ProductReviewController::class, 'store'])->name('reviews.store');
    
    // Route khusus Seller Review (Diakses Auth)
    Route::get('/seller/reviews', [SellerReviewController::class, 'index'])->name('seller.reviews.index');
    Route::post('/seller/reviews/{review}/reply', [SellerReviewController::class, 'reply'])->name('seller.reviews.reply');
    Route::put('/seller/reviews/{review}/reply', [SellerReviewController::class, 'updateReply'])->name('seller.reviews.reply.update');
    Route::delete('/seller/reviews/{review}/reply', [SellerReviewController::class, 'deleteReply'])->name('seller.reviews.reply.delete');

    // --- Search Address ---
    Route::get('/search-address', [CekOngkirController::class, 'searchAddress'])->name('customer.search.address');
    Route::post('/check-cost', [CekOngkirController::class, 'checkCost'])->name('customer.check.cost');
    
    // --- Payment Generic ---
    Route::get('/bayar/{orderId}', [PaymentController::class, 'createPayment'])->name('payment.create');
    Route::get('/payment/finish', [PaymentController::class, 'finishPage'])->name('payment.finish');
    
    // --- DANA Payment ---
    Route::prefix('dana')->name('dana.')->group(function () {
        Route::get('/create-payment/{order}', [DanaController::class, 'createPayment'])->name('payment.create');
        Route::get('/payment-finish', [DanaController::class, 'handleFinishRedirect'])->name('payment.finish');
    });

    // --- PPOB Checkout Process (General Auth) ---
    Route::post('/checkout-ppob/prepare', [PpobCheckoutController::class, 'prepare'])->name('ppob.prepare');
    Route::get('/checkout-ppob', [PpobCheckoutController::class, 'index'])->name('ppob.checkout.index');
    Route::post('/checkout-ppob/process', [PpobCheckoutController::class, 'store'])->name('ppob.checkout.store');
    Route::get('/checkout-ppob/remove/{id}', [PpobCheckoutController::class, 'removeItem'])->name('ppob.cart.remove');
    Route::post('/checkout-ppob/clear', [PpobCheckoutController::class, 'clearCart'])->name('ppob.cart.clear');
    Route::get('/invoice/{invoice}', [PpobCheckoutController::class, 'invoice'])->name('ppob.invoice');
    
    // --- PPOB Digital Pages ---
    Route::prefix('digital')->name('ppob.')->group(function () {
        Route::post('/checkout', [PpobController::class, 'store'])->name('store');
        Route::get('/status/{ref_id}', [PpobController::class, 'checkStatus'])->name('status');
        Route::get('/cek-saldo', [PpobController::class, 'cekSaldo'])->name('cek-saldo'); // Khusus Admin biasanya, tapi ada di grup ini
        // AJAX
        Route::post('/ajax/check-pln-prabayar', [PpobController::class, 'checkPlnPrabayar'])->name('check.pln.prabayar');
        Route::post('/ajax/check-bill', [PpobController::class, 'checkBill'])->name('check.bill');
        Route::get('/ajax/pdam-products', [PpobController::class, 'getPdamProducts'])->name('ajax.pdam-products');
        // Dynamic Category (Must be last)
        Route::get('/kategori/{slug}', [PpobController::class, 'category'])->name('category');
    });

    // PPOB Sync Routes
    Route::prefix('ppob')->name('ppob.')->group(function () {
        Route::get('/sync/prepaid', [PpobProductController::class, 'syncPrepaid'])->name('sync.prepaid'); 
        Route::get('/sync/postpaid', [PpobProductController::class, 'syncPostpaid'])->name('sync.postpaid'); 
    });
    
    // Agent Cities
    Route::get('/agent/ppob/cities', [AgentTransactionController::class, 'getPbbCities'])->name('admin.ppob.get-pbb-cities');
});


// =========================================================================
// 4. CUSTOMER ROUTES (Prefix: customer)
// =========================================================================

Route::middleware(['auth', 'verified', RoleMiddleware::class . ':Pelanggan|Seller'])
    ->prefix('customer')->name('customer.')
    ->group(function () {

    // --- Include Customer Specific File ---
    require __DIR__.'/web/customer.php';

    // --- Marketplace & Cart ---
    Route::get('/marketplace', [CustomerMarketplaceController::class, 'index'])->name('marketplace.index');
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/add-ppob', [CartController::class, 'addPpob'])->name('cart.addPpob');
    Route::patch('/cart/update', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');

    // --- Checkout & Orders ---
    Route::get('/checkout', [CustomerCheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CustomerCheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/invoice/{invoice}', [CustomerCheckoutController::class, 'invoice'])->name('checkout.invoice');
    Route::get('/pesanan/export-pdf', [AdminPesananController::class, 'exportPdf'])->name('pesanan.export_pdf'); // Menggunakan AdminPesananController sesuai kode asli

    // --- [FIX] Multi-Koli Routes ---
    // Pastikan ini ada di sini agar terhindar dari 404
    Route::get('/pesanan/multi/create', [KoliController::class, 'create'])->name('koli.create');
    Route::get('/pesanan/create/multi-koli', [KoliController::class, 'create'])->name('koli.create'); // Alias untuk keamanan link lama
    Route::post('/pesanan/multi/store', [KoliController::class, 'store'])->name('koli.store');
    
    // Koli Helpers
    Route::post('/koli/store', [KoliController::class, 'store'])->name('koli.store'); // Redundant tapi kadang dipanggil tanpa 'multi'
    Route::post('/koli/store-single', [KoliController::class, 'storeSingle'])->name('koli.store_single');
    Route::post('/koli/cek-ongkir', [KoliController::class, 'cek_Ongkir'])->name('koli.cek_ongkir');

    // --- PPOB History ---
    Route::prefix('ppob')->name('ppob.')->group(function () {
        Route::get('/history', [PpobHistoryController::class, 'index'])->name('history');
        Route::get('/export/excel', [PpobHistoryController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [PpobHistoryController::class, 'exportPdf'])->name('export.pdf');
    });

    // --- Kontak & Address Book ---
    Route::get('/kontak/search', [CustomerKontakController::class, 'search'])->name('kontak.search'); // Customer specific search
    Route::prefix('kontak')->name('kontak.')->group(function () {
        Route::get('/', [CustomerKontakController::class, 'index'])->name('index');
        Route::post('/', [CustomerKontakController::class, 'store'])->name('store');
        Route::get('/{kontak}/edit', [CustomerKontakController::class, 'edit'])->name('edit');
        Route::put('/{kontak}', [CustomerKontakController::class, 'update'])->name('update');
        Route::delete('/{kontak}', [CustomerKontakController::class, 'destroy'])->name('destroy');
    });

    // --- Chat ---
    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/', [CustomerChatController::class, 'index'])->name('index');
        Route::get('/messages', [CustomerChatController::class, 'fetchMessages'])->name('fetchMessages');
        Route::post('/messages', [CustomerChatController::class, 'sendMessage'])->name('sendMessage');
    });

    // --- Notifications ---
    Route::get('/notifications/unread', [NotifikasiCustomerController::class, 'getUnread'])->name('notifications.unread');
    Route::get('/notifications', [NotifikasiCustomerController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotifikasiCustomerController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/read-all', [NotifikasiCustomerController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');

    // --- Seller Registration ---
    Route::get('/seller/register', [SellerRegisterController::class, 'create'])->name('seller.register.form');
    Route::post('/seller/register', [SellerRegisterController::class, 'store'])->name('seller.register.submit');
    
    // --- KiriminAja Address Search (Profile) ---
    Route::get('/api/kiriminaja/address-search', [CustomerProfileController::class, 'searchKiriminAjaAddress'])->name('kiriminaja.address_search');
});

// --- Rute Seller Registration Tambahan (Outside group for safety but guarded by auth) ---
Route::middleware(['auth'])->group(function() {
    Route::get('seller/address/search', [SellerRegisterController::class, 'searchAddressKiriminAja'])->name('seller.address.search');
    Route::post('seller/address/geocode', [SellerRegisterController::class, 'geocodeAddress'])->name('seller.address.geocode');
});


// =========================================================================
// 5. AGENT / SELLER ROUTES
// =========================================================================

Route::middleware(['auth'])->group(function () {
    // Agent Registration
    Route::get('/agent/register', [AgentRegistrationController::class, 'index'])->name('agent.register.index');
    Route::post('/agent/register/process', [AgentRegistrationController::class, 'register'])->name('agent.register.process');

    // TopUp
    Route::get('/topup', [TopUpController::class, 'index'])->name('topup.index');
    Route::post('/topup', [TopUpController::class, 'store'])->name('topup.store');
    Route::get('/topup/{topup}', [TopUpController::class, 'show'])->name('customer.topup.show');
    Route::post('/topup/{reference_id}/upload', [TopUpController::class, 'uploadProof'])->name('topup.upload_proof');

    // Agent Products (Middleware: is_agent)
    Route::middleware(['is_agent'])->prefix('agent')->name('agent.')->group(function () {
        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', [AgentProductController::class, 'index'])->name('index');
            Route::put('/update', [AgentProductController::class, 'update'])->name('update');
            Route::post('/bulk-update', [AgentProductController::class, 'bulkUpdate'])->name('bulk_update');
        });
        
        // Transaksi Offline / Kasir
        Route::get('/transaksi/create', [AgentTransactionController::class, 'create'])->name('transaction.create');
        Route::post('/transaksi/store', [AgentTransactionController::class, 'store'])->name('transaction.store');
    });
});

Route::middleware(['auth', RoleMiddleware::class . ':Seller|Admin'])
    ->prefix('seller')->name('seller.')
    ->group(function () {
        require __DIR__.'/web/seller.php';
    });


// =========================================================================
// 6. ADMIN ROUTES (Prefix: admin)
// =========================================================================

Route::middleware(['auth', RoleMiddleware::class . ':Admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // Include External Admin Routes
    require __DIR__.'/web/admin.php';
    require __DIR__.'/admin/orders.php';

    // --- Settings & Info ---
    Route::view('/setting', 'admin.setting')->name('settings');
    Route::get('/setting-info-pesanan', [AdminController::class, 'editInfoPesanan'])->name('info.edit');
    Route::post('/setting-info-pesanan', [AdminController::class, 'updateInfoPesanan'])->name('info.update');
    Route::get('/settings/api', [ApiSettingsController::class, 'index'])->name('settings.api.index');
    Route::put('/settings/api', [ApiSettingsController::class, 'update'])->name('settings.api.update');
    Route::post('/settings/api', [ApiSettingsController::class, 'toggle'])->name('settings.api.toggle');
    Route::get('/logs', [AdminLogController::class, 'showLogs'])->name('logs.show');
    Route::post('/logs/clear', [AdminLogController::class, 'clearLogs'])->name('logs.clear');

    // --- Users & Customers ---
    Route::resource('customers/data/pengguna', DataPenggunaController::class)->names('customers.data.pengguna');
    Route::resource('pelanggan', PelangganController::class);
    Route::prefix('pelanggan')->name('pelanggan.')->group(function () {
        Route::post('/import-excel', [PelangganController::class, 'importExcel'])->name('import.excel');
        Route::get('/export-excel', [PelangganController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export-pdf', [PelangganController::class, 'exportPdf'])->name('export.pdf');
    });
    Route::post('/users/{user}/toggle-freeze', [UserController::class, 'toggleFreeze'])->name('users.toggle-freeze');
    Route::get('/pengguna/export', [PenggunaController::class, 'export'])->name('customers.pengguna.export');

    // --- Products & Marketplace ---
    Route::resource('stores', AdminMarketplaceController::class)->names('stores');
    Route::resource('marketplace', MarketplaceController::class); // Handles index, store, show, update, destroy
    Route::get('products/{slug}/specifications', [ProductController::class, 'editSpecifications'])->name('products.edit.specifications');
    Route::put('products/{slug}/specifications', [ProductController::class, 'updateSpecifications'])->name('products.update.specifications');
    
    // --- Category Attributes ---
    Route::prefix('category-attributes')->name('category-attributes.')->group(function () {
        Route::get('/', [CategoryAttributeController::class, 'index'])->name('index');
        Route::post('/{category}', [CategoryAttributeController::class, 'store'])->name('store');
        Route::get('/{attribute}/edit', [CategoryAttributeController::class, 'edit'])->name('edit');
        Route::put('/{attribute}', [CategoryAttributeController::class, 'update'])->name('update');
        Route::delete('/{attribute}', [CategoryAttributeController::class, 'destroy'])->name('destroy');
    });
    Route::get('categories/{category}/attributes', [ProductController::class, 'getAttributes'])->name('categories.attributes');

    // --- AJAX Categories ---
    // Note: Removed 'admin.' from name inside group because group already adds it, but controller methods might expect names without admin? 
    // Keeping as per request logic: group uses 'admin.', so names become 'admin.categories.storeAjax'
    Route::post('/categories/ajax-store', [AdminCategoryController::class, 'storeAjax'])->name('categories.storeAjax');
    Route::delete('/categories/ajax-delete/{id}', [AdminCategoryController::class, 'destroyAjax'])->name('categories.destroyAjax');

    // --- Orders & Shipping (Koli, Courier, SPX) ---
    Route::get('/{resi}/cetak_thermal', [AdminPesananController::class, 'cetakResiThermal'])->name('cetak_thermal');
    Route::get('/pesanan/buat-multi', [AdminKoliController::class, 'create'])->name('pesanan.create_multi');
    Route::post('/pesanan/store-multi', [AdminKoliController::class, 'store'])->name('koli.store');
    Route::post('/pesanan/store-single', [AdminKoliController::class, 'storeSingle'])->name('koli.store_single');
    Route::post('/cek-ongkir', [AdminKoliController::class, 'cek_Ongkir'])->name('koli.cek_ongkir');
    
    // Courier
    Route::get('/couriers/search', [CourierController::class, 'search'])->name('api.couriers.search');
    Route::get('couriers/{id}/scan', [CourierController::class, 'showScanPage'])->name('couriers.scan');
    Route::get('couriers/{id}/track', [CourierController::class, 'trackLocation'])->name('couriers.track');
    Route::get('couriers/{id}/print', [CourierController::class, 'printDeliveryOrder'])->name('couriers.print');
    Route::resource('couriers', CourierController::class);

    // SPX Scan
    Route::resource('spx-scans', SpxScanController::class)->names('spx_scans');
    Route::get('/surat-jalan/monitor', [SpxScanController::class, 'showMonitorPage'])->name('suratjalan.monitor.index');
    Route::get('/surat-jalan/monitor/export-pdf', [SpxScanController::class, 'exportMonitorPdf'])->name('suratjalan.monitor.export_pdf');
    Route::get('/surat-jalan/{kode_surat_jalan}/download', [SpxScanController::class, 'downloadSuratJalan'])->name('suratjalan.download');
    Route::get('/spx-scans/create', [SpxScanController::class, 'create'])->name('spx_scans.create');
    
    // Barcode
    Route::get('/generate-barcode-zoom', [BarcodeController::class, 'generateBarcode'])->name('barcode.generate');

    // --- Finance (Laporan, COA, Wallet) ---
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

    // COA
    Route::get('coa/export/excel', [CoaController::class, 'exportExcel'])->name('coa.export.excel');
    Route::get('coa/export/pdf', [CoaController::class, 'exportPdf'])->name('coa.export.pdf');
    Route::get('coa/import', [CoaController::class, 'showImportForm'])->name('coa.import.form');
    Route::post('coa/import', [CoaController::class, 'importExcel'])->name('coa.import.excel');
    Route::get('coa/import/template', [CoaController::class, 'downloadTemplate'])->name('coa.import.template');
    Route::resource('coa', CoaController::class)->except(['show']);

    // --- PPOB Admin (Gabungan Transaksi & Produk) ---
    Route::prefix('ppob')->name('ppob.')->group(function () {
        // Data Transaksi
        Route::get('/data', [AdminPpobController::class, 'index'])->name('data.index');
        Route::get('/data/export/excel', [AdminPpobController::class, 'exportExcel'])->name('data.export.excel');
        Route::get('/data/export/pdf', [AdminPpobController::class, 'exportPdf'])->name('data.export.pdf');
        
        // Export Umum
        Route::get('/export/excel', [AdminPpobController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [AdminPpobController::class, 'exportPdf'])->name('export.pdf');
        
        // Produk
        Route::post('/bulk-update', [PpobProductController::class, 'bulkUpdate'])->name('bulk-update');
        Route::get('/product-export/excel', [PpobProductController::class, 'exportExcel'])->name('product.export.excel');
        Route::get('/export-excel', [PpobProductController::class, 'exportExcel'])->name('export-excel');
        Route::get('/export-pdf', [PpobProductController::class, 'exportPdf'])->name('export-pdf');
        Route::get('/{id}', [PpobProductController::class, 'show'])->name('show');
        Route::put('/update-price/{id}', [PpobProductController::class, 'updatePrice'])->name('update-price');
        
        // Transaksi & Deposit
        Route::post('/deposit', [AdminPpobController::class, 'requestDeposit'])->name('deposit');
        Route::get('/cek-saldo', [AdminPpobController::class, 'cekSaldo'])->name('cek-saldo');
        Route::post('/topup', [AdminPpobController::class, 'topup'])->name('topup');

        // Manage Transaction (CRUD)
        Route::get('/transaction/{id}', [AdminPpobController::class, 'show'])->name('transaction.show');
        Route::put('/transaction/{id}', [AdminPpobController::class, 'update'])->name('transaction.update');
        Route::delete('/transaction/{id}', [AdminPpobController::class, 'destroy'])->name('transaction.destroy');
        Route::get('/transaction/destroy/{id}', [AdminPpobController::class, 'destroy'])->name('transaction.destroy.get');

        // Digital Menu
        Route::get('/digital', [AdminPpobController::class, 'index'])->name('index'); 
        Route::get('/digital/{slug}', [AdminPpobController::class, 'category'])->name('category');
    });
    
    // --- Content (Blog, Banners, Slider) ---
    Route::get('/import/wordpress', [ImportController::class, 'showForm'])->name('import.wordpress.form');
    Route::post('/import/wordpress', [ImportController::class, 'handleImport'])->name('import.wordpress.handle');
    Route::get('/posts/{id}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::get('/post/{post:slug}', [PostController::class, 'show'])->name('posts.post-detail');
    
    Route::resource('banners', BannerController::class);
    
    Route::get('/sliders', [SliderController::class, 'index'])->name('sliders.index');
    Route::post('/sliders', [SliderController::class, 'store'])->name('sliders.store');
    Route::delete('/sliders/{slide}', [SliderController::class, 'destroy'])->name('sliders.destroy');

    // --- Reviews Management ---
    Route::resource('reviews', AdminReviewController::class);
    Route::post('reviews/{review}/reply', [AdminReviewController::class, 'reply'])->name('reviews.reply');

    // --- Wilayah Management ---
    Route::get('/wilayah', [WilayahController::class, 'index'])->name('wilayah.index');
    Route::prefix('wilayah')->name('wilayah.')->group(function() {
        Route::get('/', [WilayahController::class, 'index'])->name('index');
        // API Dynamic
        Route::get('/api/provinces', [WilayahController::class, 'getProvinces'])->name('api.provinces');
        Route::get('/api/regencies/{province}', [WilayahController::class, 'getRegencies'])->name('api.regencies');
        Route::get('/api/districts/{regency}', [WilayahController::class, 'getDistricts'])->name('api.districts');
        Route::get('/api/villages/{district}', [WilayahController::class, 'getVillages'])->name('api.villages');
        // CRUD
        Route::post('/', [WilayahController::class, 'store'])->name('store');
        Route::put('/{id}', [WilayahController::class, 'update'])->name('update');
        Route::delete('/{id}', [WilayahController::class, 'destroy'])->name('destroy');
        // Helpers
        Route::get('/province/{province}/regencies', [WilayahController::class, 'getKabupaten'])->name('kabupaten');
        Route::get('/regency/{regency}/districts', [WilayahController::class, 'getKecamatan'])->name('kecamatan');
        Route::get('/district/{district}/villages', [WilayahController::class, 'getDesa'])->name('desa');
    });

    // --- Kode Pos ---
    Route::get('/kode-pos', [KodePosController::class, 'index'])->name('kodepos.index');
    Route::post('/kode-pos/import', [KodePosController::class, 'import'])->name('kodepos.import');

    // --- Email & Chat ---
    Route::get('/imap', [EmailController::class, 'index'])->name('imap.index');
    Route::get('/imap/{id}', [EmailController::class, 'show'])->name('imap.show');
    Route::delete('/imap/{id}', [EmailController::class, 'destroy'])->name('imap.destroy');
    
    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/', [AdminChatController::class, 'index'])->name('index');
        Route::get('/start', [AdminChatController::class, 'start'])->name('start');
        Route::get('/search-users', [AdminChatController::class, 'searchUsers'])->name('searchUsers');
        Route::get('/messages/{user}', [AdminChatController::class, 'fetchMessages'])->name('messages');
        Route::post('/messages/{user}', [AdminChatController::class, 'sendMessage'])->name('send');
    });

    Route::get('/api/contacts/search', [AdminChatController::class, 'searchKontak'])->name('api.contacts.search'); // Perhatikan duplikat nama route api.contacts.search
});