<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Attribute;
use Illuminate\Http\Request;

class CategoryAttributeController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::where('type', 'product')->orderBy('name')->get();
        $selectedCategory = null;
        $attributes = collect();

        if ($request->has('category_id') && $request->category_id != '') {
            $selectedCategory = Category::with('attributes')->find($request->category_id);
            if ($selectedCategory) {
                $attributes = $selectedCategory->attributes;
            }
        }

        return view('admin.category-attributes.index', compact('categories', 'selectedCategory', 'attributes'));
    }

    /**
     * PERBAIKAN:
     * 1. Method signature diubah untuk menerima Model Category langsung dari URL (Route Model Binding).
     * 2. Validasi untuk 'category_id' dihapus karena tidak lagi diperlukan.
     */
    public function store(Request $request, Category $category)
    {
        $request->validate([
            // 'category_id' => 'required|exists:categories,id', // <-- Dihapus
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:text,number,textarea,checkbox,select',
            'options' => 'nullable|string|max:65535', // Menggunakan max untuk TEXT
            'is_required' => 'nullable|boolean',
        ]);

        // Langsung menggunakan variabel $category yang didapat dari URL
        $category->attributes()->create([
            'name' => $request->name,
            'type' => $request->type,
            'options' => $request->options,
            'is_required' => $request->has('is_required'),
        ]);

        return redirect()->route('admin.category-attributes.index', ['category_id' => $category->id])
                         ->with('success', 'Atribut berhasil ditambahkan.');
    }

    public function edit(Attribute $attribute)
    {
        return view('admin.category-attributes.edit', compact('attribute'));
    }

    public function update(Request $request, Attribute $attribute)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:text,number,textarea,checkbox,select',
            'options' => 'nullable|string|max:65535', // Menggunakan max untuk TEXT
            'is_required' => 'nullable|boolean',
        ]);

        $attribute->update([
            'name' => $request->name,
            'type' => $request->type,
            'options' => $request->options,
            'is_required' => $request->has('is_required'),
        ]);

        return redirect()->route('admin.category-attributes.index', ['category_id' => $attribute->category_id])
                         ->with('success', 'Atribut berhasil diperbarui.');
    }

    public function destroy(Attribute $attribute)
    {
        $categoryId = $attribute->category_id;
        $attribute->delete();

        return redirect()->route('admin.category-attributes.index', ['category_id' => $categoryId])
                         ->with('success', 'Atribut berhasil dihapus.');
    }
}

