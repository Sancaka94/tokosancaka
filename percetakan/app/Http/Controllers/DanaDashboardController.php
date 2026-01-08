<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; // Wajib ada untuk logging
use Illuminate\Support\Str;
use Carbon\Carbon;

class DanaDashboardController extends Controller
{
    // =========================================================================
    // 1. TAMPILKAN HALAMAN DASHBOARD
    // =========================================================================
    public function index()
    {
        // Log sederhana saat halaman dibuka
        Log::info('=========================================');
        Log::info('USER AKSES DASHBOARD DANA');
        Log::info('=========================================');
        return view('dana_dashboard');
    }

    // =========================================================================
    // 2. MULAI BINDING (SAMBUNGKAN AKUN)
    // =========================================================================
    public function startBinding()
    {
        Log::info('[BINDING] Proses Memulai Binding Dimulai (WEB PORTAL MODE)...');

        $clientId    = config('services.dana.client_id');
        $redirectUrl = route('dana.callback'); 
        $state       = Str::random(16); //
        $timestamp   = Carbon::now('Asia/Jakarta')->toIso8601String();

        // Parameter Binding
        $queryParams = [
            'partnerId'     => $clientId, //
            'timestamp'     => $timestamp, //
            'externalId'    => 'USER-' . time(), //
            'channelId'     => '95221', //
            'merchantId'    => config('services.dana.merchant_id'), //
            'redirectUrl'   => $redirectUrl, //
            'state'         => $state, //
            // Sederhanakan Scope dulu agar tidak error permission
            'scopes'        => 'DEFAULT_BASIC_PROFILE,QUERY_BALANCE,MINI_DANA', 
            'allowRegistration' => 'true'
        ];

        // [SOLUSI UTAMA]
        // Jangan pakai /n/link/binding (Itu untuk Mobile App Deep Link)
        // Gunakan /d/portal/oauth (Ini untuk Web Portal)
        $baseUrl = 'https://m.sandbox.dana.id/d/portal/oauth'; 
        
        $fullUrl = $baseUrl . '?' . http_build_query($queryParams);

        Log::info("[BINDING] Generated WEB URL: " . $fullUrl);
        
        return redirect($fullUrl);
    }

    // =========================================================================
    // 3. HANDLE CALLBACK (VERSI FINAL DENGAN REDIRECT)
    // =========================================================================
    public function handleCallback(Request $request)
    {
        Log::info('=========================================');
        // Perhatikan Log ini beda dengan yang lama, untuk memastikan kode ini yang jalan
        Log::info('[CALLBACK] DANA Redirect Back Received (NEW CONTROLLER)'); 
        Log::info('[CALLBACK] Params:', $request->all());

        // 1. Ambil Auth Code
        $authCode = $request->authCode ?? $request->auth_code ?? null;
        
        if(!$authCode) {
            Log::error('[CALLBACK] GAGAL: Auth Code tidak ditemukan.');
            return redirect()->route('dana.dashboard')->with('error', 'Gagal Binding: Auth Code hilang.');
        }

        Log::info("[CALLBACK] Auth Code: $authCode");

        // 2. TUKAR JADI TOKEN (LOGIKA APPLY TOKEN)
        // Kita langsung tembak API Apply Token disini
        $tokenResponse = $this->exchangeAuthCodeForToken($authCode);

        // Jika Sukses dapat Token
        if(isset($tokenResponse['accessToken'])) {
            $accessToken = $tokenResponse['accessToken'];
            
            session(['dana_access_token' => $accessToken]);
            session(['dana_auth_code'  => $authCode]);

            Log::info("[CALLBACK] SUKSES! Token tersimpan. Redirecting to Dashboard...");
            
            return redirect()->route('dana.dashboard')->with('success', 'Binding Sukses! Saldo Siap Dicek.');
        } 
        
        // [FIX ERROR DISINI]
        // Kita bungkus $tokenResponse dengan [(array)...] agar tidak error jika nilainya null
        Log::error("[CALLBACK] Gagal Tukar Token.", (array)$tokenResponse);
        
        return redirect()->route('dana.dashboard')->with('error', 'Binding Gagal saat menukar Token. Cek Log.');
    }

    // =========================================================================
    // HELPER: APPLY TOKEN (VERSI SNAP B2B2C) - FINAL FIX
    // =========================================================================
    private function exchangeAuthCodeForToken($authCode)
    {
        Log::info('[APPLY TOKEN] Memulai Request SNAP B2B2C...');
        
        $clientId  = config('services.dana.client_id');
        $timestamp = \Carbon\Carbon::now('Asia/Jakarta')->toIso8601String();

        // 1. GENERATE SIGNATURE KHUSUS SNAP
        // Rumus: stringToSign = client_ID + "|" + X-TIMESTAMP
        $stringToSign = $clientId . "|" . $timestamp;
        
        Log::info("[APPLY TOKEN] StringToSign: " . $stringToSign);

        $signature = '';
        try {
            $rawKey = config('services.dana.private_key');
            $cleanKey = str_replace(["-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----", "\r", "\n", " "], "", $rawKey);
            $formattedKey = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($cleanKey, 64, "\n", true) . "\n-----END PRIVATE KEY-----";
            
            $binarySignature = '';
            // Algoritma: SHA256withRSA
            if(!openssl_sign($stringToSign, $binarySignature, $formattedKey, OPENSSL_ALGO_SHA256)) {
                throw new \Exception("OpenSSL Sign Failed");
            }
            $signature = base64_encode($binarySignature);
        } catch (\Exception $e) {
            Log::error("[APPLY TOKEN] Gagal Sign: " . $e->getMessage());
            return ['error' => 'Sign Error'];
        }

        // 2. HEADERS KHUSUS SNAP
        // Perhatikan: X-CLIENT-KEY, bukan X-PARTNER-ID
        $headers = [
            'Content-Type' => 'application/json',
            'X-TIMESTAMP'  => $timestamp,
            'X-CLIENT-KEY' => $clientId,
            'X-SIGNATURE'  => $signature,
        ];

        // 3. BODY KHUSUS SNAP
        $body = [
            "grantType"      => "AUTHORIZATION_CODE",
            "authCode"       => $authCode,
            "refreshToken"   => "", // Kosongkan string
            "additionalInfo" => (object)[] // Object kosong {}
        ];

        // 4. KIRIM REQUEST KE ENDPOINT SNAP
        $url = 'https://api.sandbox.dana.id/v1.0/access-token/b2b2c.htm';

        Log::info("Hitting URL: $url");
        Log::info("Headers:", $headers);
        Log::info("Body:", $body);

        try {
            $response = Http::withHeaders($headers)->post($url, $body);
            
            Log::info("--- SNAP RESPONSE RECEIVED ---");
            Log::info("Response: " . $response->body());

            return $response->json();

        } catch (\Exception $e) {
            Log::error("[APPLY TOKEN] Connection Error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    public function checkBalance(Request $request)
{
    Log::info('[CEK SALDO] Memulai request...');
    $accessToken = $request->access_token ?? session('dana_access_token');

    if (!$accessToken) {
        return back()->with('error', 'Token tidak ditemukan di session.');
    }

    $body = [
        "additionalInfo" => [
            "accessToken" => $accessToken
        ],
        "balanceTypes" => ["BALANCE"],
        "partnerReferenceNo" => "BAL" . date('YmdHis')
    ];

    $response = $this->sendRequest('POST', '/v1.0/balance-inquiry.htm', $body, $accessToken);
    
    // Log hasil mentah dari DANA untuk memastikan server menjawab
    Log::info('[CEK SALDO] Respon DANA:', (array)$response);

    if (isset($response['responseCode']) && $response['responseCode'] == '2001100') {
        $amount = $response['accountInfos'][0]['availableBalance']['value'] ?? '0';
        return back()->with('success', 'Cek Saldo Berhasil!')->with('saldo_terbaru', $amount);
    }

    $errorMsg = $response['responseMessage'] ?? 'Koneksi Timeout atau Respon Kosong';
    return back()->with('error', 'Gagal: ' . $errorMsg)->with('raw_debug', $response);
}

    private function sendRequest($method, $relativePath, $bodyArray, $accessToken = null)
{
    try {
        $timestamp = \Carbon\Carbon::now('Asia/Jakarta')->toIso8601String();
        $clientId  = config('services.dana.client_id');
        $jsonPayload = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $hashedBody = strtolower(hash('sha256', $jsonPayload));
        $stringToSign = strtoupper($method) . ":" . $relativePath . ":" . $accessToken . ":" . $timestamp . ":" . $hashedBody;

        $signature = $this->genSig($stringToSign);

        $headers = [
            'Content-Type'           => 'application/json',
            'X-TIMESTAMP'            => $timestamp,
            'X-SIGNATURE'            => $signature,
            'X-PARTNER-ID'           => $clientId,
            'X-EXTERNAL-ID'          => (string) mt_rand(1000, 9999) . time(),
            'X-DEVICE-ID'            => '09864ADCASA', 
            'CHANNEL-ID'             => '95221',
            'Authorization-Customer' => 'Bearer ' . $accessToken,
        ];

        $url = rtrim(config('services.dana.base_url'), '/') . $relativePath;

        // Tambahkan timeout agar tidak menggantung jika server DANA lambat
        $response = Http::withHeaders($headers)
                        ->timeout(30)
                        ->withBody($jsonPayload, 'application/json')
                        ->post($url);

        return $response->json();

    } catch (\Exception $e) {
        Log::error("[DANA ERROR] " . $e->getMessage());
        return ['responseMessage' => 'Exception: ' . $e->getMessage()];
    }
}

    public function debugForce()
    {
        $accessToken = session('dana_access_token');
        if(!$accessToken) return "<h1>ERROR: Login DANA dulu (Sambungkan Akun) biar dapat Token di session!</h1>";

        echo "<h1>🔍 DANA DIAGNOSTIC TOOL</h1>";
        echo "<p>Testing Token: " . substr($accessToken, 0, 10) . "...</p><hr>";

        $clientId = config('services.dana.client_id');
        $time     = \Carbon\Carbon::now('Asia/Jakarta')->toIso8601String();
        
        // DATA BODY
        $body = [
            "partnerReferenceNo" => 'TEST-' . time(),
            "balanceTypes"       => ["BALANCE"],
            "additionalInfo"     => ["accessToken" => $accessToken]
        ];
        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // ======================================================
        // SKENARIO 1: MURNI LEGACY (Sesuai Screenshot Mas)
        // ======================================================
        echo "<h3>1. Test Legacy Mode (.htm + X-PARTNER-ID + Signature Panjang)</h3>";
        $str1 = "POST:/v1.0/balance-inquiry.htm:" . $time . ":" . $jsonBody;
        $sig1 = $this->genSig($str1);
        $res1 = Http::withHeaders([
            'X-PARTNER-ID' => $clientId,
            'X-TIMESTAMP' => $time,
            'X-SIGNATURE' => $sig1,
            'Content-Type' => 'application/json',
            'ORIGIN' => 'https://tokosancaka.com',
            'CHANNEL-ID' => '95221',
            'Authorization-Customer' => 'Bearer ' . $accessToken,
            'X-EXTERNAL-ID' => 'EXT-1',
            'X-DEVICE-ID' => 'DEV-1'
        ])->withBody($jsonBody, 'application/json')->post('https://api.sandbox.dana.id/v1.0/balance-inquiry.htm');
        
        $this->printResult($res1, $str1);

        // ======================================================
        // SKENARIO 2: HYBRID SNAP (URL Legacy + Signature Pendek)
        // ======================================================
        echo "<h3>2. Test Hybrid Mode (.htm + X-PARTNER-ID + Signature ClientID|Time)</h3>";
        $str2 = $clientId . "|" . $time;
        $sig2 = $this->genSig($str2);
        $res2 = Http::withHeaders([
            'X-PARTNER-ID' => $clientId, // Masih pakai PARTNER-ID
            'X-TIMESTAMP' => $time,
            'X-SIGNATURE' => $sig2,      // Tapi signature SNAP
            'Content-Type' => 'application/json',
            'ORIGIN' => 'https://tokosancaka.com',
            'CHANNEL-ID' => '95221',
            'Authorization-Customer' => 'Bearer ' . $accessToken,
            'X-EXTERNAL-ID' => 'EXT-2',
            'X-DEVICE-ID' => 'DEV-2'
        ])->withBody($jsonBody, 'application/json')->post('https://api.sandbox.dana.id/v1.0/balance-inquiry.htm');
        
        $this->printResult($res2, $str2);

        // ======================================================
        // SKENARIO 3: FORCE SNAP HEADERS (Pakai X-CLIENT-KEY)
        // ======================================================
        echo "<h3>3. Test Force SNAP Headers (.htm + X-CLIENT-KEY + Signature Pendek)</h3>";
        $str3 = $clientId . "|" . $time;
        $sig3 = $this->genSig($str3);
        $res3 = Http::withHeaders([
            'X-CLIENT-KEY' => $clientId, // GANTI JADI CLIENT-KEY
            'X-TIMESTAMP' => $time,
            'X-SIGNATURE' => $sig3,
            'Content-Type' => 'application/json',
            'ORIGIN' => 'https://tokosancaka.com',
            'CHANNEL-ID' => '95221',
            'Authorization-Customer' => 'Bearer ' . $accessToken,
            'X-EXTERNAL-ID' => 'EXT-3',
            'X-DEVICE-ID' => 'DEV-3'
        ])->withBody($jsonBody, 'application/json')->post('https://api.sandbox.dana.id/v1.0/balance-inquiry.htm');
        
        $this->printResult($res3, $str3);
    }

private function genSig($str) {
    $rawKey = config('services.dana.private_key');
    // Bersihkan key dari spasi atau karakter aneh agar OpenSSL tidak gagal
    $cleanKey = str_replace(["-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----", "\r", "\n", " "], "", $rawKey);
    $formattedKey = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($cleanKey, 64, "\n", true) . "\n-----END PRIVATE KEY-----";
    
    $binarySignature = '';
    if (openssl_sign($str, $binarySignature, $formattedKey, OPENSSL_ALGO_SHA256)) {
        return base64_encode($binarySignature);
    }
    Log::error("Gagal Signing: " . openssl_error_string());
    return null;
}

    private function printResult($res, $strToSign) {
        $color = $res->successful() ? 'green' : 'red';
        echo "<div style='border:1px solid #ccc; padding:10px; margin-bottom:10px;'>";
        echo "<strong>Status:</strong> <span style='color:$color; font-weight:bold'>" . $res->status() . "</span><br>";
        echo "<strong>Body:</strong> " . $res->body() . "<br>";
        echo "<small>Signature String: $strToSign</small>";
        echo "</div>";
    }

    public function testKeyData()
{
    $rawKey = config('services.dana.private_key');
    
    echo "<h2>🛠 DANA Key Extractor</h2>";
    
    // Pastikan format PEM lengkap
    if (!str_contains($rawKey, '-----BEGIN PRIVATE KEY-----')) {
        $formattedKey = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($rawKey, 64, "\n", true) . "\n-----END PRIVATE KEY-----";
    } else {
        $formattedKey = $rawKey;
    }

    $res = openssl_get_privatekey($formattedKey);
    if ($res === false) {
        echo "<p style='color:red;'>❌ ERROR: Private Key di .env rusak!</p>";
    } else {
        // AMBIL PUBLIC KEY DARI PRIVATE KEY TERSEBUT
        $details = openssl_pkey_get_details($res);
        $publicKey = $details['key'];

        echo "<p style='color:green;'>✅ Public Key Berhasil Diekstrak!</p>";
        echo "<p>Silakan <b>Copy SEMUA teks di bawah ini</b> dan masukkan ke Dashboard DANA (Bagian Signature/Public Key):</p>";
        echo "<textarea style='width:100%; height:250px; font-family:monospace; background:#f4f4f4; padding:10px;' readonly>" . $publicKey . "</textarea>";
        
        echo "<br><br><p>Setelah di-update di DANA, silakan coba Cek Saldo lagi menggunakan Skenario 1.</p>";
    }
}

public function accountInquiry()
{
    Log::info('[TEST INQUIRY] Memulai pengetesan Account Inquiry...');

    // Data sesuai dokumentasi SNAP Service Code 37
    $body = [
        "partnerReferenceNo" => 'REQ-' . time(),
        "customerNumber"     => "62810987654321", // Contoh nomor tujuan
        "amount"             => [
            "value"    => "10000.00",
            "currency" => "IDR"
        ],
        "transactionDate"    => \Carbon\Carbon::now('Asia/Jakarta')->toIso8601String(),
        "additionalInfo"     => [
            "fundType" => "AGENT_TOPUP_FOR_USER_SETTLE" // Parameter wajib
        ]
    ];

    // Menggunakan helper sendRequest Mas yang sudah ada
    // Path: /v1.0/emoney/account-inquiry.htm
    $response = $this->sendRequest('POST', '/v1.0/emoney/account-inquiry.htm', $body, null);

    // Tampilkan hasil mentah ke layar untuk diagnosa
    return response()->json($response);
}

    
}