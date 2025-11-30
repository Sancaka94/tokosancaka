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
use Yajra\DataTables\Facades\DataTables;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Menampilkan halaman manajemen produk.
     */
    public function index(Request $request)
    {
        $categories = Category::where('type', 'product')->orderBy('name')->get(['name', 'slug']);
        $query = Product::with('category');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $categorySlug = $request->category;
            $query->whereHas('category', function($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        $products = $query->latest()->paginate(10);
        return view('admin.products.index', compact('categories', 'products'));
    }

    /**
     * Menyediakan data untuk Yajra DataTables.
     */
    public function getData(Request $request)
    {
        if ($request->ajax()) {
            try {
                $categorySlug = $request->input('category_slug');

                $data = Product::with(['category', 'productVariantTypes', 'productVariants'])
                        ->when($categorySlug, function ($query, $slug) {
                            $query->whereHas('category', function($q) use ($slug) {
                                $q->where('slug', $slug);
                            });
                        })
                        ->select('products.*');

                return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('image', function ($row) {
                        $imageUrl = $row->image_url ?? $row->image;
                        $url = $imageUrl && Storage::disk('public')->exists($imageUrl)
                               ? asset('storage/' . $imageUrl)
                               : 'https://placehold.co/80x80/EFEFEF/333333?text=N/A';
                        return '<img src="' . e($url) . '" alt="' . e($row->name) . '" class="rounded" width="60" loading="lazy" />';
                    })
                    ->editColumn('price', function ($row) {
                        $displayPrice = $row->price;
                        if($row->relationLoaded('productVariants') && $row->productVariants->isNotEmpty()){
                             $displayPrice = $row->productVariants->first()->price ?? $row->price;
                        }
                        return 'Rp' . number_format($displayPrice, 0, ',', '.');
                    })
                    ->addColumn('category_id', function ($row) {
                        return $row->category ? e($row->category->name) : '<span class="text-danger">N/A</span>';
                    })
                    ->addColumn('has_variants', function($row) {
                        return $row->productVariantTypes && $row->productVariantTypes->isNotEmpty();
                    })
                    ->addColumn('status_badge', function ($row) {
                        $color = $row->status == 'active' ? 'bg-success' : 'bg-secondary';
                        $text = $row->status == 'active' ? 'Aktif' : 'Nonaktif';
                        return '<span class="badge ' . e($color) . '">' . e($text) . '</span>';
                    })
                    ->editColumn('stock', function ($row) {
                        $stockDisplay = $row->stock ?? 0;
                        if ($row->has_variants) {
                            $stockDisplay = ($row->stock ?? 0) . ' <i class="fas fa-code-branch variant-indicator" title="Produk ini memiliki varian"></i>';
                        }
                        return $stockDisplay;
                    })
                    ->addColumn('action', function($row){
                        $editUrl = route('admin.products.edit', $row->slug);
                        $specUrl = route('admin.products.edit.specifications', $row->slug);
                        $deleteUrl = route('admin.products.destroy', $row->slug);
                        $outOfStockUrl = route('admin.products.outOfStock', $row->slug);
                        $restockUrl = route('admin.products.restock', $row->slug);

                        $actionBtn = '<div class="d-flex justify-content-center gap-2">';
                        
                        // Tombol Restock & Habis (Hanya untuk Simple Product)
                        if (!$row->has_variants) {
                             $actionBtn .= '<button type="button" onclick="openRestockModal(\''.e($restockUrl).'\', \''.e($row->name).'\')" class="btn btn-success btn-circle btn-sm" title="Restock"><i class="fas fa-plus"></i></button>';
                             $actionBtn .= '<form action="'.e($outOfStockUrl).'" method="POST" class="d-inline" onsubmit="return confirm(\'Anda yakin ingin menandai produk ini habis?\');">'.csrf_field().method_field('PATCH').'<button type="submit" class="btn btn-secondary btn-circle btn-sm" title="Tandai Habis"><i class="fas fa-box-open"></i></button></form>';
                        } else {
                             $actionBtn .= '<button type="button" class="btn btn-outline-secondary btn-circle btn-sm disabled" title="Atur stok via Edit Varian"><i class="fas fa-plus"></i></button>';
                             $actionBtn .= '<button type="button" class="btn btn-outline-secondary btn-circle btn-sm disabled" title="Atur stok via Edit Varian"><i class="fas fa-box-open"></i></button>';
                        }

                        // Tombol Edit Spesifikasi
                        $actionBtn .= '<a href="'.e($specUrl).'" class="btn btn-info btn-circle btn-sm" title="Edit Spesifikasi"><i class="fas fa-list-check"></i></a>';
                        
                        // Tombol Edit Utama
                        $actionBtn .= '<a href="'.e($editUrl).'" class="btn btn-warning btn-circle btn-sm" title="Edit"><i class="fas fa-pen-to-square"></i></a>';
                        
                        // Tombol Hapus
                        $actionBtn .= '<form action="'.e($deleteUrl).'" method="POST" class="d-inline" onsubmit="return confirm(\'Anda yakin ingin menghapus produk ini?\');">'.csrf_field().method_field('DELETE').'<button type="submit" class="btn btn-danger btn-circle btn-sm" title="Hapus"><i class="fas fa-trash"></i></button></form>';
                        
                        $actionBtn .= '</div>';

                        return $actionBtn;
                    })
                    ->rawColumns(['action', 'image', 'status_badge', 'stock', 'category_id'])
                    ->make(true);

            } catch (Exception $e) {
                Log::error('DataTables Error: ' . $e->getMessage());
                return response()->json(['error' => 'Could not process data.', 'message' => $e->getMessage()], 500);
            }
        }
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
            'variant_types'    => 'nullable|array',
            'is_promo'         => 'nullable|boolean',
            'is_shipping_discount' => 'nullable|boolean',
            'is_free_shipping' => 'nullable|boolean',
        ];

        $hasVariantsRequest = $request->has('variant_types') && !empty($request->variant_types);

        if ($hasVariantsRequest) {
            $conditionalRules = [
                'stock'                    => 'nullable|integer|min:0',
                'variant_types.*.name'     => 'required|string|max:255',
                'variant_types.*.options'  => 'required|string|max:1000',
                'product_variants'         => 'required|array|min:1',
                'product_variants.*.price' => 'required|numeric|min:0',
                'product_variants.*.stock' => 'required|integer|min:0',
                'product_variants.*.sku_code' => ['nullable', 'string', 'max:100', Rule::unique('product_variants', 'sku_code')],
                'product_variants.*.variant_options' => 'required|array|min:1',
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
            DB::beginTransaction();

            // 1. Handle File Uploads
            if ($request->hasFile('product_image')) {
                $path = $request->file('product_image')->store('products', 'public');
                $validatedDataForCreate['image_url'] = $path;
            }
            unset($validatedDataForCreate['product_image']);

            if ($request->hasFile('seller_logo')) {
                $logoPath = $request->file('seller_logo')->store('seller_logos', 'public');
                $validatedDataForCreate['seller_logo'] = $logoPath;
            }
            unset($validatedDataForCreate['seller_logo']);

            // 2. Format Data Seller
            if (!empty($request->seller_wa)) {
                $wa = preg_replace('/[^0-9]/', '', $request->seller_wa);
                if (!Str::startsWith($wa, '62')) $wa = '62' . ltrim($wa, '0');
                $validatedDataForCreate['seller_wa'] = $wa;
            }

            if (empty($validatedDataForCreate['store_name'])) {
                $validatedDataForCreate['store_name'] = Auth::user()->store->name ?? config('app.default_store_name', 'Toko Sancaka Default');
            }
            if (empty($validatedDataForCreate['seller_city'])) {
                $validatedDataForCreate['seller_city'] = Auth::user()->store->city ?? config('app.default_store_city', 'Ngawi');
            }

            // 3. Slug & SKU
            $validatedDataForCreate['slug'] = $this->generateUniqueSlug($validatedDataForCreate['name']);

            if (empty($validatedDataForCreate['sku']) && !empty($validatedDataForCreate['category_id']) && !$hasVariantsRequest) {
                 $validatedDataForCreate['sku'] = $this->generateSku($validatedDataForCreate['name'], $validatedDataForCreate['category_id']);
            }

            // 4. Tags Logic (Process String to JSON)
            $validatedDataForCreate['tags'] = $this->processTagsToJson($request->tags, $validatedDataForCreate['category_id']);

            // 5. Boolean Flags
            $validatedDataForCreate['is_new'] = $request->has('is_new');
            $validatedDataForCreate['is_bestseller'] = $request->has('is_bestseller');
            $validatedDataForCreate['is_promo'] = $request->has('is_promo');
            $validatedDataForCreate['is_shipping_discount'] = $request->has('is_shipping_discount');
            $validatedDataForCreate['is_free_shipping'] = $request->has('is_free_shipping');

            // 6. Extract Relations Input
            $attributesInput = $request->input('attributes', []);
            $variantTypesInput = $request->input('variant_types', []);
            $productVariantsInput = $request->input('product_variants', []);
            
            unset($validatedDataForCreate['attributes'], $validatedDataForCreate['variant_types'], $validatedDataForCreate['product_variants']);

            if ($hasVariantsRequest) {
                $validatedDataForCreate['stock'] = 0;
                $validatedDataForCreate['price'] = $productVariantsInput[0]['price'] ?? $validatedDataForCreate['price'];
            }

            // 7. Create Product & Sync
            $product = Product::create($validatedDataForCreate);
            
            $this->syncAttributes($product, $attributesInput);
            
            if ($hasVariantsRequest) {
                $this->syncVariantTypesAndCombinations($product, $variantTypesInput, $productVariantsInput);
            }

            DB::commit();
            return redirect()->route('admin.products.index')->with('success', 'Produk baru berhasil ditambahkan.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error saving product: ' . $e->getMessage());
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
            'productAttributes',
            'productVariantTypes.options' => fn ($q) => $q->orderBy('id'),
            'productVariants.options' => fn ($q) => $q->orderBy('product_variant_type_id')->orderBy('id')
        ]);

        $product->tags = $this->decodeTagsToString($product->tags);

        // Logic Penyiapan Data Atribut untuk Form Standard (Compatibility)
        $existingAttributesData = [];
        $attributeDefinitions = Attribute::where('category_id', $product->category_id)->get()->keyBy('name');

        foreach($product->productAttributes as $pa) {
            $name = $pa->name;
            $value = $pa->value;
            $definition = $attributeDefinitions->get($name);
            $slug = $definition ? $definition->slug : Str::slug($name);
            $type = $definition ? $definition->type : 'text';

            if (($type === 'checkbox' || str_starts_with($value, '[')) && is_string($value)) {
                try {
                    $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                    $value = is_array($decoded) ? $decoded : [$value];
                } catch (\Exception $e) { $value = [$value]; }
            }
            $existingAttributesData[$slug] = $value;
        }
        $product->existing_attributes_json = json_encode($existingAttributesData);

        // JSON Variants for JS
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
            'variant_types'    => 'nullable|array',
            'is_promo'         => 'nullable|boolean',
            'is_shipping_discount' => 'nullable|boolean',
            'is_free_shipping' => 'nullable|boolean',
        ];

        $hasVariantsRequest = $request->has('variant_types') && !empty($request->variant_types);

        if ($hasVariantsRequest) {
            $conditionalRules = [
                'stock'                    => 'nullable|integer|min:0',
                'variant_types.*.name'     => 'required|string|max:255',
                'variant_types.*.options'  => 'required|string|max:1000',
                'product_variants'         => 'required|array|min:1',
                'product_variants.*.price' => 'required|numeric|min:0',
                'product_variants.*.stock' => 'required|integer|min:0',
                'product_variants.*.sku_code' => ['nullable', 'string', 'max:100'],
                'product_variants.*.variant_options' => 'required|array|min:1',
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
            DB::transaction(function () use ($request, $product, $validatedDataForUpdate, $hasVariantsRequest) {
                
                // 1. Handle File Update
                if ($request->hasFile('product_image')) {
                    if ($product->image_url && Storage::disk('public')->exists($product->image_url)) {
                        Storage::disk('public')->delete($product->image_url);
                    }
                    $validatedDataForUpdate['image_url'] = $request->file('product_image')->store('products', 'public');
                }
                unset($validatedDataForUpdate['product_image']);

                if ($request->hasFile('seller_logo')) {
                    if ($product->seller_logo && Storage::disk('public')->exists($product->seller_logo)) {
                        Storage::disk('public')->delete($product->seller_logo);
                    }
                    $validatedDataForUpdate['seller_logo'] = $request->file('seller_logo')->store('seller_logos', 'public');
                }
                unset($validatedDataForUpdate['seller_logo']);

                // 2. Handle Seller Data
                if ($request->filled('seller_wa')) {
                    $wa = preg_replace('/[^0-9]/', '', $request->seller_wa);
                    if (!Str::startsWith($wa, '62')) $wa = '62' . ltrim($wa, '0');
                    $validatedDataForUpdate['seller_wa'] = $wa;
                } else {
                    $validatedDataForUpdate['seller_wa'] = null;
                }

                if (empty($validatedDataForUpdate['store_name'])) $validatedDataForUpdate['store_name'] = Auth::user()->store->name ?? config('app.default_store_name');
                if (empty($validatedDataForUpdate['seller_city'])) $validatedDataForUpdate['seller_city'] = Auth::user()->store->city ?? config('app.default_store_city');

                // 3. Slug & SKU
                if ($request->name !== $product->name) {
                     $validatedDataForUpdate['slug'] = $this->generateUniqueSlug($validatedDataForUpdate['name'], $product->id);
                }

                if (empty($validatedDataForUpdate['sku']) && empty($product->sku) && !$hasVariantsRequest && !empty($validatedDataForUpdate['category_id'])) {
                     $validatedDataForUpdate['sku'] = $this->generateSku($validatedDataForUpdate['name'], $validatedDataForUpdate['category_id']);
                }

                // 4. Tags
                $validatedDataForUpdate['tags'] = $this->processTagsToJson($request->tags, $validatedDataForUpdate['category_id']);

                // 5. Flags
                $validatedDataForUpdate['is_new'] = $request->has('is_new');
                $validatedDataForUpdate['is_bestseller'] = $request->has('is_bestseller');
                $validatedDataForUpdate['is_promo'] = $request->has('is_promo');
                $validatedDataForUpdate['is_shipping_discount'] = $request->has('is_shipping_discount');
                $validatedDataForUpdate['is_free_shipping'] = $request->has('is_free_shipping');

                // Extract Inputs
                $attributesInput = $request->input('attributes', []);
                $variantTypesInput = $request->input('variant_types', []);
                $productVariantsInput = $request->input('product_variants', []);
                
                unset($validatedDataForUpdate['attributes'], $validatedDataForUpdate['variant_types'], $validatedDataForUpdate['product_variants']);

                if ($hasVariantsRequest) {
                    $validatedDataForUpdate['stock'] = 0;
                    $validatedDataForUpdate['price'] = $productVariantsInput[0]['price'] ?? $validatedDataForUpdate['price'];
                } else {
                    $validatedDataForUpdate['stock'] = $request->input('stock', $product->stock);
                    $validatedDataForUpdate['price'] = $request->input('price', $product->price);
                }

                // 6. Update Product
                $product->update($validatedDataForUpdate);

                // 7. Sync Relations
                $this->syncAttributes($product, $attributesInput);

                if ($hasVariantsRequest) {
                    $this->syncVariantTypesAndCombinations($product, $variantTypesInput, $productVariantsInput);
                } else {
                    // Hapus varian jika switch ke Simple Product
                    $product->productVariants()->each(fn($v) => $v->options()->detach());
                    $product->productVariants()->delete();
                    $product->productVariantTypes()->delete();
                }
            });

            return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diperbarui.');

        } catch (Exception $e) {
            Log::error('Error updating product: ' . $e->getMessage());
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
            if ($product->image_url && Storage::disk('public')->exists($product->image_url)) { 
                Storage::disk('public')->delete($product->image_url); 
            }
            if ($product->seller_logo && Storage::disk('public')->exists($product->seller_logo)) { 
                Storage::disk('public')->delete($product->seller_logo); 
            }

            // Hapus atribut & varian (jika tidak cascade di database)
            $product->productAttributes()->delete();
            $product->productVariants()->each(fn($v) => $v->options()->detach());
            $product->productVariants()->delete();
            $product->productVariantTypes()->delete();

            $product->delete();
            DB::commit();
            return redirect()->route('admin.products.index')->with('success', 'Produk berhasil dihapus.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting product: ' . $e->getMessage());
            return redirect()->route('admin.products.index')->with('error', 'Gagal menghapus produk.');
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
            return redirect()->route('admin.products.index')->with('success', 'Stok berhasil ditambahkan.');
        } catch (Exception $e) {
             return redirect()->route('admin.products.index')->with('error', 'Gagal restock produk.');
        }
    }

    /**
     * Menandai produk (tanpa varian) sebagai habis.
     */
    public function markAsOutOfStock(Product $product)
    {
        if ($product->productVariantTypes()->exists()) {
             return redirect()->route('admin.products.index')->with('error', 'Gunakan halaman edit untuk mengatur stok varian.');
        }
        try {
            $product->stock = 0;
            $product->save();
            return redirect()->route('admin.products.index')->with('success', 'Stok diatur menjadi 0.');
        } catch (Exception $e) {
             return redirect()->route('admin.products.index')->with('error', 'Gagal update stok.');
        }
    }

    // =========================================================================
    //  BAGIAN SPESIFIKASI KHUSUS (FITUR DINAMIS)
    // =========================================================================

    /**
     * Menampilkan halaman edit spesifikasi khusus.
     */
    public function editSpecifications($idOrSlug)
    {
        $product = Product::where('id', $idOrSlug)->orWhere('slug', $idOrSlug)->firstOrFail();
        $product->load(['category', 'productAttributes']);
        
        $categories = Category::where('type', 'product')->orderBy('name')->get();

        // Persiapkan Data Atribut Lama (Existing) untuk JavaScript
        $existingAttributesData = [];
        
        foreach($product->productAttributes as $pa) {
            $key = $pa->attribute_slug ?? Str::slug($pa->name); 
            $value = $pa->value;
            
            // Coba deteksi JSON Array (Checkbox)
            if (is_string($value) && str_starts_with(trim($value), '[')) {
                try {
                    $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                    $value = is_array($decoded) ? $decoded : $value;
                } catch (\Exception $e) {}
            }
            $existingAttributesData[$key] = $value;
        }

        $existingAttributesJson = json_encode($existingAttributesData);

        return view('admin.products.edit-specifications', compact('product', 'categories', 'existingAttributesJson'));
    }

    /**
     * Menyimpan update spesifikasi (Form Dinamis).
     */
    public function updateSpecifications(Request $request, $idOrSlug)
    {
        $product = Product::where('id', $idOrSlug)->orWhere('slug', $idOrSlug)->firstOrFail();

        // Generate SKU jika kosong
        if (empty($request->input('sku'))) {
            $catId = $request->input('category_id', $product->category_id);
            $request->merge(['sku' => $this->generateSku($product->name, $catId)]);
        }

        $validated = $request->validate([
            'sku'         => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product->id)],
            'category_id' => 'required|exists:categories,id',
            'tags'        => 'nullable|string',
            'attributes'  => 'nullable|array',
        ]);

        try {
            DB::transaction(function () use ($product, $request, $validated) {
                // 1. Update Product Basic Data
                $product->update([
                    'sku'         => $validated['sku'],
                    'category_id' => $validated['category_id'],
                    'tags'        => $this->processTagsToJson($request->tags, $validated['category_id']),
                ]);

                // 2. Sync Attributes
                $attributesInput = $request->input('attributes', []);
                $this->syncAttributes($product, $attributesInput);
            });

            return redirect()->route('admin.products.edit.specifications', $product->slug)
                ->with('success', 'Data produk dan spesifikasi berhasil diperbarui!');

        } catch (Exception $e) {
            Log::error('Error updating specifications: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    // =========================================================================
    //  HELPER FUNCTIONS
    // =========================================================================

    /**
     * Helper: Sync Attributes (Handle JSON for Checkboxes)
     */
    protected function syncAttributes(Product $product, ?array $attributesData)
    {
        if (empty($attributesData)) {
            $product->productAttributes()->delete();
            return;
        }

        $processedSlugs = [];

        foreach ($attributesData as $slug => $value) {
            if ($value === null || $value === '') continue;

            // Cari Nama Cantik
            $attrDef = Attribute::where('slug', $slug)->first();
            $prettyName = $attrDef ? $attrDef->name : ucwords(str_replace(['-', '_'], ' ', $slug));

            // Jika value array (checkbox), jadikan JSON String
            $dbValue = is_array($value) ? json_encode($value) : $value;

            ProductAttribute::updateOrCreate(
                [
                    'product_id'     => $product->id,
                    'attribute_slug' => $slug
                ],
                [
                    'name'  => $prettyName,
                    'value' => $dbValue
                ]
            );

            $processedSlugs[] = $slug;
        }

        // Hapus atribut yang tidak ada di form submission
        if (!empty($processedSlugs)) {
            $product->productAttributes()
                    ->whereNotIn('attribute_slug', $processedSlugs)
                    ->delete();
        }
    }

    /**
     * Helper: Sync Variants
     */
    protected function syncVariantTypesAndCombinations(Product $product, ?array $variantTypesData, ?array $productVariantsData)
    {
        if (empty($variantTypesData) || empty($productVariantsData)) {
            $product->productVariants()->each(fn($v) => $v->options()->detach());
            $product->productVariants()->delete();
            $product->productVariantTypes()->delete();
            return;
        }

        $currentTypeIds = [];
        $optionIdMap = [];

        // 1. Types & Options
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

        // 2. Combinations
        $currentVariantIds = [];
        foreach ($productVariantsData as $comboData) {
            $variantOptions = $comboData['variant_options'] ?? [];
            if (empty($variantOptions)) continue;

            $combinationStringParts = [];
            $optionIdsForCombination = [];
            usort($variantOptions, fn($a, $b) => strcmp($a['type_name'], $b['type_name']));

            foreach ($variantOptions as $optionDetail) {
                 $mapKey = trim($optionDetail['type_name']) . ':' . trim($optionDetail['value']);
                 if (isset($optionIdMap[$mapKey])) {
                     $combinationStringParts[] = $mapKey;
                     $optionIdsForCombination[] = $optionIdMap[$mapKey];
                 } else { continue 2; }
            }

            if (count($optionIdsForCombination) !== count($variantOptions)) continue;

            $combinationString = implode(';', $combinationStringParts);
            $variant = ProductVariant::updateOrCreate(
                ['product_id' => $product->id, 'combination_string' => $combinationString],
                [
                    'price'    => $comboData['price'] ?? 0,
                    'stock'    => $comboData['stock'] ?? 0,
                    'sku_code' => $comboData['sku_code'] ?? null,
                ]
            );
            $currentVariantIds[] = $variant->id;
            if ($variant) { $variant->options()->sync($optionIdsForCombination); }
        }
        
        // Hapus varian sisa
        $variantsToDelete = ProductVariant::where('product_id', $product->id)->whereNotIn('id', $currentVariantIds)->get();
        foreach($variantsToDelete as $variantToDel) {
            $variantToDel->options()->detach();
            $variantToDel->delete();
        }
    }

    /**
     * Helper: Generate SKU
     */
    protected function generateSku(string $productName, int $categoryId): string
    {
        $category = Category::find($categoryId);
        $catCode = $category ? strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $category->name), 0, 3)) : 'GEN';
        $prodCode = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $productName), 0, 3));
        
        do {
            $random = mt_rand(100, 999);
            $sku = "{$catCode}-{$prodCode}-{$random}";
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }

    /**
     * Helper: Generate Unique Slug
     */
    protected function generateUniqueSlug(string $name, int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $count = 1;
        while (Product::where('slug', $slug)->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $original . '-' . $count++;
        }
        return $slug;
    }

    /**
     * Helper: Process Tags Input -> JSON
     */
    protected function processTagsToJson(?string $tagsInput, $categoryId)
    {
        if (empty($tagsInput)) return null;

        $tagsArray = array_values(array_unique(array_filter(array_map('trim', explode(',', $tagsInput)))));
        
        $category = Category::find($categoryId);
        if ($category && !in_array($category->name, $tagsArray)) {
            $tagsArray[] = $category->name;
        }

        return !empty($tagsArray) ? json_encode($tagsArray) : null;
    }

    /**
     * Helper: Decode JSON Tags -> String
     */
    protected function decodeTagsToString($tags): string
    {
         if (is_string($tags)) {
             try {
                 $decodedTags = json_decode($tags, true, 512, JSON_THROW_ON_ERROR);
                 return is_array($decodedTags) ? implode(', ', $decodedTags) : $tags;
             } catch (\JsonException $e) { return $tags; }
         } elseif (is_array($tags)) { return implode(', ', $tags); }
         return '';
    }
}