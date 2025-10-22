<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryAttributeController extends Controller
{
    public function index(Request $request)
    {
        // Ambil semua kategori produk untuk dropdown filter
        $categories = Category::where('type', 'product')->orderBy('name')->get();

        $selectedCategory = null;
        $attributes = collect(); // Default collection kosong

        // Jika ada kategori yang dipilih dari filter
        if ($request->has('category_id')) {
            $selectedCategory = Category::with('attributes')->find($request->category_id);
            if ($selectedCategory) {
                $attributes = $selectedCategory->attributes;
            }
        }

        return view('admin.category-attributes.index', compact('categories', 'selectedCategory', 'attributes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:text,number,checkbox,select',
            'options' => 'nullable|string', // Validasi options jika ada
        ]);

        Attribute::create([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => Str::slug($request->name, '_'),
            'type' => $request->type,
            // Ubah string "opsi1,opsi2" menjadi array JSON
            'options' => $request->type === 'checkbox' || $request->type === 'select'
                ? array_map('trim', explode(',', $request->options))
                : null,
            'is_required' => $request->has('is_required'),
        ]);

        return redirect()->route('admin.category-attributes.index', ['category_id' => $request->category_id])
                         ->with('success', 'Atribut baru berhasil ditambahkan.');
    }

    public function destroy(Attribute $attribute)
    {
        $categoryId = $attribute->category_id;
        $attribute->delete();
        return redirect()->route('admin.category-attributes.index', ['category_id' => $categoryId])
                         ->with('success', 'Atribut berhasil dihapus.');
    }
}
