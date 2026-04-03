<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Kontak; // Menggunakan Model Kontak milik Bapak

class KontakController extends Controller
{
    /**
     * Mengambil data pencarian kontak khusus untuk aplikasi Mobile
     */
    public function index(Request $request)
    {
        // 1. Tangkap kata kunci pencarian dari HP
        $search = $request->query('search', '');

        // 2. Mulai pencarian di tabel kontaks
        $query = Kontak::query();

        // [OPSIONAL] Jika sistem Bapak multi-user (pelanggan hanya bisa melihat kontaknya sendiri),
        // hapus tanda komentar (//) pada baris di bawah ini dan sesuaikan nama kolomnya:
        $query->where('id_pengguna', Auth::user()->id_pengguna);

        // 3. Logika pencarian mirip dengan versi Web Bapak
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('nama', 'LIKE', "%{$search}%")
                  ->orWhere('no_hp', 'LIKE', "%{$search}%")
                  ->orWhere('alamat', 'LIKE', "%{$search}%");
            });
        }

        // 4. Ambil datanya (dibatasi 15 agar aplikasi HP tidak berat)
        $kontaks = $query->latest()->limit(15)->get();

        // 5. Kembalikan ke HP dalam bentuk JSON
        return response()->json([
            'success' => true,
            'message' => 'Data kontak berhasil diambil',
            'data'    => $kontaks
        ], 200);
    }
}
