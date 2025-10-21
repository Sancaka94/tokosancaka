<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Marketplace as Product; // Menggunakan model Marketplace sebagai Product

class CartController extends Controller
{
    /**
     * Menampilkan halaman keranjang belanja.
     */
    public function index()
    {
        $cart = session()->get('cart', []);
        return view('customer.cart.index', compact('cart')); // Pastikan path view sudah benar
    }

    /**
     * Menambahkan produk ke keranjang.
     */
    public function add(Request $request, Product $product)
    {
        $quantity = $request->input('quantity', 1);
        $cart = session()->get('cart', []);
        $id = $product->id;

        // Validasi stok
        $newQuantity = ($cart[$id]['quantity'] ?? 0) + $quantity;
        if ($product->stock < $newQuantity) {
            return back()->with('error', "Stok produk tidak mencukupi. Stok tersedia: {$product->stock}");
        }

        // Jika produk sudah ada, tambahkan kuantitasnya
        if (isset($cart[$id])) {
            $cart[$id]['quantity'] += $quantity;
        } else {
            // Jika belum ada, tambahkan item baru
            $cart[$id] = [
                "name" => $product->name,
                "quantity" => $quantity,
                "price" => $product->price,
                "image_url" => $product->image_url,
            ];
        }

        session()->put('cart', $cart);

        return redirect()->route('cart.index')->with('success', 'Produk berhasil ditambahkan ke keranjang!');
    }

    /**
     * Memperbarui kuantitas produk di keranjang (untuk AJAX).
     */
    public function update(Request $request)
    {
        $id = $request->input('id');
        $quantity = $request->input('quantity');
        
        if ($id && $quantity) {
            $cart = session()->get('cart');
            $product = Product::find($id);

            if (!$product) {
                 return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);
            }

            // Validasi stok saat update
            if ($product->stock < $quantity) {
                return response()->json(['success' => false, 'message' => 'Stok tidak mencukupi.'], 422);
            }

            if (isset($cart[$id])) {
                $cart[$id]['quantity'] = $quantity;
                session()->put('cart', $cart);

                // PERBAIKAN: Mengembalikan respons JSON yang dibutuhkan oleh JavaScript
                return response()->json([
                    'success' => true,
                    'subtotal' => $cart[$id]['price'] * $quantity
                ]);
            }
        }

        return response()->json(['success' => false, 'message' => 'Data tidak valid.'], 400);
    }

    /**
     * Menghapus produk dari keranjang (untuk AJAX).
     */
    public function remove(Request $request)
    {
        $id = $request->input('id');

        if ($id) {
            $cart = session()->get('cart');
            if (isset($cart[$id])) {
                unset($cart[$id]);
                session()->put('cart', $cart);

                // PERBAIKAN: Mengembalikan respons JSON
                return response()->json(['success' => true]);
            }
        }

        return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);
    }
}
