<?php

namespace App\Http\Controllers\Customer;

use App\Models\OrderMarketplace;
use App\Models\OrderItemMarketplace; 
use App\Models\Marketplace;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log; // <-- Tambahkan untuk logging

class CartController extends Controller
{
    /**
     * Menampilkan halaman keranjang.
     */
    /**
     * Menampilkan halaman keranjang belanja.
     */
    public function index()
    {
        $cart = session()->get('cart', []);
        $hasChanges = false; // Penanda jika ada data yang berubah otomatis

        // Loop semua item di keranjang untuk validasi stok/harga terbaru
        foreach ($cart as $key => $details) {
            
            // -----------------------------------------------------------
            // ⚡ FIX: LEWATI VALIDASI DATABASE JIKA ITEM ADALAH PPOB ⚡
            // -----------------------------------------------------------
            if (isset($details['is_ppob']) && $details['is_ppob'] == true) {
                continue; // Langsung lanjut ke item berikutnya, jangan cek di tabel products
            }

            // --- VALIDASI PRODUK FISIK (Logic Asli) ---
            // Cek apakah ini varian atau produk simple
            if (isset($details['variant_id']) && $details['variant_id']) {
                $variant = ProductVariant::find($details['variant_id']);
                
                // Jika varian dihapus dari DB / Stok habis
                if (!$variant || $variant->stock <= 0) {
                    unset($cart[$key]);
                    $hasChanges = true;
                    continue;
                }
                
                // Update harga & stok terbaru (jika admin ubah harga saat user belanja)
                $cart[$key]['price'] = $variant->price;
                $cart[$key]['current_stock'] = $variant->stock; // Simpan stok update untuk validasi frontend

            } else {
                // Produk Simple
                $product = Product::find($details['product_id']);

                // Jika produk dihapus dari DB
                if (!$product) {
                    unset($cart[$key]);
                    $hasChanges = true;
                    continue;
                }

                // Update harga & stok terbaru
                $cart[$key]['price'] = $product->price;
                $cart[$key]['current_stock'] = $product->stock;
            }
        }

        // Jika ada item yang dihapus otomatis karena tidak valid, simpan session baru
        if ($hasChanges) {
            session()->put('cart', $cart);
            // Opsional: Redirect dengan pesan error agar user sadar itemnya hilang
            // return redirect()->route('cart.index')->with('error', 'Beberapa produk tidak tersedia dan telah dihapus.');
        }

        return view('cart.index', compact('cart'));
    }

    /**
     * ==========================================================
     * PERBAIKAN TOTAL FUNGSI ADD
     * ==========================================================
     * Menambahkan produk ke dalam keranjang (session)
     * dengan logika varian yang benar.
     */
    public function add(Request $request, Marketplace $product)
    {
        $quantity = (int)$request->input('quantity', 1);
        $variantId = $request->input('variant_id', null);
        
        // Membuat ID unik untuk keranjang
        // Cth: "12-5" (produk 12, varian 5) atau "12-0" (produk 12, tanpa varian)
        $cartId = $product->id . '-' . ($variantId ?? '0');

        $cart = session()->get('cart', []);

        $itemPrice = $product->price;
        $itemName = $product->name;
        
        // Jika ada varian, ambil data dari varian
        if ($variantId) {
            $variant = ProductVariant::find($variantId);
            if ($variant) {
                // (Opsional) Sesuaikan nama & harga jika beda
                // $itemName = $product->name . ' (' . $variant->combination_string . ')';
                // $itemPrice = $variant->price; 
            }
        }

        // Jika item sudah ada di keranjang, tambahkan kuantitasnya
        if (isset($cart[$cartId])) {
            $cart[$cartId]['quantity'] += $quantity;
        } else {
            // Jika item baru, buat entri baru
            $cart[$cartId] = [
                "product_id" => $product->id, // <-- KUNCI #1: INI YANG HILANG
                "variant_id" => $variantId,  // <-- KUNCI #2: INI YANG HILANG
                "name" => $itemName,
                "quantity" => $quantity,
                "price" => $itemPrice,
                "image_url" => $product->image_url
            ];
        }

        session()->put('cart', $cart);
        
        // Mengarahkan ke halaman keranjang setelah berhasil menambahkan produk.
        return redirect()->route('customer.cart.index')->with('success', 'Produk berhasil ditambahkan ke keranjang!');
    }

    /**
     * ==========================================================
     * PERBAIKAN FUNGSI UPDATE
     * ==========================================================
     * Memperbarui kuantitas produk di keranjang.
     * Fungsi ini sudah kompatibel dengan cart.index.blade.php Anda
     */
    public function update(Request $request)
    {
        // $request->id sekarang adalah 'cartId' (cth: "12-5")
        if ($request->id && $request->quantity) {
            $cart = session()->get('cart');
            
            if (isset($cart[$request->id])) {
                $cart[$request->id]["quantity"] = (int)$request->quantity;
                session()->put('cart', $cart);

                // Perbaikan: Kirim JSON response, BUKAN redirect
                // Ini sesuai dengan kode JavaScript 'fetch' Anda
                return response()->json(['success' => true, 'message' => 'Kuantitas berhasil diperbarui.']);
            }
        }

        Log::warning('Cart update failed', ['request' => $request->all()]);
        // Perbaikan: Kirim JSON error
        return response()->json(['success' => false, 'message' => 'Gagal memperbarui kuantitas.'], 404);
    }

    /**
     * ==========================================================
     * PERBAIKAN FUNGSI REMOVE
     * ==========================================================
     * Menghapus produk dari keranjang.
     * Fungsi ini sudah kompatibel dengan cart.index.blade.php Anda
     */
    public function remove(Request $request)
    {
        // $request->id sekarang adalah 'cartId' (cth: "12-5")
        if ($request->id) {
            $cart = session()->get('cart');
            if (isset($cart[$request->id])) {
                unset($cart[$request->id]);
                session()->put('cart', $cart);
            }
            
            // Perbaikan: Kirim JSON response, BUKAN redirect
            // Ini sesuai dengan kode JavaScript 'fetch' Anda
            return response()->json(['success' => true, 'message' => 'Produk berhasil dihapus.']);
        }
        
        Log::warning('Cart remove failed', ['request' => $request->all()]);
        // Perbaikan: Kirim JSON error
        return response()->json(['success' => false, 'message' => 'Gagal menghapus produk.'], 404);
    }

      /**
     * TAMBAHKAN FUNCTION INI SEBELUM KURUNG KURAWAL TERAKHIR '}'
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
            
            // 🔥 PAKAI HELPER DISINI (Simple & Clean)
            $logoImage = get_operator_logo($data['sku']);
    
            $cart[$cartKey] = [
                "product_id" => 0, 
                "variant_id" => null,
                "name"       => $data['name'],
                "quantity"   => 1,
                "price"      => (int) $data['price'],
                "image_url"  => $logoImage, // <--- Hasil dari helper
                "slug"       => $data['sku'],
                "weight"     => 0,
                "is_ppob"    => true, 
                "ref_id"     => $data['ref_id'],
                "customer_no"=> $data['customer_no']
            ];
    
            session()->put('cart', $cart);
    
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error("Error addPpob: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}