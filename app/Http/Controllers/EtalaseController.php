<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Models\BannerEtalase;
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
        // --- Logika untuk Produk Utama (dengan filter & paginasi) ---
        $query = Product::with('store')->where('status', 'active')->where('stock', '>', 0);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->latest()->paginate(10)->withQueryString();

        // --- Logika untuk Produk Flash Sale ---
        $flashSaleProducts = Product::with('store')->where('status', 'active')
            ->where('stock', '>', 0)
            ->whereNotNull('original_price')
            ->where('price', '<', DB::raw('original_price'))
            ->orderBy('discount_percentage', 'desc')
            ->limit(8)
            ->get();
            
        // --- Mengambil kategori unik dari tabel 'products' ---
        $categoryNames = Product::whereNotNull('category')
                                ->where('category', '!=', '')
                                ->distinct()
                                ->pluck('category');

        // Membuat koleksi objek kategori dari nama yang didapat
        $categories = $categoryNames->map(function ($name) {
            $iconMap = [
                // Jasa
                'PERIZINAN' => 'fa-file-contract',
                'KONSULTASI' => 'fa-comments-dollar',
                'KEUANGAN' => 'fa-coins',
                'KONSTRUKSI' => 'fa-building',
                'TEKNOLOGI' => 'fa-microchip',
                'DESAIN' => 'fa-pencil-ruler',
                'PELATIHAN' => 'fa-chalkboard-teacher',
                'DOKUMEN' => 'fa-folder-open',
                'LEGALITAS' => 'fa-scale-balanced',
                'MARKETING' => 'fa-bullhorn',

                // Produk Non-Jasa
                'BAHAN BANGUNAN' => 'fa-hammer',
                'PERALATAN KANTOR' => 'fa-briefcase',
                'ELEKTRONIK' => 'fa-tv',
                'KENDARAAN' => 'fa-truck',
                'PROPERTI' => 'fa-house',
                'FASHION' => 'fa-shirt',
                'MAKANAN & MINUMAN' => 'fa-utensils',
                'PERALATAN RUMAH TANGGA' => 'fa-blender',
                'PERTANIAN' => 'fa-leaf',
                'PERIKANAN' => 'fa-fish',
                'PETERNAKAN' => 'fa-cow',
                'KERAJINAN' => 'fa-hand-sparkles',
                'OBAT & KESEHATAN' => 'fa-briefcase-medical',
                'ALAT TULIS' => 'fa-pen-nib',
                'LAINNYA' => 'fa-ellipsis-h',
            ];

            return (object)[
                'name' => ucfirst(strtolower($name)),
                'slug' => Str::slug($name),
                'icon' => $iconMap[strtoupper($name)] ?? 'fa-tag',
            ];
        });

        $banners = BannerEtalase::all();
        $settings = Setting::whereIn('key', ['banner_2','banner_3'])
                            ->pluck('value','key');
                            
        return view('marketplace.index', compact('products', 'flashSaleProducts', 'banners', 'settings', 'categories'));
    }

    /**
     * PERBAIKAN: Method untuk menampilkan halaman detail produk.
     */
    public function show(Product $product)
    {
        // Memastikan produk yang diakses aktif
        if ($product->status !== 'active') {
            abort(404);
        }
        
        // Memuat relasi ke toko dan pengguna toko
        $product->load('store.user');

        // Mengambil produk terkait berdasarkan kolom 'category' (string)
        $relatedProducts = Product::with('store')
            ->where('category', $product->category)
            ->where('id', '!=', $product->id) // Jangan tampilkan produk yang sedang dilihat
            ->where('status', 'active')
            ->where('stock', '>', 0)
            ->inRandomOrder()
            ->limit(4)
            ->get();
                                    
        return view('etalase.show', compact('product', 'relatedProducts'));
    }
    
    /**
     * PERBAIKAN: Method untuk menampilkan halaman profil toko.
     */
    public function profileToko($name)
    {
        // Mengambil produk berdasarkan nama toko
        $products = Product::with('store')
            ->where('store_name', $name)
            ->where('status', 'active')
            ->paginate(12);

        // Mengambil data toko dari produk pertama (jika ada)
        $store = $products->first()->store ?? null;
    
        return view('etalase.toko', compact('products', 'name', 'store'));
    }

    /**
     * PERBAIKAN: Method untuk menampilkan produk berdasarkan kategori.
     */
    public function showCategory($categorySlug)
    {
        // Mencari nama kategori asli dari slug
        $categoryName = ucwords(str_replace('-', ' ', $categorySlug));

        // Mengambil produk yang cocok dengan nama kategori
        $products = Product::with('store')
            ->where('category', $categoryName)
            ->where('status', 'active')
            ->where('stock', '>', 0)
            ->latest()
            ->paginate(12);
        
        // Mengambil data lain yang dibutuhkan oleh layout (banners, settings, dll.)
        $banners = BannerEtalase::all();
        $settings = Setting::whereIn('key', ['banner_2','banner_3'])->pluck('value','key');
        
        // Membuat objek kategori tunggal untuk judul halaman
        $category = (object)['name' => $categoryName, 'slug' => $categorySlug, 'icon' => 'fa-tag'];

        return view('etalase.category-show', compact('category', 'products', 'banners', 'settings'));
    }
}

