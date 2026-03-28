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
                    $q->whereIn('status', ['canceled', 'rejected']);
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
            $q->whereIn('status', ['canceled', 'rejected']);
        })->count();

        return view('admin.escrow.index', compact(
            'escrows', 'countSelesai', 'countDikirim', 'countBermasalah', 'countBatal'
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
        // Ambil hanya data yang statusnya sudah 'dicairkan'
        $query = Escrow::with(['store.user', 'order'])->where('status_dana', 'dicairkan');

        // Filter berdasarkan tanggal pencairan (bukan tanggal order)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('dicairkan_pada', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        $query->orderBy('dicairkan_pada', 'desc');

        // Hitung total metrik untuk Card di atas tabel
        $statsQuery = clone $query;
        // Hitung total dana bersih yang sudah ditransfer ke penjual (Total - Ongkir)
        $totalDanaBersih = $statsQuery->sum(DB::raw('nominal_ditahan - nominal_ongkir'));
        $totalTransaksi = $statsQuery->count();

        $escrows = $query->paginate(20)->withQueryString();

        return view('admin.escrow.history', compact('escrows', 'totalDanaBersih', 'totalTransaksi'));
    }
}
