<?php

namespace App\Services;

class DanaSignatureService
{
    /**
     * Generate X-SIGNATURE sesuai standar SNAP
     */
    public function generateSignature($method, $relativePath, $body, $timestamp)
    {
        // 1. Load Private Key Path
        $privateKeyPath = config('services.dana.private_key_path');
        
        // Cek file ada atau tidak
        if (!file_exists($privateKeyPath)) {
            throw new \Exception("Private Key tidak ditemukan di: " . $privateKeyPath);
        }
        
        // Ambil isinya
        $privateKeyContent = file_get_contents($privateKeyPath);

        // [FIX 1] Convert String menjadi OpenSSL Resource
        $privateKey = openssl_pkey_get_private($privateKeyContent);
        if (!$privateKey) {
            throw new \Exception("Format Private Key INVALID. Cek isi file .pem Anda.");
        }

        // 2. Minify Body
        $minifiedBody = empty($body) ? '' : json_encode($body);

        // 3. Hash Body
        $hashedBody = strtolower(hash('sha256', $minifiedBody));

        // 4. Compose String to Sign
        $stringToSign = strtoupper($method) . ":" . $relativePath . ":" . $hashedBody . ":" . $timestamp;

        // 5. Sign dengan RSA-2048
        $signature = '';
        // [FIX 1b] Sekarang variabel $privateKey sudah ada isinya
        openssl_sign($stringToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        // 6. Base64 Encode hasilnya
        return base64_encode($signature);
    }

    public function verifySignature($method, $relativePath, $body, $timestamp, $signatureFromHeader)
    {
        // [FIX 2] Load DANA Public Key dari FILE (Bukan config string)
        $publicKeyPath = config('services.dana.public_key_path');

        if (!file_exists($publicKeyPath)) {
            throw new \Exception("Public Key DANA tidak ditemukan di: " . $publicKeyPath);
        }

        $danaPublicKeyContent = file_get_contents($publicKeyPath);
        
        // Convert ke resource untuk validasi
        $danaPublicKey = openssl_pkey_get_public($danaPublicKeyContent);
        if (!$danaPublicKey) {
             throw new \Exception("Format Public Key DANA INVALID.");
        }
        
        // 2. Re-create String to Verify
        // Pastikan urutan dan logic sama persis dengan request
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