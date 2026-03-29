<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Escrow;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\FonnteService; // Untuk kirim WA notifikasi pencairan

class EscrowController extends Controller
{
    public function index(Request $request)
    {
        // 1. AUTO-SYNC: Tarik pesanan yang dibayar/diproses
        $paidOrders = Order::whereIn('status', ['paid', 'processing', 'shipped', 'shipment', 'completed', 'selesai', 'sampai'])->get();
        foreach($paidOrders as $ord) {
            \App\Models\Escrow::firstOrCreate(
                ['order_id' => $ord->id],
                [
                    'invoice_number'  => $ord->invoice_number,
                    'store_id'        => $ord->store_id,
                    'user_id'         => $ord->user_id,
                    'nominal_ditahan' => $ord->total_amount ?? $ord->subtotal,
                    'nominal_ongkir'  => $ord->shipping_cost ?? 0,
                    'status_dana'     => 'ditahan',
                ]
            );
        }

        // 2. QUERY UTAMA UNTUK TABEL
        $query = Escrow::with(['order.items.product', 'order.items.variant', 'store.user', 'buyer']);

        // Filter Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        // Filter Status Order (Dari Tombol)
        if ($request->filled('order_status') && $request->order_status !== 'all') {
            $os = $request->order_status;
            if ($os === 'selesai') {
                $query->whereHas('order', function($q) {
                    $q->whereIn('status', ['completed', 'selesai', 'sampai', 'delivered']);
                });
            } elseif ($os === 'dikirim') {
                $query->whereHas('order', function($q) {
                    $q->whereIn('status', ['shipped', 'shipment']);
                });
            } elseif ($os === 'batal') {
                $query->whereHas('order', function($q) {
                    $q->whereIn('status', ['canceled', 'rejected', 'returned']);
                });
            } elseif ($os === 'bermasalah') {
                $query->where('status_dana', 'mediasi');
            }
        }

        $query->orderBy('created_at', 'desc');
        // Paginasi bawaan Laravel dengan query string agar filter tidak hilang saat pindah halaman
        $escrows = $query->paginate(15)->withQueryString();

        // 3. HITUNG DATA UNTUK 4 CARD METRIC (Berdasarkan filter tanggal jika ada)
        $baseStatsQuery = Escrow::query();
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $baseStatsQuery->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        $countSelesai = (clone $baseStatsQuery)->whereHas('order', function($q) {
            $q->whereIn('status', ['completed', 'selesai', 'sampai', 'delivered']);
        })->count();

        $countDikirim = (clone $baseStatsQuery)->whereHas('order', function($q) {
            $q->whereIn('status', ['shipped', 'shipment']);
        })->count();

        $countBermasalah = (clone $baseStatsQuery)->where('status_dana', 'mediasi')->count();

        $countBatal = (clone $baseStatsQuery)->whereHas('order', function($q) {
            $q->whereIn('status', ['canceled', 'rejected', 'returned']);
        })->count();

        // 🌟 TAMBAHKAN KODE INI UNTUK MENGHITUNG TOTAL REFUND 🌟
        $countRefund = (clone $baseStatsQuery)->where(function($query) {
            // Hitung yang sedang menunggu refund ATAU yang sudah direfund (berdasarkan catatan)
            $query->where('status_dana', 'refund_pending')
                  ->orWhere(function($q) {
                      $q->where('status_dana', 'dicairkan')
                        ->where('catatan', 'LIKE', '%REFUND%');
                  });
        })->count();

        // 🌟 TAMBAHKAN KODE INI UNTUK MENGHITUNG TOTAL RETUR 🌟
        $countRetur = (clone $baseStatsQuery)->whereHas('order', function($q) {
            // Hitung pesanan yang sedang proses retur atau disetujui retur
            $q->whereIn('status', ['returning', 'return_approved']);
        })->count();

        return view('admin.escrow.index', compact(
            'escrows', 'countSelesai', 'countDikirim', 'countBermasalah', 'countBatal' , 'countRefund', 'countRetur'
        ));
    }
    /**
     * Proses Pencairan Dana ke Penjual (Klik dari Admin)
     */
    public function cairkan(Request $request, $id)
    {
        $escrow = Escrow::with(['store.user', 'order'])->findOrFail($id);

        // 1. Validasi cegah double klik
        if ($escrow->status_dana === 'dicairkan') {
            return back()->with('error', 'Dana untuk invoice ini sudah pernah dicairkan sebelumnya.');
        }

        DB::beginTransaction();
        try {
            // 2. Update status Escrow
            $escrow->status_dana = 'dicairkan';
            $escrow->dicairkan_pada = now();
            $escrow->catatan = 'Dicairkan manual oleh Admin Sancaka.';
            $escrow->save();

            // 3. Tambah Saldo ke Toko / Penjual & Catat Riwayat TopUp
            $seller = $escrow->store->user ?? null;
            if ($seller) {
                // 1. Hitung Dana Murni Penjual (Total Transaksi - Ongkir)
                $nominalCair = $escrow->nominal_ditahan - $escrow->nominal_ongkir;

                // A. Tambahkan nominal MURNI ke saldo penjual
                $seller->increment('saldo', $nominalCair);

                // B. Catat mutasi di tabel TopUp
                \App\Models\TopUp::create([
                    'customer_id'    => $seller->id_pengguna,
                    'amount'         => $nominalCair, // <-- Pastikan ini pakai $nominalCair
                    'status'         => 'success',
                    'payment_method' => 'marketplace_revenue',
                    'transaction_id' => 'REV-' . $escrow->invoice_number, // Kunci Idempotency
                    'reference_id'   => $escrow->invoice_number,
                    'created_at'     => now(),
                ]);

                Log::info("ESCROW CAIR: Rp " . number_format($escrow->nominal_ditahan) . " ke {$seller->nama_lengkap} (Invoice: {$escrow->invoice_number})");

                // C. Kirim WA Notifikasi ke Penjual pakai Fonnte
                $this->kirimNotifCair($seller, $escrow);
            }

            // 4. Update status Order jadi 'completed' / selesai
            if ($escrow->order) {
                $escrow->order->status = 'completed';
                $escrow->order->save();
            }

            DB::commit();
            return back()->with('success', 'Dana sebesar Rp ' . number_format($escrow->nominal_ditahan, 0, ',', '.') . ' berhasil dicairkan ke saldo penjual dan tercatat di riwayat.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal cairkan escrow ID {$id}: " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem saat mencairkan dana: ' . $e->getMessage());
        }
    }

    /**
     * Ubah status menjadi Mediasi (Komplain)
     */
    public function mediasi($id)
    {
        $escrow = Escrow::findOrFail($id);

        if ($escrow->status_dana === 'dicairkan') {
            return back()->with('error', 'Tidak bisa mediasi, dana sudah telanjur dicairkan ke penjual.');
        }

        $escrow->status_dana = 'mediasi';
        $escrow->catatan = 'Pesanan masuk dalam tahap mediasi / komplain.';
        $escrow->save();

        if ($escrow->order) {
            $escrow->order->status = 'in_mediation'; // Atau status lain di sistem mas
            $escrow->order->save();
        }

        return back()->with('warning', "Status Escrow {$escrow->invoice_number} diubah menjadi MEDIASI. Dana ditahan permanen sampai masalah selesai.");
    }

    /**
     * Fungsi Private untuk kirim WA ke Penjual saat dana cair
     */
    private function kirimNotifCair($seller, $escrow)
    {
        if (!$seller->no_wa) return;

        $nominal = number_format($escrow->nominal_ditahan, 0, ',', '.');
        $pesan = "*Sancaka Express - PEMBERITAHUAN CAIR*\n\n";
        $pesan .= "Halo *{$seller->nama_lengkap}*,\n";
        $pesan .= "Dana penjualan Anda telah **DICAIRKAN** ke Saldo Akun.\n\n";
        $pesan .= "Rincian:\n";
        $pesan .= "- Invoice: {$escrow->invoice_number}\n";
        $pesan .= "- Nominal: *Rp {$nominal}*\n";
        $pesan .= "- Waktu: " . now()->format('d/m/Y H:i') . "\n\n";
        $pesan .= "Silakan cek menu Saldo / Keuangan Anda di Dashboard Penjual. Terima kasih telah berjualan di Sancaka!";

        try {
            // Sesuai kode Fonnte mas sebelumnya
            $phone = preg_replace('/^0/', '62', $seller->no_wa);
            app(\App\Services\FonnteService::class)->sendMessage($phone, $pesan);
        } catch (\Exception $e) {
            Log::error("Gagal kirim WA Notif Cair Escrow: " . $e->getMessage());
        }
    }

   /**
     * Menampilkan Halaman Riwayat Pencairan Escrow
     */
    public function history(Request $request)
    {
        // 1. Ambil data dengan status dicairkan ATAU yang pesanannya berstatus return
        $query = \App\Models\Escrow::with(['store.user', 'buyer', 'order'])
            ->where(function ($q) {
                $q->where('status_dana', 'dicairkan')
                  ->orWhereHas('order', function ($orderQuery) {
                      $orderQuery->whereIn('status', ['returning', 'return_approved', 'returned']);
                  });
            });

        // 2. Filter Tanggal (Opsional)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('dicairkan_pada', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ])->orWhereBetween('updated_at', [ // Fallback ke updated_at jika belum dicairkan (kasus retur)
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        $query->orderBy('updated_at', 'desc'); // Urutkan berdasarkan waktu terakhir diupdate
        $statsQuery = clone $query;

        // 3. HITUNGAN METRIK (CARDS)
        // A. Total Cair ke Penjual (Normal)
        $totalDanaBersih = (clone $statsQuery)->where('status_dana', 'dicairkan')
                            ->where('catatan', 'NOT LIKE', '%REFUND%')
                            ->sum(\Illuminate\Support\Facades\DB::raw('nominal_ditahan - nominal_ongkir'));
        $totalTransaksi = (clone $statsQuery)->where('status_dana', 'dicairkan')
                            ->where('catatan', 'NOT LIKE', '%REFUND%')
                            ->count();

        // B. Total Refund ke Pembeli
        $totalDanaRefund = (clone $statsQuery)->where('status_dana', 'dicairkan')
                            ->where('catatan', 'LIKE', '%REFUND%')
                            ->sum(\Illuminate\Support\Facades\DB::raw('nominal_ditahan - nominal_ongkir'));

        // C. Total Nilai Barang yang Diretur
        $totalNilaiRetur = (clone $statsQuery)->whereHas('order', function ($orderQuery) {
            $orderQuery->whereIn('status', ['returning', 'return_approved', 'returned']);
        })->sum('nominal_ditahan');

        // D. Jumlah Transaksi Bermasalah (Refund + Retur)
        $totalTransaksiBermasalah = (clone $statsQuery)->where(function($q) {
            $q->where('catatan', 'LIKE', '%REFUND%')
              ->orWhereHas('order', function ($oq) {
                  $oq->whereIn('status', ['returning', 'return_approved', 'returned']);
              });
        })->count();

        $escrows = $query->paginate(20)->withQueryString();

        return view('admin.escrow.history', compact('escrows', 'totalDanaBersih', 'totalTransaksi', 'totalDanaRefund', 'totalNilaiRetur', 'totalTransaksiBermasalah'));
    }

    /**
     * Menarik Riwayat Chat Komplain (AJAX Admin)
     */
    public function getChat($invoice)
    {
        $chats = \App\Models\ComplainChat::with('sender:id_pengguna,nama_lengkap')
                             ->where('invoice_number', $invoice)
                             ->orderBy('created_at', 'asc')
                             ->get();
        return response()->json(['chats' => $chats]);
    }

    /**
     * Admin Mengirim Pesan sebagai Wasit (AJAX)
     */
    public function sendChat(Request $request)
    {
        $request->validate(['invoice_number' => 'required', 'message' => 'required|string']);
        $order = Order::where('invoice_number', $request->invoice_number)->first();

        if(!$order) return response()->json(['error' => 'Pesanan tidak ditemukan'], 404);

        try {
            $chat = \App\Models\ComplainChat::create([
                'order_id'       => $order->id,
                'invoice_number' => $order->invoice_number,
                'sender_id'      => \Illuminate\Support\Facades\Auth::id(), // ID Admin
                'sender_type'    => 'admin',
                'message'        => $request->message,
            ]);

            return response()->json(['success' => true, 'chat' => $chat]);
        } catch (\Exception $e) {
            Log::error("Gagal Kirim Chat Admin: " . $e->getMessage());
            return response()->json(['error' => 'Gagal mengirim pesan'], 500);
        }
    }

    /**
     * Admin Mengeksekusi Pengembalian Dana ke Pembeli (Refund)
     */
    public function refund(Request $request, $id)
    {
        $escrow = \App\Models\Escrow::with(['buyer', 'order'])->findOrFail($id);

        if ($escrow->status_dana !== 'refund_pending') {
            return back()->with('error', 'Status belum disetujui penjual untuk refund.');
        }

        DB::beginTransaction();
        try {
            // Hitung dana yang dikembalikan (Hanya harga barang, ongkir hangus)
            $danaRefund = $escrow->nominal_ditahan - $escrow->nominal_ongkir;

            // Kembalikan dana ke saldo pembeli
            $buyer = $escrow->buyer;
            if ($buyer) {
                $buyer->increment('saldo', $danaRefund);

                // Catat mutasi TopUp untuk pembeli sebagai Refund
                \App\Models\TopUp::create([
                    'customer_id'    => $buyer->id_pengguna,
                    'amount'         => $danaRefund,
                    'status'         => 'success',
                    'payment_method' => 'refund_marketplace',
                    'transaction_id' => 'REF-' . $escrow->invoice_number,
                    'reference_id'   => $escrow->invoice_number,
                    'created_at'     => now(),
                ]);
            }

            // Ubah status Order jadi 'returned' (Dikembalikan)
            if ($escrow->order) {
                $escrow->order->update(['status' => 'returned']);
            }

            // Update status Escrow
            $escrow->update([
                'status_dana'    => 'dicairkan', // Kita set dicairkan agar masuk ke riwayat
                'dicairkan_pada' => now(),
                'catatan'        => 'REFUND KE PEMBELI: Rp ' . number_format($danaRefund, 0, ',', '.')
            ]);

            DB::commit();
            return back()->with('success', 'Berhasil! Dana sebesar Rp ' . number_format($danaRefund, 0, ',', '.') . ' telah dikembalikan ke Saldo Pembeli.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error("Refund Error: " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem saat memproses refund.');
        }
    }
}
