<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

// Import Controller
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ScannerController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\MemberAuthController;
use App\Http\Controllers\DanaWidgetController;
use App\Models\Category; // Pastikan Model Category sudah di-import
use App\Http\Controllers\TenantPaymentController;

// Route ini otomatis dapat prefix /api
// URL akhir: https://app.tokosancaka.com/api/tenant/generate-payment
Route::post('/tenant/generate-payment', [TenantPaymentController::class, 'generateUrl']);
Route::get('/tenant/check-status', [App\Http\Controllers\TenantPaymentController::class, 'checkStatus']);

// Webhook KiriminAja (Method POST)
Route::post('/kiriminaja/webhook', [OrderController::class, 'handleWebhook']);

// Endpoint utilitas untuk men-setting URL Callback (Akses sekali saja)
Route::get('/kiriminaja/set-callback', [OrderController::class, 'setCallback']);


/*
|--------------------------------------------------------------------------
| API Routes untuk Sancaka POS (Electron Desktop)
|--------------------------------------------------------------------------
|
| Semua route di sini otomatis memiliki prefix "/api".
| Contoh: http://domain.com/api/products
|
*/

// =================================================================
// 1. PUBLIC ROUTES (Bisa diakses tanpa login/token khusus sementara)
// =================================================================

// --- PRODUK & KATEGORI ---
// List Produk untuk POS Desktop (JSON)
Route::get('/products-list', [ProductController::class, 'apiList']);
// Detail Produk
Route::get('/products/{product}', [ProductController::class, 'show']);
// List Kategori
Route::get('/categories', [CategoryController::class, 'index']);
// Scan Barcode (Cek Harga/Stok)
Route::get('/orders/scan-product', [OrderController::class, 'scanProduct']);
Route::get('/products/{product}/variants', [ProductController::class, 'getVariants']);

// Tambahkan route ini:
// Route::get('/categories', function () {
    // Ambil kategori yang aktif saja & urutkan sesuai keinginan
//    return Category::where('is_active', 1)->get();
//});

// GANTI BAGIAN INI (Sekitar baris 58-62)
Route::get('/categories', function (\Illuminate\Http\Request $request) {
    // 1. Ambil Subdomain
    $host = $request->getHost();
    $subdomain = explode('.', $host)[0];

    // 2. LOGIKA BARU: Cek "Kartu VIP" untuk 'apps'
    // Jika subdomain adalah 'apps' ATAU 'admin', kita anggap dia ID 1
    if ($subdomain === 'apps' || $subdomain === 'admin' || $subdomain === 'www') {
        $tenantId = 1;
    } else {
        // Jika bukan, baru cari di database
        $tenant = \Illuminate\Support\Facades\DB::table('tenants')
                    ->where('subdomain', $subdomain)
                    ->first();

        if (!$tenant) {
            return response()->json(['error' => 'Toko tidak ditemukan'], 404);
        }
        $tenantId = $tenant->id;
    }

    // 3. Ambil Kategori dengan ID yang sudah ditentukan
    return \App\Models\Category::withoutGlobalScopes()
                   ->where('tenant_id', $tenantId)
                   ->where('is_active', 1)
                   ->get();
});

// --- ORDERS / TRANSAKSI POS ---
// Simpan Order Baru (Checkout)
Route::post('/orders', [OrderController::class, 'store']); // Ini pengganti orders.store
// Cek Ongkir (RajaOngkir/Kurir)
Route::post('/orders/check-ongkir', [OrderController::class, 'checkShippingRates']);
// Cek Kupon
Route::post('/orders/check-coupon', [OrderController::class, 'checkCoupon']);
// Cari Lokasi (Kecamatan/Kelurahan)
Route::get('/orders/search-location', [OrderController::class, 'searchLocation']);
// Ambil Channel Pembayaran Tripay
Route::get('/orders/tripay-channels', [OrderController::class, 'getPaymentChannels']);
// Cetak Invoice (Biasanya mengembalikan URL PDF atau HTML View)
Route::get('/invoice/{orderNumber}', [OrderController::class, 'invoice']);

// --- CUSTOMER (PELANGGAN) ---
// Cari Customer (Autocomplete)
Route::get('/customers/search-api', [CustomerController::class, 'searchApi']);
// Simpan Customer Baru Cepat (Ajax/API)
Route::post('/customers/store-ajax', [CustomerController::class, 'storeAjax']);

// --- REMOTE SCANNER (HP ke Laptop) ---
// Menangani hasil scan dari HP
Route::post('/scan-process', [ScannerController::class, 'handleScan']);

// --- LOGGING ERROR DARI CLIENT ---
Route::post('/log-client-error', function (Request $request) {
    Log::error("DESKTOP APP ERROR: " . $request->input('message'), $request->input('context', []));
    return response()->json(['status' => 'logged']);
});


// =================================================================
// 2. AUTHENTICATION (LOGIN ADMIN/KASIR)
// =================================================================
// Anda perlu membuat method login yang mengembalikan Token (Sanctum)
// Jika belum ada, sementara gunakan Endpoint ini untuk verifikasi user/pass biasa.

// Route::post('/login', [AuthController::class, 'loginApi']);


// =================================================================
// 3. PROTECTED ROUTES (Butuh Login)
// =================================================================
// Gunakan middleware 'auth:sanctum' jika sudah setup Laravel Sanctum.
// Untuk tahap awal development Electron, kita buka dulu (group tanpa middleware ketat).

Route::group(['prefix' => 'admin'], function () {

    // CRUD PRODUK (Create, Update, Delete dari Desktop)
    Route::post('/products', [ProductController::class, 'store']);
    Route::post('/products/{product}', [ProductController::class, 'update']); // Gunakan POST untuk update file gambar
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    // Update Varian
    Route::post('/products/{product}/variants', [ProductController::class, 'updateVariants']);

    // LOGS
    Route::get('/logs', [\App\Http\Controllers\LogViewerController::class, 'index']);

    // LAPORAN
    Route::get('/reports', [ReportController::class, 'index']);
});


// =================================================================
// 4. DANA INTEGRATION & WEBHOOKS
// =================================================================

Route::post('/dana/notify', [OrderController::class, 'handleDanaWebhook']);

Route::prefix('dana')->group(function () {
    // Cek Saldo Merchant
    Route::post('/check-merchant-balance', [\App\Http\Controllers\DanaDashboardController::class, 'checkMerchantBalance']);
    // Topup Saldo User
    Route::post('/topup', [\App\Http\Controllers\DanaDashboardController::class, 'topupSaldo']);
});


// =================================================================
// 5. MEMBER API (Mobile App / Member Area)
// =================================================================

Route::prefix('member')->group(function () {
    // Auth Member
    Route::post('/login', [MemberAuthController::class, 'loginApi']); // Perlu buat method loginApi yang return JSON

    // Order History
    Route::get('/orders', [\App\Http\Controllers\MemberOrderController::class, 'indexApi']); // Perlu method return JSON
    Route::get('/orders/{id}', [\App\Http\Controllers\MemberOrderController::class, 'showApi']);

    // Dana Features for Member
    Route::post('/dana/check-status', [MemberAuthController::class, 'checkTopupStatus']);
    Route::post('/dana/bank-inquiry', [MemberAuthController::class, 'bankAccountInquiry']);
    Route::post('/dana/transfer-bank', [MemberAuthController::class, 'transferToBank']);
});
