<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DanaDashboardController extends Controller
{
    public function index() {
        return view('dana_dashboard');
    }

    public function startBinding() {
        $queryParams = [
            'partnerId'   => config('services.dana.x_partner_id'),
            'timestamp'   => now('Asia/Jakarta')->toIso8601String(),
            'externalId'  => 'USER-' . time(),
            'merchantId'  => config('services.dana.merchant_id'),
            'redirectUrl' => config('services.dana.redirect_url_oauth'),
            'state'       => bin2hex(random_bytes(8)),
            'scopes'      => 'DEFAULT_BASIC_PROFILE,QUERY_BALANCE,MINI_DANA',
        ];
        return redirect('https://m.sandbox.dana.id/d/portal/oauth?' . http_build_query($queryParams));
    }

    public function handleCallback(Request $request) {
        $authCode = $request->authCode;
        if (!$authCode) return redirect()->route('dana.dashboard')->with('error', 'Auth Code hilang.');

        // 1. Ambil Token (B2B2C)
        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $clientId  = config('services.dana.x_partner_id');
        
        // Signature Asymmetric (ClientKey|Timestamp)
        $stringToSign = $clientId . "|" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        $response = Http::withHeaders([
            'X-TIMESTAMP'  => $timestamp,
            'X-CLIENT-KEY' => $clientId,
            'X-SIGNATURE'  => $signature,
            'Content-Type' => 'application/json'
        ])->post('https://api.sandbox.dana.id/v1.0/access-token/b2b2c.htm', [
            "grantType" => "AUTHORIZATION_CODE",
            "authCode"  => $authCode
        ]);

        $data = $response->json();
        if (isset($data['accessToken'])) {
            session(['dana_access_token' => $data['accessToken']]);
            return redirect()->route('dana.dashboard')->with('success', 'Akun Terhubung!');
        }
        return redirect()->route('dana.dashboard')->with('error', 'Gagal: ' . ($data['responseMessage'] ?? 'Unknown Error'));
    }

    public function checkBalance(Request $request) {
    $accessToken = $request->access_token ?? session('dana_access_token');
    $timestamp = now('Asia/Jakarta')->toIso8601String();
    $path = '/v1.0/balance-inquiry.htm';
    
    $body = [
        'partnerReferenceNo' => 'BAL' . time(),
        'balanceTypes' => ['BALANCE'],
        'additionalInfo' => ['accessToken' => $accessToken]
    ];

    // STEP 1 & 2: Minify & Hash Body (Lowercase Hex)
    $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $hashedBody = strtolower(hash('sha256', $jsonBody));

    // STEP 3: Compose String To Sign (IKUTI URUTAN DOKUMENTASI)
    // Format: METHOD:PATH:HASHBODY:TIMESTAMP
    $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;

    // STEP 4: Generate Signature (RSA-2048 SHA-256)
    $signature = $this->generateSignature($stringToSign);

    Log::info('[FIXED] StringToSign Sesuai Dokumen: ' . $stringToSign);

    $headers = [
        'X-TIMESTAMP'   => $timestamp,
        'X-SIGNATURE'   => $signature, // STEP 5
        'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
        'X-EXTERNAL-ID' => (string) time(),
        'X-DEVICE-ID'   => 'DANA-DASHBOARD-STATION',
        'CHANNEL-ID'    => '95221',
        'ORIGIN'        => config('services.dana.origin'),
        'Authorization-Customer' => 'Bearer ' . $accessToken,
        'Content-Type'  => 'application/json'
    ];

    $response = Http::withHeaders($headers)
                    ->withBody($jsonBody, 'application/json')
                    ->post('https://api.sandbox.dana.id' . $path);

    return $this->handleResponse($response);
}

    private function generateSignature($stringToSign) {
    $rawKey = config('services.dana.private_key');

    // 1. Bersihkan kunci dari karakter yang mungkin merusak (spasi/tab)
    $cleanKey = trim($rawKey);

    // 2. Pastikan format PEM benar: Harus ada Header, Footer, dan Newline
    // Kita rapikan secara otomatis agar OpenSSL bisa baca
    if (!str_contains($cleanKey, '-----BEGIN PRIVATE KEY-----')) {
        // Jika di .env cuma isinya string panjang, kita bungkus lagi
        $cleanKey = str_replace(["\r", "\n", " "], "", $cleanKey);
        $cleanKey = "-----BEGIN PRIVATE KEY-----\n" . 
                    wordwrap($cleanKey, 64, "\n", true) . 
                    "\n-----END PRIVATE KEY-----";
    }

    $binarySignature = "";
    
    // 3. Coba muat private key
    $privateKeyRes = openssl_pkey_get_private($cleanKey);
    
    if (!$privateKeyRes) {
        Log::error("[DANA SIG] Gagal memuat Private Key. Error: " . openssl_error_string());
        return "";
    }

    // 4. Lakukan proses Signing
    if (openssl_sign($stringToSign, $binarySignature, $privateKeyRes, OPENSSL_ALGO_SHA256)) {
        // Bebaskan memory
        openssl_free_key($privateKeyRes);
        return base64_encode($binarySignature);
    }

    Log::error("[DANA SIG] Gagal melakukan Signing. Error: " . openssl_error_string());
    return "";
}
}