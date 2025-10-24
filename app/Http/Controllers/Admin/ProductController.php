<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Attribute; // Pastikan Model Attribute ada
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
                $data = Product::with(['category', 'productVariantTypes'])
                        ->when($categorySlug, function ($query, $slug) {
                            $query->whereHas('category', function($q) use ($slug) {
                                $q->where('slug', $slug);
                            });
                        })
                        ->select('products.*'); // Pilih semua kolom dari products

                return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('image', function ($row) {
                        // PERBAIKAN: Gunakan kolom image_url secara konsisten
                        $imageUrl = $row->image_url;
                        $url = $imageUrl ? asset('storage/' . $imageUrl) : 'https://placehold.co/80x80/EFEFEF/333333?text=N/A';
                        return '<img src="' . e($url) . '" alt="' . e($row->name) . '" class="rounded" width="60" loading="lazy" />'; // Tambah lazy loading
                    })
                    ->editColumn('price', function ($row) {
                         // Tampilkan harga varian pertama jika ada
                        $displayPrice = $row->price;
                        // Pastikan relasi productVariants dimuat jika ingin mengaksesnya di sini, atau gunakan pengecekan relasi
                        // if($row->relationLoaded('productVariants') && $row->productVariants->isNotEmpty()){
                        //      $displayPrice = $row->productVariants->first()->price ?? $row->price;
                        // }
                        return 'Rp' . number_format($displayPrice, 0, ',', '.');
                    })
                    ->addColumn('category_name', function ($row) {
                        return $row->category->name ?? '<span class="text-danger">N/A</span>'; // Tanda jika kategori tidak ada
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
                        $stockDisplay = $row->stock ?? 0; // Default 0 jika null
                        if ($row->has_variants) { // Gunakan kolom virtual has_variants
                            // Jika ada varian, tampilkan ikon (stok utama mungkin 0)
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
                            // Placeholder atau tombol nonaktif jika ada varian
                             $actionBtn .= '<button type="button" class="btn btn-outline-secondary btn-circle btn-sm disabled" title="Atur stok via Edit Varian"><i class="fas fa-plus"></i></button>';
                             $actionBtn .= '<button type="button" class="btn btn-outline-secondary btn-circle btn-sm disabled" title="Atur stok via Edit Varian"><i class="fas fa-box-open"></i></button>';
                        }
                        $actionBtn .= '<a href="'.e($editUrl).'" class="btn btn-warning btn-circle btn-sm" title="Edit"><i class="fas fa-pen-to-square"></i></a>';
                        $actionBtn .= '<form action="'.e($deleteUrl).'" method="POST" class="d-inline" onsubmit="return confirm(\'Anda yakin ingin menghapus produk ini?\');">'.csrf_field().method_field('DELETE').'<button type="submit" class="btn btn-danger btn-circle btn-sm" title="Hapus"><i class="fas fa-trash"></i></button></form>';
                        $actionBtn .= '</div>';

                        return $actionBtn;
                    })
                    // Pastikan stock juga diraw
                    ->rawColumns(['action', 'image', 'status_badge', 'stock', 'category_name'])
                    ->make(true);

            } catch (Exception $e) {
                Log::error('DataTables Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
                return response()->json(['error' => 'Could not process data.', 'message' => $e->getMessage()], 500);
            }
        }
         // Jika bukan AJAX, tampilkan view biasa (jika perlu)
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
         // Gabungkan validasi dasar dan varian
        $baseRules = [
            'name'             => ['required', 'string', 'max:255', Rule::unique('products', 'name')],
            'description'      => 'nullable|string',
            'price'            => 'required|numeric|min:0',
            'original_price'   => 'nullable|numeric|min:0|gte:price', // gte:price (>= price)
            'weight'           => 'required|integer|min:0',
            'category_id'      => 'required|exists:categories,id',
            'product_image'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // Validasi input file
            'store_name'       => 'nullable|string|max:255', // Jadi nullable
            'seller_city'      => 'nullable|string|max:255', // Jadi nullable
            'seller_name'      => 'nullable|string|max:255',
            'seller_wa'        => 'nullable|string|max:20',
            'seller_logo'      => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // Validasi input file
            'sku'              => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')],
            'tags'             => 'nullable|string',
            'status'           => 'required|in:active,inactive',
            'is_new'           => 'nullable|boolean',
            'is_bestseller'    => 'nullable|boolean',
            'length'           => 'nullable|numeric|min:0',
            'width'            => 'nullable|numeric|min:0',
            'height'           => 'nullable|numeric|min:0',
            'attributes'       => 'nullable|array', // Data atribut dari form dinamis
            'attributes.*'     => 'nullable', // Bisa array (checkbox) atau string
            'variant_types'    => 'nullable|array', // Data tipe varian dari form
        ];

        // Aturan validasi yang bergantung pada ada tidaknya varian
        $conditionalRules = [];
        if ($request->has('variant_types') && !empty($request->variant_types)) {
            $conditionalRules = [
                'stock'            => 'nullable|integer|min:0', // Stok utama jadi opsional (akan di-set 0)
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

        // --- Proses Data ---
        $validatedDataForCreate = $validated; // Buat salinan untuk data create produk
        try {
            if ($request->hasFile('product_image')) {
                // PERBAIKAN: Simpan ke 'image_url'
                $validatedDataForCreate['image_url'] = $request->file('product_image')->store('products', 'public');
            }
            // Hapus 'product_image' dari data create karena itu input file, bukan kolom DB
            unset($validatedDataForCreate['product_image']);

            if ($request->hasFile('seller_logo')) {
                $validatedDataForCreate['seller_logo'] = $request->file('seller_logo')->store('seller_logos', 'public');
            }
             // Hapus 'seller_logo' (input file) dari data create
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

            if (empty($validatedDataForCreate['sku']) && !empty($validatedDataForCreate['category_id']) && !$request->has('variant_types')) {
                 $validatedDataForCreate['sku'] = $this->generateSku($validatedDataForCreate['name'], $validatedDataForCreate['category_id']);
            }

            $manualTags = [];
            if (!empty($request->tags)) {
                $manualTags = array_map('trim', explode(',', $request->tags));
                $manualTags = array_filter($manualTags);
            }
            $categoryTag = Category::find($validatedDataForCreate['category_id'])->name ?? null;
            $allTags = $manualTags;
            if ($categoryTag && !in_array($categoryTag, $allTags)) {
                $allTags[] = $categoryTag;
            }
            $validatedDataForCreate['tags'] = !empty($allTags) ? json_encode(array_values(array_unique($allTags))) : null;

            $validatedDataForCreate['is_new'] = $request->has('is_new');
            $validatedDataForCreate['is_bestseller'] = $request->has('is_bestseller');

            // Hapus data yang tidak relevan untuk tabel products sebelum create
            unset($validatedDataForCreate['attributes']);
            unset($validatedDataForCreate['variant_types']);
            unset($validatedDataForCreate['product_variants']);


            $hasVariants = $request->has('variant_types') && !empty($request->variant_types);
            if ($hasVariants) {
                $validatedDataForCreate['stock'] = 0;
                 $validatedDataForCreate['price'] = $request->product_variants[0]['price'] ?? $validatedDataForCreate['price'];
            }


            $product = DB::transaction(function () use ($validatedDataForCreate, $request, $hasVariants) {
                // Buat Produk
                $product = Product::create($validatedDataForCreate);

                // Simpan Atribut
                $this->syncAttributes($product, $request->input('attributes', []));

                // Simpan Varian jika ada
                if ($hasVariants) {
                    $this->syncVariantTypesAndCombinations($product, $request->variant_types, $request->product_variants);
                }

                return $product;
            });

            return redirect()->route('admin.products.index')->with('success', 'Produk baru berhasil ditambahkan.');

        } catch (Exception $e) {
            Log::error('Error saving product: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            // Hapus file yang mungkin sudah terupload jika error
             $imagePath = $validatedDataForCreate['image_url'] ?? null;
             if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                 Storage::disk('public')->delete($imagePath);
             }
             $logoPath = $validatedDataForCreate['seller_logo'] ?? null;
             if ($logoPath && Storage::disk('public')->exists($logoPath)) {
                 Storage::disk('public')->delete($logoPath);
             }
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
            'productAttributes.attribute',
            'productVariantTypes.options' => fn ($q) => $q->orderBy('id'),
            'productVariants.options' => fn ($q) => $q->orderBy('product_variant_type_id')->orderBy('id')
        ]);

        $product->tags = $this->decodeTags($product->tags);

        $existingAttributesData = [];
        foreach($product->productAttributes as $pa) {
            if ($pa->attribute) {
                 $value = $pa->value;
                 if ($pa->attribute->type === 'checkbox' && is_string($value)) {
                      try {
                          $decodedValue = json_decode($value, true);
                          $value = is_array($decodedValue) ? $decodedValue : [$value];
                      } catch (\JsonException $e) { $value = [$value]; }
                 }
                $existingAttributesData[$pa->attribute->slug] = $value;
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
        // Gabungkan validasi dasar dan varian
        $baseRules = [
            'name'             => ['required', 'string', 'max:255', Rule::unique('products', 'name')->ignore($product->id)],
            'description'      => 'nullable|string',
            'price'            => 'required|numeric|min:0',
            'original_price'   => 'nullable|numeric|min:0|gte:price',
            'weight'           => 'required|integer|min:0',
            'category_id'      => 'required|exists:categories,id',
            'product_image'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // Validasi input file
            'store_name'       => 'nullable|string|max:255',
            'seller_city'      => 'nullable|string|max:255',
            'seller_name'      => 'nullable|string|max:255',
            'seller_wa'        => 'nullable|string|max:20',
            'seller_logo'      => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // Validasi input file
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
        $hasVariants = $request->has('variant_types') && !empty($request->variant_types);

        if ($hasVariants) {
            $conditionalRules = [
                'stock'            => 'nullable|integer|min:0',
                'variant_types.*.name' => 'required|string|max:255',
                'variant_types.*.options' => 'required|string|max:1000',
                'product_variants' => 'required|array|min:1',
                'product_variants.*.price' => 'required|numeric|min:0',
                'product_variants.*.stock' => 'required|integer|min:0',
                // Validasi unique SKU varian saat update lebih kompleks, perlu cek manual jika diperlukan
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

        $validated = $request->validate(array_merge($baseRules, $conditionalRules));

         // --- Proses Data ---
        $validatedDataForUpdate = $validated; // Buat salinan
        try {
            // PERBAIKAN: Simpan ke 'image_url' dan hapus file lama
            if ($request->hasFile('product_image')) {
                if ($product->image_url && Storage::disk('public')->exists($product->image_url)) {
                    Storage::disk('public')->delete($product->image_url);
                }
                $validatedDataForUpdate['image_url'] = $request->file('product_image')->store('products', 'public');
            }
             // Hapus input file dari data update
            unset($validatedDataForUpdate['product_image']);


            if ($request->hasFile('seller_logo')) {
                if ($product->seller_logo && Storage::disk('public')->exists($product->seller_logo)) {
                    Storage::disk('public')->delete($product->seller_logo);
                }
                $validatedDataForUpdate['seller_logo'] = $request->file('seller_logo')->store('seller_logos', 'public');
            }
            // Hapus input file dari data update
            unset($validatedDataForUpdate['seller_logo']);

             if ($request->filled('seller_wa')) {
                $wa = preg_replace('/[^0-9]/', '', $request->seller_wa);
                if (!Str::startsWith($wa, '62')) {
                     $wa = '62' . ltrim($wa, '0');
                }
                $validatedDataForUpdate['seller_wa'] = $wa;
            } else {
                 $validatedDataForUpdate['seller_wa'] = null;
            }
            if (empty($validatedDataForUpdate['store_name'])) {
                $validatedDataForUpdate['store_name'] = Auth::user()->store->name ?? config('app.default_store_name', 'Toko Sancaka Default');
            }
            if (empty($validatedDataForUpdate['seller_city'])) {
                $validatedDataForUpdate['seller_city'] = Auth::user()->store->city ?? config('app.default_store_city', 'Ngawi');
            }

            if ($request->name !== $product->name) {
                 $validatedDataForUpdate['slug'] = $this->generateUniqueSlug($validatedDataForUpdate['name'], $product->id);
            }

            if (empty($validatedDataForUpdate['sku']) && empty($product->sku) && !$hasVariants && !empty($validatedDataForUpdate['category_id'])) {
                 $validatedDataForUpdate['sku'] = $this->generateSku($validatedDataForUpdate['name'], $validatedDataForUpdate['category_id']);
            }

            $manualTags = [];
            if (!empty($request->tags)) {
                $manualTags = array_map('trim', explode(',', $request->tags));
                $manualTags = array_filter($manualTags);
            }
            $categoryTag = Category::find($validatedDataForUpdate['category_id'])->name ?? null;
            $allTags = $manualTags;
            if ($categoryTag && !in_array($categoryTag, $allTags)) {
                $allTags[] = $categoryTag;
            }
            $validatedDataForUpdate['tags'] = !empty($allTags) ? json_encode(array_values(array_unique($allTags))) : null;

            $validatedDataForUpdate['is_new'] = $request->has('is_new');
            $validatedDataForUpdate['is_bestseller'] = $request->has('is_bestseller');

            // Hapus data yang tidak relevan untuk tabel products sebelum update
            unset($validatedDataForUpdate['attributes']);
            unset($validatedDataForUpdate['variant_types']);
            unset($validatedDataForUpdate['product_variants']);


            if ($hasVariants) {
                $validatedDataForUpdate['stock'] = 0;
                $validatedDataForUpdate['price'] = $request->product_variants[0]['price'] ?? $validatedDataForUpdate['price'];
            } else {
                // Jika tidak ada varian (dihapus saat edit), kembalikan stok & harga utama
                $validatedDataForUpdate['stock'] = $request->input('stock', $product->stock);
                $validatedDataForUpdate['price'] = $request->input('price', $product->price);
            }


            DB::transaction(function () use ($product, $validatedDataForUpdate, $request, $hasVariants) {
                // Update Produk
                $product->update($validatedDataForUpdate);

                // Sinkronisasi Atribut
                $this->syncAttributes($product, $request->input('attributes', []));

                 // Sinkronisasi Varian
                if ($hasVariants) {
                    $this->syncVariantTypesAndCombinations($product, $request->variant_types, $request->product_variants);
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
            // Hapus gambar jika ada
            $imageUrl = $product->image_url ?? $product->image; // Cek kedua kolom
            if ($imageUrl && Storage::disk('public')->exists($imageUrl)) {
                Storage::disk('public')->delete($imageUrl);
            }
            if ($product->seller_logo && Storage::disk('public')->exists($product->seller_logo)) {
                Storage::disk('public')->delete($product->seller_logo);
            }

            // Hapus produk (relasi harusnya cascade jika foreign key diatur benar)
            // Relasi pivot perlu di-detach manual jika tidak ada cascade
            // $product->productVariants()->each(fn($v) => $v->options()->detach()); // Lakukan sebelum delete product jika tidak ada cascade

            $product->delete(); // Ini akan memicu cascade jika diatur di migration

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
        // Validasi hanya untuk produk tanpa varian
        if ($product->productVariantTypes()->exists()) {
             // Redirect ke halaman index dengan pesan error
             return redirect()->route('admin.products.index')->with('error', 'Gunakan halaman edit untuk restock produk dengan varian.');
        }

        $validated = $request->validate([ 'stock' => 'required|integer|min:1' ]);

        try {
            $product->increment('stock', $validated['stock']);
            return redirect()->route('admin.products.index')->with('success', 'Stok untuk produk ' . e($product->name) . ' berhasil ditambahkan.');
        } catch (Exception $e) {
             Log::error('Error restocking product ID ' . $product->id . ': ' . $e->getMessage());
              // Redirect ke halaman index dengan pesan error
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

    /**
     * Generate unique slug.
     */
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

     /**
     * Generate SKU otomatis.
     */
     protected function generateSku(string $productName, int $categoryId): string
     {
        $category = Category::find($categoryId);
        $categoryInitial = $category ? strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $category->name), 0, 3)) : 'GEN';
        $productInitial = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $productName), 0, 3));
        $randomNum = mt_rand(100, 999);
        $sku = "{$categoryInitial}-{$productInitial}-{$randomNum}";
        // Pastikan SKU unik (meskipun kemungkinannya kecil)
        while (Product::where('sku', $sku)->exists()) {
             $randomNum = mt_rand(100, 999);
             $sku = "{$categoryInitial}-{$productInitial}-{$randomNum}";
        }
        return $sku;
     }

    /**
     * Decode JSON tags menjadi string.
     */
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
     * Sinkronisasi atribut produk menggunakan updateOrCreate dan menghapus yang lama.
     */
    protected function syncAttributes(Product $product, ?array $attributesData)
    {
        if ($attributesData === null) {
            $product->productAttributes()->delete();
            return;
        }

        $validAttributeModels = Attribute::where('category_id', $product->category_id)
                                    ->whereIn('slug', array_keys($attributesData))
                                    ->get()
                                    ->keyBy('slug'); // [slug => AttributeModel]

        $currentAttributeIds = []; // Lacak ID atribut yang disinkronkan

        foreach ($attributesData as $slug => $value) {
            $attributeModel = $validAttributeModels->get($slug);

            // Hanya proses jika slug valid dan value tidak kosong/null
            if ($attributeModel && ($value !== null && $value !== '' && (!is_array($value) || !empty(array_filter($value))))) { // Cek array kosong juga
                $processedValue = is_array($value) ? json_encode(array_values(array_filter($value))) : $value;

                $productAttribute = ProductAttribute::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'attribute_id' => $attributeModel->id,
                    ],
                    [
                        'attribute_slug' => $slug, // Tetap simpan slug
                        'value' => $processedValue,
                        // Simpan juga nama dan tipe saat itu untuk kemudahan tampilan (opsional)
                        'attribute_name' => $attributeModel->name,
                        'attribute_type' => $attributeModel->type,
                    ]
                );
                $currentAttributeIds[] = $attributeModel->id; // Lacak ID dari tabel attributes
            }
        }

        // Hapus ProductAttribute yang attribute_id-nya tidak ada di $currentAttributeIds
        $product->productAttributes()->whereNotIn('attribute_id', $currentAttributeIds)->delete();
    }


     /**
     * Sinkronisasi tipe varian, opsi, dan kombinasi varian produk.
     */
    protected function syncVariantTypesAndCombinations(Product $product, ?array $variantTypesData, ?array $productVariantsData)
    {
        if ($variantTypesData === null || $productVariantsData === null) {
            // Hapus semua jika data tidak lengkap
             $product->productVariants()->each(fn($v) => $v->options()->detach());
             $product->productVariants()->delete();
             $product->productVariantTypes()->delete();
             return;
        }

        // === Langkah 1: Sinkronisasi Tipe Varian dan Opsi-nya ===
        $currentTypeIds = [];
        $optionIdMap = []; // Map ['TipeNama:OpsiNama' => option_id]

        foreach ($variantTypesData as $typeData) {
            $typeName = trim($typeData['name'] ?? '');
            $optionsInputRaw = trim($typeData['options'] ?? '');
            if (empty($typeName) || empty($optionsInputRaw)) continue;

            $optionsInput = array_values(array_filter(array_unique(array_map('trim', explode(',', $optionsInputRaw))))); // Bersihkan opsi
            if (empty($optionsInput)) continue;

            $variantType = ProductVariantType::updateOrCreate(
                ['product_id' => $product->id, 'name' => $typeName]
            );
            $currentTypeIds[] = $variantType->id;

            // Sinkronisasi Opsi
            $currentOptionIds = [];
            foreach ($optionsInput as $optionName) {
                $option = ProductVariantOption::updateOrCreate(
                    ['product_variant_type_id' => $variantType->id, 'name' => $optionName]
                );
                $currentOptionIds[] = $option->id;
                $optionIdMap[$typeName . ':' . $optionName] = $option->id;
            }
            // Hapus opsi lama yang tidak ada di input baru untuk tipe ini
            $variantType->options()->whereNotIn('id', $currentOptionIds)->delete();
        }
        // Hapus tipe varian lama yang tidak ada di input baru
        $product->productVariantTypes()->whereNotIn('id', $currentTypeIds)->delete(); // Cascade akan hapus opsinya

        // === Langkah 2: Sinkronisasi Kombinasi Varian ===
        $currentVariantIds = [];

        foreach ($productVariantsData as $comboData) {
            $variantOptions = $comboData['variant_options'] ?? [];
            if (empty($variantOptions)) continue;

            $combinationStringParts = [];
            $optionIdsForCombination = [];

            // Urutkan opsi berdasarkan nama tipe
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
                      continue 2; // Skip kombinasi ini
                 }
            }

            if (count($optionIdsForCombination) !== count($variantOptions)) continue; // Pastikan semua opsi valid

            // Buat string kombinasi yang terurut (setelah di-sort berdasarkan type_name di atas)
            // sort($combinationStringParts); // Tidak perlu sort lagi karena sudah di-sort berdasarkan type_name
            $combinationString = implode(';', $combinationStringParts);

            // Gunakan updateOrCreate untuk kombinasi
             $variant = ProductVariant::updateOrCreate(
                 ['product_id' => $product->id, 'combination_string' => $combinationString],
                 [
                     'price' => $comboData['price'] ?? 0,
                     'stock' => $comboData['stock'] ?? 0,
                     'sku_code' => $comboData['sku_code'] ?? null,
                 ]
             );
            $currentVariantIds[] = $variant->id;

            // Sinkronisasi relasi pivot
            if ($variant) {
                try {
                    $variant->options()->sync($optionIdsForCombination);
                } catch (Exception $e) {
                     Log::error("Gagal sync options ke variant {$variant->id}: " . $e->getMessage());
                     throw $e;
                }
            }
        }
        // Hapus kombinasi varian lama yang tidak ada di input baru
         $variantsToDelete = ProductVariant::where('product_id', $product->id)->whereNotIn('id', $currentVariantIds)->get();
         foreach($variantsToDelete as $variantToDel) {
            $variantToDel->options()->detach(); // Detach pivot dulu
            $variantToDel->delete(); // Hapus varian
         }
    }

}

