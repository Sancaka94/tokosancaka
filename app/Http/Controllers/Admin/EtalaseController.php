<?php

namespace App\Http\Controllers;

use App\Models\Product; // Pastikan nama model Anda adalah 'Product'
use Illuminate\Http\Request;

class EtalaseController extends Controller
{
    /**
     * Menampilkan halaman etalase utama.
     */
    public function index(Request $request)
    {
        // --- Logika untuk Produk Utama (dengan filter & paginasi) ---
        $query = Product::where('status', 'active')->where('stock', '>', 0);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        // Anda bisa tambahkan filter lain di sini (kategori, harga, dll.)

        $products = $query->latest()->paginate(10)->withQueryString();

        // --- BARU: Logika untuk mengambil Produk Flash Sale ---
        // Mengambil 8 produk dengan diskon tertinggi yang aktif dan stoknya ada.
        $flashSaleProducts = Product::where('status', 'active')
            ->where('stock', '>', 0)
            ->whereNotNull('original_price')
            ->where('price', '<', \DB::raw('original_price')) // Memastikan ada diskon
            ->orderBy('discount_percentage', 'desc')
            ->limit(8)
            ->get();

        // Kirim kedua data ke view
        return view('etalase.index', compact('products', 'flashSaleProducts'));
    }

    /**
     * Menampilkan halaman detail untuk satu produk.
     */
    public function show(Product $product)
    {
        if ($product->status !== 'active') {
            abort(404);
        }
        
        $relatedProducts = Product::where('category', $product->category)
                                      ->where('id', '!=', $product->id)
                                      ->where('status', 'active')
                                      ->where('stock', '>', 0)
                                      ->inRandomOrder()
                                      ->limit(4)->get();
                                      
        return view('etalase.show', compact('product', 'relatedProducts'));
    }
}