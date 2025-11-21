<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Transaction; // 1. Tambahkan referensi ke model Transaction
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // 2. Tambahkan DB facade untuk keamanan transaksi

class WalletController extends Controller
{
    /**
     * Menampilkan halaman manajemen wallet.
     */
    public function index(Request $request)
    {
        // Mengambil semua pengguna untuk ditampilkan di tabel awal
        $pengguna = User::orderBy('nama_lengkap', 'asc')->paginate(20);
        return view('admin.wallet.index', compact('pengguna'));
    }

    /**
     * Menangani pencarian pengguna via AJAX untuk Select2 dan live search tabel.
     */
    public function search(Request $request)
    {
        // Menggabungkan input dari Select2 ('term') dan live search ('search')
        $term = $request->input('term', '');
        $query = $request->input('search', '');
        $searchTerm = $term ?: $query;

        if (!$searchTerm) {
            return response()->json(['results' => []]);
        }

        // Mencari pengguna berdasarkan berbagai kriteria
        $users = User::where(function ($q) use ($searchTerm) {
            $q->where('id_pengguna', 'like', "%{$searchTerm}%")
              ->orWhere('nama_lengkap', 'like', "%{$searchTerm}%")
              ->orWhere('email', 'like', "%{$searchTerm}%")
              ->orWhere('no_wa', 'like', "%{$searchTerm}%");
        })
        // Menggunakan alias agar konsisten dengan JS di view
        ->select('id_pengguna as id', 'nama_lengkap', 'email', 'saldo as balance', 'no_wa')
        ->limit(15)
        ->get();

        // Mengirim format yang berbeda tergantung pemanggil (Select2 atau Live Search)
        if ($request->has('term')) {
            $formatted_users = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'text' => "{$user->nama_lengkap} (ID: {$user->id})",
                    'item' => $user
                ];
            });
            return response()->json(['results' => $formatted_users]);
        }
        
        return response()->json($users);
    }

    /**
     * Memproses permintaan perubahan saldo (top up atau pengurangan).
     */
    public function topup(Request $request)
    {
        // Validasi input dari form
        $validated = $request->validate([
            'user_id' => 'required|exists:Pengguna,id_pengguna',
            'amount' => 'required|numeric|min:1',
            'action' => 'required|in:add,subtract' // Memvalidasi aksi yang diizinkan
        ]);

        $pelanggan = User::find($validated['user_id']);
        $amount = (float) $validated['amount'];

        // Mencegah saldo menjadi negatif
        if ($validated['action'] === 'subtract' && $pelanggan->saldo < $amount) {
            return back()->with('error', 'Gagal mengurangi. Saldo pengguna tidak mencukupi.');
        }

        try {
            // 3. Menggunakan transaksi database untuk memastikan kedua operasi (update saldo dan insert transaksi) berhasil atau gagal bersamaan
            DB::transaction(function () use ($pelanggan, $amount, $validated) {
                $description = '';
                $type = 'topup';

                if ($validated['action'] === 'add') {
                    $pelanggan->increment('saldo', $amount);
                    $description = 'Top up saldo oleh Admin';
                    $type = 'topup';
                } else { // 'subtract'
                    $pelanggan->decrement('saldo', $amount);
                    $description = 'Pengurangan saldo oleh Admin';
                    $type = 'withdrawal';
                }

                // 4. Membuat catatan di tabel 'transactions' menggunakan model
                Transaction::create([
                    'user_id'     => $pelanggan->id_pengguna,
                    'type'        => $type,
                    'amount'      => $amount,
                    'description' => $description,
                ]);
            });

        } catch (\Exception $e) {
            // Menangani jika terjadi error selama transaksi database
            return back()->with('error', 'Gagal memproses saldo. Terjadi kesalahan teknis.');
        }

        // Menyiapkan pesan sukses yang dinamis
        $actionText = $validated['action'] === 'add' ? 'menambahkan' : 'mengurangi';
        $formattedAmount = number_format($amount, 0, ',', '.');
        
        return redirect()->route('admin.wallet.index')
                         ->with('success', "Berhasil {$actionText} saldo Rp {$formattedAmount} pada akun {$pelanggan->nama_lengkap}.");
    }
}

