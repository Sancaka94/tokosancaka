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
use Carbon\Carbon; // Pastikan Carbon di-import untuk logika jadwal pickup

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
    // BAGIAN 2: KERANJANG (CART API)
    // =========================================================================

    private function _getUserCartRecord()
    {
        $user = Auth::user() ?? auth('sanctum')->user();
        if (!$user) return null;

        $userId = $user->id_pengguna ?? $user->id;

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
                $weight = $variant->weight ?? $product->weight ?? 1000;
            } else {
                if ($product->productVariantTypes()->exists()) {
                     return response()->json(['success' => false, 'message' => 'Silakan pilih varian produk.'], 400);
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
                return response()->json(['success' => false, 'message' => "Stok tidak mencukupi. Tersedia: {$stockToCheck}"], 422);
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
                    "id"         => $itemKey,
                    "product_id" => $productId,
                    "variant_id" => $variantId,
                    "name"       => $itemName,
                    "quantity"   => $quantity,
                    "qty"        => $quantity,
                    "price"      => $itemPrice,
                    "image_url"  => $itemImageUrl,
                    "slug"       => $product->slug,
                    "weight"     => $weight,
                    "store_id"   => $product->store_id,
                    "store_name" => $product->store->name ?? 'Toko Sancaka',
                    "store_regency" => $product->store->regency ?? 'Ngawi',
                    "store_logo" => $storeLogo,
                ];
            }

            $newTotalAmount = 0; $newTotalWeight = 0;
            foreach($cart as $c) {
                $qty = $c['quantity'] ?? $c['qty'] ?? 1;
                $newTotalAmount += ($c['price'] * $qty);
                $newTotalWeight += (($c['weight'] ?? 1000) * $qty);
            }

            Cart::where('id', $cartRecord->id)->update([
                'item_description' => json_encode($cart),
                'total_amount' => $newTotalAmount,
                'weight' => $newTotalWeight,
                'store_id' => $product->store_id,
            ]);
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


    // =========================================================================
    // BAGIAN 3: SISTEM CHECKOUT (INIT -> GET -> PROCESS)
    // =========================================================================

    public function initCheckout(Request $request)
    {
        $request->validate(['selected_ids' => 'required|array']);

        $user = Auth::user() ?? auth('sanctum')->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        $userId = $user->id_pengguna ?? $user->id;

        $cartRecord = Cart::where('user_id', $userId)->first();
        if (!$cartRecord) return response()->json(['success' => false, 'message' => 'Keranjang kosong.'], 400);

        $cartItems = json_decode($cartRecord->item_description, true) ?? [];
        $checkoutItems = [];
        $subtotal = 0;
        $totalWeight = 0;
        $storeId = null;

        foreach ($cartItems as $item) {
            if (in_array($item['id'], $request->selected_ids)) {
                $checkoutItems[] = $item;
                $qty = $item['quantity'] ?? $item['qty'] ?? 1;
                $subtotal += ($item['price'] * $qty);
                $totalWeight += (($item['weight'] ?? 1000) * $qty);

                if (empty($storeId) && !empty($item['store_id'])) {
                    $storeId = $item['store_id'];
                }
            }
        }

        if (empty($checkoutItems)) {
            return response()->json(['success' => false, 'message' => 'Tidak ada produk valid yang dipilih.'], 400);
        }

        do {
            $invoiceNumber = 'SCK-CHK-' . strtoupper(Str::random(8));
        } while (Checkout::where('invoice_number', $invoiceNumber)->exists() || Order::where('invoice_number', $invoiceNumber)->exists());

        Checkout::create([
            'store_id' => $storeId,
            'user_id' => $userId,
            'invoice_number' => $invoiceNumber,
            'subtotal' => $subtotal,
            'weight' => $totalWeight,
            'status' => 'draft',
            'item_description' => json_encode($checkoutItems)
        ]);

        return response()->json([
            'success' => true,
            'data' => ['checkout_invoice' => $invoiceNumber]
        ]);
    }

    public function getCheckoutData(Request $request)
    {
        $user = Auth::user() ?? auth('sanctum')->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        $userId = $user->id_pengguna ?? $user->id;

        $invoice = $request->invoice;
        $checkout = Checkout::where('invoice_number', $invoice)->where('user_id', $userId)->first();

        if(!$checkout) return response()->json(['success'=>false, 'message'=>'Sesi checkout tidak valid atau kadaluarsa.'], 404);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => json_decode($checkout->item_description, true),
                'subtotal' => $checkout->subtotal,
                'weight' => $checkout->weight
            ]
        ]);
    }

    // 🔥 PERBAIKAN FATAL MENGGUNAKAN LOGIKA WEB CHECKOUTCONTROLLER 🔥
    public function processCheckout(Request $request, KiriminAjaService $kiriminAja, DanaSignatureService $danaSignature)
    {
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
        if (!$user) return response()->json(['success' => false, 'message' => 'Silakan login kembali.'], 401);
        $userId = $user->id_pengguna ?? $user->id;

        DB::beginTransaction();

        try {
            // 1. Ambil Data Checkout
            $checkoutRecord = Checkout::where('invoice_number', $request->checkout_invoice)->where('user_id', $userId)->first();
            if (!$checkoutRecord) throw new Exception("Sesi checkout tidak valid atau sudah diproses.");

            $cartItemsPayload = json_decode($checkoutRecord->item_description, true) ?? [];
            if(empty($cartItemsPayload)) throw new Exception("Keranjang kosong.");

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

            // 2. Ekstrak Kurir
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

            // POTONG SALDO CEK
            $isPayWithSaldo = in_array(strtoupper($request->payment_method), ['POTONG SALDO', 'SALDO']);
            if ($isPayWithSaldo && ($user->saldo < $grand_total)) {
                throw new Exception("Saldo tidak cukup. Tagihan: Rp " . number_format($grand_total,0,',','.') . ", Saldo Anda: Rp " . number_format($user->saldo,0,',','.'));
            }

            // 3. AMBIL IDENTITAS PENGIRIM SECARA AMAN (ANTI N/A)
            $storeId = $checkoutRecord->store_id;

            // Jika storeId kosong, kita PAKSA cari dari tabel Product!
            if (!$storeId && count($cartItemsPayload) > 0) {
                // Ambil ID Produk dari Payload
                $extractedProductId = $cartItemsPayload[0]['product_id'] ?? null;

                // Jika tidak ada 'product_id', ektrak dari SKU/ID (contoh: 'product_12')
                if (!$extractedProductId && isset($cartItemsPayload[0]['sku'])) {
                    $extractedProductId = (int) str_replace(['product_', 'variant_'], '', $cartItemsPayload[0]['sku']);
                }

                // Tembak langsung ke Database untuk mencari pemilik produk ini
                if ($extractedProductId) {
                    $productCheck = Product::find($extractedProductId);
                    if ($productCheck) {
                        $storeId = $productCheck->store_id;
                    }
                }
            }

            // Cari Toko beserta data User (Pemilik Toko)
            $store = Store::with('user')->find($storeId);

            // Mencegah N/A: Jika Toko ditarik dari DB, pakai datanya. Jika tidak, pakai Fallback.
            $senderName = $store?->name ?? ($cartItemsPayload[0]['store_name'] ?? 'Toko Sancaka');
            $senderPhone = $store?->user?->no_wa ?? '085745808809';
            $senderAddress = $store?->address_detail ?? 'Jl.Dr.Wahidin No.18A RT.22 RW.05, Ketanggi, Ngawi';

            // Fallback Kecamatan/Kelurahan Default Sancaka (Ngawi) jika di db Toko kosong
            $originDistId = $store->district_id ?? 4354;
            $originSubId  = $store->village_id ?? 40343;

            // 4. UPDATE TABEL CHECKOUT
            $checkoutRecord->update([
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

            // GENERATE INVOICE ORDER
            do {
                 $orderInvoice = 'SCK-ORD-' . strtoupper(Str::random(8));
            } while (Order::where('invoice_number', $orderInvoice)->exists());

            // 5. PINDAHKAN DATA KE ORDERS
            $order = new Order([
                 'store_id'      => $storeId,
                 'user_id'       => $checkoutRecord->user_id,
                 'invoice_number'=> $orderInvoice,
                 'subtotal'      => $checkoutRecord->subtotal,
                 'shipping_cost' => $checkoutRecord->shipping_cost,
                 'insurance_cost'=> $checkoutRecord->insurance_cost,
                 'total_amount'  => $checkoutRecord->total_amount,
                 'shipping_method'=> $checkoutRecord->shipping_method,
                 'payment_method'=> $checkoutRecord->payment_method,
                 'status'        => 'pending',

                 // SENDER MAPPING
                 'sender_name'      => $senderName,
                 'sender_phone'     => $senderPhone,
                 'sender_address'   => $senderAddress,
                 'sender_district_id'=> $originDistId,
                 'sender_subdistrict_id'=> $originSubId,

                 // RECEIVER MAPPING
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
                 'shipping_type'      => $shippingType,
                 'customer_latitude'  => $request->latitude,
                 'customer_longitude' => $request->longitude,
                 'courier_code'     => $courierCode,
                 'service_code'     => $serviceCode,
            ]);
            $order->save();

            // INSERT ITEMS KE TABEL RELASI
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
            if ($isPayWithSaldo || in_array(strtolower($request->payment_method), ['cod', 'cash', 'codbarang'])) {

                // 1. Potong Saldo & Update Status Pesanan
                if ($isPayWithSaldo) {
                    $user->saldo -= $grand_total;
                    $user->save();
                    $order->status = 'paid';
                } else {
                    $order->status = 'processing';
                }
                $order->save();

                // 2. Tembak Callback Internal (Untuk Notifikasi WA / Fonnte)
                // Pastikan class CheckoutController Web sudah di-import atau gunakan namespace penuh seperti ini
                $webController = new \App\Http\Controllers\CheckoutController(app(\App\Services\DanaSignatureService::class));
                $webController->processOrderCallback($orderInvoice, 'PAID', []);

                // 3. Proses Booking Kurir Otomatis via API KiriminAja
                try {
                    $isCOD = in_array(strtolower($request->payment_method), ['cod', 'codbarang']);
                    $now = \Carbon\Carbon::now('Asia/Jakarta');

                    // Penjadwalan Cerdas: Jika order di atas jam 17:00, kurir dijadwalkan besok jam 09:00 pagi.
                    if ($now->hour >= 17) {
                        $finalSchedule = $now->copy()->addDay()->format('Y-m-d 09:00:00');
                    } else {
                        $finalSchedule = $now->copy()->addHour()->format('Y-m-d H:i:s');
                    }

                   // Build Data Paket (Sesuai Dokumentasi KiriminAja & Dinamis)
                    $packagesPayload = [];
                    foreach($cartItemsPayload as $item) {
                        $qty = $item['quantity'] ?? ($item['qty'] ?? 1);
                        $w = $item['weight'] ?? 1000;

                        // Mengambil dimensi dari database produk (jika kosong, fallback ke 10cm)
                        $width  = !empty($item['width']) ? (int) $item['width'] : 10;
                        $height = !empty($item['height']) ? (int) $item['height'] : 10;
                        $length = !empty($item['length']) ? (int) $item['length'] : 10;

                        $packagesPayload[] = [
                            'order_id'                 => (string) $order->invoice_number,
                            'destination_name'         => (string) substr($order->receiver_name, 0, 50),
                            'destination_phone'        => (string) $order->receiver_phone,
                            'destination_address'      => (string) $order->receiver_address,
                            'destination_kecamatan_id' => (int) $order->receiver_district_id,
                            'destination_kelurahan_id' => (int) $order->receiver_subdistrict_id,

                            // [DINAMIS] Mengambil kodepos dari tabel Order
                            'destination_zipcode'      => (string) ($order->receiver_postal_code ?? '00000'),

                            'weight'                   => (int) ($w * $qty), // WAJIB GRAM & INTEGER

                            // [DINAMIS] Menggunakan dimensi produk dari database
                            'width'                    => $width,
                            'height'                   => $height,
                            'length'                   => $length,

                            'item_value'               => (int) ($item['price'] * $qty), // Minimal 1000
                            'shipping_cost'            => (int) $shipping_cost,
                            'service'                  => (string) $courierCode,
                            'service_type'             => (string) $serviceCode,
                            'item_name'                => (string) substr(($item['name'] ?? 'Produk Sancaka'), 0, 255),

                            // [DINAMIS] Cek jika ada tipe paket khusus di database, default 7
                            'package_type_id'          => (int) ($item['package_type_id'] ?? 7),

                            'cod'                      => $isCOD ? (int) $grand_total : 0,
                            'insurance_amount'         => $applied_insurance > 0 ? (int) ($item['price'] * $qty) : 0,
                        ];
                    }

                    // [DINAMIS] Mengambil nama platform dari configurasi server (.env -> APP_NAME)
                    $platformName = config('app.name', 'Sancaka');

                    // Build Data Utama untuk KiriminAja
                    $kaPayload = [
                        'address'       => (string) $senderAddress,
                        'phone'         => (string) $senderPhone,
                        'name'          => (string) substr($senderName, 0, 50),

                        // [DINAMIS] Mengambil kodepos dari data Toko atau profil User penjual
                        'zipcode'       => (string) ($store->postal_code ?? $store->user->postal_code ?? '00000'),

                        'kecamatan_id'  => (int) $originDistId,
                        'kelurahan_id'  => (int) $originSubId,
                        'latitude'      => (float) ($store->latitude ?? 0),
                        'longitude'     => (float) ($store->longitude ?? 0),
                        'packages'      => $packagesPayload,
                        'schedule'      => (string) $finalSchedule,
                        'platform_name' => (string) $platformName
                    ];

                    // Tembak API
                    $kiriminResponse = $kiriminAja->createExpressOrder($kaPayload);

                    // Auto-Retry Jika Gagal karena Jadwal Toko/Kurir Tutup
                    if (isset($kiriminResponse['status']) && $kiriminResponse['status'] === false) {
                        $pesanError = strtolower($kiriminResponse['text'] ?? '');
                        if (str_contains($pesanError, 'jadwal') || str_contains($pesanError, 'schedule')) {
                            // Paksa jadwal ke besok pagi
                            $kaPayload['schedule'] = \Carbon\Carbon::now('Asia/Jakarta')->addDay()->format('Y-m-d 09:00:00');
                            $kiriminResponse = $kiriminAja->createExpressOrder($kaPayload);
                        }
                    }

                    // Simpan Nomor Resi (AWB) ke Database
                    if (!empty($kiriminResponse['status']) && $kiriminResponse['status'] === true) {
                        $resi = $kiriminResponse['packages'][0]['awb'] ?? ($kiriminResponse['result']['awb_no'] ?? ($kiriminResponse['results'][0]['awb'] ?? null));
                        if ($resi) {
                            $order->shipping_reference = $resi;
                            $order->save();
                            \Illuminate\Support\Facades\Log::info("API MOBILE: Booking KiriminAja Sukses! Resi: {$resi}");
                        }
                    } else {
                        \Illuminate\Support\Facades\Log::error("API MOBILE: Gagal Generate Resi KA untuk Order {$order->invoice_number}: " . json_encode($kiriminResponse));
                    }
                } catch (\Exception $kaException) {
                    \Illuminate\Support\Facades\Log::error("API MOBILE: KiriminAja Timeout/Error: " . $kaException->getMessage());
                }

            // =========================================================================
            // BLOK UNTUK METODE GATEWAY (Diarahkan ke Web)
            // =========================================================================
            } elseif (strtoupper($request->payment_method) === 'GATEWAY') {
                $order->status = 'pending';
                // URL ini akan dibuka oleh aplikasi mobile
                $paymentUrl = url('/pembayaran?akun=' . urlencode($user->no_wa));
                $order->payment_url = $paymentUrl;
                $order->save();

            // =========================================================================
            // BLOK LAMA JIKA ADA METODE TRIPAY SPESIFIK YANG TERLEWAT
            // =========================================================================
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

            // 🔥 HAPUS ITEM DARI KERANJANG
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

            // Hapus Record Checkout
            $checkoutRecord->delete();

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

    // =========================================================================
    // BAGIAN 4: RIWAYAT PESANAN & PEMBATALAN
    // =========================================================================

    public function myOrders()
    {
        $user = Auth::user() ?? auth('sanctum')->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        $userId = $user->id_pengguna ?? $user->id;

        $orders = Order::with(['store', 'items.product', 'items.variant', 'user'])
            ->where('user_id', $userId)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function cancelOrder(Request $request, $invoice)
    {
        $user = Auth::user() ?? auth('sanctum')->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        $userId = $user->id_pengguna ?? $user->id;

        $order = Order::with('items')->where('invoice_number', $invoice)
            ->where('user_id', $userId)
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Pesanan tidak ditemukan.'], 404);
        }

        if (!in_array(strtolower($order->status), ['pending', 'unpaid', 'processing', 'paid'])) {
            return response()->json(['success' => false, 'message' => 'Pesanan sudah dikirim atau tidak dapat dibatalkan.'], 400);
        }

        $reason = $request->input('reason', 'Kesalahan data paket');

        if (!empty($order->shipping_reference) && !Str::contains($order->shipping_reference, 'MOCK')) {
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
                    return response()->json(['success' => false, 'message' => $errorMessage], 400);
                }
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Gagal terhubung ke API Ekspedisi.'], 500);
            }
        }

        $order->status = 'cancelled';
        $order->save();

        if (in_array(strtoupper($order->payment_method), ['POTONG SALDO', 'SALDO'])) {
            $user->saldo += $order->total_amount;
            $user->save();

            Log::info("Refund Saldo Sukses untuk Order {$order->invoice_number} sebesar Rp{$order->total_amount}");
        }

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
