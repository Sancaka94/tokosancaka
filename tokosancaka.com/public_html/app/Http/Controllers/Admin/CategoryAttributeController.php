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
        // Pastikan query ini sinkron dengan tipe kategori yang kamu gunakan
        $categories = Category::whereIn('type', ['product', 'marketplace'])
            ->orderBy('category_group')
            ->orderBy('name')
            ->get();

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
     * Support AJAX (untuk Edit Produk) dan Form Submit Biasa.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id', // Memastikan ID kategori valid
            'name'        => 'required|string|max:255',
            'type'        => 'required|string|in:text,number,textarea,checkbox,select',
            'options'     => 'nullable|string|max:65535',
            'is_required' => 'nullable|boolean',
        ]);

        $category = Category::findOrFail($validated['category_id']);

        // Sanitasi: Hapus spasi berlebih dan hilangkan item kosong jika inputnya "Merah,, Biru"
        $options = null;
        if (!empty($validated['options'])) {
            $optionsArray = array_filter(array_map('trim', explode(',', $validated['options'])));
            $options = implode(',', $optionsArray);
        }

        $attribute = $category->attributes()->create([
            'name'        => $validated['name'],
            'slug'        => Str::slug($validated['name']),
            'type'        => $validated['type'],
            'options'     => $options,
            'is_required' => $request->boolean('is_required'),
        ]);

        // --- LOGIC HYBRID ---
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Atribut berhasil ditambahkan.',
                'data'    => $attribute
            ]);
        }

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
            'name'        => 'required|string|max:255',
            'type'        => 'required|string|in:text,number,textarea,checkbox,select',
            'options'     => 'nullable|string|max:65535',
            'is_required' => 'nullable|boolean',
        ]);

        // Sanitasi options saat update
        $options = null;
        if (!empty($validated['options'])) {
            $optionsArray = array_filter(array_map('trim', explode(',', $validated['options'])));
            $options = implode(',', $optionsArray);
        }

        $attribute->update([
            'name'        => $validated['name'],
            'slug'        => Str::slug($validated['name']),
            'type'        => $validated['type'],
            'options'     => $options,
            'is_required' => $request->boolean('is_required'),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Atribut berhasil diperbarui.',
                'data'    => $attribute
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
