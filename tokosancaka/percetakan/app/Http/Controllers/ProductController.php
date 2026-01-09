<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator; // Penting untuk log error validasi

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::orderBy('created_at', 'desc');

        if ($request->has('search') && $request->search != '') {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('supplier', 'like', '%' . $request->search . '%');
        }

        $products = $query->paginate(10);
        return view('products.index', compact('products'));
    }

    public function store(Request $request)
    {
        // 1. LOG AWAL: Cek apa yang dikirim browser (Headers & Inputs)
        Log::info('----------------------------------------------------');
        Log::info('[STORE START] Memulai proses tambah produk.');
        Log::info('[STORE INPUT] Data text yang diterima:', $request->except(['image', '_token']));
        
        // 2. LOG FILE: Cek apakah file fisik benar-benar terbaca oleh server
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            Log::info('[STORE FILE] File ditemukan:', [
                'Original Name' => $file->getClientOriginalName(),
                'Mime Type (Asli)' => $file->getClientMimeType(),
                'Size (Bytes)' => $file->getSize(),
                'Error Code' => $file->getError(), // 0 artinya sukses
                'Real Path' => $file->getRealPath()
            ]);
        } else {
            Log::warning('[STORE FILE] Tidak ada file gambar yang terdeteksi di request. (Cek enctype form!)');
        }

        // 3. VALIDASI MANUAL (Agar bisa log error-nya)
        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:255|unique:products,name',
            'base_price' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0|gte:base_price',
            'unit'       => 'required|string',
            'stock'      => 'required|integer|min:0',
            'supplier'   => 'nullable|string|max:255',
            'image'      => 'nullable|file|max:2048',
        ], [
            'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'Format harus jpeg, png, jpg, gif.',
            'image.max'   => 'Maksimal ukuran 2MB.',
        ]);

        // 4. JIKA VALIDASI GAGAL -> CATAT DI LOG
        if ($validator->fails()) {
            Log::error('[STORE VALIDATION FAIL] Validasi gagal:', $validator->errors()->toArray());
            return back()->withErrors($validator)->withInput()->with('error', 'Gagal validasi data.');
        }

        try {
            $imagePath = null;

            // 5. PROSES UPLOAD
            if ($request->hasFile('image')) {
                Log::info('[STORE UPLOAD] Mencoba memindahkan file...');
                
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $imagePath = $file->storeAs('products', $filename, 'public');

                if ($imagePath) {
                    Log::info('[STORE UPLOAD SUCCESS] File tersimpan di: ' . $imagePath);
                } else {
                    Log::error('[STORE UPLOAD FAIL] Fungsi storeAs mengembalikan false.');
                }
            }

            // 6. SIMPAN DB
            Log::info('[STORE DB] Menyimpan data ke database...');
            
            $product = Product::create([
                'name'         => $request->name,
                'base_price'   => $request->base_price,
                'sell_price'   => $request->sell_price,
                'unit'         => $request->unit,
                'stock'        => $request->stock,
                'sold'         => 0,
                'supplier'     => $request->supplier ?? '-',
                'stock_status' => $request->stock > 0 ? 'available' : 'unavailable',
                'image'        => $imagePath
            ]);

            Log::info('[STORE SUCCESS] Produk berhasil dibuat ID: ' . $product->id);
            Log::info('----------------------------------------------------');

            return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan!');

        } catch (\Exception $e) {
            Log::error('[STORE EXCEPTION] Error sistem: ' . $e->getMessage());
            Log::error($e->getTraceAsString()); // Log jejak error lengkap
            return back()->withInput()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Product $product)
    {
        Log::info('----------------------------------------------------');
        Log::info('[UPDATE START] Update produk ID: ' . $product->id);

        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:255',
            'base_price' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0|gte:base_price',
            'unit'       => 'required|string',
            'stock'      => 'required|integer|min:0',
            'supplier'   => 'nullable|string|max:255',
            'image'      => 'nullable|file|max:2048',
        ]);

        if ($validator->fails()) {
            Log::error('[UPDATE VALIDATION FAIL]', $validator->errors()->toArray());
            return back()->withErrors($validator)->withInput();
        }

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

            if ($request->hasFile('image')) {
                Log::info('[UPDATE IMAGE] User mengupload gambar baru.');

                // Hapus lama
                if ($product->image && Storage::disk('public')->exists($product->image)) {
                    Storage::disk('public')->delete($product->image);
                    Log::info('[UPDATE IMAGE] Gambar lama dihapus: ' . $product->image);
                }
                
                // Upload baru
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $data['image'] = $file->storeAs('products', $filename, 'public');
                Log::info('[UPDATE IMAGE] Gambar baru disimpan: ' . $data['image']);
            }

            $product->update($data);
            Log::info('[UPDATE SUCCESS] Data DB diperbarui.');

            return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui!');

        } catch (\Exception $e) {
            Log::error('[UPDATE ERROR] ' . $e->getMessage());
            return back()->with('error', 'Gagal update data.');
        }
    }

    public function destroy(Product $product)
    {
        Log::warning('[DESTROY] Percobaan hapus ID: ' . $product->id);
        try {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
                Log::info('[DESTROY] File gambar dihapus.');
            }

            $product->delete();
            Log::info('[DESTROY] Sukses.');
            return redirect()->route('products.index')->with('success', 'Produk dihapus!');
        } catch (\Exception $e) {
            Log::error('[DESTROY ERROR] ' . $e->getMessage());
            return redirect()->route('products.index')->with('error', 'Gagal hapus.');
        }
    }

    // Method show & edit standar
    public function show(Product $product) { return view('products.show', compact('product')); }
    public function edit(Product $product) { return view('products.edit', compact('product')); }
}