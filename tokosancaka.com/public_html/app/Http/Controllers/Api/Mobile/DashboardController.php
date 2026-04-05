<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache; // Wajib import Cache

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Identifikasi User dari Token
        $user = $request->user();
        $customerId = $user->id_pengguna ?? $user->id;
        $saldo = $user->saldo ?? 0;

        // ==========================================
        // LOGIKA BARU: CEK ADMIN (Mata Dewa)
        // ==========================================
        $isAdmin = ($customerId == 4 && strtolower($user->role) === 'admin');

        // 2. Hitung Statistik Utama (Admin lihat semua, User lihat miliknya)
        $queryTotal = DB::table('Pesanan');
        $querySelesai = DB::table('Pesanan')->whereIn('status_pesanan', ['Selesai', 'Tiba di Tujuan']);
        $queryPending = DB::table('Pesanan')->whereIn('status_pesanan', ['pending', 'Menunggu Pembayaran', 'Belum Bayar', 'Diproses']);
        $queryBatal = DB::table('Pesanan')->whereIn('status_pesanan', ['Dibatalkan', 'Batal', 'Retur']);

        // Kunci query jika BUKAN Admin
        if (!$isAdmin) {
            $queryTotal->where('id_pengguna_pembeli', $customerId);
            $querySelesai->where('id_pengguna_pembeli', $customerId);
            $queryPending->where('id_pengguna_pembeli', $customerId);
            $queryBatal->where('id_pengguna_pembeli', $customerId);
        }

        $totalPesanan = $queryTotal->count();
        $pesananSelesai = $querySelesai->count();
        $pesananPending = $queryPending->count();
        $pesananBatal = $queryBatal->count();

        // 3. REKAPITULASI PENGELUARAN EKSPEDISI (Cache dipisah untuk Admin dan User)
        $cacheKey = $isAdmin ? 'api_mobile_rekap_exp_admin_all' : 'api_mobile_rekap_exp_' . $customerId;

        $rekapEkspedisi = Cache::remember($cacheKey, 600, function () use ($customerId, $isAdmin) {

            // Master List Ekspedisi (Sesuai Web)
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
                'indah' => ['name' => 'Indah Logistik', 'logo_url' => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEicOAaLoH2eElQ93_gbkzhvk4dRhWVlk5wQsGgilihIB58321aHchlJLdjyz1ToS25P_nWrHJ_E4QBiW_OVlI7tQt7cZ5I0HZqk6StS7jZltLVvDXp2d5ZDLB9yklhV4x6z2iXyURURDv_unhf-U6vyiD_8to9OC4PBwMwyU_5wAqOiCl6tKiaTA-ri1Q/s851/Logo%20Indah%20Logistik%20Cargo@0.5x.png'],
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

            // Ambil data pesanan (Lepas kunci untuk Admin)
            $ordersQuery = DB::table('Pesanan')
                        ->select('expedition', 'shipping_cost', 'insurance_cost', 'cod_fee', 'ansuransi')
                        ->whereNotNull('expedition');

            if (!$isAdmin) {
                $ordersQuery->where('id_pengguna_pembeli', $customerId);
            }

            $orders = $ordersQuery->get();

            // Hitung Data
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

            // Filter agar HANYA mengirim ekspedisi yang memiliki order (> 0) ke HP
            return collect($stats)->filter(function ($item) {
                return $item['total_order'] > 0;
            })->sortByDesc('total_order')->values()->all();
        });

        // 4. Kembalikan Response JSON Lengkap
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
                'rekapEkspedisi' => $rekapEkspedisi // Tambahan Data Baru
            ]
        ], 200);
    }
}
