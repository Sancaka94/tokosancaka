<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        // 1. Filter Tanggal
        $fromDate = $request->from_date ?? now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? now()->endOfMonth()->format('Y-m-d');

        // 2. Query Dasar
        $query = Order::query()
            ->with(['details.product']) // Eager load untuk performa
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate);

        // 3. Clone query untuk hitungan ringkasan
        $ordersForStats = (clone $query)->get(); // Ambil semua data sesuai filter

        $totalOmzet = $ordersForStats->where('payment_status', 'paid')->sum('final_price');
        $totalPesanan = $ordersForStats->count();
        $piutang = $ordersForStats->where('payment_status', 'unpaid')->sum('final_price');

        // 4. Hitung Total Profit (Looping data yang sudah diambil)
        $totalProfit = 0;
        foreach ($ordersForStats as $o) {
            // Hanya hitung profit jika status LUNAS (opsional, tergantung kebijakan toko)
            if ($o->payment_status == 'paid') {
                $totalProfit += $o->profit; // Memanggil accessor getProfitAttribute yg dibuat di Model
            }
        }

        // 5. Data Tabel (Pagination)
        $orders = $query->latest()->paginate(10)->withQueryString();

        return view('reports.index', compact('orders', 'fromDate', 'toDate', 'totalOmzet', 'totalPesanan', 'piutang', 'totalProfit'));
    }

    /**
     * 2. READ (SHOW) - Detail Pesanan & File
     */
    public function show($id)
    {
        // Ambil order beserta detail item dan file lampiran
        $order = Order::with(['details', 'attachments'])->findOrFail($id);
        
        return view('reports.show', compact('order'));
    }

    /**
     * 3. UPDATE (EDIT) - Tampilkan Form Edit
     */
    public function edit($id)
    {
        $order = Order::findOrFail($id);
        return view('reports.edit', compact('order'));
    }

    /**
     * 3. UPDATE (STORE) - Simpan Perubahan Status
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled',
            'payment_status' => 'required|in:paid,unpaid',
            'note' => 'nullable|string'
        ]);

        $order = Order::findOrFail($id);
        
        $order->update([
            'status' => $request->status,
            'payment_status' => $request->payment_status,
            'note' => $request->note
        ]);

        return redirect()->route('reports.index')->with('success', 'Data pesanan berhasil diperbarui!');
    }

    /**
     * 4. DELETE (DESTROY) - Hapus Pesanan & File Fisik
     */
    public function destroy($id)
    {
        $order = Order::with('attachments')->findOrFail($id);

        // A. Hapus File Fisik di Storage
        foreach ($order->attachments as $file) {
            if (Storage::disk('public')->exists($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
            }
        }

        // B. Hapus Data di Database (Cascade delete akan menghapus details & attachments jika disetting di migration, 
        // tapi manual delete lebih aman jika foreign key constraint tidak strict)
        $order->details()->delete();
        $order->attachments()->delete();
        $order->delete();

        return redirect()->route('reports.index')->with('success', 'Pesanan dan file terkait berhasil dihapus.');
    }
}