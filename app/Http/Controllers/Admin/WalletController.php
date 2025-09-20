<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function index()
    {
        $pengguna = User::orderBy('nama_lengkap', 'asc')->paginate(15);
        return view('admin.wallet.index', compact('pengguna'));
    }

    /**
     * Menangani pencarian pelanggan via AJAX untuk dropdown Select2.
     * ✅ FIX: Menyesuaikan nama kolom dengan database (id_pengguna, no_wa, saldo).
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
                               ->orWhere('id_pengguna', 'like', '%' . $searchTerm . '%') // Menggunakan id_pengguna
                               ->orWhere('no_wa', 'like', '%' . $searchTerm . '%');      // Menggunakan no_wa
                     })
                     // ✅ FIX: Menggunakan alias 'id' dan 'balance' agar tidak perlu mengubah view/JS
                     ->select('id_pengguna as id', 'nama_lengkap', 'email', 'saldo as balance', 'no_wa') 
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
     * Menangani pencarian live untuk tabel utama.
     * ✅ FIX: Menyesuaikan nama kolom dengan database (id_pengguna, no_wa).
     */
    public function liveSearch(Request $request)
    {
        $search = $request->get('search');
        $query = User::query(); 

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('nama_lengkap', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('id_pengguna', 'like', '%' . $search . '%') // Menggunakan id_pengguna
                  ->orWhere('no_wa', 'like', '%' . $search . '%');      // Menggunakan no_wa
            });
        }

        // ✅ FIX: Mengganti nama kolom `balance` menjadi `saldo`
        $users = $query->select('id_pengguna as id', 'nama_lengkap', 'email', 'saldo as balance')->orderBy('nama_lengkap', 'asc')->get();
        
        return response()->json($users);
    }

    /**
     * Memproses permintaan penambahan saldo (top up).
     * ✅ FIX: Menyesuaikan nama kolom dan aturan validasi.
     */
    public function topup(Request $request)
    {
        $validated = $request->validate([
            // Memvalidasi ke tabel 'Pengguna' dengan kolom 'id_pengguna'
            'user_id' => 'required|exists:Pengguna,id_pengguna',
            'amount' => 'required|numeric|min:1000',
        ]);

        $pelanggan = User::find($validated['user_id']);

        try {
            DB::transaction(function () use ($pelanggan, $validated) {
                // ✅ FIX: Menggunakan increment pada kolom 'saldo'
                $pelanggan->increment('saldo', $validated['amount']);
            });
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal melakukan top up. Terjadi kesalahan teknis.');
        }

        $formattedAmount = number_format($validated['amount'], 0, ',', '.');
        
        return redirect()->route('admin.wallet.index')
                         ->with('success', "Berhasil menambahkan saldo Rp {$formattedAmount} ke akun {$pelanggan->nama_lengkap}.");
    }
}

