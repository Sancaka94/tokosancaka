<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
// Import SDK DANA
use Dana\Configuration;
use Dana\PaymentGateway\v1\Api\PaymentGatewayApi;
use Dana\PaymentGateway\v1\Model\BalanceInquiryRequest;
use Dana\Env;
use GuzzleHttp\Client;

class DanaDashboardController extends Controller
{
    private $apiInstance;

    public function __construct()
    {
        // Inisialisasi SDK menggunakan data dari config/services.php
        $config = new Configuration();
        $config->setApiKey('X_PARTNER_ID', config('services.dana.x_partner_id'));
        $config->setApiKey('PRIVATE_KEY', config('services.dana.private_key'));
        $config->setApiKey('ORIGIN', config('services.dana.origin'));
        
        $env = config('services.dana.dana_env') === 'PRODUCTION' ? Env::PRODUCTION : Env::SANDBOX;
        $config->setApiKey('ENV', $env);

        $this->apiInstance = new PaymentGatewayApi(new Client(), $config);
    }

    public function index()
    {
        return view('dana_dashboard');
    }

    // =========================================================================
    // 1. MULAI BINDING (Mendapatkan Auth Code)
    // =========================================================================
    public function startBinding()
    {
        $clientId    = config('services.dana.x_partner_id');
        $redirectUrl = config('services.dana.redirect_url_oauth');
        $timestamp   = now('Asia/Jakarta')->toIso8601String();

        $queryParams = [
            'partnerId'   => $clientId,
            'timestamp'   => $timestamp,
            'externalId'  => 'USER-' . time(),
            'merchantId'  => config('services.dana.merchant_id'),
            'redirectUrl' => $redirectUrl,
            'state'       => bin2hex(random_bytes(8)),
            'scopes'      => 'DEFAULT_BASIC_PROFILE,QUERY_BALANCE,MINI_DANA',
        ];

        $url = 'https://m.sandbox.dana.id/d/portal/oauth?' . http_build_query($queryParams);
        return redirect($url);
    }

    // =========================================================================
    // 2. CALLBACK (Tukar Auth Code jadi Access Token)
    // =========================================================================
    public function handleCallback(Request $request)
    {
        $authCode = $request->authCode;

        if (!$authCode) {
            return redirect()->route('dana.dashboard')->with('error', 'Auth Code tidak ditemukan.');
        }

        try {
            // Menggunakan SDK untuk Apply Token (B2B2C)
            // SDK otomatis menangani Signature B2B2C (ClientKey | Timestamp)
            $response = $this->apiInstance->applyTokenB2B2C([
                "grantType" => "AUTHORIZATION_CODE",
                "authCode"  => $authCode
            ]);

            if ($response->getResponseCode() == '2001100') {
                session(['dana_access_token' => $response->getAccessToken()]);
                return redirect()->route('dana.dashboard')->with('success', 'Akun Berhasil Tersambung!');
            }

            return redirect()->route('dana.dashboard')->with('error', 'Gagal Token: ' . $response->getResponseMessage());

        } catch (\Exception $e) {
            Log::error("DANA Token Error: " . $e->getMessage());
            return redirect()->route('dana.dashboard')->with('error', 'Sistem Error: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // 3. CEK SALDO (Menggunakan SDK)
    // =========================================================================
    public function checkBalance()
    {
        $accessToken = session('dana_access_token');

        if (!$accessToken) {
            return back()->with('error', 'Silakan sambungkan akun DANA terlebih dahulu.');
        }

        try {
            // Buat Request Object sesuai standar SDK
            $balanceRequest = new BalanceInquiryRequest([
                'partnerReferenceNo' => 'BAL' . time(),
                'balanceTypes' => ['BALANCE'],
                'additionalInfo' => ['accessToken' => $accessToken]
            ]);

            // SDK otomatis menghitung Signature B2B2C yang rumit (Method:Path:Token:Time:Hash)
            $result = $this->apiInstance->balanceInquiry($balanceRequest, $accessToken);

            if ($result->getResponseCode() == '2001100') {
                $amount = $result->getAccountInfos()[0]->getAvailableBalance()->getValue();
                return back()->with('success', 'Saldo Berhasil Diambil!')->with('saldo_terbaru', $amount);
            }

            return back()->with('error', 'Gagal: ' . $result->getResponseMessage());

        } catch (\Exception $e) {
            Log::error("DANA Balance Error: " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}