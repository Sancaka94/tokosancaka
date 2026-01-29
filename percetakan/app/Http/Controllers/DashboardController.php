<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\User;
use App\Models\Tenant; // <--- WAJIB TAMBAH
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

    public function index()
    {
        // 1. STATISTIK KARTU MONITOR (SELALU FILTER DENGAN tenant_id)

        // Total Omzet hanya milik tenant ini
        $totalOmzet = Order::where('tenant_id', $this->tenantId)
                           ->where('payment_status', 'paid')
                           ->sum('final_price');

        // Total Produk hanya milik tenant ini
        $totalProduk = Product::where('tenant_id', $this->tenantId)->count();

        // Total Item Terjual hanya milik tenant ini
        $totalTerjual = OrderDetail::where('tenant_id', $this->tenantId)->sum('quantity');

        // Total Pelanggan Unik hanya milik tenant ini
        $totalPelanggan = Order::where('tenant_id', $this->tenantId)
                               ->distinct('customer_name')
                               ->count();

        // Jumlah User/Staff - Filter berdasarkan tenant_id
        $totalUser = User::where('tenant_id', $this->tenantId)->count();

        // Saldo Merchant DANA (Hanya Master atau per Tenant jika ada)
        // Jika saldo merchant DANA hanya milik Master (ID 11), tetap biarkan id 11
        $merchantBalance = DB::table('affiliates')
                            ->where('id', 11)
                            ->value('dana_merchant_balance') ?? 0;

        // 2. DATA UNTUK GRAFIK (7 Hari Terakhir - Filter Tenant)
        $salesData = Order::where('tenant_id', $this->tenantId)
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(final_price) as total')
            )
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        // 3. DATA RECENT ACTIVITY (Aktivitas Terbaru - Filter Tenant)
        $recentOrders = Order::where('tenant_id', $this->tenantId)
                             ->orderBy('created_at', 'desc')
                             ->take(5)
                             ->get();

        $newProducts = Product::where('tenant_id', $this->tenantId)
                              ->orderBy('id', 'desc')
                              ->take(5)
                              ->get();

        // 4. KIRIM DATA KE VIEW
        return view('dashboard', [
            'totalOmzet'      => $totalOmzet,
            'totalProduk'     => $totalProduk,
            'totalTerjual'    => $totalTerjual,
            'totalPelanggan'  => $totalPelanggan,
            'totalUser'       => $totalUser,
            'merchantBalance' => $merchantBalance,
            'salesData'       => $salesData,
            'recentOrders'    => $recentOrders,
            'newProducts'     => $newProducts,
            'hariIni'         => Carbon::now()->translatedFormat('d F Y')
        ]);
    }
}
