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
            
        // PERBAIKAN: Menggunakan daftar kategori lengkap yang baru
        $iconMap = [
            // --- JASA & PROFESIONAL ---
            'PERIZINAN' => 'fa-file-contract',
            'KONSULTASI' => 'fa-comments-dollar',
            'KEUANGAN' => 'fa-coins',
            'LEGALITAS' => 'fa-scale-balanced',
            'KONSTRUKSI' => 'fa-building',
            'TEKNOLOGI' => 'fa-microchip',
            'DESAIN' => 'fa-pencil-ruler',
            'PELATIHAN' => 'fa-chalkboard-teacher',
            'DOKUMEN' => 'fa-folder-open',
            'MARKETING' => 'fa-bullhorn',
            'JASA PENGIRIMAN' => 'fa-truck-fast',
            'JASA FOTOGRAFI' => 'fa-camera-retro',
            'JASA PERCETAKAN' => 'fa-print',
            'FREELANCER DIGITAL' => 'fa-laptop-code',

            // --- PRODUK FISIK (LAYAK TOKOPEDIA/SHOPEE) ---
            'FASHION PRIA' => 'fa-user-tie',
            'FASHION WANITA' => 'fa-person-dress',
            'FASHION MUSLIM' => 'fa-mosque',
            'ANAK & BAYI' => 'fa-baby',
            'KECANTIKAN' => 'fa-heart',
            'OBAT & KESEHATAN' => 'fa-briefcase-medical',
            'ELEKTRONIK' => 'fa-tv',
            'HANDPHONE & AKSESORIS' => 'fa-mobile-screen',
            'KOMPUTER & AKSESORIS' => 'fa-desktop',
            'KAMERA' => 'fa-camera',
            'GAMING' => 'fa-gamepad',

            // --- RUMAH & GAYA HIDUP ---
            'PROPERTI' => 'fa-house',
            'PERALATAN RUMAH TANGGA' => 'fa-blender',
            'BAHAN BANGUNAN' => 'fa-hammer',
            'PERALATAN KANTOR' => 'fa-briefcase',
            'DEKORASI RUMAH' => 'fa-couch',
            'DAPUR & MASAK' => 'fa-utensils',
            'MAKANAN & MINUMAN' => 'fa-bowl-food',
            'ALAT TULIS' => 'fa-pen-nib',
            'HOBI & KOLEKSI' => 'fa-guitar',
            'BUKU & ALAT BELAJAR' => 'fa-book-open',

            // --- OTOMOTIF ---
            'KENDARAAN' => 'fa-truck',
            'AKSESORIS MOTOR' => 'fa-motorcycle',
            'AKSESORIS MOBIL' => 'fa-car',

            // --- PERTANIAN & PERIKANAN ---
            'PERTANIAN' => 'fa-leaf',
            'PERIKANAN' => 'fa-fish',
            'PETERNAKAN' => 'fa-cow',
            'PERKEBUNAN' => 'fa-seedling',

            // --- PRODUK LOKAL & UMKM ---
            'KERAJINAN' => 'fa-hand-sparkles',
            'PRODUK UMKM' => 'fa-store',
            'SOUVENIR' => 'fa-gift',
            'FASHION ETNIK' => 'fa-feather-pointed',
            'BATIK' => 'fa-shirt',

            // --- HIBURAN & DIGITAL ---
            'TIKET & EVENT' => 'fa-ticket',
            'MUSIK & FILM' => 'fa-music',
            'VOUCHER & GAME' => 'fa-ticket-simple',
            'E-WALLET & PULSA' => 'fa-wallet',

            // --- LAINNYA ---
            'HEWAN PELIHARAAN' => 'fa-paw',
            'TANAMAN HIAS' => 'fa-seedling',
            'SPAREPART' => 'fa-gears',
            'LAINNYA' => 'fa-ellipsis-h',
        ];

        $categories = collect($iconMap)->map(function ($icon, $name) {
            return (object)[
                'name' => ucfirst(strtolower($name)),
                'slug' => Str::slug($name),
                'icon' => $icon,
            ];
        })->values();

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
        $products = Product::with('store')->where('store_name', 'like', $name)->where('status', 'active')->paginate(12);
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
            
        // PERBAIKAN: Menggunakan logika dan daftar ikon yang sama seperti di method index
        $iconMap = [
            'PERIZINAN' => 'fa-file-contract', 'KONSULTASI' => 'fa-comments-dollar', 'KEUANGAN' => 'fa-coins', 'LEGALITAS' => 'fa-scale-balanced', 'KONSTRUKSI' => 'fa-building', 'TEKNOLOGI' => 'fa-microchip', 'DESAIN' => 'fa-pencil-ruler', 'PELATIHAN' => 'fa-chalkboard-teacher', 'DOKUMEN' => 'fa-folder-open', 'MARKETING' => 'fa-bullhorn', 'JASA PENGIRIMAN' => 'fa-truck-fast', 'JASA FOTOGRAFI' => 'fa-camera-retro', 'JASA PERCETAKAN' => 'fa-print', 'FREELANCER DIGITAL' => 'fa-laptop-code',
            'FASHION PRIA' => 'fa-user-tie', 'FASHION WANITA' => 'fa-person-dress', 'FASHION MUSLIM' => 'fa-mosque', 'ANAK & BAYI' => 'fa-baby', 'KECANTIKAN' => 'fa-heart', 'OBAT & KESEHATAN' => 'fa-briefcase-medical', 'ELEKTRONIK' => 'fa-tv', 'HANDPHONE & AKSESORIS' => 'fa-mobile-screen', 'KOMPUTER & AKSESORIS' => 'fa-desktop', 'KAMERA' => 'fa-camera', 'GAMING' => 'fa-gamepad',
            'PROPERTI' => 'fa-house', 'PERALATAN RUMAH TANGGA' => 'fa-blender', 'BAHAN BANGUNAN' => 'fa-hammer', 'PERALATAN KANTOR' => 'fa-briefcase', 'DEKORASI RUMAH' => 'fa-couch', 'DAPUR & MASAK' => 'fa-utensils', 'MAKANAN & MINUMAN' => 'fa-bowl-food', 'ALAT TULIS' => 'fa-pen-nib', 'HOBI & KOLEKSI' => 'fa-guitar', 'BUKU & ALAT BELAJAR' => 'fa-book-open',
            'KENDARAAN' => 'fa-truck', 'AKSESORIS MOTOR' => 'fa-motorcycle', 'AKSESORIS MOBIL' => 'fa-car',
            'PERTANIAN' => 'fa-leaf', 'PERIKANAN' => 'fa-fish', 'PETERNAKAN' => 'fa-cow', 'PERKEBUNAN' => 'fa-seedling',
            'KERAJINAN' => 'fa-hand-sparkles', 'PRODUK UMKM' => 'fa-store', 'SOUVENIR' => 'fa-gift', 'FASHION ETNIK' => 'fa-feather-pointed', 'BATIK' => 'fa-shirt',
            'TIKET & EVENT' => 'fa-ticket', 'MUSIK & FILM' => 'fa-music', 'VOUCHER & GAME' => 'fa-ticket-simple', 'E-WALLET & PULSA' => 'fa-wallet',
            'HEWAN PELIHARAAN' => 'fa-paw', 'TANAMAN HIAS' => 'fa-seedling', 'SPAREPART' => 'fa-gears', 'LAINNYA' => 'fa-ellipsis-h',
        ];

        $categories = collect($iconMap)->map(function ($icon, $name) {
            return (object)['name' => ucfirst(strtolower($name)), 'slug' => Str::slug($name), 'icon' => $icon];
        })->values();

        $category = (object)['name' => $categoryName, 'slug' => $categorySlug, 'icon' => $iconMap[strtoupper($categoryName)] ?? 'fa-tag'];

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

