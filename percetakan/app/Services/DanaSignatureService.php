<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class DanaSignatureService
{
    /**
     * Generate X-SIGNATURE sesuai standar SNAP
     */
    public function generateSignature($method, $relativePath, $body, $timestamp)
    {
        // 1. Load Private Key
        $privateKeyPath = config('services.dana.private_key_path');
        
        // Pastikan path sesuai dengan penyimpanan Anda (disini asumsi di storage/app)
        $privateKeyContent = Storage::get($privateKeyPath); 
        
        if (!$privateKeyContent) {
            throw new \Exception("Private Key tidak ditemukan di storage.");
        }

        $privateKey = openssl_pkey_get_private($privateKeyContent);

        // 2. Minify Body (Hapus spasi/newline yang tidak perlu)
        // Jika body kosong (GET request), string kosong. Jika array, json_encode.
        $minifiedBody = empty($body) ? '' : json_encode($body);

        // 3. Hash Body (SHA-256 -> Hex -> Lowercase)
        // Rumus: LowerCase(HexEncode(SHA-256(Minify(<HTTP BODY>))))
        $hashedBody = strtolower(hash('sha256', $minifiedBody));

        // 4. Compose String to Sign
        // Format: <HTTP METHOD> + ":" + <RELATIVE PATH URL> + ":" + <HASHED BODY> + ":" + <X-TIMESTAMP>
        $stringToSign = strtoupper($method) . ":" . $relativePath . ":" . $hashedBody . ":" . $timestamp;

        // 5. Sign dengan RSA-2048 dan Private Key
        $signature = '';
        openssl_sign($stringToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        // 6. Base64 Encode hasilnya
        return base64_encode($signature);
    }

    public function verifySignature($method, $relativePath, $body, $timestamp, $signatureFromHeader)
{
    // 1. Load DANA Public Key
    // Pastikan format public key diawali "-----BEGIN PUBLIC KEY-----" dst
    $danaPublicKey = config('services.dana.public_key');
    
    // 2. Re-create String to Verify (Sama seperti proses request)
    $minifiedBody = is_array($body) ? json_encode($body) : $body;
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