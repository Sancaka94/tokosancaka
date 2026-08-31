<?php

namespace App\Http\Controllers;

use App\Models\OrderMarketplace;
use App\Models\OrderItemMarketplace; 
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\Models\Product; 
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    /**
     * Menampilkan halaman keranjang belanja.
     */
    public function index()
    {
        $cart = session()->get('cart', []);
        $hasChanges = false; 

        foreach ($cart as $key => $details) {
            
            // ⚡ FIX: LEWATI VALIDASI JIKA ITEM ADALAH PPOB ⚡
            if (isset($details['is_ppob']) && $details['is_ppob'] == true) {
                continue; 
            }

            // --- VALIDASI PRODUK FISIK / JASA ---
            if (!empty($details['variant_id'])) {
                $variant = ProductVariant::find($details['variant_id']);
                
                if (!$variant || $variant->stock <= 0) {
                    unset($cart[$key]);
                    $hasChanges = true;
                    continue;
                }
                
                $cart[$key]['price'] = $variant->price;
                $cart[$key]['current_stock'] = $variant->stock; 

            } else {
                $product = Product::find($details['product_id']);

                if (!$product || $product->status !== 'active') {
                    unset($cart[$key]);
                    $hasChanges = true;
                    continue;
                }

                $cart[$key]['price'] = $product->price;
                $cart[$key]['current_stock'] = $product->stock;
            }
        }

        if ($hasChanges) {
            session()->put('cart', $cart);
        }

        return view('cart.index', compact('cart'));
    }

    /**
     * Menambahkan produk ke keranjang (Untuk Masukkan Keranjang & Beli Sekarang).
     */
    public function add(Request $request, Product $product)
    {
        $quantity = (int)$request->input('quantity', 1);
        $variantId = $request->input('product_variant_id') ?? $request->input('variant_id');

        // ⚡ KUNCI PERBAIKAN: Format Key HARUS IDProduk-IDVarian (cth: "12-0")
        // Sistem Checkout Sancaka mendeteksi ID dari format ini
        $cartKey = $product->id . '-' . ($variantId ?? '0');
        
        $cart = session()->get('cart', []);

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

        // PENGAMANAN ONGKIR: Pastikan berat minimal 1 gram
        if ($itemWeight <= 0) {
            $itemWeight = 1;
        }

        // --- Validasi Stok ---
        $currentQuantityInCart = $cart[$cartKey]['quantity'] ?? 0;
        $newTotalQuantity = $currentQuantityInCart + $quantity;
        $stockToCheck = $variantId ? ProductVariant::find($variantId)->stock : $product->stock;

        if ($stockToCheck < $newTotalQuantity) {
            return back()->with('error', "Stok produk tidak mencukupi. Stok tersedia: {$stockToCheck}.");
        }

        // --- Simpan ke Session Cart ---
        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] = $newTotalQuantity;
        } else {
            $cart[$cartKey] = [
                "product_id" => $product->id, 
                "variant_id" => $variantId,  
                "name"       => $itemName,
                "quantity"   => $quantity,
                "price"      => $itemPrice,
                "weight"     => $itemWeight, 
                "store_id"   => $product->store_id, // Wajib ada untuk Checkout
                "image_url"  => $product->image_url,
                "slug"       => $product->slug,
                "is_ppob"    => false,
            ];
        }

        session()->put('cart', $cart);

        // ⚡ LOGIKA REDIRECT: BELI SEKARANG VS MASUK KERANJANG ⚡
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
            $cart = session()->get('cart');
            
            if (isset($cart[$request->id])) {
                $cart[$request->id]["quantity"] = (int)$request->quantity;
                session()->put('cart', $cart);
                return response()->json(['success' => true, 'message' => 'Kuantitas berhasil diperbarui.']);
            }
        }
        return response()->json(['success' => false, 'message' => 'Gagal memperbarui kuantitas.'], 404);
    }

    /**
     * Menghapus produk dari keranjang (AJAX).
     */
    public function remove(Request $request)
    {
        if ($request->id) {
            $cart = session()->get('cart');
            if (isset($cart[$request->id])) {
                unset($cart[$request->id]);
                session()->put('cart', $cart);
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
        session()->forget('cart');
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