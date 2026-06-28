<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductVariant; // <-- Tambahkan ini
use Illuminate\Support\Facades\Log; // <-- Tambahkan untuk logging error (opsional)
use Illuminate\Support\Str; // <-- Tambahkan untuk parsing cart key

class CartController extends Controller
{
    /**
     * Menampilkan halaman keranjang belanja.
     */
    public function index()
    {
        $cart = session()->get('cart', []);
        // Anda mungkin perlu mengambil detail produk/varian terbaru di sini jika harga/stok bisa berubah
        // Contoh:
        // foreach ($cart as $key => &$item) {
        //     if (Str::startsWith($key, 'variant_')) {
        //         $variant = ProductVariant::find($item['variant_id']);
        //         if ($variant) {
        //             $item['current_price'] = $variant->price;
        //             $item['current_stock'] = $variant->stock;
        //             // Update image jika perlu
        //         } else { $item['current_stock'] = 0; } // Tandai jika varian tidak ditemukan
        //     } elseif (Str::startsWith($key, 'product_')) {
        //         $product = Product::find($item['product_id']);
        //         if ($product) {
        //              $item['current_price'] = $product->price;
        //              $item['current_stock'] = $product->stock;
        //              // Update image jika perlu
        //         } else { $item['current_stock'] = 0; } // Tandai jika produk tidak ditemukan
        //     }
        // }
        // unset($item); // Penting setelah loop by reference
        // session()->put('cart', $cart); // Simpan update

        // Pastikan path view ini sesuai, misal 'marketplace.cart.index' atau 'customer.cart.index'
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
            // product_variant_id tidak wajib ada, tapi jika ada harus valid
            'product_variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $productId = $validated['product_id'];
        $quantity = $validated['quantity'];
        $variantId = $validated['product_variant_id'] ?? null; // Gunakan null jika tidak ada

        $cart = session()->get('cart', []);

        $stockToCheck = 0;
        $itemPrice = 0;
        $itemName = '';
        $itemImageUrl = '';
        $cartKey = ''; // Kunci unik untuk item di session cart

        try {
            $product = Product::find($productId); // Ambil produk utama untuk info dasar / fallback

            if (!$product) {
                 return back()->with('error', 'Produk tidak ditemukan.');
            }

            if ($variantId) {
                // --- Logika untuk Produk dengan Varian ---
                $variant = ProductVariant::with('product')->find($variantId);

                // Validasi tambahan: pastikan varian milik produk yang benar
                if (!$variant || $variant->product_id != $productId) {
                    return back()->with('error', 'Varian produk tidak valid.');
                }

                $stockToCheck = $variant->stock;
                $itemPrice = $variant->price;
                // Buat nama yang lebih deskriptif
                $itemName = $variant->product->name . ' (' . str_replace(';', ', ', $variant->combination_string) . ')';
                // Prioritaskan image varian jika ada, fallback ke image produk utama
                $itemImageUrl = $variant->image_url ?? $variant->product->image_url;
                $cartKey = 'variant_' . $variantId;

            } else {
                // --- Logika untuk Produk tanpa Varian ---
                 // PENTING: Cek apakah produk ini SEHARUSNYA memiliki varian
                 if ($product->productVariantTypes()->exists()) {
                     // Jika punya tipe varian, user harus memilih salah satu
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
                // Update quantity jika sudah ada
                $cart[$cartKey]['quantity'] = $newTotalQuantity;
            } else {
                // Tambah item baru
                $cart[$cartKey] = [
                    "product_id" => $productId, // Simpan ID produk utama
                    "variant_id" => $variantId, // Simpan ID varian (bisa null)
                    "name"       => $itemName,
                    "quantity"   => $quantity,
                    "price"      => $itemPrice,
                    "image_url"  => $itemImageUrl, // Simpan URL gambar
                    // Anda bisa tambahkan data lain jika perlu, misal slug, weight
                    "slug"       => $product->slug,
                    "weight" => $variantId ? ($variant->weight ?? $product->weight ?? 0) : ($product->weight ?? 0), // Ambil weight, fallback ke 0
                ];
            }

            session()->put('cart', $cart);

            // Arahkan ke halaman keranjang atau kembali ke produk dengan pesan sukses
            // return redirect()->route('cart.index')->with('success', 'Produk berhasil ditambahkan!');
            return back()->with('success', 'Produk berhasil ditambahkan ke keranjang!'); // Kembali ke halaman produk

        } catch (\Exception $e) {
            Log::error('Error adding to cart: ' . $e->getMessage() . ' - ' . $e->getFile() . ':' . $e->getLine());
            return back()->with('error', 'Terjadi kesalahan sistem saat menambahkan produk.');
        }
    }

    /**
     * Memperbarui kuantitas produk di keranjang (untuk AJAX).
     * ID yang diterima adalah cart key (misal: 'product_1' atau 'variant_5').
     */
    public function update(Request $request)
    {
        $cartKey = $request->input('id'); // ID di sini adalah cart key
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
        $itemId = null; // ID produk atau varian asli

        // Tentukan stok berdasarkan tipe item (produk atau varian)
        if (!empty($item['variant_id'])) {
             $variant = ProductVariant::find($item['variant_id']);
             if (!$variant) {
                 // Hapus dari keranjang jika varian sudah tidak ada
                 unset($cart[$cartKey]);
                 session()->put('cart', $cart);
                 return response()->json(['success' => false, 'message' => 'Varian produk ini sudah tidak tersedia.', 'removed' => true], 404);
             }
             $stockToCheck = $variant->stock;
             $itemId = $variant->id;
        } elseif (!empty($item['product_id'])) {
            $product = Product::find($item['product_id']);
             if (!$product) {
                  // Hapus dari keranjang jika produk sudah tidak ada
                 unset($cart[$cartKey]);
                 session()->put('cart', $cart);
                 return response()->json(['success' => false, 'message' => 'Produk ini sudah tidak tersedia.', 'removed' => true], 404);
             }
             // Pastikan lagi produk ini memang tidak punya varian (jika dihapus setelah masuk cart)
             if ($product->productVariantTypes()->exists()) {
                 unset($cart[$cartKey]);
                 session()->put('cart', $cart);
                 return response()->json(['success' => false, 'message' => 'Produk ini memerlukan pemilihan varian.', 'removed' => true], 400);
             }
             $stockToCheck = $product->stock;
             $itemId = $product->id;
        } else {
             // Data item cart tidak valid, hapus saja
             unset($cart[$cartKey]);
             session()->put('cart', $cart);
             return response()->json(['success' => false, 'message' => 'Data keranjang tidak valid.', 'removed' => true], 400);
        }


        // Validasi stok
        if ($stockToCheck < $quantity) {
             return response()->json(['success' => false, 'message' => "Stok tidak mencukupi (tersisa: {$stockToCheck})."], 422);
        }

        // Update kuantitas
        $cart[$cartKey]['quantity'] = (int)$quantity;
        session()->put('cart', $cart);

        // Hitung subtotal baru untuk item ini
        $subtotal = $item['price'] * $quantity;

        // Hitung total keseluruhan keranjang (opsional, bisa dilakukan di frontend)
        $total = 0;
        foreach ($cart as $detail) {
            $total += $detail['price'] * $detail['quantity'];
        }

        return response()->json([
            'success' => true,
            'message' => 'Kuantitas berhasil diperbarui.',
            'subtotal' => $subtotal, // Subtotal item yang diupdate
            'total' => $total,       // Total keranjang baru
            'quantity' => $quantity // Kuantitas baru untuk item ini
        ]);
    }

    /**
     * Menghapus produk dari keranjang (untuk AJAX).
     * ID yang diterima adalah cart key.
     */
    public function remove(Request $request)
    {
        $cartKey = $request->input('id'); // ID di sini adalah cart key

        if ($cartKey) {
            $cart = session()->get('cart', []);
            if (isset($cart[$cartKey])) {
                unset($cart[$cartKey]);
                session()->put('cart', $cart);

                 // Hitung total baru (opsional)
                 $total = 0;
                 foreach ($cart as $detail) {
                     $total += $detail['price'] * $detail['quantity'];
                 }

                return response()->json(['success' => true, 'message' => 'Produk dihapus dari keranjang.', 'total' => $total]);
            } else {
                // Item tidak ditemukan di keranjang
                return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan di keranjang.'], 404);
            }
        }

        // Jika cartKey tidak dikirim
        return response()->json(['success' => false, 'message' => 'ID Produk tidak valid.'], 400);
    }

     /**
     * Mengosongkan keranjang belanja.
     */
    public function clear()
    {
        session()->forget('cart');
        // Arahkan kembali ke halaman keranjang atau halaman lain
        return redirect()->route('cart.index')->with('success', 'Keranjang berhasil dikosongkan.');
    }

  

}
