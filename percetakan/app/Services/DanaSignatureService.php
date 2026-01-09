<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DanaSignatureService
{
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

    /**
     * Generate Signature untuk Transaksi H2H (Symmetric Signature)
     * Menggunakan: HTTP Method + Relative Path + AccessToken + HashedBody + Timestamp
     */
    public function generateSignature($method, $path, $body, $timestamp, $accessToken)
    {
        Log::info('DANA_SIGN_GEN: Memulai pembuatan signature transaksi.');

        // 1. Minify & Hash Body
        if (is_array($body) || is_object($body)) {
            $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            $jsonBody = $body;
        }
        
        $hashedBody = strtolower(hash('sha256', $jsonBody));

        // 2. String to Sign sesuai Standar SNAP BI
        // Format: HTTPMethod + ":" + RelativePath + ":" + AccessToken + ":" + HashedBody + ":" + Timestamp
        $stringToSign = strtoupper($method) . ":" . $path . ":" . $accessToken . ":" . $hashedBody . ":" . $timestamp;

        Log::info('DANA_SIGN_STS: String to Sign disusun.', ['sts' => $stringToSign]);

        // 3. HMAC-SHA512 menggunakan Client Secret
        $clientSecret = config('services.dana.client_secret');
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