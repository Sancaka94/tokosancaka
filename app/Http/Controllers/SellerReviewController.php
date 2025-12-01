<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductReview;
use Illuminate\Support\Facades\Auth;

class SellerReviewController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // 1. Cek apakah User punya Toko
        if (!$user->store) {
            return redirect()->back()->with('error', 'Anda belum memiliki toko.');
        }

        $storeId = $user->store->id;

        // 2. Ambil Review yang tertuju pada Produk milik Toko ini
        // Logikanya: Cari Review -> Dimana Produknya -> Punya Store_ID sesuai User Login
        $reviews = ProductReview::whereHas('product', function ($query) use ($storeId) {
            $query->where('store_id', $storeId);
        })
        ->with(['user', 'product']) // Eager Load biar query ringan
        ->latest()
        ->paginate(10);

        // 3. Hitung Statistik Sederhana (Opsional, biar keren)
        $totalReviews = ProductReview::whereHas('product', function ($q) use ($storeId) {
            $q->where('store_id', $storeId);
        })->count();

        $avgRating = ProductReview::whereHas('product', function ($q) use ($storeId) {
            $q->where('store_id', $storeId);
        })->avg('rating');

        return view('seller.reviews.index', compact('reviews', 'totalReviews', 'avgRating'));
    }
}