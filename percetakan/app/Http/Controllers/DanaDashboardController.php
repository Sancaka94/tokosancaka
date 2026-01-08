<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DanaDashboardController extends Controller
{
    public function index()
    {
        return view('dana_dashboard');
    }

    // =========================================================================
    // 1. MULAI BINDING (Sesuai nama di Route/Blade kamu)
    // =========================================================================
    public function startBinding()
    {
        Log::info('[BINDING] Memulai proses redirect ke DANA Portal...');

        $queryParams = [
            'partnerId'   => config('services.dana.x_partner_id'),
            'timestamp'   => now('Asia/Jakarta')->toIso8601String(),
            'externalId'  => 'BIND-' . time(),
            'merchantId'  => config('services.dana.merchant_id'),
            'redirectUrl' => config('services.dana.redirect_url_oauth'), 
            'state'       => Str::random(10),
            'scopes'      => 'QUERY_BALANCE,MINI_DANA,DEFAULT_BASIC_PROFILE',
        ];

        $url = "https://m.sandbox.dana.id/d/portal/oauth?" . http_build_query($queryParams);
        
        return redirect($url);
    }

    // =========================================================================
    // 2. HANDLE CALLBACK (Tukar Auth Code jadi Token)
    // =========================================================================
    public function handleCallback(Request $request)
    {
        // DANA mengirimkan authCode di URL
        $authCode = $request->authCode;

        if (!$authCode) {
            Log::error('[CALLBACK] Auth Code tidak ditemukan di URL');
            return redirect()->route('dana.dashboard')->with('error', 'Gagal mendapatkan Auth Code');
        }

        Log::info('[CALLBACK] Auth Code didapat: ' . $authCode);
        session(['dana_auth_code' => $authCode]);

        // Tukar ke Access Token
        return $this->exchangeToken($authCode);
    }

    private function exchangeToken($authCode)
    {
        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $path = '/v1.0/access-token/b2b2c.htm';
        
        $body = [
            "grantType" => "AUTHORIZATION_CODE",
            "authCode"  => $authCode
        ];

        // RUMUS SIGNATURE APPLY TOKEN (Asymmetric): ClientID | Timestamp
        $stringToSign = config('services.dana.x_partner_id') . "|" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        try {
            $response = Http::withHeaders([
                'X-TIMESTAMP'  => $timestamp,
                'X-CLIENT-KEY' => config('services.dana.x_partner_id'),
                'X-SIGNATURE'  => $signature,
                'Content-Type' => 'application/json'
            ])->post('https://api.sandbox.dana.id' . $path, $body);

            $data = $response->json();
            Log::info('[TOKEN EXCHANGE] Respon:', [$data]);

            if (isset($data['accessToken'])) {
                session(['dana_access_token' => $data['accessToken']]);
                return redirect()->route('dana.dashboard')->with('success', 'Binding Sukses! Token disimpan.');
            }

            return redirect()->route('dana.dashboard')->with('error', 'Gagal tukar token: ' . ($data['responseMessage'] ?? 'Unknown Error'));
        } catch (\Exception $e) {
            return redirect()->route('dana.dashboard')->with('error', 'Sistem Error: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // 3. CEK SALDO (Logika SNAP 2.0 yang sudah Sukses)
    // =========================================================================
    public function checkBalance(Request $request)
    {
        $accessToken = $request->access_token ?? session('dana_access_token');
        if (!$accessToken) return back()->with('error', 'Token Kosong.');

        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $path = '/v1.0/balance-inquiry.htm';
        
        $body = [
            'partnerReferenceNo' => 'BAL' . time(),
            'balanceTypes' => ['BALANCE'],
            'additionalInfo' => ['accessToken' => $accessToken]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $hashedBody = strtolower(hash('sha256', $jsonBody));

        // RUMUS SIGNATURE TRANSACTIONAL: METHOD:PATH:HASH:TIMESTAMP
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        $headers = [
            'X-TIMESTAMP'   => $timestamp,
            'X-SIGNATURE'   => $signature,
            'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
            'X-EXTERNAL-ID' => (string) time(),
            'CHANNEL-ID'    => '95221',
            'ORIGIN'        => config('services.dana.origin'),
            'Authorization-Customer' => 'Bearer ' . $accessToken,
            'Content-Type'  => 'application/json'
        ];

        $response = Http::withHeaders($headers)
                        ->withBody($jsonBody, 'application/json')
                        ->post('https://api.sandbox.dana.id' . $path);

        $result = $response->json();

        if (isset($result['responseCode']) && $result['responseCode'] == '2001100') {
            $amount = $result['accountInfos'][0]['availableBalance']['value'] ?? 0;
            return back()->with('success', 'Saldo Berhasil!')->with('saldo_terbaru', $amount);
        }

        return back()->with('error', 'Gagal: ' . ($result['responseMessage'] ?? 'Gagal Inquiry'));
    }

    // =========================================================================
    // 4. TOPUP / TRANSFER
    // =========================================================================
    public function topupSaldo(Request $request)
    {
        // Kode topupSaldo yang tadi sudah dibuat...
        // (Sama seperti sebelumnya)
    }

    // =========================================================================
    // HELPER: SIGNATURE RSA-256
    // =========================================================================
    private function generateSignature($stringToSign)
    {
        $privateKey = config('services.dana.private_key');
        $binarySignature = "";
        openssl_sign($stringToSign, $binarySignature, $privateKey, OPENSSL_ALGO_SHA256);
        return base64_encode($binarySignature);
    }
}