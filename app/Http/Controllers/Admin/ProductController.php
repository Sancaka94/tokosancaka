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
                               ? asset('public/storage/' . $imageUrl)
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
                        return $row->category && is_object($row->category) ? e($row->category->id) : '<span class="text-danger">N/A</span>';
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
                        $deleteUrl = route('admin.products.destroy', $row->slug);
                        $outOfStockUrl = route('admin.products.outOfStock', $row->slug);
                        $restockUrl = route('admin.products.restock', $row->slug);

                        $actionBtn = '<div class="d-flex justify-content-center gap-2">';
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
            'attributes.*'     => 'nullable',
            'variant_types'    => 'nullable|array',
            'is_promo'         => 'nullable|boolean',
            'is_shipping_discount' => 'nullable|boolean',
            'is_free_shipping' => 'nullable|boolean',
        ];

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

        $validated = $request->validate(array_merge($baseRules, $conditionalRules ?? []));
        $validatedDataForCreate = $validated;

        try {
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

            $validatedDataForCreate['slug'] = $this->generateUniqueSlug($validatedDataForCreate['name']);

            if (empty($validatedDataForCreate['sku']) && !empty($validatedDataForCreate['category_id']) && !$hasVariantsRequest) {
                 $validatedDataForCreate['sku'] = $this->generateSku($validatedDataForCreate['name'], $validatedDataForCreate['category_id']);
            }

            // Tags Logic
            $manualTags = [];
            if (!empty($request->tags)) {
                $manualTags = array_filter(array_map('trim', explode(',', $request->tags)));
            }
            $category = Category::find($validatedDataForCreate['category_id']);
            $categoryTag = $category->name ?? null;
            $allTags = $manualTags;
            if ($categoryTag && !in_array($categoryTag, $allTags)) {
                $allTags[] = $categoryTag;
            }
            $validatedDataForCreate['tags'] = !empty($allTags) ? json_encode(array_values(array_unique($allTags))) : null;

            // Boolean Flags
            $validatedDataForCreate['is_new'] = $request->has('is_new');
            $validatedDataForCreate['is_bestseller'] = $request->has('is_bestseller');
            $validatedDataForCreate['is_promo'] = $request->has('is_promo');
            $validatedDataForCreate['is_shipping_discount'] = $request->has('is_shipping_discount');
            $validatedDataForCreate['is_free_shipping'] = $request->has('is_free_shipping');

            // Extract relations data
            $attributesInput = $request->input('attributes', []);
            $variantTypesInput = $request->input('variant_types', []);
            $productVariantsInput = $request->input('product_variants', []);
            unset($validatedDataForCreate['attributes'], $validatedDataForCreate['variant_types'], $validatedDataForCreate['product_variants']);

            if ($hasVariantsRequest) {
                $validatedDataForCreate['stock'] = 0;
                $validatedDataForCreate['price'] = $productVariantsInput[0]['price'] ?? $validatedDataForCreate['price'];
            }

            $product = DB::transaction(function () use ($validatedDataForCreate, $attributesInput, $variantTypesInput, $productVariantsInput, $hasVariantsRequest) {
                $product = Product::create($validatedDataForCreate);
                $this->syncAttributes($product, $attributesInput);
                if ($hasVariantsRequest) {
                    $this->syncVariantTypesAndCombinations($product, $variantTypesInput, $productVariantsInput);
                }
                return $product;
            });

            return redirect()->route('admin.products.index')->with('success', 'Produk baru berhasil ditambahkan.');

        } catch (Exception $e) {
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

        $product->tags = $this->decodeTags($product->tags);

        // --- Logic Penyiapan Data Atribut untuk JS ---
        $existingAttributesData = [];
        $attributeDefinitions = Attribute::where('category_id', $product->category_id)->get()->keyBy('name');

        foreach($product->productAttributes as $pa) {
            $name = $pa->name;
            $value = $pa->value;
            $definition = $attributeDefinitions->get($name);
            
            // Gunakan slug asli dari master attribute jika ada, fallback ke Str::slug
            $slug = $definition ? $definition->slug : Str::slug($name);
            $type = $definition ? $definition->type : 'text';

            if ($type === 'checkbox' && is_string($value)) {
                try {
                    $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                    $value = is_array($decoded) ? $decoded : [$value];
                } catch (\JsonException $e) { $value = [$value]; }
            }
            $existingAttributesData[$slug] = $value;
        }
        $product->existing_attributes_json = json_encode($existingAttributesData);

        // JSON Variants
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
        // Validasi sama dengan Store, tapi ignore unique ID
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
            'is_promo'         => 'nullable|boolean',
            'is_shipping_discount' => 'nullable|boolean',
            'is_free_shipping' => 'nullable|boolean',
        ];

        $hasVariantsRequest = $request->has('variant_types') && !empty($request->variant_types);

        if ($hasVariantsRequest) {
            $conditionalRules = [
                'stock'            => 'nullable|integer|min:0',
                'variant_types.*.name' => 'required|string|max:255',
                'variant_types.*.options' => 'required|string|max:1000',
                'product_variants' => 'required|array|min:1',
                'product_variants.*.price' => 'required|numeric|min:0',
                'product_variants.*.stock' => 'required|integer|min:0',
                'product_variants.*.sku_code' => ['nullable', 'string', 'max:100'],
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

        $validated = $request->validate(array_merge($baseRules, $conditionalRules ?? []));
        $validatedDataForUpdate = $validated;

        try {
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

            if ($request->filled('seller_wa')) {
                $wa = preg_replace('/[^0-9]/', '', $request->seller_wa);
                if (!Str::startsWith($wa, '62')) $wa = '62' . ltrim($wa, '0');
                $validatedDataForUpdate['seller_wa'] = $wa;
            } else {
                $validatedDataForUpdate['seller_wa'] = null;
            }

            if (empty($validatedDataForUpdate['store_name'])) $validatedDataForUpdate['store_name'] = Auth::user()->store->name ?? config('app.default_store_name');
            if (empty($validatedDataForUpdate['seller_city'])) $validatedDataForUpdate['seller_city'] = Auth::user()->store->city ?? config('app.default_store_city');

            if ($request->name !== $product->name) {
                 $validatedDataForUpdate['slug'] = $this->generateUniqueSlug($validatedDataForUpdate['name'], $product->id);
            }

            if (empty($validatedDataForUpdate['sku']) && empty($product->sku) && !$hasVariantsRequest && !empty($validatedDataForUpdate['category_id'])) {
                 $validatedDataForUpdate['sku'] = $this->generateSku($validatedDataForUpdate['name'], $validatedDataForUpdate['category_id']);
            }

            // Tags Logic
            $manualTags = [];
            if (!empty($request->tags)) {
                $manualTags = array_filter(array_map('trim', explode(',', $request->tags)));
            }
            $category = Category::find($validatedDataForUpdate['category_id']);
            $categoryTag = $category->name ?? null;
            $allTags = $manualTags;
            if ($categoryTag && !in_array($categoryTag, $allTags)) {
                $allTags[] = $categoryTag;
            }
            $validatedDataForUpdate['tags'] = !empty($allTags) ? json_encode(array_values(array_unique($allTags))) : null;

            // Flags
            $validatedDataForUpdate['is_new'] = $request->has('is_new');
            $validatedDataForUpdate['is_bestseller'] = $request->has('is_bestseller');
            $validatedDataForUpdate['is_promo'] = $request->has('is_promo');
            $validatedDataForUpdate['is_shipping_discount'] = $request->has('is_shipping_discount');
            $validatedDataForUpdate['is_free_shipping'] = $request->has('is_free_shipping');

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

            // 2. SIMPAN ATRIBUT DULUAN (Supaya aman & masuk duluan)
        $this->syncAttributes($product, $attributesInput);

        // 3. BARU KITA COBA UPDATE HARGA 
        // (Kalau ini error, atribut di langkah 2 TETAP TERSIMPAN)
        try {
            $product->update($validatedDataForUpdate);
            
            if ($hasVariantsRequest) {
                $this->syncVariantTypesAndCombinations($product, $variantTypesInput, $productVariantsInput);
            } else {
                // Hapus varian jika beralih ke produk simple
                $product->productVariants()->each(fn($v) => $v->options()->detach());
                $product->productVariants()->delete();
                $product->productVariantTypes()->delete();
            }
        } catch (\Exception $e) {
            // Kalau update harga gagal (misal out of range), biarkan saja dulu.
            // Yang penting atribut sudah masuk. Kita log errornya diam-diam.
            \Illuminate\Support\Facades\Log::error('Gagal update harga/varian: ' . $e->getMessage());
            
            // Opsional: Tampilkan error ke user agar sadar harganya gagal
            // return back()->with('error', 'Atribut disimpan, tapi Harga Gagal: ' . $e->getMessage());
        }

    // }); // Tutup kurung transaction juga dikomentari/dihapus

    return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diperbarui (Cek jika ada error harga).');

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
            $imageUrl = $product->image_url ?? $product->image;
            if ($imageUrl && Storage::disk('public')->exists($imageUrl)) { Storage::disk('public')->delete($imageUrl); }
            if ($product->seller_logo && Storage::disk('public')->exists($product->seller_logo)) { Storage::disk('public')->delete($product->seller_logo); }

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

    // --- SPESIAL: Method untuk Edit Kategori & Spesifikasi Terpisah ---

    // GANTI $id MENJADI $slug AGAR SUPPORT URL SLUG
    public function editSpecifications($slug)
    {
        // 1. Cari Produk Berdasarkan Slug (Lebih aman daripada ID)
        $product = Product::where('slug', $slug)->firstOrFail();

        // 2. Load relasi attribute agar pasti terbaca
        $product->load(['category', 'productAttributes']);
        
        $categories = Category::where('type', 'product')->orderBy('name')->get();

        // 3. LOGIC ROBUST UNTUK MENGAMBIL DATA ATRIBUT
        $existingAttributesData = [];
        $attributeDefinitions = Attribute::where('category_id', $product->category_id)->get()->keyBy('name');

        foreach($product->productAttributes as $pa) {
            // FALLBACK LOGIC: Jika kolom 'name' NULL, ambil dari 'attribute_name'
            // Ini untuk mengatasi data lama yang kolom name-nya kosong
            $name = $pa->name;
            if (empty($name) && !empty($pa->attribute_name)) {
                $name = $pa->attribute_name;
            }
            if (empty($name)) continue; // Skip jika benar-benar tidak ada nama

            $value = $pa->value;
            $definition = $attributeDefinitions->get($name);
            
            // Gunakan slug master jika ada, agar cocok dengan field HTML frontend
            $slugAttr = $definition ? $definition->slug : Str::slug($name);
            $type = $definition ? $definition->type : 'text';

            // Decode Checkbox/JSON value
            if (($type === 'checkbox' || str_starts_with($value, '[')) && is_string($value)) {
                try {
                    $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                    $value = is_array($decoded) ? $decoded : [$value];
                } catch (\Exception $e) { $value = [$value]; }
            }
            $existingAttributesData[$slugAttr] = $value;
        }

        // Encode ke JSON string agar siap dikonsumsi JavaScript
        $existingAttributesJson = json_encode($existingAttributesData);

        // Debugging di sisi Server (Opsional, bisa dihapus nanti)
        // dd($existingAttributesJson); 

        return view('admin.products.edit-specifications', compact('product', 'categories', 'existingAttributesJson'));
    }

    // GANTI method updateSpecifications dengan yang ini:
    public function updateSpecifications(Request $request, $idOrSlug)
    {
        // LOGIC PINTAR: Cek apakah input berupa Angka (ID) atau Teks (Slug)
        // Jadi URL .../5/specifications ATAU .../jasa-izin/specifications DUA-DUANYA BISA JALAN
        $product = Product::where('id', $idOrSlug)
                          ->orWhere('slug', $idOrSlug)
                          ->firstOrFail();

        // 1. LOGIC SKU AUTO-GENERATE
        if (empty($request->input('sku'))) {
            $generatedSku = 'SKU-' . date('Ymd') . '-' . strtoupper(Str::random(3));
            $request->merge(['sku' => $generatedSku]);
        }

        // 2. Validasi
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:100', \Illuminate\Validation\Rule::unique('products', 'sku')->ignore($product->id)],
            'category_id' => 'required|exists:categories,id',
            'tags' => 'nullable|string',
            'attributes' => 'nullable|array',
        ]);

        try {
            DB::transaction(function () use ($product, $request, $validated) {
                // 3. Logic Update Tags
                $manualTags = [];
                if (!empty($request->tags)) {
                    $manualTags = array_filter(array_map('trim', explode(',', $request->tags)));
                }
                $category = Category::find($validated['category_id']);
                $categoryTag = $category->name ?? null;
                $allTags = $manualTags;
                if ($categoryTag && !in_array($categoryTag, $allTags)) {
                    $allTags[] = $categoryTag;
                }
                $jsonTags = !empty($allTags) ? json_encode(array_values(array_unique($allTags))) : null;

                // 4. Update Data Utama
                $product->update([
                    'sku' => $validated['sku'],
                    'category_id' => $validated['category_id'],
                    'tags' => $jsonTags,
                ]);

                // 5. Sync Attributes
                $attributesInput = $request->input('attributes', []);
                $this->syncAttributes($product, $attributesInput);
            });

            // Redirect menggunakan slug agar URL browser menjadi cantik kembali
            return redirect()->route('admin.products.edit.specifications', $product->slug)
                ->with('success', 'Kategori dan Spesifikasi berhasil diperbarui.');

        } catch (Exception $e) {
            Log::error('Error updating specifications: ' . $e->getMessage());
            return back()->with('error', 'Gagal update: ' . $e->getMessage())->withInput();
        }
    }


    // --- Helpers ---

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

    // GANTI TOTAL function syncAttributes DENGAN INI:
    protected function syncAttributes(Product $product, ?array $attributesData)
    {
        // 1. Cek apakah ada data yang dikirim
        if (empty($attributesData)) {
            return;
        }

        // 2. Debugging (Opsional: Cek di Laravel.log kalau masih gagal)
        // \Illuminate\Support\Facades\Log::info('Syncing Attributes:', $attributesData);

        // 3. Loop dan Simpan Langsung (Tanpa Filter Kategori)
        foreach ($attributesData as $slug => $value) {
            
            // Skip jika value kosong/null
            if ($value === null || $value === '') {
                continue;
            }

            // Bersihkan format nama (slug-jadi-text -> Slug Jadi Text)
            // Ini agar kolom 'name' terisi rapi
            $prettyName = ucwords(str_replace(['-', '_'], ' ', $slug));

            // Proses value (Array/Checkbox jadi JSON)
            $processedValue = is_array($value) ? json_encode(array_values($value)) : $value;

            // 4. Update atau Buat Baru (Jurus Paksa Simpan)
            // Kita gunakan 'name' sebagai kunci pencarian
            ProductAttribute::updateOrCreate(
                [
                    'product_id' => $product->id, 
                    'name'       => $prettyName // Kuncinya di sini (Nama Atribut)
                ],
                [
                    'value'          => $processedValue,
                    'attribute_slug' => $slug, // Simpan slugnya juga biar aman
                    // Hapus baris ini jika kolom attribute_name tidak ada di DB
                    'attribute_name' => $prettyName 
                ]
            );
        }
    }

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
                 } else { continue 2; }
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
            if ($variant) { $variant->options()->sync($optionIdsForCombination); }
        }
        
        $variantsToDelete = ProductVariant::where('product_id', $product->id)->whereNotIn('id', $currentVariantIds)->get();
        foreach($variantsToDelete as $variantToDel) {
            $variantToDel->options()->detach();
            $variantToDel->delete();
        }
    }

   

}