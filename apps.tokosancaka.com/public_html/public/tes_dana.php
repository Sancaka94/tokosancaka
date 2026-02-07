<?php
// --- 1. CONFIG (Pastikan ID ini benar dari Dashboard) ---
$clientId     = "2025081520100641466855"; // Client ID / Partner ID
$merchantId   = "216620080014040009735"; // Merchant ID

// --- 2. GENERATE KUNCI BARU (OTOMATIS) ---
// Kita buat pasangan kunci baru agar 100% sinkron
$config = array(
    "digest_alg" => "sha256",
    "private_key_bits" => 2048,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
);
$res = openssl_pkey_new($config);
openssl_pkey_export($res, $privateKey); // Private Key disimpan di variabel
$pubKey = openssl_pkey_get_details($res);
$publicKey = $pubKey["key"]; // Public Key untuk di-upload ke DANA

// --- 3. SETUP DATA REQUEST ---
date_default_timezone_set('Asia/Jakarta');
$timestamp = date('Y-m-d\TH:i:sP'); 
$refNo     = "RESET-" . time(); 
$amount    = "1000.00"; 

// --- 4. BODY JSON (Clean Format) ---
$body = [
    "partnerReferenceNo" => $refNo,
    "merchantId"         => $merchantId,
    "amount" => [
        "value"    => $amount,
        "currency" => "IDR"
    ],
    "urlParams" => [
        [
            "url"        => "https://google.com",
            "type"       => "PAY_RETURN",
            "isDeeplink" => "Y"
        ]
    ],
    "additionalInfo" => [
        "productCode" => "DIGITAL_PRODUCT",
        "mcc"         => "5732",
        "order" => [
            "orderTitle" => "Tes Reset Key",
            "orderMemo"  => "Tes Signature Baru"
        ],
        "envInfo" => [
            "sourcePlatform"    => "IPG",
            "terminalType"      => "WEB",
            "orderTerminalType" => "WEB"
        ]
    ]
];

// --- 5. SIGNATURE GENERATION ---
$jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$hashedBody = strtolower(hash('sha256', $jsonBody));
$path = '/rest/redirection/v1.0/debit/payment-host-to-host';
$stringToSign = "POST|" . $path . "||" . $hashedBody . "|" . $timestamp;

$binarySig = "";
openssl_sign($stringToSign, $binarySig, $privateKey, OPENSSL_ALGO_SHA256);
$signature = base64_encode($binarySig);

// --- 6. OUTPUT ---
echo "\n=======================================================\n";
echo "       LANGKAH 1: UPDATE DASHBOARD DANA (WAJIB!)\n";
echo "=======================================================\n";
echo "Copy kode di bawah ini, lalu Paste di Dashboard DANA > Settings > Integration > Public Key\n\n";
echo $publicKey . "\n";
echo "=======================================================\n";

echo "\n=======================================================\n";
echo "       LANGKAH 2: TES POSTMAN (SETELAH UPDATE KEY)\n";
echo "=======================================================\n";
echo "URL: https://api.sandbox.dana.id/rest/redirection/v1.0/debit/payment-host-to-host\n\n";
echo "HEADERS:\n";
echo "X-PARTNER-ID  : " . $clientId . "\n";
echo "X-EXTERNAL-ID : " . uniqid() . "\n";
echo "X-TIMESTAMP   : " . $timestamp . "\n";
echo "X-SIGNATURE   : " . $signature . "\n";
echo "CHANNEL-ID    : 95221\n";
echo "ORIGIN        : https://apps.tokosancaka.com\n\n";
echo "BODY (Raw JSON):\n" . $jsonBody . "\n\n";

echo "=======================================================\n";
echo "       LANGKAH 3: SIMPAN PRIVATE KEY INI DI .ENV\n";
echo "=======================================================\n";
echo "Jika Postman sukses 200 OK, simpan kunci ini di file .env Laravel Anda (DANA_PRIVATE_KEY):\n\n";
// Hapus baris baru agar jadi satu baris untuk .env
$oneLineKey = str_replace(["\r", "\n"], "", $privateKey);
echo $oneLineKey . "\n\n";
?>
