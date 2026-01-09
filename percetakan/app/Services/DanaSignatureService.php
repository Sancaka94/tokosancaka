<?php

namespace App\Services;

class DanaSignatureService
{
    /**
     * Mendapatkan Access Token B2B (Bearer Token)
     */
    public function getAccessToken()
    {
        Log::info('DANA_AUTH: Memulai pengambilan Access Token B2B.');

        // Cek Cache agar tidak request berulang kali (Token biasanya valid 1-2 jam)
        if (Cache::has('dana_access_token')) {
            Log::info('DANA_AUTH: Menggunakan token dari Cache.');
            return Cache::get('dana_access_token');
        }

        $timestamp = Carbon::now('Asia/Jakarta')->toIso8601String();
        $path = '/v1.0/access-token/b2b.htm';
        
        // 1. Membuat Signature Auth (Asymmetric Signature)
        // Format: X-PARTNER-ID + "|" + X-TIMESTAMP
        $stringToSign = config('services.dana.x_partner_id') . "|" . $timestamp;
        
        try {
            $signature = $this->generateAsymmetricSignature($stringToSign);
            Log::info('DANA_AUTH_SIGNATURE: Signature Auth berhasil dibuat.');
        } catch (\Exception $e) {
            Log::error('DANA_AUTH_SIGNATURE_FAILED: ' . $e->getMessage());
            throw $e;
        }

        $baseUrl = config('services.dana.dana_env') === 'PRODUCTION' 
                   ? 'https://api.dana.id' 
                   : 'https://api.sandbox.dana.id';

        // 2. Request Token ke DANA
        try {
            $response = Http::withHeaders([
                'X-TIMESTAMP'  => $timestamp,
                'X-PARTNER-ID' => config('services.dana.x_partner_id'),
                'X-SIGNATURE'  => $signature,
                'Content-Type' => 'application/json',
            ])->post($baseUrl . $path, [
                'grantType' => 'client_credentials',
                'additionalInfo' => (object)[]
            ]);

            $result = $response->json();

            if ($response->successful() && isset($result['accessToken'])) {
                Log::info('DANA_AUTH_SUCCESS: Token berhasil didapatkan.');
                
                // Simpan di cache (dikurangi 5 menit untuk safety margin)
                $expiry = ($result['expiresIn'] ?? 3600) - 300;
                Cache::put('dana_access_token', $result['accessToken'], $expiry);

                return $result['accessToken'];
            }

            Log::error('DANA_AUTH_FAILED: Respon DANA tidak memberikan token.', $result);
            throw new \Exception("DANA Auth Error: " . ($result['message'] ?? 'Unknown Error'));

        } catch (\Exception $e) {
            Log::error('DANA_AUTH_HTTP_ERROR: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verifikasi Signature dari DANA (Callback/Return)
     */
    public function verifySignature($method, $relativePath, $body, $timestamp, $signatureFromHeader)
    {
        // 1. Load DANA Public Key Path
        $publicKeyPath = config('services.dana.public_key_path');

        if (!file_exists($publicKeyPath)) {
            throw new \Exception("Public Key DANA tidak ditemukan di: " . $publicKeyPath);
        }

        // Baca isi file
        $danaPublicKeyContent = file_get_contents($publicKeyPath);
        
        // Convert ke resource
        $danaPublicKey = openssl_pkey_get_public($danaPublicKeyContent);
        if (!$danaPublicKey) {
             throw new \Exception("Format Public Key DANA INVALID. Pastikan header -----BEGIN PUBLIC KEY----- ada.");
        }
        
        // 2. Re-create String to Verify
        // Logic sama persis dengan generateSignature
        if (is_string($body)) {
            $minifiedBody = $body;
        } else {
            $minifiedBody = empty($body) ? '' : json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        
        $hashedBody = strtolower(hash('sha256', $minifiedBody));
        $stringToVerify = strtoupper($method) . ":" . $relativePath . ":" . $hashedBody . ":" . $timestamp;

        // 3. Verifikasi
        $isValid = openssl_verify(
            $stringToVerify, 
            base64_decode($signatureFromHeader), 
            $danaPublicKey, 
            OPENSSL_ALGO_SHA256
        );

        return $isValid === 1;
    }
}