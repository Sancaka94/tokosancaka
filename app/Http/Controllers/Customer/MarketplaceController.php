<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Marketplace; // Menggunakan model Marketplace
use App\Models\Banner;
use App\Models\Setting;
use App\Models\Category; // PERBAIKAN: Impor model Category
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    /**
     * Menampilkan halaman utama katalog marketplace untuk customer.
     */
    public function index()
    {
        // 1. Mengambil produk untuk Flash Sale dari tabel 'marketplaces'
        $flashSaleProducts = Marketplace::where('is_flash_sale', true)
            ->where('stock', '>', 0) // Hanya tampilkan produk yang masih ada stok
            ->latest()
            ->take(10)
            ->get();

        // 2. Mengambil produk rekomendasi dari tabel 'marketplaces'
        $products = Marketplace::where('is_flash_sale', false)
            ->where('stock', '>', 0) // Hanya tampilkan produk yang masih ada stok
            ->latest()
            ->paginate(15); // Menampilkan 15 produk per halaman
            
        // 3. (Asumsi) Logika untuk banner dan settings tetap sama
        $banners = Banner::where('is_active', true)->orderBy('order', 'asc')->get();
        $settings = Setting::pluck('value', 'key')->all();

        // 4. PERBAIKAN: Mengambil data Kategori dari database
        $categories = Category::take(10)->get();

        // 5. Mengirim SEMUA data yang dibutuhkan ke view
        return view('marketplace.katalog', compact(
            'products', 
            'flashSaleProducts', 
            'banners', 
            'settings', 
            'categories' // PERBAIKAN: Menambahkan variabel categories
        ));
    }
}
