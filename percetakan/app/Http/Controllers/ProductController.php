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

    /**
     * Menyimpan produk baru
     */
    public function store(Request $request)
    {
        Log::info('[STORE START] Proses tambah produk...');
        $request->merge(['tenant_id' => $this->tenantId]);
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

        // Jika kosong, Auto-Generate Angka 13 Digit sesuai Kategori
        if (empty($barcodeToSave)) {
            $barcodeToSave = $this->generateNumericBarcode($request->category_id);
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
                'tenant_id'    => $this->tenantId, // <--- KUNCI DATA
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
                'barcode'      => $barcodeToSave, // Gunakan variabel baru ini
            ]);

            // 5. Simpan Varian (Looping)
            if ($hasVariant && $request->filled('variants')) {
                $totalVariantStock = 0;

                foreach ($request->variants as $variant) {
                    // Generate SKU Varian otomatis
                    $variantSku = $this->generateSku($request->name, $variant['name']);
                    $varBarcode = $variant['barcode'] ?? null;

                    ProductVariant::create([
                        'tenant_id'  => $this->tenantId, // <--- KUNCI VARIAN
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

        // Jika user mengosongkan barcode (ingin generate baru) atau memang kosong
        if (empty($barcodeToSave)) {
            // Gunakan kategori dari request (jika diubah) atau kategori lama produk
            $catId = $request->category_id ?? $product->category_id;
            $barcodeToSave = $this->generateNumericBarcode($catId);
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

                        // Jika varian tidak punya barcode, buatkan juga sesuai kategori induk
                        if (empty($varBarcode)) {
                            $varBarcode = $this->generateNumericBarcode($request->category_id);
                        }

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
                        'tenant_id' => $this->tenantId, // <--- WAJIB TAMBAHKAN INI
                        'name'  => $variant['name'],
                        'price' => $variant['price'],
                        'stock' => $variant['stock'],
                        'sku'   => $variantSku,
                        'barcode' => [
                            'nullable',
                            Rule::unique('products')->where('tenant_id', $this->tenantId)->ignore($product->id ?? null),
                        ],
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
     * SIMPAN VARIAN PRODUK (Dipanggil via AJAX/AlpineJS)
     */
    public function saveVariants(Request $request, $id)
    {
        // 1. Validasi Input Array
        $request->validate([
            'variants'         => 'required|array',
            'variants.*.name'  => 'required|string',
            'variants.*.price' => 'required|numeric',
            'variants.*.stock' => 'required|integer',
        ]);

        DB::beginTransaction();
        try {
            // 2. Kunci Keamanan: Pastikan produk milik Tenant ini
            $product = Product::where('tenant_id', $this->tenantId)->findOrFail($id);

            // 3. Logika Hapus: Ambil semua ID yang dikirim dari form
            // Jika ada varian lama di database yang ID-nya TIDAK dikirim, berarti user menghapusnya di form.
            $submittedIds = collect($request->variants)->pluck('id')->filter()->toArray();
            $product->variants()->whereNotIn('id', $submittedIds)->delete();

            // 4. Loop Simpan / Update
            foreach ($request->variants as $v) {
                // Handle Barcode kosong agar jadi NULL (supaya tidak error unique constraint)
                $barcode = !empty($v['barcode']) ? $v['barcode'] : null;

                $product->variants()->updateOrCreate(
                    [
                        'id' => $v['id'] ?? null, // Jika ID ada, dia Update. Jika null, dia Create.
                    ],
                    [
                        'tenant_id'  => $this->tenantId, // <--- WAJIB: Suntik ID Tenant
                        'product_id' => $product->id,
                        'name'       => $v['name'],
                        'price'      => $v['price'],
                        'stock'      => $v['stock'],
                        'sku'        => $v['sku'] ?? null,
                        'barcode'    => $barcode,
                    ]
                );
            }

            // 5. Update Total Stok Produk Utama (Opsional: Akumulasi otomatis)
            $totalStock = $product->variants()->sum('stock');
            $product->update([
                'stock' => $totalStock,
                'has_variant' => true // Tandai bahwa produk ini punya varian
            ]);

            DB::commit();

            // 6. Respon JSON (Penting buat Javascript)
            return response()->json([
                'success' => true,
                'message' => 'Varian berhasil disimpan!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            // Kembalikan pesan error yang jelas ke Javascript
            return response()->json([
                'success' => false,
                'message' => 'Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

}
