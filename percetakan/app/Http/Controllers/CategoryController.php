<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage; // Wajib import untuk hapus gambar
use Illuminate\Support\Facades\Log;     // Untuk logging error

class CategoryController extends Controller
{
    /**
     * Menampilkan daftar kategori
     */
    public function index()
    {
        // Urutkan terbaru dengan pagination
        $categories = Category::latest()->paginate(10);
        return view('categories.index', compact('categories'));
    }

    /**
     * Menampilkan form tambah kategori
     */
    public function create()
    {
        return view('categories.create');
    }

    /**
     * Menyimpan kategori baru ke database
     */
    public function store(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'name'            => 'required|string|max:255',
            'type'            => 'required|in:physical,service', // Validasi enum
            'default_unit'    => 'required|string|max:10',
            'description'     => 'nullable|string',
            'image'           => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Validasi Gambar
            'product_presets_input' => 'nullable|string', // Input dari Textarea
        ]);

        try {
            $data = $request->all();

            // 2. Generate Slug Otomatis
            $data['slug'] = Str::slug($request->name);

            // 3. Handle Upload Gambar
            if ($request->hasFile('image')) {
                // Simpan ke folder 'public/categories'
                $data['image'] = $request->file('image')->store('categories', 'public');
            }

            // 4. Handle Product Presets (Textarea -> JSON Array)
            // Memecah baris baru (\n) menjadi array
            if ($request->filled('product_presets_input')) {
                $presets = explode("\n", str_replace("\r", "", $request->product_presets_input));
                // Hapus baris kosong & trim spasi kiri/kanan
                $data['product_presets'] = array_values(array_filter(array_map('trim', $presets)));
            } else {
                $data['product_presets'] = null;
            }

            // 5. Handle Checkbox Active
            $data['is_active'] = $request->has('is_active') ? 1 : 0;

            // 6. Simpan Data
            Category::create($data);

            return redirect()->route('categories.index')->with('success', 'Kategori berhasil ditambahkan!');

        } catch (\Exception $e) {
            Log::error("Gagal store kategori: " . $e->getMessage());
            return back()->withInput()->with('error', 'Terjadi kesalahan sistem.');
        }
    }

    /**
     * Menampilkan form edit kategori
     */
    public function edit($id)
    {
        $category = Category::findOrFail($id);

        // Ubah JSON Presets kembali menjadi String (per baris) untuk ditampilkan di Textarea edit
        $presetString = '';
        if (!empty($category->product_presets) && is_array($category->product_presets)) {
            $presetString = implode("\n", $category->product_presets);
        }

        return view('categories.edit', compact('category', 'presetString'));
    }

    /**
     * Memperbarui data kategori
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        // 1. Validasi
        $request->validate([
            'name'            => 'required|string|max:255',
            'type'            => 'required|in:physical,service',
            'default_unit'    => 'required|string|max:10',
            'description'     => 'nullable|string',
            'image'           => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            $data = $request->all();

            // 2. Update Slug (Opsional: jika nama berubah)
            $data['slug'] = Str::slug($request->name);

            // 3. Handle Gambar Baru
            if ($request->hasFile('image')) {
                // Hapus gambar lama jika ada di storage
                if ($category->image && Storage::disk('public')->exists($category->image)) {
                    Storage::disk('public')->delete($category->image);
                }
                // Upload gambar baru
                $data['image'] = $request->file('image')->store('categories', 'public');
            }

            // 4. Handle Product Presets
            if ($request->filled('product_presets_input')) {
                $presets = explode("\n", str_replace("\r", "", $request->product_presets_input));
                $data['product_presets'] = array_values(array_filter(array_map('trim', $presets)));
            } else {
                $data['product_presets'] = null; // Kosongkan jika dihapus semua
            }

            // 5. Handle Checkbox Active
            $data['is_active'] = $request->has('is_active') ? 1 : 0;

            // 6. Update Database
            $category->update($data);

            return redirect()->route('categories.index')->with('success', 'Kategori berhasil diperbarui!');

        } catch (\Exception $e) {
            Log::error("Gagal update kategori: " . $e->getMessage());
            return back()->withInput()->with('error', 'Terjadi kesalahan saat update.');
        }
    }

    /**
     * Menghapus kategori
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        try {
            // 1. Hapus File Gambar Fisik (Cleanup)
            if ($category->image && Storage::disk('public')->exists($category->image)) {
                Storage::disk('public')->delete($category->image);
            }

            // 2. Hapus Data Database
            $category->delete();

            return redirect()->route('categories.index')->with('success', 'Kategori berhasil dihapus.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }
}
