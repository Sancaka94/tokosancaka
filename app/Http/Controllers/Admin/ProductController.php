<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Attribute; // Tambahkan use untuk Attribute
use App\Models\ProductAttribute; // Tambahkan use
use App\Models\ProductVariantType; // Tambahkan use
use App\Models\ProductVariantOption; // Tambahkan use
use App\Models\ProductVariant; // Tambahkan use
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use Exception;
use Illuminate\Support\Facades\Log; // Untuk logging error
use Illuminate\Support\Facades\Auth; // Jika perlu ambil data admin default


class ProductController extends Controller
{
    /**
     * Menampilkan halaman manajemen produk.
     */
    public function index()
    {
        return view('admin.products.index');
    }

    /**
     * Menyediakan data untuk Yajra DataTables.
     */
    public function getData(Request $request)
    {
        if ($request->ajax()) {
            try {
                // Ambil slug kategori jika ada filter
                $categorySlug = $request->input('category_slug');

                $data = Product::with('category')
                        ->when($categorySlug, function ($query, $slug) {
                             // Join dengan categories untuk filter berdasarkan slug
                            $query->whereHas('category', function($q) use ($slug) {
                                $q->where('slug', $slug);
                            });
                        })
                        ->select('products.*'); // Pilih semua kolom dari products

                return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('image', function ($row) {
                        $url = $row->image ? asset('storage/' . $row->image) : 'https://placehold.co/80x80/EFEFEF/333333?text=N/A';
                        return '<img src="' . e($url) . '" alt="' . e($row->name) . '" class="rounded" width="60" />';
                    })
                    ->editColumn('price', function ($row) {
                        return 'Rp' . number_format($row->price, 0, ',', '.');
                    })
                    ->addColumn('category_name', function ($row) {
                        return $row->category->name ?? 'N/A';
                    })
                    ->addColumn('status_badge', function ($row) {
                        $color = $row->status == 'active' ? 'bg-success' : 'bg-secondary';
                        return '<span class="badge ' . e($color) . '">' . e(ucfirst($row->status)) . '</span>';
                    })
                    ->addColumn('action', function($row){
                        // PERBAIKAN: Menggunakan slug
                        $editUrl = route('admin.products.edit', $row->slug);
                        $deleteUrl = route('admin.products.destroy', $row->slug);
                        $outOfStockUrl = route('admin.products.outOfStock', $row->slug);
                        $restockUrl = route('admin.products.restock', $row->slug); // URL untuk form POST restock

                        $actionBtn = '<div class="d-flex justify-content-center gap-2">';
                        // PERBAIKAN: Mengoper slug ke JavaScript modal
                        $actionBtn .= '<button type="button" onclick="openRestockModal(\''.e($restockUrl).'\', \''.e($row->name).'\')" class="btn btn-success btn-circle btn-sm" title="Restock"><i class="fas fa-plus"></i></button>';
                        $actionBtn .= '<a href="'.e($editUrl).'" class="btn btn-warning btn-circle btn-sm" title="Edit"><i class="fas fa-pen-to-square"></i></a>';
                        $actionBtn .= '<form action="'.e($outOfStockUrl).'" method="POST" class="d-inline" onsubmit="return confirm(\'Anda yakin ingin menandai produk ini habis?\');">'.csrf_field().method_field('PATCH').'<button type="submit" class="btn btn-secondary btn-circle btn-sm" title="Tandai Habis"><i class="fas fa-box-open"></i></button></form>';
                        $actionBtn .= '<form action="'.e($deleteUrl).'" method="POST" class="d-inline" onsubmit="return confirm(\'Anda yakin ingin menghapus produk ini?\');">'.csrf_field().method_field('DELETE').'<button type="submit" class="btn btn-danger btn-circle btn-sm" title="Hapus"><i class="fas fa-trash"></i></button></form>';
                        $actionBtn .= '</div>';

                        return $actionBtn;
                    })
                    ->rawColumns(['action', 'image', 'status_badge'])
                    ->make(true);

            } catch (Exception $e) {
                Log::error('DataTables Error: ' . $e->getMessage());
                return response()->json(['error' => 'Could not process data.', 'message' => $e->getMessage()], 500);
            }
        }
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
        $validated = $request->validate([
            'name'             => 'required|string|max:255|unique:products,name', // Tambah unique
            'description'      => 'nullable|string',
            'price'            => 'required|numeric|min:0',
            'original_price'   => 'nullable|numeric|min:0|gte:price', // gte:price (>= price)
             // Stok tidak wajib jika ada varian, tapi harus ada jika tidak ada varian
            'stock'            => 'required_without:product_variants|nullable|integer|min:0',
            'weight'           => 'required|integer|min:0',
            'category_id'      => 'required|exists:categories,id',
            'product_image'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'store_name'       => 'nullable|string|max:255', // Jadi nullable
            'seller_city'      => 'nullable|string|max:255', // Jadi nullable
            'seller_name'      => 'nullable|string|max:255',
            'seller_wa'        => 'nullable|string|max:20',
            'seller_logo'      => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'sku'              => 'nullable|string|max:100|unique:products,sku', // Tambah unique
            'tags'             => 'nullable|string',
            'status'           => 'required|in:active,inactive',
            'is_new'           => 'nullable|boolean',
            'is_bestseller'    => 'nullable|boolean',
            'length'           => 'nullable|numeric|min:0',
            'width'            => 'nullable|numeric|min:0',
            'height'           => 'nullable|numeric|min:0',
            'attributes'       => 'nullable|array', // Data atribut dari form dinamis
            'attributes.*'     => 'nullable|string', // Validasi dasar untuk nilai atribut
            'variant_types'    => 'nullable|array', // Data tipe varian dari form
            'variant_types.*.name' => 'required_with:variant_types|string|max:255',
            'variant_types.*.options' => 'required_with:variant_types|string|max:1000', // Batasi panjang opsi
             // Validasi untuk kombinasi varian (jika ada)
            'product_variants' => 'required_with:variant_types|array',
            'product_variants.*.price' => 'required|numeric|min:0',
            'product_variants.*.stock' => 'required|integer|min:0',
            'product_variants.*.sku_code' => 'nullable|string|max:100', // SKU per varian opsional
            'product_variants.*.variant_options' => 'required|array', // Opsi yang membentuk kombinasi ini
            'product_variants.*.variant_options.*.type_name' => 'required|string',
            'product_variants.*.variant_options.*.value' => 'required|string',
        ]);

        // 1. Handle Upload Gambar Utama
        if ($request->hasFile('product_image')) {
            $validated['image'] = $request->file('product_image')->store('products', 'public');
        }

        // 2. Handle Upload Logo Penjual
        if ($request->hasFile('seller_logo')) {
            $validated['seller_logo'] = $request->file('seller_logo')->store('seller_logos', 'public');
        }

        // 3. Format Nomor WA
        if (!empty($request->seller_wa)) {
            $wa = preg_replace('/[^0-9]/', '', $request->seller_wa);
            if (!Str::startsWith($wa, '62')) {
                 $wa = '62' . ltrim($wa, '0');
            }
            $validated['seller_wa'] = $wa;
        }

        // 4. Set Nilai Default untuk Penjual jika kosong
        if (empty($validated['store_name'])) {
            // Ambil dari data admin yang login atau config default
            $validated['store_name'] = Auth::user()->store->name ?? config('app.default_store_name', 'Toko Sancaka Default');
        }
        if (empty($validated['seller_city'])) {
            $validated['seller_city'] = Auth::user()->store->city ?? config('app.default_store_city', 'Ngawi');
        }
        // Anda bisa tambahkan default untuk seller_name, seller_wa, seller_logo jika perlu

        // 5. Generate Slug
        $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(5); // Lebih pendek dari uniqid

        // 6. Generate SKU Otomatis jika kosong
        if (empty($validated['sku']) && !empty($validated['category_id'])) {
             $category = Category::find($validated['category_id']);
             $categoryInitial = $category ? strtoupper(substr($category->name, 0, 3)) : 'GEN';
             $productInitial = strtoupper(substr($validated['name'], 0, 3));
             $randomNum = mt_rand(100, 999);
             $validated['sku'] = "{$categoryInitial}-{$productInitial}-{$randomNum}";
        }

        // 7. Handle Tags Otomatis + Manual
        $manualTags = [];
        if (!empty($request->tags)) {
            $manualTags = array_map('trim', explode(',', $request->tags));
            $manualTags = array_filter($manualTags); // Hapus tag kosong
        }
        $categoryTag = Category::find($validated['category_id'])->name ?? null;
        $allTags = $manualTags;
        if ($categoryTag && !in_array($categoryTag, $allTags)) {
            $allTags[] = $categoryTag; // Tambahkan tag kategori jika belum ada
        }
        $validated['tags'] = !empty($allTags) ? json_encode(array_values(array_unique($allTags))) : null; // Simpan sbg JSON


        // 8. Handle boolean checkbox
        $validated['is_new'] = $request->has('is_new');
        $validated['is_bestseller'] = $request->has('is_bestseller');

        // 9. Jika ada varian, stok utama = 0, harga utama = harga varian pertama
        if ($request->has('product_variants') && count($request->product_variants) > 0) {
            $validated['stock'] = 0; // Stok utama jadi 0
            // Ambil harga dari varian pertama sebagai harga utama (opsional, bisa juga null)
            $validated['price'] = $request->product_variants[0]['price'] ?? $validated['price'];
        }

        $product = null;

        DB::beginTransaction();
        try {
            // 10. Buat Produk
            $product = Product::create($validated);

            // 11. Simpan Atribut
            $this->syncAttributes($product, $request->input('attributes', []));

            // 12. Simpan Varian (Tipe, Opsi, Kombinasi)
            if ($request->has('variant_types') && $request->has('product_variants')) {
                $this->syncVariantTypesAndCombinations($product, $request->variant_types, $request->product_variants);
            }

            DB::commit();
            return redirect()->route('admin.products.index')->with('success', 'Produk baru berhasil ditambahkan.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error saving product: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
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
            'productVariantTypes.options', // Muat tipe varian dan opsinya
            'productVariants.options' // Muat kombinasi varian DAN opsi-opsi terkaitnya via pivot
        ]);

        // Decode JSON tags back to string for the input field
        if (is_string($product->tags)) {
             try {
                 $decodedTags = json_decode($product->tags, true);
                 // Check if decoding was successful and it's an array
                 if (is_array($decodedTags)) {
                     $product->tags = implode(', ', $decodedTags);
                 }
                 // If decoding fails or it's not an array, keep the original string (though it shouldn't happen with the new store logic)
             } catch (\JsonException $e) {
                 // Keep the original string if it's not valid JSON
                 Log::warning("Could not decode tags JSON for product ID {$product->id}: " . $product->tags);
             }
         } else if (is_array($product->tags)) {
             // If it's already an array (maybe from old data or direct manipulation)
             $product->tags = implode(', ', $product->tags);
         } else {
            // If it's null or something else, set to empty string
            $product->tags = '';
         }


        $categories = Category::where('type', 'product')->orderBy('name')->get();
        return view('admin.products.edit', compact('product', 'categories'));
    }


    /**
     * Memperbarui data produk di database.
     */
    public function update(Request $request, Product $product)
    {
         $validated = $request->validate([
            'name'             => 'required|string|max:255|unique:products,name,' . $product->id, // Abaikan unique untuk produk ini sendiri
            'description'      => 'nullable|string',
            'price'            => 'required|numeric|min:0',
            'original_price'   => 'nullable|numeric|min:0|gte:price',
            'stock'            => 'required_without:product_variants|nullable|integer|min:0',
            'weight'           => 'required|integer|min:0',
            'category_id'      => 'required|exists:categories,id',
            'product_image'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'store_name'       => 'nullable|string|max:255',
            'seller_city'      => 'nullable|string|max:255',
            'seller_name'      => 'nullable|string|max:255',
            'seller_wa'        => 'nullable|string|max:20',
            'seller_logo'      => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'sku'              => 'nullable|string|max:100|unique:products,sku,' . $product->id, // Abaikan unique
            'tags'             => 'nullable|string',
            'status'           => 'required|in:active,inactive',
            'is_new'           => 'nullable|boolean',
            'is_bestseller'    => 'nullable|boolean',
            'length'           => 'nullable|numeric|min:0',
            'width'            => 'nullable|numeric|min:0',
            'height'           => 'nullable|numeric|min:0',
            'attributes'       => 'nullable|array',
            'attributes.*'     => 'nullable', // Bisa string atau array (untuk checkbox)
            'variant_types'    => 'nullable|array',
            'variant_types.*.name' => 'required_with:variant_types|string|max:255',
            'variant_types.*.options' => 'required_with:variant_types|string|max:1000',
            'product_variants' => 'required_with:variant_types|array', // Wajib ada jika tipe varian didefinisikan
            'product_variants.*.price' => 'required|numeric|min:0',
            'product_variants.*.stock' => 'required|integer|min:0',
            'product_variants.*.sku_code' => 'nullable|string|max:100',
            'product_variants.*.variant_options' => 'required|array',
            'product_variants.*.variant_options.*.type_name' => 'required|string',
            'product_variants.*.variant_options.*.value' => 'required|string',
        ]);

        // 1. Handle Update Gambar Utama
        if ($request->hasFile('product_image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('product_image')->store('products', 'public');
        }

        // 2. Handle Update Logo Penjual
        if ($request->hasFile('seller_logo')) {
            if ($product->seller_logo && Storage::disk('public')->exists($product->seller_logo)) {
                Storage::disk('public')->delete($product->seller_logo);
            }
            $validated['seller_logo'] = $request->file('seller_logo')->store('seller_logos', 'public');
        }

        // 3. Format Nomor WA
         if ($request->filled('seller_wa')) { // Use filled() to check if it's present and not empty
            $wa = preg_replace('/[^0-9]/', '', $request->seller_wa);
            if (!Str::startsWith($wa, '62')) {
                 $wa = '62' . ltrim($wa, '0');
            }
            $validated['seller_wa'] = $wa;
        } else {
             $validated['seller_wa'] = null; // Set to null if empty
        }

        // 4. Set Nilai Default untuk Penjual jika kosong
        if (empty($validated['store_name'])) {
            $validated['store_name'] = Auth::user()->store->name ?? config('app.default_store_name', 'Toko Sancaka Default');
        }
        if (empty($validated['seller_city'])) {
            $validated['seller_city'] = Auth::user()->store->city ?? config('app.default_store_city', 'Ngawi');
        }

        // 5. Update Slug jika nama berubah
        if ($request->name !== $product->name) {
            $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(5);
        }

        // 6. Generate SKU Otomatis jika KOSONG saat update
        if (empty($validated['sku']) && empty($product->sku) && !empty($validated['category_id'])) {
             $category = Category::find($validated['category_id']);
             $categoryInitial = $category ? strtoupper(substr($category->name, 0, 3)) : 'GEN';
             $productInitial = strtoupper(substr($validated['name'], 0, 3));
             $randomNum = mt_rand(100, 999);
             $validated['sku'] = "{$categoryInitial}-{$productInitial}-{$randomNum}";
        }

        // 7. Handle Tags Otomatis + Manual
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


        // 8. Handle boolean checkbox
        $validated['is_new'] = $request->has('is_new');
        $validated['is_bestseller'] = $request->has('is_bestseller');

        // 9. Jika ada varian, stok utama = 0, harga utama = harga varian pertama
        if ($request->has('product_variants') && count($request->product_variants) > 0) {
            $validated['stock'] = 0;
            $validated['price'] = $request->product_variants[0]['price'] ?? $validated['price'];
        } else {
            // Jika tidak ada varian (dihapus saat edit), kembalikan stok & harga utama
            $validated['stock'] = $request->input('stock', $product->stock); // Ambil dari input atau data lama
            $validated['price'] = $request->input('price', $product->price);
        }

        DB::beginTransaction();
        try {
            // 10. Update Produk
            $product->update($validated);

            // 11. Sinkronisasi Atribut
            $this->syncAttributes($product, $request->input('attributes', []));

             // 12. Sinkronisasi Varian (Tipe, Opsi, Kombinasi)
            // Cek apakah ada data varian yang dikirim dari form
            $hasVariantData = $request->has('variant_types') && $request->has('product_variants');

            if ($hasVariantData) {
                 // Jika ada data varian baru, sinkronkan
                $this->syncVariantTypesAndCombinations($product, $request->variant_types, $request->product_variants);
            } else {
                // Jika tidak ada data varian (dihapus semua di form), hapus semua varian terkait produk ini
                 $product->productVariantTypes()->delete(); // Hapus tipe (opsi akan terhapus jika ada cascade)
                 $product->productVariants()->delete(); // Hapus kombinasi
                 // Pastikan stok utama diisi kembali jika varian dihapus
                 if($product->stock <= 0 && $request->missing('product_variants')) {
                    $product->stock = $request->input('stock', 1); // Set default jika perlu
                    $product->save();
                 }
            }


            DB::commit();
            return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diperbarui.');

        } catch (Exception $e) {
            DB::rollBack();
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

            // Hapus relasi (atribut, varian) - Seharusnya cascade jika foreign key benar
            $product->productAttributes()->delete();
            $product->productVariantTypes()->delete(); // Akan menghapus options jika cascade
            $product->productVariants()->delete();

            // Hapus produk
            $product->delete();

            DB::commit();
            return redirect()->route('admin.products.index')->with('success', 'Produk berhasil dihapus.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting product: ' . $e->getMessage());
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
            $product->increment('stock', $validated['stock']); // Lebih efisien
            return redirect()->route('admin.products.index')->with('success', 'Stok untuk produk ' . e($product->name) . ' berhasil ditambahkan.');
        } catch (Exception $e) {
             Log::error('Error restocking product: ' . $e->getMessage());
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
             Log::error('Error marking product out of stock: ' . $e->getMessage());
             return back()->with('error', 'Gagal menandai habis stok: ' . $e->getMessage());
        }
    }

    // --- Helper Methods ---

    /**
     * Sinkronisasi atribut produk. Menghapus yang lama, membuat yang baru.
     */
    protected function syncAttributes(Product $product, array $attributesData)
    {
        // 1. Hapus atribut lama
        $product->productAttributes()->delete();

        // 2. Siapkan data atribut baru
        $newAttributes = [];
        // Ambil detail atribut dari database berdasarkan slug yang dikirim
        $attributeModels = Attribute::whereIn('slug', array_keys($attributesData))
                                    ->where('category_id', $product->category_id) // Pastikan atribut milik kategori yg benar
                                    ->get()
                                    ->keyBy('slug');

        foreach ($attributesData as $slug => $value) {
            $attributeModel = $attributeModels->get($slug);
            // Hanya simpan jika value tidak kosong/null DAN atribut ada di db
            if (($value !== null && $value !== '') && $attributeModel) {
                 // Jika value adalah array (dari checkbox), simpan sebagai JSON
                $processedValue = is_array($value) ? json_encode(array_values($value)) : $value;

                $newAttributes[] = [
                    'product_id' => $product->id,
                    'attribute_id' => $attributeModel->id, // Gunakan ID dari tabel attributes
                    'attribute_slug' => $slug, // Simpan slug untuk kemudahan lookup di view edit
                    'attribute_name' => $attributeModel->name, // Simpan nama
                    'attribute_type' => $attributeModel->type, // Simpan tipe
                    'value' => $processedValue, // Simpan nilai (bisa string atau JSON string)
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // 3. Masukkan atribut baru (Bulk Insert)
        if (!empty($newAttributes)) {
            ProductAttribute::insert($newAttributes);
        }
    }

     /**
     * Sinkronisasi tipe varian, opsi, dan kombinasi varian produk.
     */
    protected function syncVariantTypesAndCombinations(Product $product, array $variantTypesData, array $productVariantsData)
    {
        // 1. Hapus data varian lama (Tipe, Opsi, Kombinasi)
        // Pastikan foreign key constraint di database diatur ke ON DELETE CASCADE
        // antara product_variant_types -> product_variant_options
        // dan product_variants -> pivot table -> product_variant_options
        $product->productVariants()->each(function($variant) {
             $variant->options()->detach(); // Hapus relasi pivot dulu
        });
        $product->productVariants()->delete(); // Hapus kombinasi
        $product->productVariantTypes()->delete(); // Hapus tipe (opsi akan terhapus jika cascade)


        // 2. Buat Tipe Varian dan Opsi-nya
        $optionIdMap = []; // Map ['TipeNama:OpsiNama' => option_id]

        foreach ($variantTypesData as $typeData) {
            if (empty($typeData['name']) || empty($typeData['options'])) continue;

            $variantType = $product->productVariantTypes()->create(['name' => $typeData['name']]);

            $options = array_map('trim', explode(',', $typeData['options']));
            $options = array_filter($options); // Hapus opsi kosong
            foreach ($options as $optionName) {
                $option = $variantType->options()->create(['name' => $optionName]);
                // Buat map untuk memudahkan lookup saat membuat kombinasi
                $optionIdMap[$variantType->name . ':' . $optionName] = $option->id;
            }
        }

         // 3. Buat Kombinasi Varian (ProductVariant) dan link ke Opsi via Pivot
        foreach ($productVariantsData as $comboData) {
             if (empty($comboData['variant_options'])) continue;

            $combinationStringParts = [];
            $optionIdsForCombination = []; // Kumpulkan ID opsi untuk relasi many-to-many

             // Urutkan berdasarkan nama tipe untuk konsistensi combination_string
            usort($comboData['variant_options'], function ($a, $b) {
                return strcmp($a['type_name'], $b['type_name']);
            });

            foreach ($comboData['variant_options'] as $optionDetail) {
                 $typeName = $optionDetail['type_name'];
                 $valueName = $optionDetail['value'];
                 $mapKey = $typeName . ':' . $valueName;

                 // Pastikan opsi ini ada di map (dibuat di langkah 2)
                 if (isset($optionIdMap[$mapKey])) {
                     $combinationStringParts[] = $mapKey;
                     $optionIdsForCombination[] = $optionIdMap[$mapKey]; // Simpan ID opsi
                 } else {
                      Log::warning("Opsi tidak ditemukan di map saat membuat kombinasi: {$mapKey} for product ID: {$product->id}");
                      continue 2; // Lanjut ke iterasi productVariantsData berikutnya
                 }
            }

            // Buat string kombinasi yang terurut
            sort($combinationStringParts);
            $combinationString = implode(';', $combinationStringParts);

            // Buat data untuk tabel product_variants
             $variantData = [
                 'product_id' => $product->id,
                 'price' => $comboData['price'],
                 'stock' => $comboData['stock'],
                 'sku_code' => $comboData['sku_code'] ?? null,
                 'combination_string' => $combinationString,
                 'created_at' => now(),
                 'updated_at' => now(),
             ];

             // 4. Masukkan kombinasi ke tabel product_variants
             $createdVariant = ProductVariant::create($variantData);

             // 5. Attach/Link ke opsi-opsi via pivot table
             if ($createdVariant && !empty($optionIdsForCombination)) {
                try {
                     // Nama relasi 'options()' harus sesuai dengan yang didefinisikan di Model ProductVariant
                    $createdVariant->options()->attach($optionIdsForCombination);
                } catch (Exception $e) {
                     Log::error("Gagal attach options ke variant {$createdVariant->id}: " . $e->getMessage());
                     // Handle error, mungkin throw exception lagi agar transaksi rollback
                     throw $e; // Re-throw untuk rollback transaksi
                }
             }
        }
    }


}