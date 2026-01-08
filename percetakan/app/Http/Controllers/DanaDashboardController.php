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
    Log::info('--- [START] CEK SALDO DANA ---');

    $accessToken = $request->access_token ?? session('dana_access_token');
    if (!$accessToken) return back()->with('error', 'Token Kosong.');

    $timestamp = now('Asia/Jakarta')->toIso8601String();
    $path = '/v1.0/balance-inquiry.htm';
    
    $body = [
        'partnerReferenceNo' => 'BAL' . time(),
        'balanceTypes' => ['BALANCE'],
        'additionalInfo' => ['accessToken' => $accessToken]
    ];

    // [PENTING] Gunakan opsi JSON_UNESCAPED_SLASHES agar format JSON bersih (Compact)
    $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
    
    // 1. Generate Signature SNAP
    $hashedBody = strtolower(hash('sha256', $jsonBody));
    
    // Pastikan urutan string to sign benar: METHOD:PATH:TOKEN:TIME:HASHBODY
    $stringToSign = "POST:" . $path . ":" . $accessToken . ":" . $timestamp . ":" . $hashedBody;
    
    $signature = $this->generateSignature($stringToSign);

    $headers = [
        'X-TIMESTAMP'   => $timestamp,
        'X-SIGNATURE'   => $signature,
        'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
        'X-EXTERNAL-ID' => (string) time(),
        'X-DEVICE-ID'   => 'DANA-DASHBOARD-STATION',
        'CHANNEL-ID'    => '95221',
        'ORIGIN'        => config('services.dana.origin'),
        'X-IP-ADDRESS'  => $request->ip() ?? '127.0.0.1',
        'Authorization-Customer' => 'Bearer ' . $accessToken,
        'Content-Type'  => 'application/json'
    ];

    Log::info('[CEK SALDO] StringToSign: ' . $stringToSign);

    try {
        // [PENTING] Gunakan withBody agar Laravel tidak merubah format JSON secara otomatis
        $response = Http::withHeaders($headers)
                        ->withBody($jsonBody, 'application/json') 
                        ->post('https://api.sandbox.dana.id' . $path);

        $result = $response->json();
        Log::info('[CEK SALDO] Respon Raw:', [$response->body()]);

        if (isset($result['responseCode']) && $result['responseCode'] == '2001100') {
            $amount = $result['accountInfos'][0]['availableBalance']['value'] ?? 0;
            return back()->with('success', 'Saldo Berhasil!')->with('saldo_terbaru', $amount);
        }

        return back()->with('error', 'Gagal: ' . ($result['responseMessage'] ?? 'Unknown Error'));

    } catch (\Exception $e) {
        return back()->with('error', 'Sistem Error: ' . $e->getMessage());
    }
}

    private function generateSignature($stringToSign) {
    $privateKey = config('services.dana.private_key');
    
    // Pastikan key dalam format PEM yang benar (ada baris baru)
    $cleanKey = str_replace(["\r", " "], "", $privateKey); 
    
    $binarySignature = "";
    if (openssl_sign($stringToSign, $binarySignature, $cleanKey, OPENSSL_ALGO_SHA256)) {
        return base64_encode($binarySignature);
    }
    return "";
}
}