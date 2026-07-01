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
                $baseFare = 10000;
                $perKmRate = 2500;
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
}
