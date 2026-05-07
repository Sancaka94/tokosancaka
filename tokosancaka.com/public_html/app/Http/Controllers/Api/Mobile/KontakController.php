<?php
// LOG LOG: Final Fix Database Sync (Hitung Repeat Order dari tabel Pesanan via Nomor HP)

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Kontak;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

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
            return response()->json(['success' => false, 'message' => 'Sesi login tidak valid. Silakan login ulang.'], 401);
        }

        $query = Kontak::query();

        // CEK ADMIN
        $userId = $user->id_pengguna ?? $user->id;
        $isAdmin = ($userId == 4 && !empty($user->role) && strtolower($user->role) === 'admin');

        if (!$isAdmin) {
            $query->where(function($q) use ($userId) {
                $q->where('user_id', $userId)->orWhere('id_Pengguna', $userId);
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

        // B. Filter Tipe
        $filter = $request->query('filter', 'Semua');
        if ($filter !== 'Semua') {
            $query->where('tipe', $filter);
        }

        // =========================================================
        // FIX BUG SINKRONISASI: HITUNG TOTAL ORDER SECARA DINAMIS
        // =========================================================
        // Karena kolom total_pengiriman di database mungkin belum akurat/kosong,
        // kita akan menghitung langsung dari tabel pesanans saat itu juga.

        $kontaksList = $query->latest()->get(); // Ambil semua data (tanpa paginasi dulu) untuk diolah

        // Menggabungkan total order ke dalam masing-masing kontak
        $processedKontaks = $kontaksList->map(function($k) {
            $total_order = DB::table('pesanans')
                ->where('sender_phone', $k->no_hp)
                ->orWhere('receiver_phone', $k->no_hp)
                ->count();

            $k->total_pengiriman = $total_order;
            return $k;
        });

        // C. Filter Status berdasarkan hasil perhitungan dinamis
        $status = $request->query('status', '');
        if (!empty($status)) {
            if ($status === 'baru') {
                $processedKontaks = $processedKontaks->where('total_pengiriman', '<=', 1);
            } elseif ($status === 'repeat') {
                $processedKontaks = $processedKontaks->where('total_pengiriman', 2);
            } elseif ($status === 'loyal') {
                $processedKontaks = $processedKontaks->where('total_pengiriman', '>', 2);
            }
        }

        // D. GENERATE STATISTIK DARI DATA YANG SUDAH DIPROSES
        $total = $processedKontaks->count();
        $count_baru = $processedKontaks->where('total_pengiriman', '<=', 1)->count();
        $count_repeat = $processedKontaks->where('total_pengiriman', 2)->count();
        $count_loyal = $processedKontaks->where('total_pengiriman', '>', 2)->count();

        $stats = [
            'total'         => $total,
            'count_baru'    => $count_baru,
            'count_repeat'  => $count_repeat,
            'count_loyal'   => $count_loyal,
            'persen_baru'   => $total > 0 ? round(($count_baru / $total) * 100) : 0,
            'persen_repeat' => $total > 0 ? round(($count_repeat / $total) * 100) : 0,
            'persen_loyal'  => $total > 0 ? round(($count_loyal / $total) * 100) : 0,
        ];

        // E. Paginasi Manual Koleksi (Karena tadi kita ambil pakai get())
        $page = $request->query('page', 1);
        $perPage = 15;
        $paginatedItems = $processedKontaks->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'success'      => true,
            'message'      => 'Data kontak berhasil diambil',
            'data'         => $paginatedItems,
            'stats'        => $stats,
            'current_page' => (int) $page,
            'last_page'    => ceil($total / $perPage),
            'total'        => $total
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
        $validatedData['nama']  = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $validatedData['nama']));

        $userId = $user->id_pengguna ?? $user->id;
        $validatedData['user_id']     = $userId;
        $validatedData['id_Pengguna'] = $userId;
        $validatedData['total_pengiriman'] = 0; // Kolom statis dibiarkan 0, perhitungan akan pakai query dinamis

        try {
            $kontak = Kontak::create($validatedData);
            return response()->json(['success' => true, 'message' => 'Kontak berhasil disimpan.', 'data' => $kontak], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * ==========================================================
     * 3. MENGUBAH KONTAK (UPDATE)
     * ==========================================================
     */
    public function update(Request $request, $id)
    {
        $user = auth('sanctum')->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

        $kontak = Kontak::find($id);
        if (!$kontak) return response()->json(['success' => false, 'message' => 'Kontak tidak ditemukan.'], 404);

        $userId = $user->id_pengguna ?? $user->id;
        $isAdmin = ($userId == 4 && !empty($user->role) && strtolower($user->role) === 'admin');
        $isOwner = ($kontak->user_id == $userId || $kontak->id_Pengguna == $userId);

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
        $validatedData['nama']  = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $validatedData['nama']));

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

        $userId = $user->id_pengguna ?? $user->id;
        $isAdmin = ($userId == 4 && !empty($user->role) && strtolower($user->role) === 'admin');
        $isOwner = ($kontak->user_id == $userId || $kontak->id_Pengguna == $userId);

        if (!$isAdmin && !$isOwner) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        try {
            $kontak->delete();
            return response()->json(['success' => true, 'message' => 'Kontak berhasil dihapus.'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Kesalahan saat menghapus.'], 500);
        }
    }

    /**
     * ==========================================================
     * 5. RIWAYAT PENGIRIMAN (HISTORY)
     * ==========================================================
     */
    public function history(Request $request, $id)
    {
        $user = auth('sanctum')->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

        $kontak = Kontak::find($id);
        if (!$kontak) {
            return response()->json(['success' => false, 'message' => 'Kontak tidak ditemukan'], 404);
        }

        // Keamanan
        $userId = $user->id_pengguna ?? $user->id;
        $isAdmin = ($userId == 4 && !empty($user->role) && strtolower($user->role) === 'admin');
        $isOwner = ($kontak->user_id == $userId || $kontak->id_Pengguna == $userId);

        if (!$isAdmin && !$isOwner) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        // SINKRONISASI DATABASE: Menggunakan tabel 'pesanans' dan mencocokkan 'sender_phone' atau 'receiver_phone'
        $riwayat = DB::table('pesanans')
                    ->where('sender_phone', $kontak->no_hp)
                    ->orWhere('receiver_phone', $kontak->no_hp)
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);

        // Menghitung omzet dari ongkir (shipping_cost)
        $total_omzet = DB::table('pesanans')
                    ->where('sender_phone', $kontak->no_hp)
                    ->orWhere('receiver_phone', $kontak->no_hp)
                    ->sum('shipping_cost');

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
