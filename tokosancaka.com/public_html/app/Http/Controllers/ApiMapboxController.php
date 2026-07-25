<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiMapboxController extends Controller
{
    protected $mapboxToken;

    public function __construct()
    {
        // Memanggil token secret untuk transaksi API sisi server
        $this->mapboxToken = \App\Models\Api::getValue('MAPBOX_SECRET_TOKEN', 'global');
    }

    /**
     * Menghitung jarak rute, waktu tempuh, dan estimasi tarif antara Toko dan Pelanggan.
     */
    public function calculateRoute(Request $request)
    {
        // === LOG 1: AWAL REQUEST DARI FRONTEND ===
        Log::info('--- [MAPBOX API] MEMULAI KALKULASI RUTE ---');
        Log::info('Input Koordinat dari Frontend:', $request->all());

        // 1. Validasi input koordinat
        $request->validate([
            'origin_lat' => 'required|numeric',
            'origin_lng' => 'required|numeric',
            'dest_lat'   => 'required|numeric',
            'dest_lng'   => 'required|numeric',
        ]);

        // Cek Apakah Token Terbaca (Mencegah pengiriman request kosong ke Mapbox)
        if (empty($this->mapboxToken)) {
            Log::error('[MAPBOX API] GAGAL: Secret Token Mapbox kosong atau bernilai null! Pastikan sudah disetting di panel admin.');
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan konfigurasi Token Peta di server. Hubungi admin.',
            ], 400);
        }

        $originLat = $request->origin_lat;
        $originLng = $request->origin_lng;
        $destLat   = $request->dest_lat;
        $destLng   = $request->dest_lng;

        // 2. Setup Parameter Mapbox
        $profile = 'mapbox/driving';

        // PENTING: Mapbox mewajibkan format {longitude},{latitude}.
        $coordinates = "{$originLng},{$originLat};{$destLng},{$destLat}";
        $url = "https://api.mapbox.com/directions/v5/{$profile}/{$coordinates}";

        $queryParams = [
            'access_token' => $this->mapboxToken,
            'geometries'   => 'geojson',
            'overview'     => 'simplified',
            'steps'        => 'false',
        ];

        // === LOG 2: PAYLOAD YANG AKAN DIKIRIM KE MAPBOX ===
        Log::info('[MAPBOX API] Mengirim Request ke Mapbox:', [
            'url_endpoint'  => $url,
            'coordinates'   => $coordinates,
            'token_snippet' => '***' . substr($this->mapboxToken, -5), // Hanya tampilkan 5 huruf terakhir token demi keamanan
        ]);

        try {
            // 3. Eksekusi Request ke Mapbox API
            $response = Http::withHeaders([
                'Referer' => url('/'),
            ])->timeout(10)->get($url, $queryParams);
            $data = $response->json();

            // === LOG 3: RESPON MENTAH DARI SERVER MAPBOX ===
            Log::info('[MAPBOX API] Respon Diterima dari Mapbox:', [
                'http_status' => $response->status(),
                'body'        => $data
            ]);

            // 4. Proses Respon Mapbox
            if ($response->successful() && isset($data['code']) && $data['code'] === 'Ok') {

                $route = $data['routes'][0];

                $distanceMeters = $route['distance'];
                $durationSeconds = $route['duration'];

                $distanceKm = round($distanceMeters / 1000, 2);
                $durationMinutes = ceil($durationSeconds / 60);

                // --- [LOGIKA TARIF KURIR LOKAL SANCAKA] ---
                // --- [LOGIKA TARIF KURIR LOKAL SANCAKA DINAMIS] ---
                // Mengambil nilai dari database, dengan fallback nilai default jika kosong
                $baseFare = (float) \App\Models\Api::getValue('SANCAKA_OJEK_BASE_FARE', 'global', 10000);
                $perKmRate = (float) \App\Models\Api::getValue('SANCAKA_OJEK_PER_KM', 'global', 2500);
                $estimatedCost = $baseFare;

                if ($distanceKm > 2) {
                    $extraDistance = $distanceKm - 2;
                    $estimatedCost += ($extraDistance * $perKmRate);
                }
                // ------------------------------------------

                Log::info('[MAPBOX API] SUKSES: Jarak dikalkulasi.', ['jarak_km' => $distanceKm, 'tarif' => $estimatedCost]);

                return response()->json([
                    'success' => true,
                    'message' => 'Rute berhasil dihitung.',
                    'data' => [
                        'distance_meters'   => $distanceMeters,
                        'distance_km'       => $distanceKm,
                        'duration_seconds'  => $durationSeconds,
                        'duration_minutes'  => $durationMinutes,
                        'estimated_cost'    => round($estimatedCost),
                        'geometry'          => $route['geometry'] ?? null,
                    ]
                ]);
            }

            // === LOG 4: JIKA HTTP SUKSES TAPI CODE BUKAN 'Ok' ATAU SERVER MENOLAK (FORBIDDEN) ===
            Log::error('[MAPBOX API] DITOLAK ATAU TIDAK ADA RUTE:', [
                'http_status' => $response->status(),
                'error_data'  => $data
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghitung rute (' . ($data['message'] ?? 'Alasan tidak diketahui') . ').',
            ], 400);

        } catch (\Exception $e) {
            // === LOG 5: JIKA KONEKSI INTERNET SERVER TERPUTUS/TIMEOUT ===
            Log::error('[MAPBOX API] Exception Connection: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat menghubungi server peta (Timeout/Koneksi Terputus).',
            ], 500);
        }
    }

    /**
     * Endpoint khusus untuk menarik data cetak resi (Mapping data agar tidak undefined/NaN)
     */
    public function get_order_resi_detail(Request $request, $order_id)
    {
        Log::info("=== [API MAPBOX] REQUEST GET RESI DETAIL MASUK ===");

        try {
            $user = $request->user();
            $userId = $user ? $user->id_pengguna : null;
            $userRole = $user ? ($user->role ?? 'Pelanggan') : 'Pelanggan';

            $query = DB::table('order_ojek_online')
                ->join('Pengguna as customer', 'order_ojek_online.customer_id', '=', 'customer.id_pengguna')
                ->leftJoin('registrasi_driver_sancaka as driver', 'order_ojek_online.driver_id', '=', 'driver.id_pengguna')
                ->leftJoin('Pengguna as admin_user', 'order_ojek_online.driver_id', '=', 'admin_user.id_pengguna')
                ->where('order_ojek_online.order_id', $order_id)
                ->select(
                    'order_ojek_online.*',
                    'customer.nama_lengkap as customer_name',
                    'customer.no_wa as customer_phone',
                    'driver.nama_lengkap as driver_name',
                    'driver.nomor_wa as driver_phone',
                    'driver.latitude as driver_lat',
                    'driver.longitude as driver_lng',
                    'driver.is_active_map as driver_is_online',
                    'driver.foto_motor',
                    'admin_user.latitude as admin_lat',
                    'admin_user.longitude as admin_lng'
                );

            // Kunci keamanan
            if ($userId != 4 && $userRole !== 'Admin') {
                $query->where(function($q) use ($userId) {
                    $q->where('order_ojek_online.customer_id', $userId)
                      ->orWhere('order_ojek_online.driver_id', $userId);
                });
            }

            $order = $query->first();

            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Pesanan tidak ditemukan atau akses ditolak.'], 404);
            }

            // Jika yang ambil order adalah Admin (ID 4)
            if ($order->driver_id == 4) {
                $order->driver_name = "Pusat Radar Sancaka";
                $order->driver_phone = "08819435180";
                $order->driver_lat = $order->admin_lat ?? -7.4025;
                $order->driver_lng = $order->admin_lng ?? 111.4558;
                $order->driver_is_online = 1;
            }

            // =========================================================================
            // MAPPING DATA KHUSUS CETAK RESI
            // =========================================================================

            $order->resi = $order->order_id;
            $order->nomor_invoice = $order->order_id;
            $order->expedition = 'Sancaka-Express-Reguler';

            // Pengirim
            $order->sender_name = $order->customer_name ?? 'Pengirim';
            $order->sender_phone = $order->customer_phone ?? '-';
            $order->sender_address = $order->origin_address ?? '-';
            $order->sender_village = '-';
            $order->sender_district = '-';
            $order->sender_regency = '-';
            $order->sender_postal_code = '-';

            // Penerima
            $order->receiver_name = 'Penerima Paket';
            $order->receiver_phone = '-';
            $order->receiver_address = $order->dest_address ?? '-';
            $order->receiver_village = '-';
            $order->receiver_district = '-';
            $order->receiver_regency = 'NGAWI';
            $order->receiver_postal_code = '-';

            // Spesifikasi Paket
            $order->weight = 1000;
            $order->item_price = 0;
            $order->insurance = false;
            $order->length = 10;
            $order->width = 10;
            $order->height = 10;
            $order->item_description = $order->catatan ?? 'Paket Sancaka Express';

            // Tarif (Konversi pasti angka/Float)
            $order->price = (float) $order->tarif;
            $order->shipping_cost = (float) $order->tarif;

            $order->created_at = \Carbon\Carbon::parse($order->created_at)->format('d M Y H:i:s');

            // Ekstrak berat gram dari catatan (Jika ada format (1000g))
            if ($order->catatan && preg_match('/\((\d+)g\)/i', $order->catatan, $matches)) {
                $order->weight = (int) $matches[1];
            }

            return response()->json(['success' => true, 'data' => $order]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Sistem Error: ' . $e->getMessage()], 500);
        }
    }
}
