<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Models\BannerEtalase;
use App\Models\Category; // <-- 1. Import model Category
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EtalaseController extends Controller
{
    /**
     * Menampilkan halaman etalase utama.
     */
    public function index(Request $request)
    {
        $query = Product::with('store')->where('status', 'active')->where('stock', '>', 0);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->latest()->paginate(10)->withQueryString();

        $flashSaleProducts = Product::with('store')->where('status', 'active')
            ->where('stock', '>', 0)
            ->whereNotNull('original_price')
            ->where('price', '<', DB::raw('original_price'))
            ->orderBy('discount_percentage', 'desc')
            ->limit(8)
            ->get();
            
        // Mengambil kategori dari database, bukan hardcode
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        $banners = BannerEtalase::all();
        $settings = Setting::whereIn('key', ['banner_2','banner_3'])->pluck('value','key');
                                
        return view('etalase.index', compact('products', 'flashSaleProducts', 'banners', 'settings', 'categories'));
    }

    /**
     * Menampilkan halaman detail produk.
     */
    public function show(Product $product)
    {
        if ($product->status !== 'active') {
            abort(404);
        }
        $product->load('store.user', 'category'); // Load relasi kategori
        $relatedProducts = Product::with('store')->where('category_id', $product->category_id) // Cari berdasarkan category_id
            ->where('id', '!=', $product->id)->where('status', 'active')
            ->where('stock', '>', 0)->inRandomOrder()->limit(4)->get();
        return view('etalase.show', compact('product', 'relatedProducts'));
    }
    
    /**
     * Menampilkan halaman profil toko.
     */
    public function profileToko($name)
    {
        $products = Product::with('store')->where('store_name', 'like', $name)->where('status', 'active')->paginate(12);
        $store = $products->first()->store ?? null;
        return view('etalase.toko', compact('products', 'name', 'store'));
    }

    /**
     * Menampilkan produk berdasarkan kategori di etalase publik.
     */
    public function showCategory($categorySlug)
    {
        // --- PERBAIKAN LOGIKA INTI ---
        // 2. Cari kategori di database berdasarkan slug-nya.
        //    'firstOrFail()' akan otomatis menampilkan halaman 404 jika kategori tidak ditemukan.
        $category = Category::where('slug', $categorySlug)->firstOrFail();

        // 3. Gunakan ID dari kategori yang ditemukan untuk mencari produk.
        $products = Product::with('store')
            ->where('category_id', $category->id) // <-- Ini adalah perbaikan utama
            ->where('status', 'active')
            ->where('stock', '>', 0)
            ->latest()
            ->paginate(12);
        
        // Logika lain tetap sama
        $banners = BannerEtalase::all();
        $settings = Setting::whereIn('key', ['banner_2','banner_3'])->pluck('value','key');
        
        $flashSaleProducts = Product::with('store')->where('status', 'active')
            ->where('stock', '>', 0)->whereNotNull('original_price')
            ->where('price', '<', DB::raw('original_price'))
            ->orderBy('discount_percentage', 'desc')->limit(8)->get();
            
        // Mengambil semua kategori untuk ditampilkan di sidebar atau menu
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        // Mengirim data ke view
        return view('marketplace.category', compact( // Pastikan nama view sudah benar
            'category', 
            'products', 
            'banners', 
            'settings', 
            'flashSaleProducts',
            'categories'
        ));
    }
}

