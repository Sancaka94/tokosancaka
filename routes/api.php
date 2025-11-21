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
//use App\Http\Controllers\TripayCallbackController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rute API yang telah digabungkan untuk aplikasi Anda.
|
*/

// --- RUTE WEBHOOK & CALLBACK (PUBLIK) ---
Route::post('/webhook/kiriminaja', [KirimAjaController::class, 'handle'])->name('api.callback.kirimaja');
Route::post('/callback/tripay', [CheckoutController::class, 'TripayCallback'])->name('api.callback.tripay');

//Route::post('/callback/tripay', [TripayCallbackController::class, 'handle']);

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

    // Manajemen Toko & Produk
    Route::apiResource('/stores', TokoController::class);
    Route::apiResource('/products', ProductController::class)->except(['index', 'show']);
    Route::apiResource('/categories', CategoryController::class)->except(['index']);
    Route::apiResource('/etalase', EtalaseController::class);

    // Pesanan & Checkout
    Route::apiResource('/orders', PesananController::class);
    Route::apiResource('/customer-orders', CustomerOrderController::class);
    Route::post('/checkout', [CheckoutController::class, 'process']);

    // Keranjang Belanja (Cart)
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'store']);
    Route::put('/cart/update/{cartId}', [CartController::class, 'update']);
    Route::delete('/cart/remove/{cartId}', [CartController::class, 'destroy']);

    // Chat
    Route::apiResource('/chats', ChatController::class);

    // Keuangan & Wallet
    Route::apiResource('/wallets', WalletController::class);
    Route::apiResource('/saldo-requests', SaldoRequestController::class);
    Route::apiResource('/laporan-keuangan', LaporanKeuanganController::class);
    Route::post('/topup', [TopUpController::class, 'store']);

    // Pengiriman & Ekspedisi
    Route::apiResource('/ekspedisi', EkspedisiController::class);
    Route::get('/lacak-pesanan/{orderId}', [LacakController::class, 'trackOrder']);

    // Notifikasi & Scan
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::post('/scan', [ScanController::class, 'processScan']);

    // Kontak
    Route::apiResource('/kontak', KontakController::class);

    // --- RUTE KHUSUS ADMIN ---
    Route::prefix('admin')->name('api.admin.')->middleware('role:admin')->group(function () {
        Route::apiResource('/users', UserController::class);
        Route::apiResource('/roles', RoleController::class);
        Route::apiResource('/activity-logs', ActivityLogController::class);
        Route::apiResource('/settings', SettingController::class);
        Route::get('/database-check', [DatabaseCheckController::class, 'check']);

        // Rute Email Admin
        Route::get('/email', [EmailController::class, 'fetchEmails'])->name('email.fetch');
        Route::post('/email/send', [EmailController::class, 'send'])->name('email.send');
        Route::get('/email/{id}', [EmailController::class, 'show'])->name('email.show');
        Route::patch('/email/{id}', [EmailController::class, 'update'])->name('email.update');
        Route::delete('/email/{id}', [EmailController::class, 'destroy'])->name('email.destroy');
    });

    // --- RUTE MODUL PONDOK ---
    Route::prefix('pondok')->name('pondok.')->middleware('role:admin_pondok')->group(function() {
        Route::apiResource('santri', App\Http\Controllers\Pondok\Admin\SantriController::class);
        Route::apiResource('pegawai', App\Http\Controllers\Pondok\Admin\PegawaiController::class);
        Route::apiResource('kelas', App\Http\Controllers\Pondok\Admin\KelasController::class);
        Route::apiResource('kamar', App\Http\Controllers\Pondok\Admin\KamarController::class);
        Route::apiResource('mata-pelajaran', App\Http\Controllers\Pondok\Admin\MataPelajaranController::class);
        Route::apiResource('jadwal-pelajaran', App\Http\Controllers\Pondok\Admin\JadwalPelajaranController::class);
        Route::apiResource('absensi-santri', App\Http\Controllers\Pondok\Admin\AbsensiSantriController::class);
        Route::apiResource('izin-santri', App\Http\Controllers\Pondok\Admin\IzinSantriController::class);
        Route::apiResource('pelanggaran-santri', App\Http\Controllers\Pondok\Admin\PelanggaranSantriController::class);
        Route::apiResource('pembayaran-santri', App\Http\Controllers\Pondok\Admin\PembayaranSantriController::class);
        Route::apiResource('tagihan-santri', App\Http\Controllers\Pondok\Admin\TagihanSantriController::class);
        Route::apiResource('tabungan-santri', App\Http\Controllers\Pondok\Admin\TabunganSantriController::class);
        Route::apiResource('tahfidz-progress', App\Http\Controllers\Pondok\Admin\TahfidzProgressController::class);
        Route::apiResource('jurnal-umum', App\Http\Controllers\Pondok\Admin\JurnalUmumController::class);
        Route::apiResource('tahun-ajaran', App\Http\Controllers\Pondok\Admin\TahunAjaranController::class);
    });
});

