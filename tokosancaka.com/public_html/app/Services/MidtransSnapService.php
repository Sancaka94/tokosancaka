<?php

namespace App\Services;

use App\Models\Api;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class MidtransSnapService
{
    protected $isProduction;
    protected $mode;
    protected $snapClientId;
    protected $privateKeyPath;
    protected $baseUrl;

    public function __construct()
    {
        // Mengambil konfigurasi dinamis yang sudah kita set di DB sebelumnya
        $this->mode = Api::getValue('MIDTRANS_MODE', 'global', 'sandbox');
        $this->isProduction = ($this->mode === 'production');
        
        $this->snapClientId = Api::getValue('MIDTRANS_SNAP_CLIENT_ID', $this->mode);
        
        // Path absolut menuju file kunci privat di storage
        $this->privateKeyPath = storage_path('app/keys/private_key_pkcs8.pem');

        // Base URL sesuai Environment BI-SNAP Midtrans
        $this->baseUrl = $this->isProduction 
            ? 'https://api.midtrans.com/v1.0' 
            : 'https://api.sandbox.midtrans.com/v1.0';
    }

    /**
     * Menghasilkan Asymmetric Signature (SHA256withRSA) untuk Access Token.
     */
    private function generateB2bSignature($clientId, $timestamp)
    {
        // Format standar BI-SNAP untuk Access Token: ClientId|Timestamp
        $stringToSign = $clientId . '|' . $timestamp;
        
        if (!file_exists($this->privateKeyPath)) {
            Log::error('LOG LOG: File Private Key Midtrans tidak ditemukan di ' . $this->privateKeyPath);
            throw new \Exception('Private key file is missing.');
        }

        $privateKey = file_get_contents($this->privateKeyPath);
        
        $signature = '';
        $signSuccess = openssl_sign($stringToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        
        if (!$signSuccess) {
            Log::error('LOG LOG: Gagal men-generate Asymmetric Signature Midtrans.');
            throw new \Exception('Failed to generate B2B signature.');
        }
        
        return base64_encode($signature);
    }

    /**
     * Melakukan Request B2B Access Token ke Midtrans.
     * Menggunakan sistem Cache agar tidak request berulang-ulang tiap transaksi.
     */
    public function getAccessToken()
    {
        // Token BI-SNAP biasanya berlaku 15 menit (900 detik). 
        // Kita cache selama 13 menit (780 detik) untuk batas aman sebelum expired.
        $cacheKey = 'midtrans_b2b_token_' . $this->mode;

        return Cache::remember($cacheKey, 780, function () {
            // Waktu saat ini dengan format ISO8601 (Zona Waktu Jakarta)
            $timestamp = Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP'); 
            
            $signature = $this->generateB2bSignature($this->snapClientId, $timestamp);
            
            $endpoint = $this->baseUrl . '/access-token/b2b';

            $headers = [
                'Content-Type' => 'application/json',
                'X-TIMESTAMP'  => $timestamp,
                'X-CLIENT-KEY' => $this->snapClientId,
                'X-SIGNATURE'  => $signature
            ];
            
            $payload = [
                'grant_type' => 'client_credentials'
            ];

            try {
                $response = Http::withHeaders($headers)->post($endpoint, $payload);

                // Mengikuti Aturan Anda: Mencatat semua aktivitas secara rinci ke LOG LOG
                Log::info('LOG LOG: Eksekusi Request Midtrans B2B Access Token', [
                    'endpoint'     => $endpoint,
                    'request_time' => $timestamp,
                    'x_client_key' => $this->snapClientId,
                    'status_code'  => $response->status(),
                    'response'     => $response->json(),
                ]);

                if ($response->successful() && isset($response['accessToken'])) {
                    return $response['accessToken'];
                }
                
                throw new \Exception('Response gagal atau token tidak ditemukan: ' . $response->body());

            } catch (\Exception $e) {
                Log::error('LOG LOG: Terjadi Kesalahan Request Midtrans B2B Token', [
                    'message' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }
}