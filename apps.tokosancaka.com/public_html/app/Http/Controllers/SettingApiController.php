<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SettingApi;
use Illuminate\Support\Facades\Log;

class SettingApiController extends Controller
{
    /**
     * Menampilkan halaman setting API
     */
    public function index()
    {
        // Cari data toggle DANA di database, kalau kosong anggap '0' (Sandbox)
        $danaMode = SettingApi::where('key', 'dana_production_mode')->value('value') ?? '0';

        // Pastikan Anda membuat file view ini nanti di folder resources/views/admin/settingapi/index.blade.php
        return view('admin.settingapi.index', compact('danaMode'));
    }

    /**
     * Memproses perubahan saat toggle di-klik
     */
    public function updateDanaMode(Request $request)
    {
        Log::info("LOG LOG: Memulai update status Mode DANA.");

        try {
            // Validasi input (hanya boleh angka 0 atau 1)
            $request->validate([
                'mode' => 'required|in:0,1'
            ]);

            // Update data di database, atau buat baru jika belum ada
            SettingApi::updateOrCreate(
                ['key' => 'dana_production_mode'],
                ['value' => $request->mode]
            );

            $modeText = $request->mode == '1' ? 'PRODUCTION' : 'SANDBOX';
            Log::info("LOG LOG: Mode DANA berhasil diubah menjadi {$modeText}!");

            // Kembalikan respon JSON untuk ditangkap oleh JavaScript
            return response()->json([
                'success' => true,
                'message' => "Mode DANA berhasil diubah ke {$modeText}!"
            ]);

        } catch (\Exception $e) {
            Log::error("LOG LOG: Gagal update Mode DANA - " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat mengubah mode.'
            ], 500);
        }
    }
}