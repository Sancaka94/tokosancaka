<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Pastikan DB facade di-import

class PondokController extends Controller
{
    /**
     * Menampilkan halaman pondok dengan daftar paket harga.
     */
    public function index()
    {
        try {
            // Mengambil data paket dari koneksi database 'pondok'
            // yang telah kita definisikan di config/database.php

            // PERBAIKAN:
            // 1. Nama tabel diubah dari 'packages' menjadi 'paket' agar sesuai dengan database.
            // 2. Kolom untuk sorting diubah dari 'price' menjadi 'harga'.
            $packages = DB::connection('pondok')->table('paket')->orderBy('harga', 'asc')->get();

            // Mengirim data packages ke view
            return view('pondok', ['packages' => $packages]);

        } catch (\Exception $e) {
            // Jika terjadi error (misal: koneksi gagal, tabel tidak ada)
            // kita akan tetap menampilkan halaman pondok, tapi dengan array kosong
            // dan mencatat error untuk debugging.
            report($e); // Melaporkan error ke log Laravel
            return view('pondok', ['packages' => []])->withErrors('Tidak dapat memuat daftar paket saat ini.');
        }
    }
}
