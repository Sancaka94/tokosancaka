<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Logging aktif
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\SettingApi;

class DanaGatewayController extends Controller
{
    /**
     * =========================================================================
     * 1. CONSTRUCTOR & DYNAMIC CONFIGURATION
     * =========================================================================
     */
    public function __construct()
    {
        $this->applyDynamicConfig();
    }

    private function applyDynamicConfig()
    {
        $settings = SettingApi::pluck('value', 'key')->toArray();
        $isProduction = ($settings['dana_production_mode'] ?? '0') === '1';

        if ($isProduction) {
            config([
                'services.dana.dana_env'      => 'PRODUCTION',
                'services.dana.base_url'      => 'https://api.dana.id',
                'services.dana.portal_url'    => 'https://m.dana.id/d/portal/oauth',
                // Pastikan URL Callback ini sama persis dengan di DANA Dashboard!
                'services.dana.redirect_url'  => 'https://apps.tokosancaka.com/dana/callback',
                'services.dana.merchant_id'   => $settings['dana_prod_merchant_id'] ?? env('DANA_PROD_MERCHANT_ID'),
                'services.dana.client_id'     => $settings['dana_prod_client_id'] ?? env('DANA_PROD_CLIENT_ID'),
                'services.dana.x_partner_id'  => $settings['dana_prod_client_id'] ?? env('DANA_PROD_CLIENT_ID'),
                'services.dana.private_key'   => $settings['dana_prod_private_key'] ?? env('DANA_PROD_PRIVATE_KEY'),
                'services.dana.public_key'    => $settings['dana_prod_public_key'] ?? env('DANA_PROD_PUBLIC_KEY'),
                'services.dana.client_secret' => $settings['dana_prod_client_secret'] ?? env('DANA_PROD_CLIENT_SECRET'),
                'services.dana.origin'        => env('DANA_ORIGIN', 'https://tokosancaka.com'),
            ]);
        } else {
            config([
                'services.dana.dana_env'      => 'SANDBOX',
                'services.dana.base_url'      => 'https://api.sandbox.dana.id',
                'services.dana.portal_url'    => 'https://m.sandbox.dana.id/d/portal/oauth',
                // Pastikan URL Callback ini sama persis dengan di DANA Dashboard!
                'services.dana.redirect_url'  => 'https://apps.tokosancaka.com/dana/callback',
                'services.dana.merchant_id'   => $settings['dana_sandbox_merchant_id'] ?? env('DANA_MERCHANT_ID'),
                'services.dana.client_id'     => $settings['dana_sandbox_client_id'] ?? env('DANA_X_PARTNER_ID'),
                'services.dana.x_partner_id'  => $settings['dana_sandbox_client_id'] ?? env('DANA_X_PARTNER_ID'),
                'services.dana.private_key'   => $settings['dana_sandbox_private_key'] ?? env('DANA_PRIVATE_KEY'),
                'services.dana.public_key'    => $settings['dana_sandbox_public_key'] ?? env('DANA_PUBLIC_KEY'),
                'services.dana.client_secret' => $settings['dana_sandbox_client_secret'] ?? env('DANA_CLIENT_SECRET'),
                'services.dana.origin'        => env('DANA_ORIGIN', 'https://tokosancaka.com'),
            ]);
        }
    }

    /**
     * =========================================================================
     * 2. ANTI-CRASH SECURITY SIGNATURE
     * =========================================================================
     */
    private function generateSignature($stringToSign)
    {
        $privateKeyContent = config('services.dana.private_key');

        if (empty($privateKeyContent)) {
            Log::error("LOG LOG: [DANA SIG] Private Key kosong! Cek konfigurasi Setting API Anda di database.");
            return null;
        }

        $cleanKey = str_replace(
            ['-----BEGIN PRIVATE KEY-----', '-----END PRIVATE KEY-----', '-----BEGIN RSA PRIVATE KEY-----', '-----END RSA PRIVATE KEY-----'],
            '',
            $privateKeyContent
        );

        $cleanKey = preg_replace('/[^a-zA-Z0-9\/\+=]/', '', $cleanKey);
        $formattedKey = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($cleanKey, 64, "\n", true) . "\n-----END PRIVATE KEY-----";

        $privateKeyResource = openssl_pkey_get_private($formattedKey);

        if (!$privateKeyResource) {
            Log::error("LOG LOG: [DANA SIG] Private Key tetap tidak valid setelah di-format.");
            return null;
        }

        $binarySignature = "";
        if (!openssl_sign($stringToSign, $binarySignature, $privateKeyResource, OPENSSL_ALGO_SHA256)) {
            Log::error("LOG LOG: [DANA SIG] OpenSSL Error saat proses signing: " . openssl_error_string());
            return null;
        }

        return base64_encode($binarySignature);
    }

    /**
     * =========================================================================
     * 3. BINDING & OAUTH (SAMBUNG AKUN)
     * =========================================================================
     */
    public function startBinding(Request $request)
    {
        Log::info('LOG LOG: [BINDING] Memulai proses redirect ke DANA Portal...');

        // Ambil ID User/Affiliate, support baik dikirim lewat form maupun Auth
        $user = Auth::user();
        $affiliateId = $request->affiliate_id ?? ($user->id_pengguna ?? ($user->id ?? 1));

        // Simpan id ke session sebagai cadangan pengenal user jika return putus Auth
        session(['dana_user_id' => $affiliateId]);

        // STRICT OAUTH 2.0 PARAMETERS (ANTI ERROR DANA PORTAL)
        $queryParams = [
            'clientId'    => config('services.dana.client_id'), // WAJIB clientId
            'redirectUrl' => config('services.dana.redirect_url'),
            'scopes'      => 'AGREEMENT_PAY,QUERY_BALANCE,DEFAULT_BASIC_PROFILE', // AGREEMENT_PAY Wajib!
            'state'       => 'MEMBER-' . $affiliateId . '-apps-1', // Disesuaikan agar lolos parse
            'terminalType'=> 'WEB',
            'merchantId'  => config('services.dana.merchant_id'),
        ];

        $fullUrl = config('services.dana.portal_url') . "?" . http_build_query($queryParams);

        Log::info('LOG LOG: [BINDING] Redirecting User to: ' . $fullUrl);

        return redirect($fullUrl);
    }

    public function handleCallback(Request $request)
    {
        Log::info('LOG LOG: [DANA CALLBACK] Masuk...', $request->all());

        $authCode = $request->input('auth_code') ?? $request->input('authCode');
        $stateRaw = $request->input('state');

        // Coba ambil ID dari Auth, jika tidak ada ambil dari Session cadangan
        $userId = Auth::check() ? (Auth::user()->id_pengguna ?? Auth::id()) : session('dana_user_id');

        if (!$authCode || !$stateRaw) {
            Log::error('LOG LOG: [DANA CALLBACK] Gagal: AuthCode atau State kosong.');
            return redirect('https://apps.tokosancaka.com')->with('error', 'Callback DANA Invalid (Data Kosong).');
        }

        // --- IDEMPOTENCY CHECK ---
        $cacheKey = 'dana_auth_process_' . $authCode;
        $isUsed = DB::table('affiliates')->where('dana_auth_code', $authCode)->exists() || DB::table('users')->where('dana_auth_code', $authCode)->exists();

        if ($isUsed || Cache::has($cacheKey)) {
            Log::warning("LOG LOG: [DANA CALLBACK] IDEMPOTENCY TRIGGERED: AuthCode $authCode sudah diproses.");
            return redirect('/member/dashboard')->with('success', 'Akun sudah terhubung (Request sebelumnya).');
        }
        Cache::put($cacheKey, true, 60);

        $parts = explode('-', $stateRaw);
        $userType  = $parts[0] ?? 'UNKNOWN';
        $stateUserId = $parts[1] ?? $userId; // Prioritaskan dari state jika ada

        // Fallback pencarian tabel
        $isUserTable = DB::table('users')->where('id', $stateUserId)->exists();
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

                DB::table($tableName)->where('id', $stateUserId)->update([
                    'dana_access_token' => $result['accessToken'] ?? $result['access_token'],
                    'dana_auth_code'    => $authCode,
                    'dana_connected_at' => now(),
                    'updated_at'        => now()
                ]);

                session()->forget('dana_user_id');

                Log::info("LOG LOG: [DANA CALLBACK] UPDATE DATABASE BERHASIL untuk User ID: $stateUserId");
                return redirect('/member/dashboard')->with('success', '✅ Akun DANA Berhasil Terhubung!');
            }

            Log::error('LOG LOG: [DANA SNAP ERROR] Token Exchange Failed: ', $result);
            return redirect('/member/dashboard')->with('error', 'Gagal menghubungkan DANA: ' . ($result['responseMessage'] ?? 'Unknown Error'));

        } catch (\Exception $e) {
            Log::error('LOG LOG: [DANA CALLBACK] System Error:', ['msg' => $e->getMessage()]);
            return redirect('/member/dashboard')->with('error', 'Terjadi Kesalahan Sistem saat verifikasi.');
        }
    }

    /**
     * =========================================================================
     * 4. SALDO & INQUIRY
     * =========================================================================
     */
    public function syncBalance(Request $request)
    {
        $user = Auth::user();
        // Coba cari accessToken di Auth User atau di tabel affiliates jika id dilempar
        $accessToken = $user->dana_access_token ?? null;

        if(!$accessToken && $request->has('affiliate_id')){
            $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
            $accessToken = $aff->dana_access_token ?? null;
        }

        if (!$accessToken) return back()->with('error', 'Token DANA tidak ditemukan. Harap hubungkan ulang DANA.');

        try {
            $timestamp = now('Asia/Jakarta')->toIso8601String();
            $path      = '/v1.0/balance-inquiry.htm';

            $body = [
                'partnerReferenceNo' => 'BAL-' . time(),
                'balanceTypes'       => ['BALANCE'],
                'additionalInfo'     => ['accessToken' => $accessToken]
            ];

            $jsonBody     = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $hashedBody   = strtolower(hash('sha256', $jsonBody));
            $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
            $signature    = $this->generateSignature($stringToSign);

            $response = Http::withHeaders([
                'X-TIMESTAMP'            => $timestamp,
                'X-SIGNATURE'            => $signature,
                'X-PARTNER-ID'           => config('services.dana.client_id'),
                'X-EXTERNAL-ID'          => (string) time(),
                'X-DEVICE-ID'            => 'DANA-DASHBOARD-STATION',
                'CHANNEL-ID'             => '95221',
                'ORIGIN'                 => config('services.dana.origin'),
                'Authorization-Customer' => 'Bearer ' . $accessToken,
                'Content-Type'           => 'application/json'
            ])->withBody($jsonBody, 'application/json')->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            Log::info("LOG LOG: [DANA SYNC SNAP] Cek Saldo User", $result);

            if (isset($result['responseCode']) && $result['responseCode'] == '2001100') {
                $amountString = $result['accountInfos'][0]['availableBalance']['value'];
                $cleanAmount  = floatval($amountString);

                $targetId = $request->affiliate_id ?? ($user->id_pengguna ?? $user->id);
                DB::table('affiliates')->where('id', $targetId)->update(['dana_user_balance' => $cleanAmount, 'updated_at' => now()]);

                return back()->with('success', 'Saldo Real DANA Terupdate: Rp ' . number_format($cleanAmount, 0, ',', '.'));
            }

            return back()->with('error', 'Gagal Sinkronisasi: ' . ($result['responseMessage'] ?? 'Unknown Error'));

        } catch (\Exception $e) {
            Log::error("LOG LOG: [DANA SYNC ERROR] " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem saat menghubungi DANA.');
        }
    }

    public function checkMerchantBalance(Request $request)
    {
        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $payload = [
            "request" => [
                "head" => [
                    "version" => "2.0", "function" => "dana.merchant.queryMerchantResource",
                    "clientId" => config('services.dana.client_id'),
                    "clientSecret" => config('services.dana.client_secret'),
                    "reqTime" => $timestamp, "reqMsgId" => (string) Str::uuid(), "reserve" => "{}"
                ],
                "body" => [
                    "requestMerchantId" => config('services.dana.merchant_id'),
                    "merchantResourceInfoList" => ["MERCHANT_DEPOSIT_BALANCE"]
                ]
            ]
        ];

        $jsonToSign = json_encode($payload['request'], JSON_UNESCAPED_SLASHES);
        $signature = $this->generateSignature($jsonToSign);

        $response = Http::post(config('services.dana.base_url') . '/dana/merchant/queryMerchantResource.htm', [
            "request" => $payload['request'],
            "signature" => $signature
        ]);

        $res = $response->json();

        if (isset($res['response']['body']['resultInfo']['resultStatus']) && $res['response']['body']['resultInfo']['resultStatus'] === 'S') {
            $val = json_decode($res['response']['body']['merchantResourceInformations'][0]['value'], true);
            Log::info("LOG LOG: Merchant Balance = " . $val['amount']);

            if($request->has('affiliate_id')) {
                DB::table('affiliates')->where('id', $request->affiliate_id)->update(['dana_merchant_balance' => $val['amount']]);
            }
            return back()->with('success', 'Saldo Merchant DANA Terupdate: Rp ' . number_format($val['amount'], 0, ',', '.'));
        }
        return back()->with('error', 'Gagal Cek Saldo Merchant');
    }

    /**
     * =========================================================================
     * 5. TOP UP CORPORATE / DISBURSEMENT (MERCHANT TO CUSTOMER)
     * =========================================================================
     */
    public function customerTopup(Request $request)
    {
        Log::info('LOG LOG: [DANA TOPUP] --- MEMULAI PROSES TOPUP B2B ---', $request->all());

        $request->validate([
            'affiliate_id' => 'required',
            'phone'        => 'required|numeric',
            'amount'       => 'required|numeric|min:1000',
        ]);

        $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
        if (!$aff) return back()->with('error', 'Affiliate tidak ditemukan.');

        if ($aff->balance < $request->amount) {
            return back()->with('error', 'Saldo internal tidak mencukupi.');
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $request->phone);
        if (substr($cleanPhone, 0, 1) === '0') { $cleanPhone = '62' . substr($cleanPhone, 1); }

        // Mencegah double cut
        DB::beginTransaction();
        try {
            DB::table('affiliates')->where('id', $aff->id)->decrement('balance', $request->amount);

            $timestamp  = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
            $partnerRef = 'TUP' . time() . mt_rand(1000, 9999);
            $amountStr  = number_format((float)$request->amount, 2, '.', '');
            $path       = '/rest/v1.0/emoney/topup';

            $body = [
                "partnerReferenceNo" => $partnerRef,
                "customerNumber"     => $cleanPhone,
                "amount" => ["value" => $amountStr, "currency" => "IDR"],
                "feeAmount" => ["value" => "0.00", "currency" => "IDR"],
                "transactionDate" => $timestamp,
                "categoryId"      => "6",
                "additionalInfo"  => [
                    "fundType"     => "AGENT_TOPUP_FOR_USER_SETTLE",
                    "chargeTarget" => "MERCHANT",
                    "merchantId"   => config('services.dana.merchant_id')
                ]
            ];

            $jsonBody     = json_encode($body, JSON_UNESCAPED_SLASHES);
            $hashedBody   = strtolower(hash('sha256', $jsonBody));
            $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
            $signature    = $this->generateSignature($stringToSign);

            $headers = [
                'Content-Type'  => 'application/json',
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'X-PARTNER-ID'  => config('services.dana.client_id'),
                'X-EXTERNAL-ID' => (string) time() . Str::random(4),
                'CHANNEL-ID'    => '95221',
                'ORIGIN'        => config('services.dana.origin'),
            ];

            $response = Http::withHeaders($headers)
                ->timeout(60)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            $resCode = trim((string)($result['responseCode'] ?? ($response->status() == 504 ? '504' : '500')));

            Log::info('LOG LOG: [DANA TOPUP] Response:', $result);

            if ($resCode === '2003800') {
                DB::table('dana_transactions')->insert([
                    'tenant_id'    => $aff->tenant_id ?? 1,
                    'affiliate_id' => $aff->id,
                    'type' => 'TOPUP_B2B',
                    'reference_no' => $partnerRef,
                    'phone' => $cleanPhone,
                    'amount' => $request->amount,
                    'status' => 'SUCCESS',
                    'response_payload' => json_encode($result),
                    'created_at' => now()
                ]);
                DB::commit();
                return back()->with('success', '✅ Pencairan Saldo Berhasil Diproses!');
            }

            if (in_array($resCode, ['504', '4293800', '5003801', '2023800'])) {
                DB::table('dana_transactions')->insert([
                    'tenant_id'    => $aff->tenant_id ?? 1,
                    'affiliate_id' => $aff->id,
                    'type' => 'TOPUP_B2B',
                    'reference_no' => $partnerRef,
                    'phone' => $cleanPhone,
                    'amount' => $request->amount,
                    'status' => 'PENDING',
                    'response_payload' => json_encode($result),
                    'created_at' => now()
                ]);
                DB::commit();
                return back()->with('warning', '⏳ Transaksi Sedang Diproses (Pending) oleh DANA.');
            }

            // Gagal -> Rollback saldo internal
            DB::rollBack();
            DB::table('dana_transactions')->insert([
                'tenant_id'    => $aff->tenant_id ?? 1,
                'affiliate_id' => $aff->id,
                'type' => 'TOPUP_B2B',
                'reference_no' => $partnerRef,
                'phone' => $cleanPhone,
                'amount' => $request->amount,
                'status' => 'FAILED',
                'response_payload' => json_encode($result),
                'created_at' => now()
            ]);

            return back()->with('error', "Gagal: " . ($result['responseMessage'] ?? "Code $resCode"));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('LOG LOG: [DANA TOPUP] Exception: ' . $e->getMessage());
            return back()->with('error', 'Sistem Error: ' . $e->getMessage());
        }
    }

    /**
     * =========================================================================
     * 6. BANK ACCOUNT INQUIRY & TRANSFER BANK
     * =========================================================================
     */
    public function bankAccountInquiry(Request $request)
    {
        Log::info('LOG LOG: [BANK INQUIRY B2B] Start', $request->all());

        $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
        if (!$aff) return back()->with('error', 'Affiliate tidak ditemukan.');

        $timestamp = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $path = '/v1.0/emoney/bank-account-inquiry.htm';
        $refNo = "BNK" . time() . Str::random(4);

        $body = [
            "partnerReferenceNo" => $refNo,
            "customerNumber"     => config('services.dana.merchant_id'),
            "beneficiaryAccountNumber" => $request->account_no,
            "amount" => [
                "value"    => number_format((float)$request->amount, 2, '.', ''),
                "currency" => "IDR"
            ],
            "additionalInfo" => [
                "fundType"               => "MERCHANT_WITHDRAW_FOR_CORPORATE",
                "beneficiaryBankCode"    => (string) $request->bank_code,
                "merchantId"             => config('services.dana.merchant_id')
            ]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;

        $signature = $this->generateSignature($stringToSign);

        try {
            $headers = [
                'Content-Type'  => 'application/json',
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'ORIGIN'        => config('services.dana.origin'),
                'X-PARTNER-ID'  => config('services.dana.client_id'),
                'X-EXTERNAL-ID' => (string) time() . Str::random(6),
                'CHANNEL-ID'    => '95221'
            ];

            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            $resCode = $result['responseCode'] ?? '500';

            if ($resCode == '2004200') {
                $accName = $result['beneficiaryAccountName'];
                return back()->with('success', "Rekening Valid: $accName")->with('valid_account_name', $accName);
            }

            return back()->with('error', "Gagal Cek Rekening ($resCode): " . ($result['responseMessage'] ?? ''));

        } catch (\Exception $e) {
            Log::error('LOG LOG: [BANK INQUIRY ERROR] ' . $e->getMessage());
            return back()->with('error', 'Sistem Error saat cek rekening.');
        }
    }

    public function transferToBank(Request $request)
    {
        Log::info('LOG LOG: [DANA TRANSFER BANK] Start', $request->all());

        $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
        if (!$aff) return back()->with('error', 'Data tidak ditemukan.');

        if ($aff->balance < $request->amount) {
            return back()->with('error', 'Saldo internal Anda tidak mencukupi.');
        }

        DB::beginTransaction();

        try {
            DB::table('affiliates')->where('id', $aff->id)->decrement('balance', $request->amount);

            $timestamp  = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
            $path       = '/v1.0/emoney/transfer-bank.htm';
            $partnerRef = "TRF" . time() . Str::random(6);

            $body = [
                "partnerReferenceNo"       => $partnerRef,
                "customerNumber"           => config('services.dana.merchant_id'),
                "beneficiaryAccountNumber" => (string) $request->account_no,
                "beneficiaryBankCode"      => (string) $request->bank_code,
                "amount" => [
                    "value"    => number_format((float)$request->amount, 2, '.', ''),
                    "currency" => "IDR"
                ],
                "additionalInfo" => [
                    "fundType"               => "MERCHANT_WITHDRAW_FOR_CORPORATE",
                    "beneficiaryAccountName" => (string) $request->account_name,
                    "merchantId"             => config('services.dana.merchant_id'),
                    "notes"                  => "Transfer Saldo",
                    "needNotify"             => true
                ]
            ];

            $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
            $hashedBody = strtolower(hash('sha256', $jsonBody));
            $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;

            $signature = $this->generateSignature($stringToSign);

            $headers = [
                'Content-Type'  => 'application/json',
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'ORIGIN'        => config('services.dana.origin'),
                'X-PARTNER-ID'  => config('services.dana.client_id'),
                'X-EXTERNAL-ID' => (string) time() . Str::random(6),
                'CHANNEL-ID'    => '95221'
            ];

            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            $resCode = $result['responseCode'] ?? '500';

            if ($resCode == '2004300') {
                DB::table('dana_transactions')->insert([
                    'affiliate_id'     => $aff->id,
                    'tenant_id'        => $aff->tenant_id ?? 1,
                    'type'             => 'TRANSFER_BANK',
                    'reference_no'     => $partnerRef,
                    'phone'            => $request->account_no,
                    'amount'           => $request->amount,
                    'status'           => 'SUCCESS',
                    'response_payload' => json_encode($result),
                    'created_at'       => now()
                ]);

                DB::commit();
                return back()->with('success', 'Transfer Bank Berhasil diproses!');

            } elseif (in_array($resCode, ['2024300', '4294300', '5004301'])) {
                DB::table('dana_transactions')->insert([
                    'affiliate_id'     => $aff->id,
                    'tenant_id'        => $aff->tenant_id ?? 1,
                    'type'             => 'TRANSFER_BANK',
                    'reference_no'     => $partnerRef,
                    'phone'            => $request->account_no,
                    'amount'           => $request->amount,
                    'status'           => 'PENDING',
                    'response_payload' => json_encode($result),
                    'created_at'       => now()
                ]);

                DB::commit();
                return back()->with('warning', "⏳ Transaksi Sedang Diproses (Pending).");

            } else {
                DB::rollBack();
                DB::table('dana_transactions')->insert([
                    'affiliate_id'     => $aff->id,
                    'tenant_id'        => $aff->tenant_id ?? 1,
                    'type'             => 'TRANSFER_BANK',
                    'reference_no'     => $partnerRef,
                    'phone'            => $request->account_no,
                    'amount'           => $request->amount,
                    'status'           => 'FAILED',
                    'response_payload' => json_encode($result),
                    'created_at'       => now()
                ]);

                Log::error('LOG LOG: [DANA TRANSFER BANK] Gagal', ['res' => $result]);
                return back()->with('error', "Gagal: " . ($result['responseMessage'] ?? 'Error') . "\n(Saldo telah dikembalikan).");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('LOG LOG: [DANA TRANSFER BANK] Exception: ' . $e->getMessage());
            return back()->with('error', 'Sistem Error saat transfer bank.');
        }
    }

    /**
     * =========================================================================
     * 7. WEBHOOK HANDLER & ORDER CANCEL/REFUND
     * =========================================================================
     */
    public function handleWebhook(Request $request)
    {
        Log::info('LOG LOG: ========== DANA WEBHOOK INCOMING ==========', $request->all());

        $head = $request->input('request.head');
        $body = $request->input('request.body');

        if (($head['function'] ?? '') === 'dana.acquiring.order.finishNotify') {
            $merchantTransId = $body['merchantTransId'];
            $status = $body['acquirementStatus']; // SUCCESS, CLOSED, FAILED

            $trx = DB::table('dana_transactions')->where('reference_no', $merchantTransId)->first();

            if ($trx) {
                // Cegah Idempotency
                if (in_array($trx->status, ['SUCCESS', 'REFUNDED'])) {
                    return response()->json(['response' => ['head' => ['resultCode' => 'SUCCESS']]]);
                }

                DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => $status]);

                if (in_array($status, ['CLOSED', 'FAILED']) && $trx->status === 'PENDING') {
                    DB::table('affiliates')->where('id', $trx->affiliate_id)->increment('balance', $trx->amount);
                    DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => 'REFUNDED']);
                    Log::info('LOG LOG: [WEBHOOK] Transaksi digagalkan. Saldo Profit Berhasil Direfund!', ['ref' => $merchantTransId]);
                }
            }
        }

        return response()->json(['response' => ['head' => ['resultCode' => 'SUCCESS']]]);
    }

    public function cancelDanaPayment($orderId)
    {
        Log::info('LOG LOG: [DANA CANCEL] Membatalkan Order ID: ' . $orderId);

        $timestamp  = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $path       = '/payment-gateway/v1.0/debit/cancel.htm';

        $body = [
            "originalPartnerReferenceNo" => (string) $orderId,
            "merchantId"                 => config('services.dana.merchant_id')
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;

        $signature = $this->generateSignature($stringToSign);

        try {
            $response = Http::withHeaders([
                'Content-Type'  => 'application/json',
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'ORIGIN'        => config('services.dana.origin'),
                'X-PARTNER-ID'  => config('services.dana.client_id'),
                'X-EXTERNAL-ID' => (string) time(),
                'CHANNEL-ID'    => '95221'
            ])->withBody($jsonBody, 'application/json')->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            Log::info('LOG LOG: [DANA CANCEL] Result: ', $result);

            if (($result['responseCode'] ?? '') === '2005700') {
                DB::table('dana_transactions')->where('reference_no', $orderId)->update(['status' => 'FAILED']);
                return back()->with('success', 'Pesanan berhasil dibatalkan di DANA.');
            }

            return back()->with('error', 'Gagal membatalkan pesanan: ' . ($result['responseMessage'] ?? 'Unknown Error'));

        } catch (\Exception $e) {
            Log::error('LOG LOG: [DANA CANCEL] Exception: ' . $e->getMessage());
            return back()->with('error', 'Sistem Error saat membatalkan pesanan.');
        }
    }
}
