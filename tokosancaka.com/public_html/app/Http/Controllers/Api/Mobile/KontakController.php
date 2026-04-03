<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Kontak;
use Illuminate\Support\Str; // Dibutuhkan untuk fitur perapian nomor HP

class KontakController extends Controller
{
    /**
     * ==========================================================
     * 1. MENGAMBIL DATA KONTAK (INDEX & SEARCH & FILTER)
     * ==========================================================
     */
    public function index(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi login tidak valid. Silakan login ulang.'
            ], 401);
        }

        $query = Kontak::query();

        // [CATATAN UNTUK BAPAK]:
        // Jika kolom 'id_Pengguna' di database Bapak sudah terisi ID user (bukan NULL lagi),
        // hapus tanda '//' di bawah ini agar pelanggan hanya bisa melihat kontaknya sendiri:
        // $query->where('id_Pengguna', $user->id_pengguna);

        // A. Filter Pencarian (Search)
        $search = $request->query('search', '');
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('nama', 'LIKE', "%{$search}%")
                  ->orWhere('no_hp', 'LIKE', "%{$search}%")
                  ->orWhere('alamat', 'LIKE', "%{$search}%");
            });
        }

        // B. Filter Berdasarkan Tipe Tab di Mobile (Pengirim / Penerima / Keduanya)
        $filter = $request->query('filter', 'Semua');
        if ($filter !== 'Semua') {
            $query->where('tipe', $filter);
        }

        // C. Eksekusi dengan Paginasi (Bukan limit biasa) agar Mobile bisa "Infinite Scroll"
        $kontaks = $query->latest()->paginate(15);

        return response()->json([
            'success'      => true,
            'message'      => 'Data kontak berhasil diambil',
            'data'         => $kontaks->items(),
            'current_page' => $kontaks->currentPage(),
            'last_page'    => $kontaks->lastPage()
        ], 200);
    }

    /**
     * ==========================================================
     * 2. MENYIMPAN KONTAK BARU (STORE)
     * ==========================================================
     */
    public function store(Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validasi input dari aplikasi Mobile
        $validatedData = $request->validate([
            'nama'   => 'required|string|max:255',
            'no_hp'  => 'required|string|max:20',
            'alamat' => 'required|string',
            'tipe'   => 'required|string|in:Pengirim,Penerima,Keduanya',
        ]);

        // Merapikan nomor HP (cth: +6281... atau 81... diubah jadi 081...)
        $validatedData['no_hp'] = $this->_sanitizePhoneNumber($validatedData['no_hp']);

        // Membersihkan nama dari simbol aneh/emoji (mencegah error di database)
        $validatedData['nama'] = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $validatedData['nama']));

        // [CATATAN UNTUK BAPAK]:
        // Jika database sudah siap untuk menampung ID Pengguna, aktifkan baris ini:
        // $validatedData['id_Pengguna'] = $user->id_pengguna;

        try {
            $kontak = Kontak::create($validatedData);
            return response()->json([
                'success' => true,
                'message' => 'Kontak berhasil disimpan.',
                'data'    => $kontak
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan kontak: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ==========================================================
     * 3. MENGHAPUS KONTAK (DESTROY)
     * ==========================================================
     */
    public function destroy($id)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $kontak = Kontak::find($id);

        if (!$kontak) {
            return response()->json([
                'success' => false,
                'message' => 'Kontak tidak ditemukan.'
            ], 404);
        }

        try {
            $kontak->delete();
            return response()->json([
                'success' => true,
                'message' => 'Kontak berhasil dihapus.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus.'
            ], 500);
        }
    }

    /**
     * ==========================================================
     * FUNGSI BANTUAN (PRIVATE HELPER): MERAPIKAN NOMOR HP
     * ==========================================================
     */
    private function _sanitizePhoneNumber(string $phone): string
    {
        // Buang semua karakter selain angka (spasi, strip, tanda plus hilang)
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Jika depannya 62, ubah jadi 0
        if (Str::startsWith($phone, '62')) {
            if (Str::startsWith(substr($phone, 2), '0')) {
                return '0' . substr($phone, 3);
            }
            return '0' . substr($phone, 2);
        }

        // Jika depannya 8 (tanpa 0), tambahkan 0 di depan
        if (!Str::startsWith($phone, '0') && Str::startsWith($phone, '8')) {
            return '0' . $phone;
        }

        return $phone;
    }
}
