<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use Exception;

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
                $data = Product::with('category')->select('products.*');
                return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('image', function ($row) {
                        $url = $row->image_url ? asset('storage/' . $row->image_url) : 'https://placehold.co/80x80/EFEFEF/333333?text=N/A';
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
                        $editUrl = route('admin.products.edit', $row->id);
                        $deleteUrl = route('admin.products.destroy', $row->id);
                        $outOfStockUrl = route('admin.products.outOfStock', $row->id);

                        $actionBtn = '<div class="d-flex justify-content-center gap-2">';
                        $actionBtn .= '<button type="button" onclick="openRestockModal('.$row->id.', \''.e($row->name).'\')" class="btn btn-success btn-circle btn-sm" title="Restock"><i class="fas fa-plus"></i></button>';
                        $actionBtn .= '<a href="'.e($editUrl).'" class="btn btn-warning btn-circle btn-sm" title="Edit"><i class="fas fa-pen-to-square"></i></a>';
                        $actionBtn .= '<form action="'.e($outOfStockUrl).'" method="POST" class="d-inline" onsubmit="return confirm(\'Anda yakin ingin menandai produk ini habis?\');">'.csrf_field().method_field('PATCH').'<button type="submit" class="btn btn-secondary btn-circle btn-sm" title="Tandai Habis"><i class="fas fa-box-open"></i></button></form>';
                        $actionBtn .= '<form action="'.e($deleteUrl).'" method="POST" class="d-inline" onsubmit="return confirm(\'Anda yakin ingin menghapus produk ini?\');">'.csrf_field().method_field('DELETE').'<button type="submit" class="btn btn-danger btn-circle btn-sm" title="Hapus"><i class="fas fa-trash"></i></button></form>';
                        $actionBtn .= '</div>';
                        
                        return $actionBtn;
                    })
                    ->rawColumns(['action', 'image', 'status_badge'])
                    ->make(true);

            } catch (Exception $e) {
                \Log::error('DataTables Error: ' . $e->getMessage());
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
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'price'         => 'required|numeric|min:0',
            'stock'         => 'required|integer|min:0',
            'weight'        => 'required|integer|min:0',
            'category_id'   => 'required|exists:categories,id',
            'product_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'store_name'    => 'required|string|max:255',
            'seller_city'   => 'required|string|max:255',
            'seller_wa'     => 'nullable|string|max:20',
            'seller_logo'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'attributes'    => 'nullable|array',
        ]);
    
        if ($request->hasFile('product_image')) {
            $path = $request->file('product_image')->store('products', 'public');
            $validated['image_url'] = $path;
        }
    
        if ($request->hasFile('seller_logo')) {
            $logoPath = $request->file('seller_logo')->store('seller_logos', 'public');
            $validated['seller_logo'] = $logoPath;
        }
        
        if (!empty($request->seller_wa)) {
            $wa = preg_replace('/[^0-9]/', '', $request->seller_wa); 
            if (Str::startsWith($wa, '0')) {
                $wa = '62' . substr($wa, 1);
            } elseif (!Str::startsWith($wa, '62')) {
                $wa = '62' . $wa;
            }
            $validated['seller_wa'] = $wa;
        }
    
        $validated['slug'] = Str::slug($validated['name']) . '-' . uniqid();
        $validated['attributes_data'] = json_encode($request->input('attributes', []));
    
        Product::create($validated);
    
        return redirect()->route('admin.products.index')->with('success', 'Produk baru berhasil ditambahkan.');
    }

    /**
     * Memperbarui data produk di database.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'price'         => 'required|numeric|min:0',
            'original_price'=> 'nullable|numeric|min:0|gt:price',
            'stock'         => 'required|integer|min:0',
            'weight'        => 'required|integer|min:0',
            'category_id'   => 'required|exists:categories,id',
            'product_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status'        => 'required|in:active,inactive',
            'tags'          => 'nullable|string',
            'is_new'        => 'nullable|boolean',
            'is_bestseller' => 'nullable|boolean',
            'store_name'    => 'required|string|max:255',
            'seller_city'   => 'required|string|max:255',
            'seller_wa'     => 'nullable|string|max:20',
            'seller_logo'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'attributes'    => 'nullable|array',
        ]);
    
        if ($request->hasFile('product_image')) {
            if ($product->image_url) Storage::disk('public')->delete($product->image_url);
            $path = $request->file('product_image')->store('products', 'public');
            $validated['image_url'] = $path;
        }
    
        if ($request->hasFile('seller_logo')) {
            if ($product->seller_logo) Storage::disk('public')->delete($product->seller_logo);
            $logoPath = $request->file('seller_logo')->store('seller_logos', 'public');
            $validated['seller_logo'] = $logoPath;
        }
    
        if ($request->name !== $product->name) {
            $validated['slug'] = Str::slug($validated['name']) . '-' . uniqid();
        }
        
        if (!empty($request->seller_wa)) {
            $wa = preg_replace('/[^0-9]/', '', $request->seller_wa);
            if (Str::startsWith($wa, '0')) {
                $wa = '62' . substr($wa, 1);
            } elseif (!Str::startsWith($wa, '62')) {
                $wa = '62' . $wa;
            }
            $validated['seller_wa'] = $wa;
        }
    
        $validated['is_new'] = $request->has('is_new');
        $validated['is_bestseller'] = $request->has('is_bestseller');
    
        if (!empty($request->tags)) {
            $validated['tags'] = json_encode(array_map('trim', explode(',', $request->tags)));
        } else {
            $validated['tags'] = null;
        }

        $validated['attributes_data'] = json_encode($request->input('attributes', []));
    
        $product->update($validated);
    
        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diperbarui.');
    }


        /**
     * Menampilkan form untuk mengedit produk.
     */
    public function edit($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return redirect()->route('admin.products.index')->with('error', 'Produk tidak ditemukan.');
        }
        // PERBAIKAN: Mengambil data Kategori dari tabel 'categories' yang tipenya 'marketplace'
        $categories = Category::where('type', 'product')->orderBy('name')->get();
        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Menghapus produk dari database.
     */
    public function destroy($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return redirect()->route('admin.products.index')->with('error', 'Produk tidak ditemukan.');
        }

        if ($product->image_url) {
            Storage::disk('public')->delete($product->image_url);
        }
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil dihapus.');
    }

    /**
     * Menambahkan stok untuk produk tertentu.
     */
    public function restock(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return redirect()->route('admin.products.index')->with('error', 'Produk tidak ditemukan.');
        }

        $validated = $request->validate([ 'stock' => 'required|integer|min:1' ]);
        $product->stock += $validated['stock'];
        $product->save();

        return redirect()->route('admin.products.index')->with('success', 'Stok untuk produk ' . e($product->name) . ' berhasil ditambahkan.');
    }

    /**
     * Menandai produk sebagai habis (stok = 0).
     */
    public function markAsOutOfStock($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return redirect()->route('admin.products.index')->with('error', 'Produk tidak ditemukan.');
        }

        $product->stock = 0;
        $product->save();

        return redirect()->route('admin.products.index')->with('success', 'Stok untuk produk ' . e($product->name) . ' telah diatur menjadi 0.');
    }

    
}

