<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Marketplace; // Menggunakan model Marketplace yang baru
use App\Models\Banner;
use App\Models\Setting;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    /**
     * Menampilkan halaman utama katalog marketplace untuk customer.
     */
    public function index()
    {
        // Mengambil produk untuk Flash Sale dari tabel 'marketplaces'
        $flashSaleProducts = Marketplace::where('is_flash_sale', true)
            ->where('stock', '>', 0) // Hanya tampilkan produk yang masih ada stok
            ->latest()
            ->take(10)
            ->get();

        // Mengambil produk rekomendasi dari tabel 'marketplaces'
        $products = Marketplace::where('is_flash_sale', false)
            ->where('stock', '>', 0) // Hanya tampilkan produk yang masih ada stok
            ->latest()
            ->paginate(15); // Menampilkan 15 produk per halaman
            
        // (Asumsi) Logika untuk banner dan settings tetap sama
        $banners = Banner::where('is_active', true)->orderBy('order', 'asc')->get();
        $settings = Setting::pluck('value', 'key')->all();

        return view('marketplace.katalog', compact('products', 'flashSaleProducts', 'banners', 'settings'));
    }
}

