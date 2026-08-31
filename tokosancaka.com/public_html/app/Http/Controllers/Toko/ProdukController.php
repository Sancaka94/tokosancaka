<?php

namespace App\Http\Controllers\Toko;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\ProductAttribute;
use App\Models\Store;
// --- DITAMBAHKAN UNTUK VARIAN ---
use App\Models\ProductVariant;
use App\Models\ProductVariantType;
use App\Models\ProductVariantOption;
// ---------------------------------
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Exports\ProductsExport;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ProdukController extends Controller
{
    /**
     * Menampilkan daftar produk HANYA untuk toko yang sedang login.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $store = $user->store;

        if (!$store) {
            return redirect()->route('seller.dashboard')->with('info', 'Anda perlu membuat toko terlebih dahulu.');
        }

        $search = $request->input('search');

        $productsQuery = Product::where('store_id', $store->id)
                                ->with('category')
                                ->latest();

        if ($search) {
            $productsQuery->where(function($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                      ->orWhere('sku', 'like', '%' . $search . '%');
            });
        }

        $products = $productsQuery->paginate(10);
        $products->appends($request->query());

        return view('seller.produk.index', compact('products'));
    }

    /**
     * Menampilkan form untuk membuat produk baru.
     */
    public function create()
    {
        $categories = Category::whereIn('type', ['product', 'marketplace'])
        ->orderBy('name')
        ->get();

        $bidangs = DB::table('master_bidang')->where('status_aktif', 1)->orderBy('nama_bidang')->get();

        return view('seller.produk.create', compact('categories', 'bidangs'));
    }

    /**
     * Menyimpan produk baru ke database.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->back()->withInput()->with('error', 'Sesi Anda telah berakhir. Silakan login kembali.');
        }

        $store = $user->store;
        if (!$store) {
            return redirect()->back()->withInput()->with('error', 'Gagal membuat produk: Data toko Anda tidak ditemukan.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('products', 'name')->where('store_id', $store->id)],
            'category_id' => 'required_without:id_master_layanan|nullable|exists:categories,id',
            'weight' => 'required_without:id_master_layanan|nullable|integer|min:0',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'product_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status' => 'required|in:active,inactive',
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->where('store_id', $store->id)],
            'supporting_images' => 'nullable|array|max:5', 
            'supporting_images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048', 
            'original_price' => 'nullable|numeric|min:0|gt:price', 
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'tags' => 'nullable|string',
            'is_new' => 'nullable|boolean', 
            'is_bestseller' => 'nullable|boolean', 
            'attributes' => 'nullable|array',
            'variant_types' => 'nullable|array', 
            'variant_types.*.name' => 'required_with:variant_types|string|max:255', 
            'variant_types.*.options' => 'required_with:variant_types|string', 
            'product_variants' => 'nullable|array', 
            'digital_url' => 'nullable|url',
            'digital_file' => 'nullable|file|mimes:pdf,zip,jpg,png|max:5120',
            'digital_sn_list' => 'nullable|string',
            'id_master_layanan' => 'nullable|integer',
        ], [
            'original_price.gt' => 'Harga Asli (Coret) harus lebih besar dari Harga Jual.'
        ]);

        $dataToCreate = $validated;
        $imagePath = null;
        $hasVariantsRequest = $request->has('variant_types') && !empty($request->variant_types);

        DB::beginTransaction();
        try {
            // 1. Handle Upload Gambar Utama
            if ($request->hasFile('product_image')) {
                $imagePath = $request->file('product_image')->store('products', 'public');
                $dataToCreate['image_url'] = $imagePath;
            }

            // 2. Handle Upload Gambar Pendukung (Maks 5)
            $supportPaths = [];
            if ($request->hasFile('supporting_images')) {
                foreach ($request->file('supporting_images') as $file) {
                    $supportPaths[] = $file->store('products', 'public');
                }
            }
            $dataToCreate['supporting_images'] = json_encode($supportPaths);

            // 3. Handle Upload File Digital
            if ($request->hasFile('digital_file')) {
                $digitalPath = $request->file('digital_file')->store('digital_products', 'public');
                $dataToCreate['digital_file_path'] = $digitalPath;
            }
            $dataToCreate['digital_url'] = $request->digital_url;
            $dataToCreate['digital_sn_list'] = $request->digital_sn_list;
            unset($dataToCreate['digital_file']);

            // 4. Tambahkan Data Toko & User
            $dataToCreate['store_id'] = $store->id; 
            $dataToCreate['store_name'] = $store->name; 
            $dataToCreate['seller_city'] = $store->regency; 
            $dataToCreate['seller_name'] = $user->nama_lengkap; 
            $dataToCreate['seller_wa'] = $user->no_wa; 

            // 5. Generate Slug & SKU
            $dataToCreate['slug'] = $this->generateUniqueSlug($validated['name']);
            if (empty($validated['sku'])) {
                $dataToCreate['sku'] = $this->generateSku($validated['name'], $validated['category_id']);
            }

            // 6. Generate Tags & Kategori
            $category = Category::find($validated['category_id']);
            $manualTags = !empty($request->tags) ? array_map('trim', explode(',', $request->tags)) : [];
            if ($category) {
                $manualTags[] = $category->name;
                $dataToCreate['category'] = $category->name; 
            }
            $dataToCreate['tags'] = json_encode(array_values(array_unique($manualTags)));

            $dataToCreate['is_new'] = $request->has('is_new');
            $dataToCreate['is_bestseller'] = $request->has('is_bestseller');

            // 7. Handle Stok Varian
            if ($hasVariantsRequest) {
                $dataToCreate['stock'] = 0; 
            }

            // 8. Bypass Kategori & Berat jika ini Layanan Jasa
            if ($request->has('id_master_layanan') && $request->id_master_layanan != null) {
                $dataToCreate['id_master_layanan'] = $request->id_master_layanan;
                $dataToCreate['weight'] = 0; 
                $dataToCreate['category_id'] = null; 
            }

            // Hapus key array mentah yang tidak perlu masuk ke DB
            unset($dataToCreate['product_image']);
            unset($dataToCreate['attributes']);
            unset($dataToCreate['variant_types']);
            unset($dataToCreate['product_variants']);

            // 9. Simpan ke Database
            $product = Product::create($dataToCreate); 

            // 10. Sinkronisasi Relasi Atribut & Varian
            if ($request->has('attributes')) {
                $this->syncAttributes($product, $request->input('attributes', []));
            }
            if ($hasVariantsRequest) {
               $this->syncVariantTypes($product, $request->input('variant_types', []));
            }

            DB::commit();
            return redirect()->route('seller.produk.index')->with('success', 'Produk berhasil ditambahkan.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Gagal simpan produk seller: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            return redirect()->back()
                             ->withInput()
                             ->with('error', 'Gagal menambahkan produk: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan form untuk mengedit produk.
     */
    public function edit($slug)
    {
        $user = Auth::user();
        if (!$user || !$user->store) { abort(403, 'Anda harus login dan memiliki toko'); }
        $storeId = $user->store->id; 

        $categories = Category::whereIn('type', ['product', 'marketplace'])->orderBy('name')->get();
        $bidangs = DB::table('master_bidang')->where('status_aktif', 1)->orderBy('nama_bidang')->get();

        $produk = Product::where('slug', $slug)
                            ->where('store_id', $storeId) 
                            ->with([
                                'category',
                                'productAttributes', 
                                'productVariantTypes.options', 
                                'productVariants.options' 
                            ])
                            ->firstOrFail();

        $tagsArray = json_decode($produk->tags, true);
        $produk->tags = is_array($tagsArray) ? implode(', ', $tagsArray) : ($produk->tags ?? '');

        $attributeDefinitions = Attribute::where('category_id', $produk->category_id)->get()->keyBy('name'); 

        $existingAttributesData = [];
        foreach($produk->productAttributes as $pa) {
            $slugAttr = Str::slug($pa->name); 
            $value = $pa->value;
            $def = $attributeDefinitions->get($pa->name);
            $type = $def->type ?? 'text';

            if ($type === 'checkbox' && is_string($value)) {
                $value = json_decode($value, true) ?? [$value];
            }
            $existingAttributesData[$slugAttr] = $value;
        }
        $existing_attributes_json = json_encode($existingAttributesData);

        $existing_variant_types_json = $produk->productVariantTypes->map(function($variantType) {
            return [
                'name' => $variantType->name,
                'options' => $variantType->options->pluck('name')->implode(', ')
            ];
        })->toJson();

        $existing_variants_json = $produk->productVariants->map(function($variant) {
            return [
                'id' => $variant->id,
                'sku' => $variant->sku_code,
                'price' => $variant->price,
                'stock' => $variant->stock,
                'combination' => $variant->options->mapWithKeys(fn($opt) => [$opt->variantType->name => $opt->name])
            ];
        })->toJson();

        return view('seller.produk.edit', compact(
            'produk',
            'categories',
            'bidangs',
            'existing_attributes_json',
            'existing_variant_types_json',
            'existing_variants_json'
        ));
    }

    /**
     * Mengupdate produk di database.
     */
    public function update(Request $request, $slug)
    {
        $user = Auth::user();
        if (!$user || !$user->store) { abort(403); }
        $storeId = $user->store->id; 

        $product = Product::where('slug', $slug)
                            ->where('store_id', $storeId) 
                            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('products', 'name')->where('store_id', $storeId)->ignore($product->id)],
            'category_id' => 'required_without:id_master_layanan|nullable|exists:categories,id',
            'weight' => 'required_without:id_master_layanan|nullable|integer|min:0',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'product_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status' => 'required|in:active,inactive',
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->where('store_id', $storeId)->ignore($product->id)],
            'attributes' => 'nullable|array',
            'supporting_images' => 'nullable|array|max:5', 
            'supporting_images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048', 
            'variant_types' => 'nullable|array',
            'variant_types.*.name' => 'required_with:variant_types|string|max:255',
            'variant_types.*.options' => 'required_with:variant_types|string',
            'product_variants' => 'nullable|array', 
            'product_variants.*.sku' => ['nullable', 'string', 'max:100'], 
            'product_variants.*.price' => 'required_with:product_variants|numeric|min:0',
            'product_variants.*.stock' => 'required_with:product_variants|integer|min:0',
            'original_price' => 'nullable|numeric|min:0|gt:price',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'tags' => 'nullable|string',
            'is_new' => 'nullable|boolean',
            'is_bestseller' => 'nullable|boolean',
            'digital_url' => 'nullable|url',
            'digital_file' => 'nullable|file|mimes:pdf,zip,jpg,png|max:5120',
            'digital_sn_list' => 'nullable|string',
            'id_master_layanan' => 'nullable|integer',
        ], [
            'original_price.gt' => 'Harga Asli (Coret) harus lebih besar dari Harga Jual.'
        ]);

        $dataToUpdate = $validated;
        $hasVariantsRequest = $request->has('variant_types') && !empty($request->variant_types);

        DB::beginTransaction();
        try {
            // 1. Handle Update Gambar Utama
            if ($request->hasFile('product_image')) {
                if ($product->image_url && Storage::disk('public')->exists($product->image_url)) {
                    Storage::disk('public')->delete($product->image_url);
                }
                $imagePath = $request->file('product_image')->store('products', 'public');
                $dataToUpdate['image_url'] = $imagePath;
            }

            // 2. Handle Update Gambar Pendukung
            if ($request->hasFile('supporting_images')) {
                // Hapus gambar lama dari storage
                if ($product->supporting_images) {
                    $oldImages = json_decode($product->supporting_images, true);
                    if (is_array($oldImages)) {
                        foreach ($oldImages as $oldImg) {
                            if (Storage::disk('public')->exists($oldImg)) {
                                Storage::disk('public')->delete($oldImg);
                            }
                        }
                    }
                }
                // Upload yang baru
                $supportPaths = [];
                foreach ($request->file('supporting_images') as $file) {
                    $supportPaths[] = $file->store('products', 'public');
                }
                $dataToUpdate['supporting_images'] = json_encode($supportPaths);
            } else {
                unset($dataToUpdate['supporting_images']); // Biarkan data lama di DB utuh jika tidak ada upload baru
            }

            // 3. Handle Update File Digital
            if ($request->hasFile('digital_file')) {
                if ($product->digital_file_path && Storage::disk('public')->exists($product->digital_file_path)) {
                    Storage::disk('public')->delete($product->digital_file_path);
                }
                $digitalPath = $request->file('digital_file')->store('digital_products', 'public');
                $dataToUpdate['digital_file_path'] = $digitalPath;
            }
            
            if ($request->has('digital_url')) {
                $dataToUpdate['digital_url'] = $request->digital_url;
            }
            if ($request->has('digital_sn_list')) {
                $dataToUpdate['digital_sn_list'] = $request->digital_sn_list;
            }
            unset($dataToUpdate['digital_file']);

            if ($request->name !== $product->name) {
                $dataToUpdate['slug'] = $this->generateUniqueSlug($request->name, $product->id);
            }

            $category = Category::find($validated['category_id']);
            $manualTags = !empty($request->tags) ? array_map('trim', explode(',', $request->tags)) : [];
            if ($category) {
                $manualTags[] = $category->name;
                $dataToUpdate['category'] = $category->name; 
            }
            $dataToUpdate['tags'] = json_encode(array_values(array_unique($manualTags)));

            $dataToUpdate['is_new'] = $request->has('is_new');
            $dataToUpdate['is_bestseller'] = $request->has('is_bestseller');

            if ($hasVariantsRequest) {
                 $dataToUpdate['stock'] = 0; 
            }

            // Bypass untuk Kategori Jasa
            if ($request->has('id_master_layanan') && $request->id_master_layanan != null) {
                $dataToUpdate['id_master_layanan'] = $request->id_master_layanan;
                $dataToUpdate['weight'] = 0; 
                $dataToUpdate['category_id'] = null; 
            }

            unset($dataToUpdate['product_image']);
            unset($dataToUpdate['attributes']);
            unset($dataToUpdate['variant_types']);
            unset($dataToUpdate['product_variants']); 

            // Update Tabel Produk
            $product->update($dataToUpdate);

            // Sinkronisasi Atribut dan Varian
            $this->syncAttributes($product, $request->input('attributes', []));
            $totalStock = $validated['stock']; 

            if ($hasVariantsRequest) {
                 $totalStock = $this->syncVariantTypesAndCombinations(
                     $product,
                     $request->input('variant_types', []),
                     $request->input('product_variants', [])
                 );
            } else {
                 $product->productVariants()->delete();
                 $product->productVariantTypes()->delete();
            }

            $product->stock = $totalStock;
            $product->save();

            DB::commit();
            return redirect()->route('seller.produk.index')->with('success', 'Produk berhasil diperbarui.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Gagal update produk seller: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui produk: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus produk dari database.
     */
    public function destroy($slug)
    {
        $userStore = Auth::user()->store;
        if (!$userStore) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus produk ini.');
        }

        $product = Product::where('slug', $slug)
                            ->where('store_id', $userStore->id) 
                            ->firstOrFail();

        DB::beginTransaction();
        try {
            // Hapus gambar utama
            if ($product->image_url && Storage::disk('public')->exists($product->image_url)) {
                Storage::disk('public')->delete($product->image_url);
            }

            // Hapus gambar pendukung
            if ($product->supporting_images) {
                $oldImages = json_decode($product->supporting_images, true);
                if (is_array($oldImages)) {
                    foreach ($oldImages as $oldImg) {
                        if (Storage::disk('public')->exists($oldImg)) {
                            Storage::disk('public')->delete($oldImg);
                        }
                    }
                }
            }

            $product->productAttributes()->delete();
            $product->productVariants()->delete();
            $product->productVariantTypes()->delete();

            $product->delete();

            DB::commit();
            return redirect()->route('seller.produk.index')->with('success', 'Produk berhasil dihapus.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Gagal hapus produk seller: ' . $e->getMessage());
            return redirect()->route('seller.produk.index')->with('error', 'Gagal menghapus produk. Silakan coba lagi.');
        }
    }

    // --- Helper Methods ---

    protected function generateUniqueSlug(string $name, int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        $query = Product::where('slug', $slug);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if (Auth::check() && Auth::user()->store) {
             $query->where('store_id', Auth::user()->store->id);
        }

        while ($query->exists()) {
            $slug = $originalSlug . '-' . $count++;
            $query = Product::where('slug', $slug);
            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }
            if (Auth::check() && Auth::user()->store) {
                 $query->where('store_id', Auth::user()->store->id);
            }
        }
        return $slug;
    }

    protected function generateSku(string $productName, ?int $categoryId = null): string
    {
        $category = $categoryId ? Category::find($categoryId) : null;
        $categoryInitial = $category ? strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $category->name), 0, 3)) : 'JAS';
        $productInitial = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $productName), 0, 3));
        $randomNum = mt_rand(100, 999);
        $sku = "{$categoryInitial}-{$productInitial}-{$randomNum}";

        $query = Product::where('sku', $sku);
        if (Auth::check() && Auth::user()->store) {
             $query->where('store_id', Auth::user()->store->id);
        }

        while ($query->exists()) {
            $randomNum = mt_rand(100, 999);
            $sku = "{$categoryInitial}-{$productInitial}-{$randomNum}";
            $query = Product::where('sku', $sku);
            if (Auth::check() && Auth::user()->store) {
                 $query->where('store_id', Auth::user()->store->id);
            }
        }
        return $sku;
    }

    protected function syncAttributes(Product $product, ?array $attributesData)
    {
        if ($attributesData === null) {
            $product->productAttributes()->delete();
            return;
        }

        $currentAttributeNames = [];

        $validAttributesInfo = Attribute::where('category_id', $product->category_id)
                                           ->whereIn('slug', array_keys($attributesData))
                                           ->get()
                                           ->keyBy('slug'); 

        foreach ($attributesData as $slug => $value) {
            $attributeInfo = $validAttributesInfo->get($slug);
            $attributeName = $attributeInfo->name ?? str_replace('-', ' ', Str::title($slug));

            if (!empty($attributeName) && ($value !== null && $value !== '' && (!is_array($value) || !empty(array_filter($value)))))
            {
                $processedValue = is_array($value) ? json_encode(array_values(array_filter($value))) : $value;

                ProductAttribute::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'name' => $attributeName, 
                    ],
                    [
                        'value' => $processedValue,
                    ]
                );
                $currentAttributeNames[] = $attributeName; 
            }
        }

        $product->productAttributes()->whereNotIn('name', $currentAttributeNames)->delete();
    }

    protected function syncVariantTypes(Product $product, array $variantTypesData)
    {
        $currentTypeIds = [];
        $syncedTypes = collect();

        foreach ($variantTypesData as $typeData) {
            if (empty($typeData['name']) || empty($typeData['options'])) continue;

            $variantType = ProductVariantType::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'name' => $typeData['name'],
                ]
            );
            $currentTypeIds[] = $variantType->id;

            $optionNames = array_map('trim', explode(',', $typeData['options']));
            $currentOptionIds = [];
            foreach ($optionNames as $optionName) {
                if(empty($optionName)) continue;
                $option = ProductVariantOption::updateOrCreate(
                    [
                        'product_variant_type_id' => $variantType->id,
                        'name' => $optionName,
                    ]
                );
                $currentOptionIds[] = $option->id;
            }
            $variantType->options()->whereNotIn('id', $currentOptionIds)->delete();

            $syncedTypes->push($variantType->load('options'));
        }
        $product->productVariantTypes()->whereNotIn('id', $currentTypeIds)->delete();

        return $syncedTypes;
    }

    protected function syncVariantTypesAndCombinations(Product $product, array $variantTypesData, ?array $productVariantsData)
    {
        $syncedTypes = $this->syncVariantTypes($product, $variantTypesData);
        $optionNameMap = $syncedTypes->flatMap(fn($type) => $type->options)->pluck('id', 'name');

        $currentVariantIds = [];
        $totalStock = 0;

        if (empty($productVariantsData)) {
            $product->productVariants()->delete();
            return 0;
        }

        foreach ($productVariantsData as $variantData) {

            $sku = $variantData['sku'] ?? null;
            $price = $variantData['price'] ?? 0;
            $stock = $variantData['stock'] ?? 0;
            $optionsInCombination = $variantData['options'] ?? []; 

            if (empty($optionsInCombination)) continue;

            $variant = null;
            if (!empty($sku)) {
                $variant = ProductVariant::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'sku_code' => $sku,
                    ],
                    [
                        'price' => $price,
                        'stock' => $stock,
                        'combination_string' => implode(';', $optionsInCombination) 
                    ]
                );
            } else {
                $combinationString = implode(';', $optionsInCombination);
                $variant = ProductVariant::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'combination_string' => $combinationString,
                    ],
                    [
                        'price' => $price,
                        'stock' => $stock,
                        'sku_code' => $sku 
                    ]
                );
            }

            $optionIdsToSync = [];
            foreach ($optionsInCombination as $typeName => $optionName) {
                if (isset($optionNameMap[$optionName])) {
                    $optionIdsToSync[] = $optionNameMap[$optionName];
                }
            }

            if (!empty($optionIdsToSync)) {
                $variant->options()->sync($optionIdsToSync);
            }

            $currentVariantIds[] = $variant->id;
            $totalStock += (int)$stock;
        }

        $product->productVariants()->whereNotIn('id', $currentVariantIds)->delete();

        return $totalStock;
    }

    public function exportExcel(Request $request)
    {
        $search = $request->input('search'); 
        return Excel::download(new ProductsExport($search), 'produk_seller.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $search = $request->input('search'); 
        return Excel::download(new ProductsExport($search), 'produk_seller.pdf', \Maatwebsite\Excel\Excel::DOMPDF);
    }
}