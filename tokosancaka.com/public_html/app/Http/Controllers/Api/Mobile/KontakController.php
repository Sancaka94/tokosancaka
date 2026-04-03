<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Kontak; // Pastikan menggunakan Model Kontak

class KontakController extends Controller
{
    /**
     * Mengambil data pencarian kontak khusus untuk aplikasi Mobile
     */
    public function index(Request $request)
    {
        // 1. Cek siapa yang login menggunakan Sanctum
        $user = auth('sanctum')->user();

        // Jika tidak ada user login, kembalikan error yang sopan (jangan sampai server crash)
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi login tidak valid. Silakan login ulang.'
            ], 401);
        }

        $search = $request->query('search', '');

        // 2. Mulai Pencarian di tabel kontaks
        $query = Kontak::query();

        // CATATAN PENTING:
        // Karena di database Bapak kolom 'id_Pengguna' isinya NULL semua,
        // saya MATIKAN (comment) filter id_Pengguna ini agar pencarian bisa memunculkan semua kontak.
        // Jika nanti database Bapak sudah rapi (ada id_Pengguna-nya), hapus tanda '//' di bawah ini:

        $query->where('id_Pengguna', $user->id_pengguna);

        // 3. Logika pencarian berdasarkan nama, no_hp, atau alamat
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('nama', 'LIKE', "%{$search}%")
                  ->orWhere('no_hp', 'LIKE', "%{$search}%")
                  ->orWhere('alamat', 'LIKE', "%{$search}%");
            });
        }

        // 4. Ambil maksimal 15 data agar aplikasi mobile tetap ringan
        $kontaks = $query->latest()->limit(15)->get();

        // 5. Kembalikan data dalam format JSON yang bisa dibaca aplikasi React Native
        return response()->json([
            'success' => true,
            'message' => 'Data kontak berhasil diambil',
            'data'    => $kontaks
        ], 200);
    }
}
