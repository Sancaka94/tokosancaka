<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product; // Pastikan model Product di-import

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
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);
    
        $productId = $request->input('product_id');
        $quantity  = $request->input('quantity');
        $product   = Product::findOrFail($productId);
    
        $cart = session()->get('cart', []);
    
        if (!empty($cart)) {
            $firstProductId = array_key_first($cart);
            $firstProduct   = Product::find($firstProductId);
    
            if ($firstProduct && $firstProduct->store_id !== $product->store_id) {
                return back()->with('error', 'Anda hanya bisa menambahkan produk dari toko yang sama.');
            }
        }
    
        $newQuantity = isset($cart[$productId])
            ? $cart[$productId]['quantity'] + $quantity
            : $quantity;
    
        if ($newQuantity > $product->stock) {
            return back()->with('error', "Stok produk tidak mencukupi. Stok tersedia: {$product->stock}");
        }
    
        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $quantity;
        } else {
            $cart[$productId] = [
                "name"      => $product->name,
                "weight"    => $product->weight,
                "quantity"  => $quantity,
                "price"     => $product->price,
                "image_url" => $product->image_url,
                "slug"      => $product->slug,
                "store_id"  => $product->store_id,
            ];
        }
    
        session()->put('cart', $cart);
    
        if ($request->action === 'buy') {
            return redirect('/checkout')->with('success', 'Produk berhasil ditambahkan, lanjut ke keranjang!');
        }
    
        return back()->with('success', 'Produk berhasil ditambahkan ke keranjang!');
    }


    /**
     * Memperbarui kuantitas produk di keranjang.
     */
    public function update(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = session()->get('cart');
        if(isset($cart[$request->product_id])) {
            $cart[$request->product_id]['quantity'] = $request->quantity;
            session()->put('cart', $cart);
        }

        return back()->with('success', 'Kuantitas berhasil diperbarui.');
    }

    /**
     * Menghapus produk dari keranjang.
     */
    public function remove(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $cart = session()->get('cart');
        if(isset($cart[$request->product_id])) {
            unset($cart[$request->product_id]);
            session()->put('cart', $cart);
        }

        return back()->with('success', 'Produk berhasil dihapus dari keranjang.');
    }
}
