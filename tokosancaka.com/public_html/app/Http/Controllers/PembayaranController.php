<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Order; // Sesuaikan dengan nama Model transaksi/order Anda
use Illuminate\Support\Facades\Log;

class PembayaranController extends Controller
{
    // LOG LOG - Inisialisasi Controller Pembayaran Publik

    /**
     * Menampilkan halaman pencarian tagihan dan hasil datanya.
     */
    public function index(Request $request)
    {
        $akun = $request->input('akun');

        // Jika form kosong (baru buka halaman), tampilkan view awal
        if (!$akun) {
            return view('pembayaran.index');
        }

        // Cari user berdasarkan No WA atau Email
        $user = User::where('no_wa', $akun)
                    ->orWhere('email', $akun)
                    ->first();

        // Jika user ditemukan, ambil daftar tagihannya
        if ($user) {
            // Mengambil order yang statusnya pending/unpaid dan via GATEWAY
            $invoices = Order::where('user_id', $user->id)
                             ->whereIn('status', ['pending', 'unpaid'])
                             ->where('payment_method', 'GATEWAY')
                             ->orderBy('created_at', 'desc')
                             ->get();

            // Kembalikan ke view dengan membawa data user dan invoices
            return view('pembayaran.index', compact('user', 'invoices'));
        }

        // Jika user tidak ditemukan, kembali ke view (Blade akan memunculkan alert gagal)
        return view('pembayaran.index');
    }

    /**
     * Memproses klik tombol "Bayar Sekarang"
     */
    public function proses($invoice_number)
    {
        // Cari data order berdasarkan nomor invoice
        $order = Order::where('invoice_number', $invoice_number)->first();

        // Validasi 1: Apakah order ditemukan?
        if (!$order) {
            return redirect()->route('pembayaran.index')
                             ->with('error', 'Tagihan tidak ditemukan.');
        }

        // Validasi 2: Apakah status masih bisa dibayar?
        if (!in_array(strtolower($order->status), ['pending', 'unpaid'])) {
            return redirect()->route('pembayaran.index')
                             ->with('error', 'Tagihan ini sudah dibayar atau dibatalkan.');
        }

        // Eksekusi: Redirect ke URL Payment Gateway
        // Asumsi: Saat order dibuat di HP, backend Anda sudah request ke Tripay/DOKU
        // dan menyimpan URL pembayarannya di kolom 'payment_url' pada tabel orders.
        if (!empty($order->payment_url)) {
            // Menggunakan redirect()->away() karena URL mengarah ke luar web Anda (pihak ke-3)
            return redirect()->away($order->payment_url);
        }

        // Jika karena suatu hal payment_url kosong, munculkan pesan error
        Log::error('Payment URL kosong untuk Invoice: ' . $invoice_number); // LOG LOG
        return redirect()->route('pembayaran.index')
                         ->with('error', 'Link pembayaran belum tersedia. Silakan hubungi Admin Sancaka.');
    }
}
