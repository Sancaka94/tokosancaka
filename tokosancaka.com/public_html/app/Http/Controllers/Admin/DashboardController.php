<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Pesanan;
use App\Models\TopUp;
use App\Models\Slide;
use App\Models\ScannedPackage;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Notifications\DatabaseNotification;
use App\Helpers\ShippingHelper;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // A. TANGKAP INPUT DULUAN
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');
    $cacheDuration = 60;

    // B. DEFINISIKAN KEY CACHE
    $statsCacheKey = 'admin_stats_v11_' . ($startDate ?? 'all') . '_' . ($endDate ?? 'all');

    // C. JALANKAN CACHE
    // Gunakan 'use ($startDate, $endDate)' agar fungsi di dalam bisa baca variabel luar
    $statsData = Cache::remember($statsCacheKey, $cacheDuration, function () use ($startDate, $endDate) {
        $topUpQuery = TopUp::where('status', 'success');
        $pesananQuery = Pesanan::query();

        if ($startDate && $endDate) {
            $range = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
            $topUpQuery->whereBetween('created_at', $range);
            $pesananQuery->whereBetween('created_at', $range);
        }

        return [
            'totalPendapatan' => $topUpQuery->sum('amount') + $pesananQuery->sum('shipping_cost'),
            'totalPesanan'    => $pesananQuery->count(),
            'jumlahToko'      => User::where('role', 'Seller')->count(),
            'penggunaBaru'    => User::where('role', 'Pelanggan')->where('created_at', '>=', now()->subDays(30))->count(),
            
            // Card Status Tambahan
            'totalTerkirim'       => (clone $pesananQuery)->where('status_pesanan', 'Selesai')->count(),
            'totalSedangDikirim'  => (clone $pesananQuery)->whereIn('status_pesanan', ['Sedang Dikirim', 'Dikirim', 'Diproses'])->count(),
            'totalMenungguPickup' => (clone $pesananQuery)->where('status_pesanan', 'Menunggu Pickup')->count(),
            'totalGagal'          => (clone $pesananQuery)->whereIn('status_pesanan', ['Batal', 'Gagal', 'Retur', 'Kadaluarsa', 'Dibatalkan'])->count(),
        ];
    });

        // --- Mengambil Notifikasi untuk Flasher (dengan Caching) ---
        $notifications = Cache::remember('admin_dashboard_notifications_v8', 60, function () {
            return [
                'pendaftaranBaru' => User::where('role', 'Pelanggan')->where('status', 'pending')->count(),
                'pesananBaru' => Pesanan::where('status_pesanan', 'Menunggu Pickup')->count(),
                'spxScanBaru' => ScannedPackage::whereDate('created_at', today())->count(),
                'riwayatScanBaru' => ScannedPackage::whereDate('created_at', today())->count(), 
            ];
        });

        // --- Mengambil Data Grafik Pendapatan (dengan Caching) ---
        $chartData = Cache::remember('admin_dashboard_revenue_chart_v5', $cacheDuration, function () {
            $salesData = Pesanan::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('sum(shipping_cost) as total')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->pluck('total', 'date');

            $dates = collect();
            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i)->format('Y-m-d');
                $dates->put($date, 0);
            }
            $dailySales = $dates->merge($salesData);
            
            return [
                'labels' => $dailySales->keys()->map(fn($date) => Carbon::parse($date)->format('d M'))->toArray(),
                'data' => $dailySales->values()->toArray(),
            ];
        });


        // 3. Grafik Peringkat Ekspedisi (Mendatar)
        $expeditionData = Cache::remember('admin_exp_rank_v1', $cacheDuration, function () {
            $orders = DB::table('Pesanan')->select('expedition', DB::raw('count(*) as total'))
                ->whereNotNull('expedition')->where('expedition', '!=', '')->groupBy('expedition')->get();
            $proc = [];
            foreach ($orders as $o) {
                $name = strtoupper(explode('-', $o->expedition)[1] ?? 'LAINNYA');
                $proc[$name] = ($proc[$name] ?? 0) + $o->total;
            }
            arsort($proc);
            return ['labels' => array_keys($proc), 'data' => array_values($proc)];
        });

        $expeditionOmzetData = Cache::remember('admin_exp_omzet_rank_v1', 600, function () {
    $orders = DB::table('Pesanan')
        ->select('expedition', DB::raw('sum(shipping_cost) as total_omzet'))
        ->whereNotNull('expedition')
        ->where('expedition', '!=', '')
        ->groupBy('expedition')
        ->get();

    $processed = [];
    foreach ($orders as $order) {
        $parts = explode('-', $order->expedition);
        // Menyesuaikan mapping nama seperti grafik sebelumnya
        $name = strtoupper($parts[1] ?? 'LAINNYA');
        
        if (isset($processed[$name])) {
            $processed[$name] += $order->total_omzet;
        } else {
            $processed[$name] = $order->total_omzet;
        }
    }

    arsort($processed); // Urutkan dari omzet terbesar

    return [
        'labels' => array_keys($processed),
        'data' => array_values($processed),
    ];
});
        

        // --- Mengambil Data Grafik Scan SPX (dengan Caching) ---
        $spxChartData = Cache::remember('admin_dashboard_spx_chart_v5', $cacheDuration, function () {
            $spxData = ScannedPackage::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(id) as total')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->pluck('total', 'date');
            
            $dates = collect();
            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i)->format('Y-m-d');
                $dates->put($date, 0);
            }
            $dailyScans = $dates->merge($spxData);

            return [
                'labels' => $dailyScans->keys()->map(fn($date) => Carbon::parse($date)->format('d M'))->toArray(),
                'data' => $dailyScans->values()->toArray(),
            ];
        });

        // --- Mengambil Data Tabel (dengan Caching) ---
// Key diperbarui ke v6 agar cache lama yang berisi hanya 6 data terhapus
$pesananTerbaru = Cache::remember('admin_dashboard_recent_orders_v7', $cacheDuration, function () {
    // Menghapus ->take(6) agar mengambil semua data pesanan
    return Pesanan::with('pembeli')->latest('created_at')->get();
});

// --- REKAPITULASI EKSPEDISI ---

// 1. Pastikan variabel ditangkap dulu di paling atas function index
$startDate = $request->input('start_date');
$endDate = $request->input('end_date');

// 2. Buat Key Cache yang unik agar data berubah saat filter diganti
$rekapCacheKey = 'admin_rekap_exp_v_' . ($startDate ?? 'all') . '_' . ($endDate ?? 'all');

// 3. Tambahkan "use ($startDate, $endDate)" agar variabel bisa dibaca di dalam cache
$rekapEkspedisi = Cache::remember($rekapCacheKey, $cacheDuration, function () use ($startDate, $endDate) {
    
    // 2. MASTER DATA COURIER
    $courierMap = [
        'jne' => ['name' => 'JNE', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jne.png'],
        'tiki' => ['name' => 'TIKI', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/tiki.png'],
        'posindonesia' => ['name' => 'POS Indonesia', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/posindonesia.png'],
        'sicepat' => ['name' => 'SiCepat', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/sicepat.png'],
        'sap' => ['name' => 'SAP Express', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/sap.png'],
        'ncs' => ['name' => 'NCS Kurir', 'logo_url' => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjxj3iyyZEjK2L4A4yCIr_E-4W3hF2lk_yb-t0Oj2oFPErCPCMHie5LHqps02xMb6sNa-Gqz5NSX_P_hzWlYpUpJUlCD4iN6_QxiSG9fzY4bsZ9XvLFDn7HCiORtNvIlPfuQbSSdW96p7x7uN8ek3FWyHW9c2bznrFBQkoLd5A9sVAFVKWLfUhT3Dxh/s320/GKL41_NCS%20Kurir%20-%20Koleksilogo.com.jpg'],
        'idx' => ['name' => 'ID Express', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/idx.png'],
        'gojek' => ['name' => 'GoSend', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/gosend.png'],
        'grab' => ['name' => 'GrabExpress', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/grab.png'],
        'jnt' => ['name' => 'J&T Express', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jnt.png'],
        'indah' => ['name' => 'Indah Cargo', 'logo_url' => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEicOAaLoH2eElQ93_gbkzhvk4dRhWVlk5wQsGgilihIB58321aHchlJLdjyz1ToS25P_nWrHJ_E4QBiW_OVlI7tQt7cZ5I0HZqk6StS7jZltLVvDXp2d5ZDLB9yklhV4x6z2iXyURURDv_unhf-U6vyiD_8to9OC4PBwMwyU_5wAqOiCl6tKiaTA-ri1Q/s851/Logo%20Indah%20Logistik%20Cargo@0.5x.png'],
        'jtcargo' => ['name' => 'J&T Cargo', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jtcargo.png'],
        'lion' => ['name' => 'Lion Parcel', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/lion.png'],
        'spx' => ['name' => 'SPX Express', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/spx.png'],
        'ninja' => ['name' => 'Ninja Express', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/ninja.png'],
        'anteraja' => ['name' => 'Anteraja', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/anteraja.png'],
        'sentral' => ['name' => 'Sentral Cargo', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/centralcargo.png'],
        'borzo' => ['name' => 'Borzo', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/borzo.png'],
    ];

    // 3. INISIALISASI STATS
    $stats = [];
    foreach ($courierMap as $code => $info) {
        $displayName = $info['name'];
        $stats[$displayName] = [
            'nama' => $displayName,
            'logo' => $info['logo_url'],
            'filter_code' => $code,
            'total_order' => 0,
            'total_profit' => 0,
            'customers' => [], 
            'senders' => [],   
            'receivers' => [], 
            'cities_origin' => [], 
            'cities_dest' => [],   
            'status_selesai' => 0,
            'status_gagal' => 0,
            'status_dikirim' => 0,
            'status_pickup' => 0,
        ];
    }

    // 4. QUERY DATA DENGAN FILTER
    $orderQuery = Pesanan::query()
        ->leftJoin('Pengguna', 'Pengguna.id_pengguna', '=', 'Pesanan.customer_id')
        ->select(
            'Pesanan.expedition', 'Pesanan.shipping_cost', 'Pesanan.customer_id',
            'Pesanan.sender_phone', 'Pesanan.sender_name', 'Pesanan.receiver_name',
            'Pesanan.sender_regency', 'Pesanan.receiver_regency', 'Pesanan.status_pesanan'
        )
        ->whereNotNull('expedition')
        ->where('expedition', '!=', '');

    // Eksekusi filter tanggal jika input start_date & end_date ada
    if ($startDate && $endDate) {
        $orderQuery->whereBetween('Pesanan.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    }

    $orders = $orderQuery->get();

    // 5. LOOPING HITUNG DATA
    foreach ($orders as $order) {
        $parts = explode('-', $order->expedition);
        if (count($parts) >= 2) {
            $dbCode = strtolower($parts[1]); 
            if (isset($courierMap[$dbCode])) {
                $displayName = $courierMap[$dbCode]['name'];
                
                $stats[$displayName]['total_order']++;
                $stats[$displayName]['total_profit'] += $order->shipping_cost;
                
                // Hitung Unik (Menggunakan Array Key agar otomatis distinct)
                $cid = $order->customer_id ?? $order->sender_phone;
                if ($cid) $stats[$displayName]['customers'][$cid] = true;
                if ($order->sender_name) $stats[$displayName]['senders'][strtoupper(trim($order->sender_name))] = true;
                if ($order->receiver_name) $stats[$displayName]['receivers'][strtoupper(trim($order->receiver_name))] = true;
                if ($order->sender_regency) $stats[$displayName]['cities_origin'][strtoupper(trim($order->sender_regency))] = true;
                if ($order->receiver_regency) $stats[$displayName]['cities_dest'][strtoupper(trim($order->receiver_regency))] = true;

                // Klasifikasi Status
                $st = strtolower($order->status_pesanan);
                if ($st == 'selesai') {
                    $stats[$displayName]['status_selesai']++;
                } elseif ($st == 'menunggu pickup') {
                    $stats[$displayName]['status_pickup']++;
                } elseif (in_array($st, ['sedang dikirim', 'diproses', 'dikirim', 'sedang diantar'])) {
                    $stats[$displayName]['status_dikirim']++;
                } elseif (in_array($st, ['batal', 'gagal resi', 'retur', 'gagal'])) {
                    $stats[$displayName]['status_gagal']++;
                }
            }
        }
    }

    // 6. FORMAT KE OBJECT & SORTING
    return collect($stats)->map(function ($item) {
        return (object) [
            'nama' => $item['nama'],
            'logo' => $item['logo'],
            'url_detail' => route('admin.pesanan.index', ['ekspedisi' => $item['filter_code']]),
            'total_order' => $item['total_order'],
            'total_profit' => $item['total_profit'],
            'total_pelanggan' => count($item['customers']),
            'total_pengirim' => count($item['senders']),
            'total_penerima' => count($item['receivers']),
            'total_kota_asal' => count($item['cities_origin']),
            'total_kota_tujuan' => count($item['cities_dest']),
            'status_selesai' => $item['status_selesai'],
            'status_gagal' => $item['status_gagal'],
            'status_dikirim' => $item['status_dikirim'],
            'status_pickup' => $item['status_pickup'],
        ];
    })->sortByDesc('total_order')->values(); 
});

        // --- Mengambil data notifikasi terbaru untuk tabel ---
        $recentNotifications = DatabaseNotification::latest()->take(10)->get();

        // --- Mengambil data slider dari tabel settings ---
        $sliderData = Setting::where('key', 'slider_informasi')->first();
        $slides = $sliderData ? json_decode($sliderData->value, true) : [];

        // --- Melewatkan semua data ke view ---
        return view('admin.dashboard', array_merge($statsData, [
            'chartData' => $chartData,
            'spxChartData' => $spxChartData,
            'expeditionData' => $expeditionData,
            'pesananTerbaru' => $pesananTerbaru,
            'rekapEkspedisi' => $rekapEkspedisi,
            'notifications' => $notifications,
            'expeditionOmzetData' => $expeditionOmzetData,
            'recentNotifications' => $recentNotifications,
            'slides' => $slides,
            'startDate' => $startDate, 
            'endDate' => $endDate,
        ]));
    }

    
}