<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    /**
     * Menampilkan daftar semua kategori, dengan filter berdasarkan tipe.
     */
    public function index(Request $request)
    {
        $query = Category::withCount(['posts', 'products']); // Menghitung relasi ke post dan produk

        // Filter berdasarkan tipe jika ada parameter di URL
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $categories = $query->latest()->paginate(15)->withQueryString();
        
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
            'type' => 'required|string|in:marketplace,blog', // Tipe kategori yang diizinkan
            'icon' => 'nullable|string|max:255',
        ]);

        $data = [
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'type' => $request->type,
            'icon' => $request->icon,
        ];

        // Hanya tambahkan user_id jika tipenya adalah 'blog'
        if ($request->type === 'blog') {
            $data['user_id'] = Auth::id();
        }

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
            'type' => 'required|string|in:marketplace,blog',
            'icon' => 'nullable|string|max:255',
        ]);

        $category->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'type' => $request->type,
            'icon' => $request->icon,
        ]);

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    /**
     * Menghapus kategori dari database.
     */
    public function destroy(Category $category)
    {
        // Opsi: Cek jika kategori masih digunakan sebelum menghapus
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
        // Logika ini akan menampilkan kategori dengan tipe 'marketplace'
        // menggunakan view yang Anda tentukan.
        $categories = Category::where('type', 'marketplace')->latest()->paginate(15);
        
        // Pastikan file view ini ada
        return view('admin.categories.etalase.index', compact('categories'));
    }
}

