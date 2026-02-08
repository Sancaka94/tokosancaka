<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Models\Tenant; // <--- WAJIB: Model Tenant
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth; // <--- WAJIB: Auth User
use Barryvdh\DomPDF\Facade\Pdf;

class ProductController extends Controller
{
    protected $tenantId;

    public function __construct(Request $request)
    {
        // 1. Deteksi Subdomain & Tenant
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];

        $tenant = Tenant::where('subdomain', $subdomain)->first();

        // Strict Check: Jika tenant tidak ada, error 404 (Security)
        if (!$tenant) {
            abort(404, 'Toko/Tenant tidak ditemukan.');
        }

        $this->tenantId = $tenant->id;

        // 2. Middleware Auth (Wajib Login)
        $this->middleware('auth');
    }

    /**
     * Menampilkan daftar produk
     * Route: GET {subdomain}/products
     */
    public function index(Request $request, $subdomain = null)
    {
        // 1. Ambil Data Kategori - Filter Tenant
        $categories = Category::where('tenant_id', $this->tenantId)
                              ->orderBy('name', 'asc')
                              ->get();

        // 2. Query Produk - Filter Tenant
        $query = Product::where('tenant_id', $this->tenantId) // <--- TENANT FILTER
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

    /**
     * Halaman Create Standard
     */
    public function create($subdomain = null)
    {
        $categories = Category::where('tenant_id', $this->tenantId)
                              ->where('is_active', true)
                              ->get();
        return view('products.create', compact('categories'));
    }

    /**
     * Proses Simpan Produk Baru
     * Route: POST {subdomain}/products
     */
    public function store(Request $request, $subdomain = null)
    {
        // 1. Ambil User ID
        $userId = Auth::id();

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
                Rule::unique('products')->where('tenant_id', $this->tenantId),
                Rule::unique('product_variants')->where('tenant_id', $this->tenantId)
            ],
            // Validasi Varian
            'variants'    => 'array',
            'variants.*.barcode' => [
                'nullable',
                'min:10',
                'max:13',
                'distinct',
                Rule::unique('products', 'barcode')->where('tenant_id', $this->tenantId),
                Rule::unique('product_variants', 'barcode')->where('tenant_id', $this->tenantId)
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
                // Simpan ke folder tenant agar terpisah: products/tenant_ID
                $imagePath = $file->storeAs("products/tenant_{$this->tenantId}", $filename, 'public');
            }

            // 4. LOGIKA AUTO BARCODE INDUK
            $barcodeToSave = $request->barcode;
            $productType = $request->type ?? 'physical'; // Default ke Barang jika null

            if (empty($barcodeToSave)) {
                // Generate 100... atau 200... (Smart Barcode)
                $barcodeToSave = $this->generateSmartBarcode($productType);
            }

            $hasVariant = $request->has('has_variant') ? 1 : 0;
            $productSku = $this->generateSku($request->name);

            // 5. SIMPAN KE DATABASE
            $product = Product::create([
                'tenant_id'    => $this->tenantId, // <--- TENANT ID
                'user_id'      => $userId,         // <--- USER ID
                'created_by'   => $userId,         // <--- LOG CREATOR
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
                'image'        => $imagePath,
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
                        'tenant_id'  => $this->tenantId, // <--- TENANT ID
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
            return redirect()->route('products.index', $subdomain)->with('success', 'Produk Berhasil Disimpan');

        } catch (\Exception $e) {
            DB::rollBack();
            // Hapus gambar jika database gagal simpan (biar server gak penuh sampah)
            if (isset($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Proses Update Produk
     * Method Signature: ($request, $subdomain, $id) -> Urutan Parameter Route
     */
    public function update(Request $request, $subdomain, $id)
    {
        // 1. Validasi Kepemilikan (Strict Tenant Check)
        $product = Product::where('tenant_id', $this->tenantId)
                          ->where('id', $id)
                          ->firstOrFail();

        // 2. Validasi Input
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'base_price'  => 'required|numeric|min:0',
            'sell_price'  => 'nullable|numeric|min:0',
            'unit'        => 'required|string',
            // Barcode unik kecuali milik produk ini sendiri
            'barcode'     => [
                'nullable',
                Rule::unique('products')->ignore($product->id)->where('tenant_id', $this->tenantId),
                Rule::unique('product_variants')->where('tenant_id', $this->tenantId)
            ],
        ]);

        // Logic Barcode (Generate Numeric jika kosong - SESUAI KODE ASLI)
        $barcodeToSave = $request->barcode;
        if (empty($barcodeToSave)) {
            $catId = $request->category_id ?? $product->category_id;
            // Gunakan Numeric Barcode sesuai kode lama Anda
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
                'updated_by'  => Auth::id(), // <--- UPDATE LOG USER
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
                $data['image'] = $file->storeAs("products/tenant_{$this->tenantId}", $filename, 'public');
            }

            $product->update($data);

            // LOGIKA UPDATE VARIAN (Manual Form / Bukan Ajax)
            // Kode asli Anda punya blok ini di update(), jadi saya pertahankan.
            // Namun jika Anda pakai Modal Ajax, blok ini mungkin tidak terpakai,
            // tapi tetap saya simpan agar "LENGKAP".
            if ($hasVariant) {
                // Hapus lama (Scope Tenant)
                ProductVariant::where('product_id', $product->id)
                              ->where('tenant_id', $this->tenantId)
                              ->delete();

                $totalVariantStock = 0;

                if ($request->filled('variants')) {
                    foreach ($request->variants as $variant) {

                        $variantSku = $variant['sku'] ?? $this->generateSku($request->name, $variant['name']);

                        // Cek Barcode Varian
                        $varBarcode = $variant['barcode'] ?? null;
                        if (empty($varBarcode)) {
                            // Gunakan Numeric Barcode (Kode Asli)
                            $varBarcode = $this->generateNumericBarcode($request->category_id);
                        }

                        ProductVariant::create([
                            'tenant_id'  => $this->tenantId, // <--- TENANT ID
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
                // Jika berubah jadi single product, hapus varian
                ProductVariant::where('product_id', $product->id)
                              ->where('tenant_id', $this->tenantId)
                              ->delete();
            }

            DB::commit();
            return redirect()->route('products.index', $subdomain)->with('success', 'Produk berhasil diperbarui!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[UPDATE ERROR] ' . $e->getMessage());
            return back()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }

    /**
     * Hapus Produk
     */
    public function destroy($subdomain, $id)
    {
        // Cari Product Scope Tenant
        $product = Product::where('tenant_id', $this->tenantId)
                          ->where('id', $id)
                          ->firstOrFail();

        try {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            // Cascade delete di database akan otomatis menghapus variants
            $product->delete();

            return redirect()->route('products.index', $subdomain)->with('success', 'Produk dihapus!');
        } catch (\Exception $e) {
            return redirect()->route('products.index', $subdomain)->with('error', 'Gagal hapus.');
        }
    }

    /**
     * Halaman Edit Standard
     */
    public function edit($subdomain, $id)
    {
        $product = Product::where('tenant_id', $this->tenantId)
                          ->where('id', $id)
                          ->firstOrFail();

        $product->load('variants');

        $categories = Category::where('tenant_id', $this->tenantId)
                              ->where('is_active', true)
                              ->orderBy('name', 'asc')
                              ->get();

        return view('products.edit', compact('product', 'categories'));
    }

    /**
     * Detail Produk
     */
    public function show($subdomain, $id)
    {
        $product = Product::where('tenant_id', $this->tenantId)
                          ->where('id', $id)
                          ->firstOrFail();

        // Jika request dari Electron, return JSON
        if (request()->wantsJson() || request()->is('api/*')) {
            $product->load(['variants', 'category']);
            return response()->json($product);
        }

        $product->load(['variants', 'category']);
        return view('products.show', compact('product'));
    }

    // ================================================================
    // API UNTUK MODAL VARIAN (AJAX) - FIX SUBDOMAIN PARAMETER
    // ================================================================

    /**
     * Get Variants for Modal
     * Method Signature: ($subdomain, $id)
     */
    public function getVariants($subdomain, $id)
    {
        // Cari Produk Tenant Ini
        $product = Product::where('tenant_id', $this->tenantId)
                          ->where('id', $id)
                          ->first();

        // Fix Javascript Proxy Error
        if (!$product) {
            return response()->json(['error' => 'Produk tidak ditemukan.'], 404);
        }

        return response()->json([
            'product_name' => $product->name,
            'variants'     => $product->variants()
                                      ->where('tenant_id', $this->tenantId) // Tenant Scope
                                      ->withoutGlobalScopes() // Opsional jika ada scope lain
                                      ->get()
        ]);
    }

    /**
     * Update Varian Produk via Ajax/Modal
     * Method Signature: ($request, $subdomain, $id)
     */
    public function updateVariants(Request $request, $subdomain, $id)
    {
        // 1. CARI PRODUK TENANT
        $product = Product::where('tenant_id', $this->tenantId)
                          ->where('id', $id)
                          ->firstOrFail();

        // 2. VALIDASI INPUT
        $validator = Validator::make($request->all(), [
            'variants' => 'array',
            'variants.*.name' => 'required|string',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock' => 'required|numeric|min:0',
            // Validasi barcode unik handle di catch
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // Deteksi Tipe Induk (Physical/Service) untuk Prefix Barcode (100/200)
            $parentType = $product->type;
            if (empty($parentType) && !empty($product->barcode)) {
                $prefixInduk = substr($product->barcode, 0, 3);
                $parentType = ($prefixInduk === '200') ? 'service' : 'physical';
            }

            // 3. HAPUS VARIAN LAMA (Reset Strategy) - Scope Tenant
            ProductVariant::where('product_id', $product->id)
                          ->where('tenant_id', $this->tenantId)
                          ->delete();

            $totalStock = 0;

            if ($request->filled('variants')) {
                foreach ($request->variants as $variant) {

                    // A. LOGIKA BARCODE VARIAN
                    $varBarcode = $variant['barcode'] ?? null;
                    if (empty($varBarcode)) {
                        // Generate Barcode Otomatis
                        $varBarcode = $this->generateSmartBarcode($parentType);
                    }

                    // B. LOGIKA SKU VARIAN
                    $variantSku = $variant['sku'] ?? null;
                    if (empty($variantSku)) {
                        // Generate SKU Otomatis
                        $variantSku = $this->generateSku($product->name, $variant['name']);
                    }

                    // C. SIMPAN VARIAN BARU
                    ProductVariant::create([
                        'tenant_id'  => $this->tenantId, // <--- WAJIB
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

            // 4. UPDATE STATUS STOK INDUK
            $product->update([
                'stock' => $totalStock,
                'has_variant' => count($request->variants) > 0 ? 1 : 0,
                'stock_status' => $totalStock > 0 ? 'available' : 'out_of_stock',
                'updated_by' => Auth::id() // <--- LOG USER
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Varian berhasil diperbarui!']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update Variants Error: ' . $e->getMessage());
            // Return error agar bisa ditangkap JavaScript Frontend
            return response()->json(['success' => false, 'message' => 'Gagal simpan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Download PDF
     * Method Signature: ($request, $subdomain)
     */
    public function downloadPdf(Request $request, $subdomain = null)
    {
        // 1. Mulai Query dengan Eager Loading
        $query = Product::where('tenant_id', $this->tenantId) // <--- Filter Tenant
                        ->with(['category', 'variants'])
                        ->orderBy('name', 'asc');

        // --- FILTER KATEGORI ---
        $categoryName = 'Semua Kategori';
        if ($request->has('category_id') && $request->category_id != 'all') {
            $query->where('category_id', $request->category_id);

            // Ambil nama kategori untuk judul di PDF (Tenant Scope)
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

        // --- FILTER PENCARIAN ---
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
     * Generate Barcode 13 Digit (Numeric Only) - DIKEMBALIKAN SESUAI PERMINTAAN
     * Format: [ID_KATEGORI + 00] + [ANGKA_ACAK]
     */
    private function generateNumericBarcode($categoryId)
    {
        // 1. Tentukan Prefix (Awalan)
        // Misal: Kategori ID 1 jadi "100", ID 12 jadi "1200"
        $prefix = $categoryId . '00';

        // 2. Hitung sisa panjang digit yang dibutuhkan
        $targetLength = 13;
        $neededLength = $targetLength - strlen($prefix);

        // Jika prefix kepanjangan, batasi minimal random 3 digit
        if ($neededLength < 3) $neededLength = 3;

        do {
            // 3. Generate Angka Random Sisa
            $randomString = '';
            for ($i = 0; $i < $neededLength; $i++) {
                $randomString .= mt_rand(0, 9); // Pastikan cuma angka 0-9
            }

            $finalBarcode = $prefix . $randomString;

            // Pastikan panjang total tidak melebihi 13
            $finalBarcode = substr($finalBarcode, 0, 13);

            // 4. Cek Unik di Database (Tenant Scope)
            $exists = DB::table('products')
                        ->where('tenant_id', $this->tenantId)
                        ->where('barcode', $finalBarcode)
                        ->exists()
                   || DB::table('product_variants')
                        ->where('tenant_id', $this->tenantId)
                        ->where('barcode', $finalBarcode)
                        ->exists();

        } while ($exists);

        return $finalBarcode;
    }

    /**
     * API KHUSUS ELECTRON: Scan Barcode
     * Method Signature: ($request) - API biasa jarang pake subdomain di method, tapi scoped tenant
     */
    public function scanProduct(Request $request)
    {
        $keyword = $request->code;

        if (!$keyword) return response()->json(['status' => 'error', 'message' => 'Kode kosong'], 400);

        // 1. Cek Varian (Filter Tenant)
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

        // 2. Cek Produk Utama (Filter Tenant)
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

    /**
     * API LIST (JSON)
     */
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
     */
    private function generateSmartBarcode($type = 'physical')
    {
        // 1. Tentukan Prefix (200 utk Jasa, 100 utk Barang)
        $prefix = ($type === 'service' || $type === 'jasa') ? '200' : '100';

        // 2. Hitung sisa digit (Target 13 digit)
        $neededLength = 13 - strlen($prefix);

        do {
            // 3. Generate Angka Random
            $randomString = '';
            for ($i = 0; $i < $neededLength; $i++) {
                $randomString .= mt_rand(0, 9);
            }

            $finalBarcode = $prefix . $randomString;

            // 4. Cek Unik (HANYA DI TENANT INI)
            $exists = DB::table('products')
                        ->where('tenant_id', $this->tenantId)
                        ->where('barcode', $finalBarcode)
                        ->exists()
                   || DB::table('product_variants')
                        ->where('tenant_id', $this->tenantId)
                        ->where('barcode', $finalBarcode)
                        ->exists();

        } while ($exists);

        return $finalBarcode;
    }
}
