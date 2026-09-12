<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Api;
use App\Models\User;
use App\Models\ShiftKas;
use App\Models\ShiftFlow;
use App\Services\DanaSignatureService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PosApiController extends Controller
{
    protected $danaSignature;

    public function __construct(DanaSignatureService $danaSignature)
    {
        $this->danaSignature = $danaSignature;
        // Inisialisasi Config DANA saat controller dipanggil
        $this->applyDynamicConfig(); 
    }

    public function getProducts(Request $request)
    {
        try {
            $user = Auth::user() ?? auth('sanctum')->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized - Token tidak valid'], 401);
            }

            // 🔥 KUNCI PERBAIKAN: Gunakan parent_id jika yang login adalah Karyawan
            $storeId = $user->parent_id ?? $user->id_pengguna ?? $user->id;

            // Fokus pencarian berdasarkan store_id pemilik
            $query = Product::where('store_id', $storeId)
                            ->where('status', 'active');
            
            if ($request->filled('search')) {
                $keyword = trim($request->search); 
                $query->where(function($q) use ($keyword) {
                    $q->where('name', 'like', '%' . $keyword . '%')
                      ->orWhere('sku', 'like', '%' . $keyword . '%'); 
                });
            }

            $products = $query->latest()->get();

            $products->map(function ($item) {
                $imagePath = $item->image_url ?? $item->image ?? $item->foto ?? null;
                if ($imagePath && strpos($imagePath, 'http') !== 0) {
                    $item->full_image_url = asset('storage/' . ltrim($imagePath, '/'));
                } else {
                    $item->full_image_url = $imagePath;
                }
                return $item;
            });

            return response()->json(['success' => true, 'data' => $products]);
        } catch (\Throwable $e) { 
            return response()->json(['success' => false, 'message' => 'ERROR ASLI: ' . $e->getMessage() . ' | Baris: ' . $e->getLine()], 500);
        }
    }

    public function processTransaction(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|string',
            'amount_paid'    => 'required|numeric',
            'items'          => 'required|array|min:1',
        ]);

        $user = Auth::user() ?? auth('sanctum')->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

        DB::beginTransaction();
        try {
            $invoiceNumber = 'POS-' . date('YmdHis') . rand(10, 99);
            $grandTotal = collect($request->items)->sum(function($item) {
                return $item['price'] * $item['qty'];
            });

            $isQris = strtoupper($request->payment_method) === 'QRIS';
            $status = $isQris ? 'pending' : 'paid';

            // 🔥 KUNCI PERBAIKAN: Pesanan menjadi milik Toko (Owner)
            $storeId = $user->parent_id ?? $user->id_pengguna ?? $user->id;

            // 🔥 TANGKAP NAMA OPERATOR / KARYAWAN YANG SEDANG LOGIN
            $operatorName = $user->nama_lengkap ?? 'Kasir';

            $order = Order::create([
                'invoice_number'   => $invoiceNumber,
                'user_id'          => $storeId, // ID Pemilik Toko
                'subtotal'         => $grandTotal,
                'shipping_cost'    => 0, 
                'shipping_method'  => 'Di Tempat (POS)',
                // 🔥 SIMPAN NAMA KASIR DI SINI AGAR TIDAK PERLU UBAH DATABASE
                'shipping_address' => $operatorName, 
                'total_amount'     => $grandTotal,
                'payment_method'   => $request->payment_method,
                'status'           => $status, 
                'type'             => 'pos'   
            ]);

            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item['id'],
                    'quantity'   => $item['qty'],
                    'price'      => $item['price']
                ]);

                // Kurangi stok produk secara langsung
                Product::where('id', $item['id'])->decrement('stock', $item['qty']);
            }

            $paymentUrl = null;

            // Jika QRIS, request ke DANA MPM Generate API
            if ($isQris) {
                // Panggil Helper MPM QRIS
                $danaRes = $this->generateDanaQrisMpm($invoiceNumber, $grandTotal);
                
                if (!$danaRes['success']) {
                    throw new \Exception($danaRes['message']);
                }
                
                // Gunakan qr_content (Raw String) atau qr_image (Base64)
                $paymentUrl = $danaRes['qr_content'] ?? $danaRes['qr_image']; 
                
                // Simpan data QRIS ke database agar bisa dimuat ulang di Riwayat Kasir
                $order->payment_url = $paymentUrl;
                $order->save();
            }

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => $isQris ? 'Menunggu Pembayaran QRIS DANA' : 'Transaksi berhasil!',
                'data' => [
                    'invoice'     => $invoiceNumber, 
                    'total'       => $grandTotal,
                    'payment_url' => $paymentUrl
                ]
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    public function getHistory(Request $request)
    {
        try {
            $user = Auth::user() ?? auth('sanctum')->user();
            if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

            // 🔥 KUNCI PERBAIKAN: Gunakan ID Toko
            $storeId = $user->parent_id ?? $user->id_pengguna ?? $user->id;

            $history = Order::with(['items.product'])
                ->where('user_id', $storeId) // <--- Ubah ini jadi $storeId
                ->where('shipping_method', 'Di Tempat (POS)')
                ->orderBy('created_at', 'desc')
                ->get();

            $history->map(function ($order) {
                if ($order->items) {
                    $order->items->map(function ($item) {
                        if ($item->product) {
                            $imagePath = $item->product->image_url ?? $item->product->image ?? $item->product->foto ?? null;
                            if ($imagePath && strpos($imagePath, 'http') !== 0) {
                                $item->product->full_image_url = asset('storage/' . ltrim($imagePath, '/'));
                            } else {
                                $item->product->full_image_url = $imagePath;
                            }
                        }
                        return $item;
                    });
                }
                return $order;
            });

            return response()->json(['success' => true, 'data' => $history]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

   // ==============================================
    // CRUD STOK PRODUK
    // ==============================================
    public function storeProduct(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'price' => 'required|numeric', // Harga Jual
            'modal' => 'required|numeric', // 🔥 Harga Beli (Modal)
            'stock' => 'required|numeric',
            'sku'   => 'nullable|string|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $user = Auth::user() ?? auth('sanctum')->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

        try {
            $slug = \Illuminate\Support\Str::slug($request->name) . '-' . uniqid();
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('products', 'public');
            }

            $product = Product::create([
                'store_id'    => $user->id_pengguna ?? $user->id,
                'seller_name' => $user->nama_lengkap,
                'name'        => $request->name,
                'price'       => $request->price,
                'modal'       => $request->modal, // 🔥 Simpan modal
                'stock'       => $request->stock,
                'sku'         => $request->sku,
                'image_url'   => $imagePath,
                'status'      => 'active',
                'slug'        => $slug
            ]);

            return response()->json(['success' => true, 'message' => 'Produk berhasil ditambahkan', 'data' => $product]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    public function updateProduct(Request $request, $id)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'price' => 'required|numeric', // Harga Jual
            'modal' => 'required|numeric', // 🔥 Harga Beli (Modal)
            'stock' => 'required|numeric',
            'sku'   => 'nullable|string|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $user = Auth::user() ?? auth('sanctum')->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

        try {
            $product = Product::find($id);
            if (!$product) return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan'], 404);

            $updateData = [
                'name'  => $request->name,
                'price' => $request->price,
                'modal' => $request->modal, // 🔥 Update modal
                'stock' => $request->stock,
                'sku'   => $request->sku
            ];

            if ($request->hasFile('image')) {
                $updateData['image_url'] = $request->file('image')->store('products', 'public');
            }

            $product->update($updateData);

            return response()->json(['success' => true, 'message' => 'Produk berhasil diperbarui', 'data' => $product]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    public function destroyProduct($id)
    {
        $user = Auth::user() ?? auth('sanctum')->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

        try {
            $product = Product::find($id);
            if (!$product) return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan'], 404);

            $product->delete();
            return response()->json(['success' => true, 'message' => 'Produk berhasil dihapus']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    public function bulkDestroyProducts(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);

        $user = Auth::user() ?? auth('sanctum')->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

        try {
            Product::whereIn('id', $request->ids)->delete();
            return response()->json(['success' => true, 'message' => count($request->ids) . ' produk berhasil dihapus']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

   // ==============================================
    // HELPER: DANA GATEWAY (GAPURA IPG FULL)
    // ==============================================
    private function createDanaPaymentGateway($invoiceNumber, $amount, $user, $paymentMethod = 'ALL')
    {
        Log::info('[API MOBILE - POS] Memulai request DANA untuk POS: ' . $invoiceNumber);

        $timestamp = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $validUpTo = \Carbon\Carbon::now('Asia/Jakarta')->addMinutes(30)->format('Y-m-d\TH:i:sP');
        $amountValue = number_format((float)$amount, 2, '.', '');

        $path = '/payment-gateway/v1.0/debit/payment-host-to-host.htm';

        $body = [
            "partnerReferenceNo" => (string) $invoiceNumber,
            "merchantId"         => config('services.dana.merchant_id'),
            "amount"             => [
                "value"    => $amountValue,
                "currency" => "IDR"
            ],
            "validUpTo"          => $validUpTo,
            "urlParams"          => [
                [
                    "url"        => url('/pos/success?trx_id='.$invoiceNumber),
                    "type"       => "PAY_RETURN",
                    "isDeeplink" => "N"
                ],
                [
                    "url"        => url('/dana/notify'),
                    "type"       => "NOTIFICATION",
                    "isDeeplink" => "N"
                ]
            ],
            "additionalInfo"     => [
                "order"   => [
                    "orderTitle"        => substr("POS - " . $invoiceNumber, 0, 64),
                    "merchantTransType" => "01", 
                    "scenario"          => "REDIRECT",
                    "buyer"             => [
                        "externalUserId"   => (string) ($user->id_pengguna ?? 'GUEST' . rand(100,999)),
                        "externalUserType" => "MERCHANT_USER",
                        "nickname"         => substr($user->nama_lengkap ?? 'Customer', 0, 40)
                    ]
                ],
                "mcc"     => "5732",
                "envInfo" => [
                    "sourcePlatform"    => "IPG",
                    "terminalType"      => "SYSTEM",
                    "orderTerminalType" => "WEB" 
                ]
            ]
        ];

        // WAJIB UNTUK QRIS: Tambahkan externalStoreId dan tentukan payMethod
        if (strtoupper($paymentMethod) === 'QRIS') {
            // Gunakan Store ID dari DANA Sandbox/Production Anda
            $body['externalStoreId'] = config('services.dana.external_store_id', '216620010023027154781'); 
            $body['payOptionDetails'] = [
                [
                    "payMethod" => "QR_CODE"
                ]
            ];
        }

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            $accessToken = $this->danaSignature->getAccessToken();
            $signature   = $this->danaSignature->generateSignature('POST', $path, $jsonBody, $timestamp);
            $baseUrl     = config('services.dana.base_url');

            $headers = [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'ORIGIN'        => config('services.dana.origin'),
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time() . \Illuminate\Support\Str::random(6),
                'CHANNEL-ID'    => '95221'
            ];

            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post($baseUrl . $path);

            $result = $response->json();

            if (isset($result['responseCode']) && $result['responseCode'] === '2005400') {
                
                // 1. Tangkap Raw String QRIS (Jika metode = QRIS)
                if (isset($result['additionalInfo']['paymentCode'])) {
                    return [
                        'success' => true, 
                        'redirect_url' => $result['additionalInfo']['paymentCode'], // Berisi String QRIS (000201010212...)
                        'is_qris' => true
                    ];
                }

                // 2. Tangkap URL Web (Jika metode = ALL / E-Wallet)
                $redirectUrl = $result['appLinkUrl'] ?? $result['webRedirectUrl'] ?? null;
                if (!empty($redirectUrl)) {
                    return [
                        'success' => true, 
                        'redirect_url' => $redirectUrl,
                        'is_qris' => false
                    ];
                }
            }

            return ['success' => false, 'message' => $result['responseMessage'] ?? 'Terjadi kesalahan sistem DANA'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Koneksi DANA Error: ' . $e->getMessage()];
        }
    }

    private function applyDynamicConfig()
    {
        $settings = Api::pluck('value', 'key')->toArray();
        $isProduction = ($settings['dana_production_mode'] ?? '0') == '1';

        if ($isProduction) {
            config([
                'services.dana.dana_env'      => 'PRODUCTION',
                'services.dana.base_url'      => 'https://api.saas.dana.id',
                'services.dana.merchant_id'   => $settings['dana_prod_merchant_id'] ?? env('DANA_PROD_MERCHANT_ID'),
                'services.dana.external_store_id' => $settings['dana_prod_store_id'] ?? env('DANA_PROD_STORE_ID'), // Tambahkan baris ini
                'services.dana.client_id'     => $settings['dana_prod_client_id'] ?? env('DANA_PROD_CLIENT_ID'),
                'services.dana.x_partner_id'  => $settings['dana_prod_client_id'] ?? env('DANA_PROD_CLIENT_ID'),
                'services.dana.private_key'   => $settings['dana_prod_private_key'] ?? env('DANA_PROD_PRIVATE_KEY'),
                'services.dana.client_secret' => $settings['dana_prod_client_secret'] ?? env('DANA_PROD_CLIENT_SECRET'),
                'services.dana.origin'        => env('DANA_ORIGIN', 'https://tokosancaka.com'),
            ]);
        } else {
            config([
                'services.dana.dana_env'      => 'SANDBOX',
                'services.dana.base_url'      => 'https://api.sandbox.dana.id',
                'services.dana.merchant_id'   => $settings['dana_sandbox_merchant_id'] ?? env('DANA_MERCHANT_ID'),
                'services.dana.external_store_id' => $settings['dana_sandbox_store_id'] ?? env('DANA_STORE_ID'), // Tambahkan baris ini
                'services.dana.client_id'     => $settings['dana_sandbox_client_id'] ?? env('DANA_X_PARTNER_ID'),
                'services.dana.x_partner_id'  => $settings['dana_sandbox_client_id'] ?? env('DANA_X_PARTNER_ID'),
                'services.dana.private_key'   => $settings['dana_sandbox_private_key'] ?? env('DANA_PRIVATE_KEY'),
                'services.dana.client_secret' => $settings['dana_sandbox_client_secret'] ?? env('DANA_CLIENT_SECRET'),
                'services.dana.origin'        => env('DANA_ORIGIN', 'https://tokosancaka.com'),
            ]);
        }
    }

    // ==============================================
    // HELPER: DANA GATEWAY (QRIS MPM GENERATE)
    // ==============================================
    private function generateDanaQrisMpm($invoiceNumber, $amount)
    {
        Log::info('[API MOBILE - POS] === MULAI REQUEST DANA MPM ===');
        Log::info('Invoice: ' . $invoiceNumber . ' | Amount: ' . $amount);

        $timestamp = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $validityPeriod = \Carbon\Carbon::now('Asia/Jakarta')->addMinutes(30)->format('Y-m-d\TH:i:sP');
        $amountValue = number_format((float)$amount, 2, '.', '');

        $path = '/v1.0/qr/qr-mpm-generate.htm';
        
        // Ambil konfigurasi
        $merchantId = config('services.dana.merchant_id');
        $storeId = config('services.dana.external_store_id', '216620010023027154781');

        $body = [
            "merchantId"         => $merchantId,
            "storeId"            => $storeId,
            "partnerReferenceNo" => (string) $invoiceNumber,
            "amount"             => [
                "value"    => $amountValue,
                "currency" => "IDR"
            ],
            "validityPeriod"     => $validityPeriod,
            "additionalInfo"     => [
                "envInfo" => [
                    "sourcePlatform"    => "IPG",
                    "terminalType"      => "SYSTEM",
                    "orderTerminalType" => "APP"
                ]
            ]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            $accessToken = $this->danaSignature->getAccessToken();
            $signature   = $this->danaSignature->generateSignature('POST', $path, $jsonBody, $timestamp);
            $baseUrl     = config('services.dana.base_url');

            $headers = [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'ORIGIN'        => config('services.dana.origin'),
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time() . \Illuminate\Support\Str::random(6),
                'CHANNEL-ID'    => '95221'
            ];

            // LOG PAYLOAD & HEADERS SEBELUM KIRIM
            Log::info('DANA_REQUEST_URL: ' . $baseUrl . $path);
            Log::info('DANA_REQUEST_HEADERS: ', $headers);
            Log::info('DANA_REQUEST_BODY: ' . $jsonBody);

            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post($baseUrl . $path);

            // LOG RAW RESPONSE DARI DANA
            Log::info('DANA_RESPONSE_STATUS: ' . $response->status());
            Log::info('DANA_RESPONSE_BODY: ' . $response->body());
            Log::info('[API MOBILE - POS] === SELESAI REQUEST DANA MPM ===');

            $result = $response->json();

            if (isset($result['responseCode']) && $result['responseCode'] === '2004700') {
                return [
                    'success' => true,
                    'qr_content' => $result['qrContent'] ?? null, 
                    'qr_image'   => $result['qrImage'] ?? null,   
                    'qr_url'     => $result['qrUrl'] ?? null      
                ];
            }

            return ['success' => false, 'message' => $result['responseMessage'] ?? 'Gagal generate QRIS DANA'];

        } catch (\Exception $e) {
            Log::error('DANA_EXCEPTION: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Koneksi DANA Error: ' . $e->getMessage()];
        }
    }

    public function downloadQris($invoice)
    {
        $order = \App\Models\Order::where('invoice_number', $invoice)->first();

        if (!$order || !$order->payment_url) {
            return response('QRIS tidak ditemukan', 404);
        }

        // 🔥 1. BERSIHKAN SAMPAH SPASI (Ini yang sering bikin gambar rusak/corrupt)
        if (ob_get_level()) {
            ob_end_clean();
        }

        try {
            // 🔥 2. Coba Generate dalam bentuk PNG
            $image = QrCode::format('png')
                           ->size(400)
                           ->margin(2)
                           ->generate($order->payment_url);

            return response($image)
                    ->header('Content-type', 'image/png')
                    ->header('Content-Disposition', 'attachment; filename="QRIS_'.$invoice.'.png"');
                    
        } catch (\Exception $e) {
            // 🔥 3. JIKA GAGAL (karena ekstensi Imagick di hosting mati), OTOMATIS GANTI KE SVG
            // SVG adalah format gambar vektor web yang pasti jalan di semua server tanpa ekstensi tambahan
            
            $image = QrCode::size(400)
                           ->margin(2)
                           ->generate($order->payment_url);

            return response($image)
                    ->header('Content-type', 'image/svg+xml')
                    ->header('Content-Disposition', 'attachment; filename="QRIS_'.$invoice.'.svg"');
        }
    }

    public function getReport(Request $request)
    {
        try {
            $user = Auth::user() ?? auth('sanctum')->user();
            if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

            // 🔥 KUNCI PERBAIKAN: Gunakan ID Toko
            $storeId = $user->parent_id ?? $user->id_pengguna ?? $user->id;
            $filter = $request->query('filter', 'Minggu Ini');
            
            // Ambil data dari database (Khusus tipe POS & Status Lunas)
            $query = Order::with('items.product')
                        ->where('user_id', $storeId) // <--- Ubah ini jadi $storeId
                        ->where('shipping_method', 'Di Tempat (POS)')
                        ->where('status', 'paid');

            $now = \Carbon\Carbon::now('Asia/Jakarta');
            
            // Filter berdasarkan tanggal
            if ($filter === 'Hari Ini') {
                $query->whereDate('created_at', $now->toDateString());
            } elseif ($filter === 'Minggu Ini') {
                $query->whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]);
            } elseif ($filter === 'Bulan Ini') {
                $query->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year);
            }

            $orders = $query->get();

            $totalPendapatan = 0;
            $totalProfit = 0;
            $productSales = []; // Untuk Chart Lingkaran

            // 1. Hitung Summary Card & Profit
            foreach ($orders as $order) {
                $totalPendapatan += $order->total_amount;
                
                foreach ($order->items as $item) {
                    // Hitung Profit: Jika ada kolom 'modal' atau 'harga_beli' di tabel produk, gunakan itu. 
                    // Jika tidak ada, fallback estimasi profit 20% dari harga jual.
                    $modal = $item->product->modal ?? $item->product->harga_beli ?? ($item->price * 0.8);
                    $profit = ($item->price - $modal) * $item->quantity;
                    $totalProfit += $profit;

                    // Kumpulkan data penjualan produk untuk Pie Chart
                    $prodName = $item->product ? substr($item->product->name, 0, 15) : 'Produk';
                    if (!isset($productSales[$prodName])) {
                        $productSales[$prodName] = 0;
                    }
                    $productSales[$prodName] += $item->quantity;
                }
            }

            $totalTransaksi = $orders->count();
            $rataRata = $totalTransaksi > 0 ? $totalPendapatan / $totalTransaksi : 0;

            // 2. Siapkan Data Line Chart (Grafik Garis Pendapatan)
            $labels = [];
            $data = [];

            if ($filter === 'Hari Ini') {
                $labels = ['08:00', '12:00', '16:00', '20:00', '23:59'];
                $data = [0, 0, 0, 0, 0];
                foreach ($orders as $o) {
                    $hour = \Carbon\Carbon::parse($o->created_at)->timezone('Asia/Jakarta')->hour;
                    if ($hour < 12) $data[0] += $o->total_amount;
                    elseif ($hour < 16) $data[1] += $o->total_amount;
                    elseif ($hour < 20) $data[2] += $o->total_amount;
                    elseif ($hour < 24) $data[3] += $o->total_amount;
                    else $data[4] += $o->total_amount;
                }
            } elseif ($filter === 'Bulan Ini') {
                $labels = ['Mg 1', 'Mg 2', 'Mg 3', 'Mg 4'];
                $data = [0, 0, 0, 0];
                foreach ($orders as $o) {
                    $day = \Carbon\Carbon::parse($o->created_at)->timezone('Asia/Jakarta')->day;
                    if ($day <= 7) $data[0] += $o->total_amount;
                    elseif ($day <= 14) $data[1] += $o->total_amount;
                    elseif ($day <= 21) $data[2] += $o->total_amount;
                    else $data[3] += $o->total_amount;
                }
            } else { 
                $labels = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
                $data = [0, 0, 0, 0, 0, 0, 0];
                foreach ($orders as $o) {
                    $dayIdx = \Carbon\Carbon::parse($o->created_at)->timezone('Asia/Jakarta')->dayOfWeekIso - 1; 
                    $data[$dayIdx] += $o->total_amount;
                }
            }

            if (empty($data) || max($data) == 0) {
                $data = array_fill(0, count($labels), 0); 
            }

            // 3. Siapkan Data Pie Chart (Produk Terlaris)
            arsort($productSales);
            $topProducts = array_slice($productSales, 0, 4, true); // Ambil 4 terlaris
            $pieData = [];
            $colors = ["#2563EB", "#10B981", "#F59E0B", "#EF4444", "#8B5CF6"];
            $i = 0;
            
            foreach ($topProducts as $name => $qty) {
                $pieData[] = [
                    "name" => $name,
                    "population" => $qty,
                    "color" => $colors[$i % count($colors)],
                    "legendFontColor" => "#374151",
                    "legendFontSize" => 11
                ];
                $i++;
            }

            // Jika kosong
            if (empty($pieData)) {
                $pieData[] = [
                    "name" => "Belum ada",
                    "population" => 1,
                    "color" => "#E5E7EB",
                    "legendFontColor" => "#9CA3AF",
                    "legendFontSize" => 11
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'totalPendapatan' => $totalPendapatan,
                    'totalProfit'     => $totalProfit,
                    'totalTransaksi'  => $totalTransaksi,
                    'rataRata'        => $rataRata,
                    'grafik'          => [
                        'labels'   => $labels,
                        'datasets' => [['data' => $data]]
                    ],
                    'pieData'         => $pieData
                ]
            ]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    public function getKaryawanShifts(Request $request)
    {
        try {
            $user = Auth::user() ?? auth('sanctum')->user();
            if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

            $storeId = $user->id_pengguna ?? $user->id;
            $now = \Carbon\Carbon::now('Asia/Jakarta')->toDateString();

            // 1. Ambil data karyawan yang terikat dengan toko ini
            $karyawans = User::where('parent_id', $storeId)->get();
            $data = [];

            foreach ($karyawans as $k) {
                // Hitung Penjualan Tunai Karyawan berdasarkan nama di shipping_address
                $today = \Carbon\Carbon::now('Asia/Jakarta')->toDateString();

                $penjualanTunai = Order::where('user_id', $storeId)
                    ->where('shipping_address', $operatorName)
                    ->where(function($q) {
                        $q->where('payment_method', 'like', '%CASH%')
                        ->orWhere('payment_method', 'like', '%tunai%');
                    })
                    ->where('status', 'paid')
                    ->whereDate('created_at', $today) // <--- MENGHITUNG SEMUA TRANSAKSI HARI INI
                    ->sum('total_amount');

                $data[] = [
                    'id' => $k->id_pengguna ?? $k->id,
                    'nama_lengkap' => $k->nama_lengkap,
                    'total_cash' => $penjualanTunai
                ];
            }

            // 2. Tambahkan juga diri sendiri (Pemilik) jika ada transaksi kasir atas nama sendiri
            $penjualanOwner = Order::where('user_id', $storeId)
                    ->where('shipping_address', $user->nama_lengkap)
                    ->where(function($q) {
                        $q->where('payment_method', 'like', '%tunai%')
                          ->orWhere('payment_method', 'like', '%cash%');
                    })
                    ->where('status', 'paid')
                    ->whereDate('created_at', $now)
                    ->sum('total_amount');

            // Selalu munculkan owner di urutan pertama
            array_unshift($data, [
                'id' => $storeId,
                'nama_lengkap' => $user->nama_lengkap . ' (Anda)',
                'total_cash' => $penjualanOwner
            ]);

            return response()->json(['success' => true, 'data' => $data]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

   // 1. GET STATUS & RIWAYAT SHIFT SAAT INI
    public function getShiftStatus(Request $request)
    {
        try {
            $user = Auth::user() ?? auth('sanctum')->user();
            if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

            $storeId = $user->parent_id ?? $user->id_pengguna ?? $user->id;
            $userId = $user->id_pengguna ?? $user->id;
            $operatorName = $user->nama_lengkap;

            // Cari shift yang sedang 'open' milik kasir ini
            $activeShift = ShiftKas::where('user_id', $userId)->where('status', 'open')->first();

            if (!$activeShift) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'is_open' => false,
                        'modal_awal' => 0,
                        'penjualan_tunai' => 0,
                        'kas_masuk' => 0,
                        'kas_keluar' => 0,
                        'riwayat' => []
                    ]
                ]);
            }

            // Hitung uang cash yang masuk dari Orders SEJAK shift dibuka
            $penjualanTunai = Order::where('user_id', $storeId)
                ->where('shipping_address', $operatorName)
                ->where(function($q) {
                    $q->where('payment_method', 'like', '%CASH%')
                      ->orWhere('payment_method', 'like', '%tunai%');
                })
                ->where('status', 'paid')
                ->where('created_at', '>=', $activeShift->created_at)
                ->sum('total_amount');

            // Hitung Kas Masuk / Keluar dari tabel shift_flows
            $kasMasuk = ShiftFlow::where('shift_id', $activeShift->id)->where('type', 'masuk')->sum('nominal');
            $kasKeluar = ShiftFlow::where('shift_id', $activeShift->id)->where('type', 'keluar')->sum('nominal');
            
            // Tarik Riwayat
            $riwayat = ShiftFlow::where('shift_id', $activeShift->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($r) {
                    return [
                        'id' => $r->id,
                        'type' => $r->type,
                        'nominal' => $r->nominal,
                        'ket' => $r->keterangan,
                        'time' => $r->created_at->format('H:i')
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'is_open' => true,
                    'modal_awal' => $activeShift->modal_awal,
                    'penjualan_tunai' => (int) $penjualanTunai,
                    'kas_masuk' => $kasMasuk,
                    'kas_keluar' => $kasKeluar,
                    'riwayat' => $riwayat
                ]
            ]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // 2. BUKA SHIFT BARU
    public function openShift(Request $request)
    {
        $user = Auth::user() ?? auth('sanctum')->user();
        $storeId = $user->parent_id ?? $user->id_pengguna ?? $user->id;
        $userId = $user->id_pengguna ?? $user->id;

        // Validasi jika masih ada shift yang belum ditutup
        $check = ShiftKas::where('user_id', $userId)->where('status', 'open')->first();
        if ($check) return response()->json(['success' => false, 'message' => 'Tutup shift sebelumnya terlebih dahulu!']);

        $shift = ShiftKas::create([
            'store_id' => $storeId,
            'user_id' => $userId,
            'status' => 'open',
            'modal_awal' => $request->modal_awal ?? 0
        ]);

        return response()->json(['success' => true, 'message' => 'Shift berhasil dibuka']);
    }

    // 3. CATAT KAS MASUK/KELUAR (IN/OUT)
    public function cashFlowShift(Request $request)
    {
        $user = Auth::user() ?? auth('sanctum')->user();
        $userId = $user->id_pengguna ?? $user->id;

        $activeShift = ShiftKas::where('user_id', $userId)->where('status', 'open')->first();
        if (!$activeShift) return response()->json(['success' => false, 'message' => 'Buka shift terlebih dahulu.']);

        ShiftFlow::create([
            'shift_id' => $activeShift->id,
            'type' => $request->type, // 'masuk' atau 'keluar'
            'nominal' => $request->nominal,
            'keterangan' => $request->keterangan
        ]);

        return response()->json(['success' => true, 'message' => 'Data kas berhasil dicatat.']);
    }

    // 4. TUTUP SHIFT
    public function closeShift(Request $request)
    {
        $user = Auth::user() ?? auth('sanctum')->user();
        $userId = $user->id_pengguna ?? $user->id;

        $activeShift = ShiftKas::where('user_id', $userId)->where('status', 'open')->first();
        if (!$activeShift) return response()->json(['success' => false, 'message' => 'Tidak ada shift aktif.']);

        $activeShift->update([
            'status' => 'closed',
            'uang_fisik' => $request->uang_fisik ?? 0
        ]);

        return response()->json(['success' => true, 'message' => 'Shift berhasil ditutup']);
    }

}