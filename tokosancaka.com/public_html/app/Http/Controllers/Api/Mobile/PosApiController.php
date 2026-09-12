<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Api;
use App\Services\DanaSignatureService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

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

            $sellerName = $user->nama_lengkap;
            $userId = $user->id_pengguna ?? $user->id;

            $query = Product::where(function($q) use ($sellerName, $userId) {
                                $q->where('seller_name', $sellerName)
                                  ->orWhere('store_id', $userId);
                            })
                            ->where('status', 'active');
            
            if ($request->filled('search')) {
                $query->where(function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('sku', $request->search);
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

            // Cek apakah metode pembayaran QRIS
            $isQris = strtoupper($request->payment_method) === 'QRIS';
            $status = $isQris ? 'pending' : 'paid';

            $order = Order::create([
                'invoice_number'   => $invoiceNumber,
                'user_id'          => $user->id_pengguna ?? $user->id, 
                'subtotal'         => $grandTotal,
                'shipping_cost'    => 0, 
                'shipping_method'  => 'Di Tempat (POS)',
                'shipping_address' => 'Pembelian di Toko (POS)',
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

            // Jika QRIS, request URL ke DANA IPG
            if ($isQris) {
                $danaRes = $this->createDanaPaymentGateway($invoiceNumber, $grandTotal, $user);
                
                if (!$danaRes['success']) {
                    throw new \Exception($danaRes['message']);
                }
                
                $paymentUrl = $danaRes['redirect_url'];
                
                // Aktifkan penyimpanan URL ke database
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

            $userId = $user->id_pengguna ?? $user->id;

            $history = Order::with(['items.product'])
                ->where('user_id', $userId)
                ->where('shipping_method', 'Di Tempat (POS)') // <--- Gunakan ini sebagai penanda POS
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
            'price' => 'required|numeric',
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
            'price' => 'required|numeric',
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
            $body['externalStoreId'] = config('services.dana.external_store_id', '20050811210214220002'); 
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
                'services.dana.client_id'     => $settings['dana_sandbox_client_id'] ?? env('DANA_X_PARTNER_ID'),
                'services.dana.x_partner_id'  => $settings['dana_sandbox_client_id'] ?? env('DANA_X_PARTNER_ID'),
                'services.dana.private_key'   => $settings['dana_sandbox_private_key'] ?? env('DANA_PRIVATE_KEY'),
                'services.dana.client_secret' => $settings['dana_sandbox_client_secret'] ?? env('DANA_CLIENT_SECRET'),
                'services.dana.origin'        => env('DANA_ORIGIN', 'https://tokosancaka.com'),
            ]);
        }
    }
}