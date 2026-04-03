<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // LOG LOG: Menarik data statistik dashboard
    public function index(Request $request)
    {
        // 1. Ambil data user yang sedang login dari Token Sanctum
        $user = $request->user();

        // 2. Ambil Saldo (Tabel Pengguna)
        $saldo = $user->saldo ?? 0;

        // 3. Hitung Statistik (Menyesuaikan dengan tabel 'Pesanan' dan 'customer_id')
        $totalPesanan = DB::table('Pesanan')
            ->where('customer_id', $user->id_pengguna)
            ->count();

        $pesananSelesai = DB::table('Pesanan')
            ->where('customer_id', $user->id_pengguna)
            ->whereIn('status_pesanan', ['Selesai', 'Tiba di Tujuan'])
            ->count();

        $pesananPending = DB::table('Pesanan')
            ->where('customer_id', $user->id_pengguna)
            ->whereIn('status_pesanan', ['Menunggu Pembayaran', 'Pending', 'Diproses', 'Dikirim'])
            ->count();

        $pesananBatal = DB::table('Pesanan')
            ->where('customer_id', $user->id_pengguna)
            ->whereIn('status_pesanan', ['Dibatalkan', 'Batal', 'Retur'])
            ->count();

        // 4. Kembalikan Response JSON ke React Native
        return response()->json([
            'success' => true,
            'data' => [
                'namaLengkap' => $user->nama_lengkap, // Menyesuaikan kolom di tabel Pengguna
                'role' => $user->role,
                'saldo_format' => number_format($saldo, 0, ',', '.'),
                'saldo_raw' => $saldo,
                'statistik' => [
                    'totalPesanan' => $totalPesanan,
                    'pesananSelesai' => $pesananSelesai,
                    'pesananPending' => $pesananPending,
                    'pesananBatal' => $pesananBatal,
                ]
            ]
        ], 200);
    }
}
