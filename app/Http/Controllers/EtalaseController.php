<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Models\BannerEtalase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EtalaseController extends Controller
{
    /**
     * Menampilkan halaman etalase utama.
     */
    public function index(Request $request)
    {
        // --- Logika untuk Produk Utama (dengan filter & paginasi) ---
        // ✅ DIPERBAIKI: Menambahkan with('store') untuk mengambil data toko
        $query = Product::with('store.user')->where('status', 'active')->where('stock', '>', 0);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->latest()->paginate(10)->withQueryString();

        // --- Logika untuk Produk Flash Sale ---
        // ✅ DIPERBAIKI: Menambahkan with('store') untuk mengambil data toko
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
                            
        return view('etalase.index', compact('products', 'flashSaleProducts', 'banners', 'settings'));
    }

    /**
     * Menampilkan halaman detail untuk satu produk.
     */
    public function show(Product $product)
    {
        if ($product->status !== 'active') {
            abort(404);
        }
        
         // ✅ DIPERBAIKI: Memuat relasi produk -> toko -> pengguna
        $product->load('store.user');

        $relatedProducts = Product::with('store')->where('category', $product->category)
                                        ->where('id', '!=', $product->id)
                                        ->where('status', 'active')
                                        ->where('stock', '>', 0)
                                        ->inRandomOrder()
                                        ->limit(4)->get();
                                        
        return view('etalase.show', compact('product', 'relatedProducts'));
    }
    
    public function profileToko($name)
    {
        // ✅ DIPERBAIKI: Menambahkan with('store') untuk mengambil data toko
        $products = Product::with('store')->where('store_name', $name)->paginate(12);
    
        return view('etalase.toko', compact('products', 'name'));
    }
}