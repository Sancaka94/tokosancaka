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
            'quantity' => 'required|integer|min:1',
        ]);

        $productId = $request->input('product_id');
        $quantity = $request->input('quantity');
        $product = Product::findOrFail($productId);

        $cart = session()->get('cart', []);

        if(isset($cart[$productId])) {
            // Jika produk sudah ada, tambahkan kuantitasnya
            $cart[$productId]['quantity'] += $quantity;
        } else {
            // Jika produk baru, tambahkan ke keranjang
            $cart[$productId] = [
                "name" => $product->name,
                "quantity" => $quantity,
                "price" => $product->price,
                "image_url" => $product->image_url,
                "slug" => $product->slug // Tambahkan slug untuk link
            ];
        }

        session()->put('cart', $cart);

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
