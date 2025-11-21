<?php

namespace App\Http\Controllers\Toko;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\Order;
use App\Models\Store;
use App\Models\Pesanan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman dashboard seller.
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }
        
        $store = Store::where('user_id', $user->id_pengguna)->first();

        // Blok jika toko belum diatur
        if (!$store) {
            $stats = [
                'revenueToday' => 0,
                'ordersToday' => 0,
                'revenueThisMonth' => 0,
                'ordersPending' => 0, // <-- Tambahan
                'ordersProcessing' => 0, // <-- Tambahan
                'ordersShipment' => 0, // <-- Tambahan
                'ordersCompletedMonth' => 0, // <-- Tambahan
                'newManualOrders' => 0,
                'totalActiveProducts' => 0,
                'dailyRevenue' => [],
                'orderStatusSummary' => [],
            ];
            $recentMarketplaceOrders = collect();
            $store = new Store(); // Buat objek Store kosong agar view tidak error
            $store->name = 'Toko Belum Diatur';

            return view('seller.dashboard', compact(
                'store',
                'stats',
                'recentMarketplaceOrders'
            ));
        }

        $storeId = $store->id;
        $userId = $user->id_pengguna;

        // Ambil semua statistik
        $stats = $this->getDashboardStats($storeId, $userId);

        // Ambil 5 pesanan terbaru
        $recentMarketplaceOrders = Order::where('store_id', $storeId)
                                        ->with([
                                            'user',
                                            'items',
                                            'items.product',
                                            'store',
                                            'store.user'
                                        ])
                                        ->latest()
                                        ->take(5)
                                        ->get();

        return view('seller.dashboard', compact(
            'store',
            'stats',
            'recentMarketplaceOrders'
        ));
    }

    /**
     * Helper function untuk mengambil data statistik
     */
    private function getDashboardStats($storeId, $userId)
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $today = Carbon::today();

        // ======================================================
        // PERBAIKAN: Statistik Harian dan Bulanan
        // ======================================================

        // 1. Pendapatan HARI INI (Hanya dari order 'completed')
        $revenueToday = Order::where('store_id', $storeId)
                                ->where('status', 'completed')
                                ->whereDate('finished_at', $today) // Gunakan finished_at untuk pendapatan
                                ->sum('subtotal');

        // 2. Pesanan HARI INI (Semua order baru yang masuk hari ini)
        $ordersToday = Order::where('store_id', $storeId)
                                ->whereDate('created_at', $today)
                                ->count();

        // 3. Pendapatan BULAN INI (Hanya dari order 'completed' 30 hari terakhir)
        $revenueThisMonth = Order::where('store_id', $storeId)
                                    ->where('status', 'completed')
                                    ->where('finished_at', '>=', $thirtyDaysAgo) // Gunakan finished_at
                                    ->sum('subtotal');

        // 4. TOTAL Pesanan BULAN INI (Dihapus, diganti di bawah)
        
        // ======================================================
        // TAMBAHAN: Statistik per Status
        // ======================================================

        // (BARU) Pesanan Menunggu Pembayaran
        $ordersPending = Order::where('store_id', $storeId)
                                ->where('status', 'pending')
                                ->count();

        // (BARU) Pesanan Perlu Diproses
        $ordersProcessing = Order::where('store_id', $storeId)
                                ->where('status', 'processing')
                                ->count();

        // (BARU) Pesanan Dalam Pengiriman
        $ordersShipment = Order::where('store_id', $storeId)
                                ->where('status', 'shipment')
                                ->count();
        
        // (BARU) Pesanan Selesai (30 Hari)
        $ordersCompletedMonth = Order::where('store_id', $storeId)
                                    ->where('status', 'completed')
                                    ->where('finished_at', '>=', $thirtyDaysAgo)
                                    ->count();
        
        // ======================================================

        // 5. Pesanan Manual Baru (Berdasarkan ID Toko)
        $newManualOrders = Pesanan::where('id_toko', $storeId)
                                    ->where('status', 'Menunggu Pickup')
                                    ->count();

        // 6. Total Produk (Berdasarkan ID Toko)
        $totalActiveProducts = Product::where('store_id', $storeId)
                                      ->where('status', 'active')
                                      ->count();

        // Data Grafik (Sudah Benar)
        $dailyRevenue = Order::where('store_id', $storeId)
            ->where('status', 'completed')
            ->where('finished_at', '>=', $thirtyDaysAgo)
            ->select(DB::raw('DATE(finished_at) as date'), DB::raw('SUM(subtotal) as total'))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
            
        $orderStatusSummary = Order::where('store_id', $storeId)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();


        return [
            'revenueToday' => $revenueToday,
            'ordersToday' => $ordersToday,
            'revenueThisMonth' => $revenueThisMonth,
            'ordersPending' => $ordersPending, // <-- Kirim data baru
            'ordersProcessing' => $ordersProcessing, // <-- Kirim data baru
            'ordersShipment' => $ordersShipment, // <-- Kirim data baru
            'ordersCompletedMonth' => $ordersCompletedMonth, // <-- Kirim data baru
            'newManualOrders' => $newManualOrders,
            'totalActiveProducts' => $totalActiveProducts,
            'dailyRevenue' => $dailyRevenue,
            'orderStatusSummary' => $orderStatusSummary,
        ];
    }
}