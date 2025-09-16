<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Pesanan;
use App\Models\ScannedPackage;
use App\Models\Setting;
use App\Models\Store; // <-- DITAMBAHKAN
use Illuminate\Support\Str; // <-- DITAMBAHKAN
use Carbon\Carbon;


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
        $saldo = $this->calculateBalance($customerId);
        $semuaPesananQuery = Pesanan::where('id_pengguna_pembeli', $customerId);
        $totalPesanan = (clone $semuaPesananQuery)->count();
        $pesananSelesai = (clone $semuaPesananQuery)->where('status_pesanan', 'Tiba di Tujuan')->count();
        $pesananPending = (clone $semuaPesananQuery)->whereIn('status_pesanan', ['pending', 'Menunggu Pembayaran'])->count();
        $pesananBulanIni = (clone $semuaPesananQuery)->whereMonth('created_at', now()->month)
                                                    ->whereYear('created_at', now()->year)
                                                    ->count();
        $recentOrders = (clone $semuaPesananQuery)->latest('tanggal_pesanan')
                                                  ->take(5)
                                                  ->get();
        $totalScanSpx = ScannedPackage::where('user_id', $customerId)->count();
        
        $recentSpxScans = ScannedPackage::where('user_id', $customerId)
                                        ->latest()
                                        ->take(5)
                                        ->get();

        // --- PENGAMBILAN DATA UNTUK GRAFIK ---
        $orderChartData = $this->getOrderChartData($customerId);
        $spxChartData = $this->getSpxScanChartData($customerId);

        // ✅ 2. Mengambil data slider dari tabel settings
        $sliderData = Setting::where('key', 'dashboard_slider')->first();
        $slides = $sliderData ? json_decode($sliderData->value, true) : [];

        $data = [
            'customer' => $customer,
            'saldo' => $saldo,
            'totalPesanan' => $totalPesanan,
            'pesananSelesai' => $pesananSelesai,
            'pesananPending' => $pesananPending,
            'pesananBulanIni' => $pesananBulanIni,
            'totalScanSpx' => $totalScanSpx,
            'recentOrders' => $recentOrders,
            'recentSpxScans' => $recentSpxScans,
            'orderChartLabels' => json_encode($orderChartData['labels']),
            'orderChartValues' => json_encode($orderChartData['values']),
            'spxChartLabels' => json_encode($spxChartData['labels']),
            'spxChartValues' => json_encode($spxChartData['values']),
            'slides' => $slides, // ✅ 3. Melewatkan data slider ke view
        ];

        return view('customer.dashboard', $data);
    }

    /**
     * Menghitung saldo customer dari database.
     */
    private function calculateBalance($customerId)
    {
        $totalPemasukan = DB::table('top_ups')
                            ->where('customer_id', $customerId)
                            ->where('status', 'success')
                            ->sum('amount');
        
        $totalPengeluaran = DB::table('pesanan')
                                ->where('id_pengguna_pembeli', $customerId)
                                ->sum('total_harga_barang');

        return $totalPemasukan - $totalPengeluaran;
    }

    /**
     * Menyiapkan data untuk grafik pesanan 7 hari terakhir.
     */
    private function getOrderChartData($customerId)
    {
        $labels = [];
        $values = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->isoFormat('ddd, D/M');
            
            $orderCount = Pesanan::where('id_pengguna_pembeli', $customerId)
                                 ->whereDate('created_at', $date)
                                 ->count();
            $values[] = $orderCount;
        }
        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Menyiapkan data untuk grafik scan SPX 7 hari terakhir.
     */
    private function getSpxScanChartData($customerId)
    {
        $labels = [];
        $values = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->isoFormat('ddd, D/M');
            
            $scanCount = ScannedPackage::where('user_id', $customerId)
                                       ->whereDate('created_at', $date)
                                       ->count();
            $values[] = $scanCount;
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
        return view('customer.seller-register'); // View form pendaftaran
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

        // 1. Buat toko baru
        Store::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
        ]);

        // 2. Update role user dari 'Pelanggan' menjadi 'Seller'
        $user = Auth::user();
        $user->role = 'Seller'; // Pastikan nama role ini sesuai dengan yang Anda gunakan di RoleMiddleware
        $user->save();

        return redirect()->route('customer.dashboard')->with('success', 'Selamat! Toko Anda berhasil dibuat.');
    }
}
