<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $tenantId;

    public function __construct(Request $request)
    {
        // Deteksi Subdomain untuk mengunci data dashboard
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];
        $tenant = Tenant::where('subdomain', $subdomain)->first();

        $this->tenantId = $tenant ? $tenant->id : 1;
    }

    public function index(Request $request)
    {
        // 1. TANGKAP INPUT FILTER
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = $request->input('search');
        $shippingStatus = $request->input('shipping_status');

        // 2. QUERY DASAR (Base Query)
        $orderQuery = Order::where('tenant_id', $this->tenantId);

        // Apply Filter Tanggal
        if ($startDate && $endDate) {
            $orderQuery->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }

        // Apply Filter Pencarian (No Order, Resi, Booking Ref)
        if ($search) {
            $orderQuery->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('resi_number', 'like', "%{$search}%")   // Sesuaikan nama kolom DB Anda
                  ->orWhere('booking_ref', 'like', "%{$search}%");  // Sesuaikan nama kolom DB Anda
            });
        }

        // Apply Filter Status Pengiriman
        if ($shippingStatus) {
            $orderQuery->where('shipping_status', $shippingStatus);
        }

        // 3. STATISTIK KARTU MONITOR
        $totalOmzet = (clone $orderQuery)->where('payment_status', 'paid')->sum('final_price');
        $totalProduk = Product::where('tenant_id', $this->tenantId)->count();
        $totalTerjual = OrderDetail::where('tenant_id', $this->tenantId)->sum('quantity');
        $totalPelanggan = (clone $orderQuery)->distinct('customer_name')->count('customer_name');
        $totalUser = User::where('tenant_id', $this->tenantId)->count();

        $merchantBalance = DB::table('affiliates')->where('id', 11)->value('dana_merchant_balance') ?? 0;

        // 4. KARTU STATUS PENGIRIMAN (KIRIMINAJA)
        $countSelesai = (clone $orderQuery)->where('shipping_status', 'Selesai Terkirim')->count();
        $countBelumDikirim = (clone $orderQuery)->where('shipping_status', 'Belum Dikirim')->count();
        $countPickup = (clone $orderQuery)->where('shipping_status', 'Menunggu Pickup')->count();
        $countGagal = (clone $orderQuery)->whereIn('shipping_status', ['Gagal', 'Cancel'])->count();

        // 5. DATA GRAFIK PERBANDINGAN BULAN (1-31)
        $now = Carbon::now();
        $daysInMonth = 31;

        // Setup Array Kosong
        $dataBulanIni = array_fill(1, 31, 0);
        $dataBulanLalu = array_fill(1, 31, 0);
        $dataTigaBulanLalu = array_fill(1, 31, 0);

        // Query Bulan Ini (Merah)
        $tmData = Order::where('tenant_id', $this->tenantId)
            ->whereYear('created_at', $now->year)->whereMonth('created_at', $now->month)
            ->where('payment_status', 'paid')
            ->select(DB::raw('DAY(created_at) as day'), DB::raw('SUM(final_price) as total'))
            ->groupBy('day')->pluck('total', 'day');
        foreach($tmData as $day => $total) $dataBulanIni[$day] = $total;

        // Query Bulan Lalu (Hijau)
        $lm = $now->copy()->subMonth();
        $lmData = Order::where('tenant_id', $this->tenantId)
            ->whereYear('created_at', $lm->year)->whereMonth('created_at', $lm->month)
            ->where('payment_status', 'paid')
            ->select(DB::raw('DAY(created_at) as day'), DB::raw('SUM(final_price) as total'))
            ->groupBy('day')->pluck('total', 'day');
        foreach($lmData as $day => $total) $dataBulanLalu[$day] = $total;

        // Query 3 Bulan Lalu (Biru)
        $tma = $now->copy()->subMonths(3);
        $tmaData = Order::where('tenant_id', $this->tenantId)
            ->whereYear('created_at', $tma->year)->whereMonth('created_at', $tma->month)
            ->where('payment_status', 'paid')
            ->select(DB::raw('DAY(created_at) as day'), DB::raw('SUM(final_price) as total'))
            ->groupBy('day')->pluck('total', 'day');
        foreach($tmaData as $day => $total) $dataTigaBulanLalu[$day] = $total;

        // 6. RECENT ACTIVITY (Pakai Base Query agar terfilter)
        $recentOrders = (clone $orderQuery)->orderBy('created_at', 'desc')->take(5)->get();
        $newProducts = Product::where('tenant_id', $this->tenantId)->orderBy('id', 'desc')->take(5)->get();

        return view('dashboard', compact(
            'totalOmzet', 'totalProduk', 'totalTerjual', 'totalPelanggan', 'totalUser', 'merchantBalance',
            'countSelesai', 'countBelumDikirim', 'countPickup', 'countGagal',
            'dataBulanIni', 'dataBulanLalu', 'dataTigaBulanLalu',
            'recentOrders', 'newProducts'
        ));
    }
}
