<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman dashboard utama untuk admin.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        try {
            $connection = DB::connection('pondok');

            // Mengambil data statistik utama
            $stats = [
                'total_santri' => $connection->table('santri')->count(),
                'total_pegawai' => $connection->table('pegawai')->count(),
                'total_kamar' => $connection->table('kamar')->count(),
                'total_pengguna' => $connection->table('pengguna')->count(),
            ];

            // Mengambil 5 pendaftar santri terbaru
            $calonSantriTerbaru = $connection->table('calon_santri')
                                    ->orderBy('created_at', 'desc')
                                    ->limit(5)
                                    ->get();

            return view('pondok.admin.dashboard', compact('stats', 'calonSantriTerbaru'));

        } catch (\Exception $e) {
            Log::error('Gagal memuat data dashboard: ' . $e->getMessage());
            // Mengembalikan view error jika koneksi gagal
            return view('pondok.admin.dashboard-error')->with('error', 'Tidak dapat terhubung ke database pondok.');
        }
    }
}

