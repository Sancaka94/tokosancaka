<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log; // Wajib import Log

class ProductController extends Controller
{
    /**
     * Menampilkan daftar produk
     */
    public function index(Request $request)
    {
        // LOG: Memulai akses halaman index
        Log::info('User mengakses halaman daftar produk.', ['ip' => $request->ip()]);

        $query = Product::orderBy('created_at', 'desc');

        if ($request->has('search') && $request->search != '') {
            // LOG: Mencatat pencarian
            Log::info('User melakukan pencarian produk.', ['keyword' => $request->search]);
            
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('supplier', 'like', '%' . $request->search . '%');
        }

        $products = $query->paginate(10);

        // LOG: Selesai mengambil data
        Log::info('Data produk berhasil dimuat.', ['total_displayed' => $products->count()]);

        return view('products.index', compact('products'));
    }

    /**
     * Menyimpan produk baru
     */
    public function store(Request $request)
    {
        // dd($request->all(), $request->hasFile('image'), $request->file('image'));
        // LOG: Percobaan simpan data baru
        Log::info('Memulai proses penyimpanan produk baru.');

        $request->validate([
            'name'       => 'required|string|max:255|unique:products,name', 
            'base_price' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0|gte:base_price',
            'unit'       => 'required|string',
            'stock'      => 'required|integer|min:0',
            'supplier'   => 'nullable|string|max:255',
            'image'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validasi gambar
        ]);

        try {
            $imagePath = null;

            // PROSES UPLOAD GAMBAR
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $imagePath = $file->storeAs('products', $filename, 'public');

                // LOG: Gambar berhasil diupload
                Log::info('Gambar produk berhasil diupload.', ['path' => $imagePath]);
            }

            // SIMPAN KE DATABASE
            $product = Product::create([
                'name'         => $request->name,
                'base_price'   => $request->base_price,
                'sell_price'   => $request->sell_price,
                'unit'         => $request->unit,
                'stock'        => $request->stock,
                'sold'         => 0,
                'supplier'     => $request->supplier ?? '-',
                'stock_status' => $request->stock > 0 ? 'available' : 'unavailable',
                'image'        => $imagePath // Simpan path gambar ke kolom database
            ]);

            // LOG: Sukses simpan database
            Log::info('Produk berhasil ditambahkan ke database.', ['product_id' => $product->id, 'name' => $product->name]);

            return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan!');

        } catch (\Exception $e) {
            // LOG: Jika terjadi error
            Log::error('Gagal menyimpan produk baru.', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Terjadi kesalahan sistem saat menyimpan data.');
        }
    }

    /**
     * Update produk yang sudah ada
     */
    public function update(Request $request, Product $product)
    {
        // LOG: Percobaan update
        Log::info('Memulai proses update produk.', ['product_id' => $product->id, 'old_name' => $product->name]);

        $request->validate([
            'name'       => 'required|string|max:255',
            'base_price' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0|gte:base_price',
            'unit'       => 'required|string',
            'stock'      => 'required|integer|min:0',
            'supplier'   => 'nullable|string|max:255',
            'image'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $data = [
                'name'         => $request->name,
                'base_price'   => $request->base_price,
                'sell_price'   => $request->sell_price,
                'unit'         => $request->unit,
                'stock'        => $request->stock,
                'supplier'     => $request->supplier,
                'stock_status' => $request->stock > 0 ? 'available' : 'unavailable'
            ];

            // LOGIKA GANTI GAMBAR
            if ($request->hasFile('image')) {
                // Hapus gambar lama jika ada
                if ($product->image && Storage::disk('public')->exists($product->image)) {
                    Storage::disk('public')->delete($product->image);
                    Log::info('Gambar lama dihapus.', ['old_path' => $product->image]);
                }
                
                // Upload gambar baru
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('products', $filename, 'public');
                
                $data['image'] = $path;
                Log::info('Gambar baru diupload untuk update.', ['new_path' => $path]);
            }

            // UPDATE DATABASE
            $product->update($data);

            Log::info('Produk berhasil diperbarui.', ['product_id' => $product->id]);

            return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui!');

        } catch (\Exception $e) {
            Log::error('Gagal mengupdate produk.', ['product_id' => $product->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Gagal update data.');
        }
    }

    public function show(Product $product) 
    { 
        Log::info('Melihat detail produk.', ['product_id' => $product->id]);
        return view('products.show', compact('product')); 
    }
    
    public function edit(Product $product) 
    { 
        Log::info('Membuka form edit produk.', ['product_id' => $product->id]);
        return view('products.edit', compact('product')); 
    }

    /**
     * Hapus produk
     */
    public function destroy(Product $product)
    {
        Log::warning('Percobaan menghapus produk.', ['product_id' => $product->id, 'name' => $product->name]);

        try {
            // HAPUS GAMBAR FISIK DULU
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
                Log::info('File gambar produk dihapus dari storage.', ['path' => $product->image]);
            }

            // HAPUS DATA DARI DB
            $product->delete();
            
            Log::info('Data produk berhasil dihapus permanen.', ['product_id' => $product->id]);

            return redirect()->route('products.index')->with('success', 'Produk dihapus!');
        } catch (\Exception $e) {
            Log::error('Gagal menghapus produk (mungkin relasi database).', ['product_id' => $product->id, 'error' => $e->getMessage()]);
            return redirect()->route('products.index')->with('error', 'Gagal hapus, produk sedang digunakan.');
        }
    }
}