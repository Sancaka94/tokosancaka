<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; // Wajib untuk manipulasi string/slug

class ProductController extends Controller
{
    /**
     * Menampilkan daftar produk
     */
    public function index(Request $request)
    {
        // Eager load category untuk performa
        $query = Product::with('category')->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;

            // PENTING: Gunakan grouping (closure) agar logika OR tidak merusak query lain
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('supplier', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $search . '%')
                  // TAMBAHAN: Pencarian Barcode
                  ->orWhere('barcode', 'like', '%' . $search . '%')
                  // OPSI: Jika ingin scan barcode harus pas (exact match) agar akurat, aktifkan baris bawah ini:
                  ->orWhere('barcode', $search)
                  ;
            });
        }

        $products = $query->paginate(10);

        // Data kategori untuk dropdown di Modal Tambah/Edit
        $categories = Category::where('is_active', true)->orderBy('name', 'asc')->get();

        return view('products.index', compact('products', 'categories'));
    }

    /**
     * Helper: Generate SKU Otomatis
     * Format: 3 Huruf Nama - Angka Acak
     */
    private function generateSku($productName, $variantName = null)
    {
        // Ambil 3 huruf pertama dari nama produk (bersihkan simbol dulu)
        $cleanName = preg_replace('/[^A-Za-z0-9]/', '', $productName);
        $pName = strtoupper(substr($cleanName, 0, 3));

        if ($variantName) {
            // Jika Varian: KOP-MER-5829 (Kopi Merah)
            $cleanVar = preg_replace('/[^A-Za-z0-9]/', '', $variantName);
            $vName = strtoupper(substr($cleanVar, 0, 3));
            return $pName . '-' . $vName . '-' . mt_rand(1000, 9999);
        }

        // Jika Induk: KOP-84920
        return $pName . '-' . mt_rand(10000, 99999);
    }

    /**
     * Menyimpan produk baru
     */
    public function store(Request $request)
    {
        Log::info('[STORE START] Proses tambah produk...');

        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'base_price'  => 'required|numeric|min:0',
            'sell_price'  => 'nullable|numeric|min:0',
            'unit'        => 'required|string',
            'supplier'    => 'nullable|string|max:255',
            'image'       => 'nullable|file|max:2048|mimes:jpeg,png,jpg,gif',
            'barcode'     => 'nullable|unique:products,barcode|unique:product_variants,barcode',

            // Validasi Array Varian
            'variants'          => 'array',
            'variants.*.name'   => 'required_with:variants|string',
            'variants.*.price'  => 'required_with:variants|numeric|min:0',
            'variants.*.stock'  => 'required_with:variants|integer|min:0',
            'variants.*.barcode'=> 'nullable|string|distinct|unique:product_variants,barcode|unique:products,barcode',
        ]);

        // 2. LOGIKA AUTO-GENERATE BARCODE
        $barcodeToSave = $request->barcode;

        // Jika input barcode kosong, buatkan otomatis
        if (empty($barcodeToSave)) {
            // Format: PRD + Timestamp + 3 Angka Acak (Contoh: PRD1705638123999)
            $barcodeToSave = 'PRD' . time() . rand(100, 999);
        }

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('error', 'Validasi gagal, periksa inputan Anda.');
        }

        DB::beginTransaction(); // Mulai Transaksi Database

        try {
            // 2. Handle Upload Gambar
            $imagePath = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $imagePath = $file->storeAs('products', $filename, 'public');
            }

            // 3. Persiapan Data Induk
            $hasVariant = $request->has('has_variant') ? 1 : 0;
            // Jika pakai varian, stok induk 0 dulu (nanti dijumlahkan dari varian)
            $initialStock = $hasVariant ? 0 : ($request->stock ?? 0);

            // Generate SKU Induk jika user tidak isi
            $productSku = $this->generateSku($request->name);

            // 4. Simpan Produk Induk
            $product = Product::create([
                'name'         => $request->name,
                'sku'          => $productSku,
                'category_id'  => $request->category_id,
                'base_price'   => $request->base_price,
                'sell_price'   => $request->sell_price ?? 0,
                'unit'         => $request->unit,
                'stock'        => $initialStock,
                'stock_status' => $initialStock > 0 ? 'available' : 'out_of_stock',
                'sold'         => 0,
                'supplier'     => $request->supplier ?? '-',
                'image'        => $imagePath,
                'has_variant'  => $hasVariant,
                'type'         => 'physical', // Default
                'barcode'      => $barcodeToSave,
            ]);

            // 5. Simpan Varian (Looping)
            if ($hasVariant && $request->filled('variants')) {
                $totalVariantStock = 0;

                foreach ($request->variants as $variant) {
                    // Generate SKU Varian otomatis
                    $variantSku = $this->generateSku($request->name, $variant['name']);
                    $varBarcode = $variant['barcode'] ?? null;

                    ProductVariant::create([
                        'product_id' => $product->id,
                        'name'       => $variant['name'],
                        'price'      => $variant['price'],
                        'stock'      => $variant['stock'] ?? 0,
                        'sku'        => $variantSku,
                        'barcode'    => $varBarcode, // <--- PENTING: Simpan Barcode Varian
                    ]);
                    $totalVariantStock += ($variant['stock'] ?? 0);
                }

                // Update Stok Induk = Total Stok Varian
                $product->update([
                    'stock' => $totalVariantStock,
                    'stock_status' => $totalVariantStock > 0 ? 'available' : 'out_of_stock'
                ]);
            }

            DB::commit(); // Simpan Permanen
            return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan!');

        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan jika error
            Log::error('[STORE ERROR] ' . $e->getMessage());
            return back()->withInput()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    /**
     * Update data produk (Menggunakan Route Model Binding)
     */
    public function update(Request $request, Product $product) // <--- HANYA $product
    {
        Log::info('[UPDATE START] ID: ' . $product->id);

        // TIDAK PERLU baris ini lagi:
        // $product = Product::findOrFail($id);
        // Karena $product sudah otomatis dicari oleh Laravel

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'base_price'  => 'required|numeric|min:0',
            'sell_price'  => 'nullable|numeric|min:0',
            'unit'        => 'required|string',
            'supplier'    => 'nullable|string|max:255',
            'image'       => 'nullable|file|max:2048|mimes:jpeg,png,jpg,gif',
            // Validasi unik barcode, kecualikan ID produk ini sendiri
            'barcode'     => 'nullable|unique:products,barcode,' . $product->id . '|unique:product_variants,barcode',
        ]);

        // 2. LOGIKA AUTO-GENERATE
        $barcodeToSave = $request->barcode;

        if (empty($barcodeToSave)) {
            $barcodeToSave = 'PRD' . time() . rand(100, 999);
        }

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();

        try {
            $hasVariant = $request->has('has_variant') ? 1 : 0;

            // Generate SKU Induk jika belum ada
            $currentSku = $product->sku;
            if (empty($currentSku)) {
                $currentSku = $this->generateSku($request->name);
            }

            $data = [
                'name'        => $request->name,
                'sku'         => $currentSku,
                'category_id' => $request->category_id,
                'base_price'  => $request->base_price,
                'sell_price'  => $request->sell_price ?? 0,
                'unit'        => $request->unit,
                'supplier'    => $request->supplier,
                'has_variant' => $hasVariant,
                'barcode'     => $barcodeToSave,
            ];

            // Jika mode Single Product (Non-Varian)
            if (!$hasVariant) {
                $data['stock'] = $request->stock ?? 0;
                $data['stock_status'] = ($data['stock'] > 0) ? 'available' : 'out_of_stock';
            }

            // Handle Ganti Gambar
            if ($request->hasFile('image')) {
                if ($product->image && Storage::disk('public')->exists($product->image)) {
                    Storage::disk('public')->delete($product->image);
                }
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $data['image'] = $file->storeAs('products', $filename, 'public');
            }

            $product->update($data);

            // 6. Logika Update Varian
            if ($hasVariant) {
                // Hapus semua varian lama
                $product->variants()->delete();

                $totalVariantStock = 0;
                if ($request->filled('variants')) {
                    foreach ($request->variants as $variant) {

                        $variantSku = $variant['sku'] ?? $this->generateSku($request->name, $variant['name']);

                        // [FIX] Ambil Barcode Varian
                        $varBarcode = $variant['barcode'] ?? null;

                        ProductVariant::create([
                            'product_id' => $product->id,
                            'name'       => $variant['name'],
                            'price'      => $variant['price'],
                            'stock'      => $variant['stock'] ?? 0,
                            'sku'        => $variantSku,
                            'barcode'    => $varBarcode,
                        ]);
                        $totalVariantStock += ($variant['stock'] ?? 0);
                    }
                }

                $product->update([
                    'stock' => $totalVariantStock,
                    'stock_status' => $totalVariantStock > 0 ? 'available' : 'out_of_stock'
                ]);
            } else {
                $product->variants()->delete();
            }

            DB::commit();
            return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[UPDATE ERROR] ' . $e->getMessage());
            return back()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }

    /**
     * Hapus Produk
     */
    public function destroy(Product $product)
    {
        try {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            // Cascade delete di database akan otomatis menghapus variants
            $product->delete();

            return redirect()->route('products.index')->with('success', 'Produk dihapus!');
        } catch (\Exception $e) {
            return redirect()->route('products.index')->with('error', 'Gagal hapus.');
        }
    }

    /**
     * Halaman Edit Standard
     */
    public function edit(Product $product)
    {
        $product->load('variants'); // Load data varian
        $categories = Category::where('is_active', true)->orderBy('name', 'asc')->get();
        return view('products.edit', compact('product', 'categories'));
    }

    public function show(Product $product)
    {
        $product->load(['variants', 'category']);
        return view('products.show', compact('product'));
    }

    // ================================================================
    // API UNTUK MODAL VARIAN (AJAX)
    // ================================================================

    /**
     * Ambil data varian untuk ditampilkan di Modal
     */
    public function getVariants(Product $product)
    {
        return response()->json([
            'product_name' => $product->name,
            'variants'     => $product->variants
        ]);
    }

    /**
     * Simpan perubahan varian dari Modal
     */
    public function updateVariants(Request $request, Product $product)
    {
        $request->validate([
            'variants' => 'array',
            'variants.*.name' => 'required|string',
            'variants.*.price'=> 'required|numeric',
            'variants.*.stock'=> 'required|numeric',
        ]);

        DB::beginTransaction();
        try {
            // Reset Varian
            $product->variants()->delete();

            $totalStock = 0;
            if ($request->filled('variants')) {
                foreach ($request->variants as $variant) {

                    // Generate SKU Varian di Modal juga
                    $variantSku = $variant['sku'] ?? null;
                    if (empty($variantSku)) {
                        $variantSku = $this->generateSku($product->name, $variant['name']);
                    }

                    $varBarcode = $variant['barcode'] ?? null;

                    $product->variants()->create([
                        'name'  => $variant['name'],
                        'price' => $variant['price'],
                        'stock' => $variant['stock'],
                        'sku'   => $variantSku,
                        'barcode' => $varBarcode, // <--- TAMBAHKAN INI
                    ]);
                    $totalStock += $variant['stock'];
                }
            }

            // Update Induk
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

    // Halaman create standar
    public function create() {
        $categories = Category::where('is_active', true)->get();
        return view('products.create', compact('categories'));
    }
}
