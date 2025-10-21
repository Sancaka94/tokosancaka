<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Banner; // 1. Impor model Banner
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Menampilkan produk berdasarkan kategori yang dipilih.
     */
    public function show(Category $category)
    {
        // Memuat produk yang berelasi dengan kategori ini
        $products = $category->products()->paginate(12);
        
        // 2. Mengambil data banner yang aktif
        $banners = Banner::where('is_active', true)->orderBy('order', 'asc')->get();

        // 3. Mengirim semua data (kategori, produk, dan banner) ke view
        return view('customer.categories.show', [
            'category' => $category,
            'products' => $products,
            'banners' => $banners, // Variabel banner sekarang tersedia di view
        ]);
    }
}
