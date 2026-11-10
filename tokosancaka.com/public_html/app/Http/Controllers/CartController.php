<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class CartController extends Controller
{
    /**
     * Helper: Dapatkan ID User yang konsisten (support id & id_pengguna)
     */
    private function getCurrentUserId()
    {
        if (!auth()->check()) {
            return null;
        }
        return auth()->id() ?? (auth()->user()->id_pengguna ?? null);
    }

    /**
     * Helper: Gabungkan keranjang Guest (session_id) ke User Login (user_id)
     * serta sinkronkan jika masih ada data di session('cart')
     */
    private function syncCartOwnership()
    {
        $sessionId = session()->getId();
        $userId = $this->getCurrentUserId();

        // 1. Jika User sudah login, klaim semua item di cartbelanja yang masih memakai session_id ini
        if ($userId) {
            $guestItems = DB::table('cartbelanja')
                ->whereNull('user_id')
                ->where('session_id', $sessionId)
                ->get();

            foreach ($guestItems as $gItem) {
                $existingUserItem = DB::table('cartbelanja')
                    ->where('user_id', $userId)
                    ->where('product_id', $gItem->product_id)
                    ->where('is_ppob', $gItem->is_ppob)
                    ->where(function ($q) use ($gItem) {
                        if ($gItem->variant_id) {
                            $q->where('variant_id', $gItem->variant_id);
                        } else {
                            $q->whereNull('variant_id');
                        }
                    })
                    ->first();

                if ($existingUserItem) {
                    DB::table('cartbelanja')->where('id', $existingUserItem->id)->update([
                        'quantity'   => $existingUserItem->quantity + $gItem->quantity,
                        'updated_at' => now(),
                    ]);
                    DB::table('cartbelanja')->where('id', $gItem->id)->delete();
                } else {
                    DB::table('cartbelanja')->where('id', $gItem->id)->update([
                        'user_id'    => $userId,
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // 2. Jika tabel cartbelanja kosong tapi di Redis / Session masih ada item, masukkan ke DB
        $dbCount = DB::table('cartbelanja')->where($this->getCartScope())->count();
        if ($dbCount === 0) {
            $fallbackItems = [];

            try {
                $cached = Redis::get($this->getRedisCartKey());
                if ($cached) {
                    $decoded = json_decode($cached, true);
                    if (is_array($decoded) && count($decoded) > 0) {
                        $fallbackItems = $decoded;
                    }
                }
            } catch (\Exception $e) {}

            if (empty($fallbackItems) && session()->has('cart')) {
                $sessCart = session('cart', []);
                if (is_array($sessCart) && count($sessCart) > 0) {
                    $fallbackItems = $sessCart;
                }
            }

            // Pulihkan ke tabel cartbelanja agar CheckoutController bisa membacanya
            if (!empty($fallbackItems)) {
                foreach ($fallbackItems as $item) {
                    $prodId = $item['product_id'] ?? 0;
                    $isPpob = (int) ($item['is_ppob'] ?? 0);

                    if ($prodId > 0 || $isPpob === 1) {
                        DB::table('cartbelanja')->insert(array_merge($this->getCartScope(), [
                            'product_id'  => $prodId,
                            'variant_id'  => $item['variant_id'] ?? null,
                            'name'        => $item['name'] ?? 'Produk',
                            'quantity'    => (int) ($item['quantity'] ?? 1),
                            'price'       => (int) ($item['price'] ?? 0),
                            'weight'      => (int) ($item['weight'] ?? 1000),
                            'store_id'    => $item['store_id'] ?? 0,
                            'image_url'   => $item['image_url'] ?? ($item['image'] ?? null),
                            'slug'        => $item['slug'] ?? null,
                            'is_ppob'     => $isPpob,
                            'ref_id'      => $item['ref_id'] ?? null,
                            'customer_no' => $item['customer_no'] ?? null,
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]));
                    }
                }
            }
        }
    }

    /**
     * Helper: Dapatkan pemilik keranjang (User Login atau Session Guest)
     */
    private function getCartScope()
    {
        if (auth()->check()) {
            return ['user_id' => $this->getCurrentUserId()];
        }
        return ['session_id' => session()->getId()];
    }

    /**
     * Helper: Dapatkan Key Redis untuk Caching
     */
    private function getRedisCartKey()
    {
        if (auth()->check()) {
            return "cart_belanja:user:" . $this->getCurrentUserId();
        }
        return "cart_belanja:guest:" . session()->getId();
    }

    /**
     * Helper: Hitung Total Keranjang untuk Balasan AJAX Blade
     */
    private function calculateCartTotals($scope)
    {
        $dbCart = DB::table('cartbelanja')->where($scope)->get();
        $grandTotal = 0;

        foreach ($dbCart as $item) {
            $grandTotal += ($item->price * $item->quantity);
        }

        return $grandTotal;
    }

    /**
     * Menampilkan halaman keranjang belanja (Anti N+1 & Redis Caching).
     */
    public function index()
    {
        // Pastikan keranjang Guest/Redis/Session tersinkron ke DB sebelum ditampilkan
        $this->syncCartOwnership();

        $scope = $this->getCartScope();
        $redisKey = $this->getRedisCartKey();

        // 1. TARIK DARI DATABASE (SUMBER KEBENARAN UTAMA AGAR SAMA DENGAN CHECKOUT)
        $dbCart = DB::table('cartbelanja')->where($scope)->get();

        $productIds = $dbCart->where('is_ppob', 0)->pluck('product_id')->filter()->unique()->toArray();
        $variantIds = $dbCart->where('is_ppob', 0)->pluck('variant_id')->filter()->unique()->toArray();

        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
        $variants = ProductVariant::whereIn('id', $variantIds)->get()->keyBy('id');

        $cart = [];

        foreach ($dbCart as $item) {
            $key = $item->is_ppob ? 'ppob_' . $item->ref_id : $item->product_id . '-' . ($item->variant_id ?? '0');
            $details = (array) $item;

            if ($item->is_ppob == 1) {
                $cart[$key] = $details;
                continue;
            }

            if (!empty($details['variant_id'])) {
                $variant = $variants[$details['variant_id']] ?? null;

                if (!$variant || $variant->stock <= 0) {
                    DB::table('cartbelanja')->where('id', $item->id)->delete();
                    continue;
                }

                $details['price'] = $variant->price;
                $details['current_stock'] = $variant->stock;
            } else {
                $product = $products[$details['product_id']] ?? null;

                if (!$product || strtolower(trim($product->status)) !== 'active') {
                    DB::table('cartbelanja')->where('id', $item->id)->delete();
                    continue;
                }

                $details['price'] = $product->price;
                $details['current_stock'] = $product->stock;
            }

            $cart[$key] = $details;
        }

        // 2. SINKRONKAN KE REDIS & SESSION
        try {
            if (!empty($cart)) {
                Redis::setex($redisKey, 1800, json_encode($cart));
                session()->put('cart', $cart);
            } else {
                Redis::del($redisKey);
                session()->forget('cart');
            }
        } catch (\Exception $e) {
            Log::warning("LOG LOG: Gagal sinkronisasi cache keranjang ke Redis: " . $e->getMessage());
        }

        return view('cart.index', compact('cart'));
    }

    /**
     * Menambahkan produk ke keranjang.
     */
    public function add(Request $request, $productId = null)
    {
        $idempotencyKey = $request->header('Idempotency-Key');
        if ($idempotencyKey && Cache::has('idemp_cart_add_' . $idempotencyKey)) {
            Log::warning("LOG LOG: Idempotency Terdeteksi! Request duplikat Add Cart dicegah untuk key: " . $idempotencyKey);
            return Cache::get('idemp_cart_add_' . $idempotencyKey);
        }

        $quantity = max(1, (int) $request->input('quantity', 1));
        $variantId = $request->input('product_variant_id') ?? $request->input('variant_id');

        $targetId = $request->input('product_id') ?? $productId;
        $product = Product::find($targetId);

        if (!$product) {
            return back()->with('error', 'Produk tidak ditemukan.');
        }

        if (auth()->check() && auth()->user()->store && auth()->user()->store->id == $product->store_id) {
            return back()->with('error', 'Anda tidak dapat membeli produk/jasa dari toko Anda sendiri.');
        }

        $itemPrice = $product->price;
        $itemName = $product->name;
        $itemWeight = max(1, $product->weight ?? 1);

        if ($variantId) {
            $variant = ProductVariant::find($variantId);
            if ($variant && $variant->product_id == $product->id) {
                $itemPrice = $variant->price;
                $itemWeight = max(1, $variant->weight ?? $itemWeight);
            } else {
                return back()->with('error', 'Varian produk tidak valid.');
            }
        } else {
            if ($product->productVariantTypes()->exists()) {
                return back()->with('error', 'Silakan pilih varian produk yang tersedia.');
            }
        }

        $scope = $this->getCartScope();

        $responseResult = DB::transaction(function () use ($product, $variantId, $quantity, $itemName, $itemPrice, $itemWeight, $request, $scope) {

            $existingItem = DB::table('cartbelanja')
                ->where($scope)
                ->where('product_id', $product->id)
                ->where(function ($q) use ($variantId) {
                    if ($variantId) {
                        $q->where('variant_id', $variantId);
                    } else {
                        $q->whereNull('variant_id');
                    }
                })->lockForUpdate()->first();

            $currentQuantityInCart = $existingItem ? $existingItem->quantity : 0;
            $newTotalQuantity = $currentQuantityInCart + $quantity;
            $stockToCheck = (int) ($variantId ? ProductVariant::find($variantId)->stock : $product->stock);

            if ($stockToCheck < $newTotalQuantity) {
                return back()->with('error', "Stok produk tidak mencukupi. Stok tersedia: {$stockToCheck}.");
            }

            if ($existingItem) {
                DB::table('cartbelanja')->where('id', $existingItem->id)->update([
                    'quantity'   => $newTotalQuantity,
                    'session_id' => session()->getId(),
                    'updated_at' => now()
                ]);
            } else {
                DB::table('cartbelanja')->insert(array_merge($scope, [
                    'session_id' => session()->getId(),
                    'product_id' => $product->id,
                    'variant_id' => $variantId,
                    'name'       => $itemName,
                    'quantity'   => $quantity,
                    'price'      => $itemPrice,
                    'weight'     => $itemWeight,
                    'store_id'   => $product->store_id ?? 0,
                    'image_url'  => $product->image_url,
                    'slug'       => $product->slug,
                    'is_ppob'    => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]));
            }

            try { Redis::del($this->getRedisCartKey()); } catch (\Exception $e) {}

            if ($request->input('action') === 'buy_now') {
                $checkoutRoute = \Illuminate\Support\Facades\Route::has('customer.checkout.index')
                    ? 'customer.checkout.index'
                    : 'checkout.index';
                return redirect()->route($checkoutRoute);
            }
            return back()->with('success', 'Produk berhasil ditambahkan ke keranjang!');
        });

        if ($idempotencyKey && !is_array($responseResult)) {
            Cache::put('idemp_cart_add_' . $idempotencyKey, $responseResult, now()->addMinutes(5));
        }

        return $responseResult;
    }

    /**
     * Memperbarui kuantitas produk di keranjang (AJAX).
     */
    public function update(Request $request)
    {
        $idempotencyKey = $request->header('Idempotency-Key');
        if ($idempotencyKey && Cache::has('idemp_cart_update_' . $idempotencyKey)) {
            return Cache::get('idemp_cart_update_' . $idempotencyKey);
        }

        if ($request->id && $request->quantity) {
            $this->syncCartOwnership();
            $scope = $this->getCartScope();
            $parts = explode('-', $request->id);
            $productId = $parts[0] ?? null;
            $variantId = (isset($parts[1]) && $parts[1] !== '0') ? $parts[1] : null;

            $result = DB::transaction(function () use ($scope, $productId, $variantId, $request) {

                $item = DB::table('cartbelanja')
                    ->where($scope)
                    ->where('product_id', $productId)
                    ->where(function ($q) use ($variantId) {
                        if ($variantId) {
                            $q->where('variant_id', $variantId);
                        } else {
                            $q->whereNull('variant_id');
                        }
                    })->lockForUpdate()->first();

                if ($item) {
                    $requestedQty = (int) $request->quantity;
                    $stockToCheck = (int) ($variantId ? ProductVariant::find($variantId)->stock : Product::find($productId)->stock);

                    if ($requestedQty > $stockToCheck) {
                        return response()->json([
                            'success'       => false,
                            'message'       => "Stok tidak mencukupi (tersisa: {$stockToCheck}).",
                            'current_stock' => $stockToCheck
                        ], 422);
                    }

                    DB::table('cartbelanja')->where('id', $item->id)->update([
                        'quantity'   => $requestedQty,
                        'updated_at' => now()
                    ]);

                    try { Redis::del($this->getRedisCartKey()); } catch (\Exception $e) {}

                    // Perbaikan operator perkalian (*)
                    $newSubtotal = $item->price * $requestedQty;
                    $grandTotal = $this->calculateCartTotals($scope);

                    return response()->json([
                        'success'       => true,
                        'message'       => 'Kuantitas berhasil diperbarui.',
                        'quantity'      => $requestedQty,
                        'subtotal'      => $newSubtotal,
                        'total'         => $grandTotal,
                        'current_stock' => $stockToCheck
                    ]);
                }

                return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);
            });

            if ($idempotencyKey) {
                Cache::put('idemp_cart_update_' . $idempotencyKey, $result, now()->addMinutes(5));
            }
            return $result;
        }

        return response()->json(['success' => false, 'message' => 'Gagal memperbarui kuantitas.'], 400);
    }

    /**
     * Menghapus produk dari keranjang (AJAX).
     */
    public function remove(Request $request)
    {
        $idempotencyKey = $request->header('Idempotency-Key');
        if ($idempotencyKey && Cache::has('idemp_cart_remove_' . $idempotencyKey)) {
            return Cache::get('idemp_cart_remove_' . $idempotencyKey);
        }

        if ($request->id) {
            $this->syncCartOwnership();
            $scope = $this->getCartScope();

            $result = DB::transaction(function () use ($scope, $request) {
                if (str_starts_with($request->id, 'ppob_')) {
                    $refId = str_replace('ppob_', '', $request->id);
                    $affected = DB::table('cartbelanja')->where($scope)->where('is_ppob', 1)->where('ref_id', $refId)->delete();
                } else {
                    $parts = explode('-', $request->id);
                    $productId = $parts[0] ?? null;
                    $variantId = (isset($parts[1]) && $parts[1] !== '0') ? $parts[1] : null;

                    $affected = DB::table('cartbelanja')
                        ->where($scope)
                        ->where('product_id', $productId)
                        ->where(function ($q) use ($variantId) {
                            if ($variantId) {
                                $q->where('variant_id', $variantId);
                            } else {
                                $q->whereNull('variant_id');
                            }
                        })->delete();
                }

                try { Redis::del($this->getRedisCartKey()); } catch (\Exception $e) {}
                session()->forget('cart');

                if ($affected) {
                    $grandTotal = $this->calculateCartTotals($scope);

                    return response()->json([
                        'success' => true,
                        'message' => 'Produk berhasil dihapus.',
                        'total'   => $grandTotal
                    ]);
                }
                return response()->json(['success' => false, 'message' => 'Gagal menghapus produk.'], 404);
            });

            if ($idempotencyKey) {
                Cache::put('idemp_cart_remove_' . $idempotencyKey, $result, now()->addMinutes(5));
            }
            return $result;
        }

        return response()->json(['success' => false, 'message' => 'ID Produk tidak valid.'], 400);
    }

    /**
     * Mengosongkan keranjang belanja.
     */
    public function clear()
    {
        $scope = $this->getCartScope();
        DB::table('cartbelanja')->where($scope)->delete();

        try { Redis::del($this->getRedisCartKey()); } catch (\Exception $e) {}
        session()->forget('cart');

        return redirect()->route('cart.index')->with('success', 'Keranjang berhasil dikosongkan.');
    }

    /**
     * Menambahkan item PPOB ke keranjang.
     */
    public function addPpob(Request $request)
    {
        $idempotencyKey = $request->header('Idempotency-Key');
        if ($idempotencyKey && Cache::has('idemp_cart_ppob_' . $idempotencyKey)) {
            Log::warning("LOG LOG: Idempotency Terdeteksi pada Add PPOB!");
            return Cache::get('idemp_cart_ppob_' . $idempotencyKey);
        }

        try {
            $data = $request->validate([
                'sku'         => 'required',
                'name'        => 'required',
                'price'       => 'required|numeric',
                'ref_id'      => 'required',
                'customer_no' => 'required',
            ]);

            $scope = $this->getCartScope();
            $logoImage = get_operator_logo($data['sku']);

            $result = DB::transaction(function () use ($scope, $data, $logoImage) {
                $existing = DB::table('cartbelanja')
                    ->where($scope)
                    ->where('is_ppob', 1)
                    ->where('ref_id', $data['ref_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$existing) {
                    DB::table('cartbelanja')->insert(array_merge($scope, [
                        'session_id'  => session()->getId(),
                        'product_id'  => 0,
                        'variant_id'  => null,
                        'name'        => $data['name'],
                        'quantity'    => 1,
                        'price'       => (int) $data['price'],
                        'image_url'   => $logoImage,
                        'slug'        => $data['sku'],
                        'weight'      => 0,
                        'is_ppob'     => 1,
                        'ref_id'      => $data['ref_id'],
                        'customer_no' => $data['customer_no'],
                        'store_id'    => 0,
                        'created_at'  => now(),
                        'updated_at'  => now()
                    ]));

                    try { Redis::del($this->getRedisCartKey()); } catch (\Exception $e) {}
                }

                return response()->json(['success' => true]);
            });

            if ($idempotencyKey) {
                Cache::put('idemp_cart_ppob_' . $idempotencyKey, $result, now()->addMinutes(5));
            }
            return $result;

        } catch (\Exception $e) {
            Log::error("Error addPpob: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
