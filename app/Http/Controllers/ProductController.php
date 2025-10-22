<?php



namespace App\Http\Controllers;



use Illuminate\Http\Request;

use App\Models\Product;

use Illuminate\Support\Str;

use Illuminate\Support\Facades\Storage;

use Intervention\Image\ImageManager;

use Intervention\Image\Drivers\Gd\Driver as GdDriver;

use Yajra\DataTables\Facades\DataTables;

use App\Models\Category;





class ProductController extends Controller

{

    /**

     * Menampilkan halaman etalase publik (hanya produk yang aktif dan stoknya ada).

     */

    public function index(Request $request)

    {

        $query = Product::where('status', 'active')->where('stock', '>', 0);



        if ($request->filled('categories')) {

            $query->whereIn('category', $request->input('categories'));

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

        $categories = Product::where('status', 'active')->distinct()->pluck('category');



        return view('admin.products.index', compact('products', 'categories'));

    }



    /**

     * Menampilkan halaman detail publik untuk satu produk.

     */

    public function show(Product $product)

    {

        $relatedProducts = Product::where('category', $product->category)

                                      ->where('id', '!=', $product->id)

                                      ->where('status', 'active')

                                      ->limit(4)->get();

        return view('etalase.show', compact('product', 'relatedProducts'));

    }



    /**

     * Menampilkan daftar semua produk di area admin dengan fitur pencarian.

     */

    public function adminIndex(Request $request)

    {

        if ($request->ajax()) {

            $data = Product::query();

            return DataTables::of($data)

                ->addIndexColumn()

                ->addColumn('image', function ($row) {

                    $url = $row->image_url ? asset('storage/' . $row->image_url) : 'https://placehold.co/80x80/EFEFEF/333333?text=N/A';

                    return '<img src="' . $url . '" class="rounded" width="80" />';

                })

                ->editColumn('price', function ($row) {

                    return 'Rp' . number_format($row->price, 0, ',', '.');

                })

                ->addColumn('status_badge', function ($row) {

                    $color = $row->status == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';

                    return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $color . '">' . ucfirst($row->status) . '</span>';

                })

                ->addColumn('action', function($row){

    $viewUrl = route('products.show', $row->slug);

    $editUrl = route('admin.products.edit', $row->id);



    $actionBtn = '<div class="d-flex gap-2 align-items-center">';



    // Tombol Lihat

    $actionBtn .= '<a href="'.$viewUrl.'" target="_blank" class="btn btn-sm btn-outline-success" title="Lihat di Etalase">

                    <i class="fas fa-eye"></i>

                   </a>';



    // Tombol Edit

    $actionBtn .= '<a href="'.$editUrl.'" class="btn btn-sm btn-outline-primary" title="Edit">

                    <i class="fas fa-pencil-alt"></i>

                   </a>';



    // Tombol Stok Habis atau Restock

    if ($row->stock > 0) {

        $actionBtn .= '<form action="'.route('admin.products.outOfStock', $row->id).'" method="POST" onsubmit="return confirm(\'Jadikan stok habis?\');" class="d-inline m-0 p-0">

                        '.csrf_field().method_field('POST').'

                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Tandai Habis">

                            <i class="fas fa-box-open"></i>

                        </button>

                       </form>';

    } else {

        $actionBtn .= '<button onclick="openRestockModal('.$row->id.', \''.e($row->name).'\')" type="button" class="btn btn-sm btn-outline-info" title="Restock">

                        <i class="fas fa-check-circle"></i>

                       </button>';

    }



    // Tombol Hapus

    $actionBtn .= '<form action="'.route('admin.products.destroy', $row->id).'" method="POST" onsubmit="return confirm(\'Yakin?\');" class="d-inline m-0 p-0">

                    '.csrf_field().method_field('DELETE').'

                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">

                        <i class="fas fa-trash"></i>

                    </button>

                   </form>';



    $actionBtn .= '</div>';

    return $actionBtn;

})



                ->rawColumns(['action', 'image', 'status_badge'])

                ->make(true);

        }

        return view('admin.products.index');

    }



    /**

     * Menampilkan detail produk di area admin.

     */

    public function adminShow(Product $product)

    {

        return view('admin.products.show', compact('product'));

    }



    /**

     * Menampilkan form untuk membuat produk baru di area admin.

     */

    public function create()

    {

        $categories = Product::distinct()->pluck('category');

        return view('admin.products.create', compact('categories'));

    }



    /**

     * Menyimpan produk baru ke database.

     */

    public function store(Request $request)

    {

        $validatedData = $request->validate([

            'name' => 'required|string|max:255',

            'product_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:5120',

            'sku' => 'required|string|max:255|unique:products,sku',

            'category' => 'required|string|max:255',

            'description' => 'nullable|string',

            'price' => 'required|numeric|min:0',

            'original_price' => 'nullable|numeric|min:0',

            'stock' => 'required|integer|min:0',

            'weight' => 'required|integer|min:0',

            'status' => 'required|in:active,inactive',

        ]);



        // --- Ganti di dalam method store() ---

    if ($request->hasFile('product_image')) {

    // Simpan ke storage/app/public/uploads/products

    $path = $request->file('product_image')->store('uploads/products', 'public');

    $validatedData['image_url'] = $path;

    }



        $validatedData['slug'] = Str::slug($validatedData['name']) . '-' . uniqid();

        

        if ($request->filled('original_price') && $request->original_price > $request->price) {

            $discount = (($request->original_price - $request->price) / $request->original_price) * 100;

            $validatedData['discount_percentage'] = round($discount, 2);

        }



        Product::create($validatedData);



        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil ditambahkan!');

    }



    /**

     * Menampilkan form untuk mengedit produk.

     */

    // KODE BARU UNTUK DEBUGGING

public function edit($id)

{

    // Coba cari produk secara manual menggunakan ID dari URL

    $product = \App\Models\Product::find($id);



    // Jika produk tetap tidak ditemukan, tampilkan error 404 dengan pesan khusus

    if (!$product) {

        abort(404, 'DEBUG: Produk tidak ditemukan saat dicari secara manual.');

    }



    // Jika produk ditemukan, lanjutkan seperti biasa

    $categories = \App\Models\Product::distinct()->pluck('category');

    return view('admin.products.edit', compact('product', 'categories'));

}



    /**

     * Memperbarui data produk di database.

     */

   // KODE BARU UNTUK MEM-BYPASS MASALAH

public function update(Request $request, $id)

{

    // Cari produk secara manual

    $product = \App\Models\Product::find($id);

    if (!$product) {

        abort(404, 'Produk tidak ditemukan saat akan di-update.');

    }



    // --- SISA KODE ANDA TETAP SAMA DARI SINI KE BAWAH ---

    $validatedData = $request->validate([

        'name' => 'required|string|max:255',

        'product_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',

        'sku' => 'required|string|max:255|unique:products,sku,' . $product->id,

        'category' => 'required|string|max:255',

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

    ]);



    if ($request->hasFile('product_image')) {

        if ($product->image_url) {

            \Illuminate\Support\Facades\Storage::disk('public')->delete($product->image_url);

        }

        $path = $request->file('product_image')->store('uploads/products', 'public');

        $validatedData['image_url'] = $path;

    }



    if ($request->name !== $product->name) {

        $validatedData['slug'] = \Illuminate\Support\Str::slug($validatedData['name']) . '-' . uniqid();

    }



    $validatedData['is_new'] = $request->has('is_new');

    $validatedData['is_bestseller'] = $request->has('is_bestseller');

    

    if (!empty($request->tags)) {

        $validatedData['tags'] = json_encode(array_map('trim', explode(',', $request->tags)));

    }



    if ($request->filled('original_price') && $request->original_price > $request->price) {

        $discount = (($request->original_price - $request->price) / $request->original_price) * 100;

        $validatedData['discount_percentage'] = round($discount, 2);

    }



    $product->update($validatedData);



    return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diperbarui!');

}

    /**

     * Menghapus produk dari database.

     */

// --- Ganti di dalam method destroy() ---

public function destroy(Product $product)

{

    // Hapus file gambar dari storage

    if ($product->image_url) {

        Storage::disk('public')->delete($product->image_url);

    }

    $product->delete();

    return redirect()->route('admin.products.index')->with('success', 'Produk berhasil dihapus.');

}



    /**

     * Menandai produk sebagai habis (stok = 0).

     */

    public function markAsOutOfStock(Product $product)

    {

        $product->update(['stock' => 0]);

        return redirect()->route('admin.products.index')->with('success', 'Stok produk berhasil diatur menjadi 0.');

    }

    

    /**

     * Menambahkan stok kembali ke produk yang habis.

     */

    public function restock(Request $request, Product $product)

    {

        $validated = $request->validate([

            'stock' => 'required|integer|min:1',

        ]);



        $product->update(['stock' => $validated['stock']]);

        return redirect()->route('admin.products.index')->with('success', 'Stok produk berhasil ditambahkan.');

    }

    

    public function profileToko($name)

    {

        dd($name);

        $products = Product::where('store_name', $name)->get();

    

        return view('etalase.toko', compact('products', 'name'));

    }

     /**
     * Menyediakan data atribut untuk kategori tertentu via API.
     */
    public function getAttributes(Category $category)
    {
        return response()->json($category->attributes);
    }

}

