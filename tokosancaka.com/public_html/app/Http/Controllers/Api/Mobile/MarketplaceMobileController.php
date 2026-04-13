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
        $query = Product::with(['category', 'store.user'])->where('status', 'active')->where('stock', '>', 0);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // --- TAMBAHKAN INI: Filter Kategori ---
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        // --------------------------------------

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
                'products' => $products // Pagination Object
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

    // Ubah paginate(12) menjadi get() agar mengirim array bersih
    $products = Product::where('store_id', $store->id)
        ->where('status', 'active')
        ->where('stock', '>', 0)
        ->get(); // <--- Pakai get()

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
    // BAGIAN 3: CHECKOUT (PERSIAPAN & PROSES) - REVISI
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

        // Ambil data alamat untuk KiriminAja
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

        // Ambil dimensi dari produk pertama (fallback 5)
        $length = $firstProduct->length ?? 5;
        $width  = $firstProduct->width ?? 5;
        $height = $firstProduct->height ?? 5;

        // 1. Ambil Ongkir Express
        $expressOptions = $kiriminAja->getExpressPricing(
            $storeAddr['district_id'], $storeAddr['subdistrict_id'],
            $userAddr['district_id'], $userAddr['subdistrict_id'],
            $finalWeight, $length, $width, $height, $itemValue, null, $category, 1
        );

        // 2. Ambil Ongkir Instant (TAMBAHAN PERBAIKAN)
        $instantOptions = ['results' => []];
        if ($store->latitude && $store->longitude && $user->latitude && $user->longitude) {
            try {
                $instantRes = $kiriminAja->getInstantPricing(
                    $store->latitude, $store->longitude, $store->address_detail ?? $storeSearch,
                    $user->latitude, $user->longitude, $user->address_detail ?? $userSearch,
                    $finalWeight, $itemValue, 'motor'
                );

                // Format hasil instant agar sesuai dengan frontend
                if(isset($instantRes['status']) && $instantRes['status'] === true && isset($instantRes['result'])) {
                    foreach ($instantRes['result'] as $provider) {
                        if (isset($provider['costs'])) {
                            foreach ($provider['costs'] as $cost) {
                                $price = $cost['price']['total_price'] ?? 0;
                                if ($price > 0) {
                                    $instantOptions['results'][] = [
                                        'service' => $provider['name'],
                                        'service_name' => ucfirst($provider['name']) . ' ' . ucfirst($cost['service_type']),
                                        'service_type' => $cost['service_type'],
                                        'cost' => $price,
                                        'group' => 'instant'
                                    ];
                                }
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                Log::error('Mobile: Gagal mendapatkan ongkir Instant');
            }
        }

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

        // Gabungkan shipping options
        $allShippingOptions = array_merge($expressOptions['results'] ?? [], $instantOptions['results'] ?? []);

        return response()->json([
            'success' => true,
            'data' => [
                'cart_items' => array_values($cart),
                'total_weight' => $finalWeight,
                'subtotal' => $itemValue,
                'shipping_options' => $allShippingOptions,
                'payment_channels' => $tripayChannels,
                'user_address' => $userSearch,
                'store_address' => $storeSearch,
                'user_balance' => $user->saldo ?? 0,
                'dest_district_id' => $userAddr['district_id'] ?? null,
                'dest_subdistrict_id' => $userAddr['subdistrict_id'] ?? null
            ]
        ]);
    }

    public function processCheckout(Request $request, KiriminAjaService $kiriminAja, DanaSignatureService $danaSignature)
    {
        $request->validate([
            'shipping_method' => 'required|string',
            'payment_method' => 'required|string',
            // 👇 1. Tambahkan validasi ini agar Laravel bersiap menerima array
            'cart_items' => 'required|array',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'use_insurance' => 'boolean',
            'destination_district_id' => 'required',
            'destination_subdistrict_id' => 'required'
        ]);

        $user = Auth::user();
        $cart = $request->input('cart_items', []);
        $cartKeyName = $this->getCartKey();

        if (empty($cart)) return response()->json(['success' => false, 'message' => 'Keranjang kosong.'], 400);

        DB::beginTransaction();

        try {
            // 1. Kalkulasi Data dari Cart
            $subtotal = 0;
            $orderItemsPayload = [];
            $firstProduct = null;
            $totalWeight = 0;

            foreach ($cart as $key => $details) {
                // 👇 PERBAIKAN 2: React Native menggunakan key 'qty', bukan 'quantity'
                $qty = isset($details['qty']) ? (int) $details['qty'] : ((int) ($details['quantity'] ?? 1));
                $price = (int) ($details['price'] ?? 0);
                $weight = (int) ($details['weight'] ?? 1000);
                $productId = $details['product_id'] ?? $details['id']; // Jaga-jaga format id

                $subtotal += ($price * $qty);
                $totalWeight += ($weight * $qty);

                if (!$firstProduct) {
                    $firstProduct = Product::with('store.user')->find($productId);

                    if (!$firstProduct || !$firstProduct->store) {
                         throw new Exception("Produk atau Toko sudah tidak tersedia. Silakan bersihkan keranjang Anda dan coba lagi.");
                    }
                }

                $orderItemsPayload[] = [
                    'sku' => $key,
                    'name' => $details['name'] ?? 'Produk',
                    'price' => $price,
                    'quantity' => $qty
                ];
            }

            // 2. Parsing Shipping
            $shippingParts = explode('-', $request->shipping_method); // format: regular-jne-REG-15000-0

            if (count($shippingParts) < 4) {
                throw new Exception("Format metode pengiriman tidak valid.");
            }

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
                 'status'        => 'pending',

                 // 👇 PERBAIKAN: Tangkap Nama, No WA, dan Gabung Alamat Lengkap
                 'receiver_name'    => $request->receiver_name ?? $user->nama_lengkap,
                 'receiver_phone'   => $request->receiver_phone ?? $user->no_wa,
                 'receiver_address' => $request->receiver_address ?? $user->address_detail,
                 'shipping_address' => ($request->receiver_address ?? $user->address_detail) . ', ' . ($request->receiver_full_region ?? ''),

                 'customer_latitude' => $request->latitude ?? null,
                 'customer_longitude' => $request->longitude ?? null,
                 'receiver_district_id' => $request->destination_district_id,
                 'receiver_subdistrict_id' => $request->destination_subdistrict_id,
                 'receiver_village' => $user->village ?? 'Tidak Diketahui',
            ]);
            $order->save();

            foreach ($cart as $key => $details) {
                 $qty = isset($details['qty']) ? (int) $details['qty'] : ((int) ($details['quantity'] ?? 1));
                 $price = (int) ($details['price'] ?? 0);
                 $variantId = $details['variant_id'] ?? null;
                 $productId = $details['product_id'] ?? $details['id'];

                 OrderItem::create([
                     'order_id' => $order->id,
                     'product_id' => $productId,
                     'product_variant_id' => $variantId,
                     'quantity' => $qty,
                     'price' => $price
                 ]);
                 // Kurangi Stok
                 if ($variantId) { ProductVariant::where('id', $variantId)->decrement('stock', $qty); }
                 else { Product::where('id', $productId)->decrement('stock', $qty); }
            }

            $paymentUrl = null;

            // --- PROSES PEMBAYARAN & EKSPEDISI ---
            if ($isPayWithSaldo) {
                // POTONG SALDO
                $user->saldo -= $grand_total;
                $user->save();
                $order->status = 'paid';
                $order->save();

                // Panggil logika otomatis dari web controller untuk booking kurir & notif WA
                // Asumsi: Method processOrderCallback dibuat/diambil dari CheckoutController web
                $webController = new \App\Http\Controllers\CheckoutController(app(\App\Services\DanaSignatureService::class));
                $webController->processOrderCallback($invoiceNumber, 'PAID', []);

            } elseif (in_array(strtolower($request->payment_method), ['cod', 'cash', 'codbarang'])) {
                // LOGIKA COD / CASH
                $order->status = 'processing';
                $order->save();

                // Hit Web Controller supaya dibooking kurir KiriminAja otomatis
                $webController = new \App\Http\Controllers\CheckoutController(app(\App\Services\DanaSignatureService::class));
                // Simulasikan success webhook untuk mentrigger booking
                $webController->processOrderCallback($invoiceNumber, 'PAID', []);

            } elseif (!in_array(strtolower($request->payment_method), ['dana', 'doku_jokul'])) {
                // TRIPAY
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
            'return_url'     => url('/mobile-payment-success'), // URL tujuan setelah bayar
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

    // =========================================================================
    // BAGIAN 4: RIWAYAT PESANAN
    // =========================================================================

    public function myOrders()
    {
        $user = Auth::user();

        // 👇 PERBAIKAN: Tambahkan 'user' ke dalam array with()
        $orders = Order::with(['store', 'items.product', 'items.variant', 'user'])
            ->where('user_id', $user->id_pengguna ?? $user->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function cancelOrder(Request $request, $invoice)
    {
        $user = Auth::user();
        $order = Order::with('items')->where('invoice_number', $invoice)
            ->where('user_id', $user->id_pengguna ?? $user->id)
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Pesanan tidak ditemukan.'], 404);
        }

        // Pastikan hanya bisa dibatalkan jika status masih dikemas/diproses
        if (!in_array(strtolower($order->status), ['pending', 'unpaid', 'processing'])) {
            return response()->json(['success' => false, 'message' => 'Pesanan sudah dikirim atau tidak dapat dibatalkan.'], 400);
        }

        $reason = $request->input('reason', 'Kesalahan data paket');

        // 🔥 JIKA ORDER SUDAH PUNYA RESI, TEMBAK API KIRIMINAJA
        if (!empty($order->shipping_reference) && !Str::contains($order->shipping_reference, 'MOCK')) {
            $mode = \App\Models\Api::getValue('KIRIMINAJA_MODE', 'global', 'sandbox');
            // Menyesuaikan versi API KiriminAja yang kamu pakai (v3 atau v6)
            $baseUrl = $mode === 'production' ? 'https://api.kiriminaja.com/api/mitra' : 'https://tdi.kiriminaja.com/api/mitra';
            $apiKey = \App\Models\Api::getValue('KIRIMINAJA_API_KEY', $mode);

            try {
                // Tembak API Cancel Shipment KiriminAja
                $response = Http::withToken($apiKey)->post($baseUrl . '/cancel_shipment', [
                    'awb' => $order->shipping_reference,
                    'reason' => $reason
                ]);

                $result = $response->json();

                // Jika ditolak oleh KiriminAja
                if (!$response->successful() || empty($result['status']) || $result['status'] === false) {
                    $errorMessage = $result['text'] ?? 'Gagal membatalkan di sistem ekspedisi.';
                    return response()->json(['success' => false, 'message' => $errorMessage], 400);
                }
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Gagal terhubung ke API Ekspedisi.'], 500);
            }
        }

        // 🔥 BATALKAN LOKAL & KEMBALIKAN STOK
        $order->status = 'cancelled';
        $order->save();

        // 👇 TAMBAHKAN KODE REFUND INI
        // Jika pembeli menggunakan saldo dan pesanan sudah dipotong (bukan unpaid)
        if (in_array(strtoupper($order->payment_method), ['POTONG SALDO', 'SALDO'])) {
            $user->saldo += $order->total_amount;
            $user->save();

            // Opsional: Jika Anda punya tabel RiwayatMutasi/HistorySaldo, catat penambahan saldo di sini
            Log::info("Refund Saldo Sukses untuk Order {$order->invoice_number} sebesar Rp{$order->total_amount}");
        }
        // --------------------------------

        foreach ($order->items as $item) {
            if ($item->product_variant_id) {
                ProductVariant::where('id', $item->product_variant_id)->increment('stock', $item->quantity);
            } else {
                Product::where('id', $item->product_id)->increment('stock', $item->quantity);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil dibatalkan.'
        ]);
    }
}
