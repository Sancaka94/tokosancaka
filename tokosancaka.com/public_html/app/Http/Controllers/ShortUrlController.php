<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShortUrl;
use Illuminate\Support\Str;

class ShortUrlController extends Controller
{
    public function index(Request $request)
    {
        // Fitur Pencarian (Search)
        $query = ShortUrl::query();

        if ($request->has('search') && $request->search != '') {
            $searchTerm = $request->search;
            $query->where('original_url', 'like', "%{$searchTerm}%")
                  ->orWhere('short_code', 'like', "%{$searchTerm}%");
        }

        // Data untuk tabel dan Card
        $shortUrls = $query->latest()->paginate(10)->withQueryString();
        $totalLinks = ShortUrl::count();
        $totalClicks = ShortUrl::sum('clicks'); // Menghitung total seluruh klik

        return view('short-urls.index', compact('shortUrls', 'totalLinks', 'totalClicks'));
    }

    public function create()
    {
        return view('short-urls.create');
    }

    public function store(Request $request)
    {
        // Validasi, pastikan custom_code unik dan tidak menabrak rute sistem
        $request->validate([
            'original_url' => 'required|url',
            'custom_code' => 'nullable|string|alpha_dash|unique:short_urls,short_code|not_in:admin,login,register,shorten,api'
        ], [
            'custom_code.unique' => 'Nama URL custom ini sudah digunakan, silakan pilih yang lain.',
            'custom_code.not_in' => 'Nama URL ini dilarang karena digunakan oleh sistem.'
        ]);

        // Cek apakah user input custom code, jika tidak buat random
        if ($request->filled('custom_code')) {
            $shortCode = $request->custom_code;
        } else {
            $shortCode = Str::random(6);
            while (ShortUrl::where('short_code', $shortCode)->exists()) {
                $shortCode = Str::random(6);
            }
        }

        ShortUrl::create([
            'original_url' => $request->original_url,
            'short_code' => $shortCode
        ]);

        return redirect('/admin/short-urls')->with('success', 'Short URL berhasil dibuat!');
    }

    public function edit($id)
    {
        $shortUrl = ShortUrl::findOrFail($id);
        return view('short-urls.edit', compact('shortUrl'));
    }

    public function update(Request $request, $id)
    {
        $shortUrl = ShortUrl::findOrFail($id);

        $request->validate([
            'original_url' => 'required|url',
            // Validasi custom_code unik kecuali untuk ID yang sedang diedit
            'custom_code' => 'required|string|alpha_dash|not_in:admin,login,register,shorten,api|unique:short_urls,short_code,' . $id
        ]);

        $shortUrl->update([
            'original_url' => $request->original_url,
            'short_code' => $request->custom_code
        ]);

        return redirect('/admin/short-urls')->with('success', 'Short URL berhasil diupdate!');
    }

    public function destroy($id)
    {
        $shortUrl = ShortUrl::findOrFail($id);
        $shortUrl->delete();

        return redirect()->back()->with('success', 'Short URL berhasil dihapus!');
    }

    // Fungsi untuk Bulk Delete
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:short_urls,id'
        ]);

        ShortUrl::whereIn('id', $request->ids)->delete();

        return redirect()->back()->with('success', count($request->ids) . ' data berhasil dihapus!');
    }

    public function redirect($short_code)
    {
        $shortUrl = ShortUrl::where('short_code', $short_code)->first();

        if (!$shortUrl) {
            abort(404, 'Link tidak ditemukan');
        }

        // Hitung 1 klik setiap kali diakses
        $shortUrl->increment('clicks');

        return redirect()->away($shortUrl->original_url);
    }
}
