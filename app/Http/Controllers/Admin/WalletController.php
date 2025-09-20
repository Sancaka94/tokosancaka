<?php

// ✅ PERBAIKAN: Menyesuaikan namespace dengan lokasi file.
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WalletController extends Controller
{
    /**
     * Menampilkan halaman utama wallet dengan fitur pencarian.
     */
    public function index(Request $request)
    {
        // ✅ PERBAIKAN: Memfilter agar hanya menampilkan pelanggan di tabel utama.
        $query = User::where('role', 'pelanggan');

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
        $pelanggan = $query->orderBy('nama_lengkap', 'asc')->paginate(10);

        return view('admin.wallet.index', compact('pelanggan'));
    }

    /**
     * Menangani pencarian pelanggan via AJAX untuk dropdown Select2.
     */
    public function search(Request $request)
    {
        // ✅ PERBAIKAN: Menggunakan 'term' sesuai dengan parameter yang dikirim oleh Select2 dari view.
        $searchTerm = $request->input('term');
        
        if (!$searchTerm) {
            return response()->json(['results' => []]);
        }

        $users = User::where('role', 'pelanggan') // Filter hanya untuk pelanggan
                     ->where('status', 'Aktif')
                     ->where(function ($query) use ($searchTerm) {
                         $query->where('nama_lengkap', 'like', '%' . $searchTerm . '%')
                               ->orWhere('email', 'like', '%' . $searchTerm . '%');
                     })
                     ->select('id', 'nama_lengkap', 'email', 'balance')
                     ->limit(15) // Batasi hasil pencarian untuk performa
                     ->get();
        
        // ✅ PERBAIKAN: Memformat data agar sesuai dengan yang diharapkan oleh 'processResults' di Select2.
        $formattedUsers = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'text' => $user->nama_lengkap . ' (Email: ' . $user->email . ')',
                'item' => $user // Menyimpan data asli untuk templating
            ];
        });

        return response()->json(['results' => $formattedUsers]);
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
        
        // ✅ PERBAIKAN: Mengarahkan kembali ke route admin yang benar.
        return redirect()->route('admin.wallet.index')
                         ->with('success', "Berhasil menambahkan saldo Rp {$formattedAmount} ke akun {$pelanggan->nama_lengkap}.");
    }
}

