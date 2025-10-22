<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Models\BannerEtalase;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EtalaseController extends Controller
{
    /**
     * Menampilkan halaman etalase utama (homepage).
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'store'])->where('status', 'active')->where('stock', '>', 0);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->latest()->paginate(10)->withQueryString();

        $flashSaleProducts = Product::with(['category', 'store'])->where('status', 'active')
            ->where('stock', '>', 0)
            ->whereNotNull('original_price')
            ->where('price', '<', DB::raw('original_price'))
            ->orderBy('discount_percentage', 'desc')
            ->limit(8)
            ->get();
            
        $categories = Category::where('type', 'product')->orderBy('name')->get();
        $banners = BannerEtalase::latest()->get(); 
        $settings = Setting::whereIn('key', ['banner_2','banner_3'])->pluck('value','key');
                                
        return view('etalase.index', compact('products', 'flashSaleProducts', 'banners', 'settings', 'categories'));
    }

    /**
     * Menampilkan produk berdasarkan kategori.
     */
    public function showCategory(Category $category)
    {
        if ($category->type !== 'product') {
            abort(404);
        }

        $products = Product::with(['store'])
            ->where('category_id', $category->id)
            ->where('status', 'active')
            ->where('stock', '>', 0)
            ->latest()
            ->paginate(12);
        
        $banners = BannerEtalase::latest()->get();
        $settings = Setting::whereIn('key', ['banner_2','banner_3'])->pluck('value','key');
        $allCategories = Category::where('type', 'product')->orderBy('name')->get();

        return view('etalase.category-show', compact('category', 'products', 'banners', 'settings', 'allCategories'));
    }
    
    /**
     * Menampilkan halaman detail produk.
     */
    public function show(Product $product)
    {
        if ($product->status !== 'active') {
            abort(404);
        }

        /**
         * PERBAIKAN: Memuat relasi 'attributes' dari 'category'.
         * Ini adalah kunci agar data spesifikasi bisa tampil di view.
         */
        $product->load(['category.attributes', 'store.user']);
        
        $relatedProducts = Product::with(['category', 'store'])->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)->where('status', 'active')
            ->where('stock', '>', 0)->inRandomOrder()->limit(5)->get();

        $categories = Category::where('type', 'product')->orderBy('name')->get();
        $banners = BannerEtalase::latest()->get(); 
        $settings = Setting::whereIn('key', ['banner_2','banner_3'])->pluck('value','key');
            
        return view('etalase.show', compact('product', 'relatedProducts', 'categories', 'banners', 'settings'));
    }

    /**
     * Menampilkan halaman profil toko.
     */
    public function profileToko($name)
    {
        $products = Product::where('store_name', 'like', '%' . $name . '%')->where('status', 'active')->paginate(12);
        
        $firstProduct = $products->first();
        $store = $firstProduct ? (object)[
            'name' => $firstProduct->store_name,
            'city' => $firstProduct->seller_city,
            'logo' => $firstProduct->seller_logo,
        ] : null;

        return view('etalase.toko', compact('products', 'name', 'store'));
    }
}

