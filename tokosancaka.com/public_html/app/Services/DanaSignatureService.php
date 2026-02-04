<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DanaSignatureService
{
    /**
     * 1. Mendapatkan Access Token B2B (Apply Token Method)
     */
    public function getAccessToken()
    {
        Log::info('DANA_AUTH: Memulai pengambilan Access Token B2B.');

        if (Cache::has('dana_access_token')) {
            return Cache::get('dana_access_token');
        }

        $timestamp = Carbon::now('Asia/Jakarta')->toIso8601String();
        $partnerId = config('services.dana.x_partner_id');
        
        // Step 1: Compose string (X-CLIENT-KEY + "|" + X-TIMESTAMP)
        $stringToSign = $partnerId . "|" . $timestamp;
        
        $signature = $this->generateAsymmetricSignature($stringToSign);

        $baseUrl = config('services.dana.dana_env') === 'PRODUCTION' 
                   ? 'https://api.dana.id' 
                   : 'https://api.sandbox.dana.id';

        $response = Http::withHeaders([
            'X-TIMESTAMP'  => $timestamp,
            'X-PARTNER-ID' => $partnerId,
            'X-CLIENT-KEY' => $partnerId,
            'X-SIGNATURE'  => $signature,
            'Content-Type' => 'application/json',
        ])->post($baseUrl . '/v1.0/access-token/b2b.htm', [
            'grantType' => 'client_credentials',
            'additionalInfo' => (object)[]
        ]);

        $result = $response->json();

        if ($response->successful() && isset($result['accessToken'])) {
            Cache::put('dana_access_token', $result['accessToken'], ($result['expiresIn'] ?? 3600) - 300);
            return $result['accessToken'];
        }

        throw new \Exception("DANA Auth Error: " . ($result['responseMessage'] ?? 'Unknown Error'));
    }

    /**
     * 2. Generate Signature untuk Transaksi (Transactional Token Method)
     * Sesuai Dokumen Step 3: <METHOD> + ":" + <PATH> + ":" + Lowercase(Hex(SHA256(Body))) + ":" + <TIMESTAMP>
     */
    public function generateSignature($method, $path, $body, $timestamp, $accessToken = null)
    {
        Log::info('DANA_SIGN_GEN: Pembuatan signature transaksi.');

        // Step 1 & 2: Minify & Hash Body
        $jsonBody = is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $hashedBody = strtolower(hash('sha256', $jsonBody));

        // Step 3: Compose string (Pemisah menggunakan TITIK DUA ":")
        $stringToSign = strtoupper($method) . ":" . $path . ":" . $hashedBody . ":" . $timestamp;

        Log::info('DANA_SIGN_STS: String disusun.', ['sts' => $stringToSign]);

        // Step 4: Generate Signature (RSA-SHA256)
        return $this->generateAsymmetricSignature($stringToSign);
    }

    /**
     * Helper: RSA-2048 SHA-256 Signature (Step 2 & 4 di Dokumen)
     */
    public function generateAsymmetricSignature($stringToSign)
    {
        $privateKey = config('services.dana.private_key');
        if (file_exists($privateKey)) {
            $privateKey = file_get_contents($privateKey);
        }

        $binarySignature = "";
        $pkeyResource = openssl_get_privatekey($privateKey);
        
        if (!$pkeyResource) {
            throw new \Exception("DANA Error: Private Key tidak valid.");
        }

        // RSA-SHA256
        openssl_sign($stringToSign, $binarySignature, $pkeyResource, OPENSSL_ALGO_SHA256);
        
        return base64_encode($binarySignature);
    }

    /**
     * 3. Verifikasi Signature (Untuk Callback)
     */
    public function verifySignature($method, $path, $body, $timestamp, $signatureFromHeader)
    {
        $publicKeyContent = file_get_contents(config('services.dana.dana_public_key_path'));
        $publicKey = openssl_pkey_get_public($publicKeyContent);

        $jsonBody = is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        
        $stringToVerify = strtoupper($method) . ":" . $path . ":" . $hashedBody . ":" . $timestamp;

        $isValid = openssl_verify(
            $stringToVerify, 
            base64_decode($signatureFromHeader), 
            $publicKey, 
            OPENSSL_ALGO_SHA256
        );

        return $isValid === 1;
    }
}