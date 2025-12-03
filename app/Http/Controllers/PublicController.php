<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PpobProduct;
use App\Models\BannerEtalase;
use App\Models\Setting; // Pastikan Model Setting di-import

class PublicController extends Controller
{
    public function pricelist()
    {
        // 1. Ambil Banner Utama
        try {
            $banners = BannerEtalase::latest()->get();
        } catch (\Exception $e) {
            $banners = collect([]); 
        }

        // 2. [BARU] Ambil Setting untuk Banner Samping (Banner 2 & 3)
        // Pastikan Anda punya table settings dengan key 'banner_2' dan 'banner_3'
        try {
            $settings = Setting::whereIn('key', ['banner_2', 'banner_3'])
                        ->pluck('value', 'key')
                        ->toArray();
        } catch (\Exception $e) {
            $settings = [];
        }

        // 3. Ambil Produk Aktif
        $products = PpobProduct::where('seller_product_status', 1)
            ->orderBy('category', 'asc')
            ->orderBy('brand', 'asc')
            ->orderBy('sell_price', 'asc')
            ->get();

        $categories = $products->pluck('category')->unique()->values();

        // Kirim $settings ke view juga
        return view('public.pricelist', compact('products', 'categories', 'banners', 'settings'));
    }
}