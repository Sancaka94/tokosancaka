<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product; // Tambahkan ini untuk update stok
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse; // Untuk Export CSV

class ReportController extends Controller
{
    public function index(Request $request)
    {
        // 1. Filter Tanggal (Default: Bulan Ini)
        $fromDate = $request->from_date ?? now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? now()->endOfMonth()->format('Y-m-d');

        // 2. Query Dasar
        $query = Order::query()
            ->with(['details.product']) // Eager load
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate);

        // Filter status spesifik (Opsional jika ada filter status di view)
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // 3. Clone query untuk statistik (Agar tidak merusak query pagination)
        $ordersForStats = (clone $query)->get(); 

        $totalOmzet = $ordersForStats->where('payment_status', 'paid')->sum('final_price');
        $totalPesanan = $ordersForStats->count();
        $piutang = $ordersForStats->where('payment_status', 'unpaid')->sum('final_price');

        // 4. Hitung Total Profit (Hanya yang status Paid & Tidak Cancelled)
        $totalProfit = 0;
        foreach ($ordersForStats as $o) {
            if ($o->payment_status == 'paid' && $o->status != 'cancelled') {
                $totalProfit += $o->profit; // Memanggil Accessor di Model Order
            }
        }

        // 5. Data Tabel (Pagination)
        $orders = $query->latest()->paginate(10)->withQueryString();

        return view('reports.index', compact('orders', 'fromDate', 'toDate', 'totalOmzet', 'totalPesanan', 'piutang', 'totalProfit'));
    }

    /**
     * FITUR TAMBAHAN: EXPORT KE CSV (Tanpa Library Berat)
     */
    public function export(Request $request)
    {
        $fromDate = $request->from_date ?? now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? now()->endOfMonth()->format('Y-m-d');

        $orders = Order::with('details')
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->get();

        $csvFileName = 'Laporan_Transaksi_' . $fromDate . '_sd_' . $toDate . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$csvFileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');
            
            // Header CSV
            fputcsv($file, ['No Invoice', 'Tanggal', 'Pelanggan', 'Status', 'Pembayaran', 'Total Belanja', 'Profit']);

            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->order_number ?? $order->invoice_number,
                    $order->created_at->format('Y-m-d H:i'),
                    $order->customer_name,
                    $order->status,
                    $order->payment_status,
                    $order->final_price,
                    $order->profit // Accessor profit
                ]);
            }
            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    public function show($id)
    {
        $order = Order::with(['details', 'attachments'])->findOrFail($id);
        return view('reports.show', compact('order'));
    }

    public function edit($id)
    {
        $order = Order::findOrFail($id);
        return view('reports.edit', compact('order'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled',
            'payment_status' => 'required|in:paid,unpaid',
            'note' => 'nullable|string'
        ]);

        $order = Order::with('details')->findOrFail($id);
        $oldStatus = $order->status;

        DB::beginTransaction();
        try {
            // LOGIKA PENGEMBALIAN STOK (RESTOCK)
            // Jika status berubah JADI 'cancelled' DAN sebelumnya BUKAN 'cancelled'
            if ($request->status == 'cancelled' && $oldStatus != 'cancelled') {
                foreach ($order->details as $detail) {
                    Product::where('id', $detail->product_id)
                        ->increment('stock', $detail->quantity);
                }
            }
            
            // Opsional: Jika status berubah DARI 'cancelled' KE 'processing' (Stok ditarik lagi)
            if ($oldStatus == 'cancelled' && $request->status != 'cancelled') {
                foreach ($order->details as $detail) {
                    $prod = Product::find($detail->product_id);
                    if ($prod->stock < $detail->quantity) {
                         throw new \Exception("Stok produk {$prod->name} tidak cukup untuk mengaktifkan kembali pesanan ini.");
                    }
                    $prod->decrement('stock', $detail->quantity);
                }
            }

            $order->update([
                'status' => $request->status,
                'payment_status' => $request->payment_status,
                'note' => $request->note
            ]);

            DB::commit();
            return redirect()->route('reports.index')->with('success', 'Data pesanan berhasil diperbarui!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $order = Order::with(['attachments', 'details'])->findOrFail($id);

        DB::beginTransaction();
        try {
            // 1. KEMBALIKAN STOK BARANG (Jika order belum dibatalkan sebelumnya)
            if ($order->status != 'cancelled') {
                foreach ($order->details as $detail) {
                    Product::where('id', $detail->product_id)
                        ->increment('stock', $detail->quantity);
                }
            }

            // 2. Hapus File Fisik
            foreach ($order->attachments as $file) {
                if (Storage::disk('public')->exists($file->file_path)) {
                    Storage::disk('public')->delete($file->file_path);
                }
            }

            // 3. Hapus Data (Cascade delete detail & attachment)
            $order->details()->delete();
            $order->attachments()->delete();
            $order->delete();

            DB::commit();
            return redirect()->route('reports.index')->with('success', 'Pesanan dihapus dan stok barang telah dikembalikan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('reports.index')->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }
}