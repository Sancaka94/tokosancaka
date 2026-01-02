<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

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
        // Tambahkan 'unique:products' agar nama tidak boleh sama
        'name'       => 'required|string|max:255|unique:products,name', 
        'base_price' => 'required|numeric|min:0',
        'sell_price' => 'required|numeric|min:0|gte:base_price',
        'unit'       => 'required|string',
        'stock'      => 'required|integer|min:0',
        'supplier'   => 'nullable|string|max:255',
    ], [
        'name.unique'    => 'Nama produk ini sudah ada di database.',
        'sell_price.gte' => 'Harga jual tidak boleh lebih rendah dari modal.',
    ]);

        Product::create([
            'name'       => $request->name,
            'base_price' => $request->base_price,
            'sell_price' => $request->sell_price,
            'unit'       => $request->unit,
            'stock'      => $request->stock,
            'sold'       => 0, // Default terjual 0
            'supplier'   => $request->supplier ?? '-',
            'stock_status' => $request->stock > 0 ? 'available' : 'unavailable'
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
        ]);

        $product->update([
            'name'       => $request->name,
            'base_price' => $request->base_price,
            'sell_price' => $request->sell_price,
            'unit'       => $request->unit,
            'stock'      => $request->stock,
            'supplier'   => $request->supplier,
            'stock_status' => $request->stock > 0 ? 'available' : 'unavailable'
        ]);

        return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui!');
    }

    // Method show, edit, destroy sama seperti sebelumnya (tidak perlu diubah)
    public function show(Product $product) { return view('products.show', compact('product')); }
    public function edit(Product $product) { return view('products.edit', compact('product')); }
    public function destroy(Product $product) 
    {
        try {
            $product->delete();
            return redirect()->route('products.index')->with('success', 'Produk dihapus!');
        } catch (\Exception $e) {
            return redirect()->route('products.index')->with('error', 'Gagal hapus, produk sedang digunakan.');
        }
    }
}