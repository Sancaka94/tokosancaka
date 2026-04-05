<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon; // <--- WAJIB ADA

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Identifikasi User dari Token
        $user = $request->user();
        $customerId = $user->id_pengguna ?? $user->id;
        $saldo = $user->saldo ?? 0;

        // LOGIKA CEK ADMIN (Mata Dewa)
        $isAdmin = ($customerId == 4 && strtolower($user->role) === 'admin');

        // 2. Siapkan Base Query Statistik Utama
        $queryTotal = DB::table('Pesanan');
        $querySelesai = DB::table('Pesanan')->whereIn('status_pesanan', ['Selesai', 'Tiba di Tujuan']);
        $queryPending = DB::table('Pesanan')->whereIn('status_pesanan', ['pending', 'Menunggu Pembayaran', 'Belum Bayar', 'Diproses']);
        $queryBatal = DB::table('Pesanan')->whereIn('status_pesanan', ['Dibatalkan', 'Batal', 'Retur']);

        if (!$isAdmin) {
            $queryTotal->where('id_pengguna_pembeli', $customerId);
            $querySelesai->where('id_pengguna_pembeli', $customerId);
            $queryPending->where('id_pengguna_pembeli', $customerId);
            $queryBatal->where('id_pengguna_pembeli', $customerId);
        }

        // ==========================================
        // FITUR BARU: FILTER WAKTU DARI REACT NATIVE
        // ==========================================
        $filterWaktu = $request->query('filter_waktu', 'Bulan Ini');
        $now = Carbon::now();

        if ($filterWaktu === 'Hari Ini') {
            $queryTotal->whereDate('tanggal_pesanan', $now->toDateString());
            $querySelesai->whereDate('tanggal_pesanan', $now->toDateString());
            $queryPending->whereDate('tanggal_pesanan', $now->toDateString());
            $queryBatal->whereDate('tanggal_pesanan', $now->toDateString());
        } elseif ($filterWaktu === 'Bulan Ini') {
            $queryTotal->whereYear('tanggal_pesanan', $now->year)->whereMonth('tanggal_pesanan', $now->month);
            $querySelesai->whereYear('tanggal_pesanan', $now->year)->whereMonth('tanggal_pesanan', $now->month);
            $queryPending->whereYear('tanggal_pesanan', $now->year)->whereMonth('tanggal_pesanan', $now->month);
            $queryBatal->whereYear('tanggal_pesanan', $now->year)->whereMonth('tanggal_pesanan', $now->month);
        } elseif ($filterWaktu === 'Bulan Kemarin') {
            $lastMonth = $now->copy()->subMonth();
            $queryTotal->whereYear('tanggal_pesanan', $lastMonth->year)->whereMonth('tanggal_pesanan', $lastMonth->month);
            $querySelesai->whereYear('tanggal_pesanan', $lastMonth->year)->whereMonth('tanggal_pesanan', $lastMonth->month);
            $queryPending->whereYear('tanggal_pesanan', $lastMonth->year)->whereMonth('tanggal_pesanan', $lastMonth->month);
            $queryBatal->whereYear('tanggal_pesanan', $lastMonth->year)->whereMonth('tanggal_pesanan', $lastMonth->month);
        } elseif ($filterWaktu === 'Tahun Ini') {
            $queryTotal->whereYear('tanggal_pesanan', $now->year);
            $querySelesai->whereYear('tanggal_pesanan', $now->year);
            $queryPending->whereYear('tanggal_pesanan', $now->year);
            $queryBatal->whereYear('tanggal_pesanan', $now->year);
        }

        $totalPesanan = $queryTotal->count();
        $pesananSelesai = $querySelesai->count();
        $pesananPending = $queryPending->count();
        $pesananBatal = $queryBatal->count();

        // 3. REKAPITULASI PENGELUARAN EKSPEDISI
        // Bikin nama cache unik berdasarkan filter biar datanya nggak nyangkut
        $namaFilterCache = str_replace(' ', '_', strtolower($filterWaktu));
        $cacheKey = $isAdmin ? 'api_mobile_rekap_exp_admin_all_' . $namaFilterCache : 'api_mobile_rekap_exp_' . $customerId . '_' . $namaFilterCache;

        $rekapEkspedisi = Cache::remember($cacheKey, 600, function () use ($customerId, $isAdmin, $filterWaktu, $now) {
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
                'indah' => ['name' => 'Indah Logistik', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/indah.png'],
                'jtcargo' => ['name' => 'J&T Cargo', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jtcargo.png'],
                'lion' => ['name' => 'Lion Parcel', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/lion.png'],
                'spx' => ['name' => 'SPX Express', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/spx.png'],
                'ninja' => ['name' => 'Ninja Express', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/ninja.png'],
                'anteraja' => ['name' => 'Anteraja', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/anteraja.png'],
                'sentral' => ['name' => 'Sentral Cargo', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/centralcargo.png'],
                'borzo' => ['name' => 'Borzo', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/borzo.png'],
            ];

            $stats = [];
            foreach ($courierMap as $code => $info) {
                $stats[$info['name']] = [
                    'nama' => $info['name'],
                    'logo' => $info['logo_url'],
                    'total_order' => 0,
                    'total_pengeluaran' => 0,
                ];
            }

            $ordersQuery = DB::table('Pesanan')
                        ->select('expedition', 'shipping_cost', 'insurance_cost', 'cod_fee', 'ansuransi')
                        ->whereNotNull('expedition');

            if (!$isAdmin) {
                $ordersQuery->where('id_pengguna_pembeli', $customerId);
            }

            // FILTER WAKTU UNTUK EKSPEDISI
            if ($filterWaktu === 'Hari Ini') {
                $ordersQuery->whereDate('tanggal_pesanan', $now->toDateString());
            } elseif ($filterWaktu === 'Bulan Ini') {
                $ordersQuery->whereYear('tanggal_pesanan', $now->year)->whereMonth('tanggal_pesanan', $now->month);
            } elseif ($filterWaktu === 'Bulan Kemarin') {
                $lastMonth = $now->copy()->subMonth();
                $ordersQuery->whereYear('tanggal_pesanan', $lastMonth->year)->whereMonth('tanggal_pesanan', $lastMonth->month);
            } elseif ($filterWaktu === 'Tahun Ini') {
                $ordersQuery->whereYear('tanggal_pesanan', $now->year);
            }

            $orders = $ordersQuery->get();

            foreach ($orders as $order) {
                $parts = explode('-', $order->expedition);
                if (count($parts) >= 2) {
                    $dbCode = strtolower($parts[1]);
                    if (isset($courierMap[$dbCode])) {
                        $displayName = $courierMap[$dbCode]['name'];

                        $stats[$displayName]['total_order']++;
                        $ongkir = $order->shipping_cost ?? 0;
                        $asuransi = ($order->ansuransi == 'iya' || $order->insurance_cost > 0) ? ($order->insurance_cost ?? 0) : 0;
                        $cod = $order->cod_fee ?? 0;

                        $stats[$displayName]['total_pengeluaran'] += ($ongkir + $asuransi + $cod);
                    }
                }
            }

            return collect($stats)->filter(function ($item) {
                return $item['total_order'] > 0;
            })->sortByDesc('total_order')->values()->all();
        });

        return response()->json([
            'success' => true,
            'data' => [
                'namaLengkap' => $user->nama_lengkap,
                'role' => $user->role,
                'saldo_format' => number_format($saldo, 0, ',', '.'),
                'statistik' => [
                    'totalPesanan' => $totalPesanan,
                    'pesananSelesai' => $pesananSelesai,
                    'pesananPending' => $pesananPending,
                    'pesananBatal' => $pesananBatal,
                ],
                'rekapEkspedisi' => $rekapEkspedisi
            ]
        ], 200);
    }
}
