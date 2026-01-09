<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting; // <-- Menggunakan model Setting
use App\Models\Banner;  // <-- Menggunakan model Banner (untuk galeri)
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SliderController extends Controller
{
    /**
     * Menampilkan halaman manajemen slider.
     * Ini akan mengambil data dari tabel 'settings' dan 'banners'.
     */
    public function index()
    {
        // 1. Ambil data slider dari tabel settings
        $sliderSetting = Setting::where('key', 'slider_informasi')->first();
        
        // 2. Decode JSON. Jika tidak ada, buat array kosong.
        $slides = $sliderSetting ? json_decode($sliderSetting->value, true) : [];

        // 3. Ambil data banner untuk galeri (sesuai Blade)
        $banners = Banner::orderBy('created_at', 'desc')->get();
        
        // 4. Tampilkan view dan kirim kedua variabel
        return view('admin.sliders', compact('slides', 'banners'));
    }

    /**
     * Menyimpan atau memperbarui pengaturan slider informasi di tabel 'settings'.
     * Ini adalah fungsi yang dipanggil oleh route 'admin.settings.slider.update'.
     */
    public function update(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'slides' => 'nullable|array',
            'slides.*.img' => 'nullable|string|max:2048', // Bisa URL atau path
            'slides.*.title' => 'nullable|string|max:255',
            'slides.*.desc' => 'nullable|string|max:1000',
        ]);

        try {
            // 2. Ambil data slides dari request
            $slidesData = $request->input('slides', []);

            // 3. Filter slide yang kosong (yang tidak diisi 'img' atau 'title')
            $filteredSlides = collect($slidesData)
                ->filter(function ($slide) {
                    // Hanya simpan jika 'img' atau 'title' ada isinya
                    return !empty($slide['img']) || !empty($slide['title']);
                })
                ->values() // Reset key array agar menjadi 0, 1, 2...
                ->all();

            // 4. Encode menjadi JSON
            $jsonSlides = json_encode($filteredSlides);

            // 5. Simpan ke database 'settings'
            // Ini akan mencari 'slider_informasi', jika ada di-update, jika tidak ada, dibuat baru.
            Setting::updateOrCreate(
                ['key' => 'slider_informasi'],
                ['value' => $jsonSlides]
            );

            return back()->with('success', 'Pengaturan slider berhasil disimpan.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menyimpan pengaturan slider: ' . $e->getMessage());
        }
    }
}