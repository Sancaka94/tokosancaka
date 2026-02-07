<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Models\Tenant; // <--- WAJIB TAMBAHKAN INI
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule; // <--- TARUH DISINI BOS!
use Illuminate\Support\Str; // Wajib untuk manipulasi string/slug
use Barryvdh\DomPDF\Facade\Pdf; // Import di atas

class ProductController extends Controller
{
    protected $tenantId;

    public function __construct(Request $request)
    {
        // Deteksi Subdomain
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];
        $tenant = Tenant::where('subdomain', $subdomain)->first();

        // Kunci ID Tenant secara global untuk controller ini
        $this->tenantId = $tenant ? $tenant->id : 1;
    }

    public function index(Request $request)
    {
        // 1. Ambil Data Kategori - Filter Tenant
        $categories = Category::where('tenant_id', $this->tenantId)->orderBy('name', 'asc')->get();

        // 2. Query Produk - Filter Tenant
        $query = Product::where('tenant_id', $this->tenantId) // <--- KUNCI DISINI
                ->with(['category', 'variants'])
                ->orderBy('name', 'asc');

        // --- FILTER PENCARIAN ---
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // --- FILTER KATEGORI ---
        if ($request->filled('category_id') && $request->category_id != 'all') {
            $query->where('category_id', $request->category_id);
        }

        // 3. Eksekusi
        $products = $query->paginate(10)->withQueryString();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json($query->get());
        }

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

    public function store(Request $request)
    {
        // 1. Bersihkan Tenant ID (Wajib Integer)
        $fixTenantId = (int) (is_array($this->tenantId) ? $this->tenantId[0] : $this->tenantId);

        // 2. Validasi Input (Termasuk Gambar)
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'type'        => 'nullable|in:physical,service', // Default physical

            // Validasi Gambar (Max 2MB, Format JPG/PNG)
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

            // Validasi Barcode (Unik per Tenant)
            'barcode'     => [
                'nullable',
                'min:10',
                'max:13',
                Rule::unique('products')->where('tenant_id', $fixTenantId),
                Rule::unique('product_variants')->where('tenant_id', $fixTenantId)
            ],
            // Validasi Varian
            'variants'    => 'array',
            'variants.*.barcode' => [
                'nullable',
                'min:10',
                'max:13',
                'distinct',
                Rule::unique('products', 'barcode')->where('tenant_id', $fixTenantId),
                Rule::unique('product_variants', 'barcode')->where('tenant_id', $fixTenantId)
            ]
        ]);

        if ($validator->fails()) return back()->withErrors($validator)->withInput();

        DB::beginTransaction();
        try {
            // 3. PROSES UPLOAD GAMBAR
            $imagePath = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                // Nama file unik: waktu_namaasli.jpg
                $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                // Simpan ke folder: storage/app/public/products
                $imagePath = $file->storeAs('products', $filename, 'public');
            }

            // 4. LOGIKA AUTO BARCODE INDUK
            $barcodeToSave = $request->barcode;
            $productType = $request->type ?? 'physical'; // Default ke Barang jika null

            if (empty($barcodeToSave)) {
                // Generate 100... atau 200...
                $barcodeToSave = $this->generateSmartBarcode($productType);
            }

            $hasVariant = $request->has('has_variant') ? 1 : 0;
            $productSku = $this->generateSku($request->name);

            // 5. SIMPAN KE DATABASE
            $product = Product::create([
                'tenant_id'    => $fixTenantId,
                'name'         => $request->name,
                'type'         => $productType,
                'sku'          => $productSku,
                'category_id'  => $request->category_id,
                'base_price'   => $request->base_price,
                'sell_price'   => $request->sell_price ?? 0,
                'stock'        => $hasVariant ? 0 : ($request->stock ?? 0),
                'stock_status' => 'available',
                'has_variant'  => $hasVariant,
                'barcode'      => $barcodeToSave,
                'image'        => $imagePath, // <--- MASUKKAN PATH GAMBAR DISINI
                'unit'         => $request->unit ?? 'pcs',
                'supplier'     => $request->supplier,
            ]);

            // 6. SIMPAN VARIAN (Jika Ada)
            if ($hasVariant && $request->filled('variants')) {
                $totalStock = 0;
                foreach ($request->variants as $variant) {

                    // Logic Auto Barcode Varian (Wariskan Tipe Induk)
                    $varBarcode = $variant['barcode'] ?? null;
                    if (empty($varBarcode)) {
                        $varBarcode = $this->generateSmartBarcode($productType);
                    }

                    ProductVariant::create([
                        'tenant_id'  => $fixTenantId,
                        'product_id' => $product->id,
                        'name'       => $variant['name'],
                        'price'      => $variant['price'],
                        'stock'      => $variant['stock'],
                        'sku'        => $variant['sku'] ?? $this->generateSku($request->name, $variant['name']),
                        'barcode'    => $varBarcode
                    ]);
                    $totalStock += $variant['stock'];
                }

                $product->update(['stock' => $totalStock]);
            }

            DB::commit();
            return redirect()->route('products.index')->with('success', 'Produk Berhasil Disimpan');

        } catch (\Exception $e) {
            DB::rollBack();
            // Hapus gambar jika database gagal simpan (biar server gak penuh sampah)
            if (isset($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, Product $product)
    {
        // 1. Validasi (Sudah Benar)
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'base_price'  => 'required|numeric|min:0',
            'sell_price'  => 'nullable|numeric|min:0',
            'unit'        => 'required|string',
            'barcode'     => 'nullable|unique:products,barcode,' . $product->id . '|unique:product_variants,barcode',
        ]);

        // Logic Barcode (Sudah Benar)
        $barcodeToSave = $request->barcode;
        if (empty($barcodeToSave)) {
            $catId = $request->category_id ?? $product->category_id;
            $barcodeToSave = $this->generateNumericBarcode($catId);
        }

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();

        try {
            $hasVariant = $request->has('has_variant') ? 1 : 0;
            $currentSku = $product->sku;
            if (empty($currentSku)) {
                $currentSku = $this->generateSku($request->name);
            }

            // Update Induk
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

            if (!$hasVariant) {
                $data['stock'] = $request->stock ?? 0;
                $data['stock_status'] = ($data['stock'] > 0) ? 'available' : 'out_of_stock';
            }

            if ($request->hasFile('image')) {
                if ($product->image && Storage::disk('public')->exists($product->image)) {
                    Storage::disk('public')->delete($product->image);
                }
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $data['image'] = $file->storeAs('products', $filename, 'public');
            }

            $product->update($data);

            // LOGIKA UPDATE VARIAN
            if ($hasVariant) {
                $product->variants()->delete(); // Hapus lama
                $totalVariantStock = 0;

                // Siapkan Tenant ID yang aman (Angka)
                $fixTenantId = (int) (is_array($this->tenantId) ? $this->tenantId[0] : $this->tenantId);

                if ($request->filled('variants')) {
                    foreach ($request->variants as $variant) {
                        $variantSku = $variant['sku'] ?? $this->generateSku($request->name, $variant['name']);

                        // Cek Barcode Varian
                        $varBarcode = $variant['barcode'] ?? null;
                        if (empty($varBarcode)) {
                            $varBarcode = $this->generateNumericBarcode($request->category_id);
                        }

                        ProductVariant::create([
                            'tenant_id'  => $fixTenantId, // <--- DULU LUPA DISINI (Sekarang sudah ada)
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
        if ($product->tenant_id != $this->tenantId) {
            abort(403, 'Anda tidak memiliki akses ke produk ini.');
        }

        $product->load('variants'); // Load data varian
        // PROTEKSI: Cek apakah produk ini milik tenant yang sedang buka subdomain ini

        $categories = Category::where('is_active', true)->orderBy('name', 'asc')->get();
        return view('products.edit', compact('product', 'categories'));
    }

    public function show(Product $product)
    {
        // Jika request dari Electron, return JSON
        if (request()->wantsJson() || request()->is('api/*')) {
            $product->load(['variants', 'category']);
            return response()->json($product);
        }

        $product->load(['variants', 'category']);
        return view('products.show', compact('product'));
    }

    // ================================================================
    // API UNTUK MODAL VARIAN (AJAX)
    // ================================================================

    public function getVariants($id)
{
    // Cek apakah yang akses adalah subdomain 'admin' atau 'pusat'
    $host = request()->getHost();
    $isCentral = str_contains($host, 'admin.') || str_contains($host, 'apps.');

    if ($isCentral) {
        // JIKA ADMIN: Cari produk TANPA peduli tenant_id (Global Scope dimatikan)
        // Pastikan Anda import namespace: use App\Models\Product;
        $product = Product::withoutGlobalScopes()->findOrFail($id);
    } else {
        // JIKA TENANT: Cari produk standar (Wajib milik tenant ybs)
        $product = Product::findOrFail($id);
    }

    return response()->json([
        'product_name' => $product->name,
        'variants'     => $product->variants // Relation variants juga harus tanpa scope jika perlu
    ]);
}

    public function updateVariants(Request $request, $id)
    {
        // 1. CARI PRODUK MANUAL (FIX ERROR STRING GIVEN)
        $product = Product::findOrFail($id);

        // Validasi input
        $request->validate([
            'variants' => 'array',
            'variants.*.name' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            // Ambil Tenant ID yang bersih
            $fixTenantId = (int) (is_array($this->tenantId) ? $this->tenantId[0] : $this->tenantId);

            // Cek Jenis Induknya
            $parentType = $product->type;
            if (empty($parentType) && !empty($product->barcode)) {
                $prefixInduk = substr($product->barcode, 0, 3);
                $parentType = ($prefixInduk === '200') ? 'service' : 'physical';
            }

            // Hapus varian lama
            $product->variants()->delete();

            $totalStock = 0;

            if ($request->filled('variants')) {
                foreach ($request->variants as $variant) {

                    // --- LOGIKA BARCODE VARIAN ---
                    $varBarcode = $variant['barcode'] ?? null;

                    if (empty($varBarcode)) {
                        $varBarcode = $this->generateSmartBarcode($parentType);
                    }

                    // Generate SKU jika kosong
                    $variantSku = $variant['sku'] ?? null;
                    if (empty($variantSku)) {
                        $variantSku = $this->generateSku($product->name, $variant['name']);
                    }

                    $product->variants()->create([
                        'tenant_id'  => $fixTenantId,
                        'product_id' => $product->id,
                        'name'       => $variant['name'],
                        'price'      => $variant['price'],
                        'stock'      => $variant['stock'],
                        'sku'        => $variantSku,
                        'barcode'    => $varBarcode,
                    ]);

                    $totalStock += $variant['stock'];
                }
            }

            // Update Stok Induk
            $product->update([
                'stock' => $totalStock,
                'has_variant' => count($request->variants) > 0 ? 1 : 0,
                'stock_status' => $totalStock > 0 ? 'available' : 'out_of_stock'
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Varian berhasil diperbarui!']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }

    // Halaman create standar
    public function create() {
        $categories = Category::where('is_active', true)->get();
        return view('products.create', compact('categories'));
    }

    public function downloadPdf(Request $request)
    {
        // 1. Mulai Query dengan Eager Loading (Relasi 'variants' WAJIB dibawa)
        $query = Product::with(['category', 'variants'])
                        ->orderBy('name', 'asc');

        // --- FILTER KATEGORI ---
        $categoryName = 'Semua Kategori';
        if ($request->has('category_id') && $request->category_id != 'all') {
            $query->where('category_id', $request->category_id);

            // Ambil nama kategori untuk judul di PDF
            $cat = Category::where('tenant_id', $this->tenantId)->find($request->category_id);
            if ($cat) {
                $categoryName = $cat->name;
            }
        }

        // --- FILTER JENIS PRODUK (Single / Variant) ---
        $typeName = 'Semua Jenis Produk';
        if ($request->has('type') && $request->type != 'all') {
            if ($request->type == 'variant') {
                $query->where('has_variant', 1);
                $typeName = 'Hanya Produk Multi Varian';
            } elseif ($request->type == 'single') {
                $query->where('has_variant', 0);
                $typeName = 'Hanya Produk Tunggal';
            }
        }

        // --- FILTER PENCARIAN (Opsional: Jika ingin cetak hasil search saja) ---
        if ($request->has('search') && $request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // 2. Eksekusi Query
        $products = $query->get();

        // 3. Load View PDF
        // Pastikan Anda sudah mengimport facade PDF di paling atas file:
        // use Barryvdh\DomPDF\Facade\Pdf;

        $pdf = Pdf::loadView('products.pdf_barcode', [
            'products' => $products,
            'categoryName' => $categoryName,
            'typeName' => $typeName
        ]);

        // 4. Atur Ukuran Kertas & Stream
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('laporan-stok-produk-' . date('Y-m-d') . '.pdf');
    }

    /**
     * Generate Barcode 13 Digit (Numeric Only)
     * Format: [ID_KATEGORI + 00] + [ANGKA_ACAK]
     * Contoh Kategori ID 1: 1008374625192
     */
    private function generateNumericBarcode($categoryId)
    {
        // 1. Tentukan Prefix (Awalan)
        // Misal: Kategori ID 1 jadi "100", ID 12 jadi "1200"
        $prefix = $categoryId . '00';

        // 2. Hitung sisa panjang digit yang dibutuhkan
        // Target EAN-13 adalah 13 digit.
        $targetLength = 13;
        $neededLength = $targetLength - strlen($prefix);

        // Jika prefix kepanjangan (jarang terjadi), batasi minimal random 3 digit
        if ($neededLength < 3) $neededLength = 3;

        do {
            // 3. Generate Angka Random Sisa
            $randomString = '';
            for ($i = 0; $i < $neededLength; $i++) {
                $randomString .= mt_rand(0, 9); // Pastikan cuma angka 0-9
            }

            $finalBarcode = $prefix . $randomString;

            // Pastikan panjang total tidak melebihi 13 (potong jika lebih)
            $finalBarcode = substr($finalBarcode, 0, 13);

            // 4. Cek Unik di Database (Produk & Varian)
            $exists = \App\Models\Product::where('barcode', $finalBarcode)->exists()
                   || \App\Models\ProductVariant::where('barcode', $finalBarcode)->exists();

        } while ($exists); // Ulangi terus sampai nemu yang belum dipakai

        return $finalBarcode;
    }

    /**
     * API KHUSUS ELECTRON: Scan Barcode
     */
    public function scanProduct(Request $request)
    {
        $keyword = $request->code;

        if (!$keyword) return response()->json(['status' => 'error', 'message' => 'Kode kosong'], 400);

        // 1. Cek Varian
        $variant = ProductVariant::where('tenant_id', $this->tenantId)
            ->where(function($q) use ($keyword) {
                $q->where('barcode', $keyword)->orWhere('sku', $keyword);
            })->first();

        if ($variant && $variant->product) {
            return response()->json([
                'status' => 'success', 'type' => 'variant',
                'data' => [
                    'id' => $variant->product_id, 'variant_id' => $variant->id,
                    'name' => $variant->product->name . ' - ' . $variant->name,
                    'price' => $variant->price, 'stock' => $variant->stock,
                    'image' => $variant->product->image,
                    'weight' => 0
                ]
            ]);
        }

        // 2. Cek Produk Utama
        $product = Product::where('tenant_id', $this->tenantId)
            ->where(function($q) use ($keyword) {
                $q->where('barcode', $keyword)->orWhere('sku', $keyword);
            })->first();

        if ($product) {
            if ($product->has_variant) {
                return response()->json(['status' => 'success', 'type' => 'choose_variant', 'data' => $product]);
            }
            return response()->json([
                'status' => 'success', 'type' => 'single',
                'data' => [
                    'id' => $product->id, 'variant_id' => null,
                    'name' => $product->name, 'price' => $product->sell_price,
                    'stock' => $product->stock, 'image' => $product->image,
                    'weight' => 0
                ]
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'Produk tidak ditemukan'], 404);
    }

    // --- FUNGSI KHUSUS API ELECTRON (PASTI JSON) ---
    public function apiList() {
        $products = Product::where('tenant_id', $this->tenantId) // <--- WAJIB TAMBAH INI
                        ->with('category')
                        ->where('stock_status', 'available')
                        ->latest()
                        ->get();
        return response()->json($products);
    }

    /**
     * GENERATOR BARCODE CERDAS (13 Digit)
     * - Prefix 200: Jasa
     * - Prefix 100: Barang
     * - Cek Unik per Tenant
     */
    private function generateSmartBarcode($type = 'physical')
    {
        // 1. Tentukan Prefix (200 utk Jasa, 100 utk Barang)
        // Asumsi nilai $type: 'service' atau 'physical'
        $prefix = ($type === 'service' || $type === 'jasa') ? '200' : '100';

        // 2. Hitung sisa digit (Target 13 digit)
        // Panjang prefix = 3. Sisa = 10 digit.
        $neededLength = 13 - strlen($prefix);

        do {
            // 3. Generate Angka Random
            $randomString = '';
            for ($i = 0; $i < $neededLength; $i++) {
                $randomString .= mt_rand(0, 9);
            }

            $finalBarcode = $prefix . $randomString;

            // 4. Cek Unik (HANYA DI TENANT INI)
            // Kita pakai query manual biar cepat
            $exists = DB::table('products')
                        ->where('tenant_id', $this->tenantId)
                        ->where('barcode', $finalBarcode)
                        ->exists()
                   || DB::table('product_variants')
                        ->where('tenant_id', $this->tenantId)
                        ->where('barcode', $finalBarcode)
                        ->exists();

        } while ($exists); // Ulangi kalau apes dapat angka kembar

        return $finalBarcode;
    }

}
