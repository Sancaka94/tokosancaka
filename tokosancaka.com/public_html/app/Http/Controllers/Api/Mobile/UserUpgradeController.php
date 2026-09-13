<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;

class UserUpgradeController extends Controller
{
    /**
     * 1. Mendapatkan Status Upgrade User (Ditampilkan di Expo)
     */
    public function getStatus(Request $request)
    {
        try {
            $user = $request->user();

            // 1. Cek Toko (Apakah kolom store_name ada isinya?)
            $hasStore = !empty($user->store_name);

            // 2. Cek Role (Ambil dari kolom 'role', default 'pelanggan')
            $role = strtolower($user->role ?? 'pelanggan');

            // 3. Cek DANA (Jika dana_access_token tidak kosong, berarti terhubung)
            $danaStatus = !empty($user->dana_access_token) ? 'SUCCESS' : null;

            // 4. Cek DOKU (Fallback aman jika kolom doku_sac_id belum dibuat di DB)
            $dokuSacId = $user->doku_sac_id ?? null;

            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil status user',
                'data' => [
                    'role'       => $role,
                    'hasStore'   => $hasStore,
                    'isDriver'   => $role === 'driver', 
                    'danaStatus' => $danaStatus,
                    'dokuSacId'  => $dokuSacId,
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
            $currentRole = strtolower($user->role ?? '');

            // Jika sudah jadi agent/admin, tolak
            if (in_array($currentRole, ['agent', 'admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun Anda sudah terdaftar sebagai Agen.'
                ], 400);
            }

            // Ubah role menjadi Agent
            $user->role = 'Agent';
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Selamat! Akun Anda berhasil diupgrade menjadi Agen Resmi Sancaka.',
                'data' => [
                    'id_pengguna' => $user->id_pengguna,
                    'role' => $user->role
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('API Register Agent Error: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

   public function registerStore(Request $request)
    {
        $user = $request->user();

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'store_name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Nama toko tidak boleh kosong.'], 422);
        }

        try {
            // 1. Simpan ke tabel Pengguna
            $user->store_name = $request->store_name;
            
            $currentRole = strtolower($user->role ?? '');
            if (in_array($currentRole, ['member', 'pelanggan', ''])) {
                $user->role = 'Seller';
            }
            $user->save();

            // 2. 🔥 SINKRONISASI OTOMATIS KE TABEL `stores` 🔥
            $userId = $user->id_pengguna ?? $user->id;
            \Illuminate\Support\Facades\DB::table('stores')->updateOrInsert(
                ['user_id' => $userId], // Kunci pencarian: Jika user_id ini sudah punya toko, maka update. Jika belum, buat baru.
                [
                    'name' => $request->store_name,
                    'slug' => \Illuminate\Support\Str::slug($request->store_name . '-' . $userId),
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Toko berhasil dibuat! Anda sekarang dapat mulai berjualan.',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('API Register Store Error: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 4. Pendaftaran DANA Bisnis Merchant
     */
    public function registerDana(Request $request)
    {
        try {
            $user = $request->user();

            // PENGAMAN: Cek apakah kolom dana_status benar-benar ada di database
            // Jika tidak ada, kita hindari $user->save() agar tidak SQL Error
            if (Schema::hasColumn('Pengguna', 'dana_status')) {
                $user->dana_status = 'PENDING'; 
                $user->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan DANA Bisnis berhasil dikirim dan sedang diproses.',
                'data' => ['danaStatus' => 'PENDING']
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

            $simulatedSacId = 'SAC-' . time() . '-' . $user->id_pengguna;
            
            // PENGAMAN: Cek apakah kolom doku_sac_id benar-benar ada di database
            if (Schema::hasColumn('Pengguna', 'doku_sac_id')) {
                $user->doku_sac_id = $simulatedSacId;
                $user->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Dompet Sancaka (DOKU) berhasil diaktifkan.',
                'data' => ['dokuSacId' => $simulatedSacId]
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