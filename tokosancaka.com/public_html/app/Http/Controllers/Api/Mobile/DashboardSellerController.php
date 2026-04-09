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

    /**
     * Helper function untuk mengambil data statistik
     */
    private function getDashboardStats($storeId, $userId)
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $today = Carbon::today();

        // ==============================================================
        // 🛠️ LOG LOG - DEBUGGING UNTUK DIKIRIM KE HP
        // ==============================================================
        $totalSemuaOrder = Order::where('store_id', $storeId)->count();

        $statusDiDatabase = Order::where('store_id', $storeId)
                                 ->select('status', DB::raw('count(*) as total'))
                                 ->groupBy('status')
                                 ->pluck('total', 'status')
                                 ->toArray();

        $contohSelesai = Order::where('store_id', $storeId)
                              ->whereIn('status', ['completed', 'Completed', 'Selesai', 'COMPLETED', 'SELESAI'])
                              ->first();

        $cekFinishedAt = $contohSelesai ? ($contohSelesai->finished_at ?? 'KOSONG / NULL') : 'Belum ada order Selesai';

        // Simpan LOG ke terminal server juga
        Log::info("=== LOG LOG DASHBOARD SELLER (Toko ID: $storeId) ===");
        Log::info("Total Semua Order: " . $totalSemuaOrder);
        Log::info("Status yang ada di DB: ", $statusDiDatabase);
        Log::info("Cek kolom finished_at: " . $cekFinishedAt);

        // Siapkan LOG LOG untuk dikirim ke JSON HP
        $logUntukHp = [
            'total_semua_order_di_db' => $totalSemuaOrder,
            'status_yang_terdeteksi' => $statusDiDatabase,
            'isi_kolom_finished_at' => $cekFinishedAt,
            'solusi_diterapkan' => 'Menggunakan whereIn untuk toleransi huruf & updated_at untuk tanggal'
        ];
        // ==============================================================


        // ==============================================================
        // PERBAIKAN QUERY AGAR TIDAK NOL (0)
        // ==============================================================
        // 1. Toleransi Status (huruf besar/kecil/bahasa)
        $statusCompleted = ['completed', 'Completed', 'Selesai', 'COMPLETED', 'SELESAI'];
        $statusPending = ['pending', 'Pending', 'Menunggu Pembayaran', 'PENDING'];
        $statusProcessing = ['processing', 'Processing', 'Diproses', 'PROCESSING'];
        $statusShipment = ['shipment', 'Shipment', 'Dikirim', 'SHIPMENT'];

        // 2. Ganti acuan tanggal dari finished_at menjadi updated_at (karena finished_at sering NULL)
        $kolomTanggalSelesai = 'updated_at';

        // 1. Pendapatan HARI INI
        $revenueToday = Order::where('store_id', $storeId)
                                ->whereIn('status', $statusCompleted)
                                ->whereDate($kolomTanggalSelesai, $today)
                                ->sum('subtotal');

        // 2. Pesanan HARI INI
        $ordersToday = Order::where('store_id', $storeId)
                                ->whereDate('created_at', $today)
                                ->count();

        // 3. Pendapatan BULAN INI
        $revenueThisMonth = Order::where('store_id', $storeId)
                                    ->whereIn('status', $statusCompleted)
                                    ->where($kolomTanggalSelesai, '>=', $thirtyDaysAgo)
                                    ->sum('subtotal');

        // 4. Pesanan Menunggu Pembayaran
        $ordersPending = Order::where('store_id', $storeId)
                                ->whereIn('status', $statusPending)
                                ->count();

        // 5. Pesanan Perlu Diproses
        $ordersProcessing = Order::where('store_id', $storeId)
                                ->whereIn('status', $statusProcessing)
                                ->count();

        // 6. Pesanan Dalam Pengiriman
        $ordersShipment = Order::where('store_id', $storeId)
                                ->whereIn('status', $statusShipment)
                                ->count();

        // 7. Pesanan Selesai (30 Hari)
        $ordersCompletedMonth = Order::where('store_id', $storeId)
                                    ->whereIn('status', $statusCompleted)
                                    ->where($kolomTanggalSelesai, '>=', $thirtyDaysAgo)
                                    ->count();

        // 8. Pesanan Manual Baru
        $newManualOrders = Pesanan::where('id_toko', $storeId)
                                    ->whereIn('status', ['Menunggu Pickup', 'menunggu pickup'])
                                    ->count();

        // 9. Total Produk Aktif
        $totalActiveProducts = Product::where('store_id', $storeId)
                                      ->whereIn('status', ['active', 'Active', 'Aktif', 'ACTIVE'])
                                      ->count();

        // Data Grafik
        $dailyRevenue = Order::where('store_id', $storeId)
            ->whereIn('status', $statusCompleted)
            ->where($kolomTanggalSelesai, '>=', $thirtyDaysAgo)
            ->select(DB::raw("DATE($kolomTanggalSelesai) as date"), DB::raw('SUM(subtotal) as total'))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $orderStatusSummary = Order::where('store_id', $storeId)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

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
            'dailyRevenue' => $dailyRevenue,
            'orderStatusSummary' => $orderStatusSummary,

            // INI DIA! Data LOG LOG dikirim ke HP di dalam json "stats"
            'LOG_LOG' => $logUntukHp
        ];
    }
}
