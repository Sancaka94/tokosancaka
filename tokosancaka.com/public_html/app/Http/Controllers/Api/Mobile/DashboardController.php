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
        $user = $request->user();

        // Ambil Saldo (Sesuaikan dengan nama kolom/relasi di database Bapak)
        $saldo = $user->saldo ?? 0;

        // Hitung Statistik (Ganti 'pesanans' dengan nama tabel asli Bapak jika berbeda)
        $totalPesanan = DB::table('pesanans')->where('user_id', $user->id)->count();
        $pesananSelesai = DB::table('pesanans')
            ->where('user_id', $user->id)
            ->whereIn('status_pesanan', ['Selesai', 'Tiba di Tujuan'])
            ->count();
        $pesananPending = DB::table('pesanans')
            ->where('user_id', $user->id)
            ->whereIn('status_pesanan', ['Menunggu Pembayaran', 'Pending', 'Diproses'])
            ->count();
        $pesananBatal = DB::table('pesanans')
            ->where('user_id', $user->id)
            ->whereIn('status_pesanan', ['Dibatalkan', 'Batal', 'Retur'])
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'namaLengkap' => $user->nama_lengkap ?? $user->name,
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
