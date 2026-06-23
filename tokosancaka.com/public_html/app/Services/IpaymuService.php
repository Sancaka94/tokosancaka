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
        // 1. Ambil mode yang sedang aktif dari Database
        $mode = \App\Models\Api::getValue('IPAYMU_MODE', 'global', 'sandbox');

        // 2. Ambil Kredensial spesifik sesuai mode (Sandbox/Production)
        $this->va      = \App\Models\Api::getValue('IPAYMU_VA', $mode);
        $this->apiKey  = \App\Models\Api::getValue('IPAYMU_API_KEY', $mode);

        // 3. Tentukan Base URL otomatis berdasarkan mode
        $this->baseUrl = ($mode === 'production')
            ? 'https://my.ipaymu.com'
            : 'https://sandbox.ipaymu.com';
    }

  /**
     * HTTP Request Wrapper dengan Algoritma Signature Akurat & Tahan Banting
     */
    protected function request(string $method, string $endpoint, array $data = [])
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
        $method = strtoupper($method);

        $cleanData = array_filter($data, fn($value) => $value !== null);

        // KUNCI PENTING IPAYMU UNTUK GET vs POST:
        // Jika GET, body yang di-hash HARUS kosong (string kosong).
        // Jika POST, body di-encode menjadi JSON.
        $jsonBody = '';
        if ($method === 'POST' && !empty($cleanData)) {
            $jsonBody = json_encode($cleanData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        // Hash SHA256 dari String JSON Body (atau hash string kosong jika GET)
        $requestBodyHash = strtolower(hash('sha256', $jsonBody));

        // Susun String to Sign (Method:VA:HashBody:ApiKey)
        $stringToSign = $method . ':' . $this->va . ':' . $requestBodyHash . ':' . $this->apiKey;

        // Generate HMAC-SHA256
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
                // Untuk GET, parameter dikirim di URL Query (?area=Ngawi)
                return Http::withHeaders($headers)->get($url, $cleanData)->json();
            }

            // Untuk POST, kirim body JSON mentah yang sama persis dengan yang di-hash
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
     * GET Area COD
     */
    public function getCodArea(string $searchArea)
    {
        // KEMBALIKAN KE GET!
        return $this->request('GET', '/api/v2/cod/area', ['area' => $searchArea]);
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
