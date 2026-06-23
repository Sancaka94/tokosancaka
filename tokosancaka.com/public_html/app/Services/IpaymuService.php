<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

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
     * Algoritma pembuatan HMAC-SHA256 Signature sesuai standar iPaymu v2
     */
    protected function generateSignature(string $method, array $body = []): string
    {
        // 1. Aturan iPaymu: Hapus semua key yang bernilai null
        $cleanBody = array_filter($body, fn($value) => $value !== null);

        // 2. Wajib gunakan JSON_UNESCAPED_SLASHES agar '/' tidak berubah jadi '\/'
        $jsonBody = empty($cleanBody) ? '' : json_encode($cleanBody, JSON_UNESCAPED_SLASHES);

        $requestBodyHash = hash('sha256', $jsonBody);

        $stringToSign = strtoupper($method) . ':' . $this->va . ':' . $requestBodyHash . ':' . $this->apiKey;

        return hash_hmac('sha256', $stringToSign, $this->apiKey);
    }

    /**
     * HTTP Request Wrapper
     */
    protected function request(string $method, string $endpoint, array $data = [])
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        // Bersihkan payload dari nilai null
        $cleanData = array_filter($data, fn($value) => $value !== null);

        // KUNCI PENTING: Untuk request GET, parameter dikirim lewat Query String (?a=1),
        // sehingga string Body yang di-hash untuk Signature HARUS kosong.
        $bodyForSignature = strtoupper($method) === 'GET' ? [] : $cleanData;

        $headers = [
            'va'           => $this->va,
            'signature'    => $this->generateSignature($method, $bodyForSignature),
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];

        try {
            if (strtoupper($method) === 'GET') {
                return Http::withHeaders($headers)->get($url, $cleanData)->json();
            }

            return Http::withHeaders($headers)->post($url, $cleanData)->json();
        } catch (Exception $e) {
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

    /**
     * 1. GET Area COD (Min. 3 Karakter)
     */
    public function getCodArea(string $searchArea)
    {
        return $this->request('GET', '/api/v2/cod/area', ['area' => $searchArea]);
    }

    /**
     * 2. POST Hitung Ongkir COD
     */
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

    /**
     * 3. POST Tracking Paket COD
     */
    public function trackPackage(string $awb, string $transactionId)
    {
        $payload = [
            'awb'            => $awb,
            'transaction_id' => $transactionId,
        ];

        return $this->request('POST', '/api/v2/cod/tracking', $payload);
    }

    /**
     * 4. BONUS: POST Create Payment (Redirect / VA) - Fitur Utama iPaymu
     */
    public function createPayment(array $paymentData)
    {
        return $this->request('POST', '/api/v2/payment', $paymentData);
    }

    /**
     * 5. POST Check Transaction
     * Berfungsi untuk mengecek status transaksi secara realtime.
     * Sangat berguna jika webhook gagal diterima dan kamu ingin mengecek manual.
     */
    public function checkTransaction(string|int $transactionId)
    {
        $payload = [
            'transactionId' => (string) $transactionId,
            'account'       => $this->va, // Dokumentasi mensyaratkan VA dikirim di body
        ];

        return $this->request('POST', '/api/v2/transaction', $payload);
    }

    /**
     * 6. POST Check Balance
     * Berfungsi untuk mengecek saldo akun iPaymu kamu.
     * Bisa digunakan untuk ditampilkan di Dashboard Admin.
     */
    public function checkBalance()
    {
        $payload = [
            'account' => $this->va,
        ];

        return $this->request('POST', '/api/v2/balance', $payload);
    }

    /**
     * 7. POST History Transaction
     * Berfungsi untuk melihat data riwayat transaksi iPaymu.
     */
    public function getHistory(array $filters = [])
    {
        // Gabungkan VA ke dalam parameter filter
        $filters['account'] = $this->va;

        return $this->request('POST', '/api/v2/history', $filters);
    }
}
