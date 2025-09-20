<?php

namespace App\Http\Controllers;

use App\Models\User; // Pastikan model Anda bernama 'User' atau 'Pengguna'
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    /**
     * Menampilkan halaman utama wallet dengan fitur pencarian.
     */
    public function index(Request $request)
    {
        // Memulai query pada model User, filter hanya untuk pelanggan
        $query = User::where('role', '!=', 'admin');

        // Menerapkan fitur pencarian untuk tabel utama
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                // Mencari berdasarkan nama_lengkap ATAU email
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Mengurutkan berdasarkan nama_lengkap dan melakukan paginasi
        $pelanggan = $query->orderBy('nama_lengkap', 'asc')->paginate(20);

        return view('admin.wallet.index', compact('pelanggan'));
    }

    /**
     * Menangani pencarian pelanggan via AJAX untuk dropdown Select2.
     * Ini membuat form top up sangat cepat dan efisien.
     */
    public function search(Request $request)
    {
        $searchTerm = $request->input('q');
        
        if (!$searchTerm) {
            return response()->json([]);
        }

        $users = User::where('role', '!=', 'admin')
                    ->where(function ($query) use ($searchTerm) {
                        $query->where('nama_lengkap', 'like', '%' . $searchTerm . '%')
                              ->orWhere('email', 'like', '%' . $searchTerm . '%');
                    })
                    ->select('id', 'nama_lengkap', 'email', 'balance')
                    ->limit(15) // Batasi hasil pencarian untuk performa
                    ->get();

        return response()->json($users);
    }

    /**
     * Memproses permintaan penambahan saldo (top up).
     */
    public function topup(Request $request)
    {
        // Validasi input dari form
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1000',
        ]);

        $pelanggan = User::find($validated['user_id']);

        try {
            // Menggunakan transaksi database untuk memastikan keamanan data
            DB::transaction(function () use ($pelanggan, $validated) {
                $pelanggan->increment('balance', $validated['amount']);
            });
        } catch (\Exception $e) {
            // Menangani jika terjadi error saat update database
            return back()->with('error', 'Gagal melakukan top up. Terjadi kesalahan teknis.');
        }

        $formattedAmount = number_format($validated['amount'], 0, ',', '.');
        
        // Menggunakan nama_lengkap pada pesan sukses
        return redirect()->route('wallet.index')
                         ->with('success', "Berhasil menambahkan saldo Rp {$formattedAmount} ke akun {$pelanggan->nama_lengkap}.");
    }
}

