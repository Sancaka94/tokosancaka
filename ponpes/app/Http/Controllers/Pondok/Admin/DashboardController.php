<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    protected $connection;

    public function __construct()
    {
        // Menggunakan koneksi database 'pondok'
        $this->connection = DB::connection('pondok');
    }

    /**
     * Menampilkan halaman dasbor admin.
     */
    public function index()
    {
        try {
            // Mengambil data statistik
            $stats = [
                'jumlah_santri' => $this->connection->table('santri')->count(),
                'jumlah_pegawai' => $this->connection->table('pegawai')->count(),
                'jumlah_kelas' => $this->connection->table('kelas')->count(),
                'jumlah_kamar' => $this->connection->table('kamar')->count(),
            ];

            // Mengambil 5 pendaftar terbaru
            $calonSantriTerbaru = $this->connection->table('calon_santri')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Mengirim data ke view jika berhasil
            return view('pondok.admin.dashboard', compact('stats', 'calonSantriTerbaru'));

        } catch (\Exception $e) {
            // Mencatat error ke log untuk debugging
            Log::error('Gagal memuat data dashboard: ' . $e->getMessage());
            
            // PERBAIKAN: 
            // Mengembalikan ke view dashboard utama dengan pesan error,
            // daripada memanggil view 'dashboard-error' yang tidak ada.
            return view('pondok.admin.dashboard')
                   ->with('error', 'Tidak dapat memuat data dasbor. Periksa koneksi atau nama tabel di database Anda.');
        }
    }
}

