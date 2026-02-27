<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Models\ProductSubVariant; // <--- WAJIB IMPORT INI
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
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth', except: [
                'apiList',
                'scanProduct',
                'getVariants'
            ]),
        ];
    }

    public function __construct(Request $request)
    {
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];

        if ($subdomain === 'apps' || $subdomain === 'www' || $subdomain === 'admin') {
            $this->tenantId = 1;
            return;
        }

        $tenant = Tenant::where('subdomain', $subdomain)->first();

        if (!$tenant) {
            abort(404, 'Toko/Tenant tidak ditemukan.');
        }

        $this->tenantId = $tenant->id;
    }

    public function index(Request $request, $subdomain = null)
    {
        $categories = Category::where('tenant_id', $this->tenantId)
                              ->orderBy('name', 'asc')
                              ->get();

        // UPDATE: Load subVariants juga
        $query = Product::where('tenant_id', $this->tenantId)
                        ->with(['category', 'variants.subVariants'])
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
            'discount_type'  => 'nullable|in:percent,nominal',
            'discount_value' => 'nullable|numeric|min:0',
            'barcode'     => [
                'nullable', 'min:10', 'max:13',
                // PERBAIKAN: Hilangkan ->ignore() karena ini insert baru
                Rule::unique('products')->where('tenant_id', $this->tenantId),
                Rule::unique('product_variants')->where('tenant_id', $this->tenantId),
                Rule::unique('product_sub_variants')->where('tenant_id', $this->tenantId)
            ],
            'variants'    => 'array',
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

            $productType = $request->type ?? 'physical';
            $barcodeToSave = $request->barcode ?: $this->generateSmartBarcode($productType);
            $hasVariant = $request->has('has_variant') ? 1 : 0;
            $productSku = $this->generateSku($request->name);

            $product = Product::create([
                'tenant_id'      => $this->tenantId,
                'user_id'        => $userId,
                'created_by'     => $userId,
                'name'           => $request->name,
                'description'    => $request->description,
                'weight'         => $request->weight ?? 0,
                'type'           => $productType,
                'sku'            => $productSku,
                'category_id'    => $request->category_id,
                'base_price'     => $request->base_price,
                'sell_price'     => $request->sell_price ?? 0,
                'discount_type'  => $request->discount_type ?? 'percent',
                'discount_value' => $request->discount_value ?? 0,
                'stock'          => $hasVariant ? 0 : ($request->stock ?? 0),
                'stock_status'   => 'available',
                'has_variant'    => $hasVariant,
                'barcode'        => $barcodeToSave,
                'image'          => $imagePath,
                'unit'           => $request->unit ?? 'pcs',
                'supplier'       => $request->supplier,
                'is_best_seller' => $request->has('is_best_seller') ? 1 : 0,
                'is_terlaris'    => $request->has('is_terlaris') ? 1 : 0,
                'is_new_arrival' => $request->has('is_new_arrival') ? 1 : 0,
                'is_flash_sale'  => $request->has('is_flash_sale') ? 1 : 0,
                'is_free_ongkir' => $request->has('is_free_ongkir') ? 1 : 0,
                'is_cashback_extra' => $request->has('is_cashback_extra') ? 1 : 0,
            ]);

            if ($hasVariant && $request->filled('variants')) {
                $totalProductStock = 0;

                foreach ($request->variants as $varData) {
                    $varBarcode = $varData['barcode'] ?? $this->generateSmartBarcode($productType);
                    $varSku = $varData['sku'] ?? $this->generateSku($request->name, $varData['name']);

                    $variant = ProductVariant::create([
                        'tenant_id'  => $this->tenantId,
                        'product_id' => $product->id,
                        'name'       => $varData['name'],
                        'price'      => $varData['price'],
                        'stock'      => $varData['stock'] ?? 0,
                        'sku'        => $varSku,
                        'barcode'    => $varBarcode,
                        'discount_type'  => $varData['discount_type'] ?? 'percent',
                        'discount_value' => $varData['discount_value'] ?? 0,
                    ]);

                    $variantStock = 0;

                    if (isset($varData['sub_variants']) && is_array($varData['sub_variants'])) {
                        foreach ($varData['sub_variants'] as $subData) {
                            $subBarcode = $subData['barcode'] ?? $this->generateSmartBarcode($productType);
                            $subSku = $subData['sku'] ?? $this->generateSku($variant->name, $subData['name']);

                            ProductSubVariant::create([
                                'tenant_id'          => $this->tenantId,
                                'product_variant_id' => $variant->id,
                                'name'               => $subData['name'],
                                'price'              => $subData['price'],
                                'stock'              => $subData['stock'] ?? 0,
                                'weight'             => $subData['weight'] ?? 0,
                                'sku'                => $subSku,
                                'barcode'            => $subBarcode,
                                'discount_type'      => $subData['discount_type'] ?? 'percent',
                                'discount_value'     => $subData['discount_value'] ?? 0,
                            ]);

                            $variantStock += ($subData['stock'] ?? 0);
                        }
                        $variant->update(['stock' => $variantStock]);
                    } else {
                        $variantStock = $varData['stock'] ?? 0;
                    }

                    $totalProductStock += $variantStock;
                }
                $product->update(['stock' => $totalProductStock]);
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
        Log::info("=== [UPDATE PRODUCT START] ===");
        Log::info("ID Produk: {$id} | Tenant ID: {$this->tenantId}");
        Log::info("Data Request:", $request->all());

        $product = Product::where('tenant_id', $this->tenantId)->findOrFail($id);
        Log::info("Produk Ditemukan: {$product->name}");

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'base_price'  => 'nullable|numeric|min:0',
            'sell_price'  => 'nullable|numeric|min:0',
            'unit'        => 'required|string',
            'discount_type'  => 'nullable|in:percent,nominal',
            'discount_value' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            Log::warning("Validasi Gagal:", $validator->errors()->toArray());
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        Log::info("Validasi Sukses. Memulai DB Transaction...");
        DB::beginTransaction();

        try {
            $hasVariant = $request->has('has_variant') ? 1 : 0;
            $currentSku = $product->sku ?: $this->generateSku($request->name);
            $barcodeToSave = $request->barcode ?: ($product->barcode ?: $this->generateNumericBarcode($request->category_id));

            Log::info("Menyiapkan Data Dasar. Mode Variant: " . ($hasVariant ? 'AKTIF' : 'NON-AKTIF'));

            $data = [
                'name'           => $request->name,
                'description'    => $request->description,
                'weight'         => $request->weight ?? 0,
                'sku'            => $currentSku,
                'category_id'    => $request->category_id,
                'base_price'     => $request->base_price ?? 0,
                'sell_price'     => $request->sell_price ?? 0,
                'discount_type'  => $request->discount_type ?? 'percent',
                'discount_value' => $request->discount_value ?? 0,
                'unit'           => $request->unit,
                'supplier'       => $request->supplier,
                'has_variant'    => $hasVariant,
                'barcode'        => $barcodeToSave,
                'updated_by'     => Auth::id(),
                'is_best_seller' => $request->has('is_best_seller') ? 1 : 0,
                'is_terlaris'    => $request->has('is_terlaris') ? 1 : 0,
                'is_new_arrival' => $request->has('is_new_arrival') ? 1 : 0,
                'is_flash_sale'  => $request->has('is_flash_sale') ? 1 : 0,
                'is_free_ongkir' => $request->has('is_free_ongkir') ? 1 : 0,
                'is_cashback_extra' => $request->has('is_cashback_extra') ? 1 : 0,
            ];

            if (!$hasVariant) {
                $data['stock'] = $request->stock ?? 0;
                $data['stock_status'] = ($data['stock'] > 0) ? 'available' : 'out_of_stock';
                Log::info("Set Stok Tunggal: {$data['stock']}");
            }

            if ($request->hasFile('image')) {
                Log::info("Memproses Gambar Baru...");
                if ($product->image && Storage::disk('public')->exists($product->image)) {
                    Storage::disk('public')->delete($product->image);
                }
                $file = $request->file('image');
                $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                $data['image'] = $file->storeAs("products/tenant_{$this->tenantId}", $filename, 'public');
            }

            Log::info("Menyimpan Update ke Tabel Products...");
            $product->update($data);

            if ($hasVariant) {
                Log::info("Memulai Proses Looping Varian...");
                $totalProductStock = 0;
                $keepVariantIds = [];

                if ($request->filled('variants')) {
                    foreach ($request->variants as $index => $varData) {
                        Log::info(">> Memproses Varian Index {$index} | Nama: {$varData['name']}");

                        $variantId = !empty($varData['id']) ? $varData['id'] : null;
                        $variant = null;

                        if ($variantId) {
                            Log::info("Mencari Varian Lama dengan ID: {$variantId}");
                            $variant = ProductVariant::where('id', $variantId)
                                        ->where('tenant_id', $this->tenantId)
                                        ->where('product_id', $product->id)
                                        ->first();

                            if ($variant) {
                                Log::info("Varian Ditemukan. Melakukan Update...");
                                $variant->update([
                                    'name'    => $varData['name'],
                                    'price'   => $varData['price'],
                                    'stock'   => $varData['stock'] ?? 0,
                                    'sku'     => $varData['sku'] ?? $this->generateSku($request->name, $varData['name']),
                                    'barcode' => $varData['barcode'] ?? $this->generateNumericBarcode($request->category_id),
                                ]);
                            }
                        }

                        if (!$variant) {
                            Log::info("Varian Baru. Melakukan Insert...");
                            $variant = ProductVariant::create([
                                'tenant_id'  => $this->tenantId,
                                'product_id' => $product->id,
                                'name'       => $varData['name'],
                                'price'      => $varData['price'],
                                'stock'      => $varData['stock'] ?? 0,
                                'sku'        => $varData['sku'] ?? $this->generateSku($request->name, $varData['name']),
                                'barcode'    => $varData['barcode'] ?? $this->generateNumericBarcode($request->category_id),
                            ]);
                        }

                        $keepVariantIds[] = $variant->id;
                        $variantStock = 0;
                        $keepSubVariantIds = [];

                        if (isset($varData['sub_variants']) && is_array($varData['sub_variants'])) {
                            Log::info("--> Memproses Sub Varian untuk Varian ID: {$variant->id}");

                            foreach ($varData['sub_variants'] as $subIndex => $subData) {
                                Log::info("----> Index Sub {$subIndex} | Nama: {$subData['name']}");

                                $subVariantId = !empty($subData['id']) ? $subData['id'] : null;
                                $subVariant = null;

                                if ($subVariantId) {
                                    $subVariant = ProductSubVariant::where('id', $subVariantId)
                                                    ->where('tenant_id', $this->tenantId)
                                                    ->where('product_variant_id', $variant->id)
                                                    ->first();

                                    if ($subVariant) {
                                        Log::info("----> Update Sub Varian ID: {$subVariantId}");
                                        $subVariant->update([
                                            'name'    => $subData['name'],
                                            'price'   => $subData['price'],
                                            'discount_type'  => $subData['discount_type'] ?? 'percent',
                                            'discount_value' => $subData['discount_value'] ?? 0,
                                            'stock'   => $subData['stock'] ?? 0,
                                            'sku'     => $subData['sku'] ?? $this->generateSku($variant->name, $subData['name']),
                                            'barcode' => $subData['barcode'] ?? $this->generateNumericBarcode($request->category_id),
                                            'weight'  => $subData['weight'] ?? 0,
                                        ]);
                                    }
                                }

                                if (!$subVariant) {
                                    Log::info("----> Insert Sub Varian Baru: {$subData['name']}");
                                    $subVariant = ProductSubVariant::create([
                                        'tenant_id'          => $this->tenantId,
                                        'product_variant_id' => $variant->id,
                                        'name'               => $subData['name'],
                                        'price'              => $subData['price'],
                                        'discount_type'      => $subData['discount_type'] ?? 'percent',
                                        'discount_value'     => $subData['discount_value'] ?? 0,
                                        'stock'              => $subData['stock'] ?? 0,
                                        'sku'                => $subData['sku'] ?? $this->generateSku($variant->name, $subData['name']),
                                        'barcode'            => $subData['barcode'] ?? $this->generateNumericBarcode($request->category_id),
                                        'weight'             => $subData['weight'] ?? 0,
                                    ]);
                                }

                                $keepSubVariantIds[] = $subVariant->id;
                                $variantStock += ($subData['stock'] ?? 0);
                            }

                            Log::info("--> Menghapus Sub Varian Yatim Piatu (jika ada)...");
                            ProductSubVariant::where('product_variant_id', $variant->id)
                                ->whereNotIn('id', $keepSubVariantIds)
                                ->delete();

                            Log::info("--> Update total stok Varian menjadi: {$variantStock}");
                            $variant->update(['stock' => $variantStock]);
                        } else {
                            Log::info("--> Tidak ada Sub Varian. Menghapus semua Sub Varian lama milik Varian ID: {$variant->id}");
                            ProductSubVariant::where('product_variant_id', $variant->id)->delete();
                            $variantStock = $varData['stock'] ?? 0;
                        }

                        $totalProductStock += $variantStock;
                    }
                }

                Log::info("Menghapus Varian Utama Yatim Piatu (jika ada)...");
                $variantsToDelete = ProductVariant::where('product_id', $product->id)
                    ->whereNotIn('id', $keepVariantIds)
                    ->get();

                foreach ($variantsToDelete as $vdel) {
                    ProductSubVariant::where('product_variant_id', $vdel->id)->delete();
                    $vdel->delete();
                }

                Log::info("Update Stok Total Produk Gabungan Varian: {$totalProductStock}");
                $product->update([
                    'stock' => $totalProductStock,
                    'stock_status' => $totalProductStock > 0 ? 'available' : 'out_of_stock'
                ]);

            } else {
                Log::info("Mode Single Diaktifkan. Menghapus semua relasi Varian & Sub Varian...");
                $existingVariantIds = ProductVariant::where('product_id', $product->id)->pluck('id');
                ProductSubVariant::whereIn('product_variant_id', $existingVariantIds)->delete();
                ProductVariant::where('product_id', $product->id)->where('tenant_id', $this->tenantId)->delete();
            }

            Log::info("DB Commit Berhasil. Menyelesaikan Request.");
            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Produk berhasil diperbarui!']);
            }
            return redirect()->route('products.index', $subdomain)->with('success', 'Produk berhasil diperbarui!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("!!! [CRITICAL ERROR SAAT UPDATE PRODUCT] !!!");
            Log::error("Pesan: " . $e->getMessage());
            Log::error("File: " . $e->getFile() . " | Baris: " . $e->getLine());

            // Mengirim detail error ke tab Console F12 secara langsung
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'errors' => ['server' => ["Terjadi Kesalahan Server"]],
                    'debug_message' => $e->getMessage(),
                    'debug_file' => $e->getFile(),
                    'debug_line' => $e->getLine()
                ], 500);
            }
            return back()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }

    public function destroy($subdomain, $id)
    {
        $product = Product::where('tenant_id', $this->tenantId)->findOrFail($id);
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
        $product = Product::where('tenant_id', $this->tenantId)->findOrFail($id);
        $product->load('variants.subVariants');

        $categories = Category::where('tenant_id', $this->tenantId)->where('is_active', true)->orderBy('name', 'asc')->get();
        return view('products.edit', compact('product', 'categories'));
    }

    public function show($subdomain, $id)
    {
        $product = Product::where('tenant_id', $this->tenantId)->findOrFail($id);

        if (request()->wantsJson() || request()->is('api/*')) {
            $product->load(['variants.subVariants', 'category']);
            return response()->json($product);
        }

        $product->load(['variants.subVariants', 'category']);
        return view('products.show', compact('product'));
    }

    public function getVariants($arg1, $arg2 = null)
    {
        $id = is_numeric($arg1) ? $arg1 : $arg2;

        if (!$id) return response()->json(['error' => 'ID Produk invalid'], 400);

        $product = Product::where('tenant_id', $this->tenantId)->where('id', $id)->first();

        if (!$product) {
            return response()->json(['error' => 'Produk tidak ditemukan.'], 404);
        }

        return response()->json([
            'product_name' => $product->name,
            'variants'     => $product->variants()->with('subVariants')->where('tenant_id', $this->tenantId)->get()
        ]);
    }

    public function updateVariants(Request $request, $arg1, $arg2 = null)
    {
        $id = is_numeric($arg1) ? $arg1 : $arg2;
        $product = Product::where('tenant_id', $this->tenantId)->findOrFail($id);

        DB::beginTransaction();
        try {
            $parentType = $product->type ?: 'physical';

            ProductVariant::where('product_id', $product->id)->where('tenant_id', $this->tenantId)->delete();

            $totalStock = 0;
            if ($request->filled('variants')) {
                foreach ($request->variants as $varData) {
                    $variant = ProductVariant::create([
                        'tenant_id'  => $this->tenantId,
                        'product_id' => $product->id,
                        'name'       => $varData['name'],
                        'price'      => $varData['price'],
                        'discount_type'  => $varData['discount_type'] ?? 'percent',
                        'discount_value' => $varData['discount_value'] ?? 0,
                        'stock'      => $varData['stock'] ?? 0,
                        'sku'        => $varData['sku'] ?? $this->generateSku($product->name, $varData['name']),
                        'barcode'    => $varData['barcode'] ?? $this->generateSmartBarcode($parentType),
                    ]);

                    $variantStock = 0;
                    if (isset($varData['sub_variants']) && is_array($varData['sub_variants'])) {
                        foreach ($varData['sub_variants'] as $subData) {
                            ProductSubVariant::create([
                                'tenant_id'          => $this->tenantId,
                                'product_variant_id' => $variant->id,
                                'name'               => $subData['name'],
                                'price'              => $subData['price'],
                                'stock'              => $subData['stock'] ?? 0,
                                'sku'                => $subData['sku'] ?? $this->generateSku($variant->name, $subData['name']),
                                'barcode'            => $subData['barcode'] ?? $this->generateSmartBarcode($parentType),
                            ]);
                            $variantStock += ($subData['stock'] ?? 0);
                        }
                        $variant->update(['stock' => $variantStock]);
                    } else {
                        $variantStock = $varData['stock'] ?? 0;
                    }
                    $totalStock += $variantStock;
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
                        ->with(['category', 'variants.subVariants'])
                        ->orderBy('name', 'asc');

        $categoryName = 'Semua Kategori';
        if ($request->has('category_id') && $request->category_id != 'all') {
            $query->where('category_id', $request->category_id);
            $cat = Category::where('tenant_id', $this->tenantId)->find($request->category_id);
            if ($cat) $categoryName = $cat->name;
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
            $finalBarcode = substr($prefix . $randomString, 0, 13);

            $exists = DB::table('products')->where('tenant_id', $this->tenantId)->where('barcode', $finalBarcode)->exists()
                   || DB::table('product_variants')->where('tenant_id', $this->tenantId)->where('barcode', $finalBarcode)->exists()
                   || DB::table('product_sub_variants')->where('tenant_id', $this->tenantId)->where('barcode', $finalBarcode)->exists();

        } while ($exists);

        return $finalBarcode;
    }

    public function scanProduct(Request $request)
    {
        $keyword = $request->code;
        if (!$keyword) return response()->json(['status' => 'error', 'message' => 'Kode kosong'], 400);

        // Cari Sub Varian dan pastikan relasinya diload
        $subVariant = ProductSubVariant::with(['variant.product'])
            ->where('tenant_id', $this->tenantId)
            ->where(function($q) use ($keyword) {
                $q->where('barcode', $keyword)->orWhere('sku', $keyword);
            })->first();

        if ($subVariant && $subVariant->variant && $subVariant->variant->product) {
            $parentProduct = $subVariant->variant->product;
            $parentVariant = $subVariant->variant;
            return response()->json([
                'status' => 'success', 'type' => 'sub_variant',
                'data' => [
                    'id' => $parentProduct->id,
                    'variant_id' => $parentVariant->id,
                    'sub_variant_id' => $subVariant->id,
                    'name' => $parentProduct->name . ' - ' . $parentVariant->name . ' (' . $subVariant->name . ')',
                    'price' => $subVariant->price,
                    'stock' => $subVariant->stock,
                    'image' => $subVariant->image ?? ($parentProduct->image),
                    'weight' => $subVariant->weight ?? 0
                ]
            ]);
        }

        // Cari Varian
        $variant = ProductVariant::with('product')
            ->where('tenant_id', $this->tenantId)
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

        // Cari Produk Utama
        $product = Product::where('tenant_id', $this->tenantId)
            ->where(function($q) use ($keyword) {
                $q->where('barcode', $keyword)->orWhere('sku', $keyword);
            })->first();

        if ($product) {
            if ($product->has_variant) {
                $product->load('variants.subVariants');
                return response()->json(['status' => 'success', 'type' => 'choose_variant', 'data' => $product]);
            }
            return response()->json([
                'status' => 'success', 'type' => 'single',
                'data' => [
                    'id' => $product->id, 'variant_id' => null,
                    'name' => $product->name, 'price' => $product->sell_price,
                    'stock' => $product->stock, 'image' => $product->image,
                    'weight' => $product->weight
                ]
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'Produk tidak ditemukan'], 404);
    }

    public function apiList() {
        $products = Product::withoutGlobalScopes()
                        ->where('tenant_id', $this->tenantId)
                        ->with(['category', 'variants.subVariants']) // Relasi lengkap di-load
                        ->where('stock_status', 'available')
                        ->latest()
                        ->get()
                        ->map(function($product) {
                            return [
                                'id'             => $product->id,
                                'tenant_id'      => $product->tenant_id,
                                'category_id'    => $product->category_id,
                                'name'           => $product->name,
                                'description'    => $product->description,
                                'price'          => $product->sell_price,
                                'sell_price'     => $product->sell_price,
                                'base_price'     => $product->base_price,
                                'discount_type'  => $product->discount_type,
                                'discount_value' => $product->discount_value,
                                'stock'          => $product->stock,
                                'unit'           => $product->unit,
                                'weight'         => $product->weight,
                                'barcode'        => $product->barcode,
                                'sku'            => $product->sku,
                                'image'          => $product->image,
                                'category_name'  => $product->category ? $product->category->name : '-',
                                'has_variant'    => ($product->variants && $product->variants->count() > 0) ? true : ($product->has_variant == 1),
                                'is_best_seller' => $product->is_best_seller,
                                'is_terlaris'    => $product->is_terlaris,
                                'is_new_arrival' => $product->is_new_arrival,
                                'is_flash_sale'  => $product->is_flash_sale,
                                // HANYA PANGGIL SEKALI DI SINI
                                'variants_data'  => $product->variants,
                                'is_free_ongkir' => $product->is_free_ongkir,
                                'is_cashback_extra' => $product->is_cashback_extra,
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

            $exists = DB::table('products')->where('tenant_id', $this->tenantId)->where('barcode', $finalBarcode)->exists()
                   || DB::table('product_variants')->where('tenant_id', $this->tenantId)->where('barcode', $finalBarcode)->exists()
                   || DB::table('product_sub_variants')->where('tenant_id', $this->tenantId)->where('barcode', $finalBarcode)->exists();

        } while ($exists);

        return $finalBarcode;
    }
}
