<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PesananAutokirim;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class KomisiMobileController extends Controller
{
    // ====================================================================
    // BAGIAN 1: API UNTUK AGENT / CUSTOMER (Hanya melihat datanya sendiri)
    // ====================================================================

    public function riwayatAgent(Request $request)
    {
        $userId = Auth::id(); // Mengambil ID user yang sedang login

        // Riwayat Pencairan Agent
        $riwayat = DB::table('riwayat_pencairans')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Kalkulasi Total Pencairan Agent
        $totalDicairkan = DB::table('riwayat_pencairans')->where('user_id', $userId)->sum('nominal');

        // Kalkulasi Total Komisi Keseluruhan
        $excluded_statuses = ['batal', 'gagal', 'waiting_payment', 'menunggu_pembayaran'];
        $totalKomisi = PesananAutokirim::where('user_id', $userId)
            ->whereNotIn('status', $excluded_statuses)
            ->sum('komisi_agen');

        // Sisa Saldo Komisi yang bisa ditarik
        $sisaKomisi = max(0, $totalKomisi - $totalDicairkan);

        return response()->json([
            'success' => true,
            'data' => [
                'riwayat' => $riwayat,
                'total_dicairkan' => $totalDicairkan,
                'sisa_komisi' => $sisaKomisi
            ]
        ]);
    }

    public function tarikKomisiMandiri(Request $request)
    {
        $request->validate([
            'nominal_cair' => 'required|numeric|min:1',
            'idempotency_key' => 'required|string'
        ]);
        $userId = Auth::id();

        $idempotencyKey = 'cust_payout_idemp_' . $request->idempotency_key;
        if (Cache::has($idempotencyKey)) {
            return response()->json(['success' => false, 'message' => 'Permintaan ini sudah diproses sebelumnya.']);
        }

        $lock = Cache::lock('payout_lock_user_' . $userId, 10);
        if (!$lock->get()) {
            return response()->json(['success' => false, 'message' => 'Sistem sedang memproses pencairan Anda.']);
        }

        try {
            DB::beginTransaction();
            $userKey = (new User)->getKeyName();
            $user = User::where($userKey, $userId)->firstOrFail();
            $nominal = $request->nominal_cair;

            $excluded_statuses = ['batal', 'gagal', 'waiting_payment', 'menunggu_pembayaran'];
            $totalKomisi = PesananAutokirim::where('user_id', $userId)->whereNotIn('status', $excluded_statuses)->sum('komisi_agen');
            $totalDicairkan = DB::table('riwayat_pencairans')->where('user_id', $userId)->sum('nominal');
            $sisaKomisi = $totalKomisi - $totalDicairkan;

            if ($nominal > $sisaKomisi) {
                throw new \Exception("Nominal penarikan (Rp " . number_format($nominal, 0, ',', '.') . ") melebihi sisa komisi Anda.");
            }

            // Tambahkan ke saldo dompet User
            $user->saldo = ($user->saldo ?? 0) + $nominal;
            $user->save();

            DB::table('riwayat_pencairans')->insert([
                'user_id' => $userId,
                'nominal' => $nominal,
                'keterangan' => 'Pencairan mandiri komisi ke saldo Via Apps',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Cache::put($idempotencyKey, true, now()->addDay());
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Komisi berhasil ditarik ke Saldo Anda.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        } finally {
            $lock->release();
        }
    }


    // ====================================================================
    // BAGIAN 2: API UNTUK ADMIN (User ID 4) - CRUD & Manajemen Penuh
    // ====================================================================

    public function listAgenAdmin(Request $request)
    {
        if (Auth::id() != 4) return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);

        $userTable = (new User)->getTable();
        $userKey = (new User)->getKeyName();
        $userCol = $userTable . '.' . $userKey;
        $excluded_statuses = ['batal', 'gagal', 'waiting_payment', 'menunggu_pembayaran'];

        $query = User::where('role', 'agent')
            ->select($userTable . '.*')
            ->addSelect(['total_komisi' => PesananAutokirim::selectRaw('COALESCE(sum(komisi_agen), 0)')
                ->whereColumn('user_id', $userCol)->whereNotIn('status', $excluded_statuses)
            ])
            ->addSelect(['total_dicairkan' => DB::table('riwayat_pencairans')
                ->selectRaw('COALESCE(sum(nominal), 0)')->whereColumn('user_id', $userCol)
            ]);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search, $userTable) {
                $q->where($userTable . '.nama_lengkap', 'like', "%{$search}%")
                  ->orWhere($userTable . '.no_wa', 'like', "%{$search}%");
            });
        }

        $agents = $query->paginate(15);

        // Modifikasi data untuk menyertakan sisa komisi
        $agents->getCollection()->transform(function($agent) {
            $agent->sisa_komisi = max(0, $agent->total_komisi - $agent->total_dicairkan);
            return $agent;
        });

        return response()->json(['success' => true, 'data' => $agents]);
    }

    public function updateFee(Request $request)
    {
        if (Auth::id() != 4) return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);

        $request->validate(['ids' => 'required|array', 'fee_percentage' => 'required|numeric|min:1|max:100']);

        $userKey = (new User)->getKeyName();
        User::whereIn($userKey, $request->ids)->update(['fee_autokirim' => $request->fee_percentage]);

        return response()->json(['success' => true, 'message' => count($request->ids) . ' Agen berhasil diupdate komisinya.']);
    }

    public function destroyAgen(Request $request)
    {
        if (Auth::id() != 4) return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);

        $request->validate(['ids' => 'required|array']);

        $userKey = (new User)->getKeyName();
        User::whereIn($userKey, $request->ids)->delete();

        return response()->json(['success' => true, 'message' => count($request->ids) . ' Agen berhasil dihapus secara massal.']);
    }

    public function cairkanKomisiAdmin(Request $request)
    {
        if (Auth::id() != 4) return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);

        $request->validate([
            'user_id' => 'required',
            'nominal_cair' => 'required|numeric|min:1'
        ]);

        DB::beginTransaction();
        try {
            $userKey = (new User)->getKeyName();
            $user = User::where($userKey, $request->user_id)->firstOrFail();

            // Admin bypass validasi saldo (paksa cair)
            $user->saldo = ($user->saldo ?? 0) + $request->nominal_cair;
            $user->save();

            DB::table('riwayat_pencairans')->insert([
                'user_id' => $request->user_id,
                'nominal' => $request->nominal_cair,
                'keterangan' => 'Dicairkan oleh Admin Pusat',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Komisi berhasil dipaksa cair ke agen.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal mencairkan komisi: ' . $e->getMessage()]);
        }
    }
}
