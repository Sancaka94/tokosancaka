<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShortUrl;
use Illuminate\Support\Str;

class ShortUrlController extends Controller
{
    public function index()
{
    // Mengambil data untuk tabel
    $shortUrls = \App\Models\ShortUrl::paginate(10);
    // Menghitung total data
    $totalLinks = \App\Models\ShortUrl::count();

    // Pastikan path view sesuai dengan lokasi file Anda (resources/views/short-urls/index.blade.php)
    return view('short-urls.index', compact('shortUrls', 'totalLinks'));
}

    // Fungsi untuk menggenerate URL Pendek
    public function store(Request $request)
    {
        $request->validate([
            'original_url' => 'required|url'
        ]);

        // Generate kode acak 6 karakter
        $shortCode = Str::random(6);

        // Pastikan kode acak belum pernah ada di database
        while (ShortUrl::where('short_code', $shortCode)->exists()) {
            $shortCode = Str::random(6);
        }

        // Simpan ke database
        $shortUrl = ShortUrl::create([
            'original_url' => $request->original_url,
            'short_code' => $shortCode
        ]);

        // Kembalikan response (Bisa diubah ke view atau JSON API)
        return response()->json([
            'success' => true,
            'short_link' => url('/' . $shortCode),
            'original_url' => $shortUrl->original_url
        ]);
    }

    // Fungsi untuk melakukan Redirect saat link pendek diakses
    public function redirect($short_code)
    {
        // Cari data berdasarkan kode pendek
        $shortUrl = ShortUrl::where('short_code', $short_code)->first();

        // Jika tidak ditemukan, tampilkan error 404
        if (!$shortUrl) {
            abort(404, 'Link tidak ditemukan');
        }

        // (Opsional) Tambah counter klik
        $shortUrl->increment('clicks');

        // Redirect ke link panjang yang asli
        return redirect()->away($shortUrl->original_url);
    }
}
