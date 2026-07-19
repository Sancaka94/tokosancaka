<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShortUrl;
use Illuminate\Support\Str;

class ShortUrlController extends Controller
{
    public function index()
    {
        // PERBAIKAN: Dibuat latest() agar data terbaru tampil paling atas
        $shortUrls = ShortUrl::latest()->paginate(10);
        $totalLinks = ShortUrl::count();

        return view('short-urls.index', compact('shortUrls', 'totalLinks'));
    }

    // Tambahkan di dalam ShortUrlController
    public function create()
    {
        return view('short-urls.create'); // Pastikan Anda membuat file view ini nantinya
    }

    public function store(Request $request)
    {
        $request->validate([
            'original_url' => 'required|url'
        ]);

        $shortCode = Str::random(6);

        while (ShortUrl::where('short_code', $shortCode)->exists()) {
            $shortCode = Str::random(6);
        }

        $shortUrl = ShortUrl::create([
            'original_url' => $request->original_url,
            'short_code' => $shortCode
        ]);

        // PERBAIKAN: Redirect kembali ke halaman index daripada menampilkan JSON
        return redirect('/admin/short-urls')->with('success', 'Short URL berhasil dibuat!');
    }

    public function redirect($short_code)
    {
        $shortUrl = ShortUrl::where('short_code', $short_code)->first();

        if (!$shortUrl) {
            abort(404, 'Link tidak ditemukan');
        }

        $shortUrl->increment('clicks');

        return redirect()->away($shortUrl->original_url);
    }

    // PENAMBAHAN: Fungsi untuk menghapus data dari database
    public function destroy($id)
    {
        $shortUrl = ShortUrl::findOrFail($id);
        $shortUrl->delete();

        return redirect()->back()->with('success', 'Short URL berhasil dihapus!');
    }
}
