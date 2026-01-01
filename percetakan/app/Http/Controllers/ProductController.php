<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Tampilkan daftar produk di halaman Manajemen Produk.
     */
    public function index()
    {
        // Mengambil produk terbaru di posisi atas
        $products = Product::orderBy('id', 'desc')->get();
        return view('products.index', compact('products'));
    }

    /**
     * Simpan produk baru ke database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'base_price' => 'required|numeric|min:0',
            'unit' => 'required|string',
        ]);

        Product::create([
            'name' => $request->name,
            'base_price' => $request->base_price,
            'unit' => $request->unit,
            'stock_status' => 'available' // Default tersedia
        ]);

        return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan!');
    }

    /**
     * Tampilkan detail produk (Opsional, biasanya untuk cek spek).
     */
    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }

    /**
     * Tampilkan halaman form edit produk.
     */
    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    /**
     * Proses update data produk di database.
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'base_price' => 'required|numeric|min:0',
            'unit' => 'required|string',
            'stock_status' => 'required|in:available,unavailable'
        ]);

        $product->update([
            'name' => $request->name,
            'base_price' => $request->base_price,
            'unit' => $request->unit,
            'stock_status' => $request->stock_status,
        ]);

        return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui!');
    }

    /**
     * Hapus produk dari database.
     */
    public function destroy(Product $product)
    {
        try {
            $product->delete();
            return redirect()->route('products.index')->with('success', 'Produk berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->route('products.index')->with('error', 'Produk tidak bisa dihapus karena sudah memiliki riwayat transaksi.');
        }
    }
}