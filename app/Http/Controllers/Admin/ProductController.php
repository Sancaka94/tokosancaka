<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\ProductAttribute;
use App\Models\ProductVariantType;
use App\Models\ProductVariantOption;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\ProductImage; // <--- JANGAN LUPA INI

class ProductController extends Controller
{
    /**
     * Menampilkan halaman manajemen produk.
     */
    /**
     * Menampilkan halaman manajemen produk.
     */
    public function index(Request $request)
    {
        // 1. Ambil Kategori untuk filter
        $categories = Category::where('type', 'product')->orderBy('name')->get(['name', 'slug']);
        
        // 2. Mulai Query Produk
        $query = Product::with('category');

        // 3. Logika Pencarian (Search)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // 4. Logika Filter Kategori
        if ($request->filled('category')) {
            $categorySlug = $request->category;
            $query->whereHas('category', function($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        // 5. Eksekusi Pagination (PENTING: Ini yang hilang sebelumnya)
        $products = $query->latest()->paginate(10);

        // 6. Kirim variabel $products ke View
        return view('admin.products.index', compact('categories', 'products'));
    }

    /**
     * Menampilkan form create.
     */
    public function create()
    {
        $categories = Category::where('type', 'product')->orderBy('name')->get();
        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->normalizeInput($request);
        $validatedData = $this->validateProduct($request);

        try {
            DB::beginTransaction();

            // 1. Handle File Upload (Logo Penjual & Image URL Default)
            $validatedData['seller_logo'] = $this->handleUpload($request, 'seller_logo', 'seller_logos');
            
            // Note: Kita handle product_image nanti via handleMultiImageUpload, tapi kita set default null dulu
            $validatedData['image_url'] = null; 

            // 2. Siapkan Data
            $this->prepareAdditionalData($validatedData, $request);

            // 3. Logic Varian
            $hasVariants = $request->has('variant_types') && !empty($request->variant_types);
            $variantTypesInput = $request->input('variant_types', []);
            $productVariantsInput = $request->input('product_variants', []);

            $productData = collect($validatedData)->except(['attributes', 'variant_types', 'product_variants'])->toArray();

            if ($hasVariants) {
                $productData['stock'] = 0;
                $productData['price'] = $productVariantsInput[0]['price'] ?? $productData['price'];
            }

            // 4. Create Product
            $product = Product::create($productData);

            // 5. Sync Attributes & Variants
            $this->syncAttributes($product, $request->input('attributes', []));
            if ($hasVariants) {
                $this->syncVariantTypesAndCombinations($product, $variantTypesInput, $productVariantsInput);
            }

            // 6. SIMPAN GAMBAR KE TABEL BARU (INI YANG SEBELUMNYA HILANG)
            $this->handleMultiImageUpload($request, $product);

            DB::commit();
            return redirect()->route('admin.products.index')->with('success', 'Produk berhasil ditambahkan.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Store Product Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Menampilkan form edit.
     */
    public function edit(Product $product)
    {
        $product->load([
            'images', // <--- TAMBAHKAN INI (PENTING!)
            'category', 
            'productAttributes', 
            'productVariantTypes.options' => fn($q) => $q->orderBy('id'),
            'productVariants.options' => fn($q) => $q->orderBy('product_variant_type_id')->orderBy('id')
        ]);

        // Decode JSON Tags
        $product->tags = $this->decodeTagsToString($product->tags);

        // Siapkan Data Atribut untuk JS
        $existingAttributesData = [];
        foreach($product->productAttributes as $pa) {
            $key = $pa->attribute_slug ?: Str::slug($pa->name);
            $val = $pa->value;
            // Cek jika JSON
            if (is_string($val) && str_starts_with($val, '[')) {
                $decoded = json_decode($val, true);
                if (json_last_error() === JSON_ERROR_NONE) $val = $decoded;
            }
            $existingAttributesData[$key] = $val;
        }
        $product->existing_attributes_json = json_encode($existingAttributesData);

        // Siapkan Data Varian untuk JS
        $product->existing_variant_types_json = $product->productVariantTypes->map(function($vt) {
            return ['name' => $vt->name, 'options' => $vt->options->pluck('name')->implode(',')];
        })->toJson();

        $product->existing_variant_combinations_json = $product->productVariants->mapWithKeys(function($v) {
            // Key format: "Warna:Merah;Ukuran:XL"
            $key = $v->options->map(fn($o) => ($o->productVariantType->name ?? 'U') . ':' . $o->name)->sort()->implode(';');
            return [$key => ['price' => $v->price, 'stock' => $v->stock, 'sku_code' => $v->sku_code]];
        })->toJson();

        $categories = Category::where('type', 'product')->orderBy('name')->get();
        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $this->normalizeInput($request);
        $validatedData = $this->validateProduct($request, $product->id);

        try {
            DB::beginTransaction();

            // 1. Update Logo Toko (jika ada)
            if ($request->hasFile('seller_logo')) {
                if ($product->seller_logo) Storage::disk('public')->delete($product->seller_logo);
                $validatedData['seller_logo'] = $this->handleUpload($request, 'seller_logo', 'seller_logos');
            }

            // 2. Siapkan Data
            $this->prepareAdditionalData($validatedData, $request, $product);

            // 3. Logic Varian
            $hasVariants = $request->has('variant_types') && !empty($request->variant_types);
            $variantTypesInput = $request->input('variant_types', []);
            $productVariantsInput = $request->input('product_variants', []);

            $productData = collect($validatedData)->except(['attributes', 'variant_types', 'product_variants'])->toArray();

            if ($hasVariants) {
                $productData['stock'] = 0;
                if(isset($productVariantsInput[0]['price'])) {
                    $productData['price'] = $productVariantsInput[0]['price'];
                }
            }

            // 4. Update Product Utama
            $product->update($productData);

            // 5. Sync Variants
            if ($hasVariants) {
                $this->syncVariantTypesAndCombinations($product, $variantTypesInput, $productVariantsInput);
            } else {
                $product->productVariants()->each(fn($v) => $v->options()->detach());
                $product->productVariants()->delete();
                $product->productVariantTypes()->delete();
            }

            // 6. SIMPAN GAMBAR KE TABEL BARU (INI YANG SEBELUMNYA HILANG)
            $this->handleMultiImageUpload($request, $product);

            DB::commit();
            return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diperbarui.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update Product Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal update: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Hapus produk.
     */
    public function destroy(Product $product)
    {
        try {
            DB::transaction(function() use ($product) {
                if ($product->image_url) Storage::disk('public')->delete($product->image_url);
                if ($product->seller_logo) Storage::disk('public')->delete($product->seller_logo);
                
                // Relasi akan terhapus via cascading DB atau manual
                $product->productAttributes()->delete();
                $product->productVariants()->each(fn($v) => $v->options()->detach());
                $product->productVariants()->delete();
                $product->productVariantTypes()->delete();
                
                $product->delete();
            });
            return redirect()->route('admin.products.index')->with('success', 'Produk dihapus.');
        } catch (Exception $e) {
            return redirect()->route('admin.products.index')->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }

    // --- FITUR KHUSUS: RESTOCK SIMPLE PRODUCT ---

    public function restock(Request $request, Product $product)
    {
        if ($product->productVariantTypes()->exists()) {
            return back()->with('error', 'Produk varian harus diedit melalui menu Edit.');
        }
        $request->validate(['stock' => 'required|integer|min:1']);
        $product->increment('stock', $request->stock);
        return back()->with('success', 'Stok berhasil ditambahkan.');
    }

    public function markAsOutOfStock(Product $product)
    {
        if ($product->productVariantTypes()->exists()) {
            return back()->with('error', 'Produk varian harus diedit melalui menu Edit.');
        }
        $product->update(['stock' => 0]);
        return back()->with('success', 'Stok diatur ke 0.');
    }

    // --- FITUR KHUSUS: EDIT SPESIFIKASI DINAMIS ---

    public function editSpecifications($idOrSlug)
    {
        $product = Product::where('id', $idOrSlug)->orWhere('slug', $idOrSlug)->firstOrFail();
        $categories = Category::where('type', 'product')->orderBy('name')->get();

        // Build existing attributes JSON
        $data = [];
        foreach($product->productAttributes as $pa) {
            $val = $pa->value;
            if(is_string($val) && str_starts_with($val, '[')) {
                $decoded = json_decode($val, true);
                if(json_last_error() === JSON_ERROR_NONE) $val = $decoded;
            }
            $data[$pa->attribute_slug ?: Str::slug($pa->name)] = $val;
        }

        return view('admin.products.edit-specifications', [
            'product' => $product,
            'categories' => $categories,
            'existingAttributesJson' => json_encode($data)
        ]);
    }

    public function updateSpecifications(Request $request, $idOrSlug)
    {
        $product = Product::where('id', $idOrSlug)->orWhere('slug', $idOrSlug)->firstOrFail();
        
        // Pastikan SKU ada
        if (!$request->filled('sku')) {
            $request->merge(['sku' => $this->generateSku($product->name, $request->input('category_id', $product->category_id))]);
        }

        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product->id)],
            'category_id' => 'required|exists:categories,id',
            'tags' => 'nullable|string',
            'attributes' => 'nullable|array'
        ]);

        try {
            DB::transaction(function() use ($product, $request, $validated) {
                $product->update([
                    'sku' => $validated['sku'],
                    'category_id' => $validated['category_id'],
                    'tags' => $this->processTagsToJson($request->tags, $validated['category_id'])
                ]);
                $this->syncAttributes($product, $request->input('attributes', []));
            });
            return redirect()->route('admin.products.edit.specifications', $product->slug)->with('success', 'Spesifikasi berhasil diperbarui.');
        } catch (Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }


    // =========================================================================
    //  PRIVATE HELPER METHODS (Untuk Mengurangi Duplikasi)
    // =========================================================================

    private function normalizeInput(Request $request)
    {
        // Hapus pemisah ribuan (titik) sebelum validasi
        $fields = ['price', 'original_price', 'weight'];
        $mergeData = [];
        foreach($fields as $field) {
            if ($request->has($field)) {
                $mergeData[$field] = str_replace('.', '', $request->input($field));
            }
        }
        if(!empty($mergeData)) $request->merge($mergeData);
    }

    private function validateProduct(Request $request, $ignoreId = null)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', Rule::unique('products', 'name')->ignore($ignoreId)],
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'weight' => 'required|integer|min:0',
            'stock' => 'nullable|integer|min:0', // Nullable karena bisa dihandle varian
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string',
            'original_price' => 'nullable|numeric|gte:price',
            'store_name' => 'nullable|string',
            'seller_city' => 'nullable|string',
            'seller_wa' => 'nullable|string',
            'sku' => ['nullable', 'string', Rule::unique('products', 'sku')->ignore($ignoreId)],
            // File rules
            'product_image' => 'nullable|image|max:2048',
            'seller_logo' => 'nullable|image|max:2048',
            // Arrays
            'attributes' => 'nullable|array',
            'variant_types' => 'nullable|array',
            'product_variants' => 'nullable|array',
            // Booleans
            'is_new' => 'nullable', 'is_bestseller' => 'nullable', 'is_promo' => 'nullable', 
            'is_shipping_discount' => 'nullable', 'is_free_shipping' => 'nullable'
        ];

        // Rules Kondisional Varian
        if ($request->has('variant_types') && !empty($request->variant_types)) {
            $rules['variant_types.*.name'] = 'required|string';
            $rules['variant_types.*.options'] = 'required|string';
            $rules['product_variants'] = 'required|array';
            $rules['product_variants.*.price'] = 'required|numeric';
            $rules['product_variants.*.stock'] = 'required|integer';
        } else {
            $rules['stock'] = 'required|integer|min:0';
        }

        return $request->validate($rules);
    }

    private function prepareAdditionalData(array &$data, Request $request, ?Product $product = null)
    {
        // 1. Slug
        if (!$product || $request->name !== $product->name) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $product ? $product->id : null);
        }

        // 2. SKU Auto Gen
        $hasVariants = $request->has('variant_types') && !empty($request->variant_types);
        if (empty($data['sku']) && (!$product || empty($product->sku)) && !$hasVariants) {
            $data['sku'] = $this->generateSku($data['name'], $data['category_id']);
        }

        // 3. Tags
        $data['tags'] = $this->processTagsToJson($request->tags, $data['category_id']);

        // 4. Seller Defaults
        $userStore = Auth::user()->store ?? null;
        if (empty($data['store_name'])) $data['store_name'] = $userStore->name ?? config('app.name');
        if (empty($data['seller_city'])) $data['seller_city'] = $userStore->city ?? 'Indonesia';
        
        // 5. WA Format
        if (!empty($data['seller_wa'])) {
            $wa = preg_replace('/[^0-9]/', '', $data['seller_wa']);
            if (!Str::startsWith($wa, '62')) $wa = '62' . ltrim($wa, '0');
            $data['seller_wa'] = $wa;
        }

        // 6. Booleans (Checkbox HTML tidak kirim value jika unchecked)
        $bools = ['is_new', 'is_bestseller', 'is_promo', 'is_shipping_discount', 'is_free_shipping'];
        foreach($bools as $b) {
            $data[$b] = $request->has($b);
        }
    }

    private function handleUpload(Request $request, $key, $folder)
    {
        if ($request->hasFile($key)) {
            return $request->file($key)->store($folder, 'public');
        }
        return null;
    }

    // --- LOGIC HELPER RELASI (TIDAK BERUBAH BANYAK) ---

    protected function syncAttributes(Product $product, array $attributesData)
    {
        if (empty($attributesData)) return;

        $processedSlugs = [];
        foreach ($attributesData as $slug => $value) {
            if ($value === null || $value === '') continue;
            
            // Format Value
            $dbValue = is_array($value) ? json_encode($value) : $value;
            $attrDef = Attribute::where('slug', $slug)->first();
            $prettyName = $attrDef ? $attrDef->name : ucwords(str_replace(['-', '_'], ' ', $slug));

            ProductAttribute::updateOrCreate(
                ['product_id' => $product->id, 'attribute_slug' => $slug],
                ['name' => $prettyName, 'value' => $dbValue]
            );
            $processedSlugs[] = $slug;
        }
        // Hapus yang tidak ada di form (jika perlu strict sync)
        // $product->productAttributes()->whereNotIn('attribute_slug', $processedSlugs)->delete();
    }

    protected function syncVariantTypesAndCombinations(Product $product, array $typesData, array $variantsData)
    {
        // 1. Sync Types & Options
        $typeIds = [];
        $optionMap = []; // "Warna:Merah" => option_id

        foreach ($typesData as $tData) {
            if (empty($tData['name']) || empty($tData['options'])) continue;
            
            $type = ProductVariantType::updateOrCreate(
                ['product_id' => $product->id, 'name' => trim($tData['name'])]
            );
            $typeIds[] = $type->id;

            $opts = array_map('trim', explode(',', $tData['options']));
            $optIds = [];
            foreach($opts as $optName) {
                if(!$optName) continue;
                $opt = ProductVariantOption::updateOrCreate(
                    ['product_variant_type_id' => $type->id, 'name' => $optName]
                );
                $optIds[] = $opt->id;
                $optionMap[$type->name . ':' . $optName] = $opt->id;
            }
            $type->options()->whereNotIn('id', $optIds)->delete();
        }
        $product->productVariantTypes()->whereNotIn('id', $typeIds)->delete();

        // 2. Sync Combinations
        $variantIds = [];
        foreach ($variantsData as $vData) {
            $optionsConfig = $vData['variant_options'] ?? [];
            if (empty($optionsConfig)) continue;

            // Sort agar kombinasi "Merah;L" sama dengan "L;Merah" (konsistensi)
            usort($optionsConfig, fn($a, $b) => strcmp($a['type_name'], $b['type_name']));

            $comboKeys = [];
            $comboOptIds = [];
            
            foreach ($optionsConfig as $oc) {
                $key = trim($oc['type_name']) . ':' . trim($oc['value']);
                if (isset($optionMap[$key])) {
                    $comboKeys[] = $key;
                    $comboOptIds[] = $optionMap[$key];
                }
            }

            if (count($comboKeys) !== count($optionsConfig)) continue; // Skip jika ada opsi tak valid

            $comboString = implode(';', $comboKeys);
            
            $variant = ProductVariant::updateOrCreate(
                ['product_id' => $product->id, 'combination_string' => $comboString],
                [
                    'price' => $vData['price'] ?? 0,
                    'stock' => $vData['stock'] ?? 0,
                    'sku_code' => $vData['sku_code'] ?? null
                ]
            );
            $variant->options()->sync($comboOptIds);
            $variantIds[] = $variant->id;
        }
        
        // Cleanup varian yatim piatu
        $product->productVariants()->whereNotIn('id', $variantIds)->each(function($v) {
            $v->options()->detach();
            $v->delete();
        });
    }

    protected function generateSku($name, $catId)
    {
        $cat = Category::find($catId);
        $cCode = $cat ? strtoupper(substr(Str::slug($cat->name, ''), 0, 3)) : 'GEN';
        $pCode = strtoupper(substr(Str::slug($name, ''), 0, 3));
        do {
            $sku = $cCode . '-' . $pCode . '-' . mt_rand(100, 999);
        } while (Product::where('sku', $sku)->exists());
        return $sku;
    }

    protected function generateUniqueSlug($name, $ignoreId = null)
    {
        $slug = Str::slug($name);
        $count = 1;
        while(Product::where('slug', $slug)->when($ignoreId, fn($q)=>$q->where('id','!=',$ignoreId))->exists()) {
            $slug = Str::slug($name) . '-' . $count++;
        }
        return $slug;
    }

    protected function processTagsToJson($input, $catId)
    {
        if (!$input) return null;
        $tags = array_map('trim', explode(',', $input));
        $cat = Category::find($catId);
        if ($cat && !in_array($cat->name, $tags)) $tags[] = $cat->name;
        return json_encode(array_unique(array_filter($tags)));
    }

    protected function decodeTagsToString($tags)
    {
        if (is_array($tags)) return implode(',', $tags);
        if (is_string($tags) && str_starts_with($tags, '[')) {
            $decoded = json_decode($tags, true);
            return is_array($decoded) ? implode(',', $decoded) : $tags;
        }
        return $tags;
    }

    /**
     * Helper khusus untuk menangani upload 5 gambar
     */
    private function handleMultiImageUpload(Request $request, Product $product)
    {
        // Loop cek index 0 sampai 4 (sesuai 5 kotak di view)
        for ($i = 0; $i < 5; $i++) {
            // Cek apakah ada file yang dikirim dari form dengan nama product_images[0], product_images[1], dst
            if ($request->hasFile("product_images.$i")) {
                
                // 1. Cek apakah sudah ada gambar lama di slot ini?
                $existingImage = ProductImage::where('product_id', $product->id)
                                             ->where('sort_order', $i)
                                             ->first();

                // 2. Jika ada, hapus file lamanya & recordnya dari DB biar bersih
                if ($existingImage) {
                    if (Storage::disk('public')->exists($existingImage->path)) {
                        Storage::disk('public')->delete($existingImage->path);
                    }
                    $existingImage->delete();
                }

                // 3. Upload File Baru
                $file = $request->file("product_images.$i");
                // Buat nama file unik: slug-urutan-waktu.jpg
                $filename = $product->slug . "-img-$i-" . time() . '.' . $file->getClientOriginalExtension();
                
                // Simpan ke folder uploads/product_media
                $path = $file->storeAs('uploads/product_media', $filename, 'public');

                // 4. Simpan ke Tabel product_images
                ProductImage::create([
                    'product_id' => $product->id,
                    'path'       => $path,
                    'sort_order' => $i,
                ]);

                // 5. (PENTING) Update juga cover utama di tabel products agar kompatibel dengan kode lama
                if ($i === 0) {
                    $product->update(['image_url' => $path]);
                }
            }
        }
    }
}