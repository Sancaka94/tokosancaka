<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Pesanan;
use App\Models\ScannedPackage;
use App\Models\Setting;
use App\Models\Store;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Http\View\Composers\CustomerLayoutComposer;

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman dashboard untuk customer dengan data lengkap.
     */
    public function index()
    {
        $customer = Auth::user();
        $customerId = $customer->id_pengguna;

        // --- PENGAMBILAN DATA STATISTIK ---
        $saldo = $customer->saldo;
        
        $semuaPesananQuery = Pesanan::where('id_pengguna_pembeli', $customerId);

        $totalPesanan = (clone $semuaPesananQuery)->count();
        $pesananSelesai = (clone $semuaPesananQuery)->where('status_pesanan', 'Tiba di Tujuan')->count();
        
        // ✅ FIX: Menggunakan kolom 'status_pesanan' yang benar, bukan 'status_pembayaran'.
        $pesananPending = (clone $semuaPesananQuery)->whereIn('status_pesanan', ['pending', 'Menunggu Pembayaran'])->count();
        
        $recentOrders = (clone $semuaPesananQuery)->latest('tanggal_pesanan')->take(5)->get();
        
        // Ganti 'ScannedPackage' dengan nama model scan Anda yang sebenarnya jika berbeda
        $recentSpxScans = ScannedPackage::where('user_id', $customerId)->latest()->take(5)->get();

        // --- PENGAMBILAN DATA UNTUK GRAFIK ---
        $orderChartData = $this->getOrderChartData($customerId);
        $spxChartData = $this->getSpxScanChartData($customerId);

        // Mengambil data slider dari tabel settings
        $sliderData = Setting::where('key', 'dashboard_slider')->first();
        $slides = $sliderData ? json_decode($sliderData->value, true) : [];

        $data = [
            'saldo' => $saldo,
            'totalPesanan' => $totalPesanan,
            'pesananSelesai' => $pesananSelesai,
            'pesananPending' => $pesananPending,
            'recentOrders' => $recentOrders,
            'recentSpxScans' => $recentSpxScans,
            'orderChartLabels' => json_encode($orderChartData['labels']),
            'orderChartValues' => json_encode($orderChartData['values']),
            'spxChartLabels' => json_encode($spxChartData['labels']),
            'spxChartValues' => json_encode($spxChartData['values']),
            'slides' => $slides,
        ];

        return view('customer.dashboard', $data);
    }

    /**
     * Menyiapkan data untuk grafik pesanan 7 hari terakhir (Metode Efisien).
     */
    private function getOrderChartData($customerId)
    {
        $orderData = Pesanan::where('id_pengguna_pembeli', $customerId)
            ->where('tanggal_pesanan', '>=', Carbon::now()->subDays(6)->startOfDay())
            ->select(DB::raw('DATE(tanggal_pesanan) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $values = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateString = $date->format('Y-m-d');
            $labels[] = $date->isoFormat('ddd, D/M');
            $values[] = $orderData->get($dateString)->count ?? 0;
        }
        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Menyiapkan data untuk grafik scan SPX 7 hari terakhir (Metode Efisien).
     */
    private function getSpxScanChartData($customerId)
    {
        $spxData = ScannedPackage::where('user_id', $customerId)
            ->where('created_at', '>=', Carbon::now()->subDays(6)->startOfDay())
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->get()
            ->keyBy('date');
            
        $labels = [];
        $values = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateString = $date->format('Y-m-d');
            $labels[] = $date->isoFormat('ddd, D/M');
            $values[] = $spxData->get($dateString)->count ?? 0;
        }
        return ['labels' => $labels, 'values' => $values];
    }
    
    // --- LOGIKA PENDAFTARAN SELLER BARU ---

    /**
     * Menampilkan halaman formulir pendaftaran seller.
     */
    public function showSellerRegistrationForm()
    {
        if (Auth::user()->store) {
            return redirect()->route('customer.dashboard')->with('info', 'Anda sudah terdaftar sebagai seller.');
        }
        return view('customer.seller-register');
    }

    /**
     * Menyimpan data pendaftaran toko dari formulir.
     */
    public function registerSeller(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:stores'],
            'description' => ['required', 'string', 'min:20'],
        ]);

        Store::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
        ]);

        $user = Auth::user();
        $user->role = 'Seller';
        $user->save();

        return redirect()->route('customer.dashboard')->with('success', 'Selamat! Toko Anda berhasil dibuat.');
    }
}

