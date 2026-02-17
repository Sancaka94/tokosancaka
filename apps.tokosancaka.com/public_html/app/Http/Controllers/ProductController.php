<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

// --- TAMBAHAN UNTUK LARAVEL 11 KE ATAS ---
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ProductController extends Controller implements HasMiddleware
{
    protected $tenantId;

    /**
     * PENGGANTI $this->middleware('auth') DI CONSTRUCT
     * Ini cara baru di Laravel 11 agar tidak error "Call to undefined method"
     */
    public static function middleware(): array
    {
        return [
            // Terapkan middleware 'auth' ke semua method di controller ini
            new Middleware('auth', except: ['apiList', 'scanProduct']),
            // Catatan: Saya mengecualikan 'apiList' & 'scanProduct' jaga-jaga jika Electron akses tanpa login.
            // Jika Electron login, hapus ", except: [...]" di atas.
        ];
    }

    public function __construct(Request $request)
{
    // 1. Deteksi Subdomain
    $host = $request->getHost();
    $subdomain = explode('.', $host)[0];

    // --- LOGIKA BARU ---
    // Tambahkan 'admin' di sini
    if ($subdomain === 'apps' || $subdomain === 'www' || $subdomain === 'admin') {

        // ID 1 adalah Admin Pusat (Sesuai database Anda)
        $this->tenantId = 1;

        return; // Langsung keluar, logic selesai.
    }

    $tenant = Tenant::where('subdomain', $subdomain)->first();

    if (!$tenant) {
        abort(404, 'Toko/Tenant tidak ditemukan.');
    }

    $this->tenantId = $tenant->id;
}

    /**
     * Menampilkan daftar produk
     * Route: GET {subdomain}/products
     */
    public function index(Request $request, $subdomain = null)
    {
        $categories = Category::where('tenant_id', $this->tenantId)
                              ->orderBy('name', 'asc')
                              ->get();

        $query = Product::where('tenant_id', $this->tenantId)
                        ->with(['category', 'variants'])
                        ->orderBy('name', 'asc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id') && $request->category_id != 'all') {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->paginate(10)->withQueryString();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json($query->get());
        }

        return view('products.index', compact('products', 'categories'));
    }

    private function generateSku($productName, $variantName = null)
    {
        $cleanName = preg_replace('/[^A-Za-z0-9]/', '', $productName);
        $pName = strtoupper(substr($cleanName, 0, 3));

        if ($variantName) {
            $cleanVar = preg_replace('/[^A-Za-z0-9]/', '', $variantName);
            $vName = strtoupper(substr($cleanVar, 0, 3));
            return $pName . '-' . $vName . '-' . mt_rand(1000, 9999);
        }
        return $pName . '-' . mt_rand(10000, 99999);
    }

    public function create($subdomain = null)
    {
        $categories = Category::where('tenant_id', $this->tenantId)
                              ->where('is_active', true)
                              ->get();
        return view('products.create', compact('categories'));
    }

    public function store(Request $request, $subdomain = null)
    {
        $userId = Auth::id();

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'type'        => 'nullable|in:physical,service',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'barcode'     => [
                'nullable', 'min:10', 'max:13',
                Rule::unique('products')->where('tenant_id', $this->tenantId),
                Rule::unique('product_variants')->where('tenant_id', $this->tenantId)
            ],
            'variants'    => 'array',
            'variants.*.barcode' => [
                'nullable', 'min:10', 'max:13', 'distinct',
                Rule::unique('products', 'barcode')->where('tenant_id', $this->tenantId),
                Rule::unique('product_variants', 'barcode')->where('tenant_id', $this->tenantId)
            ]
        ]);

        if ($validator->fails()) return back()->withErrors($validator)->withInput();

        DB::beginTransaction();
        try {
            $imagePath = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                $imagePath = $file->storeAs("products/tenant_{$this->tenantId}", $filename, 'public');
            }

            $barcodeToSave = $request->barcode;
            $productType = $request->type ?? 'physical';

            if (empty($barcodeToSave)) {
                $barcodeToSave = $this->generateSmartBarcode($productType);
            }

            $hasVariant = $request->has('has_variant') ? 1 : 0;
            $productSku = $this->generateSku($request->name);

            $product = Product::create([
                'tenant_id'    => $this->tenantId,
                'user_id'      => $userId,
                'created_by'   => $userId,
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

            if ($hasVariant && $request->filled('variants')) {
                $totalStock = 0;
                foreach ($request->variants as $variant) {
                    $varBarcode = $variant['barcode'] ?? null;
                    if (empty($varBarcode)) {
                        $varBarcode = $this->generateSmartBarcode($productType);
                    }
                    ProductVariant::create([
                        'tenant_id'  => $this->tenantId,
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
            if (isset($imagePath)) Storage::disk('public')->delete($imagePath);
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, $subdomain, $id)
    {
        $product = Product::where('tenant_id', $this->tenantId)
                          ->where('id', $id)
                          ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'base_price'  => 'required|numeric|min:0',
            'sell_price'  => 'nullable|numeric|min:0',
            'unit'        => 'required|string',
            'barcode'     => [
                'nullable',
                Rule::unique('products')->ignore($product->id)->where('tenant_id', $this->tenantId),
                Rule::unique('product_variants')->where('tenant_id', $this->tenantId)
            ],
        ]);

        $barcodeToSave = $request->barcode;
        if (empty($barcodeToSave)) {
            $catId = $request->category_id ?? $product->category_id;
            $barcodeToSave = $this->generateNumericBarcode($catId);
        }

        if ($validator->fails()) return back()->withErrors($validator)->withInput();

        DB::beginTransaction();

        try {
            $hasVariant = $request->has('has_variant') ? 1 : 0;
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
                'updated_by'  => Auth::id(),
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

            if ($hasVariant) {
                ProductVariant::where('product_id', $product->id)
                              ->where('tenant_id', $this->tenantId)
                              ->delete();

                $totalVariantStock = 0;
                if ($request->filled('variants')) {
                    foreach ($request->variants as $variant) {
                        $variantSku = $variant['sku'] ?? $this->generateSku($request->name, $variant['name']);
                        $varBarcode = $variant['barcode'] ?? null;
                        if (empty($varBarcode)) {
                            $varBarcode = $this->generateNumericBarcode($request->category_id);
                        }
                        ProductVariant::create([
                            'tenant_id'  => $this->tenantId,
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

    public function destroy($subdomain, $id)
    {
        $product = Product::where('tenant_id', $this->tenantId)
                          ->where('id', $id)
                          ->firstOrFail();
        try {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $product->delete();
            return redirect()->route('products.index', $subdomain)->with('success', 'Produk dihapus!');
        } catch (\Exception $e) {
            return redirect()->route('products.index', $subdomain)->with('error', 'Gagal hapus.');
        }
    }

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

    public function show($subdomain, $id)
    {
        $product = Product::where('tenant_id', $this->tenantId)
                          ->where('id', $id)
                          ->firstOrFail();

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

    public function getVariants($subdomain, $id)
    {
        $product = Product::where('tenant_id', $this->tenantId)
                          ->where('id', $id)
                          ->first();

        if (!$product) {
            return response()->json(['error' => 'Produk tidak ditemukan.'], 404);
        }

        return response()->json([
            'product_name' => $product->name,
            'variants'     => $product->variants()
                                      ->where('tenant_id', $this->tenantId)
                                      ->get()
        ]);
    }

    public function updateVariants(Request $request, $subdomain, $id)
    {
        $product = Product::where('tenant_id', $this->tenantId)
                          ->where('id', $id)
                          ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'variants' => 'array',
            'variants.*.name' => 'required|string',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $parentType = $product->type;
            if (empty($parentType) && !empty($product->barcode)) {
                $prefixInduk = substr($product->barcode, 0, 3);
                $parentType = ($prefixInduk === '200') ? 'service' : 'physical';
            }

            ProductVariant::where('product_id', $product->id)
                          ->where('tenant_id', $this->tenantId)
                          ->delete();

            $totalStock = 0;
            if ($request->filled('variants')) {
                foreach ($request->variants as $variant) {
                    $varBarcode = $variant['barcode'] ?? null;
                    if (empty($varBarcode)) {
                        $varBarcode = $this->generateSmartBarcode($parentType);
                    }
                    $variantSku = $variant['sku'] ?? null;
                    if (empty($variantSku)) {
                        $variantSku = $this->generateSku($product->name, $variant['name']);
                    }

                    ProductVariant::create([
                        'tenant_id'  => $this->tenantId,
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

            $product->update([
                'stock' => $totalStock,
                'has_variant' => count($request->variants) > 0 ? 1 : 0,
                'stock_status' => $totalStock > 0 ? 'available' : 'out_of_stock',
                'updated_by' => Auth::id()
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Varian berhasil diperbarui!']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update Variants Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal simpan: ' . $e->getMessage()], 500);
        }
    }

    public function downloadPdf(Request $request, $subdomain = null)
    {
        $query = Product::where('tenant_id', $this->tenantId)
                        ->with(['category', 'variants'])
                        ->orderBy('name', 'asc');

        $categoryName = 'Semua Kategori';
        if ($request->has('category_id') && $request->category_id != 'all') {
            $query->where('category_id', $request->category_id);
            $cat = Category::where('tenant_id', $this->tenantId)->find($request->category_id);
            if ($cat) {
                $categoryName = $cat->name;
            }
        }

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

        if ($request->has('search') && $request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $products = $query->get();
        $pdf = Pdf::loadView('products.pdf_barcode', [
            'products' => $products,
            'categoryName' => $categoryName,
            'typeName' => $typeName
        ]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('laporan-stok-produk-' . date('Y-m-d') . '.pdf');
    }

    private function generateNumericBarcode($categoryId)
    {
        $prefix = $categoryId . '00';
        $targetLength = 13;
        $neededLength = $targetLength - strlen($prefix);

        if ($neededLength < 3) $neededLength = 3;

        do {
            $randomString = '';
            for ($i = 0; $i < $neededLength; $i++) {
                $randomString .= mt_rand(0, 9);
            }
            $finalBarcode = $prefix . $randomString;
            $finalBarcode = substr($finalBarcode, 0, 13);

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

    public function scanProduct(Request $request)
    {
        $keyword = $request->code;
        if (!$keyword) return response()->json(['status' => 'error', 'message' => 'Kode kosong'], 400);

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

    public function apiList() {
        $products = Product::withoutGlobalScopes()
                        ->where('tenant_id', $this->tenantId)
                        ->with('category')
                        ->where('stock_status', 'available')
                        ->latest()
                        ->get()
                        ->map(function($product) {
                            // KITA FORMAT ULANG DATANYA BIAR COCOK DENGAN ELECTRON
                            return [
                                'id' => $product->id,
                                'tenant_id' => $product->tenant_id,
                                'category_id' => $product->category_id,
                                'name' => $product->name,

                                // INI KUNCINYA: Kita buat kolom 'price' yang isinya dari 'sell_price'
                                'price' => $product->sell_price,
                                'sell_price' => $product->sell_price, // Tetap sertakan aslinya jaga-jaga
                                'base_price' => $product->base_price,

                                'stock' => $product->stock,
                                'unit' => $product->unit,
                                'barcode' => $product->barcode,
                                'sku' => $product->sku,
                                'image' => $product->image,
                                'category_name' => $product->category ? $product->category->name : '-', // Tambahan info kategori
                                'has_variant' => $product->has_variant,
                            ];
                        });

        return response()->json($products);
    }

    private function generateSmartBarcode($type = 'physical')
    {
        $prefix = ($type === 'service' || $type === 'jasa') ? '200' : '100';
        $neededLength = 13 - strlen($prefix);

        do {
            $randomString = '';
            for ($i = 0; $i < $neededLength; $i++) {
                $randomString .= mt_rand(0, 9);
            }
            $finalBarcode = $prefix . $randomString;

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
