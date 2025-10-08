<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MarketplaceController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();
        if ($request->has('search') && $request->search != '') {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        $products = $query->latest()->paginate(10);
        return view('admin.marketplace.index', compact('products'));

    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_flash_sale' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['slug'] = Str::slug($data['name']) . '-' . uniqid();
        $data['is_flash_sale'] = $request->has('is_flash_sale');

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $data['image_url'] = Storage::url($path);
        }

        $product = Product::create($data);
        return response()->json($product, 201);
    }

    public function show(Product $product)
    {
        return response()->json($product);
    }

    public function update(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_flash_sale' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['is_flash_sale'] = $request->has('is_flash_sale');
        
        if ($request->hasFile('image')) {
            // Hapus gambar lama jika ada
            if ($product->image_url) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $product->image_url));
            }
            $path = $request->file('image')->store('products', 'public');
            $data['image_url'] = Storage::url($path);
        }

        $product->update($data);
        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        if ($product->image_url) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $product->image_url));
        }
        $product->delete();
        return response()->json(['success' => 'Produk berhasil dihapus.']);
    }
}
