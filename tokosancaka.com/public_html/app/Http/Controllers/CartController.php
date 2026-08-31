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
        
        return view('cart.index', compact('cart'));
    }

    /**
     * Menambahkan produk ke keranjang.
     * Tidak menggunakan Route Model Binding agar bisa handle variant_id.
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
        $cartKey = ''; 

        try {
            $product = Product::find($productId); 

            if (!$product) {
                 return back()->with('error', 'Produk tidak ditemukan.');
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
                $cartKey = 'product_' . $productId;
            }

            // --- Validasi Kuantitas vs Stok ---
            $currentQuantityInCart = $cart[$cartKey]['quantity'] ?? 0;
            $newTotalQuantity = $currentQuantityInCart + $quantity;

            if ($stockToCheck < $newTotalQuantity) {
                $errorMessage = "Stok produk tidak mencukupi. Stok tersedia: {$stockToCheck}.";
                if ($currentQuantityInCart > 0) {
                     $errorMessage .= " Anda sudah memiliki {$currentQuantityInCart} di keranjang.";
                } else {
                     $errorMessage .= " Anda mencoba menambahkan {$quantity}.";
                }
                return back()->with('error', $errorMessage);
            }

            // --- Logika Penambahan/Update ke Keranjang ---
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
                    "weight" => $variantId ? ($variant->weight ?? $product->weight ?? 0) : ($product->weight ?? 0), 
                ];
            }

            session()->put('cart', $cart);

            // =========================================================
            // ⚡ LOGIKA REDIRECT: BELI SEKARANG VS MASUKKAN KERANJANG
            // =========================================================
            if ($request->input('action') === 'buy_now') {
                // Jika klik Beli Sekarang, langsung bawa ke halaman checkout
                return redirect()->route('customer.checkout.index'); 
            }

            // Jika klik Masukkan Keranjang, tetap di halaman produk dengan pesan sukses
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
        $itemId = null; 

        if (!empty($item['variant_id'])) {
             $variant = ProductVariant::find($item['variant_id']);
             if (!$variant) {
                 unset($cart[$cartKey]);
                 session()->put('cart', $cart);
                 return response()->json(['success' => false, 'message' => 'Varian produk ini sudah tidak tersedia.', 'removed' => true], 404);
             }
             $stockToCheck = $variant->stock;
             $itemId = $variant->id;
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
             $itemId = $product->id;
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
}