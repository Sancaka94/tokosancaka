<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Escrow;
use App\Models\TopUp;
use App\Models\ComplainChat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PesananActionController extends Controller
{
    /**
     * 1. Fungsi Terima Paket & Cairkan Dana Otomatis
     */
    public function terimaPaket($id)
    {
        $order = Order::findOrFail($id);

        // Validasi: pastikan belum selesai
        if (in_array(strtolower($order->status), ['completed', 'selesai', 'delivered'])) {
            return back()->with('error', 'Pesanan ini sudah diselesaikan sebelumnya.');
        }

        DB::beginTransaction();
        try {
            // A. Update Status Pesanan menjadi Completed
            $order->status = 'completed';
            $order->finished_at = now();
            $order->save();

            // B. Cari Escrow yang menahan dana ini
            $escrow = Escrow::with('store.user')->where('order_id', $order->id)->first();

            // C. Jika dana masih ditahan / mediasi, CAIRKAN SEKARANG!
            if ($escrow && in_array($escrow->status_dana, ['ditahan', 'mediasi'])) {

                $danaPenjual = $escrow->nominal_ditahan - $escrow->nominal_ongkir;

                // Ubah status Escrow
                $escrow->status_dana = 'dicairkan';
                $escrow->dicairkan_pada = now();
                $escrow->catatan = 'Dicairkan OTOMATIS (Pembeli klik Terima Paket)';
                $escrow->save();

                // Tambah Saldo Penjual
                $seller = $escrow->store->user ?? null;
                if ($seller) {
                    $seller->increment('saldo', $danaPenjual);

                    // Catat di riwayat TopUp
                    TopUp::create([
                        'customer_id'    => $seller->id_pengguna,
                        'amount'         => $danaPenjual,
                        'status'         => 'success',
                        'payment_method' => 'marketplace_revenue',
                        'transaction_id' => 'REV-AUTO-' . $escrow->invoice_number,
                        'reference_id'   => $escrow->invoice_number,
                        'created_at'     => now(),
                    ]);
                }
            }

            DB::commit();
            return back()->with('success', 'Terima kasih! Paket telah dikonfirmasi dan dana diteruskan ke Penjual.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal Terima Paket Customer: " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem saat memproses penerimaan.');
        }
    }

    /**
     * 2. Menarik Riwayat Chat Komplain (AJAX)
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
     * 3. Mengirim Pesan Chat Komplain Baru (AJAX)
     */
    public function sendChat(Request $request)
    {
        $request->validate([
            'invoice_number' => 'required',
            'message'        => 'required|string'
        ]);

        $order = Order::where('invoice_number', $request->invoice_number)->first();
        if(!$order) return response()->json(['error' => 'Pesanan tidak ditemukan'], 404);

        DB::beginTransaction();
        try {
            // Simpan Pesan ke Database
            $chat = ComplainChat::create([
                'order_id'       => $order->id,
                'invoice_number' => $order->invoice_number,
                'sender_id'      => Auth::user()->id_pengguna,
                'sender_type'    => 'customer',
                'message'        => $request->message,
            ]);

            // Jika ini pesan pertama, Otomatis ubah status Escrow jadi 'mediasi'
            // Jika ini pesan pertama, Otomatis ubah status Escrow jadi 'mediasi'
            $escrow = Escrow::where('order_id', $order->id)->first();
            if($escrow && $escrow->status_dana === 'ditahan') {
                $escrow->status_dana = 'mediasi';
                $escrow->catatan = 'Dibekukan otomatis karena pembeli mengajukan komplain.';
                $escrow->save();

                // ----------------------------------------------------
                // 🔥 KIRIM NOTIFIKASI WA REALTIME KE PENJUAL 🔥
                // ----------------------------------------------------
                $seller = $order->store->user ?? null;
                if ($seller && $seller->no_wa) {
                    $pesan = "*⚠ PERHATIAN: KOMPLAIN PESANAN ⚠*\n\n";
                    $pesan .= "Halo *{$seller->nama_lengkap}*,\n";
                    $pesan .= "Pembeli mengajukan komplain untuk pesanan:\n";
                    $pesan .= "- Invoice: *{$order->invoice_number}*\n";
                    $pesan .= "- Keluhan: _{$request->message}_\n\n";
                    $pesan .= "Dana saat ini *dibekukan sementara* di Escrow Sancaka. Silakan login ke Dashboard Penjual dan buka menu Pusat Resolusi untuk merespon.";

                    try {
                        $phone = preg_replace('/^0/', '62', $seller->no_wa);
                        app(\App\Services\FonnteService::class)->sendMessage($phone, $pesan);
                    } catch (\Exception $e) {
                        Log::error("Gagal kirim WA komplain ke Penjual: " . $e->getMessage());
                    }
                }
            }

            DB::commit();

            // Load data pengirim agar bisa ditampilkan di frontend
            $chat->load('sender:id_pengguna,nama_lengkap');

            return response()->json(['success' => true, 'chat' => $chat]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal Kirim Chat Komplain: " . $e->getMessage());
            return response()->json(['error' => 'Gagal mengirim pesan'], 500);
        }
    }
}
