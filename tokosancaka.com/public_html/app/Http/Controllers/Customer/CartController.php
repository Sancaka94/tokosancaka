<?php

namespace App\Http\Controllers\Customer;

use App\Models\OrderMarketplace;
use App\Models\OrderItemMarketplace; 
use App\Models\Marketplace;
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
            
            // ⚡ FIX: LEWATI VALIDASI DATABASE JIKA ITEM ADALAH PPOB ⚡
            if (isset($details['is_ppob']) && $details['is_ppob'] == true) {
                continue; 
            }

            // --- VALIDASI PRODUK FISIK ---
            if (isset($details['variant_id']) && $details['variant_id']) {
                $variant = ProductVariant::find($details['variant_id']);
                
                if (!$variant || $variant->stock <= 0) {
                    unset($cart[$key]);
                    $hasChanges = true;
                    continue;
                }
                
                $cart[$key]['price'] = $variant->price;
                $cart[$key]['current_stock'] = $variant->stock; 

            } else {
                // Produk Simple
                $product = Product::find($details['product_id']);

                if (!$product) {
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
     * ==========================================================
     * PERBAIKAN TOTAL FUNGSI ADD (DENGAN LOGIKA BELI SEKARANG)
     * ==========================================================
     */
    public function add(Request $request, Product $product)
    {
        $quantity = (int)$request->input('quantity', 1);
        $variantId = $request->input('variant_id', null);
        
        $cartId = $product->id . '-' . ($variantId ?? '0');

        $cart = session()->get('cart', []);

        $itemPrice = $product->price;
        $itemName = $product->name;
        
        if ($variantId) {
            $variant = ProductVariant::find($variantId);
            if ($variant) {
                $itemPrice = $variant->price;
                // Anda bisa mengaktifkan nama varian jika mau
                // $itemName = $product->name . ' (' . $variant->combination_string . ')';
            }
        }

        if (isset($cart[$cartId])) {
            $cart[$cartId]['quantity'] += $quantity;
        } else {
            $cart[$cartId] = [
                "product_id" => $product->id, 
                "variant_id" => $variantId,  
                "name" => $itemName,
                "quantity" => $quantity,
                "price" => $itemPrice,
                "image_url" => $product->image_url
            ];
        }

        session()->put('cart', $cart);
        
        // ⚡ 1. JIKA TOMBOL YANG DIKLIK ADALAH "BELI SEKARANG"
        if ($request->input('action') === 'buy_now') {
            // Langsung arahkan ke halaman checkout
            return redirect()->route('customer.checkout.index'); 
        }

        // ⚡ 2. JIKA TOMBOL "MASUKKAN KERANJANG"
        // Kembalikan ke halaman produk 
        return back()->with('success', 'Produk berhasil ditambahkan ke keranjang!');
    }

    /**
     * Memperbarui kuantitas produk di keranjang.
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

        Log::warning('Cart update failed', ['request' => $request->all()]);
        return response()->json(['success' => false, 'message' => 'Gagal memperbarui kuantitas.'], 404);
    }

    /**
     * Menghapus produk dari keranjang.
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
        
        Log::warning('Cart remove failed', ['request' => $request->all()]);
        return response()->json(['success' => false, 'message' => 'Gagal menghapus produk.'], 404);
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