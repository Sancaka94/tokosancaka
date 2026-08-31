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
     * Menampilkan halaman keranjang belanja (Dilengkapi Redis Caching & Anti N+1).
     */
    public function index()
    {
        $scope = $this->getCartScope();
        $redisKey = $this->getRedisCartKey();

        // =========================================================================
        // 1. CEK REDIS TERLEBIH DAHULU (Bypass Query Database Jika Ada Cache)
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
        // 2. JIKA REDIS KOSONG, TARIK DARI DATABASE
        // =========================================================================
        $dbCart = DB::table('cartbelanja')->where($scope)->get();
        
        // 🔥 ANTI N+1 QUERY 🔥
        // Kumpulkan semua ID Produk & Varian untuk ditarik dalam 1x Query
        $productIds = $dbCart->where('is_ppob', 0)->pluck('product_id')->filter()->unique()->toArray();
        $variantIds = $dbCart->where('is_ppob', 0)->pluck('variant_id')->filter()->unique()->toArray();

        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
        $variants = ProductVariant::whereIn('id', $variantIds)->get()->keyBy('id');

        $cart = [];
        $hasChanges = false; 

        foreach ($dbCart as $item) {
            $key = $item->is_ppob ? 'ppob_' . $item->ref_id : $item->product_id . '-' . ($item->variant_id ?? '0');
            $details = (array) $item; 

            // Lewati validasi jika ini produk PPOB/Digital
            if ($item->is_ppob == 1) {
                $cart[$key] = $details;
                continue; 
            }

            // Validasi Produk Varian (Tanpa query find di dalam loop)
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
                // Validasi Produk Utama (Tanpa query find di dalam loop)
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
        // 3. SIMPAN KE REDIS SETELAH VALIDASI SELESAI
        // =========================================================================
        try {
            // Cache data keranjang selama 2 Jam (7200 detik)
            Redis::setex($redisKey, 7200, json_encode($cart));
        } catch (\Exception $e) {
            Log::warning("LOG LOG: Gagal menyimpan cache keranjang ke Redis: " . $e->getMessage());
        }

        return view('cart.index', compact('cart'));
    }

    /**
     * Menambahkan produk ke keranjang (Beli Sekarang & Masukkan Keranjang).
     */
    public function add(Request $request, Product $product)
    {
        // =========================================================================
        // 🔥 FITUR IDEMPOTENCY (Pencegah Double Input Saat User Spam Klik) 🔥
        // =========================================================================
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

        $product = Product::find($request->input('product_id'));

        if (!$product) {
            return back()->with('error', 'Produk tidak ditemukan.');
        }

        // Proteksi: Jangan izinkan user beli barang dari tokonya sendiri
        if (auth()->check() && auth()->user()->store && auth()->user()->store->id == $product->store_id) {
            return back()->with('error', 'Anda tidak dapat membeli produk/jasa dari toko Anda sendiri.');
        }

        $itemPrice = $product->price;
        $itemName = $product->name;
        $itemWeight = max(1, $product->weight ?? 1); // Langsung validasi minimal 1 gram
        
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

        // =========================================================================
        // 🔥 DATABASE TRANSACTION & LOCK (Pencegah Race Condition Stok) 🔥
        // =========================================================================
        $responseResult = DB::transaction(function () use ($product, $variantId, $quantity, $itemName, $itemPrice, $itemWeight, $request, $scope) {
            
            // Kunci baris spesifik ini (jika ada) agar tidak bisa diubah proses lain bersamaan
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

            // 🔥 HAPUS CACHE REDIS KARENA ADA PERUBAHAN DATA 🔥
            try {
                Redis::del($this->getRedisCartKey());
            } catch (\Exception $e) {}

            // ⚡ LOGIKA REDIRECT PINTAR ⚡
            if ($request->input('action') === 'buy_now') {
                return redirect()->route('customer.checkout.index'); 
            }
            return back()->with('success', 'Produk berhasil ditambahkan ke keranjang!');
        });

        // Simpan Hasil ke Memori Idempotency (Berlaku 5 Menit)
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
                $affected = DB::table('cartbelanja')
                    ->where($scope)
                    ->where('product_id', $productId)
                    ->where(function($q) use ($variantId) {
                        if ($variantId) $q->where('variant_id', $variantId);
                        else $q->whereNull('variant_id');
                    })
                    ->update(['quantity' => (int)$request->quantity, 'updated_at' => now()]);

                if ($affected) {
                    try { Redis::del($this->getRedisCartKey()); } catch (\Exception $e) {}
                    return response()->json(['success' => true, 'message' => 'Kuantitas berhasil diperbarui.']);
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
                    $affected = DB::table('cartbelanja')
                        ->where($scope)
                        ->where('is_ppob', 1)
                        ->where('ref_id', $refId)
                        ->delete();
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
                        })
                        ->delete();
                }

                if ($affected) {
                    try { Redis::del($this->getRedisCartKey()); } catch (\Exception $e) {}
                    return response()->json(['success' => true, 'message' => 'Produk berhasil dihapus.']);
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
        
        // Hapus Cache Redis
        try { Redis::del($this->getRedisCartKey()); } catch (\Exception $e) {}

        return redirect()->route('customer.cart.index')->with('success', 'Keranjang berhasil dikosongkan.');
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
                // Kunci dengan lockForUpdate untuk mencegah double insert pada baris ppob yang sama
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
                    
                    // Bersihkan cache jika berhasil insert
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