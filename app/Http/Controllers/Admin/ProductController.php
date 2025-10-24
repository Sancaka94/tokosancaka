<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
// Hapus use Attribute jika tidak digunakan langsung di sini
use App\Models\Attribute; // Pastikan Model Attribute ada (untuk get type)
use App\Models\ProductAttribute; // Pastikan Model ProductAttribute ada
use App\Models\ProductVariantType; // Pastikan Model ProductVariantType ada
use App\Models\ProductVariantOption; // Pastikan Model ProductVariantOption ada
use App\Models\ProductVariant; // Pastikan Model ProductVariant ada
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use Exception;
use Illuminate\Support\Facades\Log; // Untuk logging error
use Illuminate\Support\Facades\Auth; // Jika perlu ambil data admin default
use Illuminate\Validation\Rule; // Untuk unique rule


class ProductController extends Controller
{
    /**
     * Menampilkan halaman manajemen produk.
     */
    public function index()
    {
        // Ambil semua kategori produk untuk filter dropdown
        $categories = Category::where('type', 'product')->orderBy('name')->get(['name', 'slug']);
        return view('admin.products.index', compact('categories')); // Kirim categories ke view
    }

    /**
     * Menyediakan data untuk Yajra DataTables.
     */
    public function getData(Request $request)
    {
        if ($request->ajax()) {
            try {
                $categorySlug = $request->input('category_slug');

                // Muat relasi 'category' dan 'productVariantTypes'
                // Juga muat relasi 'productVariants' untuk mendapatkan harga varian pertama (jika ada)
                $data = Product::with(['category', 'productVariantTypes', 'productVariants'])
                        ->when($categorySlug, function ($query, $slug) {
                            $query->whereHas('category', function($q) use ($slug) {
                                $q->where('slug', $slug);
                            });
                        })
                        ->select('products.*'); // Pilih semua kolom dari products

                return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('image', function ($row) {
                        // PERBAIKAN: Cek 'image_url' dan fallback ke 'image', lalu cek null dan file exists
                        $imageUrl = $row->image_url ?? $row->image; // Coba image_url dulu, fallback ke image
                        $url = $imageUrl && Storage::disk('public')->exists($imageUrl)
                               ? asset('storage/' . $imageUrl)
                               : 'https://placehold.co/80x80/EFEFEF/333333?text=N/A'; // Tampilkan N/A jika null atau file tidak ada
                        return '<img src="' . e($url) . '" alt="' . e($row->name) . '" class="rounded" width="60" loading="lazy" />';
                    })
                    ->editColumn('price', function ($row) {
                         // Tampilkan harga varian pertama jika ada dan relasi dimuat
                        $displayPrice = $row->price;
                        if($row->relationLoaded('productVariants') && $row->productVariants->isNotEmpty()){
                             $displayPrice = $row->productVariants->first()->price ?? $row->price;
                        }
                        return 'Rp' . number_format($displayPrice, 0, ',', '.');
                    })
                    ->addColumn('category_id', function ($row) {
                        // PERBAIKAN: Tambahkan is_object() check
                        return $row->category && is_object($row->category) ? e($row->category->id) : '<span class="text-danger">N/A</span>';
                    })
                    ->addColumn('has_variants', function($row) {
                         // Cek apakah relasi productVariantTypes (yang di-load) ada dan tidak kosong
                        return $row->productVariantTypes && $row->productVariantTypes->isNotEmpty();
                    })
                    ->addColumn('status_badge', function ($row) {
                        $color = $row->status == 'active' ? 'bg-success' : 'bg-secondary';
                        $text = $row->status == 'active' ? 'Aktif' : 'Nonaktif';
                        return '<span class="badge ' . e($color) . '">' . e($text) . '</span>';
                    })
                    ->editColumn('stock', function ($row) {
                        // Tambahkan indikator varian
                        $stockDisplay = $row->stock ?? 0;
                        // Gunakan has_variants yang sudah dihitung
                        if ($row->has_variants) {
                            $stockDisplay = ($row->stock ?? 0) . ' <i class="fas fa-code-branch variant-indicator" title="Produk ini memiliki varian"></i>';
                        }
                        return $stockDisplay;
                    })
                    ->addColumn('action', function($row){
                        // Menggunakan slug untuk route model binding
                        $editUrl = route('admin.products.edit', $row->slug);
                        $deleteUrl = route('admin.products.destroy', $row->slug);
                        $outOfStockUrl = route('admin.products.outOfStock', $row->slug);
                        $restockUrl = route('admin.products.restock', $row->slug);

                        $actionBtn = '<div class="d-flex justify-content-center gap-2">';
                        // Tombol restock (hanya tampil jika tidak ada varian)
                        if (!$row->has_variants) {
                             $actionBtn .= '<button type="button" onclick="openRestockModal(\''.e($restockUrl).'\', \''.e($row->name).'\')" class="btn btn-success btn-circle btn-sm" title="Restock"><i class="fas fa-plus"></i></button>';
                             $actionBtn .= '<form action="'.e($outOfStockUrl).'" method="POST" class="d-inline" onsubmit="return confirm(\'Anda yakin ingin menandai produk ini habis?\');">'.csrf_field().method_field('PATCH').'<button type="submit" class="btn btn-secondary btn-circle btn-sm" title="Tandai Habis"><i class="fas fa-box-open"></i></button></form>';
                        } else {
                             $actionBtn .= '<button type="button" class="btn btn-outline-secondary btn-circle btn-sm disabled" title="Atur stok via Edit Varian"><i class="fas fa-plus"></i></button>';
                             $actionBtn .= '<button type="button" class="btn btn-outline-secondary btn-circle btn-sm disabled" title="Atur stok via Edit Varian"><i class="fas fa-box-open"></i></button>';
                        }
                        $actionBtn .= '<a href="'.e($editUrl).'" class="btn btn-warning btn-circle btn-sm" title="Edit"><i class="fas fa-pen-to-square"></i></a>';
                        $actionBtn .= '<form action="'.e($deleteUrl).'" method="POST" class="d-inline" onsubmit="return confirm(\'Anda yakin ingin menghapus produk ini?\');">'.csrf_field().method_field('DELETE').'<button type="submit" class="btn btn-danger btn-circle btn-sm" title="Hapus"><i class="fas fa-trash"></i></button></form>';
                        $actionBtn .= '</div>';

                        return $actionBtn;
                    })
                    ->rawColumns(['action', 'image', 'status_badge', 'stock', 'category_name'])
                    ->make(true);

            } catch (Exception $e) {
                Log::error('DataTables Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
                return response()->json(['error' => 'Could not process data.', 'message' => $e->getMessage()], 500);
            }
        }
         // Fallback jika bukan AJAX
         $categories = Category::where('type', 'product')->orderBy('name')->get(['name', 'slug']);
         return view('admin.products.index', compact('categories'));
    }


    /**
     * Menampilkan form untuk membuat produk baru.
     */
    public function create()
    {
        $categories = Category::where('type', 'product')->orderBy('name')->get();
        return view('admin.products.create', compact('categories'));
    }

    /**
     * Menyimpan produk baru ke database.
     */
    public function store(Request $request)
    {
        $baseRules = [
            'name'             => ['required', 'string', 'max:255', Rule::unique('products', 'name')],
            'description'      => 'nullable|string',
            'price'            => 'required|numeric|min:0',
            'original_price'   => 'nullable|numeric|min:0|gte:price',
            'weight'           => 'required|integer|min:0',
            'category_id'      => 'required|exists:categories,id',
            'product_image'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'store_name'       => 'nullable|string|max:255',
            'seller_city'      => 'nullable|string|max:255',
            'seller_name'      => 'nullable|string|max:255',
            'seller_wa'        => 'nullable|string|max:20',
            'seller_logo'      => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'sku'              => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')],
            'tags'             => 'nullable|string',
            'status'           => 'required|in:active,inactive',
            'is_new'           => 'nullable|boolean',
            'is_bestseller'    => 'nullable|boolean',
            'length'           => 'nullable|numeric|min:0',
            'width'            => 'nullable|numeric|min:0',
            'height'           => 'nullable|numeric|min:0',
            'attributes'       => 'nullable|array',
            'attributes.*'     => 'nullable',
            'variant_types'    => 'nullable|array',
        ];

        $conditionalRules = [];
        $hasVariantsRequest = $request->has('variant_types') && !empty($request->variant_types);

        if ($hasVariantsRequest) {
            $conditionalRules = [
                'stock'            => 'nullable|integer|min:0',
                'variant_types.*.name' => 'required|string|max:255',
                'variant_types.*.options' => 'required|string|max:1000',
                'product_variants' => 'required|array|min:1',
                'product_variants.*.price' => 'required|numeric|min:0',
                'product_variants.*.stock' => 'required|integer|min:0',
                'product_variants.*.sku_code' => ['nullable', 'string', 'max:100', Rule::unique('product_variants', 'sku_code')],
                'product_variants.*.variant_options' => 'required|array|min:1',
                'product_variants.*.variant_options.*.type_name' => 'required|string',
                'product_variants.*.variant_options.*.value' => 'required|string',
            ];
        } else {
            $conditionalRules = [
                'stock' => 'required|integer|min:0',
                'product_variants' => 'prohibited',
            ];
        }

        $validated = $request->validate(array_merge($baseRules, $conditionalRules));

        $validatedDataForCreate = $validated;
        try {
            if ($request->hasFile('product_image')) {
                Log::info('Processing product image upload...');
                $path = $request->file('product_image')->store('products', 'public');
                if ($path) {
                     $validatedDataForCreate['image_url'] = $path; // Use image_url
                     Log::info('Product image stored at: ' . $path);
                } else {
                     Log::error('Failed to store product image.');
                }
            } else {
                Log::info('No product image uploaded.');
            }
            unset($validatedDataForCreate['product_image']);

            if ($request->hasFile('seller_logo')) {
                 Log::info('Processing seller logo upload...');
                $logoPath = $request->file('seller_logo')->store('seller_logos', 'public');
                 if ($logoPath) {
                    $validatedDataForCreate['seller_logo'] = $logoPath;
                    Log::info('Seller logo stored at: ' . $logoPath);
                 } else {
                     Log::error('Failed to store seller logo.');
                 }
            } else {
                 Log::info('No seller logo uploaded.');
            }
            unset($validatedDataForCreate['seller_logo']);


            if (!empty($request->seller_wa)) {
                $wa = preg_replace('/[^0-9]/', '', $request->seller_wa);
                if (!Str::startsWith($wa, '62')) {
                     $wa = '62' . ltrim($wa, '0');
                }
                $validatedDataForCreate['seller_wa'] = $wa;
            }
            if (empty($validatedDataForCreate['store_name'])) {
                $validatedDataForCreate['store_name'] = Auth::user()->store->name ?? config('app.default_store_name', 'Toko Sancaka Default');
            }
            if (empty($validatedDataForCreate['seller_city'])) {
                $validatedDataForCreate['seller_city'] = Auth::user()->store->city ?? config('app.default_store_city', 'Ngawi');
            }

            $validatedDataForCreate['slug'] = $this->generateUniqueSlug($validatedDataForCreate['name']);

            if (empty($validatedDataForCreate['sku']) && !empty($validatedDataForCreate['category_id']) && !$hasVariantsRequest) {
                 $validatedDataForCreate['sku'] = $this->generateSku($validatedDataForCreate['name'], $validatedDataForCreate['category_id']);
            }

            $manualTags = [];
            if (!empty($request->tags)) {
                $manualTags = array_map('trim', explode(',', $request->tags));
                $manualTags = array_filter($manualTags);
            }
            $category = Category::find($validatedDataForCreate['category_id']);
            $categoryTag = $category->name ?? null;
            $allTags = $manualTags;
            if ($categoryTag && !in_array($categoryTag, $allTags)) {
                $allTags[] = $categoryTag;
            }
            $validatedDataForCreate['tags'] = !empty($allTags) ? json_encode(array_values(array_unique($allTags))) : null;

            $validatedDataForCreate['is_new'] = $request->has('is_new');
            $validatedDataForCreate['is_bestseller'] = $request->has('is_bestseller');

            // Hapus data relasi sebelum create product
            $attributesInput = $request->input('attributes', []);
            $variantTypesInput = $request->input('variant_types', []);
            $productVariantsInput = $request->input('product_variants', []);
            unset($validatedDataForCreate['attributes']);
            unset($validatedDataForCreate['variant_types']);
            unset($validatedDataForCreate['product_variants']);

            if ($hasVariantsRequest) {
                $validatedDataForCreate['stock'] = 0;
                 $validatedDataForCreate['price'] = $productVariantsInput[0]['price'] ?? $validatedDataForCreate['price'];
            }

            Log::info('Attempting to create product with data:', $validatedDataForCreate);

            $product = DB::transaction(function () use ($validatedDataForCreate, $attributesInput, $variantTypesInput, $productVariantsInput, $hasVariantsRequest) {
                $product = Product::create($validatedDataForCreate);
                 if (!$product) {
                    throw new Exception("Failed to create product model.");
                 }
                 Log::info("Product created with ID: " . $product->id);
                $this->syncAttributes($product, $attributesInput);
                if ($hasVariantsRequest) {
                    $this->syncVariantTypesAndCombinations($product, $variantTypesInput, $productVariantsInput);
                }
                return $product;
            });

            return redirect()->route('admin.products.index')->with('success', 'Produk baru berhasil ditambahkan.');

        } catch (Exception $e) {
            Log::error('Error saving product: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            $imagePath = $validatedDataForCreate['image_url'] ?? null;
            if ($imagePath && Storage::disk('public')->exists($imagePath)) { Storage::disk('public')->delete($imagePath); }
            $logoPath = $validatedDataForCreate['seller_logo'] ?? null;
            if ($logoPath && Storage::disk('public')->exists($logoPath)) { Storage::disk('public')->delete($logoPath); }
            return back()->with('error', 'Gagal menyimpan produk: ' . $e->getMessage())->withInput();
        }
    }


    /**
     * Menampilkan form untuk mengedit produk.
     */
    public function edit(Product $product)
    {
        $product->load([
            'category',
            'productAttributes', // Hanya load ini, tidak perlu relasi ke attribute
            'productVariantTypes.options' => fn ($q) => $q->orderBy('id'),
            'productVariants.options' => fn ($q) => $q->orderBy('product_variant_type_id')->orderBy('id')
        ]);

        $product->tags = $this->decodeTags($product->tags);

        // Siapkan data atribut yang ada untuk JavaScript [slug => value]
        $existingAttributesData = [];
        // Ambil info tipe dari tabel 'attributes' untuk konversi checkbox JSON
        $attributeDefinitions = Attribute::where('category_id', $product->category_id)
                                          ->get()
                                          ->keyBy('name'); // Key by name for lookup

        foreach($product->productAttributes as $pa) {
            $attributeName = $pa->name;
            $slug = Str::slug($attributeName); // Buat slug dari nama

            if ($slug) {
                $value = $pa->value;
                $attributeInfo = $attributeDefinitions->get($attributeName);
                $attributeType = $attributeInfo->type ?? 'text'; // Ambil tipe dari tabel attributes

                if ($attributeType === 'checkbox' && is_string($value)) {
                    try {
                        $decodedValue = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                        $value = is_array($decodedValue) ? $decodedValue : [$value];
                    } catch (\JsonException $e) { $value = [$value]; }
                }
                $existingAttributesData[$slug] = $value;
            }
        }
        $product->existing_attributes_json = json_encode($existingAttributesData);


        $product->existing_variant_types_json = $product->productVariantTypes->map(function($variantType) {
            return [ 'name' => $variantType->name, 'options' => $variantType->options->pluck('name')->implode(', ') ];
        })->toJson();

        $product->existing_variant_combinations_json = $product->productVariants->mapWithKeys(function($variant) {
            $key = $variant->options->map(fn($option) => ($option->productVariantType->name ?? 'UNKNOWN') . ':' . $option->name)->sort()->implode(';');
            return [ $key => [ 'price' => $variant->price, 'stock' => $variant->stock, 'sku_code' => $variant->sku_code ]];
        })->toJson();

        $categories = Category::where('type', 'product')->orderBy('name')->get();
        return view('admin.products.edit', compact('product', 'categories'));
    }


    /**
     * Memperbarui data produk di database.
     */
    public function update(Request $request, Product $product)
    {
        $baseRules = [
            'name'             => ['required', 'string', 'max:255', Rule::unique('products', 'name')->ignore($product->id)],
            'description'      => 'nullable|string',
            'price'            => 'required|numeric|min:0',
            'original_price'   => 'nullable|numeric|min:0|gte:price',
            'weight'           => 'required|integer|min:0',
            'category_id'      => 'required|exists:categories,id',
            'product_image'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'store_name'       => 'nullable|string|max:255',
            'seller_city'      => 'nullable|string|max:255',
            'seller_name'      => 'nullable|string|max:255',
            'seller_wa'        => 'nullable|string|max:20',
            'seller_logo'      => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'sku'              => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product->id)],
            'tags'             => 'nullable|string',
            'status'           => 'required|in:active,inactive',
            'is_new'           => 'nullable|boolean',
            'is_bestseller'    => 'nullable|boolean',
            'length'           => 'nullable|numeric|min:0',
            'width'            => 'nullable|numeric|min:0',
            'height'           => 'nullable|numeric|min:0',
            'attributes'       => 'nullable|array',
            'attributes.*'     => 'nullable',
            'variant_types'    => 'nullable|array',
        ];

        $conditionalRules = [];
        $hasVariantsRequest = $request->has('variant_types') && !empty($request->variant_types);

        if ($hasVariantsRequest) {
            $conditionalRules = [
                'stock'            => 'nullable|integer|min:0',
                'variant_types.*.name' => 'required|string|max:255',
                'variant_types.*.options' => 'required|string|max:1000',
                'product_variants' => 'required|array|min:1',
                'product_variants.*.price' => 'required|numeric|min:0',
                'product_variants.*.stock' => 'required|integer|min:0',
                'product_variants.*.sku_code' => ['nullable', 'string', 'max:100'], // Unique check might need manual loop
                'product_variants.*.variant_options' => 'required|array|min:1',
                'product_variants.*.variant_options.*.type_name' => 'required|string',
                'product_variants.*.variant_options.*.value' => 'required|string',
            ];
        } else {
            $conditionalRules = [
                'stock' => 'required|integer|min:0',
                'product_variants' => 'prohibited',
            ];
        }

        $validated = $request->validate(array_merge($baseRules, $conditionalRules));

        $validatedDataForUpdate = $validated;
        try {
            // Handle image update
            if ($request->hasFile('product_image')) {
                 Log::info('Processing product image update for product ID: ' . $product->id);
                $oldImage = $product->image_url;
                if ($oldImage && Storage::disk('public')->exists($oldImage)) {
                    Log::info('Deleting old product image: ' . $oldImage);
                    Storage::disk('public')->delete($oldImage);
                }
                $path = $request->file('product_image')->store('products', 'public');
                if ($path) {
                    $validatedDataForUpdate['image_url'] = $path; // Use image_url
                    Log::info('New product image stored at: ' . $path);
                } else {
                     Log::error('Failed to store updated product image for product ID: ' . $product->id);
                     unset($validatedDataForUpdate['image_url']); // Jangan update jika gagal
                }
            } else {
                 // JANGAN set $validatedDataForUpdate['image_url'] = $product->image_url di sini
                 // Cukup unset key agar tidak menimpa data lama jika tidak ada file baru
                 unset($validatedDataForUpdate['image_url']);
                 Log::info('No new product image uploaded for product ID: ' . $product->id . '. Keeping old: ' . $product->image_url);
            }
            unset($validatedDataForUpdate['product_image']); // Hapus input file


            // Handle logo update (logika sama seperti image)
            if ($request->hasFile('seller_logo')) {
                 Log::info('Processing seller logo update for product ID: ' . $product->id);
                $oldLogo = $product->seller_logo;
                if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                     Log::info('Deleting old seller logo: ' . $oldLogo);
                    Storage::disk('public')->delete($oldLogo);
                }
                $logoPath = $request->file('seller_logo')->store('seller_logos', 'public');
                 if ($logoPath) {
                    $validatedDataForUpdate['seller_logo'] = $logoPath;
                     Log::info('New seller logo stored at: ' . $logoPath);
                 } else {
                     Log::error('Failed to store updated seller logo for product ID: ' . $product->id);
                     unset($validatedDataForUpdate['seller_logo']);
                 }
            } else {
                // Jangan kirim key 'seller_logo' jika tidak ada file baru
                 unset($validatedDataForUpdate['seller_logo']);
                 Log::info('No new seller logo uploaded for product ID: ' . $product->id . '. Keeping old: ' . $product->seller_logo);
            }
            unset($validatedDataForUpdate['seller_logo']); // Hapus input file


            if ($request->filled('seller_wa')) {
                $wa = preg_replace('/[^0-9]/', '', $request->seller_wa);
                if (!Str::startsWith($wa, '62')) {
                     $wa = '62' . ltrim($wa, '0');
                }
                $validatedDataForUpdate['seller_wa'] = $wa;
            } else { $validatedDataForUpdate['seller_wa'] = null; }

            if (empty($validatedDataForUpdate['store_name'])) {
                $validatedDataForUpdate['store_name'] = Auth::user()->store->name ?? config('app.default_store_name', 'Toko Sancaka Default');
            }
            if (empty($validatedDataForUpdate['seller_city'])) {
                $validatedDataForUpdate['seller_city'] = Auth::user()->store->city ?? config('app.default_store_city', 'Ngawi');
            }

            if ($request->name !== $product->name) {
                 $validatedDataForUpdate['slug'] = $this->generateUniqueSlug($validatedDataForUpdate['name'], $product->id);
            }

            if (empty($validatedDataForUpdate['sku']) && empty($product->sku) && !$hasVariantsRequest && !empty($validatedDataForUpdate['category_id'])) {
                 $validatedDataForUpdate['sku'] = $this->generateSku($validatedDataForUpdate['name'], $validatedDataForUpdate['category_id']);
            }

            $manualTags = [];
            if (!empty($request->tags)) {
                $manualTags = array_map('trim', explode(',', $request->tags));
                $manualTags = array_filter($manualTags);
            }
            $category = Category::find($validatedDataForUpdate['category_id']);
            $categoryTag = $category->name ?? null;
            $allTags = $manualTags;
            if ($categoryTag && !in_array($categoryTag, $allTags)) {
                $allTags[] = $categoryTag;
            }
            $validatedDataForUpdate['tags'] = !empty($allTags) ? json_encode(array_values(array_unique($allTags))) : null;

            $validatedDataForUpdate['is_new'] = $request->has('is_new');
            $validatedDataForUpdate['is_bestseller'] = $request->has('is_bestseller');

            $attributesInput = $request->input('attributes', []);
            $variantTypesInput = $request->input('variant_types', []);
            $productVariantsInput = $request->input('product_variants', []);
            unset($validatedDataForUpdate['attributes']);
            unset($validatedDataForUpdate['variant_types']);
            unset($validatedDataForUpdate['product_variants']);

            if ($hasVariantsRequest) {
                $validatedDataForUpdate['stock'] = 0;
                $validatedDataForUpdate['price'] = $productVariantsInput[0]['price'] ?? $validatedDataForUpdate['price'];
            } else {
                $validatedDataForUpdate['stock'] = $request->input('stock', $product->stock);
                $validatedDataForUpdate['price'] = $request->input('price', $product->price);
            }

            Log::info('Attempting to update product ID: ' . $product->id . ' with data:', $validatedDataForUpdate);

            DB::transaction(function () use ($product, $validatedDataForUpdate, $attributesInput, $variantTypesInput, $productVariantsInput, $hasVariantsRequest) {
                $updateResult = $product->update($validatedDataForUpdate);
                 if (!$updateResult) {
                     throw new Exception("Failed to update product model.");
                 }
                 Log::info("Product updated successfully for ID: " . $product->id);
                $this->syncAttributes($product, $attributesInput);
                if ($hasVariantsRequest) {
                    $this->syncVariantTypesAndCombinations($product, $variantTypesInput, $productVariantsInput);
                } else {
                     $product->productVariants()->each(fn($v) => $v->options()->detach());
                     $product->productVariants()->delete();
                     $product->productVariantTypes()->delete();
                }
            });

            return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diperbarui.');

        } catch (Exception $e) {
            Log::error('Error updating product ID ' . $product->id . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return back()->with('error', 'Gagal memperbarui produk: ' . $e->getMessage())->withInput();
        }
    }


    /**
     * Menghapus produk dari database.
     */
    public function destroy(Product $product)
    {
        DB::beginTransaction();
        try {
            $imageUrl = $product->image_url ?? $product->image;
            if ($imageUrl && Storage::disk('public')->exists($imageUrl)) { Storage::disk('public')->delete($imageUrl); }
            if ($product->seller_logo && Storage::disk('public')->exists($product->seller_logo)) { Storage::disk('public')->delete($product->seller_logo); }

            // Hapus produk (cascade harusnya menangani relasi)
            $product->delete();

            DB::commit();
            return redirect()->route('admin.products.index')->with('success', 'Produk berhasil dihapus.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting product ID ' . $product->id . ': ' . $e->getMessage());
            return redirect()->route('admin.products.index')->with('error', 'Gagal menghapus produk: ' . $e->getMessage());
        }
    }

    /**
     * Menambahkan stok untuk produk tertentu (tanpa varian).
     */
    public function restock(Request $request, Product $product)
    {
        if ($product->productVariantTypes()->exists()) {
             return redirect()->route('admin.products.index')->with('error', 'Gunakan halaman edit untuk restock produk dengan varian.');
        }
        $validated = $request->validate([ 'stock' => 'required|integer|min:1' ]);
        try {
            $product->increment('stock', $validated['stock']);
            return redirect()->route('admin.products.index')->with('success', 'Stok untuk produk ' . e($product->name) . ' berhasil ditambahkan.');
        } catch (Exception $e) {
             Log::error('Error restocking product ID ' . $product->id . ': ' . $e->getMessage());
             return redirect()->route('admin.products.index')->with('error', 'Gagal restock produk: ' . $e->getMessage());
        }
    }

    /**
     * Menandai produk (tanpa varian) sebagai habis (stok = 0).
     */
    public function markAsOutOfStock(Product $product)
    {
        if ($product->productVariantTypes()->exists()) {
             return redirect()->route('admin.products.index')->with('error', 'Gunakan halaman edit untuk mengatur stok produk dengan varian.');
        }
        try {
            $product->stock = 0;
            $product->save();
            return redirect()->route('admin.products.index')->with('success', 'Stok untuk produk ' . e($product->name) . ' telah diatur menjadi 0.');
        } catch (Exception $e) {
             Log::error('Error marking product out of stock ID ' . $product->id . ': ' . $e->getMessage());
             return redirect()->route('admin.products.index')->with('error', 'Gagal menandai habis stok: ' . $e->getMessage());
        }
    }

    // --- Helper Methods ---

    protected function generateUniqueSlug(string $name, int $ignoreId = null): string
     {
         $slug = Str::slug($name);
         $originalSlug = $slug;
         $count = 1;
         while (Product::where('slug', $slug)->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->exists()) {
             $slug = $originalSlug . '-' . $count++;
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
        while (Product::where('sku', $sku)->exists()) {
             $randomNum = mt_rand(100, 999);
             $sku = "{$categoryInitial}-{$productInitial}-{$randomNum}";
        }
        return $sku;
     }

    protected function decodeTags($tags): string
    {
         if (is_string($tags)) {
             try {
                 $decodedTags = json_decode($tags, true, 512, JSON_THROW_ON_ERROR);
                 return is_array($decodedTags) ? implode(', ', $decodedTags) : $tags;
             } catch (\JsonException $e) { return $tags; }
         } elseif (is_array($tags)) { return implode(', ', $tags); }
         return '';
    }

    /**
     * Sinkronisasi atribut produk berdasarkan struktur tabel Anda (product_id, name, value).
     * Menggunakan updateOrCreate dan menghapus yang lama.
     */
    protected function syncAttributes(Product $product, ?array $attributesData)
    {
        if ($attributesData === null) {
            $product->productAttributes()->delete();
            return;
        }

        $currentAttributeNames = []; // Lacak NAMA atribut yang disinkronkan

        // Ambil info atribut valid dari tabel 'attributes' untuk mendapatkan tipe data
        // Hanya perlu jika Anda ingin menyimpan tipe di product_attributes atau handle checkbox
        $validAttributesInfo = Attribute::where('category_id', $product->category_id)
                                    ->whereIn('slug', array_keys($attributesData))
                                    ->get()
                                    ->keyBy('slug'); // [slug => AttributeModel]

        foreach ($attributesData as $slug => $value) {
             // Dapatkan nama dari Attribute model jika ada, jika tidak, buat dari slug
            $attributeInfo = $validAttributesInfo->get($slug);
            // Gunakan nama dari tabel attributes jika ada, jika tidak buat dari slug
            $attributeName = $attributeInfo->name ?? str_replace('-', ' ', Str::title($slug));

            // Hanya proses jika nama atribut ada dan value tidak kosong/null
            if (!empty($attributeName) && ($value !== null && $value !== '' && (!is_array($value) || !empty(array_filter($value)))))
            {
                // Proses value checkbox
                $processedValue = is_array($value) ? json_encode(array_values(array_filter($value))) : $value;

                // Gunakan updateOrCreate berdasarkan product_id dan name
                ProductAttribute::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'name' => $attributeName, // Gunakan 'name' sebagai kunci
                    ],
                    [
                        'value' => $processedValue,
                        // PERBAIKAN: Hapus kolom yang tidak ada di tabel Anda
                        // 'attribute_slug' => $slug,
                        // 'attribute_type' => $attributeInfo->type ?? 'text',
                    ]
                );
                $currentAttributeNames[] = $attributeName; // Lacak nama
            }
        }

        // Hapus ProductAttribute yang namanya tidak ada lagi di $attributesData
        $product->productAttributes()->whereNotIn('name', $currentAttributeNames)->delete();
    }


     /**
     * Sinkronisasi tipe varian, opsi, dan kombinasi varian produk.
     */
    protected function syncVariantTypesAndCombinations(Product $product, ?array $variantTypesData, ?array $productVariantsData)
    {
        if ($variantTypesData === null || $productVariantsData === null) {
            $product->productVariants()->each(fn($v) => $v->options()->detach());
            $product->productVariants()->delete();
            $product->productVariantTypes()->delete();
            return;
        }

        $currentTypeIds = [];
        $optionIdMap = [];

        foreach ($variantTypesData as $typeData) {
            $typeName = trim($typeData['name'] ?? '');
            $optionsInputRaw = trim($typeData['options'] ?? '');
            if (empty($typeName) || empty($optionsInputRaw)) continue;

            $optionsInput = array_values(array_filter(array_unique(array_map('trim', explode(',', $optionsInputRaw)))));
            if (empty($optionsInput)) continue;

            $variantType = ProductVariantType::updateOrCreate(
                ['product_id' => $product->id, 'name' => $typeName]
            );
            $currentTypeIds[] = $variantType->id;

            $currentOptionIds = [];
            foreach ($optionsInput as $optionName) {
                $option = ProductVariantOption::updateOrCreate(
                    ['product_variant_type_id' => $variantType->id, 'name' => $optionName]
                );
                $currentOptionIds[] = $option->id;
                $optionIdMap[$typeName . ':' . $optionName] = $option->id;
            }
            $variantType->options()->whereNotIn('id', $currentOptionIds)->delete();
        }
        $product->productVariantTypes()->whereNotIn('id', $currentTypeIds)->delete();

        $currentVariantIds = [];
        foreach ($productVariantsData as $comboData) {
            $variantOptions = $comboData['variant_options'] ?? [];
            if (empty($variantOptions)) continue;

            $combinationStringParts = [];
            $optionIdsForCombination = [];

            usort($variantOptions, fn($a, $b) => strcmp($a['type_name'], $b['type_name']));

            foreach ($variantOptions as $optionDetail) {
                 $typeName = trim($optionDetail['type_name'] ?? '');
                 $valueName = trim($optionDetail['value'] ?? '');
                 $mapKey = $typeName . ':' . $valueName;

                 if (isset($optionIdMap[$mapKey])) {
                     $combinationStringParts[] = $mapKey;
                     $optionIdsForCombination[] = $optionIdMap[$mapKey];
                 } else {
                      Log::warning("Opsi tidak ditemukan saat sinkronisasi: {$mapKey} for product ID: {$product->id}");
                      continue 2;
                 }
            }

            if (count($optionIdsForCombination) !== count($variantOptions)) continue;

            $combinationString = implode(';', $combinationStringParts);

             $variant = ProductVariant::updateOrCreate(
                 ['product_id' => $product->id, 'combination_string' => $combinationString],
                 [
                     'price' => $comboData['price'] ?? 0,
                     'stock' => $comboData['stock'] ?? 0,
                     'sku_code' => $comboData['sku_code'] ?? null,
                 ]
             );
            $currentVariantIds[] = $variant->id;

            if ($variant) {
                try {
                    $variant->options()->sync($optionIdsForCombination);
                } catch (Exception $e) {
                     Log::error("Gagal sync options ke variant {$variant->id}: " . $e->getMessage());
                     throw $e;
                }
            }
        }
         $variantsToDelete = ProductVariant::where('product_id', $product->id)->whereNotIn('id', $currentVariantIds)->get();
         foreach($variantsToDelete as $variantToDel) {
            $variantToDel->options()->detach();
            $variantToDel->delete();
         }
    }

} // End of ProductController class

