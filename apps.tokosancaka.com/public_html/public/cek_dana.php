<?php
// File: public/cek_dana.php
// Akses di: http://apps.tokosancaka.com/cek_dana.php

header('Content-Type: application/json');

// ============================================================================
// 1. CONFIGURATION (ISI SESUAI DATA DI .ENV ANDA)
// ============================================================================

$clientId     = 'ISI_X_PARTNER_ID_DISINI';      // Contoh: 202508...
$merchantId   = 'ISI_MERCHANT_ID_DISINI';       // Contoh: 2166...
$clientSecret = 'ISI_CLIENT_SECRET_DISINI';     // Contoh: 1df385...

// Copy Private Key dari .env (Hati-hati, harus persis!)
$privateKey   = "-----BEGIN PRIVATE KEY-----
PASTE_PRIVATE_KEY_ANDA_DISINI_SECARA_UTUH
TERMASUK_HEADER_DAN_FOOTERNYA
JANGAN_ADA_SPASI_ANEH
-----END PRIVATE KEY-----";

// ============================================================================
// 2. HELPER FUNCTIONS
// ============================================================================

function generateSignature($payload, $pKey) {
    // Bersihkan format key agar standar
    $pKey = str_replace(["-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----", "\r", "\n", " "], "", $pKey);
    $pKey = "-----BEGIN PRIVATE KEY-----\n" . chunk_split($pKey, 64, "\n") . "-----END PRIVATE KEY-----";

    $signature = "";
    if (!openssl_sign($payload, $signature, $pKey, OPENSSL_ALGO_SHA256)) {
        return false;
    }
    return base64_encode($signature);
}

// ============================================================================
// 3. PREPARE REQUEST (ACQUIRING ORDER)
// ============================================================================

$timestamp = date("Y-m-d\TH:i:sP");
$refNo     = "TEST-DEBUG-" . time();

// Payload Standar V2.0
$requestData = [
    "head" => [
        "version"      => "2.0",
        "function"     => "dana.acquiring.order.create",
        "clientId"     => $clientId,
        "clientSecret" => $clientSecret,
        "reqTime"      => $timestamp,
        "reqMsgId"     => uniqid(),
        "reserve"      => "{}"
    ],
    "body" => [
        "merchantId"        => $merchantId,
        "merchantTransId"   => $refNo,
        "merchantTransType" => "01",
        "order" => [
            "orderTitle"        => "Test Koneksi Script",
            "orderAmount"       => [
                "currency" => "IDR",
                "value"    => "1000.00"
            ],
            "merchantTransType" => "01",
            "orderMemo"         => "Debug Check"
        ],
        "envInfo" => [
            "sourcePlatform" => "IPG",
            "terminalType"   => "SYSTEM"
        ]
    ]
];

// Encode & Sign
$jsonPayload = json_encode($requestData);
$signature   = generateSignature($jsonPayload, $privateKey);

if (!$signature) {
    echo json_encode(["error" => "Gagal membuat signature. Format Private Key Salah."]);
    exit;
}

// Rakit Final Body
$finalBody = '{"request":' . $jsonPayload . ',"signature":"' . $signature . '"}';

// ============================================================================
// 4. EXECUTE CURL
// ============================================================================

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.sandbox.dana.id/dana/acquiring/order/create.htm");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $finalBody);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // Skip SSL check for sandbox debug
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Accept: application/json'
));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

// ============================================================================
// 5. OUTPUT HASIL DIAGNOSA
// ============================================================================

$result = [
    "status_http" => $httpCode,
    "response_raw" => $response, // <--- INI YANG PALING PENTING
    "curl_error" => $curlErr,
    "payload_sent" => json_decode($finalBody),
    "analisa" => ""
];

// Analisa Sederhana
if (empty($response)) {
    $result['analisa'] = "CRITICAL: Respon Kosong. Signature Invalid atau Public Key di Dashboard DANA tidak cocok dengan Private Key di script ini.";
} elseif ($httpCode != 200) {
    $result['analisa'] = "ERROR: Koneksi Gagal (HTTP $httpCode). Cek URL atau Firewall.";
} else {
    $jsonRes = json_decode($response, true);
    if (isset($jsonRes['response']['body']['resultInfo']['resultStatus']) && $jsonRes['response']['body']['resultInfo']['resultStatus'] == 'S') {
        $result['analisa'] = "SUKSES! Kunci & Config Benar. Masalah ada di kode Laravel.";
    } else {
        $result['analisa'] = "TERHUBUNG TAPI DITOLAK: Cek pesan error di dalam JSON response.";
    }
}

echo json_encode($result, JSON_PRETTY_PRINT);
?>
