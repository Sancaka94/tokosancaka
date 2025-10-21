<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// PERBAIKAN: Menggunakan model Marketplace dan mengalias-kannya sebagai Product.
use App\Models\Marketplace as Product;

class CartController extends Controller
{
    /**
     * Menampilkan halaman keranjang belanja.
     */
    public function index()
    {
        $cart = session()->get('cart', []);
        // Pastikan path view ini sesuai dengan struktur Anda, misalnya 'customer.cart.index'
        return view('cart.index', compact('cart'));
    }

    /**
     * Menambahkan produk ke keranjang menggunakan Route Model Binding.
     */
    public function add(Request $request, Product $product)
    {
        $quantity = $request->input('quantity', 1);
        $cart = session()->get('cart', []);
        $id = $product->id;

        // Validasi stok
        $newQuantity = ($cart[$id]['quantity'] ?? 0) + $quantity;
        
        // PERBAIKAN: Menambahkan pesan error yang lebih detail
        if ($product->stock < $newQuantity) {
            $errorMessage = "Stok produk tidak mencukupi. Stok tersedia: {$product->stock}, Anda mencoba menambahkan {$newQuantity}.";
            return back()->with('error', $errorMessage);
        }

        // Jika produk sudah ada, tambahkan kuantitasnya
        if (isset($cart[$id])) {
            $cart[$id]['quantity'] += $quantity;
        } else {
            // Jika belum ada, tambahkan item baru dari objek $product yang sudah benar
            $cart[$id] = [
                "name"      => $product->name,
                "quantity"  => (int)$quantity,
                "price"     => $product->price,
                "image_url" => $product->image_url,
            ];
        }

        session()->put('cart', $cart);

        // Arahkan ke halaman keranjang untuk melihat hasilnya
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
            $product = Product::find($id); // Mencari dari model Marketplace

            if (!$product) {
                 return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);
            }

            if ($product->stock < $quantity) {
                return response()->json(['success' => false, 'message' => 'Stok tidak mencukupi.'], 422);
            }

            if (isset($cart[$id])) {
                $cart[$id]['quantity'] = (int)$quantity;
                session()->put('cart', $cart);

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
            $cart = session()->get('cart', []);
            if (isset($cart[$id])) {
                unset($cart[$id]);
                session()->put('cart', $cart);

                return response()->json(['success' => true]);
            }
        }

        // Jika ID tidak ada di keranjang, kirim respons error
        return response()->json([
            'success' => false, 
            'message' => 'Produk tidak ditemukan di dalam sesi keranjang.',
            'requested_id' => $id,
            'cart_keys' => array_keys($cart ?? [])
        ], 404);
    }
}

