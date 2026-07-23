<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PesananAutokirim;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KomisiAgentController extends Controller
{
    public function index(Request $request)
    {
        // Tidak perlu lagi pakai with('agentFee')
        $query = User::where('role', 'agent');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('store_name', 'like', "%{$search}%")
                  ->orWhere('no_wa', 'like', "%{$search}%");
            });
        }

        $agents = $query->paginate(15)->withQueryString();

       // Hitung total statistik (Card Atas)
        $totalAgen = User::where('role', 'agent')->count();

        $excluded_statuses = ['batal', 'gagal', 'waiting_payment', 'menunggu_pembayaran'];

        $totalPencairan = PesananAutokirim::whereNotIn('status', $excluded_statuses)->sum('komisi_agen');
        $totalLabaSancaka = PesananAutokirim::whereNotIn('status', $excluded_statuses)->sum('laba_sistem');

        $stats = [
            'total_agen' => $totalAgen,
            'total_komisi_dibayar' => $totalPencairan,
            'total_laba_sancaka' => $totalLabaSancaka,
        ];

        return view('admin.komisiagent', compact('agents', 'stats'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'fee_percentage' => 'required|numeric|min:1|max:100'
        ]);

        try {
            $user = User::findOrFail($id);
            $user->update(['fee_autokirim' => $request->fee_percentage]);

            Log::info("LOG LOG: [UPDATE FEE AGENT] Admin mengubah fee agen ID {$id} menjadi {$request->fee_percentage}%");

            return redirect()->back()->with('success', 'Persentase Fee Agen berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui fee: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            // Reset ke default 40
            $user = User::findOrFail($id);
            $user->update(['fee_autokirim' => 40]);

            Log::info("LOG LOG: [RESET FEE AGENT] Admin mereset fee agen ID {$id} ke default (40%)");

            return redirect()->back()->with('success', 'Fee agen berhasil direset ke default sistem (40%).');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mereset fee agen.');
        }
    }

    // Hapus User (Soft Delete / Permanen tergantung konfigurasi modelmu)
    public function deleteUser($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();
            Log::info("LOG LOG: [DELETE AGENT] Admin menghapus agen ID {$id}");
            return redirect()->back()->with('success', 'Data Agen berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus agen: ' . $e->getMessage());
        }
    }

    // Bulk Edit Komisi
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'fee_percentage' => 'required|numeric|min:1|max:100'
        ]);

        try {
            User::whereIn('id_pengguna', $request->ids)->update(['fee_autokirim' => $request->fee_percentage]);
            Log::info("LOG LOG: [BULK UPDATE FEE] Admin mengubah fee massal untuk " . count($request->ids) . " agen menjadi {$request->fee_percentage}%");
            return redirect()->back()->with('success', count($request->ids) . ' Agen berhasil diupdate komisinya.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat update massal.');
        }
    }

    // Bulk Destroy (Hapus Massal)
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array'
        ]);

        try {
            User::whereIn('id_pengguna', $request->ids)->delete();
            Log::info("LOG LOG: [BULK DELETE AGENT] Admin menghapus " . count($request->ids) . " agen secara massal");
            return redirect()->back()->with('success', count($request->ids) . ' Agen berhasil dihapus secara massal.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat hapus massal.');
        }
    }

    // --- FITUR BARU: CAIRKAN KOMISI ---
    public function cairkanKomisi(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'nominal_cair' => 'required|numeric|min:1'
        ]);

        try {
            DB::beginTransaction();

            $user = User::where('id', $request->user_id)->firstOrFail();
            $nominal = $request->nominal_cair;

            // Tambahkan ke saldo agen
            $user->saldo = ($user->saldo ?? 0) + $nominal;
            $user->save();

            // Catat ke tabel riwayat_pencairans
            DB::table('riwayat_pencairans')->insert([
                'user_id' => $user->id,
                'nominal' => $nominal,
                'keterangan' => 'Pencairan komisi ke saldo agen',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            Log::info("LOG LOG: [PENCAIRAN KOMISI] Admin mencairkan Rp {$nominal} ke saldo agen ID {$user->id}");

            return redirect()->back()->with('success', 'Komisi sebesar Rp ' . number_format($nominal, 0, ',', '.') . ' berhasil dicairkan ke saldo agen.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mencairkan komisi: ' . $e->getMessage());
        }
    }

    // --- FITUR BARU: HALAMAN RIWAYAT PENCAIRAN ---
    public function riwayatPencairan(Request $request)
    {
        $query = DB::table('riwayat_pencairans')
            ->join('users', 'riwayat_pencairans.user_id', '=', 'users.id')
            ->select('riwayat_pencairans.*', 'users.nama_lengkap', 'users.store_name', 'users.no_wa')
            ->orderBy('riwayat_pencairans.created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('users.nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('users.store_name', 'like', "%{$search}%")
                  ->orWhere('users.no_wa', 'like', "%{$search}%");
            });
        }

        $riwayat = $query->paginate(15)->withQueryString();

        return view('admin.riwayatpencairan', compact('riwayat'));
    }

}
