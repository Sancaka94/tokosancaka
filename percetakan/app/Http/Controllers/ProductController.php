<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category; // <--- 1. IMPORT MODEL CATEGORY
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Ambil Data Produk (dengan relasi category agar tidak n+1 query problem)
        $query = Product::with('category')->orderBy('created_at', 'desc');

        if ($request->has('search') && $request->search != '') {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('supplier', 'like', '%' . $request->search . '%');
        }

        $products = $query->paginate(10);

        // <--- 2. AMBIL DATA KATEGORI UNTUK DROPDOWN DI MODAL TAMBAH --->
        $categories = Category::where('is_active', true)->get();

        return view('products.index', compact('products', 'categories'));
    }

    public function store(Request $request)
    {
        Log::info('----------------------------------------------------');
        Log::info('[STORE START] Memulai proses tambah produk.');
        Log::info('[STORE INPUT] Data text:', $request->except(['image', '_token']));

        // Log File
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            Log::info('[STORE FILE] File ditemukan: ' . $file->getClientOriginalName());
        } else {
            Log::warning('[STORE FILE] Tidak ada file gambar.');
        }

        // <--- 3. VALIDASI DITAMBAH CATEGORY_ID --->
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255|unique:products,name',
            'category_id' => 'required|exists:categories,id', // Wajib ada di tabel categories
            'base_price'  => 'required|numeric|min:0',
            'sell_price'  => 'required|numeric|min:0|gte:base_price',
            'unit'        => 'required|string',
            'stock'       => 'required|integer|min:0',
            'supplier'    => 'nullable|string|max:255',
            'image'       => 'nullable|file|max:2048|mimes:jpeg,png,jpg,gif',
        ], [
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists'   => 'Kategori tidak valid.',
            'image.max'            => 'Maksimal ukuran gambar 2MB.',
        ]);

        if ($validator->fails()) {
            Log::error('[STORE VALIDATION FAIL]', $validator->errors()->toArray());
            return back()->withErrors($validator)->withInput()->with('error', 'Gagal validasi data.');
        }

        try {
            $imagePath = null;

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $imagePath = $file->storeAs('products', $filename, 'public');
            }

            // <--- 4. SIMPAN CATEGORY_ID KE DATABASE --->
            $product = Product::create([
                'name'         => $request->name,
                'category_id'  => $request->category_id, // Simpan ID Kategori
                'base_price'   => $request->base_price,
                'sell_price'   => $request->sell_price,
                'unit'         => $request->unit,
                'stock'        => $request->stock,
                'sold'         => 0,
                'supplier'     => $request->supplier ?? '-',
                'stock_status' => $request->stock > 0 ? 'available' : 'unavailable',
                'image'        => $imagePath
            ]);

            Log::info('[STORE SUCCESS] Produk dibuat ID: ' . $product->id);
            Log::info('----------------------------------------------------');

            return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan!');

        } catch (\Exception $e) {
            Log::error('[STORE EXCEPTION] ' . $e->getMessage());
            return back()->withInput()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Product $product)
    {
        Log::info('----------------------------------------------------');
        Log::info('[UPDATE START] Update produk ID: ' . $product->id);

        // <--- 5. VALIDASI UPDATE (CATEGORY JUGA) --->
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'base_price'  => 'required|numeric|min:0',
            'sell_price'  => 'required|numeric|min:0|gte:base_price',
            'unit'        => 'required|string',
            'stock'       => 'required|integer|min:0',
            'supplier'    => 'nullable|string|max:255',
            'image'       => 'nullable|file|max:2048|mimes:jpeg,png,jpg,gif',
        ]);

        if ($validator->fails()) {
            Log::error('[UPDATE VALIDATION FAIL]', $validator->errors()->toArray());
            return back()->withErrors($validator)->withInput();
        }

        try {
            $data = [
                'name'         => $request->name,
                'category_id'  => $request->category_id, // Update Kategori
                'base_price'   => $request->base_price,
                'sell_price'   => $request->sell_price,
                'unit'         => $request->unit,
                'stock'        => $request->stock,
                'supplier'     => $request->supplier,
                'stock_status' => $request->stock > 0 ? 'available' : 'unavailable'
            ];

            if ($request->hasFile('image')) {
                if ($product->image && Storage::disk('public')->exists($product->image)) {
                    Storage::disk('public')->delete($product->image);
                }

                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $data['image'] = $file->storeAs('products', $filename, 'public');
            }

            $product->update($data);
            Log::info('[UPDATE SUCCESS] Data diperbarui.');

            return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui!');

        } catch (\Exception $e) {
            Log::error('[UPDATE ERROR] ' . $e->getMessage());
            return back()->with('error', 'Gagal update data.');
        }
    }

    public function destroy(Product $product)
    {
        try {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            $product->delete();
            return redirect()->route('products.index')->with('success', 'Produk dihapus!');
        } catch (\Exception $e) {
            return redirect()->route('products.index')->with('error', 'Gagal hapus.');
        }
    }

    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        // PERBAIKAN:
        // 1. where('is_active', true) -> Agar kategori yang dimatikan tidak muncul
        // 2. orderBy('name', 'asc')   -> Agar urutan kategori rapi A-Z
        $categories = Category::where('is_active', true)
                              ->orderBy('name', 'asc')
                              ->get();

        return view('products.edit', compact('product', 'categories'));
    }
}
