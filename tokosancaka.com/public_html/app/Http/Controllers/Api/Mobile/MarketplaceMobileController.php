<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Exception;

// Models
use App\Models\Product;
use App\Models\Setting;
use App\Models\BannerEtalase;
use App\Models\Category;
use App\Models\Store;
use App\Models\ProductVariant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Cart;
use App\Models\Checkout;

// Services
use App\Services\KiriminAjaService;
use App\Services\DokuJokulService;
use App\Services\DanaSignatureService;

class MarketplaceMobileController extends Controller
{
    // =========================================================================
    // BAGIAN 1: ETALASE (PRODUK & HOMEPAGE)
    // =========================================================================

    public function home(Request $request)
    {
        $query = Product::with(['category', 'store.user'])->where('status', 'active')->where('stock', '>', 0);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->latest()->paginate(10);

        $flashSaleProducts = Product::with(['category', 'store.user'])->where('status', 'active')
            ->where('stock', '>', 0)
            ->whereNotNull('original_price')
            ->where('price', '<', DB::raw('original_price'))
            ->orderBy('discount_percentage', 'desc')
            ->limit(8)
            ->get();

        $categories = Category::where('type', 'product')->orderBy('name')->get();
        $banners = BannerEtalase::latest()->get();
        $settings = Setting::whereIn('key', ['banner_2','banner_3'])->pluck('value','key');

        return response()->json([
            'success' => true,
            'data' => [
                'banners' => $banners,
                'settings' => $settings,
                'categories' => $categories,
                'flash_sale' => $flashSaleProducts,
                'products' => $products
            ]
        ]);
    }

    public function showCategory($id)
    {
        $category = Category::findOrFail($id);

        $products = Product::with(['store.user'])
            ->where('category_id', $category->id)
            ->where('status', 'active')
            ->where('stock', '>', 0)
            ->latest()
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
                'products' => $products
            ]
        ]);
    }

    public function showProduct($id)
    {
        $product = Product::with(['category.attributes', 'store.user'])->findOrFail($id);

        if ($product->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'Produk tidak aktif atau dihapus'], 404);
        }

        $relatedProducts = Product::with(['category', 'store'])
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)->where('status', 'active')
            ->where('stock', '>', 0)->inRandomOrder()->limit(5)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'product' => $product,
                'related_products' => $relatedProducts
            ]
        ]);
    }

    public function showStore($id)
    {
        $store = Store::with('user')->findOrFail($id);

        $products = Product::where('store_id', $store->id)
            ->where('status', 'active')
            ->where('stock', '>', 0)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'store' => $store,
                'products' => $products
            ]
        ]);
    }


    // =========================================================================
    // BAGIAN 2: KERANJANG (CART API - BERBASIS DATABASE)
    // =========================================================================

    private function _getUserCartRecord()
    {
        $user = Auth::user() ?? auth('sanctum')->user();
        if (!$user) return null;

        $userId = $user->id_pengguna ?? $user->id; // Fallback jika pakai id

        return Cart::firstOrCreate(
            ['user_id' => $userId],
            [
                'item_description' => json_encode([]),
                'total_amount' => 0,
                'weight' => 0
            ]
        );
    }

    public function getCart()
    {
        $cartRecord = $this->_getUserCartRecord();
        if (!$cartRecord) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

        $cart = json_decode($cartRecord->item_description, true) ?? [];

        $total = 0;
        foreach ($cart as $item) {
            $qty = $item['quantity'] ?? $item['qty'] ?? 1;
            $total += $item['price'] * $qty;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'items' => array_values($cart),
                'total_amount' => $total,
                'total_items' => count($cart)
            ]
        ]);
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'product_variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $productId = $request->product_id;
        $quantity = $request->quantity;
        $variantId = $request->product_variant_id;

        $cartRecord = $this->_getUserCartRecord();
        if (!$cartRecord) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

        $cart = json_decode($cartRecord->item_description, true) ?? [];

        try {
            $product = Product::with('store.user')->find($productId);
            if (!$product) return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);

            if ($variantId) {
                $variant = ProductVariant::with('product')->find($variantId);
                if (!$variant || $variant->product_id != $productId) {
                    return response()->json(['success' => false, 'message' => 'Varian tidak valid.'], 400);
                }

                $stockToCheck = $variant->stock;
                $itemPrice = $variant->price;
                $itemName = $product->name . ' (' . str_replace(';', ', ', $variant->combination_string) . ')';
                $itemImageUrl = $variant->image_url ?? $product->image_url;
                $itemKey = 'variant_' . $variantId;
                $weight = $variant->weight ?? $product->weight ?? 0;
            } else {
                if ($product->productVariantTypes()->exists()) {
                     return response()->json(['success' => false, 'message' => 'Silakan pilih varian produk.'], 400);
                }

                $stockToCheck = $product->stock;
                $itemPrice = $product->price;
                $itemName = $product->name;
                $itemImageUrl = $product->image_url;
                $itemKey = 'product_' . $productId;
                $weight = $product->weight ?? 0;
            }

            $currentQuantityInCart = $cart[$itemKey]['quantity'] ?? $cart[$itemKey]['qty'] ?? 0;
            $newTotalQuantity = $currentQuantityInCart + $quantity;

            if ($stockToCheck < $newTotalQuantity) {
                return response()->json(['success' => false, 'message' => "Stok tidak mencukupi. Tersedia: {$stockToCheck}"], 422);
            }

            if (isset($cart[$itemKey])) {
                $cart[$itemKey]['quantity'] = $newTotalQuantity;
                $cart[$itemKey]['qty'] = $newTotalQuantity; // Backup bilingual
                $cart[$itemKey]['image_url'] = $itemImageUrl;
                $cart[$itemKey]['store_logo'] = $product->store->logo ?? $product->store->user->store_logo_path ?? null;
                $cart[$itemKey]['store_name'] = $product->store->name ?? 'Toko Sancaka';
                $cart[$itemKey]['store_regency'] = $product->store->regency ?? 'Ngawi';
            } else {
                $storeName = $product->store->name ?? 'Toko Sancaka';
                $storeRegency = $product->store->regency ?? 'Ngawi';
                // Ambil logo toko dari tabel store, jika kosong ambil dari tabel pengguna
                $storeLogo = $product->store->logo ?? $product->store->user->store_logo_path ?? null;

                $cart[$itemKey] = [
                    "id"         => $itemKey,
                    "product_id" => $productId,
                    "variant_id" => $variantId,
                    "name"       => $itemName,
                    "quantity"   => $quantity,
                    "qty"        => $quantity, // Backup bilingual
                    "price"      => $itemPrice,
                    "image_url"  => $itemImageUrl,
                    "slug"       => $product->slug,
                    "weight"     => $weight,
                    "store_id"   => $product->store_id,
                    "store_name" => $storeName,
                    "store_regency" => $storeRegency,
                    "store_logo" => $storeLogo,
                ];
            }

            $newTotalAmount = 0; $newTotalWeight = 0;
            foreach($cart as $c) {
                $qty = $c['quantity'] ?? $c['qty'] ?? 1;
                $newTotalAmount += ($c['price'] * $qty);
                $newTotalWeight += ($c['weight'] * $qty);
            }

            $cartRecord->item_description = json_encode($cart);
            $cartRecord->total_amount = $newTotalAmount;
            $cartRecord->weight = $newTotalWeight;
            $cartRecord->store_id = $product->store_id;
            $cartRecord->save();

            return response()->json(['success' => true, 'message' => 'Produk ditambahkan ke keranjang.']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function updateCart(Request $request)
    {
        $itemKey = $request->input('id');
        $quantity = $request->input('quantity');

        if (!$itemKey || !$quantity || $quantity < 1) {
             return response()->json(['success' => false, 'message' => 'Data tidak valid.'], 400);
        }

        $cartRecord = $this->_getUserCartRecord();
        $cart = json_decode($cartRecord->item_description, true) ?? [];

        if (!isset($cart[$itemKey])) {
            return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan di keranjang.'], 404);
        }

        $item = $cart[$itemKey];
        $stockToCheck = 0;

        if (!empty($item['variant_id'])) {
             $variant = ProductVariant::find($item['variant_id']);
             if (!$variant) {
                 unset($cart[$itemKey]);
                 $cartRecord->item_description = json_encode($cart); $cartRecord->save();
                 return response()->json(['success' => false, 'message' => 'Varian sudah tidak tersedia.'], 404);
             }
             $stockToCheck = $variant->stock;
        } else {
            $product = Product::find($item['product_id']);
             if (!$product) {
                 unset($cart[$itemKey]);
                 $cartRecord->item_description = json_encode($cart); $cartRecord->save();
                 return response()->json(['success' => false, 'message' => 'Produk sudah tidak tersedia.'], 404);
             }
             $stockToCheck = $product->stock;
        }

        if ($stockToCheck < $quantity) {
             return response()->json(['success' => false, 'message' => "Stok tidak mencukupi."], 422);
        }

        $cart[$itemKey]['quantity'] = (int)$quantity;
        $cart[$itemKey]['qty'] = (int)$quantity;

        $cartRecord->item_description = json_encode($cart);
        $cartRecord->save();

        return response()->json(['success' => true, 'message' => 'Kuantitas diperbarui.']);
    }

    public function removeFromCart(Request $request)
    {
        $itemKey = $request->input('id');

        $cartRecord = $this->_getUserCartRecord();
        $cart = json_decode($cartRecord->item_description, true) ?? [];

        if (isset($cart[$itemKey])) {
            unset($cart[$itemKey]);
            $cartRecord->item_description = json_encode($cart);
            $cartRecord->save();
            return response()->json(['success' => true, 'message' => 'Produk dihapus.']);
        }

        return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);
    }

    public function clearCart()
    {
        $userId = Auth::id() ?? auth('sanctum')->id();
        Cart::where('user_id', $userId)->delete();
        return response()->json(['success' => true, 'message' => 'Keranjang dibersihkan.']);
    }


    // =========================================================================
    // BAGIAN 3: CHECKOUT (PERSIAPAN & PROSES)
    // =========================================================================

    public function processCheckout(Request $request, KiriminAjaService $kiriminAja, DanaSignatureService $danaSignature)
    {
        $request->validate([
            'shipping_method' => 'required|string',
            'payment_method' => 'required|string',
            'cart_items' => 'required|array',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'use_insurance' => 'boolean',
            'destination_district_id' => 'required',
            'destination_subdistrict_id' => 'required'
        ]);

        $user = Auth::user() ?? auth('sanctum')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Silakan login kembali.'], 401);
        }

        $userId = $user->id_pengguna ?? $user->id;

        $cartItemsPayload = $request->input('cart_items', []);

        if (empty($cartItemsPayload)) {
            return response()->json(['success' => false, 'message' => 'Keranjang kosong.'], 400);
        }

        DB::beginTransaction();

        try {
            // 1. Kalkulasi Data dari Payload
            $subtotal = 0;
            $orderItemsPayload = [];
            $totalWeight = 0;

            // KUNCI AMAN: Ambil store_id dari item keranjang pertama
            $firstItem = reset($cartItemsPayload);
            $storeId = $firstItem['store_id'] ?? null;

            if (!$storeId) {
                 // Jika tidak ada di payload, cari dari produk
                 $productId = $firstItem['product_id'] ?? $firstItem['id'];
                 $product = Product::find($productId);
                 if (!$product) throw new Exception("Produk tidak ditemukan.");
                 $storeId = $product->store_id;
            }

            // AMBIL DATA TOKO
            $store = Store::with('user')->find($storeId);
            if (!$store) throw new Exception("Toko tidak ditemukan.");

            foreach ($cartItemsPayload as $key => $details) {
                $qty = isset($details['qty']) ? (int) $details['qty'] : ((int) ($details['quantity'] ?? 1));
                $price = (int) ($details['price'] ?? 0);
                $weight = (int) ($details['weight'] ?? 1000);

                $subtotal += ($price * $qty);
                $totalWeight += ($weight * $qty);

                $orderItemsPayload[] = [
                    'sku' => $details['id'] ?? $key,
                    'name' => $details['name'] ?? 'Produk',
                    'price' => $price,
                    'quantity' => $qty
                ];
            }

            $shippingParts = explode('-', $request->shipping_method);
            if (count($shippingParts) < 4) {
                throw new Exception("Format metode pengiriman tidak valid.");
            }

            $shipping_cost = (int) ($shippingParts[3] ?? 0);
            $insurance_cost = (int) ($shippingParts[count($shippingParts) - 2] ?? 0);

            $useInsurance = $request->use_insurance && $insurance_cost > 0;
            $applied_insurance = $useInsurance ? $insurance_cost : 0;
            $grand_total = $subtotal + $shipping_cost + $applied_insurance;

            // POTONG SALDO CEK
            $isPayWithSaldo = in_array(strtoupper($request->payment_method), ['POTONG SALDO', 'SALDO']);
            if ($isPayWithSaldo && ($user->saldo < $grand_total)) {
                throw new Exception("Saldo tidak cukup. Tagihan: Rp " . number_format($grand_total,0,',','.') . ", Saldo Anda: Rp " . number_format($user->saldo,0,',','.'));
            }

            // BUAT INVOICE
            do {
                 $invoiceNumber = 'SCK-ORD-' . strtoupper(Str::random(8));
            } while (Order::where('invoice_number', $invoiceNumber)->exists() || Checkout::where('invoice_number', $invoiceNumber)->exists());

            // ========================================================
            // 🔥 SIMPAN KE TABEL CHECKOUT DULU (STAGING) 🔥
            // ========================================================
            $checkoutRecord = Checkout::create([
                'store_id'      => $store->id,
                'user_id'       => $userId,
                'invoice_number'=> $invoiceNumber,
                'subtotal'      => $subtotal,
                'shipping_cost' => $shipping_cost,
                'insurance_cost'=> $applied_insurance,
                'total_amount'  => $grand_total,
                'shipping_method'=> $request->shipping_method,
                'payment_method'=> $request->payment_method,
                'status'        => 'pending',
                'receiver_name'    => $request->receiver_name ?? $user->nama_lengkap,
                'receiver_phone'   => $request->receiver_phone ?? $user->no_wa,
                'receiver_address' => $request->receiver_address ?? $user->address_detail,
                'shipping_address' => ($request->receiver_address ?? $user->address_detail) . ', ' . ($request->receiver_full_region ?? ''),
                'customer_latitude' => $request->latitude ?? null,
                'customer_longitude' => $request->longitude ?? null,
                'receiver_district_id' => $request->destination_district_id,
                'receiver_subdistrict_id' => $request->destination_subdistrict_id,
                'receiver_village' => $user->village ?? 'Tidak Diketahui',
                'item_description' => json_encode($cartItemsPayload),
            ]);

            // ========================================================
            // 🔥 LANJUT SIMPAN KE TABEL ORDERS (AGAR RELASI AMAN) 🔥
            // ========================================================
            $order = new Order([
                 'store_id'      => $checkoutRecord->store_id,
                 'user_id'       => $checkoutRecord->user_id,
                 'invoice_number'=> $checkoutRecord->invoice_number,
                 'subtotal'      => $checkoutRecord->subtotal,
                 'shipping_cost' => $checkoutRecord->shipping_cost,
                 'insurance_cost'=> $checkoutRecord->insurance_cost,
                 'total_amount'  => $checkoutRecord->total_amount,
                 'shipping_method'=> $checkoutRecord->shipping_method,
                 'payment_method'=> $checkoutRecord->payment_method,
                 'status'        => 'pending',
                 'receiver_name'    => $checkoutRecord->receiver_name,
                 'receiver_phone'   => $checkoutRecord->receiver_phone,
                 'receiver_address' => $checkoutRecord->receiver_address,
                 'shipping_address' => $checkoutRecord->shipping_address,
                 'customer_latitude' => $checkoutRecord->customer_latitude,
                 'customer_longitude' => $checkoutRecord->customer_longitude,
                 'receiver_district_id' => $checkoutRecord->receiver_district_id,
                 'receiver_subdistrict_id' => $checkoutRecord->receiver_subdistrict_id,
                 'receiver_village' => $checkoutRecord->receiver_village,
            ]);
            $order->save();

            // INSERT ITEMS (DENGAN NULL SAFETY)
            foreach ($cartItemsPayload as $key => $details) {
                 $qty = isset($details['qty']) ? (int) $details['qty'] : ((int) ($details['quantity'] ?? 1));
                 $price = (int) ($details['price'] ?? 0);
                 $variantId = $details['variant_id'] ?? null;
                 $productId = $details['product_id'] ?? $details['id'] ?? null;

                 if (!$productId) continue;

                 OrderItem::create([
                     'order_id' => $order->id,
                     'product_id' => $productId,
                     'product_variant_id' => $variantId,
                     'quantity' => $qty,
                     'price' => $price
                 ]);

                 if ($variantId) { ProductVariant::where('id', $variantId)->decrement('stock', $qty); }
                 else { Product::where('id', $productId)->decrement('stock', $qty); }
            }

            $paymentUrl = null;

            // --- PROSES PEMBAYARAN & EKSPEDISI ---
            if ($isPayWithSaldo) {
                // POTONG SALDO (Arahkan ke id_pengguna sesuai tabelmu)
                $user->saldo -= $grand_total;
                $user->save();
                $order->status = 'paid';
                $order->save();

                $webController = new \App\Http\Controllers\CheckoutController(app(\App\Services\DanaSignatureService::class));
                $webController->processOrderCallback($invoiceNumber, 'PAID', []);

            } elseif (in_array(strtolower($request->payment_method), ['cod', 'cash', 'codbarang'])) {
                $order->status = 'processing';
                $order->save();

                $webController = new \App\Http\Controllers\CheckoutController(app(\App\Services\DanaSignatureService::class));
                $webController->processOrderCallback($invoiceNumber, 'PAID', []);

            } elseif (!in_array(strtolower($request->payment_method), ['dana', 'doku_jokul'])) {
                $tripayResult = $this->_createTripayTransaction($order, $request->payment_method, $grand_total, $user, $orderItemsPayload);
                if ($tripayResult['success']) {
                    $paymentUrl = $tripayResult['data']['checkout_url'] ?? $tripayResult['data']['pay_url'];
                    $order->payment_url = $paymentUrl;
                    $order->pay_code = $tripayResult['data']['pay_code'] ?? null;
                    $order->qr_url = $tripayResult['data']['qr_url'] ?? null;
                    $order->save();
                } else {
                    throw new Exception($tripayResult['message']);
                }
            }

            DB::commit();

            // 🔥 HAPUS KERANJANG DI DATABASE KARENA SUDAH CHECKOUT 🔥
            Cart::where('user_id', $userId)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat.',
                'data' => [
                    'invoice_number' => $order->invoice_number,
                    'total_amount' => $grand_total,
                    'payment_method' => $order->payment_method,
                    'status' => $order->status,
                    'payment_url' => $paymentUrl
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Checkout API Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal memproses order: ' . $e->getMessage()], 500);
        }
    }

    // --- Helper Tripay untuk API ---
    private function _createTripayTransaction($order, $methodChannel, $amount, $user, $items)
    {
        $mode = \App\Models\Api::getValue('TRIPAY_MODE', 'global', 'sandbox');
        $baseUrl      = $mode === 'production' ? 'https://tripay.co.id/api/transaction/create' : 'https://tripay.co.id/api-sandbox/transaction/create';
        $apiKey       = \App\Models\Api::getValue('TRIPAY_API_KEY', $mode);
        $privateKey   = \App\Models\Api::getValue('TRIPAY_PRIVATE_KEY', $mode);
        $merchantCode = \App\Models\Api::getValue('TRIPAY_MERCHANT_CODE', $mode);

        $signature = hash_hmac('sha256', $merchantCode . $order->invoice_number . $amount, $privateKey);

        $payload = [
            'method'         => $methodChannel,
            'merchant_ref'   => $order->invoice_number,
            'amount'         => $amount,
            'customer_name'  => $user->nama_lengkap,
            'customer_email' => $user->email,
            'customer_phone' => $user->no_wa,
            'order_items'    => $items,
            'return_url'     => url('/mobile-payment-success'),
            'expired_time'   => (time() + (24 * 60 * 60)),
            'signature'      => $signature
        ];

        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])->post($baseUrl, $payload);
            $body = $response->json();
            if ($response->successful() && ($body['success'] ?? false)) {
                return ['success' => true, 'data' => $body['data']];
            }
            return ['success' => false, 'message' => $body['message'] ?? 'Tripay Failed'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Koneksi Gateway bermasalah.'];
        }
    }
}
