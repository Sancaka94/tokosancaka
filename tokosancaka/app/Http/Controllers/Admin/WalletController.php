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
    // 1. Sesuaikan validasi dengan nama tabel yang benar (biasanya lowercase di production)
    // Jika tetap error, gunakan nama koneksi atau pastikan nama tabel sesuai database
    $validated = $request->validate([
        'user_id' => 'required|exists:Pengguna,id_pengguna', // Pastikan P besar/kecil sesuai database
        'amount'  => 'required|numeric|min:1',
        'action'  => 'required|in:add,subtract'
    ]);

    // 2. Gunakan findOrFail agar jika data tidak ditemukan langsung melempar error yang jelas
    try {
        $pelanggan = User::findOrFail($validated['user_id']);
        $amount = (float) $validated['amount'];

        if ($validated['action'] === 'subtract' && $pelanggan->saldo < $amount) {
            return back()->with('error', 'Gagal mengurangi. Saldo pengguna tidak mencukupi.');
        }

        DB::transaction(function () use ($pelanggan, $amount, $validated) {
            $type = ($validated['action'] === 'add') ? 'topup' : 'withdrawal';
            $description = ($validated['action'] === 'add') ? 'Top up saldo oleh Admin' : 'Pengurangan saldo oleh Admin';

            // Mengupdate saldo
            if ($validated['action'] === 'add') {
                $pelanggan->increment('saldo', $amount);
            } else {
                $pelanggan->decrement('saldo', $amount);
            }

            // SIMPAN TRANSAKSI
            // Pastikan tabel 'transactions' sudah ada di database tokq3391_db
            Transaction::create([
                'user_id'     => $pelanggan->id_pengguna,
                'type'        => $type,
                'amount'      => $amount,
                'description' => $description,
                'reference_id' => 'ADM-' . time() . '-' . $pelanggan->id_pengguna, // Menambahkan nilai reference_id
            ]);
        });

        return redirect()->route('admin.wallet.index')
                         ->with('success', "Berhasil memperbarui saldo {$pelanggan->nama_lengkap}.");

    } catch (\Exception $e) {
        // Log error asli agar Anda bisa cek di storage/logs/laravel.log
        \Log::error("Wallet Error: " . $e->getMessage());
        
        // Tampilkan pesan error spesifik sementara untuk debugging
        return back()->with('error', 'Gagal: ' . $e->getMessage());
    }
}
}

