<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DanaDashboardController extends Controller
{
    public function index()
    {
        // Menampilkan semua data untuk dashboard admin
        $affiliates = DB::table('affiliates')->orderBy('id', 'DESC')->get();
        return view('dana_dashboard', compact('affiliates'));
    }

    // 1. START BINDING (LOGIKA AWAL BOS)
    public function startBinding(Request $request)
    {
        Log::info('[BINDING] Memulai proses redirect ke DANA Portal...');
        
        $affiliateId = $request->affiliate_id ?? 11;

        $queryParams = [
            'partnerId'   => config('services.dana.x_partner_id'),
            'timestamp'   => now('Asia/Jakarta')->toIso8601String(),
            'externalId'  => 'BIND-' . $affiliateId . '-' . time(),
            'merchantId'  => config('services.dana.merchant_id'),
            'redirectUrl' => config('services.dana.redirect_url_oauth'), 
            'state'       => 'ID-' . $affiliateId,
            'scopes'      => 'QUERY_BALANCE,MINI_DANA,DEFAULT_BASIC_PROFILE',
        ];

        return redirect("https://m.sandbox.dana.id/d/portal/oauth?" . http_build_query($queryParams));
    }

    public function handleCallback(Request $request)
{
    Log::info('[DANA CALLBACK] Mendapatkan Auth Code:', $request->all());

    $authCode = $request->input('auth_code');
    // Ambil ID dari state yang kita kirim saat startBinding (contoh: ID-11)
    $state = $request->input('state');
    $affiliateId = $state ? str_replace('ID-', '', $state) : 11;

    if ($authCode) {
        // 1. Simpan Auth Code dulu (biar tidak hilang kalau exchange gagal)
        DB::table('affiliates')->where('id', $affiliateId)->update([
            'dana_auth_code' => $authCode,
            'updated_at' => now()
        ]);

        // 2. LANGSUNG TUKAR KE ACCESS TOKEN (Logika Exchange Token)
        try {
            $timestamp = now('Asia/Jakarta')->toIso8601String();
            $path = '/v1.0/access-token/b2b2c.htm';
            
            $body = [
                'grantType' => 'authorization_code',
                'authCode' => $authCode,
                'additionalInfo' => (object)[]
            ];

            $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
            $hashedBody = strtolower(hash('sha256', $jsonBody));
            $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
            $signature = $this->generateSignature($stringToSign);

            $response = Http::withHeaders([
                'X-TIMESTAMP' => $timestamp,
                'X-SIGNATURE' => $signature,
                'X-PARTNER-ID' => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time(),
                'Content-Type' => 'application/json'
            ])->post('https://api.sandbox.dana.id' . $path, $body);

            $result = $response->json();

            if (isset($result['responseCode']) && $result['responseCode'] == '2001100') {
                // SUKSES! Simpan Token ke Database
                $accessToken = $result['accessToken'];
                DB::table('affiliates')->where('id', $affiliateId)->update([
                    'dana_access_token' => $accessToken,
                    'updated_at' => now()
                ]);
                return redirect()->route('dana.dashboard')->with('success', 'Akun Berhasil Terhubung & Token Didapat!');
            }

            Log::error('[EXCHANGE FAILED]', [$result]);
            return redirect()->route('dana.dashboard')->with('error', 'Auth Code OK, tapi Gagal Tukar Token: ' . ($result['responseMessage'] ?? 'Unknown'));

        } catch (\Exception $e) {
            return redirect()->route('dana.dashboard')->with('error', 'Sistem Error: ' . $e->getMessage());
        }
    }

    return redirect()->route('dana.dashboard')->with('error', 'Gagal mendapatkan Auth Code');
}

    // 3. CEK SALDO USER (LOGIKA SNAP 2001100 - TIDAK DIRUBAH)
    public function checkBalance(Request $request)
    {
        $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
        $accessToken = $request->access_token ?? $aff->dana_access_token;

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
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        $response = Http::withHeaders([
            'X-TIMESTAMP'   => $timestamp,
            'X-SIGNATURE'   => $signature,
            'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
            'X-EXTERNAL-ID' => (string) time(),
            'X-DEVICE-ID'   => 'DANA-DASHBOARD-STATION',
            'CHANNEL-ID'    => '95221',
            'ORIGIN'        => config('services.dana.origin'),
            'Authorization-Customer' => 'Bearer ' . $accessToken,
            'Content-Type'  => 'application/json'
        ])->withBody($jsonBody, 'application/json')->post('https://api.sandbox.dana.id' . $path);

        $result = $response->json();

        if (isset($result['responseCode']) && $result['responseCode'] == '2001100') {
            $amount = $result['accountInfos'][0]['availableBalance']['value'];
            // Simpan ke dana_user_balance (Pemisah Profit)
            DB::table('affiliates')->where('id', $request->affiliate_id)->update(['dana_user_balance' => $amount, 'updated_at' => now()]);
            return back()->with('success', 'Saldo Riil DANA Terupdate!');
        }
        return back()->with('error', 'Gagal: ' . ($result['responseMessage'] ?? 'Error'));
    }

    // 4. CEK SALDO MERCHANT (LOGIKA OPEN API V2.0 - TIDAK DIRUBAH)
    public function checkMerchantBalance(Request $request)
    {
        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $payload = ["request" => ["head" => ["version" => "2.0", "function" => "dana.merchant.queryMerchantResource", "clientId" => config('services.dana.x_partner_id'), "clientSecret" => config('services.dana.client_secret'), "reqTime" => $timestamp, "reqMsgId" => (string) Str::uuid(), "reserve" => "{}"], "body" => ["requestMerchantId" => config('services.dana.merchant_id'), "merchantResourceInfoList" => ["MERCHANT_DEPOSIT_BALANCE"]]]];

        $jsonToSign = json_encode($payload['request'], JSON_UNESCAPED_SLASHES);
        $signature = $this->generateSignature($jsonToSign);
        
        $response = Http::post('https://api.sandbox.dana.id/dana/merchant/queryMerchantResource.htm', ["request" => $payload['request'], "signature" => $signature]);
        $res = $response->json();

        if (isset($res['response']['body']['resultInfo']['resultStatus']) && $res['response']['body']['resultInfo']['resultStatus'] === 'S') {
            $val = json_decode($res['response']['body']['merchantResourceInformations'][0]['value'], true);
            DB::table('affiliates')->where('id', $request->affiliate_id)->update(['dana_merchant_balance' => $val['amount']]);
            return back()->with('success', 'Saldo Merchant Terupdate!');
        }
        return back()->with('error', 'Gagal Cek Merchant');
    }

    private function generateSignature($stringToSign) {
        $privateKey = config('services.dana.private_key');
        $binarySignature = "";
        openssl_sign($stringToSign, $binarySignature, $privateKey, OPENSSL_ALGO_SHA256);
        return base64_encode($binarySignature);
    }
}