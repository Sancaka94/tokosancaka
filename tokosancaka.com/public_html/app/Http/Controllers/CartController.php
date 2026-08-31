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
     * Helper: Dapatkan pemilik keranjang (User Login atau Session Guest)
     */
    private function getCartScope()
    {
        if (auth()->check()) {
            return ['user_id' => auth()->id()];
        }
        return ['session_id' => session()->getId()];
    }

    /**
     * Helper: Dapatkan Key Redis untuk Caching
     */
    private function getRedisCartKey()
    {
        if (auth()->check()) {
            return "cart_belanja:user:" . auth()->id();
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
        $scope = $this->getCartScope();
        $redisKey = $this->getRedisCartKey();

        // =========================================================================
        // 1. CEK REDIS (Bypass Query Database Jika Ada Cache)
        // =========================================================================
        try {
            $cachedCart = Redis::get($redisKey);
            if ($cachedCart) {
                $cart = json_decode($cachedCart, true);
                Log::info("LOG LOG: Keranjang diambil dari REDIS CACHE untuk key: {$redisKey}");
                return view('cart.index', compact('cart'));
            }
        } catch (\Exception $e) {
            Log::warning("LOG LOG: Redis Cache Error di Cart Index: " . $e->getMessage());
        }

        // =========================================================================
        // 2. JIKA REDIS KOSONG, TARIK DARI DATABASE (ANTI N+1)
        // =========================================================================
        $dbCart = DB::table('cartbelanja')->where($scope)->get();
        
        // Tarik semua ID Produk & Varian sekaligus untuk menghindari query berulang (N+1)
        $productIds = $dbCart->where('is_ppob', 0)->pluck('product_id')->filter()->unique()->toArray();
        $variantIds = $dbCart->where('is_ppob', 0)->pluck('variant_id')->filter()->unique()->toArray();

        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
        $variants = ProductVariant::whereIn('id', $variantIds)->get()->keyBy('id');

        $cart = [];
        $hasChanges = false; 

        foreach ($dbCart as $item) {
            $key = $item->is_ppob ? 'ppob_' . $item->ref_id : $item->product_id . '-' . ($item->variant_id ?? '0');
            $details = (array) $item; 

            // Lewati validasi jika ini produk PPOB
            if ($item->is_ppob == 1) {
                $cart[$key] = $details;
                continue; 
            }

            // Validasi Varian
            if (!empty($details['variant_id'])) {
                $variant = $variants[$details['variant_id']] ?? null;
                
                if (!$variant || $variant->stock <= 0) {
                    DB::table('cartbelanja')->where('id', $item->id)->delete();
                    $hasChanges = true;
                    continue;
                }
                
                $details['price'] = $variant->price;
                $details['current_stock'] = $variant->stock; 
            } else {
                // Validasi Produk Utama
                $product = $products[$details['product_id']] ?? null;

                if (!$product || strtolower(trim($product->status)) !== 'active') {
                    DB::table('cartbelanja')->where('id', $item->id)->delete();
                    $hasChanges = true;
                    continue;
                }

                $details['price'] = $product->price;
                $details['current_stock'] = $product->stock;
            }

            $cart[$key] = $details;
        }

        // =========================================================================
        // 3. SIMPAN KE REDIS (TTL: 30 Menit)
        // =========================================================================
        try {
            Redis::setex($redisKey, 1800, json_encode($cart));
        } catch (\Exception $e) {
            Log::warning("LOG LOG: Gagal menyimpan cache keranjang ke Redis: " . $e->getMessage());
        }

        return view('cart.index', compact('cart'));
    }

    /**
     * Menambahkan produk ke keranjang.
     */
    public function add(Request $request, Product $product)
    {
        // 🔥 FITUR IDEMPOTENCY (Pencegah Double Input) 🔥
        $idempotencyKey = $request->header('Idempotency-Key');
        if ($idempotencyKey && Cache::has('idemp_cart_add_' . $idempotencyKey)) {
            Log::warning("LOG LOG: Idempotency Terdeteksi! Request duplikat Add Cart dicegah untuk key: " . $idempotencyKey);
            return Cache::get('idemp_cart_add_' . $idempotencyKey);
        }

        $quantity = (int)$request->input('quantity', 1);
        $variantId = $request->input('product_variant_id') ?? $request->input('variant_id');

        Log::info('--- CEK DEBUGGING ADD TO CART ---');
        Log::info('Semua data dari Form Request: ', $request->all());
        Log::info('Data Product dari Parameter Method: ', $product->toArray());

        // Timpa model kosong dengan pencarian manual
        $product = Product::find($request->input('product_id'));

        if (!$product) {
            return back()->with('error', 'Produk tidak ditemukan.');
        }

        // Proteksi Toko Sendiri
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

        // 🔥 DATABASE TRANSACTION & ROW LOCKING 🔥
        $responseResult = DB::transaction(function () use ($product, $variantId, $quantity, $itemName, $itemPrice, $itemWeight, $request, $scope) {
            
            $existingItem = DB::table('cartbelanja')
                ->where($scope)
                ->where('product_id', $product->id)
                ->where(function($q) use ($variantId) {
                    if ($variantId) $q->where('variant_id', $variantId);
                    else $q->whereNull('variant_id');
                })->lockForUpdate()->first();

            $currentQuantityInCart = $existingItem ? $existingItem->quantity : 0;
            $newTotalQuantity = $currentQuantityInCart + $quantity;
            $stockToCheck = (int) ($variantId ? ProductVariant::find($variantId)->stock : $product->stock);

            if ($stockToCheck < $newTotalQuantity) {
                return back()->with('error', "Stok produk tidak mencukupi. Stok tersedia: {$stockToCheck}.");
            }

            if ($existingItem) {
                DB::table('cartbelanja')->where('id', $existingItem->id)->update([
                    'quantity' => $newTotalQuantity,
                    'updated_at' => now()
                ]);
            } else {
                DB::table('cartbelanja')->insert(array_merge($scope, [
                    "product_id" => $product->id, 
                    "variant_id" => $variantId,  
                    "name"       => $itemName,
                    "quantity"   => $quantity,
                    "price"      => $itemPrice,
                    "weight"     => $itemWeight, 
                    "store_id"   => $product->store_id, 
                    "image_url"  => $product->image_url,
                    "slug"       => $product->slug,
                    "is_ppob"    => 0,
                    "created_at" => now(),
                    "updated_at" => now()
                ]));
            }

            // Hapus cache keranjang
            try { Redis::del($this->getRedisCartKey()); } catch (\Exception $e) {}

            if ($request->input('action') === 'buy_now') {
                return redirect()->route('checkout.index'); 
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
            $scope = $this->getCartScope();
            $parts = explode('-', $request->id);
            $productId = $parts[0] ?? null;
            $variantId = (isset($parts[1]) && $parts[1] !== '0') ? $parts[1] : null;

            $result = DB::transaction(function () use ($scope, $productId, $variantId, $request) {
                
                $item = DB::table('cartbelanja')
                    ->where($scope)
                    ->where('product_id', $productId)
                    ->where(function($q) use ($variantId) {
                        if ($variantId) $q->where('variant_id', $variantId);
                        else $q->whereNull('variant_id');
                    })->lockForUpdate()->first();

                if ($item) {
                    $requestedQty = (int)$request->quantity;
                    
                    // Validasi Stok Asli dari Database
                    $stockToCheck = (int) ($variantId ? ProductVariant::find($variantId)->stock : Product::find($productId)->stock);
                    
                    if ($requestedQty > $stockToCheck) {
                        return response()->json([
                            'success' => false, 
                            'message' => "Stok tidak mencukupi (tersisa: {$stockToCheck}).",
                            'current_stock' => $stockToCheck
                        ], 422);
                    }

                    // Update
                    DB::table('cartbelanja')->where('id', $item->id)->update([
                        'quantity' => $requestedQty, 
                        'updated_at' => now()
                    ]);

                    try { Redis::del($this->getRedisCartKey()); } catch (\Exception $e) {}

                    // Persiapkan JSON khusus untuk Blade
                    $newSubtotal = $item->price * $requestedQty;
                    $grandTotal = $this->calculateCartTotals($scope);

                    return response()->json([
                        'success' => true, 
                        'message' => 'Kuantitas berhasil diperbarui.',
                        'quantity' => $requestedQty,
                        'subtotal' => $newSubtotal,
                        'total' => $grandTotal,
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
                        ->where(function($q) use ($variantId) {
                            if ($variantId) $q->where('variant_id', $variantId);
                            else $q->whereNull('variant_id');
                        })->delete();
                }

                if ($affected) {
                    try { Redis::del($this->getRedisCartKey()); } catch (\Exception $e) {}
                    
                    // Kembalikan JSON khusus untuk Blade
                    $grandTotal = $this->calculateCartTotals($scope);

                    return response()->json([
                        'success' => true, 
                        'message' => 'Produk berhasil dihapus.',
                        'total' => $grandTotal
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
                'sku' => 'required',
                'name' => 'required',
                'price' => 'required|numeric',
                'ref_id' => 'required',
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
                        "product_id"  => 0, 
                        "variant_id"  => null,
                        "name"        => $data['name'],
                        "quantity"    => 1,
                        "price"       => (int) $data['price'],
                        "image_url"   => $logoImage, 
                        "slug"        => $data['sku'],
                        "weight"      => 0,
                        "is_ppob"     => 1, 
                        "ref_id"      => $data['ref_id'],
                        "customer_no" => $data['customer_no'],
                        "store_id"    => 0,
                        "created_at"  => now(),
                        "updated_at"  => now()
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