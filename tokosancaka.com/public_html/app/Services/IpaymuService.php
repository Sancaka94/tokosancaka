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
        $mode = \App\Models\Api::getValue('IPAYMU_MODE', 'global', 'sandbox');

        $this->va      = \App\Models\Api::getValue('IPAYMU_VA', $mode);
        $this->apiKey  = \App\Models\Api::getValue('IPAYMU_API_KEY', $mode);
        $this->baseUrl = ($mode === 'production')
            ? 'https://my.ipaymu.com'
            : 'https://sandbox.ipaymu.com';
    }

    /**
     * HTTP Request Wrapper dengan Algoritma Signature 100% Sesuai Sample iPaymu
     */
    protected function request(string $method, string $endpoint, array $data = [])
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
        $method = strtoupper($method);

        $cleanData = array_filter($data, fn($value) => $value !== null);

        // 1. ATURAN BODY (Sesuai Sample Code iPaymu)
        $jsonBody = '';
        if ($method === 'POST') {
            // Jika POST, encode body. Wajib menggunakan JSON_UNESCAPED_SLASHES
            // Gunakan (object) jika array kosong agar menjadi {} bukan []
            $jsonBody = empty($cleanData) ? json_encode((object)[], JSON_UNESCAPED_SLASHES) : json_encode($cleanData, JSON_UNESCAPED_SLASHES);
        }
        // Jika GET, $jsonBody dibiarkan kosong ('') karena parameter akan dikirim via URL, bukan Body.

        // 2. GENERATE SIGNATURE (Persis seperti Sample Code iPaymu)
        $requestBody  = strtolower(hash('sha256', $jsonBody));
        $stringToSign = $method . ':' . $this->va . ':' . $requestBody . ':' . $this->apiKey;
        $signature    = hash_hmac('sha256', $stringToSign, $this->apiKey);
        $timestamp    = date('YmdHis');

        $headers = [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
            'va'           => $this->va,
            'signature'    => $signature,
            'timestamp'    => $timestamp,
        ];

        try {
            if ($method === 'GET') {
                // Untuk GET, cleanData dikirim sebagai Query String di URL (?area=Ngawi)
                return Http::withHeaders($headers)->get($url, $cleanData)->json();
            }

            // Untuk POST, kirim jsonBody mentah agar tidak dimodifikasi oleh Laravel Guzzle
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

    public function getCodArea(string $searchArea)
    {
        // IPAYMU AREA MENGGUNAKAN GET
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
