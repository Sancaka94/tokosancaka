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
    public function index()
    {
        $allSlides = Slide::orderBy('created_at', 'desc')->get();
        
        $cacheDuration = 600; // Durasi cache dalam detik (10 menit)

        // --- Mengambil Statistik Utama (dengan Caching) ---
        $stats = Cache::remember('admin_dashboard_stats_v10', $cacheDuration, function () {
            $totalTopUp = TopUp::where('status', 'success')->sum('amount');
            // Jika ingin total ongkir pesanan sebagai pendapatan, gunakan shipping_cost
            // Jika ingin total harga barang, gunakan total_harga_barang. 
            // Di sini saya biarkan total_harga_barang untuk stats global (sesuai kode asli), 
            // tapi Anda bisa ubah ke shipping_cost jika perlu.
            $totalOngkirPesanan = Pesanan::sum('shipping_cost'); 

            return [
                'totalPendapatan' => $totalTopUp + $totalOngkirPesanan,
                'totalPesanan' => Pesanan::count(),
                'jumlahToko' => User::where('role', 'Seller')->count(),
                'penggunaBaru' => User::where('role', 'Pelanggan')->where('created_at', '>=', now()->subDays(30))->count(),
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
        $pesananTerbaru = Cache::remember('admin_dashboard_recent_orders_v5', $cacheDuration, function () {
            return Pesanan::with('pembeli')->latest('created_at')->take(6)->get();
        });

// --- REKAPITULASI EKSPEDISI (LENGKAP: KOTA & STATUS) ---
        $rekapEkspedisi = Cache::remember('admin_dashboard_rekap_ekspedisi_v35', $cacheDuration, function () {
            
            // 1. MASTER DATA
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

            // 2. INISIALISASI
            $stats = [];
            foreach ($courierMap as $code => $info) {
                $displayName = $info['name'];
                $stats[$displayName] = [
                    'nama' => $displayName,
                    'logo' => $info['logo_url'],
                    'filter_code' => $code,
                    'total_order' => 0,
                    'total_profit' => 0,
                    // Array Penampung Unik
                    'customers' => [], 
                    'senders' => [],   
                    'receivers' => [], 
                    'cities_origin' => [], // Kota Asal
                    'cities_dest' => [],   // Kota Tujuan
                    // Counter Status
                    'status_selesai' => 0,
                    'status_gagal' => 0,
                    'status_dikirim' => 0,
                    'status_pickup' => 0,
                ];
            }


$orders = Pesanan::query()
    ->leftJoin('Pengguna', 'Pengguna.id_pengguna', '=', 'Pesanan.customer_id')
    ->select(
        'Pesanan.resi',           // <--- PASTIKAN INI ADA
        'Pesanan.nomor_invoice',  // <--- PASTIKAN INI ADA
        'Pesanan.expedition',
        'Pesanan.shipping_cost',
        'Pesanan.sender_phone',
        'Pesanan.sender_name',
        'Pesanan.receiver_name',
        'Pesanan.sender_regency',
        'Pesanan.receiver_regency',
        'Pesanan.status_pesanan',
        'Pengguna.store_name as nama_toko_anda', // ALIAS KHUSUS
        'Pengguna.nama_lengkap as nama_user_anda' // ALIAS KHUSUS
    )
    ->whereNotNull('Pesanan.expedition')
    ->where('Pesanan.expedition', '!=', '')
    ->latest('Pesanan.created_at') // Urutkan dari yang terbaru
    ->take(5)
    ->get();

// ... the rest of the logic
            // 4. LOGIKA HITUNG
            foreach ($orders as $order) {
                $parts = explode('-', $order->expedition);
                if (count($parts) >= 2) {
                    $dbCode = strtolower($parts[1]); 
                    if (isset($courierMap[$dbCode])) {
                        $displayName = $courierMap[$dbCode]['name'];
                        
                        // --- Metric Dasar ---
                        $stats[$displayName]['total_order']++;
                        $stats[$displayName]['total_profit'] += $order->shipping_cost;
                        
                        // --- Hitung Unik (Orang & Kota) ---
                        // Pelanggan
                        $customerId = $order->id_pengguna_pembeli ?? $order->sender_phone;
                        if ($customerId) $stats[$displayName]['customers'][$customerId] = true;

                        // Pengirim & Penerima
                        if ($order->sender_name) $stats[$displayName]['senders'][strtoupper(trim($order->sender_name))] = true;
                        if ($order->receiver_name) $stats[$displayName]['receivers'][strtoupper(trim($order->receiver_name))] = true;

                        // Kota (Regency)
                        if ($order->sender_regency) $stats[$displayName]['cities_origin'][strtoupper(trim($order->sender_regency))] = true;
                        if ($order->receiver_regency) $stats[$displayName]['cities_dest'][strtoupper(trim($order->receiver_regency))] = true;

                        // --- Klasifikasi Status ---
                        $st = strtolower($order->status_pesanan);
                        
                        // Logic Mapping Status (Sesuaikan dengan value DB Anda)
                        if ($st == 'selesai') {
                            $stats[$displayName]['status_selesai']++;
                        } 
                        elseif ($st == 'menunggu pickup') {
                            $stats[$displayName]['status_pickup']++;
                        }
                        elseif (in_array($st, ['sedang dikirim', 'diproses', 'dikirim', 'sedang diantar'])) {
                            $stats[$displayName]['status_dikirim']++;
                        }
                        elseif (in_array($st, ['batal', 'gagal resi', 'retur', 'gagal'])) {
                            $stats[$displayName]['status_gagal']++;
                        }
                    }
                }
            }

            // 5. MAPPING OUTPUT
            return collect($stats)->map(function ($item) {
                return (object) [
                    'nama' => $item['nama'],
                    'logo' => $item['logo'],
                    'url_detail' => route('admin.pesanan.index', ['ekspedisi' => $item['filter_code']]),
                    
                    'total_order' => $item['total_order'],
                    'total_profit' => $item['total_profit'],
                    
                    // Count array keys untuk mendapatkan jumlah unik
                    'total_pelanggan' => count($item['customers']),
                    'total_pengirim' => count($item['senders']),
                    'total_penerima' => count($item['receivers']),
                    'total_kota_asal' => count($item['cities_origin']),
                    'total_kota_tujuan' => count($item['cities_dest']),
                    
                    // Status
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
        return view('admin.dashboard', array_merge($stats, [
            'chartData' => $chartData,
            'spxChartData' => $spxChartData,
            'expeditionData' => $expeditionData,
            'pesananTerbaru' => $pesananTerbaru,
            'rekapEkspedisi' => $rekapEkspedisi,
            'notifications' => $notifications,
            'recentNotifications' => $recentNotifications,
            'slides' => $slides,
        ]));
    }

    
}