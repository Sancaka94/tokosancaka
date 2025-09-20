<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    /**
     * Menampilkan halaman utama wallet.
     * ✅ PERBAIKAN: Mengganti nama variabel agar lebih jelas.
     */
    public function index()
    {
        // ✅ FIX: Menyesuaikan nama variabel dari '$users' menjadi '$pengguna' agar cocok dengan view.
        $pengguna = User::orderBy('nama_lengkap', 'asc')->paginate(15);
        return view('admin.wallet.index', compact('pengguna'));
    }

    /**
     * Menangani pencarian pelanggan via AJAX untuk dropdown Select2.
     * ✅ PERBAIKAN: Menambahkan pencarian berdasarkan ID dan No. HP.
     */
    public function search(Request $request)
    {
        $searchTerm = $request->input('term');
        if (!$searchTerm) {
            return response()->json(['results' => []]);
        }

        $users = User::where('role', 'pelanggan')
                     ->where('status', 'Aktif')
                     ->where(function ($query) use ($searchTerm) {
                         $query->where('nama_lengkap', 'like', '%' . $searchTerm . '%')
                               ->orWhere('email', 'like', '%' . $searchTerm . '%')
                               ->orWhere('id', 'like', '%' . $searchTerm . '%')
                               ->orWhere('no_hp', 'like', '%' . $searchTerm . '%');
                     })
                     ->select('id', 'nama_lengkap', 'email', 'balance', 'no_hp')
                     ->limit(15)
                     ->get();
        
        $formattedUsers = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'text' => $user->nama_lengkap . ' (' . $user->email . ')',
                'item' => $user
            ];
        });

        return response()->json(['results' => $formattedUsers]);
    }
    
    /**
     * ✅ BARU: Menangani pencarian live untuk tabel utama.
     * Fungsi ini akan membuat tabel merespons secara instan saat admin mengetik.
     */
    public function liveSearch(Request $request)
    {
        $search = $request->get('search');
        $query = User::query(); // Mulai query untuk semua user

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('nama_lengkap', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('id', 'like', '%' . $search . '%')
                  ->orWhere('no_hp', 'like', '%' . $search . '%');
            });
        }

        $users = $query->orderBy('nama_lengkap', 'asc')->get();
        
        // Mengembalikan data dalam format JSON untuk diproses oleh JavaScript
        return response()->json($users);
    }

    /**
     * Memproses permintaan penambahan saldo (top up).
     */
    public function topup(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1000',
        ]);

        $pelanggan = User::find($validated['user_id']);

        try {
            DB::transaction(function () use ($pelanggan, $validated) {
                $pelanggan->increment('balance', $validated['amount']);
            });
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal melakukan top up. Terjadi kesalahan teknis.');
        }

        $formattedAmount = number_format($validated['amount'], 0, ',', '.');
        
        return redirect()->route('admin.wallet.index')
                         ->with('success', "Berhasil menambahkan saldo Rp {$formattedAmount} ke akun {$pelanggan->nama_lengkap}.");
    }
}

