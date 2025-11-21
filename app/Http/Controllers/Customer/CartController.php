<?php

namespace App\Http\Controllers\Customer;

use App\Models\Marketplace;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CartController extends Controller
{
    /**
     * Menampilkan halaman keranjang.
     */
    public function index()
    {
        $cart = session()->get('cart', []);
        return view('customer.cart.index', compact('cart'));
    }

    /**
     * Menambahkan produk ke dalam keranjang (session).
     */
    public function add(Request $request, Marketplace $product)
    {
        $cart = session()->get('cart', []);
        $quantity = $request->input('quantity', 1);

        if(isset($cart[$product->id])) {
            $cart[$product->id]['quantity'] += $quantity;
        } else {
            $cart[$product->id] = [
                "name" => $product->name,
                "quantity" => $quantity,
                "price" => $product->price,
                "image_url" => $product->image_url
            ];
        }

        session()->put('cart', $cart);
        // Mengarahkan ke halaman keranjang setelah berhasil menambahkan produk.
        return redirect()->route('customer.cart.index')->with('success', 'Produk berhasil ditambahkan ke keranjang!');
    }

    /**
     * Memperbarui kuantitas produk di keranjang.
     */
    public function update(Request $request)
    {
        if($request->id && $request->quantity){
            $cart = session()->get('cart');
            if(isset($cart[$request->id])) {
                $cart[$request->id]["quantity"] = $request->quantity;
                session()->put('cart', $cart);
                return redirect()->back()->with('success', 'Kuantitas berhasil diperbarui.');
            }
        }
        return redirect()->back()->with('error', 'Gagal memperbarui kuantitas.');
    }

    /**
     * Menghapus produk dari keranjang.
     */
    public function remove(Request $request)
    {
        if($request->id) {
            $cart = session()->get('cart');
            if(isset($cart[$request->id])) {
                unset($cart[$request->id]);
                session()->put('cart', $cart);
            }
            return redirect()->back()->with('success', 'Produk berhasil dihapus dari keranjang.');
        }
        return redirect()->back()->with('error', 'Gagal menghapus produk.');
    }
}

