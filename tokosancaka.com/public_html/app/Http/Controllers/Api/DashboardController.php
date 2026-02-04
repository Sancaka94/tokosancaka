<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Pesanan;
use App\Models\ScannedPackage;

class DashboardController extends Controller
{
    /**
     * Mengambil dan mengembalikan data untuk dashboard customer.
     * Hanya bisa diakses oleh pengguna yang sudah login (via Sanctum).
     */
    public function index(Request $request)
    {
        $customer = $request->user(); // Mengambil data user yang sedang login
        $customerId = $customer->id_pengguna;

        // --- Mengambil data statistik ---
        $saldo = $customer->saldo; // Mengambil saldo langsung dari model user
        $totalPesanan = Pesanan::where('id_pengguna_pembeli', $customerId)->count();
        $pesananSelesai = Pesanan::where('id_pengguna_pembeli', $customerId)->where('status_pesanan', 'Tiba di Tujuan')->count();
        $menungguPembayaran = Pesanan::where('id_pengguna_pembeli', $customerId)->where('status_pesanan', 'Menunggu Pembayaran')->count();
        
        // Mengambil 5 pesanan terbaru
        $pesananTerbaru = Pesanan::where('id_pengguna_pembeli', $customerId)
            ->latest('tanggal_pesanan')
            ->take(5)
            ->get();

        // Menggabungkan semua data ke dalam satu array
        $data = [
            'saldo' => [
                'value' => $saldo,
                'formatted' => 'Rp ' . number_format($saldo, 0, ',', '.')
            ],
            'totalPesanan' => $totalPesanan,
            'pesananSelesai' => $pesananSelesai,
            'menungguPembayaran' => $menungguPembayaran,
            'pesananTerbaru' => $pesananTerbaru
        ];

        // Mengembalikan semua data dalam format JSON
        return response()->json($data);
    }
}
