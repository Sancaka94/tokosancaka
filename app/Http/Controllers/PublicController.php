<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PpobProduct;
use App\Models\BannerEtalase; // Pastikan Model ini di-import

class PublicController extends Controller
{
    public function pricelist()
    {
        // 1. Ambil Banner (Terbaru)
        // Gunakan try-catch agar jika tabel belum ada, tidak error (fallback array kosong)
        try {
            $banners = BannerEtalase::latest()->get();
        } catch (\Exception $e) {
            $banners = collect([]); 
        }

        // 2. Ambil Produk Aktif
        $products = PpobProduct::where('seller_product_status', 1)
            ->orderBy('category', 'asc')
            ->orderBy('brand', 'asc')
            ->orderBy('sell_price', 'asc')
            ->get();

        $categories = $products->pluck('category')->unique()->values();

        // Kirim $banners ke view
        return view('public.pricelist', compact('products', 'categories', 'banners'));
    }
}