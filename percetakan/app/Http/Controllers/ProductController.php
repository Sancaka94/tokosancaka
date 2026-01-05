<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; // <--- TAMBAHKAN INI

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::orderBy('created_at', 'desc');

        if ($request->has('search') && $request->search != '') {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('supplier', 'like', '%' . $request->search . '%');
        }

        $products = $query->paginate(10);
        return view('products.index', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255|unique:products,name',
            'base_price' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0|gte:base_price',
            'unit'       => 'required|string',
            'stock'      => 'required|integer|min:0',
            'supplier'   => 'nullable|string|max:255',
            // Validasi gambar (opsional, max 2MB)
            'image'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
        ], [
            'name.unique'    => 'Nama produk ini sudah ada di database.',
            'sell_price.gte' => 'Harga jual tidak boleh lebih rendah dari modal.',
            'image.image'    => 'File harus berupa gambar.',
            'image.max'      => 'Ukuran gambar maksimal 2MB.',
        ]);

        // LOGIKA UPLOAD GAMBAR
        $imagePath = null;
        if ($request->hasFile('image')) {
            // Simpan ke folder 'public/products'
            $imagePath = $request->file('image')->store('products', 'public');
        }

        Product::create([
            'name'       => $request->name,
            'base_price' => $request->base_price,
            'sell_price' => $request->sell_price,
            'unit'       => $request->unit,
            'stock'      => $request->stock,
            'sold'       => 0,
            'supplier'   => $request->supplier ?? '-',
            'stock_status' => $request->stock > 0 ? 'available' : 'unavailable',
            'image'      => $imagePath, // <--- SIMPAN PATH GAMBAR KE DATABASE
        ]);

        return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan!');
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'base_price' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0|gte:base_price',
            'unit'       => 'required|string',
            'stock'      => 'required|integer|min:0',
            'supplier'   => 'nullable|string|max:255',
            'image'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Data yang akan diupdate
        $data = [
            'name'       => $request->name,
            'base_price' => $request->base_price,
            'sell_price' => $request->sell_price,
            'unit'       => $request->unit,
            'stock'      => $request->stock,
            'supplier'   => $request->supplier,
            'stock_status' => $request->stock > 0 ? 'available' : 'unavailable'
        ];

        // LOGIKA UPDATE GAMBAR
        if ($request->hasFile('image')) {
            // 1. Hapus gambar lama jika ada
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            
            // 2. Upload gambar baru
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui!');
    }

    public function show(Product $product) { return view('products.show', compact('product')); }
    public function edit(Product $product) { return view('products.edit', compact('product')); }

    public function destroy(Product $product)
    {
        try {
            // HAPUS GAMBAR FISIK SEBELUM HAPUS DATA
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            $product->delete();
            return redirect()->route('products.index')->with('success', 'Produk dihapus!');
        } catch (\Exception $e) {
            return redirect()->route('products.index')->with('error', 'Gagal hapus, produk sedang digunakan.');
        }
    }
}