<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant;
use App\Models\Affiliate;
use App\Models\TopUp;
use Illuminate\Support\Str;
use App\Models\SettingApi;

class DanaDashboardController extends Controller
{
    protected $tenantId;

    public function __construct()
    {
        $this->applyDynamicConfig();
    }

    private function applyDynamicConfig()
    {
        $settings = \App\Models\SettingApi::pluck('value', 'key')->toArray();
        $isProduction = ($settings['dana_production_mode'] ?? '0') == '1';

        if ($isProduction) {
            config([
                'services.dana.dana_env'      => 'PRODUCTION',
                'services.dana.base_url'      => 'https://api.dana.id',
                'services.dana.portal_url'    => 'https://m.dana.id/d/portal/oauth',
                'services.dana.redirect_url_oauth' => 'https://apps.tokosancaka.com/dana/callback',
                'services.dana.merchant_id'   => $settings['dana_prod_merchant_id'] ?? env('DANA_PROD_MERCHANT_ID'),
                'services.dana.client_id'     => $settings['dana_prod_client_id'] ?? env('DANA_PROD_CLIENT_ID'),
                'services.dana.x_partner_id'  => $settings['dana_prod_client_id'] ?? env('DANA_PROD_CLIENT_ID'),
                'services.dana.private_key'   => $settings['dana_prod_private_key'] ?? env('DANA_PROD_PRIVATE_KEY'),
                'services.dana.client_secret' => $settings['dana_prod_client_secret'] ?? env('DANA_PROD_CLIENT_SECRET'),
                'services.dana.origin'        => env('DANA_ORIGIN', 'https://tokosancaka.com'),
            ]);
        } else {
            config([
                'services.dana.dana_env'      => 'SANDBOX',
                'services.dana.base_url'      => 'https://api.sandbox.dana.id',
                'services.dana.portal_url'    => 'https://m.sandbox.dana.id/d/portal/oauth',
                'services.dana.redirect_url_oauth' => 'https://apps.tokosancaka.com/dana/callback',
                'services.dana.merchant_id'   => $settings['dana_sandbox_merchant_id'] ?? env('DANA_MERCHANT_ID'),
                'services.dana.client_id'     => $settings['dana_sandbox_client_id'] ?? env('DANA_X_PARTNER_ID'),
                'services.dana.x_partner_id'  => $settings['dana_sandbox_client_id'] ?? env('DANA_X_PARTNER_ID'),
                'services.dana.private_key'   => $settings['dana_sandbox_private_key'] ?? env('DANA_PRIVATE_KEY'),
                'services.dana.client_secret' => $settings['dana_sandbox_client_secret'] ?? env('DANA_CLIENT_SECRET'),
                'services.dana.origin'        => env('DANA_ORIGIN', 'https://tokosancaka.com'),
            ]);
        }
    }

    public function index(Request $request)
    {
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];
        $isMainDomain = ($subdomain === 'tokosancaka' || $subdomain === 'app' || $subdomain === 'localhost');

        $user = auth()->user();

        if ($user->role === 'super_admin' && $isMainDomain) {
            $transactions = DB::table('dana_transactions')->orderBy('id', 'DESC')->paginate(15);
            $affiliates = DB::table('affiliates')->get();
        } else {
            $transactions = DB::table('dana_transactions')->where('tenant_id', $user->tenant_id)->orderBy('id', 'DESC')->paginate(10);
            $affiliates = DB::table('affiliates')->where('tenant_id', $user->tenant_id)->get();
        }

        return view('dana_dashboard', compact('transactions', 'affiliates'));
    }

    // --- KODE EXACT DARI ANDA ---
    public function startBinding(Request $request)
    {
        Log::info('LOG LOG: [BINDING] Memulai proses redirect ke DANA (Debug)...');
        $user = \Illuminate\Support\Facades\Auth::user();

        // Menggunakan fallback ID jika id_pengguna tidak ada di tabel user Anda
        $userId = $user->id_pengguna ?? $user->id;

        // Simpan id_pengguna ke session sebagai cadangan pengenal user
        session(['dana_user_id' => $userId]);

        // DANA OAuth 2.0 Web Authorize Parameters (Standar Resmi)
        $queryParams = [
            'clientId'     => config('services.dana.client_id'), // PENTING: Harus clientId, bukan partnerId
            'redirectUrl'  => url('/dana/callback'), // Pastikan route ini sesuai dengan setting di Dashboard DANA Anda
            'scopes'       => 'AGREEMENT_PAY,QUERY_BALANCE,DEFAULT_BASIC_PROFILE', // AGREEMENT_PAY wajib untuk Direct Debit!
            'state'        => \Illuminate\Support\Str::random(16),
            'terminalType' => 'WEB', // Penting agar UI DANA tahu dirender sebagai Web
            'merchantId'   => config('services.dana.merchant_id'),
        ];

        $baseUrl = config('services.dana.dana_env') === 'PRODUCTION'
            ? 'https://m.dana.id/d/portal/oauth'
            : 'https://m.sandbox.dana.id/d/portal/oauth';

        $fullUrl = $baseUrl . "?" . http_build_query($queryParams);

        Log::info('LOG LOG: [BINDING] Redirecting User to: ' . $fullUrl);

        return redirect($fullUrl);
    }

    // --- PENYESUAIAN CALLBACK AGAR BISA MEMBACA SESSION DARI startBinding ANDA ---
    public function handleCallback(Request $request)
    {
        Log::info('[DANA CALLBACK] Masuk...', $request->all());

        $authCode = $request->input('auth_code') ?? $request->input('authCode');
        $stateRaw = $request->input('state');

        // AMBIL USER ID DARI SESSION SESUAI KODE START BINDING ANDA
        $userId = session('dana_user_id');

        if (!$authCode || !$userId) {
            Log::error('[DANA CALLBACK] Gagal: AuthCode atau Session User ID kosong.');
            return redirect('https://apps.tokosancaka.com')->with('error', 'Callback DANA Invalid atau Sesi Kadaluarsa.');
        }

        $cacheKey = 'dana_auth_process_' . $authCode;
        $isUsed = DB::table('affiliates')->where('dana_auth_code', $authCode)->exists() || DB::table('users')->where('dana_auth_code', $authCode)->exists();

        if ($isUsed || \Illuminate\Support\Facades\Cache::has($cacheKey)) {
            Log::warning("[DANA CALLBACK] IDEMPOTENCY: AuthCode $authCode sudah diproses.");
            return redirect("/member/dashboard")->with('success', 'Akun sudah terhubung (Request sebelumnya).');
        }
        \Illuminate\Support\Facades\Cache::put($cacheKey, true, 60);

        // Cari tahu apakah user ini di tabel users atau affiliates
        $isUserTable = DB::table('users')->where('id', $userId)->exists();
        $tableName = $isUserTable ? 'users' : 'affiliates';

        try {
            $timestamp  = now('Asia/Jakarta')->toIso8601String();
            $clientId   = config('services.dana.client_id');
            $externalId = (string) time();

            $stringToSign = $clientId . "|" . $timestamp;
            $signature    = $this->generateSignature($stringToSign);

            $path = '/v1.0/access-token/b2b2c.htm';
            $body = [
                'grantType' => 'authorization_code',
                'authCode'  => $authCode,
                'additionalInfo' => (object)[]
            ];

            $response = Http::withHeaders([
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'X-PARTNER-ID'  => $clientId,
                'X-CLIENT-KEY'  => $clientId,
                'X-EXTERNAL-ID' => $externalId,
                'Content-Type'  => 'application/json'
            ])->post(config('services.dana.base_url') . $path, $body);

            $result = $response->json();
            $successCodes = ['2001100', '2007400', '2000000'];

            if (isset($result['responseCode']) && in_array($result['responseCode'], $successCodes)) {

                DB::table($tableName)->where('id', $userId)->update([
                    'dana_access_token' => $result['accessToken'] ?? $result['access_token'],
                    'dana_auth_code'    => $authCode,
                    'dana_connected_at' => now(),
                    'updated_at'        => now()
                ]);

                // Clear session
                session()->forget('dana_user_id');

                Log::info("[DANA CALLBACK] Berhasil untuk User ID: $userId");
                return redirect('/member/dashboard')->with('success', '✅ Akun DANA Berhasil Terhubung!');
            }

            Log::error('[DANA CALLBACK] DANA Reject:', $result);
            return redirect('/member/dashboard')->with('error', 'Gagal menghubungkan DANA: ' . ($result['responseMessage'] ?? 'Unknown Error'));

        } catch (\Exception $e) {
            Log::error('[DANA CALLBACK] System Error:', ['msg' => $e->getMessage()]);
            return redirect('/member/dashboard')->with('error', 'Terjadi Kesalahan Sistem saat verifikasi.');
        }
    }

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
        ])->withBody($jsonBody, 'application/json')->post(config('services.dana.base_url') . $path);

        $result = $response->json();

        if (isset($result['responseCode']) && $result['responseCode'] == '2001100') {
            $amount = $result['accountInfos'][0]['availableBalance']['value'];
            DB::table('affiliates')->where('id', $request->affiliate_id)->update(['dana_user_balance' => $amount, 'updated_at' => now()]);
            return back()->with('success', 'Saldo Real DANA Terupdate!');
        }
        return back()->with('error', 'Gagal: ' . ($result['responseMessage'] ?? 'Error'));
    }

    public function checkMerchantBalance(Request $request)
    {
        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $payload = ["request" => ["head" => ["version" => "2.0", "function" => "dana.merchant.queryMerchantResource", "clientId" => config('services.dana.x_partner_id'), "clientSecret" => config('services.dana.client_secret'), "reqTime" => $timestamp, "reqMsgId" => (string) Str::uuid(), "reserve" => "{}"], "body" => ["requestMerchantId" => config('services.dana.merchant_id'), "merchantResourceInfoList" => ["MERCHANT_DEPOSIT_BALANCE"]]]];

        $jsonToSign = json_encode($payload['request'], JSON_UNESCAPED_SLASHES);
        $signature = $this->generateSignature($jsonToSign);

        $response = Http::post(config('services.dana.base_url') . '/dana/merchant/queryMerchantResource.htm', ["request" => $payload['request'], "signature" => $signature]);
        $res = $response->json();

        if (isset($res['response']['body']['resultInfo']['resultStatus']) && $res['response']['body']['resultInfo']['resultStatus'] === 'S') {
            $val = json_decode($res['response']['body']['merchantResourceInformations'][0]['value'], true);
            DB::table('affiliates')->where('id', $request->affiliate_id)->update(['dana_merchant_balance' => $val['amount']]);
            return back()->with('success', 'Saldo Merchant Terupdate!');
        }
        return back()->with('error', 'Gagal Cek Merchant');
    }

    private function generateSignature($stringToSign) {
        $rawKey = config('services.dana.private_key');

        if (empty($rawKey)) {
            Log::error("DANA Error: Private Key kosong!");
            return null;
        }

        $cleanKey = str_replace(
            ['-----BEGIN PRIVATE KEY-----', '-----END PRIVATE KEY-----', '-----BEGIN RSA PRIVATE KEY-----', '-----END RSA PRIVATE KEY-----'],
            '',
            $rawKey
        );

        $cleanKey = preg_replace('/[^a-zA-Z0-9\/\+=]/', '', $cleanKey);
        $formattedKey = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($cleanKey, 64, "\n", true) . "\n-----END PRIVATE KEY-----";
        $privateKeyResource = openssl_pkey_get_private($formattedKey);

        if (!$privateKeyResource) return null;

        $binarySignature = "";
        openssl_sign($stringToSign, $binarySignature, $privateKeyResource, OPENSSL_ALGO_SHA256);

        return base64_encode($binarySignature);
    }

    public function topupSaldo(Request $request)
    {
        Log::info('[DANA TOPUP] --- MEMULAI PROSES TOPUP ---', ['affiliate_id' => $request->affiliate_id]);
        $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();

        if (!$aff || $aff->balance < $request->amount) {
            return back()->with('error', 'Gagal: Saldo profit tidak mencukupi.');
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $request->phone ?? $aff->whatsapp);
        if (substr($cleanPhone, 0, 1) === '0') $cleanPhone = '62' . substr($cleanPhone, 1);

        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $path = '/v1.0/emoney/customer-top-up.htm';
        $partnerRef = 'TP' . time() . Str::random(4);

        $body = [
            'partnerReferenceNo' => $partnerRef,
            'amount' => ['value' => number_format((float)$request->amount, 2, '.', ''), 'currency' => 'IDR'],
            'beneficiaryAccountNo' => $cleanPhone,
            'additionalInfo' => (object)[]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        $headers = [
            'X-TIMESTAMP'   => $timestamp,
            'X-SIGNATURE'   => $signature,
            'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
            'X-EXTERNAL-ID' => (string) time() . Str::random(4),
            'X-DEVICE-ID'   => 'DANA-DASHBOARD-STATION',
            'CHANNEL-ID'    => '95221',
            'Content-Type'  => 'application/json',
        ];

        try {
            $response = Http::withHeaders($headers)->withBody($jsonBody, 'application/json')->post(config('services.dana.base_url') . $path);
            $result = $response->json();

            if ($response->successful()) {
                DB::table('affiliates')->where('id', $aff->id)->decrement('balance', $request->amount);

                DB::table('dana_transactions')->insert([
                    'tenant_id'    => $aff->tenant_id ?? 1,
                    'affiliate_id' => $aff->id,
                    'type' => 'TOPUP',
                    'reference_no' => $partnerRef,
                    'phone' => $cleanPhone,
                    'amount' => $request->amount,
                    'status' => 'SUCCESS',
                    'response_payload' => json_encode($result),
                    'created_at' => now()
                ]);

                return back()->with('success', '💸 Topup Berhasil!');
            }
            return back()->with('error', 'Gagal dari DANA: ' . ($result['responseMessage'] ?? 'Respon Server Error'));
        } catch (\Exception $e) {
            return back()->with('error', 'Sistem Error: ' . $e->getMessage());
        }
    }
}
