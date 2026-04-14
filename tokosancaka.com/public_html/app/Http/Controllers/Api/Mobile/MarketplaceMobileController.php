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
use Carbon\Carbon;

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
    // HELPER DEBUG & STORE ID (BARU)
    // =========================================================================

    private function _getUserCartRecord()
    {
        $user = Auth::user() ?? auth('sanctum')->user();
        if (!$user) {
            Log::warning('Mobile API: _getUserCartRecord → User tidak ditemukan');
            return null;
        }

        $userId = $user->id_pengguna ?? $user->id;
        Log::info('Mobile API: _getUserCartRecord success', ['user_id' => $userId]);

        return Cart::firstOrCreate(
            ['user_id' => $userId],
            [
                'item_description' => json_encode([]),
                'total_amount' => 0,
                'weight' => 0
            ]
        );
    }

    private function getStoreIdFromCart($cartRecord)
    {
        if ($cartRecord && $cartRecord->store_id) {
            Log::info('getStoreIdFromCart → Dari kolom DB cart', ['store_id' => $cartRecord->store_id]);
            return $cartRecord->store_id;
        }

        $items = $cartRecord ? json_decode($cartRecord->item_description, true) ?? [] : [];
        if (empty($items)) {
            Log::warning('getStoreIdFromCart → Cart kosong');
            return null;
        }

        $firstItem = reset($items);

        if (!empty($firstItem['store_id'])) {
            Log::info('getStoreIdFromCart → Dari JSON item_description', ['store_id' => $firstItem['store_id']]);
            return $firstItem['store_id'];
        }

        $productId = $firstItem['product_id']
            ?? (isset($firstItem['id']) && str_starts_with($firstItem['id'], 'product_')
                ? (int) str_replace('product_', '', $firstItem['id'])
                : null);

        if ($productId) {
            $product = Product::select('store_id')->find($productId);
            $storeId = $product?->store_id;
            Log::info('getStoreIdFromCart → Fallback dari Product', ['product_id' => $productId, 'store_id' => $storeId]);
            return $storeId;
        }

        Log::warning('getStoreIdFromCart → Tidak bisa menemukan store_id');
        return null;
    }

    private function jsonResponse($data, $status = 200)
    {
        $debug = [
            'endpoint'  => debug_backtrace()[1]['function'] ?? 'unknown',
            'timestamp' => now()->toDateTimeString(),
            'user_id'   => Auth::id() ?? null,
        ];

        if (isset($data['debug_extra'])) {
            $debug = array_merge($debug, $data['debug_extra']);
            unset($data['debug_extra']);
        }

        $data['debug'] = $debug;   // ← Debug JSON untuk Expo / VSCode

        return response()->json($data, $status);
    }

    // =========================================================================
    // BAGIAN 1: ETALASE (PRODUK & HOMEPAGE)
    // =========================================================================

    public function home(Request $request)
    {
        Log::info('Mobile API: home called', $request->only(['search', 'category_id']));

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

        return $this->jsonResponse([
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
        Log::info('Mobile API: showCategory', ['category_id' => $id]);
        $category = Category::findOrFail($id);

        $products = Product::with(['store.user'])
            ->where('category_id', $category->id)
            ->where('status', 'active')
            ->where('stock', '>', 0)
            ->latest()
            ->paginate(12);

        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'category' => $category,
                'products' => $products
            ]
        ]);
    }

    public function showProduct($id)
    {
        Log::info('Mobile API: showProduct', ['product_id' => $id]);
        $product = Product::with(['category.attributes', 'store.user'])->findOrFail($id);

        if ($product->status !== 'active') {
            return $this->jsonResponse(['success' => false, 'message' => 'Produk tidak aktif atau dihapus'], 404);
        }

        $relatedProducts = Product::with(['category', 'store'])
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)->where('status', 'active')
            ->where('stock', '>', 0)->inRandomOrder()->limit(5)->get();

        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'product' => $product,
                'related_products' => $relatedProducts
            ]
        ]);
    }

    public function showStore($id)
    {
        Log::info('Mobile API: showStore', ['store_id' => $id]);
        $store = Store::with('user')->findOrFail($id);

        $products = Product::where('store_id', $store->id)
            ->where('status', 'active')
            ->where('stock', '>', 0)
            ->get();

        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'store' => $store,
                'products' => $products
            ]
        ]);
    }

    // =========================================================================
    // BAGIAN 2: KERANJANG (CART API)
    // =========================================================================

    public function getCart()
    {
        $cartRecord = $this->_getUserCartRecord();
        if (!$cartRecord) return $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);

        $cart = json_decode($cartRecord->item_description, true) ?? [];
        Log::info('Mobile API: getCart', ['total_items' => count($cart), 'cart_store_id' => $cartRecord->store_id]);

        $total = 0;
        foreach ($cart as $item) {
            $qty = $item['quantity'] ?? $item['qty'] ?? 1;
            $total += $item['price'] * $qty;
        }

        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'items' => array_values($cart),
                'total_amount' => $total,
                'total_items' => count($cart)
            ],
            'debug_extra' => ['cart_store_id' => $cartRecord->store_id ?? null]
        ]);
    }

    public function addToCart(Request $request)
    {
        Log::info('Mobile API: addToCart called', $request->only(['product_id', 'quantity', 'product_variant_id']));

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'product_variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $productId = $request->product_id;
        $quantity = $request->quantity;
        $variantId = $request->product_variant_id;

        $cartRecord = $this->_getUserCartRecord();
        if (!$cartRecord) return $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);

        $cart = json_decode($cartRecord->item_description, true) ?? [];

        try {
            $product = Product::with('store.user')->find($productId);
            if (!$product) return $this->jsonResponse(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);

            if ($variantId) {
                $variant = ProductVariant::with('product')->find($variantId);
                if (!$variant || $variant->product_id != $productId) {
                    return $this->jsonResponse(['success' => false, 'message' => 'Varian tidak valid.'], 400);
                }

                $stockToCheck = $variant->stock;
                $itemPrice = $variant->price;
                $itemName = $product->name . ' (' . str_replace(';', ', ', $variant->combination_string) . ')';
                $itemImageUrl = $variant->image_url ?? $product->image_url;
                $itemKey = 'variant_' . $variantId;
                $weight = $variant->weight ?? $product->weight ?? 1000;
            } else {
                if ($product->productVariantTypes()->exists()) {
                    return $this->jsonResponse(['success' => false, 'message' => 'Silakan pilih varian produk.'], 400);
                }

                $stockToCheck = $product->stock;
                $itemPrice = $product->price;
                $itemName = $product->name;
                $itemImageUrl = $product->image_url;
                $itemKey = 'product_' . $productId;
                $weight = $product->weight ?? 1000;
            }

            $currentQuantityInCart = $cart[$itemKey]['quantity'] ?? $cart[$itemKey]['qty'] ?? 0;
            $newTotalQuantity = $currentQuantityInCart + $quantity;

            if ($stockToCheck < $newTotalQuantity) {
                return $this->jsonResponse(['success' => false, 'message' => "Stok tidak mencukupi. Tersedia: {$stockToCheck}"], 422);
            }

            $storeLogo = $product->store->logo ?? $product->store->user->store_logo_path ?? null;

            if (isset($cart[$itemKey])) {
                $cart[$itemKey]['quantity'] = $newTotalQuantity;
                $cart[$itemKey]['qty'] = $newTotalQuantity;
                $cart[$itemKey]['image_url'] = $itemImageUrl;
                $cart[$itemKey]['store_logo'] = $storeLogo;
                $cart[$itemKey]['store_name'] = $product->store->name ?? 'Toko Sancaka';
            } else {
                $cart[$itemKey] = [
                    "id"              => $itemKey,
                    "product_id"      => $productId,
                    "variant_id"      => $variantId,
                    "name"            => $itemName,
                    "quantity"        => $quantity,
                    "qty"             => $quantity,
                    "price"           => $itemPrice,
                    "image_url"       => $itemImageUrl,
                    "slug"            => $product->slug,
                    "weight"          => $weight,
                    "store_id"        => $product->store_id,
                    "store_name"      => $product->store->name ?? 'Toko Sancaka',
                    "store_regency"   => $product->store->regency ?? 'Ngawi',
                    "store_logo"      => $storeLogo,
                ];
            }

            $newTotalAmount = 0;
            $newTotalWeight = 0;
            foreach ($cart as $c) {
                $qty = $c['quantity'] ?? $c['qty'] ?? 1;
                $newTotalAmount += ($c['price'] * $qty);
                $newTotalWeight += (($c['weight'] ?? 1000) * $qty);
            }

            Cart::where('id', $cartRecord->id)->update([
                'item_description' => json_encode($cart),
                'total_amount'     => $newTotalAmount,
                'weight'           => $newTotalWeight,
                'store_id'         => $product->store_id,
            ]);

            $cartRecord->refresh();
            $cartRecord->store_id = $product->store_id;
            $cartRecord->save();

            Log::info('Mobile API: addToCart success', [
                'product_id' => $productId,
                'store_id'   => $product->store_id,
                'total_items'=> count($cart)
            ]);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Produk ditambahkan ke keranjang.',
                'debug_extra' => ['store_id' => $product->store_id]
            ]);

        } catch (\Exception $e) {
            Log::error('Mobile API: addToCart error', ['message' => $e->getMessage()]);
            return $this->jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function updateCart(Request $request)
    {
        Log::info('Mobile API: updateCart called', $request->only(['id', 'quantity']));
        $itemKey = $request->input('id');
        $quantity = $request->input('quantity');

        if (!$itemKey || !$quantity || $quantity < 1) {
            return $this->jsonResponse(['success' => false, 'message' => 'Data tidak valid.'], 400);
        }

        $cartRecord = $this->_getUserCartRecord();
        $cart = json_decode($cartRecord->item_description, true) ?? [];

        if (!isset($cart[$itemKey])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Produk tidak ditemukan di keranjang.'], 404);
        }

        $item = $cart[$itemKey];
        $stockToCheck = 0;

        if (!empty($item['variant_id'])) {
            $variant = ProductVariant::find($item['variant_id']);
            if (!$variant) {
                unset($cart[$itemKey]);
                $cartRecord->item_description = json_encode($cart);
                $cartRecord->save();
                return $this->jsonResponse(['success' => false, 'message' => 'Varian sudah tidak tersedia.'], 404);
            }
            $stockToCheck = $variant->stock;
        } else {
            $product = Product::find($item['product_id']);
            if (!$product) {
                unset($cart[$itemKey]);
                $cartRecord->item_description = json_encode($cart);
                $cartRecord->save();
                return $this->jsonResponse(['success' => false, 'message' => 'Produk sudah tidak tersedia.'], 404);
            }
            $stockToCheck = $product->stock;
        }

        if ($stockToCheck < $quantity) {
            return $this->jsonResponse(['success' => false, 'message' => "Stok tidak mencukupi."], 422);
        }

        $cart[$itemKey]['quantity'] = (int)$quantity;
        $cart[$itemKey]['qty'] = (int)$quantity;

        $cartRecord->item_description = json_encode($cart);
        $cartRecord->save();

        Log::info('Mobile API: updateCart success', ['item_key' => $itemKey, 'new_qty' => $quantity]);

        return $this->jsonResponse(['success' => true, 'message' => 'Kuantitas diperbarui.']);
    }

    public function removeFromCart(Request $request)
    {
        Log::info('Mobile API: removeFromCart called', $request->only(['id']));
        $itemKey = $request->input('id');
        $cartRecord = $this->_getUserCartRecord();
        $cart = json_decode($cartRecord->item_description, true) ?? [];

        if (isset($cart[$itemKey])) {
            unset($cart[$itemKey]);
            $cartRecord->item_description = json_encode($cart);
            $cartRecord->save();

            Log::info('Mobile API: removeFromCart success', ['item_key' => $itemKey]);
            return $this->jsonResponse(['success' => true, 'message' => 'Produk dihapus.']);
        }

        return $this->jsonResponse(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);
    }

    // =========================================================================
    // BAGIAN 3: CHECKOUT
    // =========================================================================

    public function initCheckout(Request $request)
    {
        Log::info('Mobile API: initCheckout called', $request->only(['selected_ids']));

        $request->validate(['selected_ids' => 'required|array']);

        $user = Auth::user() ?? auth('sanctum')->user();
        if (!$user) return $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
        $userId = $user->id_pengguna ?? $user->id;

        $cartRecord = Cart::where('user_id', $userId)->first();
        if (!$cartRecord) return $this->jsonResponse(['success' => false, 'message' => 'Keranjang kosong.'], 400);

        $cartItems = json_decode($cartRecord->item_description, true) ?? [];
        $checkoutItems = [];
        $subtotal = 0;
        $totalWeight = 0;

        $storeId = $this->getStoreIdFromCart($cartRecord);

        foreach ($cartItems as $item) {
            if (in_array($item['id'], $request->selected_ids)) {
                $checkoutItems[] = $item;
                $qty = $item['quantity'] ?? $item['qty'] ?? 1;
                $subtotal += ($item['price'] * $qty);
                $totalWeight += (($item['weight'] ?? 1000) * $qty);
            }
        }

        if (empty($checkoutItems)) {
            return $this->jsonResponse(['success' => false, 'message' => 'Tidak ada produk valid yang dipilih.'], 400);
        }

        do {
            $invoiceNumber = 'SCK-CHK-' . strtoupper(Str::random(8));
        } while (Checkout::where('invoice_number', $invoiceNumber)->exists() || Order::where('invoice_number', $invoiceNumber)->exists());

        Checkout::create([
            'store_id'         => $storeId,
            'user_id'          => $userId,
            'invoice_number'   => $invoiceNumber,
            'subtotal'         => $subtotal,
            'weight'           => $totalWeight,
            'status'           => 'draft',
            'item_description' => json_encode($checkoutItems)
        ]);

        Log::info('Mobile API: initCheckout success', ['checkout_invoice' => $invoiceNumber, 'store_id' => $storeId]);

        return $this->jsonResponse([
            'success' => true,
            'data' => ['checkout_invoice' => $invoiceNumber],
            'debug_extra' => ['store_id' => $storeId]
        ]);
    }

    public function getCheckoutData(Request $request)
    {
        Log::info('Mobile API: getCheckoutData called', ['invoice' => $request->invoice]);

        $user = Auth::user() ?? auth('sanctum')->user();
        if (!$user) return $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
        $userId = $user->id_pengguna ?? $user->id;

        $invoice = $request->invoice;
        $checkout = Checkout::where('invoice_number', $invoice)->where('user_id', $userId)->first();

        if (!$checkout) return $this->jsonResponse(['success' => false, 'message' => 'Sesi checkout tidak valid atau kadaluarsa.'], 404);

        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'items' => json_decode($checkout->item_description, true),
                'subtotal' => $checkout->subtotal,
                'weight' => $checkout->weight
            ],
            'debug_extra' => ['checkout_store_id' => $checkout->store_id]
        ]);
    }

    public function processCheckout(Request $request, KiriminAjaService $kiriminAja, DanaSignatureService $danaSignature)
    {
        Log::info('Mobile API: processCheckout started', $request->only(['checkout_invoice', 'shipping_method', 'payment_method']));

        $request->validate([
            'checkout_invoice' => 'required|string',
            'shipping_method' => 'required|string',
            'payment_method' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'use_insurance' => 'boolean',
            'destination_district_id' => 'required',
            'destination_subdistrict_id' => 'required'
        ]);

        $user = Auth::user() ?? auth('sanctum')->user();
        if (!$user) return $this->jsonResponse(['success' => false, 'message' => 'Silakan login kembali.'], 401);
        $userId = $user->id_pengguna ?? $user->id;

        DB::beginTransaction();

        try {
            $checkoutRecord = Checkout::where('invoice_number', $request->checkout_invoice)->where('user_id', $userId)->first();
            if (!$checkoutRecord) throw new Exception("Sesi checkout tidak valid atau sudah diproses.");

            $cartItemsPayload = json_decode($checkoutRecord->item_description, true) ?? [];
            if (empty($cartItemsPayload)) throw new Exception("Keranjang kosong.");

            Log::info('Mobile API: Checkout data loaded', ['invoice' => $request->checkout_invoice, 'items_count' => count($cartItemsPayload)]);

            $subtotal = $checkoutRecord->subtotal;
            $orderItemsPayload = [];

            foreach ($cartItemsPayload as $key => $details) {
                $qty = isset($details['qty']) ? (int) $details['qty'] : ((int) ($details['quantity'] ?? 1));
                $price = (int) ($details['price'] ?? 0);
                $orderItemsPayload[] = [
                    'sku' => $details['id'] ?? $key,
                    'name' => $details['name'] ?? 'Produk',
                    'price' => $price,
                    'quantity' => $qty
                ];
            }

            $shippingParts = explode('-', $request->shipping_method);
            if (count($shippingParts) < 4) throw new Exception("Format metode pengiriman tidak valid.");

            $shippingType = $shippingParts[0];
            $courierCode = $shippingParts[1] ?? 'unknown';
            $serviceCode = $shippingParts[2] ?? 'REG';
            $shipping_cost = (int) ($shippingParts[3] ?? 0);
            $insurance_cost = (int) ($shippingParts[count($shippingParts) - 2] ?? 0);

            $useInsurance = $request->use_insurance && $insurance_cost > 0;
            $applied_insurance = $useInsurance ? $insurance_cost : 0;
            $grand_total = $subtotal + $shipping_cost + $applied_insurance;

            $isPayWithSaldo = in_array(strtoupper($request->payment_method), ['POTONG SALDO', 'SALDO']);
            if ($isPayWithSaldo && ($user->saldo < $grand_total)) {
                throw new Exception("Saldo tidak cukup. Tagihan: Rp " . number_format($grand_total,0,',','.') . ", Saldo Anda: Rp " . number_format($user->saldo,0,',','.'));
            }

            // === STORE ID & SENDER INFO ===
            $storeId = $checkoutRecord->store_id;

            if (!$storeId && count($cartItemsPayload) > 0) {
                $first = $cartItemsPayload[0];
                $extractedProductId = $first['product_id']
                    ?? ($first['id'] && str_starts_with($first['id'], 'product_')
                        ? (int) str_replace('product_', '', $first['id'])
                        : null);

                if ($extractedProductId) {
                    $productCheck = Product::select('store_id')->find($extractedProductId);
                    if ($productCheck) {
                        $storeId = $productCheck->store_id;
                        $checkoutRecord->update(['store_id' => $storeId]);
                        Log::info('Mobile API: Store ID di-recover dari product', ['store_id' => $storeId]);
                    }
                }
            }

            $store = Store::with('user')->find($storeId);

            $senderName = $store?->name ?? ($cartItemsPayload[0]['store_name'] ?? 'Toko Sancaka');
            $senderPhone = $store?->user?->no_wa ?? '085745808809';
            $senderAddress = $store?->address_detail ?? 'Jl.Dr.Wahidin No.18A RT.22 RW.05, Ketanggi, Ngawi';

            $originDistId = $store->district_id ?? 4354;
            $originSubId  = $store->village_id ?? 40343;

            Log::info('Mobile API: Sender info ready', ['store_id' => $storeId, 'sender_name' => $senderName]);

            // Update Checkout
            $checkoutRecord->update([
                'shipping_type' => $shippingType,
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
            ]);

            // Buat Order
            do {
                $orderInvoice = 'SCK-ORD-' . strtoupper(Str::random(8));
            } while (Order::where('invoice_number', $orderInvoice)->exists());

            $order = new Order([
                'store_id'      => $storeId,
                'shipping_type' => $shippingType,
                'user_id'       => $checkoutRecord->user_id,
                'invoice_number'=> $orderInvoice,
                'subtotal'      => $checkoutRecord->subtotal,
                'shipping_cost' => $checkoutRecord->shipping_cost,
                'insurance_cost'=> $checkoutRecord->insurance_cost,
                'total_amount'  => $checkoutRecord->total_amount,
                'shipping_method'=> $checkoutRecord->shipping_method,
                'payment_method'=> $checkoutRecord->payment_method,
                'status'        => 'pending',

                'sender_name'      => $senderName,
                'sender_phone'     => $senderPhone,
                'sender_address'   => $senderAddress,
                'sender_district_id'=> $originDistId,
                'sender_subdistrict_id'=> $originSubId,

                'receiver_name'    => $checkoutRecord->receiver_name,
                'receiver_phone'   => $checkoutRecord->receiver_phone,
                'receiver_address' => $checkoutRecord->receiver_address,
                'shipping_address' => $checkoutRecord->shipping_address,
                'customer_latitude' => $checkoutRecord->customer_latitude,
                'customer_longitude' => $checkoutRecord->customer_longitude,
                'receiver_district_id' => $checkoutRecord->receiver_district_id,
                'receiver_subdistrict_id' => $checkoutRecord->receiver_subdistrict_id,
                'receiver_village' => $checkoutRecord->receiver_village,

                'item_description' => $checkoutRecord->item_description,
                'weight'           => $checkoutRecord->weight,
                'courier_code'     => $courierCode,
                'service_code'     => $serviceCode,
            ]);
            $order->save();

            Log::info('Mobile API: Order created', ['order_invoice' => $orderInvoice, 'store_id' => $storeId]);

            // Insert Order Items + kurangi stok
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

                if ($variantId) {
                    ProductVariant::where('id', $variantId)->decrement('stock', $qty);
                } else {
                    Product::where('id', $productId)->decrement('stock', $qty);
                }
            }

            $paymentUrl = null;

            if ($isPayWithSaldo || in_array(strtolower($request->payment_method), ['cod', 'cash', 'codbarang'])) {

                if ($isPayWithSaldo) {
                    $user->saldo -= $grand_total;
                    $user->save();
                    $order->status = 'paid';
                } else {
                    $order->status = 'processing';
                }
                $order->save();

                $webController = new \App\Http\Controllers\CheckoutController(app(\App\Services\DanaSignatureService::class));
                $webController->processOrderCallback($orderInvoice, 'PAID', []);

                // KiriminAja
                try {
                    $isCOD = in_array(strtolower($request->payment_method), ['cod', 'codbarang']);
                    $now = Carbon::now('Asia/Jakarta');

                    if ($now->hour >= 17) {
                        $finalSchedule = $now->copy()->addDay()->format('Y-m-d 09:00:00');
                    } else {
                        $finalSchedule = $now->copy()->addHour()->format('Y-m-d H:i:s');
                    }

                    $packagesPayload = [];
                    foreach ($cartItemsPayload as $item) {
                        $qty = $item['quantity'] ?? ($item['qty'] ?? 1);
                        $w = $item['weight'] ?? 1000;
                        $packagesPayload[] = [
                            'order_id' => $order->invoice_number,
                            'destination_name' => $order->receiver_name,
                            'destination_phone' => $order->receiver_phone,
                            'destination_address' => $order->receiver_address,
                            'destination_kecamatan_id' => $order->receiver_district_id,
                            'destination_kelurahan_id' => $order->receiver_subdistrict_id,
                            'weight' => $w * $qty,
                            'width' => 10, 'height' => 10, 'length' => 10,
                            'item_value' => $item['price'] * $qty,
                            'item_name' => $item['name'] ?? 'Produk Sancaka',
                            'service' => $courierCode,
                            'service_type' => $serviceCode,
                            'shipping_cost' => $shipping_cost,
                            'package_type_id' => 1,
                            'cod' => $isCOD ? $grand_total : 0,
                            'insurance_amount' => $applied_insurance > 0 ? ($item['price'] * $qty) : 0,
                        ];
                    }

                    $kaPayload = [
                        'kecamatan_id' => $originDistId,
                        'kelurahan_id' => $originSubId,
                        'address' => $senderAddress,
                        'phone' => $senderPhone,
                        'name' => $senderName,
                        'zipcode' => $store->postal_code ?? '63211',
                        'latitude' => $store->latitude ?? 0,
                        'longitude' => $store->longitude ?? 0,
                        'packages' => $packagesPayload,
                        'category' => ($shippingType == 'cargo' || $shippingType == 'trucking') ? 'trucking' : 'regular',
                        'schedule' => $finalSchedule,
                        'platform_name' => 'TOKOSANCAKA.COM'
                    ];

                    $kiriminResponse = $kiriminAja->createExpressOrder($kaPayload);

                    if (isset($kiriminResponse['status']) && $kiriminResponse['status'] === false) {
                        $pesanError = strtolower($kiriminResponse['text'] ?? '');
                        if (str_contains($pesanError, 'jadwal') || str_contains($pesanError, 'schedule')) {
                            $kaPayload['schedule'] = Carbon::now()->addDay()->format('Y-m-d 09:00:00');
                            $kiriminResponse = $kiriminAja->createExpressOrder($kaPayload);
                        }
                    }

                    if (!empty($kiriminResponse['status']) && $kiriminResponse['status'] === true) {
                        $resi = $kiriminResponse['packages'][0]['awb'] ?? ($kiriminResponse['result']['awb_no'] ?? ($kiriminResponse['results'][0]['awb'] ?? null));
                        if ($resi) {
                            $order->shipping_reference = $resi;
                            $order->save();
                            Log::info("API MOBILE: Booking KA Sukses! Resi: {$resi}");
                        }
                    } else {
                        Log::error("API MOBILE: Gagal Generate Resi KA", ['response' => $kiriminResponse]);
                    }
                } catch (\Exception $kaException) {
                    Log::error("API MOBILE: KiriminAja Error", ['message' => $kaException->getMessage()]);
                }

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

            // Bersihkan keranjang
            $cartRecord = Cart::where('user_id', $userId)->first();
            if ($cartRecord) {
                $currentCart = json_decode($cartRecord->item_description, true) ?? [];
                $checkoutIds = array_column($cartItemsPayload, 'id');

                $newCart = array_filter($currentCart, function($item) use ($checkoutIds) {
                    return !in_array($item['id'], $checkoutIds);
                });

                if (empty($newCart)) {
                    $cartRecord->delete();
                } else {
                    $newCart = array_values($newCart);
                    $cartRecord->item_description = json_encode($newCart);

                    $newSub = 0; $newWeight = 0;
                    foreach($newCart as $nc) {
                        $qty = $nc['quantity'] ?? $nc['qty'] ?? 1;
                        $newSub += ($nc['price'] * $qty);
                        $newWeight += (($nc['weight'] ?? 1000) * $qty);
                    }
                    $cartRecord->total_amount = $newSub;
                    $cartRecord->weight = $newWeight;
                    $cartRecord->save();
                }
            }

            $checkoutRecord->delete();

            Log::info('Mobile API: processCheckout SUCCESS', ['invoice' => $orderInvoice]);

            return $this->jsonResponse([
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
            Log::error('Mobile API: processCheckout ERROR', [
                'message' => $e->getMessage(),
                'invoice' => $request->checkout_invoice ?? null
            ]);
            return $this->jsonResponse(['success' => false, 'message' => 'Gagal memproses order: ' . $e->getMessage()], 500);
        }
    }

    private function _createTripayTransaction($order, $methodChannel, $amount, $user, $items)
    {
        Log::info('Mobile API: _createTripayTransaction started', ['method' => $methodChannel]);

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
                Log::info('Mobile API: Tripay success');
                return ['success' => true, 'data' => $body['data']];
            }

            Log::warning('Mobile API: Tripay failed', ['body' => $body]);
            return ['success' => false, 'message' => $body['message'] ?? 'Tripay Failed'];
        } catch (\Exception $e) {
            Log::error('Mobile API: Tripay exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Koneksi Gateway bermasalah.'];
        }
    }

    // =========================================================================
    // BAGIAN 4: RIWAYAT PESANAN & PEMBATALAN
    // =========================================================================

    public function myOrders()
    {
        $user = Auth::user() ?? auth('sanctum')->user();
        if (!$user) return $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
        $userId = $user->id_pengguna ?? $user->id;

        Log::info('Mobile API: myOrders called', ['user_id' => $userId]);

        $orders = Order::with(['store', 'items.product', 'items.variant', 'user'])
            ->where('user_id', $userId)
            ->latest()
            ->get();

        return $this->jsonResponse([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function cancelOrder(Request $request, $invoice)
    {
        Log::info('Mobile API: cancelOrder called', ['invoice' => $invoice]);

        $user = Auth::user() ?? auth('sanctum')->user();
        if (!$user) return $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
        $userId = $user->id_pengguna ?? $user->id;

        $order = Order::with('items')->where('invoice_number', $invoice)
            ->where('user_id', $userId)
            ->first();

        if (!$order) {
            return $this->jsonResponse(['success' => false, 'message' => 'Pesanan tidak ditemukan.'], 404);
        }

        if (!in_array(strtolower($order->status), ['pending', 'unpaid', 'processing', 'paid'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Pesanan sudah dikirim atau tidak dapat dibatalkan.'], 400);
        }

        $reason = $request->input('reason', 'Kesalahan data paket');

        if (!empty($order->shipping_reference) && !Str::contains($order->shipping_reference, 'MOCK')) {
            // ... (kode cancel KiriminAja tetap sama)
            $mode = \App\Models\Api::getValue('KIRIMINAJA_MODE', 'global', 'sandbox');
            $baseUrl = $mode === 'production' ? 'https://api.kiriminaja.com/api/mitra' : 'https://tdi.kiriminaja.com/api/mitra';
            $apiKey = \App\Models\Api::getValue('KIRIMINAJA_API_KEY', $mode);

            try {
                $response = Http::withToken($apiKey)->post($baseUrl . '/cancel_shipment', [
                    'awb' => $order->shipping_reference,
                    'reason' => $reason
                ]);

                $result = $response->json();

                if (!$response->successful() || empty($result['status']) || $result['status'] === false) {
                    $errorMessage = $result['text'] ?? 'Gagal membatalkan di sistem ekspedisi.';
                    return $this->jsonResponse(['success' => false, 'message' => $errorMessage], 400);
                }
            } catch (\Exception $e) {
                return $this->jsonResponse(['success' => false, 'message' => 'Gagal terhubung ke API Ekspedisi.'], 500);
            }
        }

        $order->status = 'cancelled';
        $order->save();

        if (in_array(strtoupper($order->payment_method), ['POTONG SALDO', 'SALDO'])) {
            $user->saldo += $order->total_amount;
            $user->save();
            Log::info("Refund Saldo Sukses untuk Order {$order->invoice_number}");
        }

        foreach ($order->items as $item) {
            if ($item->product_variant_id) {
                ProductVariant::where('id', $item->product_variant_id)->increment('stock', $item->quantity);
            } else {
                Product::where('id', $item->product_id)->increment('stock', $item->quantity);
            }
        }

        Log::info('Mobile API: cancelOrder success', ['invoice' => $invoice]);

        return $this->jsonResponse([
            'success' => true,
            'message' => 'Pesanan berhasil dibatalkan.'
        ]);
    }
}
