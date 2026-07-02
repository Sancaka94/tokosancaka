<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminDriverController extends Controller
{
    /**
     * Mengambil semua data pendaftaran driver beserta data penggunanya
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

            // Ambil data driver + Join dengan tabel Pengguna
            $drivers = DB::table('registrasi_driver_sancaka')
                ->leftJoin('Pengguna', 'registrasi_driver_sancaka.id_pengguna', '=', 'Pengguna.id_pengguna')
                ->select(
                    'registrasi_driver_sancaka.*',
                    'Pengguna.email as email_pengguna',
                    'Pengguna.role as role_pengguna',
                    'Pengguna.status as status_akun'
                )
                ->orderBy('registrasi_driver_sancaka.created_at', 'desc')
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
     * Update Status Driver (Terima / Tolak) dan Sinkronisasi Role Pengguna
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            // Pengaman akses Admin
            $user = $request->user();
            if ($user->id != 4 && $user->id_pengguna != 4) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak!'], 403);
            }

            $status = $request->input('status'); // 'approved' atau 'rejected'

            // Cari data pendaftaran driver terlebih dahulu
            $driverRegistration = DB::table('registrasi_driver_sancaka')->where('id', $id)->first();

            if (!$driverRegistration) {
                return response()->json(['success' => false, 'message' => 'Data pendaftaran tidak ditemukan.'], 404);
            }

            // Gunakan Transaction agar jika satu query gagal, database tidak rusak/setengah jalan
            DB::beginTransaction();

            try {
                // 1. Update status di tabel registrasi_driver_sancaka
                DB::table('registrasi_driver_sancaka')
                    ->where('id', $id)
                    ->update([
                        'status' => $status,
                        'updated_at' => now()
                    ]);

                // 2. Sinkronisasi dengan tabel Pengguna jika status = 'approved'
                if ($status === 'approved' && $driverRegistration->id_pengguna) {
                    DB::table('Pengguna')
                        ->where('id_pengguna', $driverRegistration->id_pengguna)
                        ->update([
                            'role' => 'Driver' // Sesuaikan jika penamaan rolenya berbeda di sistem Anda
                        ]);
                }

                // Commit transaksi jika semua sukses
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => "Status pendaftaran driver berhasil diubah menjadi " . strtoupper($status) . " dan tersinkronisasi."
                ]);

            } catch (\Exception $ex) {
                // Rollback transaksi jika terjadi kegagalan saat update
                DB::rollBack();
                throw $ex; // Lempar error ke catch utama
            }

        } catch (\Exception $e) {
            Log::error("[ADMIN DRIVER STATUS] Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server.'], 500);
        }
    }
}
