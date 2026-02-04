<?php

namespace App\Http\Controllers\Toko; 

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception; // Pastikan ini ada

class CategoryController extends Controller
{
    /**
     * Mengambil atribut yang terkait dengan kategori tertentu.
     *
     * @param Category $category
     * @return JsonResponse
     */
    public function getAttributes(Category $category): JsonResponse
    {
        try {
            // Mengambil semua atribut terkait
            // Pastikan relasi 'attributes()' ada di model Category Anda
            $attributes = $category->attributes()->get(); 

            return response()->json($attributes);

        } catch (Exception $e) {
            // Catat error jika gagal
            Log::error("Failed to fetch attributes for category {$category->id}: " . $e->getMessage());
            
            // Kirim respons error (INI YANG DIPERBAIKI)
            return response()->json(['error' => 'Gagal memuat atribut.'], 500);
        }
    }
}