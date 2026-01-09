<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

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
        // Format String to Sign: X-PARTNER-ID + "|" + X-TIMESTAMP
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

   public function generateSignature($method, $path, $body, $timestamp, $accessToken)
{
    Log::info('DANA_SIGN_GEN: Memulai pembuatan signature transaksi.');

    // 1. Minify & Hash Body
    if (is_array($body) || is_object($body)) {
        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    } else {
        $jsonBody = $body;
    }
    
    // Hash body dengan SHA256 lalu lowercase hex
    $hashedBody = strtolower(hash('sha256', $jsonBody));

    // 2. String to Sign sesuai Standar SNAP BI (Gunakan karakter | sebagai pemisah)
    // FORMAT: HTTPMethod + "|" + RelativePath + "|" + AccessToken + "|" + HashedBody + "|" + Timestamp
    $stringToSign = strtoupper($method) . "|" . $path . "|" . $accessToken . "|" . $hashedBody . "|" . $timestamp;

    Log::info('DANA_SIGN_STS: String to Sign disusun.', ['sts' => $stringToSign]);

    // 3. HMAC-SHA512 menggunakan Client Secret
    $clientSecret = config('services.dana.client_secret');
    
    // Pastikan menggunakan raw binary (true) untuk hash_hmac sebelum di-base64
    $signature = base64_encode(hash_hmac('sha512', $stringToSign, $clientSecret, true));

    return $signature;
}

    public function generateAsymmetricSignature($stringToSign)
{
    $privateKey = config('services.dana.private_key');

    // Cek jika private key kosong
    if (empty($privateKey)) {
        Log::error('DANA_KEY_ERROR: Private Key kosong di config.');
        throw new \Exception("Private Key is empty.");
    }

    // Jika berupa path, ambil isinya
    if (file_exists($privateKey)) {
        $privateKey = file_get_contents($privateKey);
    }

    $binarySignature = "";
    $pkeyResource = openssl_get_privatekey($privateKey);

    if (!$pkeyResource) {
        // Log detail untuk melihat apakah formatnya terbaca
        Log::error('DANA_KEY_ERROR: Format Private Key salah.', [
            'key_start' => substr($privateKey, 0, 20) . '...',
            'openssl_error' => openssl_error_string()
        ]);
        throw new \Exception("Private Key tidak valid. Pastikan format .pem benar.");
    }

    openssl_sign($stringToSign, $binarySignature, $pkeyResource, OPENSSL_ALGO_SHA256);
    return base64_encode($binarySignature);
}
    /**
     * Verifikasi Signature dari DANA (Callback/Return)
     */
    public function verifySignature($method, $relativePath, $body, $timestamp, $signatureFromHeader)
    {
        Log::info('DANA_VERIFY: Memulai verifikasi signature callback.');

        $publicKeyPath = config('services.dana.dana_public_key_path');

        if (!file_exists($publicKeyPath)) {
            Log::error('DANA_VERIFY_ERROR: Public Key file tidak ditemukan.');
            return false;
        }

        $danaPublicKeyContent = file_get_contents($publicKeyPath);
        $danaPublicKey = openssl_pkey_get_public($danaPublicKeyContent);

        if (!$danaPublicKey) {
            Log::error('DANA_VERIFY_ERROR: Format Public Key DANA tidak valid.');
            return false;
        }
        
        // Re-create String to Verify (Sesuai dokumen DANA: Method:Path:HashedBody:Timestamp)
        $minifiedBody = is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $hashedBody = strtolower(hash('sha256', $minifiedBody));
        
        $stringToVerify = strtoupper($method) . ":" . $relativePath . ":" . $hashedBody . ":" . $timestamp;

        $isValid = openssl_verify(
            $stringToVerify, 
            base64_decode($signatureFromHeader), 
            $danaPublicKey, 
            OPENSSL_ALGO_SHA256
        );

        Log::info('DANA_VERIFY_RESULT: ' . ($isValid === 1 ? 'VALID' : 'INVALID'));

        return $isValid === 1;
    }
}