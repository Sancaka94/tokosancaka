<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductVariant; 
use Illuminate\Support\Facades\Log; 
use Illuminate\Support\Str; 

class CartController extends Controller
{
    /**
     * Menampilkan halaman keranjang belanja.
     */
    public function index()
    {
        $cart = session()->get('cart', []);
        
        // Halaman keranjang standar
        return view('cart.index', compact('cart'));
    }

    /**
     * Menambahkan produk ke keranjang.
     * Tidak menggunakan Route Model Binding agar bisa handle variant_id secara manual.
     */
    public function add(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'product_variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $productId = $validated['product_id'];
        $quantity = $validated['quantity'];
        $variantId = $validated['product_variant_id'] ?? null; 

        $cart = session()->get('cart', []);

        $stockToCheck = 0;
        $itemPrice = 0;
        $itemName = '';
        $itemImageUrl = '';
        $itemWeight = 1; // Default minimal 1 gram agar API ongkir tidak error
        $cartKey = ''; 

        try {
            $product = Product::with('store')->find($productId); 

            if (!$product) {
                 return back()->with('error', 'Produk tidak ditemukan.');
            }

            // ⚡ VALIDASI MARKETPLACE: Jangan izinkan checkout produk toko sendiri 
            if (auth()->check() && auth()->user()->store && auth()->user()->store->id == $product->store_id) {
                return back()->with('error', 'Anda tidak dapat membeli produk/jasa dari toko Anda sendiri. Silakan gunakan akun pembeli lain.');
            }

            if ($variantId) {
                // --- Logika untuk Produk dengan Varian ---
                $variant = ProductVariant::with('product')->find($variantId);

                if (!$variant || $variant->product_id != $productId) {
                    return back()->with('error', 'Varian produk tidak valid.');
                }

                $stockToCheck = $variant->stock;
                $itemPrice = $variant->price;
                $itemName = $variant->product->name . ' (' . str_replace(';', ', ', $variant->combination_string) . ')';
                $itemImageUrl = $variant->image_url ?? $variant->product->image_url;
                $itemWeight = $variant->weight ?? $product->weight ?? 1;
                $cartKey = 'variant_' . $variantId;

            } else {
                // --- Logika untuk Produk tanpa Varian ---
                 if ($product->productVariantTypes()->exists()) {
                     return back()->with('error', 'Silakan pilih varian produk yang tersedia.');
                 }

                $stockToCheck = $product->stock;
                $itemPrice = $product->price;
                $itemName = $product->name;
                $itemImageUrl = $product->image_url;
                $itemWeight = $product->weight ?? 1;
                $cartKey = 'product_' . $productId;
            }

            // PENGAMANAN: Pastikan berat minimal 1 gram agar lolos Checkout
            if ($itemWeight <= 0) {
                $itemWeight = 1;
            }

            // --- Validasi Kuantitas vs Stok ---
            $currentQuantityInCart = $cart[$cartKey]['quantity'] ?? 0;
            $newTotalQuantity = $currentQuantityInCart + $quantity;

            if ($stockToCheck < $newTotalQuantity) {
                $errorMessage = "Stok produk tidak mencukupi. Stok tersedia: {$stockToCheck}.";
                return back()->with('error', $errorMessage);
            }

            // --- Logika Penambahan ke Keranjang ---
            if (isset($cart[$cartKey])) {
                $cart[$cartKey]['quantity'] = $newTotalQuantity;
            } else {
                $cart[$cartKey] = [
                    "product_id" => $productId, 
                    "variant_id" => $variantId, 
                    "name"       => $itemName,
                    "quantity"   => $quantity,
                    "price"      => $itemPrice,
                    "image_url"  => $itemImageUrl, 
                    "slug"       => $product->slug,
                    "weight"     => $itemWeight, 
                    "store_id"   => $product->store_id, // ⚡ WAJIB ADA AGAR LOLOS CHECKOUT
                    "is_ppob"    => false,
                ];
            }

            session()->put('cart', $cart);

            // ⚡ LOGIKA PINTAR: REDIRECT BELI SEKARANG VS MASUK KERANJANG ⚡
            if ($request->input('action') === 'buy_now') {
                return redirect()->route('customer.checkout.index'); 
            }

            return back()->with('success', 'Produk berhasil ditambahkan ke keranjang!'); 

        } catch (\Exception $e) {
            Log::error('Error adding to cart: ' . $e->getMessage() . ' - ' . $e->getFile() . ':' . $e->getLine());
            return back()->with('error', 'Terjadi kesalahan sistem saat menambahkan produk.');
        }
    }

    /**
     * Memperbarui kuantitas produk di keranjang (untuk AJAX).
     */
    public function update(Request $request)
    {
        $cartKey = $request->input('id'); 
        $quantity = $request->input('quantity');

        if (!$cartKey || !$quantity || $quantity < 1) {
             return response()->json(['success' => false, 'message' => 'Data tidak valid.'], 400);
        }

        $cart = session()->get('cart', []);

        if (!isset($cart[$cartKey])) {
            return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan di keranjang.'], 404);
        }

        $item = $cart[$cartKey];
        $stockToCheck = 0;

        if (!empty($item['variant_id'])) {
             $variant = ProductVariant::find($item['variant_id']);
             if (!$variant) {
                 unset($cart[$cartKey]);
                 session()->put('cart', $cart);
                 return response()->json(['success' => false, 'message' => 'Varian produk ini sudah tidak tersedia.', 'removed' => true], 404);
             }
             $stockToCheck = $variant->stock;
        } elseif (!empty($item['product_id'])) {
            $product = Product::find($item['product_id']);
             if (!$product) {
                 unset($cart[$cartKey]);
                 session()->put('cart', $cart);
                 return response()->json(['success' => false, 'message' => 'Produk ini sudah tidak tersedia.', 'removed' => true], 404);
             }
             if ($product->productVariantTypes()->exists()) {
                 unset($cart[$cartKey]);
                 session()->put('cart', $cart);
                 return response()->json(['success' => false, 'message' => 'Produk ini memerlukan pemilihan varian.', 'removed' => true], 400);
             }
             $stockToCheck = $product->stock;
        } else {
             unset($cart[$cartKey]);
             session()->put('cart', $cart);
             return response()->json(['success' => false, 'message' => 'Data keranjang tidak valid.', 'removed' => true], 400);
        }

        if ($stockToCheck < $quantity) {
             return response()->json(['success' => false, 'message' => "Stok tidak mencukupi (tersisa: {$stockToCheck})."], 422);
        }

        $cart[$cartKey]['quantity'] = (int)$quantity;
        session()->put('cart', $cart);

        $subtotal = $item['price'] * $quantity;
        $total = 0;
        foreach ($cart as $detail) {
            $total += $detail['price'] * $detail['quantity'];
        }

        return response()->json([
            'success' => true,
            'message' => 'Kuantitas berhasil diperbarui.',
            'subtotal' => $subtotal, 
            'total' => $total,       
            'quantity' => $quantity 
        ]);
    }

    /**
     * Menghapus produk dari keranjang (untuk AJAX).
     */
    public function remove(Request $request)
    {
        $cartKey = $request->input('id'); 

        if ($cartKey) {
            $cart = session()->get('cart', []);
            if (isset($cart[$cartKey])) {
                unset($cart[$cartKey]);
                session()->put('cart', $cart);

                 $total = 0;
                 foreach ($cart as $detail) {
                     $total += $detail['price'] * $detail['quantity'];
                 }

                return response()->json(['success' => true, 'message' => 'Produk dihapus dari keranjang.', 'total' => $total]);
            } else {
                return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan di keranjang.'], 404);
            }
        }

        return response()->json(['success' => false, 'message' => 'ID Produk tidak valid.'], 400);
    }

     /**
     * Mengosongkan keranjang belanja.
     */
    public function clear()
    {
        session()->forget('cart');
        return redirect()->route('cart.index')->with('success', 'Keranjang berhasil dikosongkan.');
    }

    /**
     * Tambah produk PPOB.
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
    
            $cart = session()->get('cart', []);
            $cartKey = 'ppob_' . $data['ref_id'];
            
            $logoImage = get_operator_logo($data['sku']);
    
            $cart[$cartKey] = [
                "product_id" => 0, 
                "variant_id" => null,
                "name"       => $data['name'],
                "quantity"   => 1,
                "price"      => (int) $data['price'],
                "image_url"  => $logoImage, 
                "slug"       => $data['sku'],
                "weight"     => 0,
                "is_ppob"    => true, 
                "ref_id"     => $data['ref_id'],
                "customer_no"=> $data['customer_no'],
                "store_id"   => 0
            ];
    
            session()->put('cart', $cart);
    
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error("Error addPpob: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}