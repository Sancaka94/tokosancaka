<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use App\Models\Banner;
use App\Models\Setting;
use App\Models\Category;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    public function index()
    {
        $flashSaleProducts = Marketplace::where('is_flash_sale', true)
            ->where('stock', '>', 0)
            ->latest()
            ->take(10)
            ->get();

        $products = Marketplace::where('is_flash_sale', false)
            ->where('stock', '>', 0)
            ->latest()
            ->paginate(15);
            
        $banners = Banner::where('is_active', true)->orderBy('order', 'asc')->get();
        $settings = Setting::pluck('value', 'key')->all();

        // --- PERBAIKAN DI SINI ---
        // Menghapus ->take(10) agar semua kategori marketplace diambil dari database.
        $categories = Category::where('type', 'marketplace')->get();

        return view('marketplace.katalog', compact(
            'products', 
            'flashSaleProducts', 
            'banners', 
            'settings', 
            'categories'
        ));
    }
}

