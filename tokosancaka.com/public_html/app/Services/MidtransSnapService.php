<?php

namespace App\Services;

use App\Models\Api;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Str;

class MidtransSnapService
{
    protected $isProduction;
    protected $mode;
    protected $snapClientId;
    protected $snapClientSecret; 
    protected $partnerId;        
    protected $privateKeyPath;
    protected $baseUrl;

    public function __construct()
    {
        $this->mode = Api::getValue('MIDTRANS_MODE', 'global', 'sandbox');
        $this->isProduction = ($this->mode === 'production');
        
        $this->snapClientId = Api::getValue('MIDTRANS_SNAP_CLIENT_ID', $this->mode);
        $this->snapClientSecret = Api::getValue('MIDTRANS_SNAP_CLIENT_SECRET', $this->mode);
        
        $this->partnerId = 'SANCAKA'; 

        $this->privateKeyPath = storage_path('app/keys/private_key_pkcs8.pem');

        $this->baseUrl = $this->isProduction 
            ? 'https://api.midtrans.com' 
            : 'https://api.sandbox.midtrans.com';
    }

    /**
     * 1. Menghasilkan Asymmetric Signature (SHA256withRSA) untuk Access Token.
     */
    private function generateB2bSignature($clientId, $timestamp)
    {
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
     * 2. Mendapatkan Request B2B Access Token dari Midtrans (Cached).
     */
    public function getAccessToken()
    {
        $cacheKey = 'midtrans_b2b_token_' . $this->mode;

        return Cache::remember($cacheKey, 780, function () {
            $timestamp = Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP'); 
            $signature = $this->generateB2bSignature($this->snapClientId, $timestamp);
            
            $endpoint = $this->baseUrl . '/v1.0/access-token/b2b';

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

                Log::info('LOG LOG: Eksekusi Request Midtrans B2B Access Token', [
                    'endpoint'     => $endpoint,
                    'request_time' => $timestamp,
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

    /**
     * 3. Menghasilkan Symmetric Signature (HMAC_SHA512) untuk Transactional API.
     */
    private function generateTransactionalSignature($httpMethod, $relativeUrl, $accessToken, $requestBody, $timestamp)
    {
        $minifiedBody = is_array($requestBody) || is_object($requestBody) 
            ? json_encode($requestBody, JSON_UNESCAPED_SLASHES) 
            : (string) $requestBody;
            
        if (empty($minifiedBody)) {
            $minifiedBody = '';
        }

        $hashedBody = strtolower(hash('sha256', $minifiedBody));
        $stringToSign = strtoupper($httpMethod) . ':' . $relativeUrl . ':' . $accessToken . ':' . $hashedBody . ':' . $timestamp;
        $signature = hash_hmac('sha512', $stringToSign, $this->snapClientSecret, true);

        return base64_encode($signature);
    }

    /**
     * 4. Mengeksekusi Transactional API (Pembayaran, Refund, Cek Status, dll)
     */
    public function executeTransaction($method, $relativeUrl, $payload = [])
    {
        try {
            $accessToken = $this->getAccessToken();
            $timestamp   = Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP'); 
            $externalId  = (string) Str::uuid(); 
            $signature   = $this->generateTransactionalSignature($method, $relativeUrl, $accessToken, $payload, $timestamp);

            $endpoint = $this->baseUrl . $relativeUrl;

            $headers = [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'X-PARTNER-ID'  => $this->partnerId,
                'X-EXTERNAL-ID' => $externalId,
                'CHANNEL-ID'    => '12345', 
                'X-DEVICE-ID'   => request()->header('User-Agent') ?? 'Sancaka-Server/1.0',
            ];

            $http = Http::withHeaders($headers);
            $response = strtolower($method) === 'post' 
                ? $http->post($endpoint, $payload)
                : $http->get($endpoint, $payload);

            Log::info('LOG LOG: Eksekusi Transactional API Midtrans BI-SNAP', [
                'method'        => $method,
                'endpoint'      => $endpoint,
                'x_external_id' => $externalId,
                'status_code'   => $response->status(),
                'response'      => $response->json(),
            ]);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('LOG LOG: Kegagalan Sistem pada Transactional API Midtrans', [
                'endpoint' => $relativeUrl ?? 'unknown',
                'error'    => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}