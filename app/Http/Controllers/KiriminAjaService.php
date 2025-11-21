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
        $this->baseUrl = config('services.kiriminaja.base_url', 'https://client.kiriminaja.com');
        $this->token = config('services.kiriminaja.token');
        
        // PERIKSA: Pastikan token ada di produksi
        if (empty($this->token)) {
            Log::critical('KIRIMINAJA_TOKEN tidak diatur di file .env!');
        }
    }

    /**
     * Mencari alamat berdasarkan kata kunci.
     * @param string $keyword
     * @return array
     */
    public function searchAddress($keyword)
    {
        if (empty($this->token)) {
            Log::warning('KiriminAja searchAddress dipanggil tanpa API Token.');
            return ['status' => false, 'message' => 'API Token KiriminAja tidak ditemukan.'];
        }

        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->get($this->baseUrl . '/api/mitra/v6.1/addresses', [
                    'search' => $keyword
                ]);

            // --- PERBAIKAN PENTING ---
            // Cek jika respons BUKAN 2xx (e.g., 401, 404, 422, 503)
            if (!$response->successful()) {
                Log::error('KiriminAja Address Search Gagal (API Error)', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                    'keyword' => $keyword
                ]);
                return ['status' => false, 'message' => 'Gagal mengambil data alamat dari KiriminAja.', 'errors' => $response->json()];
            }
            // --- Akhir Perbaikan ---

            Log::info('KiriminAja Address Search Response:', ['body' => $response->json()]);
            return $response->json(); // Hanya kembalikan jika sukses

        } catch (\Exception $e) {
            // Ini hanya akan menangkap error koneksi (cth: timeout, DNS fail)
            Log::error('KiriminAja Search Address Exception (Connection Error): ' . $e->getMessage());
            return ['status' => false, 'message' => 'Gagal terhubung ke layanan pencarian alamat.'];
        }
    }

    /**
     * Mendapatkan harga pengiriman express & reguler.
     * @return array
     */
    public function getExpressPricing($origin, $subOrigin, $destination, $subDestination, $weight, $length, $width, $height, $itemValue, $insurance = 0)
    {
        if (empty($this->token)) {
            Log::warning('KiriminAja getExpressPricing dipanggil tanpa API Token.');
            return ['status' => false, 'message' => 'API Token KiriminAja tidak ditemukan.'];
        }

        try {
            // Rumus volumetrik standar
            $volumetricWeight = ($width * $length * $height) / 6000 * 1000;
            $finalWeight = max($weight, $volumetricWeight);

            $payload = [
                "origin" => $origin,
                "subdistrict_origin" => $subOrigin,
                "destination" => $destination,
                "subdistrict_destination" => $subDestination,
                "weight" => (int) ceil($finalWeight),
                "length" => $length,
                "width" => $width,
                "height" => $height,
                "item_value" => $itemValue,
                "insurance" => $insurance,
                "courier" => [] // Kosongkan agar semua kurir muncul
            ];

            // --- PERBAIKAN: Potensi Typo Versi API ---
            // Mengubah v1 ke v6.1 agar konsisten dengan searchAddress
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->post($this->baseUrl . '/api/mitra/v6.1/shipping_price', $payload); // Diubah dari v1

            // --- PERBAIKAN PENTING ---
            // Cek jika respons BUKAN 2xx (e.g., 401, 404, 422, 503)
            if (!$response->successful()) {
                Log::error('KiriminAja Get Pricing Gagal (API Error)', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                    'payload' => $payload
                ]);
                return ['status' => false, 'message' => 'Gagal mengambil data harga dari KiriminAja.', 'errors' => $response->json()];
            }
            // --- Akhir Perbaikan ---

            Log::info('KiriminAja Pricing Response:', ['body' => $response->json()]);
            return $response->json(); // Hanya kembalikan jika sukses

        } catch (\Exception $e) {
            // Ini hanya akan menangkap error koneksi (cth: timeout, DNS fail)
            Log::error('KiriminAja Get Pricing Exception (Connection Error): ' . $e->getMessage());
            return ['status' => false, 'message' => 'Gagal terhubung ke layanan cek ongkir.'];
        }
    }
}