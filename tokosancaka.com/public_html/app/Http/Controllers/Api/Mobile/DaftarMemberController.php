<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Store;
use App\Models\User;

class DaftarMemberController extends Controller
{
    /**
     * Mengambil status lengkap keanggotaan dan pusat bisnis user
     */
    public function getStatus(Request $request)
    {
        $user = Auth::user();
        $userId = $user->id_pengguna ?? $user->id;

        // 1. Cek Toko (Store)
        $store = Store::where('user_id', $userId)->first();

        // 2. Cek Integrasi DANA (dari tabel dana_shops)
        $danaShop = DB::table('dana_shops')->where('user_id', $userId)->first();

        // 3. Susun respons data untuk Expo
        return response()->json([
            'success' => true,
            'data' => [
                'role'       => strtolower($user->role ?? 'member'),
                'saldo'      => (float) ($user->saldo ?? 0),
                'hasStore'   => $store ? true : false,
                'dokuSacId'  => $store ? $store->doku_sac_id : null,
                'danaStatus' => $danaShop ? $danaShop->dana_status : null,
            ]
        ]);
    }

    /**
     * Memproses Upgrade ke Agen (Potong Saldo 100rb)
     */
    public function registerAgent(Request $request)
    {
        $user = Auth::user();
        $userId = $user->id_pengguna ?? $user->id;

        // 1. Validasi Role
        if (strtolower($user->role) === 'agent' || strtolower($user->role) === 'admin' || strtolower($user->role) === 'superadmin') {
            return response()->json(['success' => false, 'message' => 'Anda sudah menjadi Agen/Admin.']);
        }

        // 2. Validasi Saldo Minimal
        if ($user->saldo < 2000000) {
            return response()->json(['success' => false, 'message' => 'Saldo Anda kurang dari syarat minimal Rp 2.000.000.']);
        }

        DB::beginTransaction();
        try {
            // 3. Potong Saldo Rp 100.000 (Biaya Server)
            // Catatan: Pastikan menggunakan query builder jika instance User tidak memiliki method save() yang sesuai
            DB::table('Pengguna')->where('id_pengguna', $userId)->update([
                'saldo' => DB::raw('saldo - 100000'),
                'role'  => 'agent'
            ]);

            /*
             * (Opsional) Jika Anda punya tabel mutasi/riwayat saldo,
             * Anda bisa melakukan insert data history di sini.
             * DB::table('mutasi_saldo')->insert([...]);
             */

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Selamat! Anda berhasil terdaftar sebagai Agen Resmi Sancaka.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal memproses pendaftaran: ' . $e->getMessage()], 500);
        }
    }
}
