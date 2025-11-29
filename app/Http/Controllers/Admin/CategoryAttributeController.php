<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryAttributeController extends Controller
{
    /**
     * Menampilkan daftar atribut (Halaman Management Atribut).
     */
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
     * Menyimpan atribut baru.
     * Support AJAX (untuk Edit Produk) dan Form Submit Biasa (untuk Halaman Atribut).
     */
    public function store(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:text,number,textarea,checkbox,select',
            'options' => 'nullable|string|max:65535', // Opsi untuk select/checkbox
            'is_required' => 'nullable|boolean',
        ]);

        // Simpan data
        $attribute = $category->attributes()->create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']), // Generate slug otomatis
            'type' => $validated['type'],
            'options' => $validated['options'] ?? null,
            // Gunakan helper boolean() agar aman untuk JSON (true/false) maupun Form (on/off)
            'is_required' => $request->boolean('is_required'),
        ]);

        // --- LOGIC HYBRID (PENTING) ---
        
        // 1. Jika Request dari AJAX (Popup di Edit Produk)
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Atribut berhasil ditambahkan.',
                'data' => $attribute // Kirim data balik agar bisa dirender JS
            ]);
        }

        // 2. Jika Request dari Form Biasa (Halaman Management Atribut)
        return redirect()->route('admin.category-attributes.index', ['category_id' => $category->id])
                         ->with('success', 'Atribut berhasil ditambahkan.');
    }

    /**
     * Menampilkan form edit.
     */
    public function edit(Attribute $attribute)
    {
        return view('admin.category-attributes.edit', compact('attribute'));
    }

    /**
     * Memperbarui atribut.
     */
    public function update(Request $request, Attribute $attribute)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:text,number,textarea,checkbox,select',
            'options' => 'nullable|string|max:65535',
            'is_required' => 'nullable|boolean',
        ]);

        $attribute->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'type' => $validated['type'],
            'options' => $validated['options'] ?? null,
            'is_required' => $request->boolean('is_required'),
        ]);

        // Support JSON response juga jika nanti dibutuhkan edit via popup
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Atribut berhasil diperbarui.',
                'data' => $attribute
            ]);
        }

        return redirect()->route('admin.category-attributes.index', ['category_id' => $attribute->category_id])
                         ->with('success', 'Atribut berhasil diperbarui.');
    }

    /**
     * Menghapus atribut.
     */
    public function destroy(Attribute $attribute)
    {
        $categoryId = $attribute->category_id;
        $attribute->delete();

        // Support JSON response untuk delete via AJAX
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Atribut berhasil dihapus.'
            ]);
        }

        return redirect()->route('admin.category-attributes.index', ['category_id' => $categoryId])
                         ->with('success', 'Atribut berhasil dihapus.');
    }
}