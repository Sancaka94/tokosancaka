<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductReview;
use Illuminate\Support\Facades\Auth;

class ProductReviewController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating'     => 'required|integer|min:1|max:5',
            'comment'    => 'required|string|max:1000',
        ]);

        // Cek apakah user sudah login (redundant jika sudah pakai middleware auth, tapi aman)
        if (!Auth::check()) {
            return back()->with('error', 'Silakan login untuk memberikan ulasan.');
        }

        // Simpan Ulasan
        ProductReview::create([
            'product_id' => $request->product_id,
            'user_id'    => Auth::id(),
            'rating'     => $request->rating,
            'comment'    => $request->comment,
        ]);

        return back()->with('success', 'Terima kasih! Ulasan Anda berhasil dikirim.');
    }
}