<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Kontak;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB; // Tambahkan ini jika butuh query DB langsung

class KontakController extends Controller
{
    /**
     * ==========================================================
     * 1. MENGAMBIL DATA KONTAK (INDEX & SEARCH & FILTER & STATS)
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

        // ==========================================
        // LOGIKA ADMIN: ID 4 Bisa Lihat Semua
        // ==========================================
        $isAdmin = ($user->id_pengguna == 4 && strtolower($user->role) === 'admin');

        if (!$isAdmin) {
            $query->where(function($q) use ($user) {
                $q->where('user_id', $user->id_pengguna)
                  ->orWhere('id_Pengguna', $user->id_pengguna);
            });
        }

        // A. Filter Pencarian (Search)
        $search = $request->query('search', '');
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('nama', 'LIKE', "%{$search}%")
                  ->orWhere('no_hp', 'LIKE', "%{$search}%")
                  ->orWhere('alamat', 'LIKE', "%{$search}%");
            });
        }

        // B. Filter Berdasarkan Tipe Tab (Pengirim / Penerima / Keduanya)
        $filter = $request->query('filter', 'Semua');
        if ($filter !== 'Semua') {
            $query->where('tipe', $filter);
        }

        // C. Filter Berdasarkan Status (Baru, Repeat, Loyal)
        // Asumsi ada kolom 'total_pengiriman' di tabel kontak. Jika tidak ada, sesuaikan logikanya.
        $status = $request->query('status', '');
        if (!empty($status)) {
            if ($status === 'baru') {
                $query->where(function($q) {
                    $q->whereNull('total_pengiriman')->orWhere('total_pengiriman', '<=', 1);
                });
            } elseif ($status === 'repeat') {
                $query->where('total_pengiriman', 2);
            } elseif ($status === 'loyal') {
                $query->where('total_pengiriman', '>', 2);
            }
        }

        // D. GENERATE STATISTIK (Dihitung sebelum dipaginasi)
        $statsQuery = clone $query; // Kloning query agar tidak merusak query utama
        $total = $statsQuery->count();

        $count_baru = (clone $statsQuery)->where(function($q) {
            $q->whereNull('total_pengiriman')->orWhere('total_pengiriman', '<=', 1);
        })->count();

        $count_repeat = (clone $statsQuery)->where('total_pengiriman', 2)->count();
        $count_loyal = (clone $statsQuery)->where('total_pengiriman', '>', 2)->count();

        $stats = [
            'total' => $total,
            'count_baru' => $count_baru,
            'count_repeat' => $count_repeat,
            'count_loyal' => $count_loyal,
            'persen_baru' => $total > 0 ? round(($count_baru / $total) * 100) : 0,
            'persen_repeat' => $total > 0 ? round(($count_repeat / $total) * 100) : 0,
            'persen_loyal' => $total > 0 ? round(($count_loyal / $total) * 100) : 0,
        ];

        // E. Eksekusi dengan Paginasi
        $kontaks = $query->latest()->paginate(15);

        return response()->json([
            'success'      => true,
            'message'      => 'Data kontak berhasil diambil',
            'data'         => $kontaks->items(),
            'stats'        => $stats, // STATISTIK DIKIRIM KE REACT NATIVE
            'current_page' => $kontaks->currentPage(),
            'last_page'    => $kontaks->lastPage(),
            'total'        => $kontaks->total()
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
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

        $validatedData = $request->validate([
            'nama'   => 'required|string|max:255',
            'no_hp'  => 'required|string|max:20',
            'alamat' => 'required|string',
            'tipe'   => 'required|string|in:Pengirim,Penerima,Keduanya',
        ]);

        $validatedData['no_hp'] = $this->_sanitizePhoneNumber($validatedData['no_hp']);
        $validatedData['nama'] = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $validatedData['nama']));
        $validatedData['user_id'] = $user->id_pengguna;
        $validatedData['id_Pengguna'] = $user->id_pengguna;
        $validatedData['total_pengiriman'] = 0; // Set default 0

        try {
            $kontak = Kontak::create($validatedData);
            return response()->json(['success' => true, 'message' => 'Kontak berhasil disimpan.', 'data' => $kontak], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * ==========================================================
     * 3. MENGUBAH KONTAK (UPDATE) - TAMBAHAN WAJIB UNTUK MOBILE
     * ==========================================================
     */
    public function update(Request $request, $id)
    {
        $user = auth('sanctum')->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

        $kontak = Kontak::find($id);
        if (!$kontak) return response()->json(['success' => false, 'message' => 'Kontak tidak ditemukan.'], 404);

        $isAdmin = ($user->id_pengguna == 4 && strtolower($user->role) === 'admin');
        $isOwner = ($kontak->user_id == $user->id_pengguna || $kontak->id_Pengguna == $user->id_pengguna);

        if (!$isAdmin && !$isOwner) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $validatedData = $request->validate([
            'nama'   => 'required|string|max:255',
            'no_hp'  => 'required|string|max:20',
            'alamat' => 'required|string',
            'tipe'   => 'required|string|in:Pengirim,Penerima,Keduanya',
        ]);

        $validatedData['no_hp'] = $this->_sanitizePhoneNumber($validatedData['no_hp']);
        $validatedData['nama'] = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $validatedData['nama']));

        try {
            $kontak->update($validatedData);
            return response()->json(['success' => true, 'message' => 'Kontak berhasil diperbarui.', 'data' => $kontak], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal update: ' . $e->getMessage()], 500);
        }
    }

    /**
     * ==========================================================
     * 4. MENGHAPUS KONTAK (DESTROY)
     * ==========================================================
     */
    public function destroy($id)
    {
        $user = auth('sanctum')->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

        $kontak = Kontak::find($id);
        if (!$kontak) return response()->json(['success' => false, 'message' => 'Kontak tidak ditemukan.'], 404);

        $isAdmin = ($user->id_pengguna == 4 && strtolower($user->role) === 'admin');
        $isOwner = ($kontak->user_id == $user->id_pengguna || $kontak->id_Pengguna == $user->id_pengguna);

        if (!$isAdmin && !$isOwner) return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);

        try {
            $kontak->delete();
            return response()->json(['success' => true, 'message' => 'Kontak berhasil dihapus.'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Kesalahan saat menghapus.'], 500);
        }
    }

    /**
     * ==========================================================
     * 5. RIWAYAT PENGIRIMAN (HISTORY) - TAMBAHAN WAJIB UNTUK MOBILE
     * ==========================================================
     */
    public function history(Request $request, $id)
    {
        $user = auth('sanctum')->user();
        $kontak = Kontak::find($id);

        if (!$kontak) {
            return response()->json(['success' => false, 'message' => 'Kontak tidak ditemukan'], 404);
        }

        // Ambil data histori dari tabel 'pesanans' atau nama tabel transaksimu
        // Asumsi: dicari berdasarkan nomor hp penerima/pengirim yang sama dengan kontak
        $riwayat = DB::table('pesanans') // GANTI 'pesanans' DENGAN NAMA TABEL ASLI KAMU JIKA BEDA
                    ->where('sender_phone', $kontak->no_hp)
                    ->orWhere('receiver_phone', $kontak->no_hp)
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);

        $total_omzet = DB::table('pesanans')
                    ->where('sender_phone', $kontak->no_hp)
                    ->orWhere('receiver_phone', $kontak->no_hp)
                    ->sum('shipping_cost'); // Sesuaikan nama kolom harga ongkir

        return response()->json([
            'success'     => true,
            'kontak'      => $kontak,
            'total_paket' => $riwayat->total(),
            'total_omzet' => (int) $total_omzet,
            'history'     => $riwayat
        ], 200);
    }

    /**
     * ==========================================================
     * FUNGSI BANTUAN (PRIVATE HELPER)
     * ==========================================================
     */
    private function _sanitizePhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (Str::startsWith($phone, '62')) {
            if (Str::startsWith(substr($phone, 2), '0')) return '0' . substr($phone, 3);
            return '0' . substr($phone, 2);
        }
        if (!Str::startsWith($phone, '0') && Str::startsWith($phone, '8')) return '0' . $phone;
        return $phone;
    }
}
