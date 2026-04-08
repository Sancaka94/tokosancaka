<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\ProductAttribute;
use App\Models\Store;
use App\Models\ProductVariant;
use App\Models\ProductVariantType;
use App\Models\ProductVariantOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Exception;

class ProdukSellerMobileController extends Controller
{
    public function getCategories()
    {
        $categories = Category::where('type', 'product')->orderBy('name')->get(['id', 'name']);
        return response()->json(['success' => true, 'data' => $categories]);
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        // 🔥 PERBAIKAN: Gunakan Query Manual yang lebih aman
        $userId = $user->id_pengguna ?? $user->id;
        $store = \App\Models\Store::where('user_id', $userId)->first();

        if (!$store) {
            return response()->json(['success' => false, 'message' => 'Anda perlu membuat toko terlebih dahulu.'], 403);
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
        return response()->json(['success' => true, 'data' => $products]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $store = $user->store;

        if (!$store) return response()->json(['success' => false, 'message' => 'Toko tidak ditemukan.'], 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('products', 'name')->where('store_id', $store->id)],
            'category_id' => 'required|exists:categories,id',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'weight' => 'required|integer|min:1',
            'product_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status' => 'required|in:active,inactive',
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->where('store_id', $store->id)],
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

        $dataToCreate = $validated;
        $imagePath = null;

        // Cek varian (Jika dikirim sebagai string JSON dari React Native, kita decode)
        $variantTypes = $request->input('variant_types') ? json_decode($request->input('variant_types'), true) : [];
        $hasVariantsRequest = !empty($variantTypes);

        DB::beginTransaction();
        try {
            if ($request->hasFile('product_image')) {
                $imagePath = $request->file('product_image')->store('products', 'public');
                $dataToCreate['image_url'] = $imagePath;
            }

            $dataToCreate['store_id'] = $store->id;
            $dataToCreate['store_name'] = $store->name;
            $dataToCreate['seller_city'] = $store->regency;
            $dataToCreate['seller_name'] = $user->nama_lengkap;
            $dataToCreate['seller_wa'] = $user->no_wa;

            $dataToCreate['slug'] = $this->generateUniqueSlug($validated['name']);
            if (empty($validated['sku'])) {
                $dataToCreate['sku'] = $this->generateSku($validated['name'], $validated['category_id']);
            }

            $category = Category::find($validated['category_id']);
            $manualTags = !empty($request->tags) ? array_map('trim', explode(',', $request->tags)) : [];
            if ($category) $manualTags[] = $category->name;
            $dataToCreate['tags'] = json_encode(array_values(array_unique($manualTags)));
            $dataToCreate['category'] = $category ? $category->name : 'Uncategorized';

            $dataToCreate['is_new'] = filter_var($request->is_new, FILTER_VALIDATE_BOOLEAN);
            $dataToCreate['is_bestseller'] = filter_var($request->is_bestseller, FILTER_VALIDATE_BOOLEAN);
            $dataToCreate['is_promo'] = filter_var($request->is_promo, FILTER_VALIDATE_BOOLEAN);
            $dataToCreate['is_shipping_discount'] = filter_var($request->is_shipping_discount, FILTER_VALIDATE_BOOLEAN);
            $dataToCreate['is_free_shipping'] = filter_var($request->is_free_shipping, FILTER_VALIDATE_BOOLEAN);

            if ($hasVariantsRequest) $dataToCreate['stock'] = 0;

            unset($dataToCreate['product_image'], $dataToCreate['attributes'], $dataToCreate['variant_types'], $dataToCreate['product_variants']);

            $product = Product::create($dataToCreate);

            // 👇 TAMBAHKAN BLOK KODE INI JUGA 👇
            $product->length = $request->input('length', 0);
            $product->width = $request->input('width', 0);
            $product->height = $request->input('height', 0);
            $product->is_promo = filter_var($request->input('is_promo'), FILTER_VALIDATE_BOOLEAN);
            $product->is_shipping_discount = filter_var($request->input('is_shipping_discount'), FILTER_VALIDATE_BOOLEAN);
            $product->save();
            // 👆 ============================= 👆

            // Decode Attributes dari React Native
            $attributes = $request->input('attributes') ? json_decode($request->input('attributes'), true) : [];
            if (!empty($attributes)) $this->syncAttributes($product, $attributes);

            if ($hasVariantsRequest) $this->syncVariantTypes($product, $variantTypes);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Produk berhasil ditambahkan.', 'data' => $product]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Gagal simpan produk API: ' . $e->getMessage());
            if ($imagePath && Storage::disk('public')->exists($imagePath)) Storage::disk('public')->delete($imagePath);
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    public function show($slug)
    {
        $user = Auth::user();
        if (!$user || !$user->store) return response()->json(['success' => false], 403);

        $produk = Product::where('slug', $slug)
                    ->where('store_id', $user->store->id)
                    ->with(['category', 'productAttributes', 'productVariantTypes.options', 'productVariants.options'])
                    ->firstOrFail();

        return response()->json(['success' => true, 'data' => $produk]);
    }

    public function update(Request $request, $slug)
    {
        $user = Auth::user();
        if (!$user || !$user->store) return response()->json(['success' => false], 403);
        $storeId = $user->store->id;

        $product = Product::where('slug', $slug)->where('store_id', $storeId)->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('products', 'name')->where('store_id', $storeId)->ignore($product->id)],
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'weight' => 'required|integer|min:1',
            'product_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status' => 'required|in:active,inactive',
        ]);

        $dataToUpdate = $validated;

        $variantTypes = $request->input('variant_types') ? json_decode($request->input('variant_types'), true) : [];
        $productVariants = $request->input('product_variants') ? json_decode($request->input('product_variants'), true) : [];
        $hasVariantsRequest = !empty($variantTypes);

        DB::beginTransaction();
        try {
            if ($request->hasFile('product_image')) {
                if ($product->image_url && Storage::disk('public')->exists($product->image_url)) Storage::disk('public')->delete($product->image_url);
                $dataToUpdate['image_url'] = $request->file('product_image')->store('products', 'public');
            }

            if ($request->name !== $product->name) $dataToUpdate['slug'] = $this->generateUniqueSlug($request->name, $product->id);

            $category = Category::find($validated['category_id']);
            if ($category) $dataToUpdate['category'] = $category->name;

            $dataToUpdate['is_new'] = filter_var($request->is_new, FILTER_VALIDATE_BOOLEAN);
            $dataToUpdate['is_bestseller'] = filter_var($request->is_bestseller, FILTER_VALIDATE_BOOLEAN);
            $dataToUpdate['is_promo'] = filter_var($request->is_promo, FILTER_VALIDATE_BOOLEAN);
            $dataToUpdate['is_shipping_discount'] = filter_var($request->is_shipping_discount, FILTER_VALIDATE_BOOLEAN);
            $dataToUpdate['is_free_shipping'] = filter_var($request->is_free_shipping, FILTER_VALIDATE_BOOLEAN);


            if ($hasVariantsRequest) $dataToUpdate['stock'] = 0;

            unset($dataToUpdate['product_image'], $dataToUpdate['attributes'], $dataToUpdate['variant_types'], $dataToUpdate['product_variants']);

            $product->update($dataToUpdate);

            $attributes = $request->input('attributes') ? json_decode($request->input('attributes'), true) : [];
            $this->syncAttributes($product, $attributes);

            $totalStock = $validated['stock'];
            if ($hasVariantsRequest) {
                $totalStock = $this->syncVariantTypesAndCombinations($product, $variantTypes, $productVariants);
            } else {
                $product->productVariants()->delete();
                $product->productVariantTypes()->delete();
            }

            $product->stock = $totalStock;
            // 👇 TAMBAHKAN BLOK KODE INI (PAKSA SIMPAN) 👇
            $product->length = $request->input('length', 0);
            $product->width = $request->input('width', 0);
            $product->height = $request->input('height', 0);
            $product->is_promo = filter_var($request->input('is_promo'), FILTER_VALIDATE_BOOLEAN);
            $product->is_shipping_discount = filter_var($request->input('is_shipping_discount'), FILTER_VALIDATE_BOOLEAN);
            // 👆 ====================================== 👆

            $product->save();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Produk diperbarui.']);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update Produk API Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($slug)
    {
        $userStore = Auth::user()->store;
        $product = Product::where('slug', $slug)->where('store_id', $userStore->id)->firstOrFail();

        DB::beginTransaction();
        try {
            if ($product->image_url && Storage::disk('public')->exists($product->image_url)) {
                Storage::disk('public')->delete($product->image_url);
            }
            $product->productAttributes()->delete();
            $product->productVariants()->delete();
            $product->productVariantTypes()->delete();
            $product->delete();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Produk dihapus.']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal hapus: ' . $e->getMessage()], 500);
        }
    }

    // ==========================================
    // HELPER FUNCTIONS (Sama Persis Seperti Web)
    // ==========================================
    protected function generateUniqueSlug(string $name, int $ignoreId = null): string {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;
        $query = Product::where('slug', $slug);
        if ($ignoreId) $query->where('id', '!=', $ignoreId);
        if (Auth::check() && Auth::user()->store) $query->where('store_id', Auth::user()->store->id);

        while ($query->exists()) {
            $slug = $originalSlug . '-' . $count++;
            $query = Product::where('slug', $slug);
            if ($ignoreId) $query->where('id', '!=', $ignoreId);
            if (Auth::check() && Auth::user()->store) $query->where('store_id', Auth::user()->store->id);
        }
        return $slug;
    }

    protected function generateSku(string $productName, int $categoryId): string {
        $category = Category::find($categoryId);
        $categoryInitial = $category ? strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $category->name), 0, 3)) : 'GEN';
        $productInitial = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $productName), 0, 3));
        $randomNum = mt_rand(100, 999);
        $sku = "{$categoryInitial}-{$productInitial}-{$randomNum}";

        $query = Product::where('sku', $sku);
        if (Auth::check() && Auth::user()->store) $query->where('store_id', Auth::user()->store->id);

        while ($query->exists()) {
            $randomNum = mt_rand(100, 999);
            $sku = "{$categoryInitial}-{$productInitial}-{$randomNum}";
            $query = Product::where('sku', $sku);
            if (Auth::check() && Auth::user()->store) $query->where('store_id', Auth::user()->store->id);
        }
        return $sku;
    }

    protected function syncAttributes(Product $product, ?array $attributesData) {
        if ($attributesData === null) { $product->productAttributes()->delete(); return; }
        $currentAttributeNames = [];
        $validAttributesInfo = Attribute::where('category_id', $product->category_id)->whereIn('slug', array_keys($attributesData))->get()->keyBy('slug');

        foreach ($attributesData as $slug => $value) {
            $attributeInfo = $validAttributesInfo->get($slug);
            $attributeName = $attributeInfo->name ?? str_replace('-', ' ', Str::title($slug));
            if (!empty($attributeName) && ($value !== null && $value !== '' && (!is_array($value) || !empty(array_filter($value))))) {
                $processedValue = is_array($value) ? json_encode(array_values(array_filter($value))) : $value;
                ProductAttribute::updateOrCreate(
                    ['product_id' => $product->id, 'name' => $attributeName],
                    ['value' => $processedValue]
                );
                $currentAttributeNames[] = $attributeName;
            }
        }
        $product->productAttributes()->whereNotIn('name', $currentAttributeNames)->delete();
    }

    protected function syncVariantTypes(Product $product, array $variantTypesData) {
        $currentTypeIds = [];
        $syncedTypes = collect();

        foreach ($variantTypesData as $typeData) {
            if (empty($typeData['name']) || empty($typeData['options'])) continue;
            $variantType = ProductVariantType::updateOrCreate(['product_id' => $product->id, 'name' => $typeData['name']]);
            $currentTypeIds[] = $variantType->id;

            $optionNames = array_map('trim', explode(',', $typeData['options']));
            $currentOptionIds = [];
            foreach ($optionNames as $optionName) {
                if(empty($optionName)) continue;
                $option = ProductVariantOption::updateOrCreate(['product_variant_type_id' => $variantType->id, 'name' => $optionName]);
                $currentOptionIds[] = $option->id;
            }
            $variantType->options()->whereNotIn('id', $currentOptionIds)->delete();
            $syncedTypes->push($variantType->load('options'));
        }
        $product->productVariantTypes()->whereNotIn('id', $currentTypeIds)->delete();
        return $syncedTypes;
    }

    protected function syncVariantTypesAndCombinations(Product $product, array $variantTypesData, ?array $productVariantsData) {
        $syncedTypes = $this->syncVariantTypes($product, $variantTypesData);
        $optionNameMap = $syncedTypes->flatMap(fn($type) => $type->options)->pluck('id', 'name');

        $currentVariantIds = [];
        $totalStock = 0;

        if (empty($productVariantsData)) {
            $product->productVariants()->delete(); return 0;
        }

        foreach ($productVariantsData as $variantData) {
            $sku = $variantData['sku'] ?? null;
            $price = $variantData['price'] ?? 0;
            $stock = $variantData['stock'] ?? 0;
            $optionsInCombination = $variantData['options'] ?? [];

            if (empty($optionsInCombination)) continue;
            $combinationString = implode(';', $optionsInCombination);

            $variant = null;
            if (!empty($sku)) {
                $variant = ProductVariant::updateOrCreate(
                    ['product_id' => $product->id, 'sku_code' => $sku],
                    ['price' => $price, 'stock' => $stock, 'combination_string' => $combinationString]
                );
            } else {
                $variant = ProductVariant::updateOrCreate(
                    ['product_id' => $product->id, 'combination_string' => $combinationString],
                    ['price' => $price, 'stock' => $stock, 'sku_code' => $sku]
                );
            }

            $optionIdsToSync = [];
            foreach ($optionsInCombination as $typeName => $optionName) {
                if (isset($optionNameMap[$optionName])) $optionIdsToSync[] = $optionNameMap[$optionName];
            }
            if (!empty($optionIdsToSync)) $variant->options()->sync($optionIdsToSync);

            $currentVariantIds[] = $variant->id;
            $totalStock += (int)$stock;
        }

        $product->productVariants()->whereNotIn('id', $currentVariantIds)->delete();
        return $totalStock;
    }

    public function getAttributes($categoryId)
    {
        // Ambil atribut berdasarkan kategori
        $attributes = \App\Models\Attribute::where('category_id', $categoryId)->get();
        return response()->json(['success' => true, 'data' => $attributes]);
    }
}
