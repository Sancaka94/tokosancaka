<?php

namespace App\Http\Controllers\Customer; // <-- NAMESPACE DIPERBARUI

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Banner;
use App\Models\Setting;

class MarketplaceController extends Controller
{
    /**
     * Menampilkan halaman utama marketplace dengan data dinamis.
     */
    public function index()
    {
        // 1. Mengambil banner utama yang aktif
        $banners = Banner::where('is_active', true)->orderBy('order', 'asc')->get();

        // 2. Mengambil pengaturan untuk banner samping
        // Diasumsikan Anda memiliki tabel settings dengan format key-value
        $settings = Setting::whereIn('key', ['banner_2', 'banner_3'])->pluck('value', 'key');

        // 3. Mengambil produk flash sale
        $flashSaleProducts = Product::where('is_flash_sale', true)
                                    ->where('stock', '>', 0)
                                    ->latest()
                                    ->take(10)
                                    ->get();

        // 4. Mengambil produk rekomendasi dengan paginasi
        $products = Product::where('is_flash_sale', false)
                           ->where('stock', '>', 0)
                           ->latest()
                           ->paginate(15);

        // 5. Mengirim semua data ke view
        return view('marketplace.katalog', compact('banners', 'settings', 'flashSaleProducts', 'products'));
    }
}
