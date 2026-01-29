<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Tampilkan halaman dashboard utama dengan statistik.
     */
    public function index()
    {
        // 1. STATISTIK KARTU MONITOR

        // Total Omzet dari pesanan yang sudah dibayar (Paid)
        $totalOmzet = Order::where('payment_status', 'paid')->sum('final_price');

        // Total Produk yang terdaftar
        $totalProduk = Product::count();

        // Total Item Terjual (Quantity dari Order Details)
        $totalTerjual = OrderDetail::sum('quantity');

        // Total Pelanggan Unik (Berdasarkan Nama Pelanggan di tabel Order)
        $totalPelanggan = Order::distinct('customer_name')->count();

        // Jumlah User/Staff yang bisa login ke sistem
        $totalUser = User::count();

        // Ambil Saldo Merchant DANA (Disbursement Account)
        // Diambil dari kolom dana_merchant_balance di tabel affiliates untuk ID Master (11)
        $merchantBalance = DB::table('affiliates')
                            ->where('id', 11)
                            ->value('dana_merchant_balance') ?? 0;

        // 2. DATA UNTUK GRAFIK (OPSIONAL - 7 Hari Terakhir)

        $salesData = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(final_price) as total')
            )
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();


        // 3. DATA RECENT ACTIVITY (Aktivitas Terbaru)

        // 5 Pesanan terbaru
        $recentOrders = Order::orderBy('created_at', 'desc')->take(5)->get();

        // 5 Produk terbaru yang ditambahkan
        $newProducts = Product::orderBy('id', 'desc')->take(5)->get();


        // 4. KIRIM DATA KE VIEW
        return view('dashboard', [
            'totalOmzet'      => $totalOmzet,
            'totalProduk'     => $totalProduk,
            'totalTerjual'    => $totalTerjual,
            'totalPelanggan'  => $totalPelanggan,
            'totalUser'       => $totalUser,
            'merchantBalance' => $merchantBalance, // <--- Data Saldo Merchant Baru
            'salesData'       => $salesData,
            'recentOrders'    => $recentOrders,
            'newProducts'     => $newProducts,
            'hariIni'         => Carbon::now()->translatedFormat('d F Y')
        ]);
    }
}
