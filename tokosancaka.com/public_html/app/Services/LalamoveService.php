<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LalamoveService
{
    protected string $key;
    protected string $secret;
    protected string $market;
    protected string $baseUrl;

    // LOG LOG - Menjaga catatan log seperti instruksi Anda

    public function __construct()
    {
        $this->key = config('lalamove.key');
        $this->secret = config('lalamove.secret');
        $this->market = config('lalamove.market');
        $this->baseUrl = config('lalamove.base_url');
    }

    /**
     * Main method to hit Lalamove API
     */
    public function request(string $method, string $path, array $body = [])
    {
        $method = strtoupper($method);
        
        // Dapatkan Unix timestamp dalam milliseconds
        $timestamp = (int) (microtime(true) * 1000);
        
        // Parse payload (kosongkan string jika GET)
        $bodyPayload = empty($body) ? '' : json_encode($body);

        // Generate raw signature
        // Format: <TIMESTAMP>\r\n<HTTP_VERB>\r\n<PATH>\r\n\r\n<BODY>
        $rawSignature = "{$timestamp}\r\n{$method}\r\n{$path}\r\n\r\n{$bodyPayload}";
        
        // Hash dengan HMAC SHA256 (PHP otomatis return lowercase hex)
        $signature = hash_hmac('sha256', $rawSignature, $this->secret);

        // Buat Auth Token
        $token = "{$this->key}:{$timestamp}:{$signature}";
        
        // Buat UUID untuk Request-ID Nonce
        $requestId = Str::uuid()->toString();

        // Susun Headers
        $headers = [
            'Authorization' => "hmac {$token}",
            'Market'        => $this->market,
            'Request-ID'    => $requestId,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];

        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');

        // Execute request via Laravel HTTP Client
        $request = Http::withHeaders($headers);

        if ($method === 'GET') {
            $response = $request->get($url);
        } else {
            // Untuk POST/PUT/PATCH kirimkan body sebagai raw payload
            $response = $request->send($method, $url, [
                'body' => $bodyPayload
            ]);
        }

        return $response->json();
    }

    /**
     * Contoh Wrapper: Mendapatkan Quotation
     * Dokumentasi Lalamove biasa meminta wrapper "data" di root JSON.
     */
    public function getQuotation(array $payload)
    {
        return $this->request('POST', '/v3/quotations', [
            'data' => $payload
        ]);
    }

    /**
     * 1. Get Quotation
     * Dokumentasi: POST /v3/quotations
     */
    public function createQuotation(array $payload)
    {
        return $this->request('POST', '/v3/quotations', [
            'data' => $payload
        ]);
    }

    /**
     * 2. Place Order
     * Dokumentasi: POST /v3/orders
     */
    public function placeOrder(array $payload)
    {
        return $this->request('POST', '/v3/orders', [
            'data' => $payload
        ]);
    }
}