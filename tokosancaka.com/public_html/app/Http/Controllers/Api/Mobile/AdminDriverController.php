<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminDriverController extends Controller
{
    /**
     * Mengambil semua data pendaftaran driver
     */
    public function index(Request $request)
    {
        try {
            // Pengaman 1: Pastikan yang mengakses adalah Admin (Misal ID = 4)
            $user = $request->user();
            if ($user->id != 4 && $user->id_pengguna != 4) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak! Anda bukan Admin Sancaka.'
                ], 403);
            }

            // Ambil semua data driver, urutkan dari yang terbaru
            $drivers = DB::table('registrasi_driver_sancaka')
                ->orderBy('created_at', 'desc')
                ->get();

            // Format URL Gambar/File agar bisa diakses langsung via React Native
            $baseUrl = url('storage');
            $drivers->transform(function ($driver) use ($baseUrl) {
                $driver->file_ktp_url = $driver->file_ktp ? $baseUrl . '/' . $driver->file_ktp : null;
                $driver->file_kk_url = $driver->file_kk ? $baseUrl . '/' . $driver->file_kk : null;
                $driver->file_buku_nikah_url = $driver->file_buku_nikah ? $baseUrl . '/' . $driver->file_buku_nikah : null;
                $driver->file_stnk_url = $driver->file_stnk ? $baseUrl . '/' . $driver->file_stnk : null;
                $driver->file_bpkb_url = $driver->file_bpkb ? $baseUrl . '/' . $driver->file_bpkb : null;
                $driver->foto_motor_url = $driver->foto_motor ? $baseUrl . '/' . $driver->foto_motor : null;
                $driver->foto_wajah_url = $driver->foto_wajah ? $baseUrl . '/' . $driver->foto_wajah : null;
                return $driver;
            });

            return response()->json([
                'success' => true,
                'data' => $drivers
            ]);

        } catch (\Exception $e) {
            Log::error("[ADMIN DRIVER] Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server.'
            ], 500);
        }
    }

    /**
     * Update Status Driver (Terima / Tolak)
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $user = $request->user();
            if ($user->id != 4 && $user->id_pengguna != 4) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak!'], 403);
            }

            $status = $request->input('status'); // 'approved' atau 'rejected'

            DB::table('registrasi_driver_sancaka')
                ->where('id', $id)
                ->update([
                    'status' => $status,
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => "Status pendaftaran driver berhasil diubah menjadi " . strtoupper($status)
            ]);

        } catch (\Exception $e) {
            Log::error("[ADMIN DRIVER STATUS] Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server.'], 500);
        }
    }
}
