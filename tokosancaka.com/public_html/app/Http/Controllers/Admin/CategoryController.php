<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::query();

        // Tambahkan filter di halaman admin
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Opsional filter berdasarkan kelompok produk/jasa
        if ($request->filled('category_group')) {
            $query->where('category_group', $request->category_group);
        }

        // TAMBAHAN: Opsional filter berdasarkan bendera (flag)
        if ($request->filled('flag')) {
            $query->where('flag', $request->flag);
        }

        $categories = $query->latest()->paginate(15);
        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Menampilkan form untuk membuat kategori baru.
     */
    public function create()
    {
        return view('admin.categories.create');
    }

    /**
     * Menyimpan kategori baru ke dalam database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'type' => 'required|string|in:marketplace,blog,product',
            'category_group' => 'required|string|in:produk_fisik,produk_digital,jasa',
            // TAMBAHAN: Validasi Flag
            'flag' => 'required|string|in:non_fisik,fisik,lokal',
            'icon' => 'nullable|string|max:255',
        ]);

        $data = [
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'type' => $request->type,
            'category_group' => $request->category_group,
            // TAMBAHAN: Simpan Flag
            'flag' => $request->flag,
            'icon' => $request->icon,
            'user_id' => Auth::id(),
        ];

        Category::create($data);

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    /**
     * Menampilkan form untuk mengedit kategori.
     */
    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    /**
     * Mengupdate data kategori di dalam database.
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'type' => 'required|string|in:marketplace,blog,product',
            'category_group' => 'required|string|in:produk_fisik,produk_digital,jasa',
            // TAMBAHAN: Validasi Flag
            'flag' => 'required|string|in:non_fisik,fisik,lokal',
            'icon' => 'nullable|string|max:255',
        ]);

        $category->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'type' => $request->type,
            'category_group' => $request->category_group,
            // TAMBAHAN: Update Flag
            'flag' => $request->flag,
            'icon' => $request->icon,
        ]);

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    /**
     * Menghapus kategori dari database.
     */
    public function destroy(Category $category)
    {
        if ($category->type === 'marketplace' && $category->products()->count() > 0) {
            return back()->with('error', 'Kategori tidak dapat dihapus karena masih digunakan oleh produk.');
        }
        if ($category->type === 'blog' && $category->posts()->count() > 0) {
            return back()->with('error', 'Kategori tidak dapat dihapus karena masih digunakan oleh post.');
        }

        $category->delete();
        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil dihapus.');
    }

    /**
     * Menampilkan halaman khusus untuk kategori etalase.
     */
    public function etalaseIndex()
    {
        $categories = Category::where('type', 'marketplace')->latest()->paginate(15);
        return view('admin.categories.etalase.index', compact('categories'));
    }

    /**
     * Menampilkan detail satu kategori.
     */
    public function show(Category $category)
    {
        return redirect()->route('admin.categories.edit', $category);
    }

    public function storeAjax(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'category_group' => 'nullable|string|in:produk_fisik,produk_digital,jasa',
            // TAMBAHAN: Validasi Flag untuk AJAX
            'flag' => 'nullable|string|in:non_fisik,fisik,lokal'
        ]);

        $category = Category::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'type' => 'product',
            'category_group' => $request->category_group ?? 'produk_fisik',
            // TAMBAHAN: Default fallback ke 'fisik' jika request dari AJAX kosong
            'flag' => $request->flag ?? 'fisik',
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil ditambahkan',
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
                'attributes_url' => route('admin.categories.attributes', $category->id)
            ]
        ]);
    }

    public function destroyAjax($id)
    {
        $category = Category::findOrFail($id);

        if ($category->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal! Kategori ini sedang digunakan oleh produk lain.'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dihapus'
        ]);
    }
}
