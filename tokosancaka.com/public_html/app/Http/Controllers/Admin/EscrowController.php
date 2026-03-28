<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EscrowController extends Controller
{
    /**
     * Tampilkan halaman daftar Escrow
     */
    public function index()
    {
        // Tampilkan order yang sudah LUNAS tapi dananya BELUM DICAIRKAN ke penjual
        $orders = Order::with(['store.user', 'user', 'items.product', 'items.variant'])
            ->whereIn('status', ['paid', 'processing', 'shipped', 'delivered', 'sampai'])
            ->where('is_fund_disbursed', false) // Hanya yang dananya masih ditahan admin
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.escrow.index', compact('orders'));
    }

    /**
     * Proses pencairan dana ke penjual
     */
    public function cairkan(Request $request, $id)
    {
        $order = Order::with('store.user')->findOrFail($id);

        // Validasi ganda biar admin nggak double klik
        if ($order->is_fund_disbursed) {
            return back()->with('error', 'Dana untuk pesanan ini sudah pernah dicairkan.');
        }

        DB::beginTransaction();
        try {
            // 1. Ubah status penanda dana
            $order->is_fund_disbursed = true;
            $order->status = 'completed'; // Atau biarkan statusnya jika pakai status lain
            $order->save();

            // 2. Tambahkan Saldo ke Toko Penjual
            $store = $order->store;
            if ($store) {
                // Asumsi mas punya kolom 'saldo' di tabel stores atau users penjual
                // Jika pakai DOKU SAC, di sinilah proses API Transfer DOKU dari Master ke SAC dipanggil

                // Contoh tambah saldo internal sistem:
                // $store->increment('saldo', $order->subtotal);

                // Catat mutasi/log keuangan jika mas punya tabel mutasi
                Log::info("Escrow Cair: Dana Rp " . number_format($order->subtotal) . " dicairkan ke toko {$store->name} (Order: {$order->invoice_number})");
            }

            DB::commit();

            // 3. Kirim Notifikasi ke Penjual (Opsional tapi disarankan)
            if ($store && $store->user) {
                $pesan = "Dana sebesar Rp " . number_format($order->subtotal) . " dari pesanan {$order->invoice_number} telah diteruskan ke saldo Anda.";
                $store->user->notify(new \App\Notifications\NotifikasiUmum([
                    'tipe' => 'Pencairan Dana',
                    'judul' => 'Dana Masuk!',
                    'pesan_utama' => $pesan,
                    'url' => url('seller/keuangan'), // Sesuaikan url seller
                    'icon' => 'fas fa-wallet',
                ]));
            }

            return back()->with('success', 'Dana berhasil dicairkan ke penjual!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal cairkan escrow: " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem saat mencairkan dana.');
        }
    }

    /**
     * Tampilkan halaman / proses Mediasi (Komplain)
     */
    public function mediasi($id)
    {
        $order = Order::findOrFail($id);

        // Ubah status order menjadi mediasi/komplain
        $order->status = 'in_mediation';
        $order->save();

        // Redirect ke halaman khusus mediasi (Misal ada chat antara admin, pembeli, penjual)
        // Sementara kita lempar notif sukses dulu
        return back()->with('info', "Pesanan {$order->invoice_number} masuk dalam status Mediasi/Komplain. Dana ditahan secara permanen sampai masalah selesai.");
    }
}
