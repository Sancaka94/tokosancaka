<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DokuSacService
{
    protected $clientId;
    protected $secretKey;
    protected $baseUrl;

    public function __construct()
    {
        // Pastikan Anda sudah set di .env: DOKU_CLIENT_ID, DOKU_SECRET_KEY, DOKU_IS_PRODUCTION
        $this->clientId = config('doku.client_id');
        $this->secretKey = config('doku.secret_key');
        $this->baseUrl = config('doku.is_production')
            ? 'https://api.doku.com'
            : 'https://api-sandbox.doku.com';
    }

    /**
     * Mengirim Payout (Pencairan Dana)
     */
    public function sendPayout($sacId, $invoiceNumber, $amount, $beneficiary)
    {
        $endpoint = '/sac-merchant/v1/payouts';

        $body = [
            "account" => [
                "id" => $sacId //
            ],
            "payout" => [
                "amount" => (string) $amount, // Harus string
                "invoice_number" => $invoiceNumber
            ],
            "beneficiary" => [
                "bank_code" => $beneficiary['bank_code'], //
                "bank_account_number" => $beneficiary['bank_account_number'],
                "bank_account_name" => $beneficiary['bank_account_name']
            ]
        ];

        return $this->sendRequest('POST', $endpoint, $body);
    }

    /**
     * Cek Saldo Real di DOKU
     * Penting untuk validasi sebelum payout.
     */
    public function getBalance($sacId)
    {
        $endpoint = "/sac-merchant/v1/balances/{$sacId}";
        return $this->sendRequest('GET', $endpoint, null);
    }

    /**
     * Core Request Handler (Header, Signature, HTTP Client)
     */
    private function sendRequest($method, $path, $body = null)
    {
        $requestId = Str::uuid()->toString(); // Request-Id
        // Timestamp harus UTC+0 (dikurangi 7 jam dari WIB)
        $timestamp = gmdate("Y-m-d\TH:i:s\Z");

        // Generate Signature
        $signature = $this->generateSignature($this->clientId, $requestId, $timestamp, $path, $body);

        $headers = [
            'Client-Id' => $this->clientId, //
            'Request-Id' => $requestId, //
            'Request-Timestamp' => $timestamp, //
            'Signature' => $signature, //
        ];

        try {
            if (strtoupper($method) === 'POST') {
                $response = Http::withHeaders($headers)->post($this->baseUrl . $path, $body);
            } else {
                $response = Http::withHeaders($headers)->get($this->baseUrl . $path);
            }

            Log::info("DOKU SAC {$path} Response:", $response->json());

            if ($response->successful()) {
                return [
                    'status' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'status' => false,
                'message' => $response->json()['error']['message'] ?? 'Gagal menghubungi DOKU'
            ];

        } catch (\Exception $e) {
            Log::error("DOKU SAC Error: " . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Logic Signature DOKU Jokul/SubAccount
     */
    private function generateSignature($clientId, $requestId, $timestamp, $requestTarget, $body)
    {
        $digest = base64_encode(hash('sha256', is_array($body) ? json_encode($body) : ($body ?? ""), true));

        $stringToSign = "Client-Id:" . $clientId . "\n"
            . "Request-Id:" . $requestId . "\n"
            . "Request-Timestamp:" . $timestamp . "\n"
            . "Request-Target:" . $requestTarget . "\n"
            . "Digest:" . $digest;

        $signature = base64_encode(hash_hmac('sha256', $stringToSign, $this->secretKey, true));

        return "HMACSHA256=" . $signature; //
    }
}
