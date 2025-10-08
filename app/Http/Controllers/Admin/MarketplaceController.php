<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace; // Menggunakan model Marketplace yang baru
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MarketplaceController extends Controller
{
    /**
     * Menampilkan halaman utama manajemen produk dengan fitur pencarian dan paginasi.
     */
    public function index(Request $request)
    {
        $query = Marketplace::query();

        // Logika untuk menangani pencarian
        if ($request->has('search') && $request->search != '') {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->latest()->paginate(10);
        
        // Jika permintaan adalah AJAX (dari pencarian/paginasi), kirim hanya data tabel
        if ($request->ajax()) {
            return view('admin.marketplace.partials.product_rows', compact('products'))->render();
        }

        // Jika permintaan biasa, muat halaman lengkap
        return view('admin.marketplace.index', compact('products'));
    }

    /**
     * Menyimpan data produk baru ke dalam database.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validasi untuk gambar
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['is_flash_sale'] = $request->has('is_flash_sale');

        // Proses unggah gambar jika ada
        if ($request->hasFile('image_url')) {
            // Simpan gambar di 'storage/app/public/products'
            // Pastikan Anda sudah menjalankan `php artisan storage:link`
            $path = $request->file('image_url')->store('public/products');
            $data['image_url'] = Storage::url($path); // Dapatkan URL yang bisa diakses publik
        }
        
        $product = Marketplace::create($data);
        return response()->json($product, 201);
    }

    /**
     * Mengambil data satu produk untuk ditampilkan di form edit.
     */
    public function show($id)
    {
        $product = Marketplace::findOrFail($id);
        return response()->json($product);
    }

    /**
     * Memperbarui data produk yang sudah ada di database.
     */
    public function update(Request $request, $id)
    {
        $product = Marketplace::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $data = $validator->validated();
        $data['is_flash_sale'] = $request->has('is_flash_sale');

        // Proses unggah gambar baru jika ada
        if ($request->hasFile('image_url')) {
            // Hapus gambar lama jika ada
            if ($product->image_url) {
                Storage::delete(str_replace('/storage', 'public', $product->image_url));
            }
            // Simpan gambar baru
            $path = $request->file('image_url')->store('public/products');
            $data['image_url'] = Storage::url($path);
        }

        $product->update($data);
        return response()->json($product);
    }

    /**
     * Menghapus data produk dari database.
     */
    public function destroy($id)
    {
        $product = Marketplace::findOrFail($id);
        
        // Hapus gambar terkait dari storage
        if ($product->image_url) {
            Storage::delete(str_replace('/storage', 'public', $product->image_url));
        }

        $product->delete();
        return response()->json(['success' => 'Produk berhasil dihapus.']);
    }
}

