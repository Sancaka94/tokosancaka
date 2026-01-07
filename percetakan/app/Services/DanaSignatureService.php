<?php

namespace App\Services;

class DanaSignatureService
{
    /**
     * Generate X-SIGNATURE sesuai standar SNAP / Widget DANA
     */
    public function generateSignature($method, $relativePath, $body, $timestamp)
    {
        // 1. Load Private Key Path (Menggunakan Path Absolut dari config)
        $privateKeyPath = config('services.dana.private_key_path');
        
        // Cek keberadaan file
        if (!file_exists($privateKeyPath)) {
            throw new \Exception("Private Key tidak ditemukan di: " . $privateKeyPath);
        }
        
        // Baca isi file
        $privateKeyContent = file_get_contents($privateKeyPath);

        // Convert String menjadi OpenSSL Resource
        $privateKey = openssl_pkey_get_private($privateKeyContent);
        if (!$privateKey) {
            throw new \Exception("Format Private Key INVALID. Cek isi file .pem Anda.");
        }

        // 2. Minify Body (KUNCI PERBAIKAN DISINI)
        // Kita harus memastikan string yang di-hash SAMA PERSIS dengan string yang dikirim via HTTP
        if (is_string($body)) {
            // Jika Controller sudah mengirim JSON String mentah, pakai langsung.
            // Ini mencegah double-encoding (contoh: "{"key"..."} menjadi "{\"key\"...")
            $minifiedBody = $body;
        } else {
            // Jika masih Array, encode dengan standar DANA (Tanpa escape slash)
            $minifiedBody = empty($body) ? '' : json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        // 3. Hash Body (SHA-256 -> Hex -> Lowercase)
        $hashedBody = strtolower(hash('sha256', $minifiedBody));

        // 4. Compose String to Sign
        // Format: METHOD:URL:HASH:TIMESTAMP
        $stringToSign = strtoupper($method) . ":" . $relativePath . ":" . $hashedBody . ":" . $timestamp;

        // 5. Sign dengan RSA-2048
        $signature = '';
        if (!openssl_sign($stringToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new \Exception("Gagal melakukan signing OpenSSL: " . openssl_error_string());
        }

        // 6. Base64 Encode hasilnya
        return base64_encode($signature);
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