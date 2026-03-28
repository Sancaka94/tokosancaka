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
    /**
     * Menampilkan daftar Escrow (Penahanan Dana)
     */
    public function index(Request $request)
    {
        // Ambil data escrow beserta relasinya biar tidak N+1 query (loading cepat)
        // Kita ambil detail pesanan, toko, penjual (user di dalam toko), dan pembeli (buyer)
        $query = Escrow::with(['order.items.product', 'order.items.variant', 'store.user', 'buyer'])
                       ->orderBy('created_at', 'desc');

        // Filter opsional jika mas mau tambahkan dropdown filter status di blade nanti
        if ($request->has('status') && $request->status != '') {
            $query->where('status_dana', $request->status);
        } else {
            // Default: Tampilkan yang masih 'ditahan' atau 'mediasi' di halaman awal
            $query->whereIn('status_dana', ['ditahan', 'mediasi']);
        }

        $escrows = $query->paginate(15);

        // Lempar data ke view blade yang sudah mas buat sebelumnya
        // Ganti nama variabel di foreach blade mas dari $orders menjadi $escrows
        return view('admin.escrow.index', compact('escrows'));
    }

    /**
     * Proses Pencairan Dana ke Penjual
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

            // 3. Tambah Saldo ke Toko / Penjual
            $seller = $escrow->store->user ?? null;
            if ($seller) {
                // Asumsi di tabel Pengguna mas ada kolom 'saldo'
                // Kita tambahkan nominal_ditahan ke saldo penjual
                $seller->increment('saldo', $escrow->nominal_ditahan);

                Log::info("ESCROW CAIR: Rp " . number_format($escrow->nominal_ditahan) . " ke {$seller->nama_lengkap} (Invoice: {$escrow->invoice_number})");

                // 4. Kirim WA Notifikasi ke Penjual pakai Fonnte
                $this->kirimNotifCair($seller, $escrow);
            }

            // 5. Update status Order jadi 'completed' / selesai (Opsional, sesuaikan alur bisnis mas)
            if ($escrow->order) {
                $escrow->order->status = 'completed';
                $escrow->order->save();
            }

            DB::commit();
            return back()->with('success', 'Dana sebesar Rp ' . number_format($escrow->nominal_ditahan, 0, ',', '.') . ' berhasil dicairkan ke saldo penjual.');

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
}
