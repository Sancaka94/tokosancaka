<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting; // WAJIB: Import Model Setting yang sudah Anda buat

class AdminController extends Controller
{
    /**
     * Menampilkan Dashboard Admin (Contoh jika belum ada)
     */
    public function index()
    {
        return view('admin.dashboard'); // Sesuaikan dengan view dashboard Anda
    }

    // =================================================================
    // BAGIAN PENGATURAN INFORMASI PESANAN (KOTAK MERAH)
    // =================================================================

    /**
     * 1. Menampilkan Form Edit Pesan
     * Route: GET /admin/setting-info-pesanan
     */
    public function editInfoPesanan()
    {
        // Cari data di database berdasarkan key 'info_pesanan'
        $setting = Setting::where('key', 'info_pesanan')->first();

        // Jika ada, ambil isinya (value). Jika tidak ada, default kosong.
        $pesan = $setting ? $setting->value : '';

        // Tampilkan view edit_info (pastikan file blade ini sudah dibuat)
        return view('admin.settings.edit_info', compact('pesan'));
    }

    /**
     * 2. Menyimpan/Update Pesan ke Database
     * Route: POST /admin/setting-info-pesanan
     */
    public function updateInfoPesanan(Request $request)
    {
        // Validasi: Input boleh text apa saja, boleh juga kosong (nullable)
        $request->validate([
            'pesan_admin' => 'nullable|string',
        ]);

        // Logika Simpan:
        // updateOrCreate akan mencari data dengan 'key' => 'info_pesanan'.
        // Jika ketemu -> Update 'value'-nya.
        // Jika tidak ketemu -> Buat baris baru.
        Setting::updateOrCreate(
            ['key' => 'info_pesanan'], // Kriteria pencarian
            ['value' => $request->pesan_admin] // Data yang disimpan
        );

        // Kembali ke halaman sebelumnya dengan pesan sukses
        return back()->with('success', 'Informasi Admin berhasil diperbarui!');
    }
}