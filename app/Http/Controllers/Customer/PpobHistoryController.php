<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PpobTransaction;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf; // Pastikan package dompdf terinstall
use Carbon\Carbon;

class PpobHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = PpobTransaction::where('user_id', Auth::id());

        // 1. Filter Search (Order ID / No Pelanggan / SKU)
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

    // Export Excel (CSV Format agar ringan tanpa library berat)
    public function exportExcel(Request $request)
    {
        $fileName = 'transaksi-ppob-' . date('Y-m-d') . '.csv';
        $transactions = PpobTransaction::where('user_id', Auth::id())->latest()->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Order ID', 'Produk', 'No Pelanggan', 'Harga', 'Status', 'SN/Token', 'Tanggal'];

        $callback = function() use($transactions, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($transactions as $trx) {
                fputcsv($file, [
                    $trx->order_id,
                    $trx->buyer_sku_code,
                    $trx->customer_no,
                    $trx->selling_price,
                    $trx->status,
                    $trx->sn,
                    $trx->created_at
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    
    // Export PDF (Menggunakan DomPDF atau View Print sederhana)
    public function exportPdf(Request $request)
    {
        $transactions = PpobTransaction::where('user_id', Auth::id())->latest()->limit(100)->get();
        
        // Jika menggunakan barryvdh/laravel-dompdf
        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('customer.ppob.pdf_export', compact('transactions'));
            return $pdf->download('transaksi-ppob.pdf');
        } 
        
        // Fallback jika belum install library PDF
        return back()->with('error', 'Library PDF belum terinstall.');
    }
}