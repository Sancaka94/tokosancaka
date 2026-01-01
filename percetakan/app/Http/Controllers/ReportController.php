<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        // Filter Tanggal (Default: Bulan Ini)
        $fromDate = $request->get('from_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->get('to_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // Ambil Data Berdasarkan Range Tanggal
        $query = Order::whereDate('created_at', '>=', $fromDate)
                      ->whereDate('created_at', '<=', $toDate);

        // Hitung Ringkasan
        $totalOmzet = (clone $query)->where('payment_status', 'paid')->sum('final_price');
        $totalPesanan = (clone $query)->count();
        $piutang = (clone $query)->where('payment_status', 'unpaid')->sum('final_price');

        // Ambil Daftar Transaksi
        $orders = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('reports.index', compact('orders', 'totalOmzet', 'totalPesanan', 'piutang', 'fromDate', 'toDate'));
    }
}