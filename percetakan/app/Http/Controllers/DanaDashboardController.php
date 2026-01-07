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
    
    // =========================================================================
    // 4. CEK SALDO (FIXED LOGIC)
    // =========================================================================
    public function checkBalance(Request $request)
    {
        Log::info('=========================================');
        Log::info('[CEK SALDO] Request Initiated');

        $accessToken = $request->access_token; 
        if(!$accessToken) return back()->with('error', 'Access Token Wajib Diisi!');

        // Struktur Body Wajib V1.0
        $body = [
            "partnerReferenceNo" => 'BAL-' . time(),
            "balanceTypes"       => ["BALANCE"],
            "additionalInfo"     => ["accessToken" => $accessToken]
        ];

        // Kirim Request
        $response = $this->sendRequest('POST', '/v1.0/balance-inquiry.htm', $body, $accessToken);
        
        // Cek Response Code
        if(isset($response['responseCode']) && $response['responseCode'] == '2001100') {
            // Ambil nominal saldo
            $saldo = $response['accountInfos'][0]['availableBalance']['value'] ?? '0';
            
            Log::info("[CEK SALDO] SUKSES! Saldo: $saldo");
            
            // Kirim ke Blade session 'saldo_terbaru'
            return back()
                ->with('success', "Cek Saldo Berhasil!")
                ->with('saldo_terbaru', $saldo); 
        }

        $msg = $response['responseMessage'] ?? 'Unknown Error';
        return back()->with('error', 'Gagal Cek Saldo: ' . $msg);
    }

    // =========================================================================
    // HELPER: SEND REQUEST (HYBRID SIGNATURE SWITCHER)
    // =========================================================================
    private function sendRequest($method, $relativePath, $bodyArray, $accessToken = null)
    {
        Log::info("--- START REQUEST [$method] $relativePath ---");
        
        // 1. JSON ENCODE (Minified, tanpa spasi)
        // Kita sort dulu key-nya biar aman (A-Z)
        ksort($bodyArray);
        if(isset($bodyArray['additionalInfo']) && is_array($bodyArray['additionalInfo'])) {
            ksort($bodyArray['additionalInfo']);
        }
        $jsonBody = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        $timestamp = \Carbon\Carbon::now('Asia/Jakarta')->toIso8601String();
        $clientId  = config('services.dana.client_id');
        
        // 2. LOGIKA TANDA TANGAN (SIGNATURE SWITCHING)
        if ($accessToken) {
            // [JIKA CEK SALDO / TRANSAKSI] -> Pakai Format V1.0
            // Rumus: Method + ":" + Path + ":" + Timestamp + ":" + Body
            $stringToSign = $method . ":" . $relativePath . ":" . $timestamp . ":" . $jsonBody;
        } else {
            // [JIKA APPLY TOKEN] -> Pakai Format SNAP (Terbukti Sukses)
            // Rumus: ClientID + "|" + Timestamp
            $stringToSign = $clientId . "|" . $timestamp;
        }

        Log::info("StringToSign: " . $stringToSign);

        // 3. GENERATE SIGNATURE
        $signature = '';
        try {
            $rawKey = config('services.dana.private_key');
            $cleanKey = str_replace(["-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----", "\r", "\n", " "], "", $rawKey);
            $formattedKey = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($cleanKey, 64, "\n", true) . "\n-----END PRIVATE KEY-----";
            
            $binarySignature = '';
            openssl_sign($stringToSign, $binarySignature, $formattedKey, OPENSSL_ALGO_SHA256);
            $signature = base64_encode($binarySignature);
        } catch (\Exception $e) {
            return ['error' => 'Signature Error'];
        }

        // 4. HEADERS
        $headers = [
            'Content-Type'  => 'application/json',
            'X-TIMESTAMP'   => $timestamp,
            'X-SIGNATURE'   => $signature,
            'CHANNEL-ID'    => '95221',
            'ORIGIN'        => 'https://tokosancaka.com',
        ];

        // 5. HEADER SWITCHING
        if($accessToken) {
            // Transaksi V1.0 butuh X-PARTNER-ID
            $headers['X-PARTNER-ID'] = $clientId; 
            $headers['X-EXTERNAL-ID'] = \Illuminate\Support\Str::random(32);
            $headers['X-DEVICE-ID'] = 'DEVICE-' . time();
            $headers['Authorization-Customer'] = 'Bearer ' . $accessToken;
        } else {
            // Apply Token SNAP butuh X-CLIENT-KEY
            $headers['X-CLIENT-KEY'] = $clientId;
        }

        $fullUrl = 'https://api.sandbox.dana.id' . $relativePath;
        
        try {
            $response = Http::withHeaders($headers)->withBody($jsonBody, 'application/json')->post($fullUrl);
            Log::info("Response: " . $response->body());
            return $response->json();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // =========================================================================
    // 5. TOPUP / TRANSFER SALDO
    // =========================================================================
    public function topupSaldo(Request $request)
    {
        Log::info('=========================================');
        Log::info('[TOPUP] Request Initiated');

        $phoneInput = $request->phone;
        $amount = $request->amount;
        $finalPhone = $phoneInput;

        Log::info("[TOPUP] Input Awal -> Phone: $phoneInput, Amount: $amount");

        // 1. Validasi Format Nomor (Ubah 08 jadi 62)
        if(substr($phoneInput, 0, 2) == '08') {
            $finalPhone = '62' . substr($phoneInput, 1);
            Log::info("[TOPUP] Format Phone Diubah ke International: $finalPhone");
        }

        // 2. Override Nomor Magic Sandbox (Khusus Testing)
        if(env('APP_ENV') != 'production') {
            Log::info('[TOPUP] Mode SANDBOX Terdeteksi: Menggunakan Magic Number 08123456789');
            $finalPhone = '08123456789'; 
        }

        $orderId = 'TOPUP-' . time();
        Log::info("[TOPUP] Generated Order ID: $orderId");

        $body = [
            "partnerReferenceNo" => $orderId,
            "amount" => ["value" => $amount . ".00", "currency" => "IDR"],
            "feeAmount" => ["value" => "0.00", "currency" => "IDR"],
            "customerNumber" => $finalPhone,
            "additionalInfo" => ["fundType" => "TRANS_TO_USER"]
        ];

        // Kirim Request
        Log::info('[TOPUP] Mengirim Request ke Helper...');
        $response = $this->sendRequest('POST', '/v1.0/emoney/topup.htm', $body);

        // Cek Hasil
        if(isset($response['responseCode']) && substr($response['responseCode'], 0, 3) == '200') {
            Log::info("[TOPUP] SUKSES! Response Code: " . $response['responseCode']);
            return back()->with('success', "Topup Rp $amount ke $finalPhone Berhasil!");
        }

        Log::error('[TOPUP] GAGAL. Pesan Error DANA:', $response);
        return back()->with('error', 'Topup Gagal: ' . json_encode($response));
    }

    
}