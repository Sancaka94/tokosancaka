<?php

namespace App\Http\Controllers; 

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductVariant; 
use Illuminate\Support\Facades\Log; 
use Illuminate\Support\Facades\DB; // ⚡ TAMBAHAN BARU UNTUK DATABASE

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
     * Menampilkan halaman keranjang belanja.
     */
    public function index()
    {
        $scope = $this->getCartScope();
        $dbCart = DB::table('cartbelanja')->where($scope)->get();
        
        $cart = [];
        $hasChanges = false; 

        foreach ($dbCart as $item) {
            // Merakit kembali Key persis seperti session lama agar View Blade tidak perlu diubah
            $key = $item->is_ppob ? 'ppob_' . $item->ref_id : $item->product_id . '-' . ($item->variant_id ?? '0');
            $details = (array) $item; 

            // Lewati validasi jika ini produk PPOB/Digital
            if ($item->is_ppob == 1) {
                $cart[$key] = $details;
                continue; 
            }

            // Validasi Produk Varian
            if (!empty($details['variant_id'])) {
                $variant = ProductVariant::find($details['variant_id']);
                
                if (!$variant || $variant->stock <= 0) {
                    DB::table('cartbelanja')->where('id', $item->id)->delete();
                    $hasChanges = true;
                    continue;
                }
                
                $details['price'] = $variant->price;
                $details['current_stock'] = $variant->stock; 

            } else {
                // Validasi Produk Utama
                $product = Product::find($details['product_id']);

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

        return view('cart.index', compact('cart'));
    }

    /**
     * Menambahkan produk ke keranjang (Beli Sekarang & Masukkan Keranjang).
     */
    public function add(Request $request, Product $product)
    {
        $quantity = (int)$request->input('quantity', 1);
        $variantId = $request->input('product_variant_id') ?? $request->input('variant_id');

        Log::info('--- CEK DEBUGGING ADD TO CART ---');
        Log::info('Semua data dari Form Request: ', $request->all());
        Log::info('Data Product dari Parameter Method: ', $product->toArray());

        // ⚡ SISIPKAN 1 BARIS INI UNTUK MENGISI DATA PRODUK YANG KOSONG
        $product = Product::find($request->input('product_id'));

        // Proteksi: Jangan izinkan user beli barang dari tokonya sendiri
        if (auth()->check() && auth()->user()->store && auth()->user()->store->id == $product->store_id) {
            return back()->with('error', 'Anda tidak dapat membeli produk/jasa dari toko Anda sendiri.');
        }

        $itemPrice = $product->price;
        $itemName = $product->name;
        $itemWeight = $product->weight ?? 1;
        
        if ($variantId) {
            $variant = ProductVariant::find($variantId);
            if ($variant && $variant->product_id == $product->id) {
                $itemPrice = $variant->price;
                $itemWeight = $variant->weight ?? $itemWeight;
            } else {
                return back()->with('error', 'Varian produk tidak valid.');
            }
        } else {
            if ($product->productVariantTypes()->exists()) {
                return back()->with('error', 'Silakan pilih varian produk yang tersedia.');
            }
        }

        // Pastikan berat minimal 1 gram agar API Ongkir Checkout tidak error
        if ($itemWeight <= 0) {
            $itemWeight = 1;
        }

        $scope = $this->getCartScope();

        // Cari apakah produk sudah ada di database cart
        $existingItem = DB::table('cartbelanja')
            ->where($scope)
            ->where('product_id', $product->id)
            ->where(function($q) use ($variantId) {
                if ($variantId) $q->where('variant_id', $variantId);
                else $q->whereNull('variant_id');
            })->first();

        // Validasi Kuantitas vs Stok
        $currentQuantityInCart = $existingItem ? $existingItem->quantity : 0;
        $newTotalQuantity = $currentQuantityInCart + $quantity;
        $stockToCheck = (int) ($variantId ? ProductVariant::find($variantId)->stock : $product->stock);

        if ($stockToCheck < $newTotalQuantity) {
            return back()->with('error', "Stok produk tidak mencukupi. Stok tersedia: {$stockToCheck}.");
        }

        // Insert atau Update ke Database Cart
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

        // ⚡ LOGIKA REDIRECT PINTAR ⚡
        if ($request->input('action') === 'buy_now') {
            return redirect()->route('customer.checkout.index'); 
        }

        return back()->with('success', 'Produk berhasil ditambahkan ke keranjang!');
    }

    /**
     * Memperbarui kuantitas produk di keranjang (AJAX).
     */
    public function update(Request $request)
    {
        if ($request->id && $request->quantity) {
            $scope = $this->getCartScope();
            $parts = explode('-', $request->id);
            $productId = $parts[0] ?? null;
            $variantId = (isset($parts[1]) && $parts[1] !== '0') ? $parts[1] : null;

            DB::table('cartbelanja')
                ->where($scope)
                ->where('product_id', $productId)
                ->where(function($q) use ($variantId) {
                    if ($variantId) $q->where('variant_id', $variantId);
                    else $q->whereNull('variant_id');
                })
                ->update(['quantity' => (int)$request->quantity, 'updated_at' => now()]);

            return response()->json(['success' => true, 'message' => 'Kuantitas berhasil diperbarui.']);
        }
        return response()->json(['success' => false, 'message' => 'Gagal memperbarui kuantitas.'], 404);
    }

    /**
     * Menghapus produk dari keranjang (AJAX).
     */
    public function remove(Request $request)
    {
        if ($request->id) {
            $scope = $this->getCartScope();

            if (str_starts_with($request->id, 'ppob_')) {
                $refId = str_replace('ppob_', '', $request->id);
                DB::table('cartbelanja')
                    ->where($scope)
                    ->where('is_ppob', 1)
                    ->where('ref_id', $refId)
                    ->delete();
            } else {
                $parts = explode('-', $request->id);
                $productId = $parts[0] ?? null;
                $variantId = (isset($parts[1]) && $parts[1] !== '0') ? $parts[1] : null;

                DB::table('cartbelanja')
                    ->where($scope)
                    ->where('product_id', $productId)
                    ->where(function($q) use ($variantId) {
                        if ($variantId) $q->where('variant_id', $variantId);
                        else $q->whereNull('variant_id');
                    })
                    ->delete();
            }
            return response()->json(['success' => true, 'message' => 'Produk berhasil dihapus.']);
        }
        return response()->json(['success' => false, 'message' => 'Gagal menghapus produk.'], 404);
    }

    /**
     * Mengosongkan keranjang belanja.
     */
    public function clear()
    {
        $scope = $this->getCartScope();
        DB::table('cartbelanja')->where($scope)->delete();
        
        return redirect()->route('customer.cart.index')->with('success', 'Keranjang berhasil dikosongkan.');
    }

    /**
     * Menambahkan item PPOB ke keranjang.
     */
    public function addPpob(Request $request)
    {
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
    
            // Cek jika sudah ada
            $existing = DB::table('cartbelanja')
                ->where($scope)
                ->where('is_ppob', 1)
                ->where('ref_id', $data['ref_id'])
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
            }
    
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error("Error addPpob: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}