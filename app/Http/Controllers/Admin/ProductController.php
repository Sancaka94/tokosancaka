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
                        // Gunakan accessor getImageAttribute jika ada, atau kolom 'image'
                        $imageUrl = $row->image_url ?? $row->image; // Coba image_url dulu, fallback ke image
                        $url = $imageUrl ? asset('storage/' . $imageUrl) : 'https://placehold.co/80x80/EFEFEF/333333?text=N/A';
                        return '<img src="' . e($url) . '" alt="' . e($row->name) . '" class="rounded" width="60" loading="lazy" />'; // Tambah lazy loading
                    })
                    ->editColumn('price', function ($row) {
                         // Tampilkan harga varian pertama jika ada
                        $displayPrice = $row->price;
                        if($row->productVariants && $row->productVariants->isNotEmpty()){
                            $displayPrice = $row->productVariants->first()->price ?? $row->price;
                        }
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
                            $stockDisplay = $row->stock . ' <i class="fas fa-code-branch variant-indicator" title="Produk ini memiliki varian"></i>';
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
            'attributes.*'     => 'nullable', // Bisa array (checkbox) atau string
            'variant_types'    => 'nullable|array',
        ];

        // Aturan validasi yang bergantung pada ada tidaknya varian
        $conditionalRules = [];
        if ($request->has('variant_types') && !empty($request->variant_types)) {
            // Jika ADA varian
            $conditionalRules = [
                'stock'            => 'nullable|integer|min:0', // Stok utama jadi opsional (akan di-set 0)
                'variant_types.*.name' => 'required|string|max:255',
                'variant_types.*.options' => 'required|string|max:1000',
                'product_variants' => 'required|array|min:1', // Kombinasi wajib ada
                'product_variants.*.price' => 'required|numeric|min:0',
                'product_variants.*.stock' => 'required|integer|min:0',
                'product_variants.*.sku_code' => ['nullable', 'string', 'max:100', Rule::unique('product_variants', 'sku_code')], // Unique SKU Varian
                'product_variants.*.variant_options' => 'required|array|min:1', // Minimal 1 opsi per kombinasi
                'product_variants.*.variant_options.*.type_name' => 'required|string',
                'product_variants.*.variant_options.*.value' => 'required|string',
            ];
        } else {
            // Jika TIDAK ADA varian
            $conditionalRules = [
                'stock' => 'required|integer|min:0', // Stok utama jadi wajib
                'product_variants' => 'prohibited', // Tidak boleh ada data kombinasi
            ];
        }

        $validated = $request->validate(array_merge($baseRules, $conditionalRules));


        // --- Proses Data ---
        try {
            if ($request->hasFile('product_image')) {
                $validated['image'] = $request->file('product_image')->store('products', 'public');
            }
            if ($request->hasFile('seller_logo')) {
                $validated['seller_logo'] = $request->file('seller_logo')->store('seller_logos', 'public');
            }
            if (!empty($request->seller_wa)) {
                $wa = preg_replace('/[^0-9]/', '', $request->seller_wa);
                if (!Str::startsWith($wa, '62')) {
                     $wa = '62' . ltrim($wa, '0');
                }
                $validated['seller_wa'] = $wa;
            }
            if (empty($validated['store_name'])) {
                $validated['store_name'] = Auth::user()->store->name ?? config('app.default_store_name', 'Toko Sancaka Default');
            }
            if (empty($validated['seller_city'])) {
                $validated['seller_city'] = Auth::user()->store->city ?? config('app.default_store_city', 'Ngawi');
            }

            $validated['slug'] = $this->generateUniqueSlug($validated['name']);

            if (empty($validated['sku']) && !empty($validated['category_id']) && !$request->has('variant_types')) { // Hanya generate SKU utama jika tidak ada varian & SKU kosong
                 $validated['sku'] = $this->generateSku($validated['name'], $validated['category_id']);
            }

            $manualTags = [];
            if (!empty($request->tags)) {
                $manualTags = array_map('trim', explode(',', $request->tags));
                $manualTags = array_filter($manualTags);
            }
            $categoryTag = Category::find($validated['category_id'])->name ?? null;
            $allTags = $manualTags;
            if ($categoryTag && !in_array($categoryTag, $allTags)) {
                $allTags[] = $categoryTag;
            }
            $validated['tags'] = !empty($allTags) ? json_encode(array_values(array_unique($allTags))) : null;

            $validated['is_new'] = $request->has('is_new');
            $validated['is_bestseller'] = $request->has('is_bestseller');

            $hasVariants = $request->has('variant_types') && !empty($request->variant_types);
            if ($hasVariants) {
                $validated['stock'] = 0; // Stok utama selalu 0 jika ada varian
                // Harga utama bisa diambil dari varian pertama atau dibiarkan saja
                 $validated['price'] = $request->product_variants[0]['price'] ?? $validated['price'];
            }


            $product = DB::transaction(function () use ($validated, $request, $hasVariants) {
                // Buat Produk
                $product = Product::create($validated);

                // Simpan Atribut
                $this->syncAttributes($product, $request->input('attributes', []));

                // Simpan Varian jika ada
                if ($hasVariants) {
                    $this->syncVariantTypesAndCombinations($product, $request->variant_types, $request->product_variants);
                }

                return $product; // Kembalikan produk yang dibuat
            });

            return redirect()->route('admin.products.index')->with('success', 'Produk baru berhasil ditambahkan.');

        } catch (Exception $e) {
            Log::error('Error saving product: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            // Hapus file yang mungkin sudah terupload jika error
            if (!empty($validated['image']) && Storage::disk('public')->exists($validated['image'])) {
                Storage::disk('public')->delete($validated['image']);
            }
             if (!empty($validated['seller_logo']) && Storage::disk('public')->exists($validated['seller_logo'])) {
                Storage::disk('public')->delete($validated['seller_logo']);
            }
            return back()->with('error', 'Gagal menyimpan produk: ' . $e->getMessage())->withInput();
        }
    }


    /**
     * Menampilkan form untuk mengedit produk.
     */
    public function edit(Product $product)
    {
        // Muat semua relasi yang dibutuhkan oleh view edit.blade.php
        $product->load([
            'category',
            'productAttributes.attribute', // Muat juga detail attribute (nama, tipe, dll)
            'productVariantTypes.options' => function ($query) {
                 $query->orderBy('id'); // Urutkan opsi berdasarkan ID
            },
             // Muat kombinasi varian DAN opsi-opsi terkaitnya via pivot, urutkan opsi
            'productVariants.options' => function ($query) {
                $query->orderBy('product_variant_type_id')->orderBy('id');
            }
        ]);

        // Decode JSON tags back to string for the input field
        $product->tags = $this->decodeTags($product->tags);

        // Siapkan data atribut yang ada untuk JavaScript
        // Kita perlu data dari tabel attributes (tipe, dll) dan product_attributes (value)
        $existingAttributesData = [];
        foreach($product->productAttributes as $pa) {
            if ($pa->attribute) { // Pastikan relasi attribute terload
                 $value = $pa->value;
                 // Coba decode jika tipe checkbox
                 if ($pa->attribute->type === 'checkbox' && is_string($value)) {
                      try {
                          $decodedValue = json_decode($value, true);
                          // Pastikan hasil decode adalah array
                          $value = is_array($decodedValue) ? $decodedValue : [$value];
                      } catch (\JsonException $e) {
                          $value = [$value]; // Jika gagal decode, anggap sebagai string tunggal
                      }
                 }
                $existingAttributesData[$pa->attribute->slug] = $value; // Gunakan slug dari tabel attributes
            }
        }
         // Encode kembali untuk JS
        $product->existing_attributes_json = json_encode($existingAttributesData);


        // Siapkan data tipe varian yang ada untuk JavaScript
         $product->existing_variant_types_json = $product->productVariantTypes->map(function($variantType) {
            return [
                'name' => $variantType->name,
                // Pastikan options terurut jika perlu
                'options' => $variantType->options->pluck('name')->implode(', ')
            ];
        })->toJson();


        // Siapkan data kombinasi varian yang ada untuk JavaScript
        $product->existing_variant_combinations_json = $product->productVariants->mapWithKeys(function($variant) {
             // Pastikan relasi options terload dan terurut
            $key = $variant->options
                        // ->sortBy('product_variant_type_id') // Urutkan berdasarkan tipe
                        ->map(function($option) {
                            // Dapatkan nama tipe dari relasi option ke type
                            return ($option->productVariantType->name ?? 'UNKNOWN') . ':' . $option->name;
                        })
                        ->sort() // Urutkan string mapKey
                        ->implode(';'); // Gabungkan
            return [
                $key => [
                    'price' => $variant->price,
                    'stock' => $variant->stock,
                    'sku_code' => $variant->sku_code
                ]
            ];
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

        // Aturan validasi yang bergantung pada ada tidaknya varian
        $conditionalRules = [];
        $hasVariants = $request->has('variant_types') && !empty($request->variant_types);

        if ($hasVariants) {
            $conditionalRules = [
                'stock'            => 'nullable|integer|min:0',
                'variant_types.*.name' => 'required|string|max:255',
                'variant_types.*.options' => 'required|string|max:1000',
                 // Validasi unique SKU Varian perlu lebih kompleks jika ingin update
                 // Untuk sementara, kita bisa validasi manual setelah ini atau biarkan bisa duplikat saat update
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

        $validated = $request->validate(array_merge($baseRules, $conditionalRules));

         // --- Proses Data ---
        try {
            if ($request->hasFile('product_image')) {
                if ($product->image && Storage::disk('public')->exists($product->image)) {
                    Storage::disk('public')->delete($product->image);
                }
                $validated['image'] = $request->file('product_image')->store('products', 'public');
            }
            if ($request->hasFile('seller_logo')) {
                if ($product->seller_logo && Storage::disk('public')->exists($product->seller_logo)) {
                    Storage::disk('public')->delete($product->seller_logo);
                }
                $validated['seller_logo'] = $request->file('seller_logo')->store('seller_logos', 'public');
            }
             if ($request->filled('seller_wa')) {
                $wa = preg_replace('/[^0-9]/', '', $request->seller_wa);
                if (!Str::startsWith($wa, '62')) {
                     $wa = '62' . ltrim($wa, '0');
                }
                $validated['seller_wa'] = $wa;
            } else {
                 $validated['seller_wa'] = null;
            }
            if (empty($validated['store_name'])) {
                $validated['store_name'] = Auth::user()->store->name ?? config('app.default_store_name', 'Toko Sancaka Default');
            }
            if (empty($validated['seller_city'])) {
                $validated['seller_city'] = Auth::user()->store->city ?? config('app.default_store_city', 'Ngawi');
            }

            if ($request->name !== $product->name) {
                 $validated['slug'] = $this->generateUniqueSlug($validated['name'], $product->id);
            }

            if (empty($validated['sku']) && empty($product->sku) && !$hasVariants && !empty($validated['category_id'])) {
                 $validated['sku'] = $this->generateSku($validated['name'], $validated['category_id']);
            }

            $manualTags = [];
            if (!empty($request->tags)) {
                $manualTags = array_map('trim', explode(',', $request->tags));
                $manualTags = array_filter($manualTags);
            }
            $categoryTag = Category::find($validated['category_id'])->name ?? null;
            $allTags = $manualTags;
            if ($categoryTag && !in_array($categoryTag, $allTags)) {
                $allTags[] = $categoryTag;
            }
            $validated['tags'] = !empty($allTags) ? json_encode(array_values(array_unique($allTags))) : null;

            $validated['is_new'] = $request->has('is_new');
            $validated['is_bestseller'] = $request->has('is_bestseller');

            if ($hasVariants) {
                $validated['stock'] = 0;
                $validated['price'] = $request->product_variants[0]['price'] ?? $validated['price'];
            } else {
                // Jika tidak ada varian (dihapus saat edit), kembalikan stok & harga utama
                $validated['stock'] = $request->input('stock', $product->stock); // Ambil dari input atau data lama
                $validated['price'] = $request->input('price', $product->price);
            }


            DB::transaction(function () use ($product, $validated, $request, $hasVariants) {
                // Update Produk
                $product->update($validated);

                // Sinkronisasi Atribut
                $this->syncAttributes($product, $request->input('attributes', []));

                 // Sinkronisasi Varian
                if ($hasVariants) {
                    $this->syncVariantTypesAndCombinations($product, $request->variant_types, $request->product_variants);
                } else {
                    // Jika tidak ada data varian (dihapus semua di form), hapus semua varian terkait
                     $product->productVariants()->each(fn($v) => $v->options()->detach()); // Detach pivot
                     $product->productVariants()->delete(); // Hapus kombinasi
                     $product->productVariantTypes()->delete(); // Hapus tipe (cascade harusnya hapus options)
                }
            });

            return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diperbarui.');

        } catch (Exception $e) {
            Log::error('Error updating product: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
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
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            if ($product->seller_logo && Storage::disk('public')->exists($product->seller_logo)) {
                Storage::disk('public')->delete($product->seller_logo);
            }

            // Hapus produk (relasi harusnya cascade jika foreign key diatur benar)
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
        // Validasi hanya untuk produk tanpa varian
        if ($product->productVariantTypes()->exists()) {
             return back()->with('error', 'Gunakan halaman edit untuk restock produk dengan varian.');
        }

        $validated = $request->validate([ 'stock' => 'required|integer|min:1' ]);

        try {
            // Gunakan increment untuk mencegah race condition
            $product->increment('stock', $validated['stock']);
            return redirect()->route('admin.products.index')->with('success', 'Stok untuk produk ' . e($product->name) . ' berhasil ditambahkan.');
        } catch (Exception $e) {
             Log::error('Error restocking product ID ' . $product->id . ': ' . $e->getMessage());
             return back()->with('error', 'Gagal restock produk: ' . $e->getMessage());
        }
    }

    /**
     * Menandai produk (tanpa varian) sebagai habis (stok = 0).
     */
    public function markAsOutOfStock(Product $product)
    {
        // Validasi hanya untuk produk tanpa varian
        if ($product->productVariantTypes()->exists()) {
             return back()->with('error', 'Gunakan halaman edit untuk mengatur stok produk dengan varian.');
        }

        try {
            $product->stock = 0;
            $product->save();
            return redirect()->route('admin.products.index')->with('success', 'Stok untuk produk ' . e($product->name) . ' telah diatur menjadi 0.');
        } catch (Exception $e) {
             Log::error('Error marking product out of stock ID ' . $product->id . ': ' . $e->getMessage());
             return back()->with('error', 'Gagal menandai habis stok: ' . $e->getMessage());
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

         // Loop Cek Keunikan Slug
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
        $categoryInitial = $category ? strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $category->name), 0, 3)) : 'GEN'; // Ambil 3 huruf/angka pertama
        $productInitial = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $productName), 0, 3)); // Ambil 3 huruf/angka pertama
        $randomNum = mt_rand(100, 999);
        return "{$categoryInitial}-{$productInitial}-{$randomNum}";
     }

    /**
     * Decode JSON tags menjadi string.
     */
    protected function decodeTags($tags): string
    {
         if (is_string($tags)) {
             try {
                 $decodedTags = json_decode($tags, true);
                 return is_array($decodedTags) ? implode(', ', $decodedTags) : $tags;
             } catch (\JsonException $e) {
                 // Jika bukan JSON valid, kembalikan string asli
                 return $tags;
             }
         } elseif (is_array($tags)) {
             return implode(', ', $tags);
         }
         return ''; // Default string kosong jika null atau tipe lain
    }


    /**
     * Sinkronisasi atribut produk.
     */
    protected function syncAttributes(Product $product, ?array $attributesData)
    {
        if ($attributesData === null) {
             $product->productAttributes()->delete(); // Hapus semua jika tidak ada data atribut
             return;
        }

        // 1. Ambil ID atribut yang valid untuk kategori produk
        $validAttributeIds = Attribute::where('category_id', $product->category_id)
                                    ->pluck('id', 'slug'); // [slug => id]

        $syncData = [];
        foreach ($attributesData as $slug => $value) {
            // Hanya proses jika slug valid dan value tidak kosong
            if (isset($validAttributeIds[$slug]) && ($value !== null && $value !== '')) {
                $attributeId = $validAttributeIds[$slug];
                // Jika value adalah array (dari checkbox), simpan sebagai JSON
                $processedValue = is_array($value) ? json_encode(array_values(array_filter($value))) : $value; // Filter nilai kosong dari checkbox

                 // Gunakan updateOrCreate untuk efisiensi
                // Key: product_id dan attribute_id
                // Values: sisanya (value, slug, name, type)
                 ProductAttribute::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'attribute_id' => $attributeId,
                    ],
                    [
                        'attribute_slug' => $slug, // Simpan slug untuk kemudahan lookup
                        'value' => $processedValue,
                        // Anda bisa juga menyimpan nama & tipe di sini jika perlu, ambil dari $validAttributeIds->get($slug)->name, dll.
                        // Tapi lebih baik load relasi 'attribute' saat menampilkan.
                    ]
                );
                // Tandai ID ini untuk tidak dihapus
                $syncData[$attributeId] = true; // Cukup tandai ID nya saja
            }
        }

        // 2. Hapus atribut yang tidak ada lagi di $attributesData
        $product->productAttributes()->whereNotIn('attribute_id', array_keys($syncData))->delete();
    }


     /**
     * Sinkronisasi tipe varian, opsi, dan kombinasi varian produk.
     */
    protected function syncVariantTypesAndCombinations(Product $product, ?array $variantTypesData, ?array $productVariantsData)
    {
         if ($variantTypesData === null || $productVariantsData === null) {
            // Jika salah satu data kosong (misal dihapus semua di form), hapus semua varian
             $product->productVariants()->each(fn($v) => $v->options()->detach());
             $product->productVariants()->delete();
             $product->productVariantTypes()->delete(); // Cascade akan hapus options
             return;
        }

        // === Langkah 1: Sinkronisasi Tipe Varian dan Opsi-nya ===
        $existingTypeIds = $product->productVariantTypes()->pluck('id')->toArray();
        $currentTypeIds = [];
        $optionIdMap = []; // Map ['TipeNama:OpsiNama' => option_id]
        $typeIdMap = [];   // Map ['TipeNama' => type_id]

        foreach ($variantTypesData as $typeData) {
            if (empty($typeData['name']) || empty($typeData['options'])) continue;

            $typeName = trim($typeData['name']);
            $optionsInput = array_map('trim', explode(',', $typeData['options']));
            $optionsInput = array_filter(array_unique($optionsInput)); // Filter kosong & duplikat

            if (empty($optionsInput)) continue; // Skip jika tidak ada opsi valid

            // Gunakan updateOrCreate untuk tipe varian
            $variantType = ProductVariantType::updateOrCreate(
                ['product_id' => $product->id, 'name' => $typeName]
            );
            $currentTypeIds[] = $variantType->id;
            $typeIdMap[$typeName] = $variantType->id;

            // Sinkronisasi Opsi untuk tipe ini
            $existingOptionIds = $variantType->options()->pluck('id')->toArray();
            $currentOptionIds = [];
            foreach ($optionsInput as $optionName) {
                 // Gunakan updateOrCreate untuk opsi
                $option = ProductVariantOption::updateOrCreate(
                    ['product_variant_type_id' => $variantType->id, 'name' => $optionName]
                );
                $currentOptionIds[] = $option->id;
                $optionIdMap[$typeName . ':' . $optionName] = $option->id; // Buat map untuk kombinasi
            }
            // Hapus opsi yang tidak ada lagi untuk tipe ini
            $variantType->options()->whereNotIn('id', $currentOptionIds)->delete();
        }
        // Hapus tipe varian yang tidak ada lagi di form
        ProductVariantType::where('product_id', $product->id)->whereNotIn('id', $currentTypeIds)->delete(); // Cascade harusnya hapus opsinya

        // === Langkah 2: Sinkronisasi Kombinasi Varian ===
        $existingVariantIds = $product->productVariants()->pluck('id')->toArray();
        $currentVariantIds = [];

        foreach ($productVariantsData as $comboData) {
            if (empty($comboData['variant_options'])) continue;

            $combinationStringParts = [];
            $optionIdsForCombination = [];

             // Urutkan opsi berdasarkan nama tipe untuk konsistensi kunci
            usort($comboData['variant_options'], fn($a, $b) => strcmp($a['type_name'], $b['type_name']));

            foreach ($comboData['variant_options'] as $optionDetail) {
                 $typeName = trim($optionDetail['type_name']);
                 $valueName = trim($optionDetail['value']);
                 $mapKey = $typeName . ':' . $valueName;

                 if (isset($optionIdMap[$mapKey])) {
                     $combinationStringParts[] = $mapKey;
                     $optionIdsForCombination[] = $optionIdMap[$mapKey];
                 } else {
                      Log::warning("Opsi tidak ditemukan di map saat sinkronisasi: {$mapKey} for product ID: {$product->id}");
                      continue 2; // Skip kombinasi ini jika ada opsi yang tidak valid
                 }
            }

            if (empty($optionIdsForCombination)) continue; // Skip jika tidak ada opsi valid

            // Buat string kombinasi yang terurut
            sort($combinationStringParts); // Pastikan urutan konsisten
            $combinationString = implode(';', $combinationStringParts);

            // Gunakan updateOrCreate untuk kombinasi varian
             $variant = ProductVariant::updateOrCreate(
                 [
                     'product_id' => $product->id,
                     'combination_string' => $combinationString, // Kunci unik
                 ],
                 [
                     'price' => $comboData['price'],
                     'stock' => $comboData['stock'],
                     'sku_code' => $comboData['sku_code'] ?? null,
                 ]
             );
            $currentVariantIds[] = $variant->id;

            // Sinkronisasi relasi pivot (opsi untuk kombinasi ini)
            if ($variant) {
                try {
                    // Sync akan otomatis attach/detach sesuai array ID yang diberikan
                    $variant->options()->sync($optionIdsForCombination);
                } catch (Exception $e) {
                     Log::error("Gagal sync options ke variant {$variant->id}: " . $e->getMessage());
                     throw $e; // Re-throw agar transaksi rollback
                }
            }
        }
        // Hapus kombinasi varian yang tidak ada lagi di form
         $variantsToDelete = ProductVariant::where('product_id', $product->id)->whereNotIn('id', $currentVariantIds)->get();
         foreach($variantsToDelete as $variantToDel) {
            $variantToDel->options()->detach(); // Detach pivot dulu
            $variantToDel->delete(); // Hapus varian
         }
    }

}

