<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\DriverPerforma;

class RewardDriverOnlineMobileController extends Controller
{
    /**
     * Helper Statik untuk menyuntikkan Medali & Bintang ke string Nama Driver.
     * Dapat dipanggil dari ApiMapboxController Anda tanpa mengubah alur utamanya.
     */
    public static function formatNamaDriverDenganReward($id_pengguna, $nama_asli)
    {
        if ($id_pengguna == 4) {
            return "{$nama_asli} ⭐⭐⭐⭐⭐ [Pusat]"; // Khusus Admin
        }

        $rapor = DB::table('driver_performa')
            ->leftJoin('driver_medali', 'driver_performa.id_medali', '=', 'driver_medali.id')
            ->where('driver_performa.id_pengguna', $id_pengguna)
            ->select('bintang_manual', 'ikon', 'nama_medali')
            ->first();

        if (!$rapor) {
            return "{$nama_asli} ⭐⭐⭐⭐⭐ [🔰 Newbie]"; // Fallback jika belum disync
        }

        // Bikin deretan bintang sesuai skor
        $bintangStr = str_repeat('⭐', $rapor->bintang_manual ?? 5);
        $ikon = $rapor->ikon ?? '🔰';
        $namaMedali = $rapor->nama_medali ?? 'Newbie';

        // Hasil akhir: "Budi Santoso ⭐⭐⭐⭐ [🥇 Gold]"
        return "{$nama_asli} {$bintangStr} [{$ikon} {$namaMedali}]";
    }

    /**
     * Cek apakah driver punya izin Sancaka Express
     */
    public static function isTrustedForExpress($id_pengguna)
    {
        if ($id_pengguna == 4) return true; // Admin selalu bisa

        return DB::table('driver_performa')
            ->where('id_pengguna', $id_pengguna)
            ->value('is_trusted_express') == 1;
    }
}
