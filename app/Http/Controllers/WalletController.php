<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    /**
     * Menampilkan halaman utama wallet yang berisi daftar semua pelanggan.
     * Admin dapat melihat saldo dan melakukan top up dari halaman ini.
     */
    public function index()
    {
        // Mengambil semua pengguna yang bukan admin, diurutkan berdasarkan nama
        // Anda bisa menyesuaikan query ini, misalnya: ->where('role', 'pelanggan')
        $pelanggan = User::where('role', '!=', 'admin')
                         ->orderBy('name', 'asc')
                         ->paginate(20); // Menggunakan paginate untuk data yang banyak

        return view('admin.wallet.index', [
            'pelanggan' => $pelanggan
        ]);
    }

    /**
     * Memproses permintaan penambahan saldo (top up) untuk pelanggan.
     */
    public function topup(Request $request)
    {
        // 1. Validasi input dari form
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1000', // Minimal top up 1,000
        ]);

        // 2. Cari pengguna berdasarkan ID
        $pelanggan = User::find($validated['user_id']);

        if (!$pelanggan) {
            return back()->with('error', 'Pelanggan tidak ditemukan.');
        }

        // 3. Lakukan penambahan saldo dalam database transaction
        //    Ini memastikan jika ada error, prosesnya akan dibatalkan (rollback)
        try {
            DB::transaction(function () use ($pelanggan, $validated) {
                // Gunakan increment untuk operasi yang aman dari race condition
                $pelanggan->increment('balance', $validated['amount']);

                // (Opsional) Anda bisa mencatat transaksi ini ke tabel lain
                // Transaction::create([
                //     'user_id' => $pelanggan->id,
                //     'amount' => $validated['amount'],
                //     'type' => 'topup',
                //     'description' => 'Top up saldo oleh admin.'
                // ]);
            });

        } catch (\Exception $e) {
            // Jika terjadi error selama transaksi
            return back()->with('error', 'Gagal melakukan top up. Terjadi kesalahan: ' . $e->getMessage());
        }

        // 4. Kembalikan ke halaman index dengan pesan sukses
        $formattedAmount = number_format($validated['amount'], 0, ',', '.');
        return redirect()->route('wallet.index')
                         ->with('success', "Berhasil menambahkan saldo sebesar Rp {$formattedAmount} ke akun {$pelanggan->name}.");
    }
}
