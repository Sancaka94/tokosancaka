<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KiriminAjaService
{
    protected $baseUrl;
    protected $token;

    public function __construct()
    {
        // Mengambil konfigurasi dari config/services.php
        $this->baseUrl = config('services.kiriminaja.base_url', 'https://tdev.kiriminaja.com');
        $this->token   = config('services.kiriminaja.token');
    }

    /**
     * Mencari alamat berdasarkan kata kunci.
     * @param string $keyword
     * @return array
     */
    public function searchAddress($keyword)
    {
        if (empty($this->token)) {
            return ['status' => false, 'message' => 'API Token KiriminAja tidak ditemukan.'];
        }

        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->get($this->baseUrl . '/api/mitra/v6.1/addresses', [
                    'search' => $keyword
                ]);
            
            Log::info('KiriminAja Address Search Response:', ['body' => $response->json()]);
            return $response->json();

        } catch (\Exception $e) {
            Log::error('KiriminAja Search Address Exception: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Gagal terhubung ke layanan pencarian alamat.'];
        }
    }

    /**
     * Mendapatkan harga pengiriman express & reguler.
     * @return array
     */
    public function getExpressPricing($origin, $subOrigin, $destination, $subDestination, $weight, $length, $width, $height, $itemValue, $insurance = 0)
    {
        try {
            // Rumus volumetrik standar
            $volumetricWeight = ($width * $length * $height) / 6000 * 1000;
            $finalWeight = max($weight, $volumetricWeight);

            $payload = [
                "origin"                  => $origin,
                "subdistrict_origin"      => $subOrigin,
                "destination"             => $destination,
                "subdistrict_destination" => $subDestination,
                "weight"                  => (int) ceil($finalWeight),
                "length"                  => $length,
                "width"                   => $width,
                "height"                  => $height,
                "item_value"              => $itemValue,
                "insurance"               => $insurance,
                "courier"                 => [] // Kosongkan agar semua kurir muncul
            ];

            $response = Http::withToken($this->token)
                ->acceptJson()
                ->post($this->baseUrl . '/api/mitra/v1/shipping_price', $payload);

            Log::info('KiriminAja Pricing Response:', ['body' => $response->json()]);
            return $response->json();

        } catch (\Exception $e) {
            Log::error('KiriminAja Get Pricing Exception: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Gagal terhubung ke layanan cek ongkir.'];
        }
    }
}

