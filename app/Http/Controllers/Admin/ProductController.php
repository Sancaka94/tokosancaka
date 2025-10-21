<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category; // Impor model Category
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Menampilkan halaman daftar produk.
     */
    public function index(Request $request)
    {
        $query = Product::with('category'); 

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->latest()->paginate(10);
        return view('admin.products.index', compact('products'));
    }

    /**
     * Menampilkan form untuk membuat produk baru.
     */
    public function create()
    {
        // PERBAIKAN: Mengambil kategori yang tipenya 'marketplace' saja.
        $categories = Category::where('type', 'marketplace')->orderBy('name')->get();
        return view('admin.products.create', compact('categories'));
    }

    /**
     * Menyimpan produk baru ke database.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'product_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'weight' => 'required|integer|min:0',
            'sku' => 'required|string|max:255|unique:products,sku',
            'category_id' => 'required|exists:categories,id',
            'tags' => 'nullable|string',
            'store_name' => 'nullable|string|max:255',
            'seller_city' => 'nullable|string|max:255',
            'seller_wa' => 'nullable|string|max:20',
            'seller_logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'status' => 'required|in:active,inactive',
        ]);

        $data = $validatedData;
        $data['slug'] = Str::slug($request->name . '-' . Str::random(5));
        
        $data['is_new'] = $request->has('is_new');
        $data['is_bestseller'] = $request->has('is_bestseller');

        if ($request->hasFile('product_image')) {
            $path = $request->file('product_image')->store('products', 'public');
            $data['image_url'] = $path;
        }

        if ($request->hasFile('seller_logo')) {
            $path = $request->file('seller_logo')->store('seller_logos', 'public');
            $data['seller_logo'] = $path;
        }

        Product::create($data);

        return redirect()->route('admin.products.index')->with('success', 'Produk baru berhasil ditambahkan.');
    }

    /**
     * Menampilkan form untuk mengedit produk.
     */
    public function edit(Product $product)
    {
        // PERBAIKAN: Mengirim data kategori 'marketplace' ke halaman edit juga.
        $categories = Category::where('type', 'marketplace')->orderBy('name')->get();
        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Memperbarui data produk di database.
     */
    public function update(Request $request, Product $product)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'product_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'weight' => 'required|integer|min:0',
            'sku' => 'required|string|max:255|unique:products,sku,' . $product->id,
            'category_id' => 'required|exists:categories,id',
            'tags' => 'nullable|string',
            'store_name' => 'nullable|string|max:255',
            'seller_city' => 'nullable|string|max:255',
            'seller_wa' => 'nullable|string|max:20',
            'seller_logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'status' => 'required|in:active,inactive',
        ]);

        $data = $validatedData;
        
        if ($request->name !== $product->name) {
            $data['slug'] = Str::slug($request->name . '-' . Str::random(5));
        }
        
        $data['is_new'] = $request->has('is_new');
        $data['is_bestseller'] = $request->has('is_bestseller');

        if ($request->hasFile('product_image')) {
            if ($product->image_url) {
                Storage::disk('public')->delete($product->image_url);
            }
            $path = $request->file('product_image')->store('products', 'public');
            $data['image_url'] = $path;
        }

        if ($request->hasFile('seller_logo')) {
            if ($product->seller_logo) {
                Storage::disk('public')->delete($product->seller_logo);
            }
            $path = $request->file('seller_logo')->store('seller_logos', 'public');
            $data['seller_logo'] = $path;
        }

        $product->update($data);

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    /**
     * Menghapus produk dari database.
     */
    public function destroy(Product $product)
    {
        if ($product->image_url) {
            Storage::disk('public')->delete($product->image_url);
        }

        if ($product->seller_logo) {
            Storage::disk('public')->delete($product->seller_logo);
        }

        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil dihapus.');
    }
}

