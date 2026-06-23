<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;
use Illuminate\Support\Facades\Log;

class IpaymuService
{
    protected string $va;
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->va      = config('ipaymu.va');
        $this->apiKey  = config('ipaymu.api_key');
        $this->baseUrl = config('ipaymu.base_url');
    }

    /**
     * HTTP Request Wrapper dengan Algoritma Signature Akurat & Tahan Banting
     */
    protected function request(string $method, string $endpoint, array $data = [])
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
        $method = strtoupper($method);

        // 1. Bersihkan payload dari nilai null
        $cleanData = array_filter($data, fn($value) => $value !== null);

        // 2. KUNCI PENTING IPAYMU: Encode JSON secara manual!
        // Kita menggunakan hasil string JSON ini SEKALIGUS untuk di-hash DAN untuk dikirim.
        // Mencegah Laravel Guzzle mengubah format JSON di tengah jalan.
        $jsonBody = empty($cleanData) ? '' : json_encode($cleanData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // 3. Hash SHA256 dari String JSON Body
        $requestBodyHash = strtolower(hash('sha256', $jsonBody));

        // 4. Susun String to Sign (Method:VA:HashBody:ApiKey)
        $stringToSign = $method . ':' . $this->va . ':' . $requestBodyHash . ':' . $this->apiKey;

        // 5. Generate HMAC-SHA256
        $signature = hash_hmac('sha256', $stringToSign, $this->apiKey);

        $headers = [
            'va'           => $this->va,
            'signature'    => $signature,
            'timestamp'    => date('YmdHis'),
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];

        try {
            if ($method === 'GET') {
                return Http::withHeaders($headers)->get($url, $cleanData)->json();
            }

            // PERBAIKAN: Gunakan withBody() alih-alih melempar array ke ->post()
            // Menjamin Payload JSON yang dikirim 100% presisi dengan Payload yang di-Hash
            return Http::withHeaders($headers)
                        ->withBody($jsonBody, 'application/json')
                        ->post($url)
                        ->json();
        } catch (Exception $e) {
            Log::error("iPaymu Request Exception: " . $e->getMessage());
            return [
                'status'  => 500,
                'success' => false,
                'message' => 'Gagal terhubung ke iPaymu: ' . $e->getMessage()
            ];
        }
    }

    // =================================================================
    // IMPLEMENTASI ENDPOINT BERDASARKAN DOKUMENTASI
    // =================================================================

    public function createPayment(array $paymentData)
    {
        return $this->request('POST', '/api/v2/payment', $paymentData);
    }

    /**
     * POST Area COD
     * PERBAIKAN: iPaymu v2 mewajibkan endpoint Area menggunakan POST, BUKAN GET!
     */
    public function getCodArea(string $searchArea)
    {
        return $this->request('POST', '/api/v2/cod/area', ['area' => $searchArea]);
    }

    public function calculateShipping(string|int $destinationId, string|int $pickupId, float|int $weightKg, float|int $amount)
    {
        $payload = [
            'destination_area_id' => (string) $destinationId,
            'pickup_area_id'      => (string) $pickupId,
            'weight'              => (string) $weightKg,
            'amount'              => (string) $amount,
        ];

        return $this->request('POST', '/api/v2/cod/shipping-calculate', $payload);
    }

    public function trackCodPackage(string $awb, string|int $transactionId)
    {
        $payload = [
            'awb'            => $awb,
            'transaction_id' => (string) $transactionId,
        ];

        return $this->request('POST', '/api/v2/cod/tracking', $payload);
    }

    public function requestCodPickup(string|int $transactionId, string $pickupDate, string $pickupTime, string $vehicle = 'Motor')
    {
        $payload = [
            'transaction_id' => (string) $transactionId,
            'pickup_date'    => $pickupDate,
            'pickup_time'    => $pickupTime,
            'pickup_vehicle' => $vehicle,
        ];

        return $this->request('POST', '/api/v2/cod/pickup', $payload);
    }

    public function checkTransaction(string|int $transactionId)
    {
        $payload = [
            'transactionId' => (string) $transactionId,
            'account'       => $this->va,
        ];

        return $this->request('POST', '/api/v2/transaction', $payload);
    }

    public function checkBalance()
    {
        $payload = [
            'account' => $this->va,
        ];

        return $this->request('POST', '/api/v2/balance', $payload);
    }

    public function getHistory(array $filters = [])
    {
        $filters['account'] = $this->va;

        return $this->request('POST', '/api/v2/history', $filters);
    }
}
