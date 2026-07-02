<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Pesanan;
use App\Models\Api; // Asumsi model Config Anda

class ApiMapboxController extends Controller
{
    /**
     * Mengecek tarif Ojek Online & Sancaka Express
     */
    public function cek_tarif(Request $request)
    {
        $latAsal = $request->input('sender_lat');
        $lngAsal = $request->input('sender_lng');
        $latTujuan = $request->input('receiver_lat');
        $lngTujuan = $request->input('receiver_lng');
        $layanan = $request->input('layanan'); // 'express' atau 'ojek'
        $beratGram = (float) $request->input('weight', 1000);

        if (!$latAsal || !$lngAsal || !$latTujuan || !$lngTujuan) {
            return response()->json(['status' => false, 'message' => 'Koordinat tidak lengkap.']);
        }

        // Tembak API Mapbox Backend
        $mapboxToken = Api::getValue('MAPBOX_SECRET_TOKEN', 'global', env('MAPBOX_TOKEN'));
        $url = "https://api.mapbox.com/directions/v5/mapbox/driving/{$lngAsal},{$latAsal};{$lngTujuan},{$latTujuan}";

        try {
            $response = Http::get($url, [
                'access_token' => $mapboxToken,
                'geometries'   => 'geojson',
                'overview'     => 'simplified'
            ]);

            if (!$response->successful() || empty($response['routes'][0])) {
                return response()->json(['status' => false, 'message' => 'Gagal mendapatkan rute dari Mapbox']);
            }

            $route = $response['routes'][0];
            $distanceKm = $route['distance'] / 1000;
            $durationMin = ceil($route['duration'] / 60);

            // LOGIKA TARIF SESUAI KODE REFERENSI
            if ($layanan == 'ojek') {
                $baseFare = (float) Api::getValue('SANCAKA_OJEK_BASE_FARE', 'global', 5000);
                $pricePerKm = (float) Api::getValue('SANCAKA_OJEK_PER_KM', 'global', 2500);
                $totalCost = $baseFare + ($distanceKm * $pricePerKm);
            } else {
                // Express Same Day
                $baseFare = (float) Api::getValue('SANCAKA_EXPRESS_BASE_FARE', 'global', 3000);
                $pricePerKm = (float) Api::getValue('SANCAKA_EXPRESS_PER_KM', 'global', 1000);
                $pricePerKg = (float) Api::getValue('SANCAKA_EXPRESS_PER_KG', 'global', 1000);

                $weightKg = max(1, ceil($beratGram / 1000));
                $totalCost = $baseFare + ($distanceKm * $pricePerKm) + ($weightKg * $pricePerKg);
            }

            // Pembulatan Kelipatan Rp 500 (Aturan Sancaka)
            $finalCost = (int) (ceil($totalCost / 500) * 500);

            return response()->json([
                'status' => true,
                'data' => [
                    'jarak_km' => round($distanceKm, 2),
                    'waktu_menit' => $durationMin,
                    'tarif' => $finalCost
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("API Mapbox Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Internal Server Error']);
        }
    }

    /**
     * Membuat Pesanan Baru (Create Order)
     */
    public function create_order(Request $request)
    {
        $user_id = $request->input('user_id');
        $layanan = $request->input('layanan'); // express / ojek
        $payment_method = strtoupper($request->input('payment_method'));

        // ATURAN VALIDASI PEMBAYARAN DI BACKEND
        if ($payment_method === 'CASH') {
            // KHUSUS USER ID 4 ADMIN BISA CASH
            if ((int)$user_id !== 4) {
                return response()->json([
                    'status' => false,
                    'message' => 'Akses Ditolak. Pembayaran Cash hanya untuk Admin Sancaka.'
                ], 403);
            }
        }

        // Siapkan Data
        $dataPesanan = [
            'user_id' => $user_id,
            'layanan' => $layanan,
            'status' => 'Mencari Driver',
            'metode_pembayaran' => $payment_method,
            'tanggal_pesanan' => now(),
            // Masukkan data Mapbox & Form...
        ];

        // Jika pakai potong saldo
        if ($payment_method === 'POTONG SALDO') {
            // $user = User::find($user_id);
            // $user->saldo -= $request->input('total_tarif');
            // $user->save();
        }

        // Simpan Pesanan (Pesanan::create($dataPesanan))

        return response()->json([
            'status' => true,
            'message' => 'Pesanan berhasil dibuat, Menunggu Driver.'
        ]);
    }
}
