<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    /**
     * -------------------------------------------------------------------------
     * METODE PUBLIK (ETALASE)
     * -------------------------------------------------------------------------
     */

    public function index(Request $request)
    {
        $query = Product::with('category') // Menggunakan relasi categoryData
            ->where('status', 'active')
            ->where('stock', '>', 0);

        // Filter berdasarkan ID Kategori
        if ($request->filled('categories')) {
            $query->whereIn('category_id', $request->input('categories'));
        }
        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->input('price_min'));
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->input('price_max'));
        }
        if ($request->filled('rating')) {
            $query->where('rating', '>=', $request->input('rating'));
        }

        $sortBy = $request->input('sort', 'latest');
        switch ($sortBy) {
            case 'price_asc': $query->orderBy('price', 'asc'); break;
            case 'price_desc': $query->orderBy('price', 'desc'); break;
            case 'bestseller': $query->orderBy('sold_count', 'desc'); break;
            default: $query->latest(); break;
        }

        $products = $query->paginate(12)->withQueryString();
        
        $categories = Category::whereHas('products', function ($q) {
            $q->where('status', 'active')->where('stock', '>', 0);
        })->orderBy('name')->get();

        return view('etalase.index', compact('products', 'categories'));
    }

    public function show(Product $product)
    {
        if ($product->status !== 'active') {
             abort(404, 'Produk tidak ditemukan atau tidak aktif.');
        }

        // Eager load SEMUA relasi yang dibutuhkan
        $product->load(
            'category.attributes',    // Relasi kategori yang sudah diperbaiki
            'productAttributes',          // Relasi atribut custom
            'store.user',                      // Relasi toko
            'productVariantTypes.options',// Tipe Varian (Warna, Ukuran) & Opsi-nya
            'productVariants'             // Semua SKU/Kombinasi Varian
        );

        // Ambil produk terkait
        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('status', 'active')
            ->limit(4)
            ->get();

        // Kirim data varian sebagai JSON agar mudah dibaca JavaScript
        $variantData = [
            'variants' => $product->productVariants,
            'types'    => $product->productVariantTypes
        ];

        return view('etalase.show', compact('product', 'relatedProducts', 'variantData'));
    }

    public function profileToko($name)
    {
        // Gunakan store_slug jika ada, fallback ke nama
        $store = Store::where('slug', $name)->orWhere('name', $name)->firstOrFail();

        $products = Product::where('store_id', $store->id)
            ->where('status', 'active')
            ->where('stock', '>', 0)
            ->paginate(12);

        return view('etalase.toko', compact('products', 'store'));
    }

    /**
     * -------------------------------------------------------------------------
     * METODE ADMIN
     * -------------------------------------------------------------------------
     */

    public function adminIndex(Request $request)
    {
        if ($request->ajax()) {
            $data = Product::with('categoryData')->query(); 

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('image', function ($row) {
                    $url = $row->image_url ? asset('storage/' . $row->image_url) : 'https://placehold.co/80x80/EFEFEF/333333?text=N/A';
                    return '<img src="' . $url . '" class="rounded" width="80" />';
                })
                ->addColumn('category_name', function ($row) {
                    return $row->categoryData?->name ?? 'N/A'; 
                })
                ->editColumn('price', function ($row) {
                    return 'Rp' . number_format($row->price, 0, ',', '.');
                })
                ->addColumn('status_badge', function ($row) {
                    $color = $row->status == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                    return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $color . '">' . ucfirst($row->status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $viewUrl = route('products.show', $row->slug);
                    $editUrl = route('admin.products.edit', $row->id);
                    $deleteUrl = route('admin.products.destroy', $row->id);
                    $outOfStockUrl = route('admin.products.outOfStock', $row->id);

                    $actionBtn = '<div class="d-flex gap-2 align-items-center">';
                    $actionBtn .= '<a href="' . $viewUrl . '" target="_blank" class="btn btn-sm btn-outline-success" title="Lihat di Etalase"><i class="fas fa-eye"></i></a>';
                    $actionBtn .= '<a href="' . $editUrl . '" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-pencil-alt"></i></a>';

                    if ($row->stock > 0) {
                        $actionBtn .= '<form action="' . $outOfStockUrl . '" method="POST" onsubmit="return confirm(\'Jadikan stok habis?\');" class="d-inline m-0 p-0">'
                            . csrf_field() . method_field('POST') .
                            '<button type="submit" class="btn btn-sm btn-outline-warning" title="Tandai Habis"><i class="fas fa-box-open"></i></button></form>';
                    } else {
                        $actionBtn .= '<button onclick="openRestockModal(' . $row->id . ', \'' . e($row->name) . '\')" type="button" class="btn btn-sm btn-outline-info" title="Restock"><i class="fas fa-check-circle"></i></button>';
                    }

                    $actionBtn .= '<form action="' . $deleteUrl . '" method="POST" onsubmit="return confirm(\'Yakin?\');" class="d-inline m-0 p-0">'
                        . csrf_field() . method_field('DELETE') .
                        '<button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="fas fa-trash"></i></button></form>';

                    $actionBtn .= '</div>';
                    return $actionBtn;
                })
                ->rawColumns(['action', 'image', 'status_badge'])
                ->make(true);
        }
        return view('admin.products.index');
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'product_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
            'sku' => 'required|string|max:255|unique:products,sku',
            'category_id' => 'required|integer|exists:categories,id',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'weight' => 'required|integer|min:0',
            'status' => 'required|in:active,inactive',
            'tags' => 'nullable|string',
            'category' => 'nullable|string|max:255', // Kolom lama (string), biarkan null
        ]);

        if ($request->hasFile('product_image')) {
            $path = $request->file('product_image')->store('uploads/products', 'public');
            $validatedData['image_url'] = $path;
        }

        $validatedData['slug'] = Str::slug($validatedData['name']) . '-' . uniqid();

        if ($request->filled('original_price') && $request->original_price > $request->price) {
            $discount = (($request->original_price - $request->price) / $request->original_price) * 100;
            $validatedData['discount_percentage'] = round($discount, 2);
        }

        if (!empty($request->tags)) {
            $validatedData['tags'] = array_map('trim', explode(',', $request->tags));
        }

        $product = Product::create($validatedData);

        // --- Logika Penyimpanan Atribut Relasional ---
        $mainFields = array_keys($validatedData);
        $mainFields[] = '_token';
        $mainFields[] = 'product_image';

        $attributesData = $request->except($mainFields);

        if (!empty($attributesData)) {
            foreach ($attributesData as $attributeName => $attributeValue) {
                // Konversi nama field (misal: 'jenis_izin') menjadi 'Jenis Izin'
                $displayName = ucwords(str_replace('_', ' ', $attributeName));

                if (is_array($attributeValue)) {
                    $attributeValue = implode(', ', $attributeValue);
                }
                if (!is_null($attributeValue) && $attributeValue !== '') {
                    $product->productAttributes()->create([
                        'name' => $displayName, // Menyimpan NAMA
                        'value' => $attributeValue
                    ]);
                }
            }
        }

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil ditambahkan!');
    }

    public function edit(Product $product)
    {
        $product->load('productAttributes', 'categoryData');
        $categories = Category::orderBy('name')->get();
        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * PERBAIKAN UTAMA DI SINI
     * Menggunakan Route Model Binding (Product $product)
     * Menggunakan logika Relasional, BUKAN JSON
     */
    public function update(Request $request, Product $product)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'product_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
            'sku' => 'required|string|max:255|unique:products,sku,' . $product->id,
            'category_id' => 'required|integer|exists:categories,id',
            'description' => 'nullable|string',
            'store_name' => 'nullable|string|max:255',
            'seller_city' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'weight' => 'required|integer|min:0',
            'status' => 'required|in:active,inactive',
            'is_new' => 'nullable|boolean',
            'is_bestseller' => 'nullable|boolean',
            'tags' => 'nullable|string',
            'category' => 'nullable|string|max:255', // Kolom lama (string), biarkan null
        ]);

        if ($request->hasFile('product_image')) {
            if ($product->image_url) {
                Storage::disk('public')->delete($product->image_url);
            }
            $path = $request->file('product_image')->store('uploads/products', 'public');
            $validatedData['image_url'] = $path;
        }

        if ($request->name !== $product->name) {
            $validatedData['slug'] = Str::slug($validatedData['name']) . '-' . uniqid();
        }

        $validatedData['is_new'] = $request->has('is_new');
        $validatedData['is_bestseller'] = $request->has('is_bestseller');

        if (!empty($request->tags)) {
            $validatedData['tags'] = array_map('trim', explode(',', $request->tags));
        }

        if ($request->filled('original_price') && $request->original_price > $request->price) {
            $discount = (($request->original_price - $request->price) / $request->original_price) * 100;
            $validatedData['discount_percentage'] = round($discount, 2);
        } else {
             $validatedData['discount_percentage'] = null;
        }

        // Update data produk utama
        $product->update($validatedData);

        // --- LOGIKA SINKRONISASI ATRIBUT (RELASIONAL) ---
        $mainFields = array_keys($validatedData);
        $mainFields[] = '_token';
        $mainFields[] = '_method';
        $mainFields[] = 'product_image';
        
        // Ambil semua input kecuali field utama
        $attributesData = $request->except($mainFields);

        // Hapus semua atribut lama
        $product->productAttributes()->delete();

        // Buat ulang atribut berdasarkan data form
        if (!empty($attributesData)) {
            foreach ($attributesData as $attributeName => $attributeValue) {
                
                // Ganti nama field dari form (misal: 'jenis_izin') menjadi 'Jenis Izin'
                $displayName = ucwords(str_replace('_', ' ', $attributeName));

                if (is_array($attributeValue)) {
                    $attributeValue = implode(', ', $attributeValue);
                }
                
                if (!is_null($attributeValue) && $attributeValue !== '') {
                    $product->productAttributes()->create([
                        'name' => $displayName, // <-- MENYIMPAN NAMA YANG BENAR
                        'value' => $attributeValue
                    ]);
                }
            }
        }
        // --- AKHIR PERBAIKAN ---

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diperbarui!');
    }

    public function destroy(Product $product)
    {
        if ($product->image_url) {
            Storage::disk('public')->delete($product->image_url);
        }
        // Hapus juga atribut dan varian terkait
        $product->productAttributes()->delete();
        $product->productVariants()->delete();
        $product->productVariantTypes()->delete();
        
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil dihapus.');
    }

    public function markAsOutOfStock(Product $product)
    {
        $product->update(['stock' => 0]);
        return redirect()->route('admin.products.index')->with('success', 'Stok produk berhasil diatur menjadi 0.');
    }

    public function restock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'stock' => 'required|integer|min:1',
        ]);
        $product->update(['stock' => $validated['stock']]);
        return redirect()->route('admin.products.index')->with('success', 'Stok produk berhasil ditambahkan.');
    }

    public function getAttributes(Category $category)
    {
        $category->load('attributes'); 
        return response()->json($category->attributes);
    }
}