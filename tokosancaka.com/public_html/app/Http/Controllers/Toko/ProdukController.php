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
public function index(Request $request) // Tambahkan Request
{
    $user = Auth::user();
    $store = $user->store; // <-- AMBIL TOKO

    if (!$store) {
        return redirect()->route('seller.dashboard')->with('info', 'Anda perlu membuat toko terlebih dahulu.');
    }
    
    $userId = $user->id_pengguna ?? $user->id; // Ini mungkin tidak terpakai jika semua query pakai store->id
    $search = $request->input('search'); // Ambil input pencarian

    // === PERBAIKAN ===
    // Ambil produk berdasarkan store_id milik user yang login
    $productsQuery = Product::where('store_id', $store->id) // <-- Gunakan $store->id
                            ->with('category') // Muat relasi kategori
                            ->latest();

    // Terapkan filter pencarian jika ada
    if ($search) {
        $productsQuery->where(function($query) use ($search) {
            $query->where('name', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $search . '%');
        });
    }

    // Eksekusi query dengan paginate
    $products = $productsQuery->paginate(10);

    // Penting: agar pagination tetap menyertakan query pencarian
    $products->appends($request->query()); 

    return view('seller.produk.index', compact('products'));
}

    /**
     * Menampilkan form untuk membuat produk baru.
     */
    public function create()
    {
        // Ambil kategori untuk dropdown
        $categories = Category::where('type', 'product')->orderBy('name')->get(['id', 'name']);
        
        // Kirim $categories ke view
        // Pastikan nama view ini sesuai
        return view('seller.produk.create', compact('categories'));
    }

    /**
     * Menyimpan produk baru ke database.
     */
    public function store(Request $request)
    {
        // === PERBAIKAN: DEFINISIKAN $user DI SINI ===
        $user = Auth::user(); 
        if (!$user) {
            return redirect()->back()->withInput()->with('error', 'Sesi Anda telah berakhir. Silakan login kembali.');
        }

        $store = $user->store;
        if (!$store) {
            return redirect()->back()
                             ->withInput()
                             ->with('error', 'Gagal membuat produk: Data toko Anda tidak ditemukan.');
        }

        // Validasi berdasarkan form
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('products', 'name')->where('store_id', $store->id)],
            'category_id' => 'required|exists:categories,id',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'weight' => 'required|integer|min:1',
            'product_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            //'jenis_barang' => 'required|string', // atau integer, sesuaikan
            'status' => 'required|in:active,inactive',
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->where('store_id', $store->id)],
            
            // --- DITAMBAHKAN: Validasi Opsional ---
            'original_price' => 'nullable|numeric|min:0|gt:price', // Harga coret harus > harga jual
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'tags' => 'nullable|string',
            'is_new' => 'nullable|boolean', // Akan 0 jika tidak dicentang
            'is_bestseller' => 'nullable|boolean', // Akan 0 jika tidak dicentang
            'attributes' => 'nullable|array',
            'variant_types' => 'nullable|array', // Untuk grup varian
            'variant_types.*.name' => 'required_with:variant_types|string|max:255', // Validasi nested array
            'variant_types.*.options' => 'required_with:variant_types|string', // Validasi nested array
            'product_variants' => 'nullable|array', // Untuk kombinasi varian (biasanya di-handle di 'update')
        ], [
            'original_price.gt' => 'Harga Asli (Coret) harus lebih besar dari Harga Jual.'
        ]);

        $dataToCreate = $validated;
        $imagePath = null;
        $hasVariantsRequest = $request->has('variant_types') && !empty($request->variant_types);

        DB::beginTransaction();
        try {
            // 1. Handle Upload Gambar
            if ($request->hasFile('product_image')) {
                // Simpan path relatif (products/namafile.jpg)
                $imagePath = $request->file('product_image')->store('products', 'public');
                $dataToCreate['image_url'] = $imagePath;
            }

            // === PERBAIKAN: Ambil data dari $store dan $user ===
            // 2. Tambahkan Data Toko & User
            $dataToCreate['store_id'] = $store->id; // <-- PERBAIKAN KUNCI (Gunakan ID Toko)
            $dataToCreate['store_name'] = $store->name; // Ambil dari $store
            $dataToCreate['seller_city'] = $store->regency; // Ambil dari $store
            $dataToCreate['seller_name'] = $user->nama_lengkap; // Ambil dari $user
            $dataToCreate['seller_wa'] = $user->no_wa; // Ambil dari $user
            // --- AKHIR PERBAIKAN ---

            // 3. Generate Slug
            $dataToCreate['slug'] = $this->generateUniqueSlug($validated['name']);

            // 4. Generate SKU jika kosong
            if (empty($validated['sku'])) {
                $dataToCreate['sku'] = $this->generateSku($validated['name'], $validated['category_id']);
            }

            // 5. Generate Tags
            $category = Category::find($validated['category_id']);
            $manualTags = !empty($request->tags) ? array_map('trim', explode(',', $request->tags)) : [];
            if ($category) {
                $manualTags[] = $category->name;
            }
            $dataToCreate['tags'] = json_encode(array_values(array_unique($manualTags)));
            $dataToCreate['category'] = $category->name; // <-- TAMBAHKAN BARIS INI

            // 6. Handle Checkbox
            $dataToCreate['is_new'] = $request->has('is_new');
            $dataToCreate['is_bestseller'] = $request->has('is_bestseller');

            // 7. Handle Stok
            if ($hasVariantsRequest) {
                $dataToCreate['stock'] = 0; // Stok utama jadi 0 jika ada varian
            }

            // Hapus key 'product_image' karena sudah dihandle
            unset($dataToCreate['product_image']);
            unset($dataToCreate['attributes']);
            unset($dataToCreate['variant_types']);
            unset($dataToCreate['product_variants']);
            
            // 8. Buat Produk
            $product = Product::create($dataToCreate); // <-- Variabel $product dibuat di sini

            // 9. SINKRONISASI ATRIBUT
            if ($request->has('attributes')) {
                $this->syncAttributes($product, $request->input('attributes', []));
            }

            // 10. SINKRONISASI TIPE VARIAN (Hanya Tipe, belum kombinasi)
            if ($hasVariantsRequest) {
               $this->syncVariantTypes($product, $request->input('variant_types', []));
            }


            DB::commit();

            return redirect()->route('seller.produk.index')->with('success', 'Produk berhasil ditambahkan.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Gagal simpan produk seller: ' + $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            // Hapus gambar jika terlanjur di-upload tapi database gagal
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
 * @param string $slug Slug produk dari URL.
 */
    public function edit($slug)
    {
        $user = Auth::user();
        if (!$user || !$user->store) { abort(403, 'Anda harus login dan memiliki toko'); }
        $storeId = $user->store->id; // <-- AMBIL ID TOKO

        $categories = Category::where('type', 'product')->orderBy('name')->get(['id', 'name']);

        // === PERBAIKAN ===
        $produk = Product::where('slug', $slug)
                            ->where('store_id', $storeId) // <-- Gunakan $storeId
                            ->with([
                                'category', 
                                'productAttributes', // Relasi ke product_attributes
                                'productVariantTypes.options', // Relasi ke variant_types dan options
                                'productVariants.options' // DITAMBAHKAN: Ambil kombinasi varian yg ada
                            ])
                            ->firstOrFail();

        // 1. Prepare Tags (decode JSON to comma-separated string)
        $tagsArray = json_decode($produk->tags, true);
        $produk->tags = is_array($tagsArray) ? implode(', ', $tagsArray) : ($produk->tags ?? '');

        // 2. Prepare Attributes JSON
        $attributeDefinitions = Attribute::where('category_id', $produk->category_id)
                                                ->get()
                                                ->keyBy('name'); // Key by name
        
        $existingAttributesData = [];
        foreach($produk->productAttributes as $pa) {
            $slug = Str::slug($pa->name); // Buat slug dari nama
            $value = $pa->value;
            $def = $attributeDefinitions->get($pa->name);
            $type = $def->type ?? 'text';
            
            if ($type === 'checkbox' && is_string($value)) {
                $value = json_decode($value, true) ?? [$value];
            }
            $existingAttributesData[$slug] = $value;
        }
        $existing_attributes_json = json_encode($existingAttributesData);

        // 3. Prepare Variants JSON
        $existing_variant_types_json = $produk->productVariantTypes->map(function($variantType) {
            return [ 
                'name' => $variantType->name, 
                'options' => $variantType->options->pluck('name')->implode(', ') 
            ];
        })->toJson();

        // 4. Prepare Variant Combinations (DITAMBAHKAN)
        // Ini untuk mengisi tabel SKU/Harga/Stok varian di view edit
        $existing_variants_json = $produk->productVariants->map(function($variant) {
            return [
                'id' => $variant->id,
                'sku' => $variant->sku_code,
                'price' => $variant->price,
                'stock' => $variant->stock,
                // Buat 'combination_string' agar JS tahu ini kombinasi apa
                'combination' => $variant->options->mapWithKeys(fn($opt) => [$opt->variantType->name => $opt->name])
            ];
        })->toJson();


        return view('toko.produk.edit', compact(
            'produk', 
            'categories', 
            'existing_attributes_json', 
            'existing_variant_types_json',
            'existing_variants_json' // <-- DITAMBAHKAN
        ));
    }

   /**
     * Mengupdate produk di database.
     * @param string $slug Slug produk dari URL.
     */
    public function update(Request $request, $slug)
    {
        $user = Auth::user();
        if (!$user || !$user->store) { abort(403); }
        $storeId = $user->store->id; // <-- AMBIL ID TOKO

        // === PERBAIKAN ===
        $product = Product::where('slug', $slug)
                            ->where('store_id', $storeId) // <-- Gunakan $storeId
                            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('products', 'name')->where('store_id', $storeId)->ignore($product->id)],
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'weight' => 'required|integer|min:1',
            'product_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status' => 'required|in:active,inactive',
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->where('store_id', $storeId)->ignore($product->id)],
            'attributes' => 'nullable|array',
            'variant_types' => 'nullable|array',
            'variant_types.*.name' => 'required_with:variant_types|string|max:255',
            'variant_types.*.options' => 'required_with:variant_types|string',
            'product_variants' => 'nullable|array', // Validasi untuk kombinasi varian (SKU, harga, stok)
            'product_variants.*.sku' => ['nullable', 'string', 'max:100'], // Validasi unik SKU varian lebih kompleks, bisa di-handle di helper
            'product_variants.*.price' => 'required_with:product_variants|numeric|min:0',
            'product_variants.*.stock' => 'required_with:product_variants|integer|min:0',
            'original_price' => 'nullable|numeric|min:0|gt:price',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'tags' => 'nullable|string',
            'is_new' => 'nullable|boolean',
            'is_bestseller' => 'nullable|boolean',
        ], [
            'original_price.gt' => 'Harga Asli (Coret) harus lebih besar dari Harga Jual.'
        ]);
        
        $dataToUpdate = $validated;
        $hasVariantsRequest = $request->has('variant_types') && !empty($request->variant_types);

        DB::beginTransaction();
        try {
            // 1. Handle Update Gambar
            if ($request->hasFile('product_image')) {
                if ($product->image_url && Storage::disk('public')->exists($product->image_url)) {
                    Storage::disk('public')->delete($product->image_url);
                }
                $imagePath = $request->file('product_image')->store('products', 'public');
                $dataToUpdate['image_url'] = $imagePath;
            }

            // 2. Handle Slug
            if ($request->name !== $product->name) {
                $dataToUpdate['slug'] = $this->generateUniqueSlug($request->name, $product->id);
            }

            // 3. Handle Tags
            $category = Category::find($validated['category_id']);
            $manualTags = !empty($request->tags) ? array_map('trim', explode(',', $request->tags)) : [];
            if ($category) {
                $manualTags[] = $category->name;
                $dataToUpdate['category'] = $category->name; // <-- TAMBAHKAN BARIS INI
            }
            $dataToUpdate['tags'] = json_encode(array_values(array_unique($manualTags)));
            
            // 4. Handle Checkbox
            $dataToUpdate['is_new'] = $request->has('is_new');
            $dataToUpdate['is_bestseller'] = $request->has('is_bestseller');

            // 5. Handle Stock (Akan di-override oleh varian jika ada)
            if ($hasVariantsRequest) {
                 $dataToUpdate['stock'] = 0; // Stok utama akan dihitung dari total varian
            }

            // Hapus key yang tidak perlu
            unset($dataToUpdate['product_image']);
            unset($dataToUpdate['attributes']);
            unset($dataToUpdate['variant_types']);
            unset($dataToUpdate['product_variants']); // <-- DITAMBAHKAN

            // 6. Update Produk
            $product->update($dataToUpdate);

            // 7. Sinkronisasi Attributes & Variants
            
            // --- DILENGKAPI: SINKRONISASI ATRIBUT DAN VARIAN ---
            $this->syncAttributes($product, $request->input('attributes', []));
            
            $totalStock = $validated['stock']; // Default ke stok utama

            if ($hasVariantsRequest) {
                 // Jika ada data varian, panggil helper lengkap
                 $totalStock = $this->syncVariantTypesAndCombinations(
                     $product, 
                     $request->input('variant_types', []), 
                     $request->input('product_variants', [])
                 );
            } else {
                 // Hapus varian lama jika beralih ke non-varian
                 $product->productVariants()->delete();
                 $product->productVariantTypes()->delete();
            }
            
            // Update stok utama produk
            $product->stock = $totalStock;
            $product->save();
            // --- AKHIR BLOK SINKRONISASI ---

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
     * @param string $slug Slug produk dari URL.
     */
    public function destroy($slug)
    {
        $userStore = Auth::user()->store;
        if (!$userStore) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus produk ini.');
        }

        // === PERBAIKAN ===
        $product = Product::where('slug', $slug)
                            ->where('store_id', $userStore->id) // <-- Gunakan $userStore->id
                            ->firstOrFail();

        DB::beginTransaction();
        try {
            // Hapus gambar dari storage
            if ($product->image_url && Storage::disk('public')->exists($product->image_url)) {
                Storage::disk('public')->delete($product->image_url);
            }
            
            // Hapus relasi (varian, atribut) - akan di-handle oleh foreign key 'cascadeOnDelete' jika diset di migrasi
            // Jika tidak, hapus manual:
            $product->productAttributes()->delete();
            $product->productVariants()->delete();
            $product->productVariantTypes()->delete();

            // Hapus produk dari database
            $product->delete();
            
            DB::commit();
            return redirect()->route('seller.produk.index')->with('success', 'Produk berhasil dihapus.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Gagal hapus produk seller: ' . $e->getMessage());
            return redirect()->route('seller.produk.index')->with('error', 'Gagal menghapus produk. Silakan coba lagi.');
        }
    }


    // --- Helper Methods (Copied from Admin\ProductController) ---

    protected function generateUniqueSlug(string $name, int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;
        
        $query = Product::where('slug', $slug);
        
        // Saat update, abaikan ID produk itu sendiri
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }
        
        // Cek juga berdasarkan store_id, agar slug bisa sama antar toko
        // (Asumsi: slug unik per toko)
        if (Auth::check() && Auth::user()->store) {
             $query->where('store_id', Auth::user()->store->id);
        }

        while ($query->exists()) {
            $slug = $originalSlug . '-' . $count++;
            // Update query untuk cek slug baru
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

    protected function generateSku(string $productName, int $categoryId): string
    {
        $category = Category::find($categoryId);
        $categoryInitial = $category ? strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $category->name), 0, 3)) : 'GEN';
        $productInitial = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $productName), 0, 3));
        $randomNum = mt_rand(100, 999);
        $sku = "{$categoryInitial}-{$productInitial}-{$randomNum}";

        $query = Product::where('sku', $sku);
        // Asumsi SKU juga unik per toko
        if (Auth::check() && Auth::user()->store) {
             $query->where('store_id', Auth::user()->store->id);
        }

        while ($query->exists()) {
            $randomNum = mt_rand(100, 999);
            $sku = "{$categoryInitial}-{$productInitial}-{$randomNum}";
            // Update query untuk cek SKU baru
            $query = Product::where('sku', $sku);
            if (Auth::check() && Auth::user()->store) {
                 $query->where('store_id', Auth::user()->store->id);
            }
        }
        return $sku;
    }
    
    /**
     * =============================================
     * TAMBAHKAN FUNGSI HELPER INI
     * =============================================
     * Sinkronisasi atribut produk (copy-paste dari AdminController)
     */
    protected function syncAttributes(Product $product, ?array $attributesData)
    {
        if ($attributesData === null) {
            $product->productAttributes()->delete();
            return;
        }

        $currentAttributeNames = []; 

        // Ambil info atribut valid dari tabel 'attributes'
        $validAttributesInfo = Attribute::where('category_id', $product->category_id)
                                           ->whereIn('slug', array_keys($attributesData))
                                           ->get()
                                           ->keyBy('slug'); // [slug => AttributeModel]

        foreach ($attributesData as $slug => $value) {
            $attributeInfo = $validAttributesInfo->get($slug);
            // Gunakan nama dari tabel attributes jika ada, jika tidak buat dari slug
            $attributeName = $attributeInfo->name ?? str_replace('-', ' ', Str::title($slug));

            // Hanya proses jika nama atribut ada dan value tidak kosong/null
            if (!empty($attributeName) && ($value !== null && $value !== '' && (!is_array($value) || !empty(array_filter($value)))))
            {
                // Proses value checkbox
                $processedValue = is_array($value) ? json_encode(array_values(array_filter($value))) : $value;

                ProductAttribute::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'name' => $attributeName, // Gunakan 'name' sebagai kunci
                    ],
                    [
                        'value' => $processedValue,
                        // Pastikan model ProductAttribute Anda punya 'name' di $fillable
                    ]
                );
                $currentAttributeNames[] = $attributeName; // Lacak nama
            }
        }

        // Hapus ProductAttribute yang namanya tidak ada lagi di $attributesData
        $product->productAttributes()->whereNotIn('name', $currentAttributeNames)->delete();
    }


    // ==========================================================
    // === HELPER FUNCTIONS UNTUK VARIAN (DITAMBAHKAN) ===
    // ==========================================================

    /**
     * Sinkronisasi Tipe Varian (cth: 'Warna') dan Opsinya (cth: 'Merah', 'Biru').
     * Mengembalikan collection dari Tipe Varian yang sudah di-sync.
     */
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
            
            // Sinkronkan Opsi
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
            // Hapus opsi lama yang tidak ada di request
            $variantType->options()->whereNotIn('id', $currentOptionIds)->delete();
            
            $syncedTypes->push($variantType->load('options'));
        }
        // Hapus tipe lama yang tidak ada di request
        $product->productVariantTypes()->whereNotIn('id', $currentTypeIds)->delete();
        
        return $syncedTypes;
    }

    /**
     * Sinkronisasi penuh: Tipe, Opsi, dan Kombinasi Varian (SKU/Harga/Stok).
     * Digunakan oleh 'update'.
     * @return int Total stok dari semua varian
     */
    protected function syncVariantTypesAndCombinations(Product $product, array $variantTypesData, ?array $productVariantsData)
    {
        // 1. Sinkronkan Tipe dan Opsi dulu
        $syncedTypes = $this->syncVariantTypes($product, $variantTypesData);

        // Buat pemetaan Opsi 'Nama' -> ID untuk lookup cepat
        // Cth: 'Merah' => 1, 'XL' => 5
        $optionNameMap = $syncedTypes->flatMap(fn($type) => $type->options)->pluck('id', 'name');
        
        $currentVariantIds = [];
        $totalStock = 0;

        if (empty($productVariantsData)) {
            // Jika array varian kosong (misal user hapus semua kombinasi), hapus semua
            $product->productVariants()->delete();
            return 0;
        }

        // 2. Loop data kombinasi varian (dari tabel SKU/Harga/Stok di form)
        foreach ($productVariantsData as $variantData) {
            
            $sku = $variantData['sku'] ?? null;
            $price = $variantData['price'] ?? 0;
            $stock = $variantData['stock'] ?? 0;
            $optionsInCombination = $variantData['options'] ?? []; // Cth: ['Warna' => 'Merah', 'Ukuran' => 'XL']

            if (empty($optionsInCombination)) continue;

            // 3. Buat/Update ProductVariant (kombinasinya)
            // Kita gunakan SKU sebagai ID unik jika ada, jika tidak, kita cari berdasarkan kombinasi
            
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
                        'combination_string' => implode(';', $optionsInCombination) // Simpan string 'Merah;XL'
                    ]
                );
            } else {
                // Jika SKU tidak ada, cari berdasarkan string kombinasi
                $combinationString = implode(';', $optionsInCombination);
                $variant = ProductVariant::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'combination_string' => $combinationString,
                    ],
                    [
                        'price' => $price,
                        'stock' => $stock,
                        'sku_code' => $sku // Bisa jadi null
                    ]
                );
            }

            // 4. Sinkronkan relasi Many-to-Many (product_variant_options_pivot)
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

        // 5. Hapus kombinasi varian lama yang tidak ada di request
        $product->productVariants()->whereNotIn('id', $currentVariantIds)->delete();

        // 6. Kembalikan total stok
        return $totalStock;
    }
    
    /**
     * Handle ekspor data produk ke Excel.
     */
    public function exportExcel(Request $request) 
    {
        $search = $request->input('search'); // Dapatkan query pencarian
        return Excel::download(new ProductsExport($search), 'produk_seller.xlsx');
    }
    
    /**
     * Handle ekspor data produk ke PDF.
     */
    public function exportPdf(Request $request) 
    {
        $search = $request->input('search'); // Dapatkan query pencarian
        // Pastikan Anda sudah 'composer require dompdf/dompdf'
        return Excel::download(new ProductsExport($search), 'produk_seller.pdf', \Maatwebsite\Excel\Excel::DOMPDF);
    }
    
}