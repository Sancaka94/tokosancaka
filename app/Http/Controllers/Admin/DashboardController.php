<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Pesanan;
use App\Models\TopUp;
use App\Models\ScannedPackage;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Notifications\DatabaseNotification;

class DashboardController extends Controller
{
    public function index()
    {
        $cacheDuration = 600; // Durasi cache dalam detik (10 menit)

        // --- Mengambil Statistik Utama (dengan Caching) ---
        $stats = Cache::remember('admin_dashboard_stats_v9', $cacheDuration, function () {
            // ✅ PERBAIKAN: Logika perhitungan pendapatan
            $totalTopUp = TopUp::where('status', 'success')->sum('amount');
            $totalOngkirPesanan = Pesanan::sum('total_harga_barang'); // Asumsi 'total_harga_barang' adalah pendapatan ongkir

            // ✅ PERBAIKAN: Menggunakan 'role' (lowercase) sesuai standar
            return [
                'totalPendapatan' => $totalTopUp + $totalOngkirPesanan,
                'totalPesanan' => Pesanan::count(),
                'jumlahToko' => User::where('role', 'Toko')->count(),
                'penggunaBaru' => User::where('role', 'Pelanggan')->where('created_at', '>=', now()->subDays(30))->count(),
            ];
        });

        // --- Mengambil Notifikasi untuk Flasher (dengan Caching) ---
        $notifications = Cache::remember('admin_dashboard_notifications_v7', 60, function () {
            // ✅ PERBAIKAN: Menggunakan 'role' (lowercase)
            return [
                'pendaftaranBaru' => User::where('role', 'Pelanggan')->where('status', 'pending')->count(),
                'pesananBaru' => Pesanan::where('status_pesanan', 'Menunggu Pickup')->count(),
                'spxScanBaru' => ScannedPackage::whereDate('created_at', today())->count(),
                'riwayatScanBaru' => ScannedPackage::whereDate('created_at', today())->count(), // Ini mungkin duplikat, sesuaikan jika perlu
            ];
        });

        // --- Mengambil Data Grafik Pendapatan (dengan Caching) ---
        $chartData = Cache::remember('admin_dashboard_revenue_chart_v4', $cacheDuration, function () {
            // Menggunakan 'created_at' sebagai fallback jika 'tanggal_pesanan' tidak ada
            $salesData = Pesanan::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('sum(total_harga_barang) as total')
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
                'labels' => $dailySales->keys()->map(function($date) {return Carbon::parse($date)->format('d M');})->toArray(),
                'data' => $dailySales->values()->toArray(),
            ];
        });

        // --- Mengambil Data Grafik Scan SPX (dengan Caching) ---
        $spxChartData = Cache::remember('admin_dashboard_spx_chart_from_scans_v4', $cacheDuration, function () {
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
        $pesananTerbaru = Cache::remember('admin_dashboard_recent_orders_v4', $cacheDuration, function () {
            // Menggunakan 'created_at' sebagai fallback
            return Pesanan::with('pembeli')->latest('created_at')->take(5)->get();
        });

        $rekapEkspedisi = Cache::remember('admin_dashboard_rekap_ekspedisi_v9', $cacheDuration, function () {
            // Daftar ekspedisi dan logonya
            $logoMap = [
                'Sicepat' => 'https://assets.bukalapak.com/beagle/images/courier_logo/sicepat.png',
                'J&T Express' => 'https://upload.wikimedia.org/wikipedia/commons/0/01/J%26T_Express_logo.svg',
                'SPX Express' => 'https://placehold.co/150x60/EE4D2D/FFFFFF?text=SPX+Express',
                'JNE' => 'https://upload.wikimedia.org/wikipedia/commons/9/92/New_Logo_JNE.png',
                'Indah Cargo' => 'https://assets.autokirim.com/courier/indah.png',
                'Sancaka Express' => 'https://tokosancaka.biz.id/storage/uploads/sancaka.png',
                'LEX (Lazada Express)' => 'https://assets.autokirim.com/courier/lex.png',
                'Lion Parcel' => 'https://assets.bukalapak.com/beagle/images/courier_logo/lionparcel.png',
                'Ninja Xpress' => 'https://assets.bukalapak.com/beagle/images/courier_logo/ninja-express.png',
                'J&T Cargo' => 'https://assets.autokirim.com/courier/jt-cargo.png',
                'Wahana Express' => 'https://placehold.co/150x60/facc15/FFFFFF?text=Wahana',
                'POS Indonesia' => 'https://www.posindonesia.co.id/_next/image?url=https%3A%2F%2Fadmin-piol.posindonesia.co.id%2Fmedia%2FUntitled%20design%20(7).png&w=384&q=75',
                'Sentral Cargo' => 'https://assets.autokirim.com/courier/sc.png',
                'Paxel' => 'https://assets.autokirim.com/courier/paxel.png',
                'ID Express' => 'https://assets.bukalapak.com/beagle/images/courier_logo/id-express.png',
                'SAP Express' => 'https://assets.bukalapak.com/beagle/images/courier_logo/sap.png',
            ];
            
            $ekspedisiData = Pesanan::query()
                ->selectRaw('expedition as nama, COUNT(id_pesanan) as total_order, COUNT(DISTINCT id_pengguna_pembeli) as total_pelanggan, SUM(total_harga_barang) as total_profit')
                ->whereNotNull('expedition')->where('expedition', '!=', '')->groupBy('expedition')->get()->keyBy('nama');
            
            return collect($logoMap)->map(function ($logoUrl, $namaEkspedisi) use ($ekspedisiData) {
                $data = $ekspedisiData->get($namaEkspedisi);
                return (object) [
                    'nama' => $namaEkspedisi, 'logo' => $logoUrl,
                    'total_order' => optional($data)->total_order ?? 0,
                    'total_pelanggan' => optional($data)->total_pelanggan ?? 0,
                    'total_profit' => optional($data)->total_profit ?? 0,
                ];
            })->sortByDesc('total_order')->values();
        });

        // --- Mengambil data notifikasi terbaru untuk tabel ---
        $recentNotifications = DatabaseNotification::latest()->take(10)->get();

        // --- Mengambil data slider dari tabel settings ---
        $sliderData = Setting::where('key', 'dashboard_slider')->first();
        $slides = $sliderData ? json_decode($sliderData->value, true) : [];

        // --- Melewatkan semua data ke view ---
        return view('admin.dashboard', array_merge($stats, [
            'chartData' => $chartData,
            'spxChartData' => $spxChartData,
            'pesananTerbaru' => $pesananTerbaru,
            'rekapEkspedisi' => $rekapEkspedisi,
            'notifications' => $notifications,
            'recentNotifications' => $recentNotifications,
            'slides' => $slides,
        ]));
    }
}
