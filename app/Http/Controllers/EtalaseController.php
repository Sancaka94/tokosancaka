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
     * Menampilkan halaman etalase utama.
     */
    public function index(Request $request)
    {
        $query = Product::with('store.user')->where('status', 'active')->where('stock', '>', 0);

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

        $banners = BannerEtalase::all();
        $settings = Setting::whereIn('key', ['banner_2','banner_3'])
                            ->pluck('value','key');
                            
        $categories = Category::where('type', 'marketplace')->get();
                            
        return view('etalase.index', compact('products', 'flashSaleProducts', 'banners', 'settings', 'categories'));
    }

    /**
     * Menampilkan halaman detail untuk satu produk.
     */
    public function show(Product $product)
    {
        if ($product->status !== 'active') {
            abort(404);
        }
        
        $product->load('store.user');

        $relatedProducts = Product::with('store')->where('category_id', $product->category_id)
                                    ->where('id', '!=', $product->id)
                                    ->where('status', 'active')
                                    ->where('stock', '>', 0)
                                    ->inRandomOrder()
                                    ->limit(4)->get();
                                    
        return view('etalase.show', compact('product', 'relatedProducts'));
    }
    
    public function profileToko($name)
    {
        $products = Product::with('store')->where('store_name', $name)->paginate(12);
    
        return view('etalase.toko', compact('products', 'name'));
    }

    /**
     * Menampilkan produk berdasarkan kategori di etalase publik.
     */
    public function showCategory(Category $category, Request $request)
    {
        if ($category->type !== 'marketplace') {
            abort(404);
        }

        $products = $category->products()
                             ->with('store')
                             ->where('status', 'active')
                             ->where('stock', '>', 0)
                             ->latest()
                             ->paginate(12);
        
        $banners = BannerEtalase::all();
        $settings = Setting::whereIn('key', ['banner_2','banner_3'])->pluck('value','key');
        $categories = Category::where('type', 'marketplace')->get();

        // --- PERBAIKAN DI SINI ---
        // Menambahkan logika untuk mengambil data Flash Sale
        $flashSaleProducts = Product::with('store')->where('status', 'active')
            ->where('stock', '>', 0)
            ->whereNotNull('original_price')
            ->where('price', '<', DB::raw('original_price'))
            ->orderBy('discount_percentage', 'desc')
            ->limit(8)
            ->get();
        // --- AKHIR DARI PERBAIKAN ---

        // Menambahkan 'flashSaleProducts' ke data yang dikirim ke view
        return view('etalase.category-show', compact('category', 'products', 'banners', 'settings', 'categories', 'flashSaleProducts'));
    }
}

