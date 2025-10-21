<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use App\Models\Category; // Impor model Category
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str; // Impor Str facade untuk slug

class MarketplaceController extends Controller
{
    /**
     * Menampilkan halaman manajemen produk dengan filter dan pencarian.
     */
    public function index(Request $request)
    {
        // Memulai query dengan eager loading untuk efisiensi
        $query = Marketplace::query()->with('category');

        // Menerapkan filter pencarian
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Menerapkan filter kategori
        if ($request->filled('category_filter') && $request->category_filter != 'all') {
            $query->where('category_id', $request->category_filter);
        }

        // withQueryString() memastikan parameter filter tidak hilang saat paginasi
        $products = $query->latest()->paginate(10)->withQueryString();
        
        // Jika request datang dari AJAX (JavaScript), kirim hanya bagian tabelnya
        if ($request->ajax()) {
            return view('admin.marketplace.partials.product_rows', compact('products'))->render();
        }

        // Untuk request halaman penuh, kirim juga daftar kategori untuk dropdown filter
        $categories = Category::where('type', 'marketplace')->orderBy('name')->get();

        return view('admin.marketplace.index', compact('products', 'categories'));
    }

    /**
     * Menyimpan produk baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id', // Validasi untuk kategori
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['slug'] = Str::slug($data['name'] . '-' . Str::random(5)); // Membuat slug unik
        $data['is_flash_sale'] = $request->has('is_flash_sale');

        if ($request->hasFile('image_url')) {
            $path = $request->file('image_url')->store('products', 'public');
            $data['image_url'] = $path;
        }
        
        $product = Marketplace::create($data);
        return response()->json(['success' => true, 'message' => 'Produk berhasil ditambahkan.'], 201);
    }

    /**
     * Mengambil data satu produk untuk diedit.
     */
    public function show($id)
    {
        $product = Marketplace::findOrFail($id);
        return response()->json($product);
    }

    /**
     * Memperbarui produk yang ada.
     */
    public function update(Request $request, $id)
    {
        $product = Marketplace::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id', // Validasi untuk kategori
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $data = $validator->validated();
        $data['is_flash_sale'] = $request->has('is_flash_sale');

        if ($request->hasFile('image_url')) {
            if ($product->image_url) {
                Storage::disk('public')->delete($product->image_url);
            }
            $path = $request->file('image_url')->store('products', 'public');
            $data['image_url'] = $path;
        }

        $product->update($data);
        return response()->json(['success' => true, 'message' => 'Produk berhasil diperbarui.']);
    }

    /**
     * Menghapus produk.
     */
    public function destroy($id)
    {
        $product = Marketplace::findOrFail($id);
        if ($product->image_url) {
            Storage::disk('public')->delete($product->image_url);
        }
        $product->delete();
        return response()->json(['success' => true, 'message' => 'Produk berhasil dihapus.']);
    }
}

