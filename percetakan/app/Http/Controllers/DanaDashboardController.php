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
    $affiliates = DB::table('affiliates')->orderBy('id', 'DESC')->get();
    return view('dana_dashboard', compact('affiliates'));
}

    public function handleCallback(Request $request)
    {
        // Logika awal bos: Ambil auth_code (underscore sesuai log)
        $authCode = $request->input('auth_code');

        if ($authCode) {
            DB::table('affiliates')->where('id', 11)->update([
                'dana_auth_code' => $authCode,
                'updated_at' => now()
            ]);
            return redirect()->route('dana.dashboard', ['id' => 11])->with('success', 'Auth Code Berhasil Disinkronkan ke Database!');
        }
        return redirect()->route('dana.dashboard')->with('error', 'Gagal mendapatkan Auth Code');
    }

    public function checkBalance(Request $request)
    {
        // Ambil token dari database agar tidak hilang saat refresh
        $affiliate = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
        $accessToken = $request->access_token ?? $affiliate->dana_access_token;

        if (!$accessToken) return back()->with('error', 'Token Kosong.');

        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $path = '/v1.0/balance-inquiry.htm';
        
        $body = [
            'partnerReferenceNo' => 'BAL' . time(),
            'balanceTypes' => ['BALANCE'],
            'additionalInfo' => ['accessToken' => $accessToken]
        ];

        // LOGIKA SIGNATURE SNAP YANG SUDAH SUKSES (JANGAN DIUBAH)
        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        $headers = [
            'X-TIMESTAMP'   => $timestamp,
            'X-SIGNATURE'   => $signature,
            'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
            'X-EXTERNAL-ID' => (string) time(),
            'X-DEVICE-ID'   => 'DANA-DASHBOARD-STATION',
            'CHANNEL-ID'    => '95221',
            'ORIGIN'        => config('services.dana.origin'),
            'Authorization-Customer' => 'Bearer ' . $accessToken,
            'Content-Type'  => 'application/json'
        ];

        $response = Http::withHeaders($headers)->withBody($jsonBody, 'application/json')
                        ->post('https://api.sandbox.dana.id' . $path);

        $result = $response->json();

        if (isset($result['responseCode']) && $result['responseCode'] == '2001100') {
            $amount = $result['accountInfos'][0]['availableBalance']['value'];

            // SIMPAN OTOMATIS KE DATABASE (SINKRONISASI REALTIME)
            DB::table('affiliates')->where('id', $request->affiliate_id)->update([
                'balance' => $amount,
                'dana_access_token' => $accessToken,
                'updated_at' => now()
            ]);

            return back()->with('success', 'Saldo User Berhasil Diperbarui!');
        }

        return back()->with('error', 'Gagal: ' . ($result['responseMessage'] ?? 'Error'));
    }

    public function checkMerchantBalance(Request $request)
    {
        // LOGIKA OPEN API V2.0 YANG SUDAH SESUAI DOKUMEN
        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $payloadData = [
            "request" => [
                "head" => [
                    "version" => "2.0",
                    "function" => "dana.merchant.queryMerchantResource",
                    "clientId" => config('services.dana.x_partner_id'),
                    "clientSecret" => config('services.dana.client_secret'),
                    "reqTime" => $timestamp,
                    "reqMsgId" => (string) Str::uuid(),
                    "reserve" => "{}"
                ],
                "body" => [
                    "requestMerchantId" => config('services.dana.merchant_id'),
                    "merchantResourceInfoList" => ["MERCHANT_DEPOSIT_BALANCE"]
                ]
            ]
        ];

        $jsonToSign = json_encode($payloadData['request'], JSON_UNESCAPED_SLASHES);
        $signature = $this->generateSignature($jsonToSign);

        $response = Http::post('https://api.sandbox.dana.id/dana/merchant/queryMerchantResource.htm', [
            "request" => $payloadData['request'],
            "signature" => $signature
        ]);

        $result = $response->json();
        $body = $result['response']['body'] ?? null;

        if (isset($body['resultInfo']['resultStatus']) && $body['resultInfo']['resultStatus'] === 'S') {
            $resources = json_decode($body['merchantResourceInformations'][0]['value'], true);
            
            // SIMPAN SALDO MERCHANT KE DATABASE
            DB::table('affiliates')->where('id', $request->affiliate_id)->update([
                'dana_merchant_balance' => $resources['amount']
            ]);

            return back()->with('success', 'Saldo Merchant Terupdate!');
        }
        return back()->with('error', 'Gagal cek saldo merchant.');
    }

    private function generateSignature($stringToSign)
    {
        $privateKey = config('services.dana.private_key');
        $binarySignature = "";
        openssl_sign($stringToSign, $binarySignature, $privateKey, OPENSSL_ALGO_SHA256);
        return base64_encode($binarySignature);
    }
}