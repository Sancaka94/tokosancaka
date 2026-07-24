<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PesananAutokirim;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class KomisiMobileController extends Controller
{
    // ==========================================
    // API UNTUK ADMIN (Melihat Semua Data)
    // ==========================================
    public function riwayatAdmin(Request $request)
    {
        $userTable = (new User)->getTable();
        $userKey = (new User)->getKeyName();

        $query = DB::table('riwayat_pencairans')
            ->join($userTable, 'riwayat_pencairans.user_id', '=', $userTable . '.' . $userKey)
            ->select('riwayat_pencairans.*', $userTable . '.nama_lengkap', $userTable . '.store_name', $userTable . '.no_wa')
            ->orderBy('riwayat_pencairans.created_at', 'desc');

        // Fitur Pencarian untuk Admin
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search, $userTable) {
                $q->where($userTable . '.nama_lengkap', 'like', "%{$search}%")
                  ->orWhere($userTable . '.store_name', 'like', "%{$search}%")
                  ->orWhere($userTable . '.no_wa', 'like', "%{$search}%");
            });
        }

        $riwayat = $query->paginate(15);
        $totalDicairkanSistem = DB::table('riwayat_pencairans')->sum('nominal');

        return response()->json([
            'success' => true,
            'data' => [
                'riwayat' => $riwayat,
                'total_dicairkan_sistem' => $totalDicairkanSistem
            ]
        ]);
    }

    // ==========================================
    // API UNTUK AGENT (Hanya Melihat Datanya Sendiri)
    // ==========================================
    public function riwayatAgent(Request $request)
    {
        $userId = Auth::id(); // Hanya mengambil ID user yang sedang login

        // Riwayat Pencairan Agent
        $riwayat = DB::table('riwayat_pencairans')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Kalkulasi Total Pencairan Agent
        $totalDicairkan = DB::table('riwayat_pencairans')
            ->where('user_id', $userId)
            ->sum('nominal');

        // Kalkulasi Total Komisi Keseluruhan
        $excluded_statuses = ['batal', 'gagal', 'waiting_payment', 'menunggu_pembayaran'];
        $totalKomisi = PesananAutokirim::where('user_id', $userId)
            ->whereNotIn('status', $excluded_statuses)
            ->sum('komisi_agen');

        // Sisa Saldo Komisi yang bisa ditarik
        $sisaKomisi = $totalKomisi - $totalDicairkan;
        if ($sisaKomisi < 0) {
            $sisaKomisi = 0;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'riwayat' => $riwayat,
                'total_dicairkan' => $totalDicairkan,
                'sisa_komisi' => $sisaKomisi
            ]
        ]);
    }

    // ==========================================
    // API UNTUK ADMIN: LIST AGEN & STATISTIK
    // ==========================================
    public function listAgenAdmin(Request $request)
    {
        // Pastikan hanya admin (misal ID 4) yang bisa akses
        if (Auth::id() != 4) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak. Khusus Admin.'], 403);
        }

        $userTable = (new \App\Models\User)->getTable();
        $userKey = (new \App\Models\User)->getKeyName();
        $userCol = $userTable . '.' . $userKey;
        $excluded_statuses = ['batal', 'gagal', 'waiting_payment', 'menunggu_pembayaran'];

        $query = \App\Models\User::where('role', 'agent')
            ->select($userTable . '.*')
            ->addSelect(['total_komisi' => \App\Models\PesananAutokirim::selectRaw('COALESCE(sum(komisi_agen), 0)')
                ->whereColumn('user_id', $userCol)->whereNotIn('status', $excluded_statuses)
            ])
            ->addSelect(['total_dicairkan' => \Illuminate\Support\Facades\DB::table('riwayat_pencairans')
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

    // ==========================================
    // API UNTUK ADMIN: CRUD & BULK ACTIONS
    // ==========================================
    public function updateFee(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'fee_percentage' => 'required|numeric|min:1|max:100']);
        \App\Models\User::whereIn('id_pengguna', $request->ids)->update(['fee_autokirim' => $request->fee_percentage]);
        return response()->json(['success' => true, 'message' => count($request->ids) . ' Agen berhasil diupdate komisinya.']);
    }

    public function destroyAgen(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        \App\Models\User::whereIn('id_pengguna', $request->ids)->delete();
        return response()->json(['success' => true, 'message' => count($request->ids) . ' Agen berhasil dihapus.']);
    }

    public function cairkanKomisiAdmin(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'nominal_cair' => 'required|numeric|min:1'
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $userKey = (new \App\Models\User)->getKeyName();
            $user = \App\Models\User::where($userKey, $request->user_id)->firstOrFail();

            // Bypass validasi sisa saldo jika admin yang memaksa cairkan, atau Anda bisa tambahkan validasi seperti di web
            $user->saldo = ($user->saldo ?? 0) + $request->nominal_cair;
            $user->save();

            \Illuminate\Support\Facades\DB::table('riwayat_pencairans')->insert([
                'user_id' => $request->user_id,
                'nominal' => $request->nominal_cair,
                'keterangan' => 'Dicairkan oleh Admin',
                'created_at' => now(), 'updated_at' => now(),
            ]);

            \Illuminate\Support\Facades\DB::commit();
            return response()->json(['success' => true, 'message' => 'Komisi berhasil dicairkan ke agen.']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal mencairkan komisi: ' . $e->getMessage()]);
        }
    }
}
