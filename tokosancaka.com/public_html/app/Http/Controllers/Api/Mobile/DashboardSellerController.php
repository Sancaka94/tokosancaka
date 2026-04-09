<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;
use App\Models\Store;
use App\Models\Pesanan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Tetap simpan untuk log di terminal server

class DashboardSellerController extends Controller
{
    /**
     * Menampilkan data dashboard seller untuk aplikasi mobile.
     */
    public function index(Request $request)
    {
        // Menggunakan $request->user() yang divalidasi via middleware auth:sanctum / api
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Silakan login terlebih dahulu.',
            ], 401);
        }

        $store = Store::where('user_id', $user->id_pengguna)->first();

        // Blok jika toko belum diatur
        if (!$store) {
            $stats = [
                'revenueToday' => 0,
                'ordersToday' => 0,
                'revenueThisMonth' => 0,
                'ordersPending' => 0,
                'ordersProcessing' => 0,
                'ordersShipment' => 0,
                'ordersCompletedMonth' => 0,
                'newManualOrders' => 0,
                'totalActiveProducts' => 0,
                'dailyRevenue' => [],
                'orderStatusSummary' => [],
                'LOG_LOG' => 'Toko belum diatur, tidak ada data.' // Pesan debug jika tidak ada toko
            ];

            return response()->json([
                'success' => true,
                'message' => 'Toko belum diatur.',
                'data' => [
                    'store' => null, // Biarkan null di sisi mobile untuk me-trigger tampilan "Buat Toko"
                    'stats' => $stats,
                    'recentMarketplaceOrders' => []
                ]
            ], 200);
        }

        $storeId = $store->id;
        $userId = $user->id_pengguna;

        // Ambil semua statistik (Sudah termasuk data LOG LOG)
        $stats = $this->getDashboardStats($storeId, $userId);

        // Ambil 5 pesanan terbaru
        $recentMarketplaceOrders = Order::where('store_id', $storeId)
                                        ->with([
                                            'user',
                                            'items',
                                            'items.product'
                                            // Catatan: 'store' dan 'store.user' saya hilangkan dari with()
                                            // karena kita sudah tahu ini order untuk toko ini sendiri,
                                            // ini akan menghemat payload JSON di mobile.
                                            // Jika tetap butuh, silakan tambahkan kembali.
                                        ])
                                        ->latest()
                                        ->take(5)
                                        ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data dashboard berhasil diambil.',
            'data' => [
                'store' => $store,
                'stats' => $stats,
                'recentMarketplaceOrders' => $recentMarketplaceOrders
            ]
        ], 200);
    }

    private function getDashboardStats($storeId, $userId)
{
    $thirtyDaysAgo = Carbon::now()->subDays(30);
    $today = Carbon::today();

    // === LOG LOG DEBUGGING (Tetap biarkan agar kamu bisa pantau) ===
    $totalSemuaOrder = Order::where('store_id', $storeId)->count();
    $statusDiDatabase = Order::where('store_id', $storeId)
                             ->select('status', DB::raw('count(*) as total'))
                             ->groupBy('status')
                             ->pluck('total', 'status')
                             ->toArray();

    $logUntukHp = [
        'total_semua_order_di_db' => $totalSemuaOrder,
        'status_yang_terdeteksi' => $statusDiDatabase,
        'catatan' => 'Status PAID sekarang dimasukkan ke kategori PROSES'
    ];

    // ==============================================================
    // PERBAIKAN QUERY BERDASARKAN HASIL DEBUG TADI
    // ==============================================================

    // 1. Definisikan kategori status sesuai yang ada di DB kamu ("paid")
    $statusCompleted = ['completed', 'Selesai', 'SELESAI', 'paid']; // Tambahkan 'paid' di sini
    $statusPending   = ['pending', 'Menunggu Pembayaran'];
    $statusProcessing = ['processing', 'paid', 'PAID', 'Diproses']; // <--- "paid" masuk sini
    $statusShipment   = ['shipment', 'Dikirim'];

    // Gunakan updated_at karena finished_at kamu NULL
    $kolomTanggal = 'updated_at';

    // HITUNG DATA DINAMIS
    $revenueToday = Order::where('store_id', $storeId)
                            ->whereIn('status', $statusCompleted)
                            ->whereDate($kolomTanggal, $today)
                            ->sum('subtotal');

    $ordersToday = Order::where('store_id', $storeId)
                            ->whereDate('created_at', $today)
                            ->count();

    $revenueThisMonth = Order::where('store_id', $storeId)
                                ->whereIn('status', $statusCompleted)
                                ->where($kolomTanggal, '>=', $thirtyDaysAgo)
                                ->sum('subtotal');

    // Stats Grid
    $ordersPending = Order::where('store_id', $storeId)
                            ->whereIn('status', $statusPending)
                            ->count();

    $ordersProcessing = Order::where('store_id', $storeId)
                            ->whereIn('status', $statusProcessing)
                            ->count();

    $ordersShipment = Order::where('store_id', $storeId)
                            ->whereIn('status', $statusShipment)
                            ->count();

    $ordersCompletedMonth = Order::where('store_id', $storeId)
                                ->whereIn('status', $statusCompleted)
                                ->where($kolomTanggal, '>=', $thirtyDaysAgo)
                                ->count();

    // Data Manual & Produk
    $newManualOrders = Pesanan::where('id_toko', $storeId)
                                ->whereIn('status', ['Menunggu Pickup', 'menunggu pickup'])
                                ->count();

    $totalActiveProducts = Product::where('store_id', $storeId)
                                  ->whereIn('status', ['active', 'Aktif', 'ACTIVE'])
                                  ->count();

    return [
        'revenueToday' => (int) $revenueToday,
        'ordersToday' => $ordersToday,
        'revenueThisMonth' => (int) $revenueThisMonth,
        'ordersPending' => $ordersPending,
        'ordersProcessing' => $ordersProcessing,
        'ordersShipment' => $ordersShipment,
        'ordersCompletedMonth' => $ordersCompletedMonth,
        'newManualOrders' => $newManualOrders,
        'totalActiveProducts' => $totalActiveProducts,
        'LOG_LOG' => $logUntukHp
    ];
}
}
