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

      /**
     * Menampilkan Halaman Kategori Spesifik
     * URL: /layanan/pln-pascabayar, /layanan/pulsa, dll
     */
    public function showCategory($slug)
    {
        // 1. Ambil Data Umum (Banner & Setting)
        try {
            $banners = BannerEtalase::latest()->get();
            $settings = Setting::whereIn('key', ['banner_2', 'banner_3'])->pluck('value', 'key')->toArray();
        } catch (\Exception $e) {
            $banners = collect([]);
            $settings = [];
        }

        // 2. Konfigurasi Halaman & Mode
        $pageInfo = [
            'title'       => ucfirst(str_replace('-', ' ', $slug)),
            'slug'        => $slug,
            'input_label' => 'Nomor Handphone',
            'input_place' => 'Contoh: 0812xxxx',
            'icon'        => 'fa-mobile-alt',
            'is_postpaid' => false, // Default Prabayar
        ];

        // 3. Deteksi Mode Pascabayar (Cek Tagihan)
        if ($slug == 'pln-pascabayar') {
            $pageInfo['title']       = 'Cek Tagihan PLN';
            $pageInfo['input_label'] = 'ID Pelanggan PLN';
            $pageInfo['input_place'] = 'Contoh: 53xxxx';
            $pageInfo['icon']        = 'fa-file-invoice-dollar';
            $pageInfo['is_postpaid'] = true; 

        } elseif ($slug == 'pdam') {
            $pageInfo['title']       = 'Cek Tagihan PDAM';
            $pageInfo['input_label'] = 'ID Pelanggan PDAM';
            $pageInfo['input_place'] = 'Nomor Pelanggan';
            $pageInfo['icon']        = 'fa-faucet';
            $pageInfo['is_postpaid'] = true; 
        
        } elseif ($slug == 'bpjs') {
            $pageInfo['title']       = 'Cek Tagihan BPJS';
            $pageInfo['input_label'] = 'Nomor VA Keluarga';
            $pageInfo['input_place'] = '88888xxxx';
            $pageInfo['icon']        = 'fa-heart-pulse';
            $pageInfo['is_postpaid'] = true; 
        }

        // 4. Ambil Produk (Jika Mode Prabayar)
        $products = collect([]);
        $categories = collect([]);
        
        if (!$pageInfo['is_postpaid']) {
            $products = PpobProduct::where('seller_product_status', 1)
                ->orderBy('category', 'asc')
                ->orderBy('brand', 'asc')
                ->orderBy('sell_price', 'asc')
                ->get();
            $categories = $products->pluck('category')->unique()->values();
        }

        // 5. Return View yang sama dengan Pricelist
        return view('public.pricelist', compact('products', 'categories', 'banners', 'settings', 'pageInfo'));
    }
}