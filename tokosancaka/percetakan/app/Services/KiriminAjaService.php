<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response; // Import Response
use Exception; // Import Exception

class KiriminAjaService
{
    protected $baseUrl;
    protected $token;

    public function __construct()
    {
        $this->baseUrl = config('services.kiriminaja.base_url', 'https://client.kiriminaja.com');
        $this->token = config('services.kiriminaja.token');

        // PERIKSA: Pastikan token ada di produksi
        if (empty($this->token)) {
            Log::critical('KIRIMINAJA_TOKEN tidak diatur di file .env!');
        }
    }

    /**
     * Melakukan pelacakan paket (Express atau Instant).
     */
    public function track(?string $serviceType, string $orderId): ?array
    {
        if (empty($orderId)) {
            return null;
        }

        // Gunakan $this->request() yang sudah diperbarui
        if ($serviceType == 'instant') {
            return $this->request('GET', "/api/mitra/v4/instant/tracking/{$orderId}");
        }

        // Gunakan $this->request() yang sudah diperbarui
        return $this->request('POST', '/api/mitra/tracking', ['order_id' => $orderId]);
    }

    public function getExpressPricing($origin, $subOrigin, $destination, $subDestination, $weight, $length, $width, $height, $itemValue, $couriers = null, $category = 'regular', $insurance = 0)
    {
        if ($category === 'trucking') {
            $volumetricWeight = ($width * $length * $height) / 4000 * 1000; // Standar trucking
        } else {
            $volumetricWeight = ($width * $length * $height) / 6000 * 1000; // Standar reguler
        }

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
            "courier" => $couriers ?? []
        ];

        Log::info('KiriminAja Pricing Payload:', $payload);

        $response = Http::withToken($this->token)
            ->acceptJson()
            ->post($this->baseUrl . '/api/mitra/v6.1/shipping_price', $payload);

        // --- PERBAIKAN: Tambahkan Pengecekan Error ---
        if (!$response->successful()) {
            Log::error('KiriminAja getExpressPricing Gagal', [
                'status' => $response->status(),
                'body' => $response->json(),
                'payload' => $payload
            ]);
            return null; // Kembalikan null agar controller bisa menangani
        }
        // --- Akhir Perbaikan ---

        Log::info('KiriminAja Pricing Response:', ['body' => $response->json()]);
        return $response->json();
    }

    public function getInstantPricing($originLat, $originLng, $originAddress, $destLat, $destLng, $destAddress, $weight, $itemPrice, $vehicle = 'motor', $services = ['gosend', 'grab_express'])
    {
        $payload = [
            "service" => $services,
            "item_price" => (int)$itemPrice,
            "origin" => [
                "lat" => $originLat,
                "long" => $originLng,
                "address" => $originAddress,
            ],
            "destination" => [
                "lat" => $destLat,
                "long" => $destLng,
                "address" => $destAddress,
            ],
            "weight" => (int)$weight,
            "vehicle" => $vehicle,
            "timezone" => "WIB"
        ];
        
        $response = Http::withToken($this->token)
            ->acceptJson()
            ->post($this->baseUrl . '/api/mitra/v4/instant/pricing', $payload);

        // --- PERBAIKAN: Tambahkan Pengecekan Error ---
        if (!$response->successful()) {
            Log::error('KiriminAja getInstantPricing Gagal', [
                'status' => $response->status(),
                'body' => $response->json(),
                'payload' => $payload
            ]);
            return null;
        }
        // --- Akhir Perbaikan ---

        return $response->json();
    }

    public function searchAddress($keyword)
    {
        $response = Http::withToken($this->token)
            ->acceptJson()
            ->get($this->baseUrl . '/api/mitra/v6.1/addresses', [
                'search' => $keyword
            ]);

        // --- PERBAIKAN: Tambahkan Pengecekan Error ---
        if (!$response->successful()) {
            Log::error('KiriminAja searchAddress Gagal', [
                'status' => $response->status(),
                'body' => $response->json(),
                'keyword' => $keyword
            ]);
            return null;
        }
        // --- Akhir Perbaikan ---

        return $response->json();
    }

    public function createExpressOrder(array $data)
    {
        // Ganti ke $this->request() agar konsisten
        return $this->request('POST', '/api/mitra/v6.1/request_pickup', $data);
    }

    public function createInstantOrder(array $data)
    {
        // Ganti ke $this->request() agar konsisten
        return $this->request('POST', '/api/mitra/v4/instant/pickup/request', $data);
    }

    public function setCallback(string $url)
    {
        return $this->request('POST', '/api/mitra/set_callback', [
            'url' => $url,
        ]);
    }

    /**
     * --- PERBAIKAN BESAR:
     * Mengganti cURL manual dengan Laravel HTTP Client agar konsisten
     * dan menambahkan penanganan error yang lebih baik.
     */
    private function request($method, $endpoint, $payload = [])
    {
        $url = $this->baseUrl . $endpoint;
        $method = strtolower($method); // pastikan method lowercase

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->{$method}($url, $payload); // Panggil method secara dinamis (get, post, dll)

            // Cek jika request TIDAK sukses (bukan 2xx)
            if (!$response->successful()) {
                Log::error("KiriminAja API Error: {$method} {$endpoint}", [
                    'status' => $response->status(),
                    'response' => $response->body(), // Gunakan body() untuk teks mentah
                    'payload' => $payload
                ]);
                return null; // Kembalikan null untuk di-handle oleh pemanggil
            }

            // Jika sukses, kembalikan data JSON
            return $response->json();

        } catch (Exception $e) {
            // Tangani error level koneksi (mis. DNS, timeout)
            Log::error("KiriminAja Connection Error: {$e->getMessage()}", [
                'endpoint' => $endpoint,
                'payload' => $payload
            ]);
            return null;
        }
    }

    public function getSchedules()
    {
        // Gunakan $this->request() yang baru
        $response = $this->request('POST', '/api/mitra/v2/schedules');

        // $this->request() sudah menangani logging error, kita hanya perlu menangani data
        if ($response && isset($response['schedules'])) {
            foreach ($response['schedules'] as $schedule) {
                if (!$schedule['libur']) {
                    return $schedule;
                }
            }
        }

        return null; // Kembalikan null jika tidak ada jadwal
    }
    
     /**
     * Melacak paket berdasarkan Nomor Resi atau Order ID
     * (Fungsi ini yang sebelumnya hilang)
     */
    public function trackPackage($resi)
    {
        if (empty($this->token)) {
            return ['status' => false, 'message' => 'API Token tidak ditemukan.'];
        }

        try {
            // Endpoint Tracking Mitra KiriminAja
            $endpoint = '/api/mitra/tracking';
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($this->baseUrl . $endpoint, [
                'order_id' => $resi // Bisa resi (AWB) atau Order ID
            ]);

            if (!$response->successful()) {
                Log::error('KiriminAja Track Error:', ['body' => $response->body()]);
                return ['status' => false, 'text' => 'Gagal mengambil data tracking.'];
            }
            
            // Bungkus dalam format standar
            $responseData = $response->json();
            
            return [
                'status' => $responseData['status'] ?? false,
                'text' => $responseData['text'] ?? '',
                'data' => [
                    'summary' => $responseData['details'] ?? [],
                    'histories' => $responseData['histories'] ?? []
                ]
            ];

        } catch (\Exception $e) {
            Log::error('KiriminAja Track Exception: ' . $e->getMessage());
            return [
                'status' => false,
                'text' => 'Gagal koneksi ke server tracking.'
            ];
        }
    }
}