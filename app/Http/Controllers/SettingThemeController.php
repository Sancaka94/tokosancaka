<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SettingTheme;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class SettingThemeController extends Controller
{
    /**
     * Menampilkan Form Edit Tema
     */
    public function edit()
    {
        // 1. Ambil semua data setting dan ubah jadi format: ['key' => 'value']
        // Contoh hasil: ['site_title' => 'Sancaka', 'primary_color' => '#3b82f6']
        $theme = SettingTheme::pluck('value', 'key')->toArray();

        // 2. Kirim ke view
        return view('admin.theme-edit', compact('theme'));
    }

    /**
     * Memproses Penyimpanan Data
     */
    public function update(Request $request)
    {
        // 1. Ambil semua input kecuali _token
        $data = $request->except('_token');

        // 2. Loop setiap input
        foreach ($data as $key => $value) {
            
            // Cek apakah input ini adalah File (Gambar/Logo)
            if ($request->hasFile($key)) {
                // Proses Upload Gambar
                $file = $request->file($key);
                $filename = time() . '_' . $file->getClientOriginalName();
                
                // Simpan ke folder public/uploads/theme
                $path = $file->storeAs('uploads/theme', $filename, 'public');
                $value = '/storage/' . $path; // Simpan path-nya ke database
            }

            // Simpan atau Update ke Database berdasarkan 'key'
            SettingTheme::updateOrCreate(
                ['key' => $key],    // Cari berdasarkan kolom key
                ['value' => $value] // Update kolom value
            );
        }

        // 3. (Opsional) Hapus Cache jika Anda menggunakan Cache di AppServiceProvider
        // Cache::forget('global_theme');

        return back()->with('success', 'Tampilan website berhasil diperbarui!');
    }
}