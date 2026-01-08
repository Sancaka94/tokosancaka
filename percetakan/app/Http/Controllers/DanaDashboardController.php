<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DanaDashboardController extends Controller
{
    public function index()
    {
        return view('dana_dashboard');
    }

    // 1. LOGIKA AWAL: Hanya Redirect ke DANA
    public function startBinding()
    {
        $queryParams = [
            'partnerId'   => config('services.dana.x_partner_id'),
            'timestamp'   => now('Asia/Jakarta')->toIso8601String(),
            'externalId'  => 'BIND-' . time(),
            'merchantId'  => config('services.dana.merchant_id'),
            'redirectUrl' => config('services.dana.redirect_url_oauth'),
            'state'       => bin2hex(random_bytes(8)),
            'scopes'      => 'QUERY_BALANCE,MINI_DANA,DEFAULT_BASIC_PROFILE',
        ];

        return redirect("https://m.sandbox.dana.id/d/portal/oauth?" . http_build_query($queryParams));
    }

    // 2. LOGIKA AWAL: Hanya ambil authCode dari URL dan simpan ke session
    public function handleCallback(Request $request)
{
    Log::info('[DANA CALLBACK] Data Masuk:', $request->all());

    // DANA mengirim 'auth_code' (pakai underscore), bukan 'authCode'
    $authCode = $request->input('auth_code'); 

    if ($authCode) {
        // Simpan ke session agar muncul di Blade {{ session('dana_auth_code') }}
        session(['dana_auth_code' => $authCode]);
        
        Log::info('[CALLBACK] Berhasil menyimpan auth_code ke session.');
        
        return redirect()->route('dana.dashboard')->with('success', 'Auth Code Berhasil Didapat!');
    }

    Log::error('[CALLBACK] auth_code tidak ditemukan dalam request.');
    return redirect()->route('dana.dashboard')->with('error', 'Gagal mendapatkan Auth Code dari DANA');
}

    // 3. LOGIKA BARU: Cek Saldo (Tetap saya sertakan karena ini yang tadi sukses 2001100)
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

    // Urutan SNAP Sukses: METHOD:PATH:HASH:TIMESTAMP
    $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
    $signature = $this->generateSignature($stringToSign);

    // HEADERS LENGKAP (Jangan ada yang dirubah lagi bos)
    $headers = [
        'X-TIMESTAMP'   => $timestamp,
        'X-SIGNATURE'   => $signature,
        'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
        'X-EXTERNAL-ID' => (string) time(),
        'X-DEVICE-ID'   => 'DANA-DASHBOARD-STATION', // INI YANG TADI ILANG
        'CHANNEL-ID'    => '95221',                  // INI JUGA WAJIB
        'ORIGIN'        => config('services.dana.origin'),
        'X-IP-ADDRESS'  => $request->ip() ?? '127.0.0.1',
        'Authorization-Customer' => 'Bearer ' . $accessToken,
        'Content-Type'  => 'application/json'
    ];

    try {
        $response = Http::withHeaders($headers)
                        ->withBody($jsonBody, 'application/json')
                        ->post('https://api.sandbox.dana.id' . $path);

        $result = $response->json();

        if (isset($result['responseCode']) && $result['responseCode'] == '2001100') {
            $amount = $result['accountInfos'][0]['availableBalance']['value'] ?? 0;
            return back()->with('success', 'Saldo Berhasil!')->with('saldo_terbaru', $amount);
        }

        return back()->with('error', 'Gagal: ' . ($result['responseMessage'] ?? 'Error'));
    } catch (\Exception $e) {
        return back()->with('error', 'Sistem Error: ' . $e->getMessage());
    }
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