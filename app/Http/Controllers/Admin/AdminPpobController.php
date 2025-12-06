<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PpobTransaction;
use Illuminate\Support\Facades\DB;
use App\Models\PpobProduct; // ⭐ Wajib di-import
use Barryvdh\DomPDF\Facade\Pdf; // Pastikan package dompdf sudah diinstall

class AdminPpobController extends Controller
{
    /**
     * Menampilkan data transaksi dengan filter & search.
     */
   // Admin/PpobController.php
public function index(Request $request)
{
    $type = $request->input('type', 'prepaid'); // Ambil type, default ke prepaid
    $query = $request->input('q');

    $products = PpobProduct::query();

    // Lakukan filtering berdasarkan tipe
    if ($type === 'prepaid') {
        $products->where('category', '!=', 'Pascabayar');
    } else { // 'postpaid'
        $products->where('category', 'Pascabayar');
    }
    
    // Lakukan filtering pencarian
    if ($query) {
        $products->where(function ($q) use ($query) {
            $q->where('product_name', 'like', '%' . $query . '%')
              ->orWhere('buyer_sku_code', 'like', '%' . $query . '%')
              ->orWhere('brand', 'like', '%' . $query . '%');
        });
    }

    $products = $products->paginate(20);

    return view('admin.ppob.index', compact('products'));
}

    /**
     * Export data ke Excel (Format CSV)
     * Menggunakan Stream Download agar hemat memori server.
     */
    public function exportExcel(Request $request)
    {
        $fileName = 'transaksi_ppob_' . date('Y-m-d_H-i') . '.csv';
        
        // Ambil semua data (bukan paginate)
        $transactions = $this->getFilteredQuery($request)->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'Waktu', 'Order ID', 'User', 'Produk', 'Tujuan', 'Harga Beli', 'Harga Jual', 'Profit', 'Status', 'SN / Pesan'];

        $callback = function() use ($transactions, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($transactions as $trx) {
                // Gunakan helper get_ppob_message jika ada RC
                $statusMessage = $trx->sn ? "SN: " . $trx->sn : (function_exists('get_ppob_message') && $trx->rc ? get_ppob_message($trx->rc) : $trx->message);

                $row = [
                    $trx->id,
                    $trx->created_at->format('Y-m-d H:i:s'),
                    $trx->order_id,
                    $trx->user->name ?? 'User Terhapus',
                    $trx->product_name . ' (' . $trx->buyer_sku_code . ')',
                    $trx->customer_no,
                    $trx->price, // Harga Beli (Modal)
                    $trx->selling_price, // Harga Jual
                    $trx->profit,
                    $trx->status,
                    $statusMessage
                ];

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export data ke PDF
     * Membutuhkan view khusus untuk cetak.
     */
    public function exportPdf(Request $request)
    {
        // Ambil semua data
        $transactions = $this->getFilteredQuery($request)->get();

        // Hitung total untuk ringkasan di PDF
        $totalOmset = $transactions->sum('selling_price');
        $totalProfit = $transactions->sum('profit');

        // Load View PDF (Kita akan buat view ini di langkah ke-2)
        $pdf = Pdf::loadView('admin.ppob.data.pdf', compact('transactions', 'totalOmset', 'totalProfit'));
        
        // Setup Kertas Landscape agar muat banyak kolom
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('laporan-transaksi-ppob-' . date('d-m-Y') . '.pdf');
    }

    /**
     * PRIVATE FUNCTION: Logika Filter Utama
     * Dipisahkan agar tidak menulis ulang kode yang sama di index, excel, dan pdf.
     */
    private function getFilteredQuery(Request $request)
    {
        $query = PpobTransaction::with('user')->latest(); 

        // 1. SEARCH
        if ($request->has('search') && $request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhere('customer_no', 'like', "%{$search}%")
                  ->orWhere('sn', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQ) use ($search) {
                      $userQ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // 2. FILTER STATUS
        if ($request->has('status') && $request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 3. FILTER TANGGAL
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = $request->start_date . ' 00:00:00';
            $endDate   = $request->end_date . ' 23:59:59';
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return $query;
    }
}