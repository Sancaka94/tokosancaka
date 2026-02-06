<?php
// Simpan file ini dengan nama: tes_dana.php
// Jalankan di terminal: php tes_dana.php

// --- 1. CONFIG (HARDCODE DARI CHAT ANDA) ---
$clientId     = "2025081520100641466855";
$merchantId   = "216620080014040009735";
$privateKey   = "-----BEGIN PRIVATE KEY-----
MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDNVB5kzP1G9sgg
IGAyNzIHaK9fY5pmP2HUhDsYY0eSrljlgksAOVHgaCION0vZ4679ZRQXWZciJqZL
XAhJE8Iyna9RNL4bM2qDk3RvMR3xnaDRA97FofxL99fMFXl2vVn4k6Az3PGZtSKj
GOtb1E02F/iJckZVO3jBacVKbUUS6e8Dut8wScw0R5VLAurNIvLxFoYJa3mPkVmx
77fkL9S0qTbu/cRLayhguiPzg/P9DlQYa5ah7lT92P+79dSBp7TxrQbbm6Yic1Wf
sS3deREV1qp30om2frp5lyOpcrxcs+5dGV0viRV41bg4LOFjD1uIc7YiXEJn8ZIW
37K1ZvJrAgMBAAECggEAA91U8x2+mKLVcnFZjihmyyfnwRpdUhZYT4krmZJoyvR4
HN2+bqMljN044t6ckV3NMdzAq43Wn+BtWdbCGyoBijVYkuU0vMtTcmWIl/0rLJyE
Zdq2Sy740i84gxFWZ2s58clJhyBd9cAohjxWVbShvWZnGaMqerkzVSSZ/4Qd/DSd
VxU2+YuooLq3QgVasmlZkSy4W720Q2Op6NS8joq0LRHxQRRbvl9J99zs+3cTtSfV
K3nLOixhiLu0O/keek8yZ6Kw98Rms/od1TWDY0ivo24y0ABfnWOOy6f/+v3MzKq2
ghvFIX0ft6Z79EDt839AjJXW82l5E085J7qY66kKhQKBgQDnAb1iVLL6ycR3RqBC
R0MYBdJC8uNdgxw/vi6+fic7MAYY9/FsdDVQr0do4tTCkIwjcHoOPGwrwYl3xnTz
DSgd5cX0wU0hbBXrSfN+zZjkwf+8eec+mIvMBV3UMe2kJ/Z8aWvtUmhqVK9fgAqg
giFNGmIAjmxJPi3iBdl9Qvrm1QKBgQDjiymT8cSl9bMqUQxG0ggfTFXlZFiVBlmk
5qYEcbSaz247Hqo2sLR5it4qHxiWV/QqXabhVYFkQcLTd3Qgj9t8TwWOvSYN69gB
xW3dYqsptYVQ8lywjKKt3WKVGSKOgqslMwXnJTHZ/PycBDigDP1nmhczmx0DEQFV
ltW3n+GUPwKBgCSAzeBf6fhfMcB3VJOklyGQqe0SXINGWIxqDRDk9mYP7Ka9Z1Tv
+AzL5cjZLy2fkcV33JGrUpyHdKWMoqZVieVPjbxjX0DMx5nqkaOT8XkUfsjVqojl
qhGPN4h0a0zpU7XNItTZlM5Ym23H2eYLKh/470uPNeVNAgsZSYjVsLgRAoGAJuEa
Y5sF3M2UpYBftqIgnShv7NgugpgpLRH0AAJlt6YF0bg1oU6kJ7hgqZXSn627nJmP
8CSqDTVnUrawcvfhquXdrzwGio5nxDW1xgQb9u57Lw+aYthE26xeMdevneYZ1CtZ
sNscH4EosIfQHRjbG56qpDi2xlVbgwJY1h1NcAUCgYB28OEqvgeYcu2YJfcn66kg
d/eTNPiHrGxDL6zhU7MDOl07Cm7AaRFeyLuYrHchI2cbGSc5ssZNYjf5Fp9mh6Xr
NR/qAr2HmcN0nJdx1gTNIP2bYRxzrqLqfxoHSKmORMh4BCS+saRwkmMdIFzXdNVO
L5vXkAGZnIBgAJ/9t+HC0w==
-----END PRIVATE KEY-----";

// --- 2. SETUP DATA REQUEST ---
// Gunakan zona waktu Jakarta agar Timestamp valid
date_default_timezone_set('Asia/Jakarta');
$timestamp = date('Y-m-d\TH:i:sP');
$refNo     = "TEST-" . time();
$amount    = "1000.00";

// Body JSON (Sesuai Dokumentasi SNAP Direct Debit)
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
        "order" => [
            "orderTitle" => "Tes Postman",
            "orderMemo"  => "Cek Signature"
        ],
        "mcc" => "5732",
        "envInfo" => [
            "sourcePlatform"    => "IPG",
            "terminalType"      => "WEB",
            "orderTerminalType" => "WEB"
        ]
    ]
];

// --- 3. GENERATE SIGNATURE (SNAP LOGIC - PIPA | ) ---
$jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$hashedBody = strtolower(hash('sha256', $jsonBody));
$path = '/rest/redirection/v1.0/debit/payment-host-to-host';

// String to Sign: Method|Path||LowerHash|Timestamp
$stringToSign = "POST|" . $path . "||" . $hashedBody . "|" . $timestamp;

// Bersihkan Format Key
$pKey = str_replace(["-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----", "\r", "\n", " "], "", $privateKey);
$pKey = "-----BEGIN PRIVATE KEY-----\n" . chunk_split($pKey, 64, "\n") . "-----END PRIVATE KEY-----";

$binarySig = "";
openssl_sign($stringToSign, $binarySig, $pKey, OPENSSL_ALGO_SHA256);
$signature = base64_encode($binarySig);

// --- 4. OUTPUT UNTUK POSTMAN ---
echo "\n============================================\n";
echo "   DATA UNTUK COPAS KE POSTMAN (VALID)\n";
echo "============================================\n\n";

echo "1. URL (POST):\n";
echo "https://api.sandbox.dana.id/rest/redirection/v1.0/debit/payment-host-to-host\n\n";

echo "2. HEADERS (Masukkan satu per satu):\n";
echo "--------------------------------------------\n";
echo "X-PARTNER-ID  : " . $clientId . "\n";
echo "X-EXTERNAL-ID : " . uniqid() . "\n";
echo "X-TIMESTAMP   : " . $timestamp . "\n";
echo "X-SIGNATURE   : " . $signature . "\n";
echo "CHANNEL-ID    : 95221\n";
echo "ORIGIN        : https://apps.tokosancaka.com\n";
echo "--------------------------------------------\n\n";

echo "3. BODY (Pilih 'raw' -> 'JSON'):\n";
echo "--------------------------------------------\n";
echo $jsonBody . "\n";
echo "--------------------------------------------\n\n";
?>
