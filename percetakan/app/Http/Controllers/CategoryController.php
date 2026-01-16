<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Menampilkan daftar kategori & form tambah
     */
    public function index()
    {
        $categories = Category::orderBy('created_at', 'desc')->paginate(10);
        return view('categories.index', compact('categories'));
    }

    /**
     * Menyimpan kategori baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        Category::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name), // Auto generate slug
            'description' => $request->description,
            'is_active' => true,
        ]);

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil ditambahkan!');
    }

    /**
     * Menghapus kategori
     */
    public function destroy(Category $category)
    {
        try {
            $category->delete();
            return redirect()->route('categories.index')->with('success', 'Kategori dihapus!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus kategori (Mungkin sedang digunakan oleh produk).');
        }
    }
}
