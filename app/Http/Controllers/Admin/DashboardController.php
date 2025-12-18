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

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil Input Filter Range Tanggal
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // 2. Buat Base Query untuk Filter (PENTING: Gunakan clone nanti)
        $baseQuery = Pesanan::query();
        if ($startDate && $endDate) {
            $baseQuery->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }

        // Suffix cache supaya data berganti saat filter tanggal berubah
        $cacheSuffix = ($startDate && $endDate) ? '_' . $startDate . '_' . $endDate : '_all_time';
        $cacheDuration = 600;

        // --- 3. Statistik Utama (Total Akumulasi Sesuai Filter) ---
        $stats = Cache::remember('admin_stats' . $cacheSuffix, $cacheDuration, function () use ($baseQuery) {
            $totalTopUp = TopUp::where('status', 'success')->sum('amount');
            $totalOngkir = (clone $baseQuery)->sum('shipping_cost'); 

            return [
                'totalPendapatan' => $totalTopUp + $totalOngkir,
                'totalPesanan' => (clone $baseQuery)->count(),
                'jumlahToko' => User::where('role', 'Seller')->count(),
                'penggunaBaru' => User::where('role', 'Pelanggan')->where('created_at', '>=', now()->subDays(30))->count(),
            ];
        });

        // --- 4. Grafik Peringkat Ekspedisi (Jumlah & Omzet) ---
        $expRankData = Cache::remember('admin_exp_rank' . $cacheSuffix, $cacheDuration, function () use ($baseQuery) {
            $orders = (clone $baseQuery)
                ->select('expedition', DB::raw('count(*) as total'))
                ->whereNotNull('expedition')->where('expedition', '!=', '')
                ->groupBy('expedition')->get();
            
            $proc = [];
            foreach ($orders as $o) {
                $name = strtoupper(explode('-', $o->expedition)[1] ?? 'LAINNYA');
                $proc[$name] = ($proc[$name] ?? 0) + $o->total;
            }
            arsort($proc);
            return ['labels' => array_keys($proc), 'data' => array_values($proc)];
        });

        $expOmzetData = Cache::remember('admin_exp_omzet' . $cacheSuffix, $cacheDuration, function () use ($baseQuery) {
            $orders = (clone $baseQuery)
                ->select('expedition', DB::raw('sum(shipping_cost) as total_omzet'))
                ->whereNotNull('expedition')->where('expedition', '!=', '')
                ->groupBy('expedition')->get();

            $processed = [];
            foreach ($orders as $order) {
                $name = strtoupper(explode('-', $order->expedition)[1] ?? 'LAINNYA');
                $processed[$name] = ($processed[$name] ?? 0) + $order->total_omzet;
            }
            arsort($processed);
            return ['labels' => array_keys($processed), 'data' => array_values($processed)];
        });

        // --- 5. Rekapitulasi Ekspedisi (LENGKAP SEMUA KURIR TERMASUK BORZO) ---
        $rekapEkspedisi = Cache::remember('admin_rekap_full' . $cacheSuffix, $cacheDuration, function () use ($baseQuery) {
            $courierMap = [
                'jne'          => ['name' => 'JNE', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jne.png'],
                'jnt'          => ['name' => 'J&T Express', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jnt.png'],
                'spx'          => ['name' => 'SPX Express', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/spx.png'],
                'sicepat'      => ['name' => 'SiCepat', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/sicepat.png'],
                'tiki'         => ['name' => 'TIKI', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/tiki.png'],
                'posindonesia' => ['name' => 'POS Indonesia', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/posindonesia.png'],
                'lion'         => ['name' => 'Lion Parcel', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/lion.png'],
                'anteraja'     => ['name' => 'Anteraja', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/anteraja.png'],
                'ninja'        => ['name' => 'Ninja Xpress', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/ninja.png'],
                'idx'          => ['name' => 'ID Express', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/idx.png'],
                'jtcargo'      => ['name' => 'J&T Cargo', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jtcargo.png'],
                'sap'          => ['name' => 'SAP Express', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/sap.png'],
                'sentral'      => ['name' => 'Sentral Cargo', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/centralcargo.png'],
                'indah'        => ['name' => 'Indah Logistik', 'logo_url' => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEicOAaLoH2eElQ93_gbkzhvk4dRhWVlk5wQsGgilihIB58321aHchlJLdjyz1ToS25P_nWrHJ_E4QBiW_OVlI7tQt7cZ5I0HZqk6StS7jZltLVvDXp2d5ZDLB9yklhV4x6z2iXyURURDv_unhf-U6vyiD_8to9OC4PBwMwyU_5wAqOiCl6tKiaTA-ri1Q/s851/Logo%20Indah%20Logistik%20Cargo@0.5x.png'],
                'ncs'          => ['name' => 'NCS Kurir', 'logo_url' => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjxj3iyyZEjK2L4A4yCIr_E-4W3hF2lk_yb-t0Oj2oFPErCPCMHie5LHqps02xMb6sNa-Gqz5NSX_P_hzWlYpUpJUlCD4iN6_QxiSG9fzY4bsZ9XvLFDn7HCiORtNvIlPfuQbSSdW96p7x7uN8ek3FWyHW9c2bznrFBQkoLd5A9sVAFVKWLfUhT3Dxh/s320/GKL41_NCS%20Kurir%20-%20Koleksilogo.com.jpg'],
                'gojek'        => ['name' => 'GoSend', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/gosend.png'],
                'grab'         => ['name' => 'GrabExpress', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/grab.png'],
                'borzo'        => ['name' => 'Borzo', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/borzo.png'],
            ];

            $statsRecap = [];
            foreach ($courierMap as $code => $info) {
                $statsRecap[$info['name']] = [
                    'nama' => $info['name'], 'logo' => $info['logo_url'], 'filter_code' => $code,
                    'total_order' => 0, 'total_profit' => 0, 'customers' => [],
                    'status_selesai' => 0, 'status_gagal' => 0, 'status_dikirim' => 0, 'status_pickup' => 0
                ];
            }

            // HITUNG TOTAL AKUMULASI (Hapus take(5))
            $allOrders = (clone $baseQuery)->get();

            foreach ($allOrders as $order) {
                $parts = explode('-', $order->expedition);
                $dbCode = strtolower($parts[1] ?? '');
                
                if (isset($courierMap[$dbCode])) {
                    $name = $courierMap[$dbCode]['name'];
                    $statsRecap[$name]['total_order']++;
                    $statsRecap[$name]['total_profit'] += $order->shipping_cost;
                    $statsRecap[$name]['customers'][$order->customer_id ?? $order->sender_phone] = true;

                    $st = strtolower($order->status_pesanan);
                    if ($st == 'selesai') $statsRecap[$name]['status_selesai']++;
                    elseif ($st == 'menunggu pickup') $statsRecap[$name]['status_pickup']++;
                    elseif (in_array($st, ['dikirim', 'sedang dikirim'])) $statsRecap[$name]['status_dikirim']++;
                    elseif (in_array($st, ['batal', 'gagal'])) $statsRecap[$name]['status_gagal']++;
                }
            }

            return collect($statsRecap)->map(function ($item) {
                $obj = (object) $item;
                $obj->total_pelanggan = count($item['customers']);
                $obj->url_detail = route('admin.pesanan.index', ['ekspedisi' => $item['filter_code']]);
                return $obj;
            })->sortByDesc('total_order')->values();
        });

        // --- 6. Data Pelengkap (Tabel Pesanan & Notifikasi) ---
        $pesananTerbaru = Pesanan::with('pembeli')->latest()->take(6)->get();
        $recentNotifications = DatabaseNotification::latest()->take(10)->get();
        $slides = Setting::where('key', 'slider_informasi')->first();
        $slides = $slides ? json_decode($slides->value, true) : [];

        return view('admin.dashboard', array_merge($stats, [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'expeditionData' => $expRankData,
            'expeditionOmzetData' => $expOmzetData,
            'rekapEkspedisi' => $rekapEkspedisi,
            'pesananTerbaru' => $pesananTerbaru,
            'recentNotifications' => $recentNotifications,
            'slides' => $slides,
            'chartData' => $this->getChartRevenue(),
            'spxChartData' => $this->getChartSpx(),
        ]));
    }

    private function getChartRevenue() {
        $data = Pesanan::select(DB::raw('DATE(created_at) as date'), DB::raw('sum(shipping_cost) as total'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')->orderBy('date', 'asc')->get()->pluck('total', 'date');
        $dates = collect();
        for ($i = 29; $i >= 0; $i--) { $date = now()->subDays($i)->format('Y-m-d'); $dates->put($date, 0); }
        $daily = $dates->merge($data);
        return [
            'labels' => $daily->keys()->map(fn($d) => Carbon::parse($d)->format('d M'))->toArray(),
            'data' => $daily->values()->toArray()
        ];
    }

    private function getChartSpx() {
        $data = ScannedPackage::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(id) as total'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')->orderBy('date', 'asc')->get()->pluck('total', 'date');
        $dates = collect();
        for ($i = 29; $i >= 0; $i--) { $date = now()->subDays($i)->format('Y-m-d'); $dates->put($date, 0); }
        $daily = $dates->merge($data);
        return [
            'labels' => $daily->keys()->map(fn($d) => Carbon::parse($d)->format('d M'))->toArray(),
            'data' => $daily->values()->toArray()
        ];
    }
}