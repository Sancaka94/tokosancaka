<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AgentFee;
use App\Models\PesananAutokirim;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KomisiAgentController extends Controller
{
    public function index(Request $request)
    {
        // Ambil semua user dengan role agen beserta relasi custom fee & pesanannya
        $query = User::where('role', 'agent')->with('agentFee');

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
        $totalPencairan = PesananAutokirim::whereNotIn('status', ['batal', 'gagal', 'menunggu_pembayaran'])->sum('komisi_agen');
        $totalLabaSancaka = PesananAutokirim::whereNotIn('status', ['batal', 'gagal', 'menunggu_pembayaran'])->sum('laba_sistem');

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
            DB::beginTransaction();

            $fee = AgentFee::updateOrCreate(
                ['user_id' => $id],
                ['fee_percentage' => $request->fee_percentage]
            );

            Log::info("LOG LOG: [UPDATE FEE AGENT] Admin mengubah fee agen ID {$id} menjadi {$request->fee_percentage}%");

            DB::commit();
            return redirect()->back()->with('success', 'Persentase Fee Agen berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memperbarui fee: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            // Hapus custom fee (akan kembali ke default 40% di perhitungan)
            AgentFee::where('user_id', $id)->delete();

            Log::info("LOG LOG: [RESET FEE AGENT] Admin mereset fee agen ID {$id} ke default (40%)");

            return redirect()->back()->with('success', 'Fee agen berhasil direset ke default sistem (40%).');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mereset fee agen.');
        }
    }
}
