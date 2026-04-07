<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
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

// Services
use App\Services\KiriminAjaService;
use App\Services\DokuJokulService;
use App\Services\DanaSignatureService;
use App\Services\FonnteService;

class MarketplaceMobileController extends Controller
{
    // =========================================================================
    // BAGIAN 1: ETALASE (PRODUK & HOMEPAGE)
    // =========================================================================

    public function home(Request $request)
    {
        $query = Product::with(['category', 'store'])->where('status', 'active')->where('stock', '>', 0);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->latest()->paginate(10);

        $flashSaleProducts = Product::with(['category', 'store'])->where('status', 'active')
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
                'products' => $products // Pagination Object
            ]
        ]);
    }

    public function showCategory($id)
    {
        $category = Category::findOrFail($id);

        $products = Product::with(['store'])
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

    public function showStore($slugOrName)
    {
        $store = Store::with('user')
            ->where('slug', $slugOrName)
            ->orWhere('name', $slugOrName)
            ->firstOrFail();

        $products = Product::where('store_id', $store->id)
            ->where('status', 'active')
            ->where('stock', '>', 0)
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => [
                'store' => $store,
                'products' => $products
            ]
        ]);
    }


    // =========================================================================
    // BAGIAN 2: KERANJANG (CART API - BERBASIS CACHE)
    // =========================================================================

    private function getCartKey()
    {
        return 'cart_mobile_' . Auth::id();
    }

    public function getCart()
    {
        $cart = Cache::get($this->getCartKey(), []);

        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'items' => array_values($cart), // Ubah ke array index untuk Mobile
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

        $cartKeyName = $this->getCartKey();
        $cart = Cache::get($cartKeyName, []);

        try {
            $product = Product::find($productId);
            if (!$product) return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);

            if ($variantId) {
                $variant = ProductVariant::with('product')->find($variantId);
                if (!$variant || $variant->product_id != $productId) {
                    return response()->json(['success' => false, 'message' => 'Varian tidak valid.'], 400);
                }

                $stockToCheck = $variant->stock;
                $itemPrice = $variant->price;
                $itemName = $variant->product->name . ' (' . str_replace(';', ', ', $variant->combination_string) . ')';
                $itemImageUrl = $variant->image_url ?? $variant->product->image_url;
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

            $currentQuantityInCart = $cart[$itemKey]['quantity'] ?? 0;
            $newTotalQuantity = $currentQuantityInCart + $quantity;

            if ($stockToCheck < $newTotalQuantity) {
                return response()->json(['success' => false, 'message' => "Stok tidak mencukupi. Tersedia: {$stockToCheck}"], 422);
            }

            if (isset($cart[$itemKey])) {
                $cart[$itemKey]['quantity'] = $newTotalQuantity;
            } else {
                $cart[$itemKey] = [
                    "id"         => $itemKey, // Kunci unik
                    "product_id" => $productId,
                    "variant_id" => $variantId,
                    "name"       => $itemName,
                    "quantity"   => $quantity,
                    "price"      => $itemPrice,
                    "image_url"  => $itemImageUrl,
                    "slug"       => $product->slug,
                    "weight"     => $weight,
                ];
            }

            Cache::put($cartKeyName, $cart, now()->addDays(7)); // Simpan di cache 7 Hari

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

        $cartKeyName = $this->getCartKey();
        $cart = Cache::get($cartKeyName, []);

        if (!isset($cart[$itemKey])) {
            return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan di keranjang.'], 404);
        }

        // Logic validasi stok sama seperti web...
        $item = $cart[$itemKey];
        $stockToCheck = 0;

        if (!empty($item['variant_id'])) {
             $variant = ProductVariant::find($item['variant_id']);
             if (!$variant) {
                 unset($cart[$itemKey]); Cache::put($cartKeyName, $cart, now()->addDays(7));
                 return response()->json(['success' => false, 'message' => 'Varian sudah tidak tersedia.'], 404);
             }
             $stockToCheck = $variant->stock;
        } else {
            $product = Product::find($item['product_id']);
             if (!$product) {
                 unset($cart[$itemKey]); Cache::put($cartKeyName, $cart, now()->addDays(7));
                 return response()->json(['success' => false, 'message' => 'Produk sudah tidak tersedia.'], 404);
             }
             $stockToCheck = $product->stock;
        }

        if ($stockToCheck < $quantity) {
             return response()->json(['success' => false, 'message' => "Stok tidak mencukupi."], 422);
        }

        $cart[$itemKey]['quantity'] = (int)$quantity;
        Cache::put($cartKeyName, $cart, now()->addDays(7));

        return response()->json(['success' => true, 'message' => 'Kuantitas diperbarui.']);
    }

    public function removeFromCart(Request $request)
    {
        $itemKey = $request->input('id');
        $cartKeyName = $this->getCartKey();
        $cart = Cache::get($cartKeyName, []);

        if (isset($cart[$itemKey])) {
            unset($cart[$itemKey]);
            Cache::put($cartKeyName, $cart, now()->addDays(7));
            return response()->json(['success' => true, 'message' => 'Produk dihapus.']);
        }

        return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);
    }

    public function clearCart()
    {
        Cache::forget($this->getCartKey());
        return response()->json(['success' => true, 'message' => 'Keranjang dibersihkan.']);
    }


    // =========================================================================
    // BAGIAN 3: CHECKOUT (PERSIAPAN & PROSES)
    // =========================================================================

    public function prepareCheckout(KiriminAjaService $kiriminAja)
    {
        $user = Auth::user();
        $cart = Cache::get($this->getCartKey(), []);

        if (empty($cart)) {
            return response()->json(['success' => false, 'message' => 'Keranjang kosong.'], 400);
        }

        if (empty($user->village) || empty($user->district) || empty($user->regency) || empty($user->province)) {
            return response()->json(['success' => false, 'message' => 'Alamat pengiriman Anda belum lengkap. Mohon lengkapi profil.', 'error_type' => 'INCOMPLETE_PROFILE'], 400);
        }

        $firstCartItemData = reset($cart);
        $firstProduct = Product::with('store')->find($firstCartItemData['product_id']);

        if (!$firstProduct || !$firstProduct->store) {
            Cache::forget($this->getCartKey());
            return response()->json(['success' => false, 'message' => 'Toko tidak valid, keranjang direset.'], 400);
        }

        $store = $firstProduct->store;

        // Proses API Ekspedisi (Sama seperti web, diubah ke JSON Return)
        $storeSearch = $store->village . ', ' . $store->district . ', ' . $store->regency . ', ' . $store->province;
        $userSearch  = $user->village . ', ' . $user->district . ', ' . $user->regency . ', ' . $user->province;

        $storeAddrRes = $kiriminAja->searchAddress($storeSearch);
        $userAddrRes  = $kiriminAja->searchAddress($userSearch);
        $storeAddr = $storeAddrRes['data'][0] ?? null;
        $userAddr  = $userAddrRes['data'][0] ?? null;

        if (!$storeAddr || !$userAddr) {
             return response()->json(['success' => false, 'message' => 'Alamat pengiriman tidak dikenali oleh sistem ekspedisi.'], 400);
        }

        // Kalkulasi Berat & Total
        $totalWeight = array_reduce($cart, fn($carry, $item) => $carry + ($item['weight'] * $item['quantity']), 0);
        $finalWeight = max(1000, $totalWeight);
        $itemValue   = array_reduce($cart, fn($carry, $item) => $carry + ($item['price'] * $item['quantity']), 0);

        $category = $finalWeight >= 30000 ? 'trucking' : 'regular';

        // Ambil Ongkir
        $expressOptions = $kiriminAja->getExpressPricing(
            $storeAddr['district_id'], $storeAddr['subdistrict_id'],
            $userAddr['district_id'], $userAddr['subdistrict_id'],
            $finalWeight, 5, 5, 5, $itemValue, null, $category, 1
        );

        // Ambil Channel Tripay
        $currentMode = \App\Models\Api::getValue('TRIPAY_MODE', 'global', 'sandbox');
        $tripayChannels = Cache::remember('tripay_channels_list_' . $currentMode, 60 * 24, function () use ($currentMode) {
            $baseUrl = $currentMode === 'production' ? 'https://tripay.co.id/api' : 'https://tripay.co.id/api-sandbox';
            $apiKey  = \App\Models\Api::getValue('TRIPAY_API_KEY', $currentMode);
            try {
                $response = Http::withToken($apiKey)->timeout(10)->get($baseUrl . '/merchant/payment-channel');
                if ($response->successful()) return $response->json()['data'] ?? [];
            } catch (\Exception $e) {}
            return [];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'cart_items' => array_values($cart),
                'total_weight' => $finalWeight,
                'subtotal' => $itemValue,
                'shipping_options' => $expressOptions['results'] ?? [], // Array list JNE, J&T, dll
                'payment_channels' => $tripayChannels,
                'user_address' => $userSearch,
                'store_address' => $storeSearch,
                'user_balance' => $user->saldo ?? 0
            ]
        ]);
    }

    public function processCheckout(Request $request, KiriminAjaService $kiriminAja, DanaSignatureService $danaSignature)
    {
        $request->validate([
            'shipping_method' => 'required|string',
            'payment_method' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'use_insurance' => 'boolean'
        ]);

        $user = Auth::user();
        $cartKeyName = $this->getCartKey();
        $cart = Cache::get($cartKeyName, []);

        if (empty($cart)) return response()->json(['success' => false, 'message' => 'Keranjang kosong.'], 400);

        DB::beginTransaction();

        try {
            // 1. Kalkulasi Data dari Cart (Bukan dari Payload Request agar aman)
            $subtotal = 0;
            $orderItemsPayload = [];
            $firstProduct = null;
            $totalWeight = 0;

            foreach ($cart as $key => $details) {
                $subtotal += ($details['price'] * $details['quantity']);
                $totalWeight += ($details['weight'] * $details['quantity']);
                if (!$firstProduct) $firstProduct = Product::with('store.user')->find($details['product_id']);

                $orderItemsPayload[] = [
                    'sku' => $key,
                    'name' => $details['name'],
                    'price' => (int) $details['price'],
                    'quantity' => $details['quantity']
                ];
            }

            // 2. Parsing Shipping
            $shippingParts = explode('-', $request->shipping_method); // format: regular-jne-REG-15000-0
            $shipping_cost = (int) ($shippingParts[3] ?? 0);
            $insurance_cost = (int) ($shippingParts[count($shippingParts) - 2] ?? 0);

            // 3. Kalkulasi Grand Total
            $useInsurance = $request->use_insurance && $insurance_cost > 0;
            $applied_insurance = $useInsurance ? $insurance_cost : 0;
            $grand_total = $subtotal + $shipping_cost + $applied_insurance;

            // 4. Potong Saldo Cek
            $isPayWithSaldo = in_array(strtoupper($request->payment_method), ['POTONG SALDO', 'SALDO']);
            if ($isPayWithSaldo && ($user->saldo < $grand_total)) {
                throw new Exception("Saldo tidak cukup. Tagihan: Rp {$grand_total}, Saldo: Rp {$user->saldo}");
            }

            // 5. Buat Order
            do {
                 $invoiceNumber = 'SCK-ORD-' . strtoupper(Str::random(8));
            } while (Order::where('invoice_number', $invoiceNumber)->exists());

            $order = new Order([
                 'store_id'      => $firstProduct->store->id,
                 'user_id'       => $user->id_pengguna ?? $user->id,
                 'invoice_number'=> $invoiceNumber,
                 'subtotal'      => $subtotal,
                 'shipping_cost' => $shipping_cost,
                 'insurance_cost'=> $applied_insurance,
                 'total_amount'  => $grand_total,
                 'shipping_method'=> $request->shipping_method,
                 'payment_method'=> $request->payment_method,
                 'status'        => (in_array($request->payment_method, ['cod', 'cash', 'CODBARANG'])) ? 'processing' : 'pending',
                 'shipping_address'=> $user->address_detail ?? 'Alamat tidak diatur',
                 'customer_latitude' => $request->latitude ?? null,
                 'customer_longitude' => $request->longitude ?? null,
            ]);
            $order->save();

            // Insert Items
            foreach ($cart as $key => $details) {
                 OrderItem::create([
                     'order_id' => $order->id,
                     'product_id' => $details['product_id'],
                     'product_variant_id' => $details['variant_id'],
                     'quantity' => $details['quantity'],
                     'price' => $details['price']
                 ]);
                 // Kurangi Stok
                 if ($details['variant_id']) { ProductVariant::where('id', $details['variant_id'])->decrement('stock', $details['quantity']); }
                 else { Product::where('id', $details['product_id'])->decrement('stock', $details['quantity']); }
            }

            $paymentUrl = null;

            // --- PROSES PEMBAYARAN (POTONG SALDO) ---
            if ($isPayWithSaldo) {
                $user->saldo -= $grand_total;
                $user->save();

                $order->status = 'paid'; // Langsung lunas
                // Idealnya panggil logika hit API Kirimin Aja di sini (Booking Ekspedisi)
                // (Anda bisa pindahkan fungsi booking KiriminAja dari Controller Web Anda ke sini)
            }
            // --- PROSES TRIPAY ---
            elseif (!in_array(strtolower($request->payment_method), ['cod', 'dana', 'doku_jokul'])) {

                $tripayResult = $this->_createTripayTransaction($order, $request->payment_method, $grand_total, $user, $orderItemsPayload);
                if ($tripayResult['success']) {
                    $paymentUrl = $tripayResult['data']['checkout_url'] ?? $tripayResult['data']['pay_url'];
                    $order->payment_url = $paymentUrl;
                    $order->pay_code = $tripayResult['data']['pay_code'] ?? null;
                    $order->qr_url = $tripayResult['data']['qr_url'] ?? null;
                } else {
                    throw new Exception($tripayResult['message']);
                }
            }
            // (Note: Logika DANA & Doku bisa disesuaikan sama seperti Tripay di atas, memanggil Service lalu save URL)

            $order->save();
            DB::commit();

            // Clear Cart Cache
            Cache::forget($cartKeyName);

            // ==========================================
            // RESPONSE API
            // ==========================================
            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat.',
                'data' => [
                    'invoice_number' => $order->invoice_number,
                    'total_amount' => $grand_total,
                    'payment_method' => $order->payment_method,
                    'status' => $order->status,
                    'payment_url' => $paymentUrl // <-- Dibuka oleh React Native menggunakan WebView / Linking
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
            'return_url'     => 'https://tokosancaka.com/mobile-payment-success', // URL tujuan setelah bayar
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
