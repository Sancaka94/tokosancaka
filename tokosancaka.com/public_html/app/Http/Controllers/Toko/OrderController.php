<?php

namespace App\Http\Controllers\Toko;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Store; // <-- PENTING: Import model Store
use App\Models\ComplainChat; // <-- PENTING: Import model Chat
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Menampilkan daftar pesanan marketplace (dari model Order).
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Anda harus login.');
        }

        // --- PERBAIKAN LOGIKA DIMULAI DI SINI ---

        // 1. Dapatkan Toko (Store) yang terkait dengan User yang login
        //    (Kita asumsikan relasinya user_id di tabel stores ke id_pengguna di tabel Pengguna)
        $store = Store::where('user_id', $user->id_pengguna)->first();

        // 2. Jika user ini tidak punya toko (atau belum setup), kembalikan view kosong
        if (!$store) {
            // Buat koleksi kosong agar view tidak error
            $orders = collect();
            // Kirim $store (yang isinya null) dan $orders (yang kosong)
            return view('seller.pesanan.index', compact('orders', 'store'));
        }

        // 3. Gunakan ID Toko (misal: 13) BUKAN ID User (misal: 8)
        $storeId = $store->id;

        // --- AKHIR PERBAIKAN LOGIKA ---

        $search = $request->input('search');

        // Ambil data pesanan (Order) untuk toko ini
        $query = Order::where('store_id', $storeId)
             ->with([
                 'user', // Relasi ke pelanggan (customer)
                 'items', // Relasi ke item pesanan
                 'items.product', // Relasi ke produk dari setiap item
                 'store', // <-- TAMBAHKAN INI (Relasi ke Toko/Store)
                 'store.user' // <-- TAMBAHKAN INI (Untuk ambil No WA Toko)
             ])
                        ->latest();

        // Terapkan filter pencarian jika ada
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', '%' . $search . '%')
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('nama_lengkap', 'like', '%' . $search . '%');
                  });
            });
        }

        $orders = $query->paginate(10)->appends($request->query());

        // HAPUS BARIS INI: $store = $user;
        // Variabel $store sudah benar berisi data toko dari langkah 1.

        return view('seller.pesanan.index', compact('orders', 'store'));
    }

    /**
     * Menarik Riwayat Chat Komplain (AJAX Seller)
     */
    public function getChat($invoice)
    {
        $chats = ComplainChat::with('sender:id_pengguna,nama_lengkap')
                             ->where('invoice_number', $invoice)
                             ->orderBy('created_at', 'asc')
                             ->get();

        return response()->json(['chats' => $chats]);
    }

    /**
     * Penjual Mengirim Balasan Komplain (AJAX)
     */
    public function sendChat(Request $request)
    {
        $request->validate([
            'invoice_number' => 'required',
            'message' => 'required|string'
        ]);

        $order = Order::where('invoice_number', $request->invoice_number)->first();

        if(!$order) {
            return response()->json(['error' => 'Pesanan tidak ditemukan'], 404);
        }

        try {
            // Simpan chat ke database
            $chat = ComplainChat::create([
                'order_id'       => $order->id,
                'invoice_number' => $order->invoice_number,
                'sender_id'      => Auth::user()->id_pengguna, // ID Penjual yang sedang login
                'sender_type'    => 'seller',
                'message'        => $request->message,
            ]);

            // Load data pengirim agar nama_lengkap bisa ditampilkan langsung di frontend tanpa refresh
            $chat->load('sender:id_pengguna,nama_lengkap');

            return response()->json(['success' => true, 'chat' => $chat]);

        } catch (\Exception $e) {
            Log::error("Gagal Kirim Chat Seller: " . $e->getMessage());
            return response()->json(['error' => 'Gagal mengirim pesan'], 500);
        }
    }

    /**
     * Aksi Penjual: Menyetujui Retur Barang
     */
    public function approveRetur($invoice)
    {
        $order = Order::where('invoice_number', $invoice)->firstOrFail();

        // Ubah status order (opsional, sesuaikan dengan nama status di database mas)
        $order->status = 'return_approved';
        $order->save();

        // Kirim Chat Otomatis
        ComplainChat::create([
            'order_id'       => $order->id,
            'invoice_number' => $order->invoice_number,
            'sender_id'      => Auth::user()->id_pengguna,
            'sender_type'    => 'seller',
            'message'        => '✅ PENJUAL MENYETUJUI RETUR BARANG. Silakan pembeli mempersiapkan paket. Sistem akan segera memproses resi retur via KiriminAja.',
        ]);

        // (NANTI MAS BISA TAMBAHKAN LOGIKA API KIRIMINAJA DI SINI UNTUK GENERATE RESI RETUR)

        return back()->with('success', 'Persetujuan retur berhasil dikirim. Menunggu resi balasan dari pembeli.');
    }

    /**
     * Aksi Penjual: Setujui Pengembalian Dana (Refund)
     */
    public function approveRefund($invoice)
    {
        $order = Order::where('invoice_number', $invoice)->firstOrFail();

        // Cari Dana Escrow yang sedang ditahan
        $escrow = \App\Models\Escrow::where('order_id', $order->id)->first();
        if($escrow) {
            // Ubah status jadi refund_pending agar Admin tahu harus mengembalikan dana
            $escrow->status_dana = 'refund_pending';
            $escrow->catatan = 'Penjual menyetujui Pengembalian Dana. Menunggu Admin Sancaka memproses refund ke Pembeli (Hanya Harga Barang).';
            $escrow->save();
        }

        // Kirim Chat Otomatis
        ComplainChat::create([
            'order_id'       => $order->id,
            'invoice_number' => $order->invoice_number,
            'sender_id'      => Auth::user()->id_pengguna,
            'sender_type'    => 'seller',
            'message'        => '✅ PENJUAL MENYETUJUI PENGEMBALIAN DANA. Menunggu Admin Sancaka memproses pencairan dana kembali ke saldo Pembeli (Tidak termasuk Ongkir).',
        ]);

        return back()->with('success', 'Persetujuan pengembalian dana berhasil dikirim ke Admin.');
    }
}
