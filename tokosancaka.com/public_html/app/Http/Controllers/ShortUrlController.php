<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShortUrl;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route; // TAMBAHKAN INI UNTUK MEMBACA RUTE APLIKASI

class ShortUrlController extends Controller
{
    /**
     * ========================================================
     * FUNGSI BANTUAN: Mengecek apakah kode bentrok dengan Route
     * ========================================================
     */
    private function isCodeRestricted($code)
    {
        $code = strtolower(trim($code));
        $reservedPaths = [];

        // Ambil semua rute yang terdaftar di aplikasi (web.php, api.php, dll)
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            // Ambil segmen pertama dari URL (misal: /etalase/kategori -> 'etalase')
            $segments = explode('/', $uri);
            $firstSegment = $segments[0] ?? '';

            // Abaikan parameter dinamis dari rute (seperti {resi} atau {short_code})
            if (!empty($firstSegment) && !str_starts_with($firstSegment, '{')) {
                $reservedPaths[] = strtolower($firstSegment);
            }
        }

        // Tambahan manual folder bawaan framework yang tidak masuk di web.php
        $manualRestricted = ['storage', 'build', 'vendor', 'shorten'];

        // Gabungkan semua rute dan hapus duplikat
        $allRestricted = array_unique(array_merge($reservedPaths, $manualRestricted));

        // Kembalikan true jika kode yang diketik user ada di dalam daftar rute sistem
        return in_array($code, $allRestricted);
    }

    /**
     * ========================================================
     * FUNGSI AJAX: Merespons ketikan user secara Real-Time
     * ========================================================
     */
    public function checkCode(Request $request)
    {
        $code = strtolower(trim($request->query('code')));
        $currentId = $request->query('current_id');

        if (!$code) {
            return response()->json(['status' => 'empty']);
        }

        // 1. Cek apakah bertabrakan dengan Rute Sistem Toko Sancaka (web.php dll)
        if ($this->isCodeRestricted($code)) {
            return response()->json(['is_available' => false]);
        }

        // 2. Cek apakah bertabrakan dengan database short_urls
        $query = ShortUrl::where('short_code', $code);
        if ($currentId) {
            $query->where('id', '!=', $currentId);
        }

        return response()->json(['is_available' => !$query->exists()]);
    }

    public function index(Request $request)
    {
        $query = ShortUrl::query();

        if ($request->has('search') && $request->search != '') {
            $searchTerm = $request->search;
            $query->where('original_url', 'like', "%{$searchTerm}%")
                  ->orWhere('short_code', 'like', "%{$searchTerm}%");
        }

        $shortUrls = $query->latest()->paginate(10)->withQueryString();
        $totalLinks = ShortUrl::count();
        $totalClicks = ShortUrl::sum('clicks');

        return view('short-urls.index', compact('shortUrls', 'totalLinks', 'totalClicks'));
    }

    public function create()
    {
        return view('short-urls.create');
    }

    public function store(Request $request)
    {
        // Validasi database
        $request->validate([
            'original_url' => 'required|url',
            'custom_code' => 'nullable|string|alpha_dash|unique:short_urls,short_code'
        ], [
            'custom_code.unique' => 'Nama URL custom ini sudah digunakan di database.'
        ]);

        if ($request->filled('custom_code')) {
            // Validasi Backend: Mencegah bypass/tembus dari inspect element
            if ($this->isCodeRestricted($request->custom_code)) {
                return back()->withErrors(['custom_code' => 'Nama URL ini tidak tersedia karena sudah digunakan oleh rute sistem Sancaka.'])->withInput();
            }
            $shortCode = $request->custom_code;
        } else {
            $shortCode = Str::random(6);
            // Pastikan random code juga tidak menabrak database & route sistem
            while (ShortUrl::where('short_code', $shortCode)->exists() || $this->isCodeRestricted($shortCode)) {
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
            'custom_code' => 'required|string|alpha_dash|unique:short_urls,short_code,' . $id
        ], [
            'custom_code.unique' => 'Nama URL custom ini sudah digunakan di database.'
        ]);

        // Validasi Backend: Mencegah bentrok dengan sistem
        if ($this->isCodeRestricted($request->custom_code)) {
            return back()->withErrors(['custom_code' => 'Nama URL ini tidak tersedia karena sudah digunakan oleh rute sistem Sancaka.'])->withInput();
        }

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

        $shortUrl->increment('clicks');

        return redirect()->away($shortUrl->original_url);
    }
}
