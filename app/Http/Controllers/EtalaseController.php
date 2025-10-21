<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Models\BannerEtalase;
use App\Models\Category;
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
            
        $categoryNames = Product::whereNotNull('category')
                                ->where('category', '!=', '')
                                ->distinct()
                                ->pluck('category');

        $categories = $categoryNames->map(function ($name) {
            $iconMap = [
                'PERIZINAN' => 'fa-file-contract', 'KONSULTASI' => 'fa-comments-dollar', 'KEUANGAN' => 'fa-coins',
                'KONSTRUKSI' => 'fa-building', 'TEKNOLOGI' => 'fa-microchip', 'DESAIN' => 'fa-pencil-ruler',
                'PELATIHAN' => 'fa-chalkboard-teacher', 'DOKUMEN' => 'fa-folder-open', 'LEGALITAS' => 'fa-scale-balanced',
                'MARKETING' => 'fa-bullhorn', 'BAHAN BANGUNAN' => 'fa-hammer', 'PERALATAN KANTOR' => 'fa-briefcase',
                'ELEKTRONIK' => 'fa-tv', 'KENDARAAN' => 'fa-truck', 'PROPERTI' => 'fa-house', 'FASHION' => 'fa-shirt',
                'MAKANAN & MINUMAN' => 'fa-utensils', 'PERALATAN RUMAH TANGGA' => 'fa-blender', 'PERTANIAN' => 'fa-leaf',
                'PERIKANAN' => 'fa-fish', 'PETERNAKAN' => 'fa-cow', 'KERAJINAN' => 'fa-hand-sparkles',
                'OBAT & KESEHATAN' => 'fa-briefcase-medical', 'ALAT TULIS' => 'fa-pen-nib', 'LAINNYA' => 'fa-ellipsis-h',
            ];

            return (object)[
                'name' => ucfirst(strtolower($name)),
                'slug' => Str::slug($name),
                'icon' => $iconMap[strtoupper($name)] ?? 'fa-tag',
            ];
        });

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
        $product->load('store.user');
        $relatedProducts = Product::with('store')->where('category', $product->category)
            ->where('id', '!=', $product->id)->where('status', 'active')
            ->where('stock', '>', 0)->inRandomOrder()->limit(4)->get();
        return view('etalase.show', compact('product', 'relatedProducts'));
    }
    
    /**
     * Menampilkan halaman profil toko.
     */
    public function profileToko($name)
    {
        $products = Product::with('store')->where('store_name', $name)->where('status', 'active')->paginate(12);
        $store = $products->first()->store ?? null;
        return view('etalase.toko', compact('products', 'name', 'store'));
    }

    /**
     * Menampilkan produk berdasarkan kategori di etalase publik.
     */
    public function showCategory($categorySlug)
    {
        $categoryName = ucwords(str_replace('-', ' ', $categorySlug));

        $products = Product::with('store')->where('category', $categoryName)
            ->where('status', 'active')->where('stock', '>', 0)
            ->latest()->paginate(12);
        
        $banners = BannerEtalase::all();
        $settings = Setting::whereIn('key', ['banner_2','banner_3'])->pluck('value','key');
        
        $flashSaleProducts = Product::with('store')->where('status', 'active')
            ->where('stock', '>', 0)->whereNotNull('original_price')
            ->where('price', '<', DB::raw('original_price'))
            ->orderBy('discount_percentage', 'desc')->limit(8)->get();
            
        // PERBAIKAN: Mengganti nama variabel menjadi $categories
        $categories = Product::whereNotNull('category')->where('category', '!=', '')
            ->distinct()->pluck('category')->map(function ($name) {
                // Anda bisa menambahkan iconMap di sini jika diperlukan
                return (object)['name' => ucfirst(strtolower($name)), 'slug' => Str::slug($name), 'icon' => 'fa-tag'];
            });

        $category = (object)['name' => $categoryName, 'slug' => $categorySlug, 'icon' => 'fa-tag'];

        // PERBAIKAN: Memanggil variabel 'categories' dengan benar di dalam compact()
        return view('etalase.category-show', compact(
            'category', 
            'products', 
            'banners', 
            'settings', 
            'flashSaleProducts',
            'categories'
        ));
    }
}

