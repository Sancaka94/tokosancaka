<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PpobTransaction;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf; // Pastikan ini di-import
use Maatwebsite\Excel\Facades\Excel; // Pastikan ini di-import
use App\Exports\PpobTransactionsExport; // Jika pakai class export terpisah (opsional)
use Carbon\Carbon;

class PpobHistoryController extends Controller
{
    // Function index() sama seperti sebelumnya (tidak ada perubahan)
    public function index(Request $request)
    {
        $query = PpobTransaction::where('user_id', Auth::id());

        // 1. Filter Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_id', 'like', "%$search%")
                  ->orWhere('customer_no', 'like', "%$search%")
                  ->orWhere('buyer_sku_code', 'like', "%$search%");
            });
        }

        // 2. Filter Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 3. Filter Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00', 
                $request->end_date . ' 23:59:59'
            ]);
        }

        // 4. Sorting & Pagination
        $transactions = $query->latest()->paginate(10)->withQueryString();

        return view('customer.ppob.history', compact('transactions'));
    }

    // Export Excel (CSV Format - Ringan)
    public function exportExcel(Request $request)
    {
        $fileName = 'transaksi-ppob-' . date('Y-m-d-His') . '.csv';
        // Ambil semua data (tanpa pagination)
        $transactions = PpobTransaction::where('user_id', Auth::id())->latest()->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Order ID', 'Produk', 'No Pelanggan', 'Harga Jual', 'Status', 'SN/Token/Pesan', 'Tanggal Transaksi'];

        $callback = function() use($transactions, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($transactions as $trx) {
                fputcsv($file, [
                    $trx->order_id,
                    strtoupper($trx->buyer_sku_code),
                    "'" . $trx->customer_no, // Tambah tanda kutip agar tidak dianggap angka oleh Excel
                    $trx->selling_price,
                    $trx->status,
                    $trx->sn ?? $trx->message,
                    $trx->created_at->format('Y-m-d H:i:s')
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    
    // Export PDF (Menggunakan DomPDF)
    public function exportPdf(Request $request)
    {
        // Batasi 200 transaksi terakhir agar tidak terlalu berat saat generate PDF
        $transactions = PpobTransaction::where('user_id', Auth::id())->latest()->limit(200)->get();
        
        if (class_exists(Pdf::class)) {
            // Pastikan view 'customer.ppob.pdf_export' dibuat
            $pdf = Pdf::loadView('customer.ppob.pdf_export', compact('transactions'));
            $pdf->setPaper('a4', 'landscape'); // Atur kertas landscape agar muat banyak kolom
            return $pdf->download('laporan-transaksi-ppob-' . date('Y-m-d') . '.pdf');
        } 
        
        return back()->with('error', 'Library PDF (dompdf) belum terinstall. Hubungi admin.');
    }
}