<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category; // Import Category
use App\Models\ProductVariant; // <--- 1. IMPORT MODEL VARIANT
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB; // <--- 2. IMPORT DB UNTUK TRANSACTION

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

        // Ambil data kategori aktif untuk dropdown modal tambah
        $categories = Category::where('is_active', true)->orderBy('name', 'asc')->get();

        return view('products.index', compact('products', 'categories'));
    }

    public function store(Request $request)
    {
        Log::info('----------------------------------------------------');
        Log::info('[STORE START] Memulai proses tambah produk.');

        // <--- 3. VALIDASI (TAMBAH VALIDASI VARIAN) --->
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255|unique:products,name',
            'category_id' => 'required|exists:categories,id',
            'base_price'  => 'required|numeric|min:0',
            // Jika tidak ada varian, harga jual wajib. Jika ada, harga jual diambil dari varian nanti.
            'sell_price'  => 'nullable|numeric|min:0',
            'unit'        => 'required|string',
            'supplier'    => 'nullable|string|max:255',
            'image'       => 'nullable|file|max:2048|mimes:jpeg,png,jpg,gif',
            // Validasi Varian
            'variants'    => 'array',
            'variants.*.name'  => 'required_with:variants|string',
            'variants.*.price' => 'required_with:variants|numeric|min:0',
            'variants.*.stock' => 'required_with:variants|integer|min:0',
        ], [
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists'   => 'Kategori tidak valid.',
            'image.max'            => 'Maksimal ukuran gambar 2MB.',
        ]);

        if ($validator->fails()) {
            Log::error('[STORE VALIDATION FAIL]', $validator->errors()->toArray());
            return back()->withErrors($validator)->withInput()->with('error', 'Gagal validasi data.');
        }

        // Mulai Transaksi Database
        DB::beginTransaction();

        try {
            $imagePath = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $imagePath = $file->storeAs('products', $filename, 'public');
            }

            // Cek apakah user mengaktifkan varian
            $hasVariant = $request->has('has_variant') ? 1 : 0;

            // Jika pakai varian, stok awal induk 0 dulu (nanti dijumlahkan)
            // Jika tidak pakai varian, pakai stok dari input biasa
            $initialStock = $hasVariant ? 0 : ($request->stock ?? 0);
            $sellPrice    = $request->sell_price ?? 0;

            // <--- 4. SIMPAN PRODUK INDUK --->
            $product = Product::create([
                'name'         => $request->name,
                'category_id'  => $request->category_id,
                'base_price'   => $request->base_price,
                'sell_price'   => $sellPrice,
                'unit'         => $request->unit,
                'stock'        => $initialStock,
                'sold'         => 0,
                'supplier'     => $request->supplier ?? '-',
                'stock_status' => 'available', // Default available dulu
                'image'        => $imagePath,
                'has_variant'  => $hasVariant, // Simpan status varian
                'type'         => 'physical'   // Default type
            ]);

            // <--- 5. SIMPAN VARIAN (JIKA ADA) --->
            if ($hasVariant && $request->filled('variants')) {
                $totalVariantStock = 0;

                foreach ($request->variants as $variant) {
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'name'       => $variant['name'],
                        'price'      => $variant['price'],
                        'stock'      => $variant['stock'] ?? 0,
                        'sku'        => $variant['sku'] ?? null,
                    ]);
                    $totalVariantStock += ($variant['stock'] ?? 0);
                }

                // Update stok induk berdasarkan total varian
                $product->update([
                    'stock' => $totalVariantStock,
                    'stock_status' => $totalVariantStock > 0 ? 'available' : 'out_of_stock'
                ]);

                Log::info("[STORE VARIAN] Menambahkan {$totalVariantStock} stok dari varian.");
            } else {
                // Update status stok non-varian
                $product->update([
                    'stock_status' => $initialStock > 0 ? 'available' : 'out_of_stock'
                ]);
            }

            DB::commit(); // Simpan permanen jika sukses
            Log::info('[STORE SUCCESS] Produk dibuat ID: ' . $product->id);

            return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan!');

        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan semua jika error
            Log::error('[STORE EXCEPTION] ' . $e->getMessage());
            return back()->withInput()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Product $product)
    {
        Log::info('[UPDATE START] Update produk ID: ' . $product->id);

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255', // Unique dihapus agar tidak error update diri sendiri
            'category_id' => 'required|exists:categories,id',
            'base_price'  => 'required|numeric|min:0',
            'sell_price'  => 'nullable|numeric|min:0',
            'unit'        => 'required|string',
            'supplier'    => 'nullable|string|max:255',
            'image'       => 'nullable|file|max:2048|mimes:jpeg,png,jpg,gif',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();

        try {
            $hasVariant = $request->has('has_variant') ? 1 : 0;

            $data = [
                'name'        => $request->name,
                'category_id' => $request->category_id,
                'base_price'  => $request->base_price,
                'sell_price'  => $request->sell_price ?? 0,
                'unit'        => $request->unit,
                'supplier'    => $request->supplier,
                'has_variant' => $hasVariant,
            ];

            // Jika tidak pakai varian, ambil stok dari input biasa
            if (!$hasVariant) {
                $data['stock'] = $request->stock ?? 0;
                $data['stock_status'] = ($data['stock'] > 0) ? 'available' : 'out_of_stock';
            }

            // Handle Gambar
            if ($request->hasFile('image')) {
                if ($product->image && Storage::disk('public')->exists($product->image)) {
                    Storage::disk('public')->delete($product->image);
                }
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $data['image'] = $file->storeAs('products', $filename, 'public');
            }

            $product->update($data);

            // <--- 6. LOGIKA UPDATE VARIAN (REPLACE STRATEGY) --->
            if ($hasVariant) {
                // Hapus varian lama agar bersih
                $product->variants()->delete();

                $totalVariantStock = 0;
                if ($request->filled('variants')) {
                    foreach ($request->variants as $variant) {
                        ProductVariant::create([
                            'product_id' => $product->id,
                            'name'       => $variant['name'],
                            'price'      => $variant['price'],
                            'stock'      => $variant['stock'] ?? 0,
                            'sku'        => $variant['sku'] ?? null,
                        ]);
                        $totalVariantStock += ($variant['stock'] ?? 0);
                    }
                }

                // Update stok induk
                $product->update([
                    'stock' => $totalVariantStock,
                    'stock_status' => $totalVariantStock > 0 ? 'available' : 'out_of_stock'
                ]);
            } else {
                // Jika user mematikan fitur varian, hapus sisa varian di DB
                $product->variants()->delete();
            }

            DB::commit();
            Log::info('[UPDATE SUCCESS] Data diperbarui.');

            return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[UPDATE ERROR] ' . $e->getMessage());
            return back()->with('error', 'Gagal update data: ' . $e->getMessage());
        }
    }

    public function destroy(Product $product)
    {
        try {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            // Varian otomatis terhapus karena ON DELETE CASCADE di database
            $product->delete();

            return redirect()->route('products.index')->with('success', 'Produk dihapus!');
        } catch (\Exception $e) {
            return redirect()->route('products.index')->with('error', 'Gagal hapus.');
        }
    }

    public function show(Product $product)
    {
        // Load variants agar tampil di detail
        $product->load('variants', 'category');
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        // Load variants agar tampil di form edit
        $product->load('variants');

        $categories = Category::where('is_active', true)
                              ->orderBy('name', 'asc')
                              ->get();

        return view('products.edit', compact('product', 'categories'));
    }

    // ==========================================================
    // TAMBAHAN: API UNTUK MODAL KELOLA VARIAN
    // ==========================================================

    public function getVariants(Product $product)
    {
        return response()->json([
            'product_name' => $product->name,
            'variants'     => $product->variants
        ]);
    }

    public function updateVariants(Request $request, Product $product)
    {
        $request->validate([
            'variants'        => 'array',
            'variants.*.name' => 'required|string',
            'variants.*.price'=> 'required|numeric',
            'variants.*.stock'=> 'required|numeric',
        ]);

        DB::beginTransaction();
        try {
            // Hapus varian lama
            $product->variants()->delete();

            $totalStock = 0;
            if ($request->filled('variants')) {
                foreach ($request->variants as $variant) {
                    $product->variants()->create([
                        'name'  => $variant['name'],
                        'price' => $variant['price'],
                        'stock' => $variant['stock'],
                        'sku'   => $variant['sku'] ?? null,
                    ]);
                    $totalStock += $variant['stock'];
                }
            }

            // Update stok induk & flag has_variant
            $product->update([
                'stock' => $totalStock,
                'has_variant' => count($request->variants) > 0 ? 1 : 0,
                'stock_status' => $totalStock > 0 ? 'available' : 'out_of_stock'
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Varian berhasil diperbarui!']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
