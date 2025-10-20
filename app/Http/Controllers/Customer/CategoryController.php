<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Menampilkan produk berdasarkan kategori yang dipilih.
     */
    public function show(Category $category)
    {
        // Memuat produk yang berelasi dengan kategori ini
        // 'load' lebih efisien daripada memanggil $category->products secara langsung
        $category->load('products');

        // Mengirim data kategori dan produknya ke view
        return view('customer.categories.show', [
            'category' => $category,
            'products' => $category->products()->paginate(12) // Mengambil produk dengan paginasi
        ]);
    }
}