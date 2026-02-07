<?php
// =============================================================================
// SCRIPT GET BEARER TOKEN (B2B2C) - AUTO KEY GENERATOR
// =============================================================================

// --- 1. KONFIGURASI ---
$clientId   = "2025081520100641466855"; // Client ID Anda
// Masukkan Auth Code yang BARU SAJA didapat dari Browser (Cepat kadaluwarsa!)
$authCode   = "DgMwYE8nioHEApkqxE6sSmsFX88CAjb189Gp8600"; 

// --- 2. GENERATE KUNCI BARU (Agar Sinkron & Tidak 401) ---
$config = array(
    "digest_alg" => "sha256",
    "private_key_bits" => 2048,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
);
$res = openssl_pkey_new($config);
openssl_pkey_export($res, $privateKey); // Private Key Baru
$pubKeyDetails = openssl_pkey_get_details($res);
$publicKey = $pubKeyDetails["key"];     // Public Key Baru

// --- 3. PERSIAPAN DATA ---
date_default_timezone_set('Asia/Jakarta');
$timestamp = date('Y-m-d\TH:i:sP'); 
$path      = '/v1.0/access-token/b2b2c.htm';
$method    = 'POST';

$body = [
    "grantType" => "authorization_code",
    "authCode"  => $authCode
];

// --- 4. PENYUSUNAN SIGNATURE (Sesuai Definisi Anda) ---
// A. Minify JSON
$jsonBody = json_encode($body); 

// B. SHA256 Hash dari Body (Lowercase)
$hashedBody = strtolower(hash('sha256', $jsonBody));

// C. String To Sign (Format SNAP)
// Pattern: Method|Path|AccessToken|LowerHash|Timestamp
// AccessToken kosong (||) karena kita baru mau minta token
$stringToSign = $method . "|" . $path . "||" . $hashedBody . "|" . $timestamp;

// D. SHA256withRSA Signing
$binarySignature = "";
// Ini implementasi dari: asymmetric signature SHA256withRSA(Private_Key, stringToSign)
openssl_sign($stringToSign, $binarySignature, $privateKey, OPENSSL_ALGO_SHA256);
$signature = base64_encode($binarySignature);

// --- 5. OUTPUT ---
echo "\n=======================================================\n";
echo "   LANGKAH 1: UPDATE DASHBOARD DANA (WAJIB!)\n";
echo "=======================================================\n";
echo "Agar tidak error 401, Copy Public Key di bawah ini ke Dashboard DANA:\n";
echo "(Settings > Integration > DANA Public Key)\n\n";
echo $publicKey . "\n";
echo "=======================================================\n\n";

echo "=======================================================\n";
echo "   LANGKAH 2: EKSEKUSI REQUEST\n";
echo "=======================================================\n";
echo "Sedang mengirim request ke DANA...\n\n";

// --- 6. KIRIM REQUEST (CURL) ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.sandbox.dana.id" . $path);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "X-PARTNER-ID: " . $clientId,
    "X-CLIENT-KEY: " . $clientId,
    "X-TIMESTAMP: " . $timestamp,
    "X-SIGNATURE: " . $signature,
    "X-EXTERNAL-ID: " . time()
]);

$response = curl_exec($ch);
curl_close($ch);

// --- 7. HASIL ---
$jsonRes = json_decode($response, true);
echo "RESPONSE DANA:\n";
echo $response . "\n\n";

if (isset($jsonRes['accessToken'])) {
    echo "✅ SUKSES! BEARER TOKEN ANDA:\n";
    echo $jsonRes['accessToken'] . "\n\n";
    
    // Tampilkan Private Key untuk disimpan
    echo "⚠️ SIMPAN PRIVATE KEY INI DI .ENV LARAVEL ANDA:\n";
    echo str_replace(["\r", "\n"], "", $privateKey) . "\n";
} else {
    echo "❌ GAGAL. Pastikan:\n";
    echo "1. Anda sudah update Public Key di Dashboard.\n";
    echo "2. Auth Code belum kadaluwarsa (harus fresh dari browser).\n";
}
?>
