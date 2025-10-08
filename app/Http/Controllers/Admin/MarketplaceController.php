<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MarketplaceController extends Controller
{
    /**
     * Menampilkan halaman manajemen produk.
     */
    public function index(Request $request)
    {
        $query = Marketplace::query();

        if ($request->has('search') && $request->search != '') {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->latest()->paginate(10);
        
        if ($request->ajax()) {
            return view('admin.marketplace.partials.product_rows', compact('products'))->render();
        }

        return view('admin.marketplace.index', compact('products'));
    }

    /**
     * Menyimpan produk baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0', // Ditambahkan
            'stock' => 'required|integer|min:0',
            'sold_count' => 'nullable|integer|min:0', // Ditambahkan
            'description' => 'nullable|string',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['is_flash_sale'] = $request->has('is_flash_sale');

        if ($request->hasFile('image_url')) {
            $path = $request->file('image_url')->store('public/products');
            $data['image_url'] = Storage::url($path);
        }
        
        $product = Marketplace::create($data);
        return response()->json($product, 201);
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
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0', // Ditambahkan
            'stock' => 'required|integer|min:0',
            'sold_count' => 'nullable|integer|min:0', // Ditambahkan
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
                Storage::delete(str_replace('/storage', 'public', $product->image_url));
            }
            $path = $request->file('image_url')->store('public/products');
            $data['image_url'] = Storage::url($path);
        }

        $product->update($data);
        return response()->json($product);
    }

    /**
     * Menghapus produk.
     */
    public function destroy($id)
    {
        $product = Marketplace::findOrFail($id);
        if ($product->image_url) {
            Storage::delete(str_replace('/storage', 'public', $product->image_url));
        }
        $product->delete();
        return response()->json(['success' => 'Produk berhasil dihapus.']);
    }
}

