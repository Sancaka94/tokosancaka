<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class UserUpgradeController extends Controller
{
    /**
     * 1. Mendapatkan Status Upgrade User (Ditampilkan di Expo)
     */
    public function getStatus(Request $request)
    {
        try {
            $user = $request->user();

            // Berdasarkan referensi ProfileController, nama toko disimpan di tabel user
            // Maka hasStore bisa dicek dari ada atau tidaknya store_name
            $hasStore = !empty($user->store_name);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil status user',
                'data' => [
                    'role'       => strtolower($user->role ?? 'member'), // 'agent', 'seller', atau 'member'
                    'hasStore'   => $hasStore,
                    'danaStatus' => $user->dana_status ?? null, // Misal: 'SUCCESS' atau 'PENDING'
                    'dokuSacId'  => $user->doku_sac_id ?? null,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('API Upgrade Status Error: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat mengambil status.'
            ], 500);
        }
    }

    /**
     * 2. Mendaftar Menjadi Agen Resmi Sancaka
     */
    public function registerAgent(Request $request)
    {
        try {
            $user = $request->user();

            // Jika sudah jadi agent/admin, tolak
            if (in_array(strtolower($user->role), ['agent', 'admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun Anda sudah terdaftar sebagai Agen.'
                ], 400);
            }

            // --- Tulis Logika/Syarat Pendaftaran Agen Di Sini ---
            // Contoh: Mengubah role menjadi Agent
            $user->role = 'Agent';
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Selamat! Akun Anda berhasil diupgrade menjadi Agen Resmi Sancaka.',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            Log::error('API Register Agent Error: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 3. Mendaftar Buka Toko (Seller)
     */
    public function registerStore(Request $request)
    {
        $user = $request->user();

        // Gunakan Validator sama seperti di ProfileController
        $validator = Validator::make($request->all(), [
            'store_name' => ['required', 'string', 'max:255'],
            // Tambahkan validasi lain jika diperlukan (misal: provinsi, kota)
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data toko tidak valid. Cek kembali form Anda.',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // Update data user
            $user->store_name = $request->store_name;
            
            // Ubah role menjadi seller jika saat ini hanya member biasa
            if (strtolower($user->role) === 'member' || empty($user->role)) {
                $user->role = 'Seller';
            }
            
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Toko berhasil dibuat! Anda sekarang dapat mulai berjualan.',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            Log::error('API Register Store Error: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membuka toko: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 4. Pendaftaran DANA Bisnis Merchant
     */
    public function registerDana(Request $request)
    {
        try {
            $user = $request->user();

            // --- Tulis Logika Integrasi DANA Di Sini ---
            // Contoh simulasi: Status menjadi PENDING untuk menunggu verifikasi DANA
            $user->dana_status = 'PENDING'; 
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan DANA Bisnis berhasil dikirim dan sedang diproses.',
                'data' => ['danaStatus' => $user->dana_status]
            ]);

        } catch (\Exception $e) {
            Log::error('API Register DANA Error: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pengajuan DANA: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 5. Aktivasi Dompet Sancaka (DOKU SAC)
     */
    public function activateDoku(Request $request)
    {
        try {
            $user = $request->user();

            if (empty($user->store_name)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda harus membuka toko terlebih dahulu sebelum mengaktifkan Dompet Sancaka.'
                ], 400);
            }

            // --- Tulis Logika Request API DOKU Di Sini ---
            // Contoh simulasi: Generate SAC ID
            $simulatedSacId = 'SAC-' . time() . '-' . $user->id_pengguna;
            
            $user->doku_sac_id = $simulatedSacId;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Dompet Sancaka (DOKU) berhasil diaktifkan.',
                'data' => ['dokuSacId' => $user->doku_sac_id]
            ]);

        } catch (\Exception $e) {
            Log::error('API Activate DOKU Error: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan aktivasi DOKU: ' . $e->getMessage()
            ], 500);
        }
    }
}