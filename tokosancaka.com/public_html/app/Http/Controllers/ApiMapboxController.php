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
            $this->mapboxToken = env('MAPBOX_TOKEN');
        }

    /**
     * Menghitung jarak rute, waktu tempuh, dan estimasi tarif antara Toko dan Pelanggan.
     */
    public function calculateRoute(Request $request)
    {
        // 1. Validasi input koordinat
        $request->validate([
            'origin_lat' => 'required|numeric',
            'origin_lng' => 'required|numeric',
            'dest_lat'   => 'required|numeric',
            'dest_lng'   => 'required|numeric',
        ]);

        $originLat = $request->origin_lat;
        $originLng = $request->origin_lng;
        $destLat   = $request->dest_lat;
        $destLng   = $request->dest_lng;

        // 2. Setup Parameter Mapbox
        // Profil mapbox/driving akan mencari rute mobil tercepat[cite: 10, 11].
        // Bisa juga diganti ke 'mapbox/driving-traffic' untuk rute dengan mempertimbangkan macet[cite: 7, 8].
        $profile = 'mapbox/driving';

        // PENTING: Mapbox mewajibkan format {longitude},{latitude}.
        $coordinates = "{$originLng},{$originLat};{$destLng},{$destLat}";
        $url = "https://api.mapbox.com/directions/v5/{$profile}/{$coordinates}";

        try {
            // 3. Eksekusi Request ke Mapbox API
            $response = Http::timeout(10)->get($url, [
                'access_token' => $this->mapboxToken,
                'geometries'   => 'geojson',    // Mengembalikan jalur untuk digambar di peta [cite: 68, 69]
                'overview'     => 'simplified', // Mengembalikan geometri yang disederhanakan [cite: 74, 75]
                'steps'        => 'false',      // Set ke 'true' jika Anda butuh instruksi belok kiri/kanan [cite: 86]
            ]);

            $data = $response->json();

            // 4. Proses Respon Mapbox
            // Mapbox mengembalikan code 'Ok' jika rute berhasil ditemukan [cite: 165]
            if ($response->successful() && isset($data['code']) && $data['code'] === 'Ok') {

                // Ambil rute pertama (rute terbaik yang direkomendasikan Mapbox) [cite: 174]
                $route = $data['routes'][0];

                // Mapbox selalu mereturn distance dalam satuan Meter [cite: 366] dan duration dalam Detik [cite: 365]
                $distanceMeters = $route['distance'];
                $durationSeconds = $route['duration'];

                // Konversi agar lebih mudah dibaca/diolah
                $distanceKm = round($distanceMeters / 1000, 2);
                $durationMinutes = ceil($durationSeconds / 60);

                // --- [LOGIKA TARIF KURIR LOKAL SANCAKA] ---
                // Silakan sesuaikan rumus ini dengan model bisnis Anda.
                // Contoh: Jarak 0-2 KM (Tarif Dasar) = Rp 10.000. Jarak selanjutnya = Rp 2.500 / KM.
                $baseFare = 10000;
                $perKmRate = 2500;
                $estimatedCost = $baseFare;

                if ($distanceKm > 2) {
                    $extraDistance = $distanceKm - 2;
                    $estimatedCost += ($extraDistance * $perKmRate);
                }
                // ------------------------------------------

                return response()->json([
                    'success' => true,
                    'message' => 'Rute berhasil dihitung.',
                    'data' => [
                        'distance_meters'   => $distanceMeters,
                        'distance_km'       => $distanceKm,
                        'duration_seconds'  => $durationSeconds,
                        'duration_minutes'  => $durationMinutes,
                        'estimated_cost'    => round($estimatedCost),
                        // Geometry ini bisa dikirim ke Frontend untuk menggambar garis biru rute di Peta
                        'geometry'          => $route['geometry'] ?? null,
                    ]
                ]);
            }

            // Jika Mapbox gagal menemukan jalan (Contoh: beda pulau / dipisah laut) [cite: 678, 679]
            Log::error('Mapbox API Failed (No Route):', $data);
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menemukan rute darat untuk lokasi tersebut.',
            ], 400);

        } catch (\Exception $e) {
            // Menangkap error jika server Mapbox down atau jaringan putus
            Log::error('Mapbox Connection Exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat menghubungi server peta.',
            ], 500);
        }
    }
}
