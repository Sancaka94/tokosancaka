<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant; // <--- WAJIB TAMBAH
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    protected $tenantId;

    public function __construct(Request $request)
    {
        // Deteksi Subdomain untuk mengunci data laporan
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];
        $tenant = Tenant::where('subdomain', $subdomain)->first();

        $this->tenantId = $tenant ? $tenant->id : 1;
    }

    public function index(Request $request)
    {
        $fromDate = $request->from_date ?? now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? now()->endOfMonth()->format('Y-m-d');

        // 1. KUNCI QUERY DASAR DENGAN tenant_id
        $query = Order::where('tenant_id', $this->tenantId) // <--- KUNCI DATA
            ->with(['details.product'])
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 2. Statistik Terfilter
        $ordersForStats = (clone $query)->get();

        $totalOmzet = $ordersForStats->where('payment_status', 'paid')->sum('final_price');
        $totalPesanan = $ordersForStats->count();
        $piutang = $ordersForStats->where('payment_status', 'unpaid')->sum('final_price');

        $totalProfit = 0;
        foreach ($ordersForStats as $o) {
            if ($o->payment_status == 'paid' && $o->status != 'cancelled') {
                $totalProfit += $o->profit;
            }
        }

        $orders = $query->latest()->paginate(10)->withQueryString();

        return view('reports.index', compact('orders', 'fromDate', 'toDate', 'totalOmzet', 'totalPesanan', 'piutang', 'totalProfit'));
    }

    public function export(Request $request)
    {
        $fromDate = $request->from_date ?? now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? now()->endOfMonth()->format('Y-m-d');

        // 3. KUNCI EXPORT CSV AGAR TIDAK BOCOR DATA TOKO LAIN
        $orders = Order::where('tenant_id', $this->tenantId) // <--- KUNCI DATA
            ->with('details')
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->get();

        $csvFileName = 'Laporan_Transaksi_' . $fromDate . '_sd_' . $toDate . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$csvFileName",
        ];

        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['No Invoice', 'Tanggal', 'Pelanggan', 'Status', 'Pembayaran', 'Total Belanja', 'Profit']);
            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->order_number,
                    $order->created_at->format('Y-m-d H:i'),
                    $order->customer_name,
                    $order->status,
                    $order->payment_status,
                    $order->final_price,
                    $order->profit
                ]);
            }
            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    public function show($id)
    {
        // 4. KEAMANAN URL: Cegah akses detail nota milik tenant lain
        $order = Order::where('tenant_id', $this->tenantId)
                      ->with(['details', 'attachments'])
                      ->findOrFail($id);

        return view('reports.show', compact('order'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled',
            'payment_status' => 'required|in:paid,unpaid'
        ]);

        // 5. KEAMANAN UPDATE: Hanya boleh update nota milik sendiri
        $order = Order::where('tenant_id', $this->tenantId)->with('details')->findOrFail($id);
        $oldStatus = $order->status;

        DB::beginTransaction();
        try {
            if ($request->status == 'cancelled' && $oldStatus != 'cancelled') {
                foreach ($order->details as $detail) {
                    // Pastikan stok yang dikembalikan adalah stok produk di toko yang benar
                    Product::where('id', $detail->product_id)
                           ->where('tenant_id', $this->tenantId)
                           ->increment('stock', $detail->quantity);
                }
            }

            $order->update([
                'status' => $request->status,
                'payment_status' => $request->payment_status,
                'note' => $request->note
            ]);

            DB::commit();
            return redirect()->route('reports.index')->with('success', 'Berhasil update!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        // 6. KEAMANAN DELETE
        $order = Order::where('tenant_id', $this->tenantId)->findOrFail($id);

        DB::beginTransaction();
        try {
            if ($order->status != 'cancelled') {
                foreach ($order->details as $detail) {
                    Product::where('id', $detail->product_id)
                           ->where('tenant_id', $this->tenantId)
                           ->increment('stock', $detail->quantity);
                }
            }

            $order->details()->delete();
            $order->delete();

            DB::commit();
            return redirect()->route('reports.index')->with('success', 'Dihapus!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('reports.index')->with('error', 'Gagal hapus.');
        }
    }
}
