<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Import semua controller yang dibutuhkan dari kedua file
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\PublicApiController;
use App\Http\Controllers\Api\KontakController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DatabaseCheckController;
use App\Http\Controllers\Admin\EmailController;
use App\Http\Controllers\Admin\EtalaseController;
use App\Http\Controllers\Admin\LaporanKeuanganController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SaldoRequestController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TokoController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WalletController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CekOngkirController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CourierController;
use App\Http\Controllers\Customer\LacakController;
use App\Http\Controllers\Customer\TopUpController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\EkspedisiController;
use App\Http\Controllers\KirimAjaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PesananController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\DokuPaymentController;
use App\Http\Controllers\TestOrderController;
use App\Http\Controllers\DokuWebhookController;
use App\Http\Controllers\DokuController;
use App\Http\Controllers\Api\OngkirApiController; // <-- IMPORT
use App\Http\Controllers\Customer\PesananController as CustomerPesananController; // ALIAS
use App\Http\Controllers\TelegramPpobController;
use App\Http\Controllers\Api\ScraperController;

// Website fontend WA Integration
//use App\Http\Controllers\WhatsappController;


// Endpoint untuk DOKU Notification
// Route::post('/doku/notify', [TopUpController::class, 'dokuNotify'])->name('doku.notify');
// Route::post('/doku/notify', [DokuWebhookController::class, 'handle'])->name('doku.notify');

Route::get('/cek-koneksi', function() {
    return "Rute API Aktif!";
});

Route::match(['post', 'options'], '/import-scraper', [ScraperController::class, 'store']);


Route::post('/telegram/webhook', [TelegramPpobController::class, 'handle']);


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rute untuk mencari alamat (GET)
Route::get('/search-address', [OngkirApiController::class, 'searchAddress'])->name('ongkir.search-address');

// Rute untuk mengecek ongkir (POST)
Route::post('/check-cost', [OngkirApiController::class, 'checkCost'])->name('ongkir.check-cost');

// --- Rute untuk Virtual Account ---
Route::post('/doku/va/create', [DokuController::class, 'createVirtualAccount']);
Route::post('/doku/va/update', [DokuController::class, 'updateVirtualAccount']);
Route::post('/doku/va/delete', [DokuController::class, 'deleteVirtualAccount']);
Route::post('/doku/va/check-status', [DokuController::class, 'checkStatusVirtualAccount']);

// PERBAIKAN FONTTE: Ditempatkan di sini, tanpa prefix 'api'
Route::post('whatsapp/send-resi', [CustomerPesananController::class, 'sendResiViaWhatsappApi'])
    ->name('api.whatsapp.send_resi');

// --- Rute untuk Account Binding ---
Route::post('/doku/account-binding', [DokuController::class, 'accountBinding']);
Route::post('/doku/account-unbinding', [DokuController::class, 'accountUnbinding']);

// --- Rute untuk Card Registration ---
Route::post('/doku/card-registration', [DokuController::class, 'cardRegistration']);
Route::post('/doku/card-unbinding', [DokuController::class, 'cardUnbinding']);

// --- Rute untuk Payment ---
Route::post('/doku/payment/direct-debit', [DokuController::class, 'paymentDirectDebit']);
Route::post('/doku/payment/jump-app', [DokuController::class, 'paymentJumpApp']);

// --- Rute untuk Operasi Lainnya ---
Route::post('/doku/transaction/check-status', [DokuController::class, 'checkTransactionStatus']);
Route::post('/doku/transaction/refund', [DokuController::class, 'refundTransaction']);
Route::post('/doku/transaction/balance-inquiry', [DokuController::class, 'balanceInquiry']);

// --- RUTE INBOUND DARI DOKU (Webhook) ---
// Ini adalah endpoint yang akan DIHIT DOKU, bukan yang Anda hit.
Route::post('/doku/webhook/direct-inquiry', [DokuController::class, 'handleDirectInquiry']);
Route::post('/doku/webhook/payment-notification', [DokuController::class, 'handlePaymentNotification']);

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rute API yang telah digabungkan untuk aplikasi Anda.
|
*/

Route::post('/webhook/doku-jokul', [DokuWebhookController::class, 'handle'])
     ->name('webhook.doku.jokul');

// --- RUTE WEBHOOK & CALLBACK (PUBLIK) ---
Route::get('/kiriminaja/set-callback', [KirimAjaController::class, 'setCallback']);
Route::post('/webhook/kiriminaja', [KirimAjaController::class, 'handle'])->name('api.callback.kirimaja');
Route::post('/callback/tripay', [CheckoutController::class, 'TripayCallback'])->name('api.callback.tripay');


// URL lengkapnya akan menjadi: https://tokosancaka.com/api/payment/callback
Route::post('/payment/callback', [DokuPaymentController::class, 'callbackHandler'])->name('doku.callback');

// --- RUTE PUBLIK (Tidak Perlu Login) ---

// Autentikasi
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [ResetPasswordController::class, 'reset']);

// Produk, Blog, & Kategori
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/blogs', [BlogController::class, 'index']);
Route::get('/blogs/{blog}', [BlogController::class, 'show']);
Route::get('/kontak/search', [KontakController::class, 'search'])->name('api.search.kontak');

// Cek Ongkir & Wilayah
Route::post('/ongkir/cost', [CekOngkirController::class, 'checkCost'])->name('api.ongkir.cost.check');
Route::get('/ongkir/address/search', [CekOngkirController::class, 'searchAddress'])->name('api.ongkir.address.search');

// Rute Publik Lainnya
Route::get('/tracking/{nomor_resi}', [TrackingController::class, 'track']);
Route::post('/packages/update-status', [CourierController::class, 'updatePackageStatus'])->name('api.packages.update_status');
Route::post('/couriers/{id}/location', [CourierController::class, 'updateLocation'])->name('api.couriers.location');

// Data Wilayah (dari PublicApiController)
Route::get('/wilayah/provinces', [PublicApiController::class, 'getProvinces'])->name('api.wilayah.provinces');
Route::get('/wilayah/kabupaten/{province}', [PublicApiController::class, 'getKabupaten'])->name('api.wilayah.kabupaten');
Route::get('/wilayah/kecamatan/{regency}', [PublicApiController::class, 'getKecamatan'])->name('api.wilayah.kecamatan');
Route::get('/kodepos/by-district/{district}', [PublicApiController::class, 'getDesaByDistrict'])->name('api.kodepos.by-district');
Route::get('/kodepos/public-search', [PublicApiController::class, 'searchKodePos'])->name('api.kodepos.public.search');

// Data Wilayah (dari LocationController)
Route::prefix('locations')->group(function () {
    Route::get('/provinces', [LocationController::class, 'getProvinces']);
    Route::get('/regencies/{province_id}', [LocationController::class, 'getRegencies']);
    Route::get('/districts/{regency_id}', [LocationController::class, 'getDistricts']);
    Route::get('/villages/{district_id}', [LocationController::class, 'getVillages']);
});


// --- RUTE TERAUTENTIKASI (Perlu Login) ---
Route::middleware('auth:sanctum')->group(function () {
    // Pengguna & Autentikasi
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', fn(Request $request) => $request->user());
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Profil Pengguna
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile', [ProfileController::class, 'update']);

    Route::post('/checkout', [CheckoutController::class, 'process']);

    // Keranjang Belanja (Cart)
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'store']);
    Route::put('/cart/update/{cartId}', [CartController::class, 'update']);
    Route::delete('/cart/remove/{cartId}', [CartController::class, 'destroy']);


    Route::post('/topup', [TopUpController::class, 'store']);

    // Pengiriman & Ekspedisi
    Route::get('/lacak-pesanan/{orderId}', [LacakController::class, 'trackOrder']);

    // Notifikasi & Scan
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::post('/scan', [ScanController::class, 'processScan']);

    // Kontak

    // --- RUTE KHUSUS ADMIN ---
    Route::prefix('admin')->name('api.admin.')->middleware('role:admin')->group(function () {

        Route::get('/database-check', [DatabaseCheckController::class, 'check']);

        // Rute Email Admin
        Route::get('/email', [EmailController::class, 'fetchEmails'])->name('email.fetch');
        Route::post('/email/send', [EmailController::class, 'send'])->name('email.send');
        Route::get('/email/{id}', [EmailController::class, 'show'])->name('email.show');
        Route::patch('/email/{id}', [EmailController::class, 'update'])->name('email.update');
        Route::delete('/email/{id}', [EmailController::class, 'destroy'])->name('email.destroy');
    });

});

