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
        Log::info('[BINDING] Proses Memulai Binding Dimulai...');

        $clientId    = config('services.dana.client_id');
        $redirectUrl = route('dana.callback'); 
        $state       = Str::random(16); 
        $timestamp   = Carbon::now('Asia/Jakarta')->toIso8601String();

        Log::info("[BINDING] Client ID: $clientId");
        Log::info("[BINDING] Redirect URL: $redirectUrl");

        // Parameter Binding
        $queryParams = [
            'partnerId'     => $clientId,
            'timestamp'     => $timestamp,
            'externalId'    => 'USER-' . time(),
            'channelId'     => '95221', 
            'merchantId'    => config('services.dana.merchant_id'),
            'redirectUrl'   => $redirectUrl,
            'state'         => $state,
            'scopes'        => 'DEFAULT_BASIC_PROFILE,QUERY_BALANCE,MINI_DANA',
            'allowRegistration' => 'true'
        ];

        // URL Portal Sandbox
        $baseUrl = 'https://m.sandbox.dana.id/d/portal/oauth'; 
        $fullUrl = $baseUrl . '?' . http_build_query($queryParams);

        Log::info("[BINDING] Generated Full URL: " . $fullUrl);
        Log::info('[BINDING] Redirecting User ke DANA...');

        return redirect($fullUrl);
    }

    // =========================================================================
    // 3. HANDLE CALLBACK (TERIMA AUTH CODE)
    // =========================================================================
    public function handleCallback(Request $request)
    {
        Log::info('=========================================');
        Log::info('[CALLBACK] DANA Redirect Back Received');
        Log::info('[CALLBACK] Full Query Params:', $request->all());

        $authCode = $request->authCode;
        
        if(!$authCode) {
            Log::error('[CALLBACK] GAGAL: Auth Code tidak ditemukan di URL!');
            return redirect()->route('dana.dashboard')->with('error', 'Gagal Binding: Auth Code tidak ditemukan.');
        }

        Log::info("[CALLBACK] Auth Code Berhasil Didapat: $authCode");

        // Simpan Auth Code ke Session (Untuk Testing)
        session(['dana_auth_code' => $authCode]);
        
        // Disini seharusnya proses "Apply Token" (Tukar AuthCode -> AccessToken)
        // Karena endpoint apply token belum kita set, kita pakai dummy dulu atau manual.
        // session(['dana_access_token' => 'TOKEN_DARI_APPLY_TOKEN_API']); 

        Log::info('[CALLBACK] Auth Code disimpan ke Session. Redirect ke Dashboard.');

        return redirect()->route('dana.dashboard')->with('success', "Binding Berhasil! Auth Code: $authCode");
    }

    // =========================================================================
    // 4. CEK SALDO (BALANCE INQUIRY)
    // =========================================================================
    public function checkBalance(Request $request)
    {
        Log::info('=========================================');
        Log::info('[CEK SALDO] Request Initiated');

        $accessToken = $request->access_token; 

        if(!$accessToken) {
            Log::warning('[CEK SALDO] Access Token Kosong!');
            return back()->with('error', 'Access Token Wajib Diisi!');
        }

        Log::info("[CEK SALDO] Menggunakan Token: " . substr($accessToken, 0, 10) . "...");

        $body = [
            "partnerReferenceNo" => 'BAL-' . time(),
            "balanceTypes"       => ["BALANCE"],
            "additionalInfo"     => ["accessToken" => $accessToken]
        ];

        // Kirim Request via Helper
        Log::info('[CEK SALDO] Mengirim Request ke Helper...');
        $response = $this->sendRequest('POST', '/v1.0/balance-inquiry.htm', $body, $accessToken);
        
        // Log Hasil Akhir
        if(isset($response['responseCode']) && $response['responseCode'] == '2001100') {
            $saldo = $response['accountInfos'][0]['availableBalance']['value'] ?? '0';
            Log::info("[CEK SALDO] SUKSES! Saldo: $saldo");
            return back()->with('success', "Cek Saldo Berhasil! Saldo: Rp " . number_format($saldo));
        }

        Log::error('[CEK SALDO] GAGAL. Response DANA:', $response);
        return back()->with('error', 'Gagal Cek Saldo: ' . json_encode($response));
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

    // =========================================================================
    // HELPER: SEND REQUEST (CORE SYSTEM DENGAN FULL LOG)
    // =========================================================================
    private function sendRequest($method, $relativePath, $bodyArray, $accessToken = null)
    {
        Log::info("--- START HTTP REQUEST [$method] $relativePath ---");
        
        // 1. Siapkan Body JSON
        $jsonBody = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $timestamp = Carbon::now('Asia/Jakarta')->toIso8601String();
        
        Log::info("Payload Body:", $bodyArray);
        Log::info("Timestamp: $timestamp");

        // 2. Generate Signature
        try {
            $rawKey = config('services.dana.private_key');
            
            // Log Key Check (Jangan log key asli, cuma cek ada/tidak)
            if(empty($rawKey)) Log::error("CRITICAL: Private Key Kosong di Config!");

            $cleanKey = str_replace(["-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----", "\r", "\n", " "], "", $rawKey);
            $formattedKey = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($cleanKey, 64, "\n", true) . "\n-----END PRIVATE KEY-----";
            
            $signatureData = $method . ":" . $relativePath . ":" . $timestamp . ":" . $jsonBody;
            
            Log::info("Signature String Data: " . $signatureData);

            $binarySignature = '';
            if(!openssl_sign($signatureData, $binarySignature, $formattedKey, OPENSSL_ALGO_SHA256)) {
                 throw new \Exception("OpenSSL Sign Failed: " . openssl_error_string());
            }
            $signature = base64_encode($binarySignature);
            
            Log::info("Signature Generated: " . substr($signature, 0, 20) . "...");

        } catch (\Exception $e) {
            Log::error("SIGNATURE ERROR: " . $e->getMessage());
            return ['error' => 'Signature Error: ' . $e->getMessage()];
        }

        // 3. Susun Headers
        $headers = [
            'X-PARTNER-ID'  => config('services.dana.client_id'),
            'X-EXTERNAL-ID' => Str::random(32),
            'X-TIMESTAMP'   => $timestamp,
            'X-SIGNATURE'   => $signature,
            'Content-Type'  => 'application/json',
            'CHANNEL-ID'    => '95221',
        ];

        // Tambah Token & Device ID jika ada (untuk Cek Saldo)
        if($accessToken) {
            $headers['Authorization-Customer'] = 'Bearer ' . $accessToken;
            $headers['X-DEVICE-ID'] = 'DEVICE-' . time();
            Log::info("Header Authorization-Customer ditambahkan.");
        }

        Log::info("Request Headers:", $headers);

        $fullUrl = 'https://api.sandbox.dana.id' . $relativePath;
        Log::info("Hitting URL: $fullUrl");

        // 4. Eksekusi Request
        try {
            $response = Http::withHeaders($headers)->withBody($jsonBody, 'application/json')->post($fullUrl);
            
            // LOG RESPONSE BALASAN DARI DANA
            Log::info("--- DANA RESPONSE RECEIVED ---");
            Log::info("Status Code: " . $response->status());
            Log::info("Response Body: " . $response->body());

            return $response->json();

        } catch (\Exception $e) {
            Log::error("HTTP CONNECTION ERROR: " . $e->getMessage());
            return ['error' => 'Connection Error: ' . $e->getMessage()];
        }
    }
}