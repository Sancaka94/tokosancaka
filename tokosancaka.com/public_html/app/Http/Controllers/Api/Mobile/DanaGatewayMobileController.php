<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;
use App\Services\DanaSignatureService;

class DanaGatewayMobileController extends Controller
{
    protected $danaSignature;

    public function __construct(DanaSignatureService $danaSignature)
    {
        $this->danaSignature = $danaSignature;
        $this->applyDynamicConfig();
    }

    // =========================================================================
    // DINAMISASI CONFIG DANA BERDASARKAN DATABASE
    // =========================================================================
    private function applyDynamicConfig()
    {
        $danaMode = Api::getValue('dana_production_mode', 'global', '0');
        $isProduction = ($danaMode == '1');

        if ($isProduction) {
            Log::info('LOG LOG: DANA Menggunakan Mode PRODUCTION');
            config([
                'services.dana.dana_env'      => 'PRODUCTION',
                'services.dana.base_url'      => 'https://api.saas.dana.id',
                'services.dana.merchant_id'   => Api::getValue('dana_prod_merchant_id', 'production', env('DANA_PROD_MERCHANT_ID')),
                'services.dana.client_id'     => Api::getValue('dana_prod_client_id', 'production', env('DANA_PROD_CLIENT_ID')),
                'services.dana.x_partner_id'  => Api::getValue('dana_prod_client_id', 'production', env('DANA_PROD_CLIENT_ID')),
                'services.dana.private_key'   => Api::getValue('dana_prod_private_key', 'production', env('DANA_PROD_PRIVATE_KEY')),
                'services.dana.public_key'    => Api::getValue('dana_prod_public_key', 'production'),
                'services.dana.client_secret' => Api::getValue('dana_prod_client_secret', 'production', env('DANA_PROD_CLIENT_SECRET')),
            ]);
        } else {
            Log::info('LOG LOG: DANA Menggunakan Mode SANDBOX');
            config([
                'services.dana.dana_env'      => 'SANDBOX',
                'services.dana.base_url'      => 'https://api.sandbox.dana.id',
                'services.dana.merchant_id'   => Api::getValue('dana_sandbox_merchant_id', 'sandbox', env('DANA_MERCHANT_ID')),
                'services.dana.client_id'     => Api::getValue('dana_sandbox_client_id', 'sandbox', env('DANA_X_PARTNER_ID')),
                'services.dana.x_partner_id'  => Api::getValue('dana_sandbox_client_id', 'sandbox', env('DANA_X_PARTNER_ID')),
                'services.dana.private_key'   => Api::getValue('dana_sandbox_private_key', 'sandbox', env('DANA_PRIVATE_KEY')),
                'services.dana.public_key'    => Api::getValue('dana_sandbox_public_key', 'sandbox'),
                'services.dana.client_secret' => Api::getValue('dana_sandbox_client_secret', 'sandbox', env('DANA_CLIENT_SECRET')),
            ]);
        }
    }

    private function generateSignature($stringToSign) {
        Log::debug('=== [DANA DEBUG LOG] START GENERATE SIGNATURE ===');
        Log::debug('[DANA DEBUG LOG] 1. String To Sign (Mentah):', ['string' => $stringToSign]);

        $rawKey = config('services.dana.private_key');

        if (empty($rawKey)) {
            Log::error('[DANA DEBUG LOG] ERROR: Private Key dari config KOSONG!');
            throw new Exception("Private Key kosong. Pastikan DANA_PRIVATE_KEY di .env atau Pengaturan Database sudah terisi.");
        }

        Log::debug('[DANA DEBUG LOG] 2. Raw Key Berhasil Diambil', ['panjang_karakter' => strlen($rawKey)]);

        $cleanKey = str_replace(
            ["-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----", "\r", "\n", " ", "\"", "'"],
            "",
            $rawKey
        );
        Log::debug('[DANA DEBUG LOG] 3. Clean Key Berhasil Dibuat', ['panjang_karakter' => strlen($cleanKey)]);

        $formattedKey = "-----BEGIN PRIVATE KEY-----\n" .
                        wordwrap($cleanKey, 64, "\n", true) .
                        "\n-----END PRIVATE KEY-----";

        $privateKeyResource = openssl_pkey_get_private($formattedKey);
        if (!$privateKeyResource) {
            Log::error('[DANA DEBUG LOG] ERROR: Gagal load OpenSSL Resource!', [
                'preview_key' => substr($formattedKey, 0, 50) . '...'
            ]);
            throw new Exception("Format Private Key salah atau korup. Tidak dapat diproses oleh OpenSSL.");
        }

        Log::debug('[DANA DEBUG LOG] 4. OpenSSL Resource Valid. Memulai proses SHA256...');

        $binarySignature = "";
        $isSignSuccess = openssl_sign($stringToSign, $binarySignature, $privateKeyResource, OPENSSL_ALGO_SHA256);

        if (!$isSignSuccess) {
            $sslError = openssl_error_string();
            Log::error('[DANA DEBUG LOG] ERROR: OpenSSL Sign Failed.', ['ssl_error' => $sslError]);
            throw new Exception("OpenSSL Sign Failed. Detail: " . $sslError);
        }

        $finalBase64Signature = base64_encode($binarySignature);

        Log::debug('[DANA DEBUG LOG] 5. Signature Berhasil Dibuat!', [
            'signature_result' => $finalBase64Signature
        ]);
        Log::debug('=== [DANA DEBUG LOG] END GENERATE SIGNATURE ===');

        return $finalBase64Signature;
    }

    // =========================================================================
    // PEMBAYARAN: DANA DIRECT / HOST TO HOST
    // =========================================================================
    public function createPaymentDANA(Transaction $transaction)
    {
        $trxId = $transaction->reference_id;
        Log::info('DANA START for Transaction Table: ' . $trxId); // LOG LOG dipertahankan

        $user = Auth::user();
        $returnUrl = url('/api/mobile/dana/return');
        $timestamp = Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $expiryTime = Carbon::now('Asia/Jakarta')->addMinutes(60)->format('Y-m-d\TH:i:sP');

        $merchantId = isset($merchantId) ? $merchantId : config('services.dana.merchant_id');
        $finalAmount = isset($testAmount) ? $testAmount : number_format($transaction->amount, 2, '.', '');

        $bodyArray = [
            "partnerReferenceNo" => $trxId,
            "merchantId" => $merchantId,
            "amount" => [
                "value" => $finalAmount,
                "currency" => "IDR"
            ],
            "validUpTo" => $expiryTime,
            "urlParams" => [
                [
                    "url" => $returnUrl,
                    "type" => "PAY_RETURN",
                    "isDeeplink" => "N"
                ],
                [
                    "url" => url('/api/mobile/dana/notify'),
                    "type" => "NOTIFICATION",
                    "isDeeplink" => "N"
                ]
            ],
            "payOptionDetails" => [
                [
                    "payMethod" => "BALANCE",
                    "payOption" => "",
                    "transAmount" => [
                        "value" => $finalAmount,
                        "currency" => "IDR"
                    ]
                ]
            ],
            "additionalInfo" => [
                "mcc" => "5732",
                "order" => [
                    "orderTitle" => "Top Up " . $trxId,
                    "merchantTransType" => "01",
                    "scenario" => "REDIRECT",
                    "buyer" => [
                        "externalUserId" => (string) $user->id_pengguna,
                        "externalUserType" => "MERCHANT_USER",
                        "nickname" => Str::limit($user->nama_lengkap ?? 'Guest', 40),
                    ]
                ],
                "envInfo" => [
                    "sourcePlatform" => "IPG",
                    "terminalType" => "SYSTEM",
                    "orderTerminalType" => "WEB",
                ]
            ]
        ];

        $jsonBody = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            $accessToken = $this->danaSignature->getAccessToken();
            $signature = $this->danaSignature->generateSignature('POST', '/payment-gateway/v1.0/debit/payment-host-to-host.htm', $jsonBody, $timestamp);

            $baseUrl = config('services.dana.base_url');

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'X-PARTNER-ID' => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => Str::random(32),
                'X-TIMESTAMP' => $timestamp,
                'X-SIGNATURE' => $signature,
                'Content-Type' => 'application/json',
                'CHANNEL-ID' => '95221',
                'ORIGIN' => config('services.dana.origin'),
            ])->withBody($jsonBody, 'application/json')
              ->post($baseUrl . '/payment-gateway/v1.0/debit/payment-host-to-host.htm');

            $result = $response->json();

            Log::info('DANA Create Payment Result:', $result);

            if (isset($result['responseCode']) && $result['responseCode'] == '2005400') {
                $redirectUrl = $result['webRedirectUrl'] ?? $result['appLinkUrl'] ?? null;
                if ($redirectUrl) {
                    $transaction->payment_url = $redirectUrl;
                    $transaction->save();
                    return response()->json(['success' => true, 'redirect_url' => $redirectUrl]);
                }
            }

            Log::error('DANA Gagal:', $result);
            $errorCode = $result['responseCode'] ?? 'N/A';
            return response()->json(['success' => false, 'message' => 'Gagal dari DANA: ' . ($result['responseMessage'] ?? 'Unknown') . ' (Code: ' . $errorCode . ')'], 400);

        } catch (Exception $e) {
            Log::error('DANA Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Koneksi DANA Error.'], 500);
        }
    }

    // =========================================================================
    // PEMBAYARAN: DANA DIRECT DEBIT
    // =========================================================================
    public function createTopUpPaymentDANA(Transaction $transaction)
    {
        $validId = "216620080014040009735";
        $merchantIdConf = $validId;
        $partnerIdConf  = "2025081520100641466855";

        $user = Auth::user();
        $cleanInvoice = preg_replace('/[^a-zA-Z0-9]/', '', $transaction->reference_id);
        $timestamp    = Carbon::now('Asia/Jakarta')->toIso8601String();
        $expiryTime   = Carbon::now('Asia/Jakarta')->addMinutes(30)->format('Y-m-d\TH:i:sP');
        $amountValue  = number_format((float)$transaction->amount, 2, '.', '');

        $bodyArray = [
            "partnerReferenceNo" => $cleanInvoice,
            "merchantId"         => $merchantIdConf,
            "amount"             => [
                "value"    => $amountValue,
                "currency" => "IDR"
            ],
            "validUpTo"          => $expiryTime,
            "urlParams" => [
                ["url" => url('/api/mobile/dana/return'), "type" => "PAY_RETURN", "isDeeplink" => "Y"],
                ["url" => url('/api/mobile/dana/notify'), "type" => "NOTIFICATION", "isDeeplink" => "Y"]
            ],
            "payOptionDetails"   => [
                [
                    "payMethod"   => "BALANCE",
                    "payOption"   => "BALANCE",
                    "transAmount" => ["value" => $amountValue, "currency" => "IDR"],
                    "feeAmount"   => ["value" => "0.00", "currency" => "IDR"]
                ]
            ],
            "additionalInfo"     => [
                "productCode" => "51051000100000000001",
                "mcc"         => "5732",
                "order"       => [
                    "orderTitle"        => substr("Top Up " . $cleanInvoice, 0, 40),
                    "merchantTransType" => "01",
                    "orderMemo"         => substr("Inv " . $cleanInvoice, 0, 40),
                    "createdTime"       => $timestamp,
                    "buyer"             => [
                        "externalUserId"   => (string) ($user->id_pengguna ?? 'GUEST'.rand(100,999)),
                        "externalUserType" => "MERCHANT_USER",
                        "nickname"         => substr(preg_replace('/[^a-zA-Z0-9 ]/', '', $user->nama_lengkap ?? 'Guest'), 0, 20),
                    ],
                    "goods" => [
                        [
                            "name"            => "Saldo Top Up",
                            "merchantGoodsId" => substr("TOPUP" . $cleanInvoice, 0, 40),
                            "description"     => "Top Up Saldo Akun",
                            "category"        => "DIGITAL_GOODS",
                            "price"           => ["value" => $amountValue, "currency" => "IDR"],
                            "unit"            => "pcs",
                            "quantity"        => "1"
                        ]
                    ]
                ],
                "envInfo" => [
                    "sourcePlatform"    => "IPG",
                    "terminalType"      => "SYSTEM",
                    "orderTerminalType" => "WEB",
                ]
            ]
        ];

        $jsonBody = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $relativePath = '/rest/redirection/v1.0/debit/payment-host-to-host';

        try {
            $accessToken = $this->danaSignature->getAccessToken();
            $signature   = $this->danaSignature->generateSignature('POST', $relativePath, $jsonBody, $timestamp);

            $headers = [
                'Authorization'  => 'Bearer ' . $accessToken,
                'X-PARTNER-ID'   => $partnerIdConf,
                'X-EXTERNAL-ID'  => Str::random(32),
                'X-TIMESTAMP'    => $timestamp,
                'X-SIGNATURE'    => $signature,
                'Content-Type'   => 'application/json',
                'CHANNEL-ID'     => '95221',
                'ORIGIN'         => config('services.dana.origin'),
            ];

            Log::info('DANA_REQ_START_TOPUP', [
                'Invoice' => $cleanInvoice,
                'URL'     => config('services.dana.base_url') . $relativePath,
                'Headers' => $headers,
                'Body'    => $bodyArray
            ]);

            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $relativePath);

            $result = $response->json();

            Log::info('DANA_RES_END_TOPUP', [
                'Invoice'     => $cleanInvoice,
                'Status_Code' => $response->status(),
                'Result'      => $result
            ]);

            if (isset($result['responseCode']) && $result['responseCode'] == '2005400') {
                $redirectUrl = $result['webRedirectUrl'] ?? null;
                if($redirectUrl) {
                    $transaction->payment_url = substr($redirectUrl, 0, 255);
                    $transaction->save();
                    return response()->json(['success' => true, 'redirect_url' => $redirectUrl]);
                }
            }

            Log::error('DANA_FAIL_TOPUP', ['Result' => $result]);
            return response()->json(['success' => false, 'message' => 'Gagal memproses DANA: ' . ($result['responseMessage'] ?? 'Unknown Error')], 400);

        } catch (Exception $e) {
            Log::error('DANA_EXCEPTION_TOPUP', ['Error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan koneksi ke DANA.'], 500);
        }
    }

    // =========================================================================
    // PEMBAYARAN: DANA AUTO DEBIT BINDING
    // =========================================================================
    public function createPaymentDanaBinding(Transaction $transaction, $userAccount)
    {
        $trxId = $transaction->reference_id;
        Log::info('LOG LOG: [DANA BINDING] Memulai Auto-Debit / Checkout untuk Top Up: ' . $trxId);

        $timestamp = Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $validUpTo = Carbon::now('Asia/Jakarta')->addMinutes(30)->format('Y-m-d\TH:i:sP');
        $path = '/rest/redirection/v1.0/debit/payment-host-to-host';
        $amountValue = number_format($transaction->amount, 2, '.', '');

        $body = [
            "partnerReferenceNo" => $trxId,
            "merchantId"         => config('services.dana.merchant_id'),
            "validUpTo"          => $validUpTo,
            "amount" => [
                "value"    => $amountValue,
                "currency" => "IDR"
            ],
            "urlParams" => [
                [
                    "url"        => url('/api/mobile/dana/return?trx_id=' . $trxId),
                    "type"       => "PAY_RETURN",
                    "isDeeplink" => "Y"
                ],
                [
                    "url"        => url('/api/mobile/dana/notify'),
                    "type"       => "NOTIFICATION",
                    "isDeeplink" => "N"
                ]
            ],
            "payOptionDetails" => [
                [
                    "payMethod"   => "BALANCE",
                    "payOption"   => "BALANCE",
                    "transAmount" => [
                        "value"    => $amountValue,
                        "currency" => "IDR"
                    ]
                ]
            ],
            "additionalInfo" => [
                "supportDeepLinkCheckoutUrl" => "true",
                "productCode"                => "51051000100000000001",
                "mcc"                        => "5732",
                "order" => [
                    "orderTitle"        => substr("Top Up " . $trxId, 0, 64),
                    "merchantTransType" => "01",
                    "scenario"          => "DIRECT_DEBIT",
                    "buyer" => [
                        "externalUserId"   => (string) $userAccount->id_pengguna,
                        "externalUserType" => "MERCHANT_USER",
                        "nickname"         => substr(preg_replace('/[^a-zA-Z0-9 ]/', '', $userAccount->nama_lengkap ?? 'Customer'), 0, 64)
                    ]
                ],
                "envInfo" => [
                    "sourcePlatform"    => "IPG",
                    "terminalType"      => "SYSTEM",
                    "orderTerminalType" => "WEB"
                ]
            ]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            $accessTokenB2B = $this->danaSignature->getAccessToken();
            $signature = $this->danaSignature->generateSignature('POST', $path, $jsonBody, $timestamp);
            $baseUrl   = config('services.dana.base_url');

            $headers = [
                'Content-Type'           => 'application/json',
                'Authorization'          => 'Bearer ' . $accessTokenB2B,
                'Authorization-Customer' => 'Bearer ' . $userAccount->dana_access_token,
                'X-TIMESTAMP'            => $timestamp,
                'X-SIGNATURE'            => $signature,
                'ORIGIN'                 => config('services.dana.origin'),
                'X-PARTNER-ID'           => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID'          => (string) time() . Str::random(6),
                'X-DEVICE-ID'            => 'SANCAKA-WEB-POS',
                'CHANNEL-ID'             => '95221'
            ];

            Log::info('LOG LOG: [DANA BINDING] Menyiapkan Request API.', [
                'URL' => $baseUrl . $path,
                'Headers' => [
                    'X-TIMESTAMP' => $timestamp,
                    'X-PARTNER-ID' => config('services.dana.x_partner_id')
                ]
            ]);
            Log::info('LOG LOG: [DANA BINDING] Payload Body: ', $body);

            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post($baseUrl . $path);

            $result = $response->json();

            Log::info('LOG LOG: [DANA BINDING] Respon API DANA: ', [
                'HTTP_Status' => $response->status(),
                'Result'      => $result
            ]);

            if (isset($result['responseCode']) && $result['responseCode'] === '2005400') {
                if (!empty($result['webRedirectUrl'])) {
                    Log::info('LOG LOG: [DANA BINDING] User perlu diarahkan ke Web DANA untuk konfirmasi PIN/Pembayaran.');
                    $transaction->update(['payment_url' => $result['webRedirectUrl']]);
                    return response()->json(['success' => true, 'redirect_url' => $result['webRedirectUrl']]);
                }

                Log::info('LOG LOG: [DANA BINDING] Auto-Debit berhasil seketika tanpa PIN.');
                $transaction->update(['status' => 'success']);
                $userAccount->increment('saldo', $transaction->amount);

                return response()->json(['success' => true, 'message' => 'Pembayaran Berhasil! Saldo DANA Anda telah terpotong secara otomatis.']);
            } else {
                $transaction->update(['status' => 'failed']);
                $errorCode  = $result['responseCode'] ?? 'UNKNOWN';
                $pesanGagal = $result['responseMessage'] ?? 'Terjadi kesalahan pada sistem pembayaran.';

                Log::error("LOG LOG: [DANA BINDING] Gagal memotong saldo. Code: $errorCode | Msg: $pesanGagal");
                return response()->json(['success' => false, 'message' => "Gagal dari DANA [$errorCode]: $pesanGagal"], 400);
            }

        } catch (Exception $e) {
            Log::error('LOG LOG: [DANA BINDING] Fatal Exception: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Koneksi ke sistem DANA gagal.'], 500);
        }
    }

    // =========================================================================
    // DANA BINDING & OAUTH
    // =========================================================================
    public function startBinding(Request $request)
    {
        Log::info('LOG LOG: [BINDING] Memulai proses redirect ke DANA (Debug)...');
        $user = Auth::user();

        session(['dana_user_id' => $user->id_pengguna]);

        $queryParams = [
            'partnerId'   => config('services.dana.x_partner_id'),
            'merchantId'  => config('services.dana.merchant_id'),
            'timestamp'   => now('Asia/Jakarta')->format('Y-m-d\TH:i:s+07:00'),
            'externalId'  => 'BIND-' . $user->id_pengguna . '-' . time(),
            'channelId'   => 'DANAID',
            'redirectUrl' => url('/api/mobile/dana/callback'),
            'state'       => Str::random(16),
            'scopes'      => 'QUERY_BALANCE,MINI_DANA,DEFAULT_BASIC_PROFILE',
            'allowRegistration' => 'true',
        ];

        $baseUrl = config('services.dana.dana_env') === 'PRODUCTION'
            ? 'https://m.dana.id/d/portal/oauth'
            : 'https://m.sandbox.dana.id/d/portal/oauth';

        return response()->json(['success' => true, 'redirect_url' => $baseUrl . "?" . http_build_query($queryParams)]);
    }

    public function handleCallback(Request $request)
    {
        Log::info('LOG LOG: [DANA CALLBACK] SNAP Apply Token Start...', $request->all());

        $authCode = $request->input('auth_code') ?? $request->input('authCode');

        $userId = null;
        if (Auth::check()) {
            $userId = Auth::user()->id_pengguna;
        } elseif (session()->has('dana_user_id')) {
            $userId = session('dana_user_id');
        }

        if (!$authCode || !$userId) {
            Log::error('LOG LOG: [DANA CALLBACK] Gagal Ekstraksi ID atau Auth Code Kosong. User ID: ' . ($userId ?? 'NULL'));
            return response()->json(['success' => false, 'message' => 'Sesi kadaluarsa. Pastikan Anda masih login.']);
        }

        try {
            $timestamp = now('Asia/Jakarta')->toIso8601String();
            $clientId = config('services.dana.client_id');

            $stringToSign = $clientId . "|" . $timestamp;
            $signature = $this->generateSignature($stringToSign);

            $body = [
                "grantType"    => "AUTHORIZATION_CODE",
                "authCode"     => $authCode,
                "refreshToken" => "",
                "additionalInfo" => (object)[]
            ];

            $path = '/v1.0/access-token/b2b2c.htm';
            $fullUrl = config('services.dana.base_url') . $path;

            $response = Http::withHeaders([
                'Content-Type'  => 'application/json',
                'X-TIMESTAMP'   => $timestamp,
                'X-CLIENT-KEY'  => $clientId,
                'X-SIGNATURE'   => $signature,
                'X-PARTNER-ID'  => $clientId,
            ])->post($fullUrl, $body);

            $result = $response->json();
            Log::info('LOG LOG: [DANA CALLBACK] Respon Apply Token:', $result);

            $successCodes = ['2000000', '2007400'];

            if (isset($result['responseCode']) && in_array($result['responseCode'], $successCodes)) {
                $accessToken = $result['accessToken'] ?? $result['access_token'] ?? null;

                if ($accessToken) {
                    DB::table('Pengguna')->where('id_pengguna', $userId)->update([
                        'dana_access_token' => $accessToken,
                        'dana_auth_code'    => $authCode,
                    ]);

                    session()->forget('dana_user_id');

                    Log::info("LOG LOG: [DANA CALLBACK] UPDATE DATABASE BERHASIL untuk User ID: $userId");
                    return response()->json(['success' => true, 'message' => '✅ Akun DANA Berhasil Terhubung!']);
                }
            }

            Log::error('LOG LOG: [DANA SNAP ERROR]', $result);
            return response()->json(['success' => false, 'message' => 'DANA Reject: ' . ($result['responseMessage'] ?? 'Unknown Error')]);

        } catch (Exception $e) {
            Log::error('LOG LOG: [DANA CALLBACK] System Error:', ['msg' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan sistem.'], 500);
        }
    }

    // =========================================================================
    // DANA SALDO & INQUIRY (USER & MERCHANT)
    // =========================================================================
    public function checkBalance(Request $request)
    {
        $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
        $accessToken = $request->access_token ?? $aff->dana_access_token;

        if (!$accessToken) return response()->json(['success' => false, 'message' => 'Token Kosong.']);

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
            return response()->json(['success' => true, 'balance' => $amount]);
        }
        return response()->json(['success' => false, 'message' => 'Gagal: ' . ($result['responseMessage'] ?? 'Error')]);
    }

    public function checkMyDanaBalance()
    {
        $user = Auth::user();
        $accessToken = $user->dana_access_token;

        if (empty($accessToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Akun DANA belum terhubung. Silakan hubungkan terlebih dahulu.'
            ]);
        }

        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $path = '/v1.0/balance-inquiry.htm';
        $body = [
            'partnerReferenceNo' => 'BAL' . time() . Str::random(5),
            'balanceTypes' => ['BALANCE'],
            'additionalInfo' => [
                'accessToken' => $accessToken
            ]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        try {
            Log::info('[DANA BALANCE CHECK] Meminta info saldo user ID: ' . $user->id_pengguna);

            $response = Http::withHeaders([
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time(),
                'X-DEVICE-ID'   => 'CUSTOMER-WEB-STATION',
                'CHANNEL-ID'    => '95221',
                'ORIGIN'        => config('services.dana.origin'),
                'Authorization-Customer' => 'Bearer ' . $accessToken,
                'Content-Type'  => 'application/json'
            ])->withBody($jsonBody, 'application/json')->post(config('services.dana.base_url') . $path);

            $result = $response->json();

            if (isset($result['responseCode']) && $result['responseCode'] == '2001100') {
                $amount = $result['accountInfos'][0]['availableBalance']['value'] ?? 0;
                return response()->json([
                    'success' => true,
                    'balance' => $amount,
                    'formatted_balance' => 'Rp ' . number_format($amount, 0, ',', '.'),
                    'message' => 'Berhasil mengambil saldo DANA.'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil saldo: ' . ($result['responseMessage'] ?? 'Token mungkin kadaluarsa.')
            ]);

        } catch (Exception $e) {
            Log::error('[DANA BALANCE CHECK] Error System: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Sistem Error saat mengecek saldo.'
            ], 500);
        }
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
            return response()->json(['success' => true, 'balance' => $val['amount']]);
        }
        return response()->json(['success' => false, 'message' => 'Gagal Cek Merchant']);
    }

    public function accountInquiry(Request $request)
    {
        Log::info('[DANA INQUIRY] Start Process', [
            'affiliate_id' => $request->affiliate_id,
            'amount' => $request->amount,
            'ip' => $request->ip()
        ]);

        $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
        if (!$aff) {
            Log::error('[DANA INQUIRY] Affiliate Not Found', ['id' => $request->affiliate_id]);
            return response()->json(['success' => false, 'message' => 'Affiliate tidak ditemukan.']);
        }

        $rawPhone = $request->phone ?? $aff->whatsapp;
        $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
        if (substr($cleanPhone, 0, 1) === '0') {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        }
        Log::info('[DANA INQUIRY] Phone Sanitized', ['original' => $rawPhone, 'clean' => $cleanPhone]);

        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $path = '/v1.0/emoney/account-inquiry.htm';
        $amountValue = $request->amount ?? 10000;

        $body = [
            "partnerReferenceNo" => "INQ" . time() . Str::random(5),
            "customerNumber"     => $cleanPhone,
            "amount" => [
                "value"    => number_format((float)$amountValue, 2, '.', ''),
                "currency" => "IDR"
            ],
            "transactionDate" => $timestamp,
            "additionalInfo"  => [
                "fundType"           => "AGENT_TOPUP_FOR_USER_SETTLE",
                "externalDivisionId" => "",
                "chargeTarget"       => "MERCHANT",
                "customerId"         => ""
            ]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        Log::info('[DANA INQUIRY] Security Detail', [
            'path' => $path,
            'stringToSign' => $stringToSign,
            'signature' => $signature
        ]);

        $headers = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $aff->dana_access_token,
            'X-TIMESTAMP'   => $timestamp,
            'X-SIGNATURE'   => $signature,
            'ORIGIN'        => config('services.dana.origin'),
            'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
            'X-EXTERNAL-ID' => (string) time() . Str::random(5),
            'X-IP-ADDRESS'  => $request->ip() ?? '82.25.62.13',
            'X-DEVICE-ID'   => 'DANA-DASHBOARD-01',
            'CHANNEL-ID'    => '95221'
        ];

        try {
            Log::info('[DANA INQUIRY] Sending Request to DANA', ['body' => $body]);

            $response = Http::withHeaders($headers)->withBody($jsonBody, 'application/json')->post(config('services.dana.base_url') . $path);
            $result = $response->json();

            Log::info('[DANA INQUIRY] Response Received', ['status' => $response->status(), 'result' => $result]);

            $resCode = $result['responseCode'] ?? '5003700';
            $resMsg = $result['responseMessage'] ?? 'Unexpected response';

            DB::table('dana_transactions')->insert([
                'affiliate_id' => $request->affiliate_id,
                'type' => 'INQUIRY',
                'reference_no' => $body['partnerReferenceNo'],
                'phone' => $cleanPhone,
                'amount' => $amountValue,
                'status' => in_array($resCode, ['2000000', '2003700']) ? 'SUCCESS' : 'FAILED',
                'response_payload' => json_encode($result),
                'created_at' => now()
            ]);

            $responseMapping = [
                '2003700' => '✅ SUCCESS: Account Inquiry processed.',
                '4003700' => '❌ FAILED: Bad Request (General).',
                '5003701' => '❌ FAILED: Internal Server Error.',
            ];

            $displayMsg = $responseMapping[$resCode] ?? "[$resCode] $resMsg";

            if (in_array($resCode, ['2000000', '2003700'])) {
                $customerName = $result['additionalInfo']['customerName'] ?? 'Akun Valid';

                DB::table('affiliates')->where('id', $request->affiliate_id)->update([
                    'dana_user_name' => $customerName,
                    'updated_at' => now()
                ]);

                return response()->json(['success' => true, 'message' => $displayMsg, 'customerName' => $customerName]);
            }

            return response()->json(['success' => false, 'message' => $displayMsg]);

        } catch (Exception $e) {
            Log::error('[DANA INQUIRY] Exception!', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Sistem Error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // TRANSFER, TOPUP KE USER LAIN & BANK INQUIRY
    // =========================================================================
    public function topupSaldo(Request $request)
    {
        Log::info('[DANA TOPUP] --- MEMULAI PROSES TOPUP ---', ['affiliate_id' => $request->affiliate_id]);

        $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();

        if (!$aff || $aff->balance < $request->amount) {
            Log::warning('[DANA TOPUP] Saldo Tidak Cukup atau Affiliate Tidak Ditemukan');
            return response()->json(['success' => false, 'message' => 'Gagal: Saldo profit tidak mencukupi.']);
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $request->phone ?? $aff->whatsapp);
        if (substr($cleanPhone, 0, 1) === '0') $cleanPhone = '62' . substr($cleanPhone, 1);

        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $path = '/v1.0/emoney/customer-top-up.htm';
        $partnerRef = 'TP' . time() . Str::random(4);

        $body = [
            'partnerReferenceNo' => $partnerRef,
            'amount' => [
                'value' => number_format((float)$request->amount, 2, '.', ''),
                'currency' => 'IDR'
            ],
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
            'Authorization-Customer' => 'Bearer ' . $aff->dana_access_token
        ];

        try {
            Log::info('[DANA TOPUP] Mengirim Request...', ['headers' => $headers]);

            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();

            Log::info('[DANA TOPUP] Respon Diterima', ['status' => $response->status(), 'result' => $result]);

            if ($response->successful()) {
                DB::table('affiliates')->where('id', $aff->id)->decrement('balance', $request->amount);

                DB::table('dana_transactions')->insert([
                    'affiliate_id' => $aff->id,
                    'type' => 'TOPUP',
                    'reference_no' => $partnerRef,
                    'phone' => $cleanPhone,
                    'amount' => $request->amount,
                    'status' => 'SUCCESS',
                    'response_payload' => json_encode($result),
                    'created_at' => now()
                ]);

                Log::info('[DANA TOPUP] BERHASIL & WA TERKIRIM');
                return response()->json(['success' => true, 'message' => '💸 Topup Berhasil, Saldo Dipotong, dan WA Terkirim!']);
            }

            return response()->json(['success' => false, 'message' => 'Gagal dari DANA: ' . ($result['responseMessage'] ?? 'Respon Server Error')]);

        } catch (Exception $e) {
            Log::error('[DANA TOPUP] Exception!', ['msg' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Sistem Error: ' . $e->getMessage()], 500);
        }
    }

    public function customerTopup(Request $request)
    {
        Log::info('[DANA TOPUP] --- MEMULAI PROSES TOPUP ---', [
            'affiliate_id' => $request->affiliate_id,
            'target_phone' => $request->phone,
            'amount' => $request->amount,
            'ip' => $request->ip()
        ]);

        $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
        if (!$aff) {
            Log::error('[DANA TOPUP] Affiliate Tidak Ditemukan', ['id' => $request->affiliate_id]);
            return response()->json(['success' => false, 'message' => 'Affiliate tidak terdaftar di sistem.']);
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $request->phone);

        if (substr($cleanPhone, 0, 2) === '62') {
        } elseif (substr($cleanPhone, 0, 1) === '0') {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        } elseif (substr($cleanPhone, 0, 1) === '8') {
            $cleanPhone = '62' . $cleanPhone;
        }

        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $partnerRef = (string) time() . Str::random(8);
        $valStr = number_format((float)$request->amount, 2, '.', '');

        $body = [
            "partnerReferenceNo" => $partnerRef,
            "customerNumber"     => $cleanPhone,
            "amount" => [
                "value"    => $valStr,
                "currency" => "IDR"
            ],
            "feeAmount" => [
                "value"    => "0.00",
                "currency" => "IDR"
            ],
            "transactionDate" => $timestamp,
            "sessionId"       => (string) Str::uuid(),
            "categoryId"      => "6",
            "notes"           => "Topup Sancaka",
            "additionalInfo"  => [
                    "fundType"           => "AGENT_TOPUP_FOR_USER_SETTLE",
                    "externalDivisionId" => "",
                    "chargeTarget"       => "MERCHANT",
                    "customerId"         => ""
                ]
        ];

        $path = '/v1.0/emoney/topup.htm';
        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        $headers = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $aff->dana_access_token,
            'X-TIMESTAMP'   => $timestamp,
            'X-SIGNATURE'   => $signature,
            'ORIGIN'        => config('services.dana.origin'),
            'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
            'X-EXTERNAL-ID' => (string) time() . Str::random(6),
            'X-IP-ADDRESS'  => $request->ip(),
            'X-DEVICE-ID'   => 'SANCAKA-DANA-01',
            'CHANNEL-ID'    => '95221'
        ];

        try {
            Log::info('[DANA TOPUP] Mengirim Request ke DANA API', ['body' => $body]);

            $response = Http::withHeaders($headers)->withBody($jsonBody, 'application/json')->post(config('services.dana.base_url') . $path);
            $result = $response->json();

            $resCode = $result['responseCode'] ?? '5003801';
            $isSuccessCode = in_array($resCode, ['2000000', '2003800']);

            DB::table('dana_transactions')->insert([
                'affiliate_id' => $aff->id,
                'type' => 'TOPUP',
                'reference_no' => $partnerRef,
                'phone' => $cleanPhone,
                'amount' => $request->amount,
                'status' => $isSuccessCode ? 'SUCCESS' : 'FAILED',
                'response_payload' => json_encode($result),
                'created_at' => now()
            ]);

            if ($isSuccessCode) {
                DB::table('affiliates')->where('id', $aff->id)->decrement('balance', $request->amount);
                Log::info('[DANA TOPUP] Berhasil', ['code' => $resCode]);
                return response()->json(['success' => true, 'message' => 'Topup Berhasil!']);
            } else {
                Log::error('[DANA TOPUP] Gagal/Error Response', ['result' => $result]);
                return response()->json(['success' => false, 'message' => 'Gagal: ' . ($result['responseMessage'] ?? 'Unknown Error')]);
            }

        } catch (Exception $e) {
            Log::error('[DANA TOPUP] EXCEPTION ERROR', ['msg' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Sistem Error: ' . $e->getMessage()], 500);
        }
    }

    public function bankAccountInquiry(Request $request)
    {
        $aff = DB::table('Pengguna')->where('id_pengguna', $request->affiliate_id)->first();
        if (!$aff) return response()->json(['success' => false, 'message' => 'Pengguna tidak ditemukan.']);

        $customerNumber = preg_replace('/[^0-9]/', '', $aff->no_wa);
        if (substr($customerNumber, 0, 1) === '0') $customerNumber = '62' . substr($customerNumber, 1);

        $timestamp = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $path = '/v1.0/emoney/bank-account-inquiry.htm';
        $refNo = "BNK" . time() . Str::random(4);

        $cekBank = DB::table('dana_bank_codes')->where('bank_code', $request->bank_code)->first();
        $readableBank = $cekBank ? $cekBank->bank_name : $request->bank_code;

        $body = [
            "partnerReferenceNo" => $refNo,
            "customerNumber"     => $customerNumber,
            "beneficiaryAccountNumber" => $request->account_no,
            "amount" => [
                "value"    => number_format((float)$request->amount, 2, '.', ''),
                "currency" => "IDR"
            ],
            "additionalInfo" => [
                "fundType"               => "MERCHANT_WITHDRAW_FOR_CORPORATE",
                "beneficiaryBankCode"    => (string) $request->bank_code,
                "beneficiaryAccountName" => "",
            ]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        try {
            $accessTokenB2B = $this->danaSignature->getAccessToken();
            Log::info('[BANK INQUIRY] Sending Request to DANA', ['body' => $body]);

            $headers = [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $accessTokenB2B,
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'ORIGIN'        => config('services.dana.origin'),
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time() . Str::random(6),
                'X-IP-ADDRESS'  => $request->ip() ?? '82.25.62.13',
                'X-DEVICE-ID'   => 'SANCAKA-DANA-01',
                'CHANNEL-ID'    => '95221'
            ];

            if (!empty($aff->dana_access_token)) {
                $headers['Authorization-Customer'] = 'Bearer ' . $aff->dana_access_token;
            }

            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            Log::info('[BANK INQUIRY]', ['res' => $result]);

            $resCode = $result['responseCode'] ?? '500';

            DB::table('dana_transactions')->insert([
                'affiliate_id' => $aff->id_pengguna,
                'type' => 'BANK_INQUIRY',
                'reference_no' => $refNo,
                'phone' => $request->account_no . " (" . $readableBank . ")",
                'amount' => $request->amount,
                'status' => ($resCode == '2004200') ? 'SUCCESS' : 'FAILED',
                'response_payload' => json_encode($result),
                'created_at' => now()
            ]);

            if ($resCode == '2004200') {
                $bankName = $result['beneficiaryBankShortName'] ?? $result['beneficiaryBankName'] ?? $readableBank;
                $accName  = $result['beneficiaryAccountName'];

                return response()->json([
                    'success' => true,
                    'account_name' => $accName,
                    'bank_name' => $bankName,
                    'message' => "Rekening Valid: $accName ($bankName)"
                ]);
            }

            $errMsg = $result['responseMessage'] ?? 'Unknown Error';
            return response()->json(['success' => false, 'message' => $errMsg]);

        } catch (Exception $e) {
            Log::error('[BANK INQUIRY ERROR]', ['msg' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Sistem Error saat cek rekening.'], 500);
        }
    }

    public function transferToBank(Request $request)
    {
        Log::info('[DANA TRANSFER BANK] Start', [
            'affiliate_id' => $request->affiliate_id,
            'bank_code'    => $request->bank_code,
            'account_no'   => $request->account_no,
            'amount'       => $request->amount
        ]);

        $aff = DB::table('Pengguna')->where('id_pengguna', $request->affiliate_id)->first();
        if (!$aff) return response()->json(['success' => false, 'message' => 'Pengguna tidak ditemukan.']);

        if ($aff->saldo < $request->amount) {
            return response()->json(['success' => false, 'message' => 'Saldo Anda tidak mencukupi.']);
        }

        $customerNumber = preg_replace('/[^0-9]/', '', $aff->no_wa);
        if (substr($customerNumber, 0, 1) === '0') $customerNumber = '62' . substr($customerNumber, 1);

        DB::table('Pengguna')->where('id_pengguna', $aff->id_pengguna)->decrement('saldo', $request->amount);

        $timestamp  = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $path       = '/v1.0/emoney/transfer-bank.htm';
        $partnerRef = "TRF" . time() . Str::random(6);

        $cekBank = DB::table('dana_bank_codes')->where('bank_code', $request->bank_code)->first();
        $readableBank = $cekBank ? $cekBank->bank_name : $request->bank_code;

        $body = [
            "partnerReferenceNo"       => $partnerRef,
            "customerNumber"           => $customerNumber,
            "beneficiaryAccountNumber" => (string) $request->account_no,
            "beneficiaryBankCode"      => (string) $request->bank_code,
            "amount" => [
                "value"    => number_format((float)$request->amount, 2, '.', ''),
                "currency" => "IDR"
            ],
            "additionalInfo" => [
                "fundType"               => "MERCHANT_WITHDRAW_FOR_CORPORATE",
                "beneficiaryAccountName" => (string) $request->account_name,
                "notes"                  => "Transfer ke Bank " . $readableBank]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        try {
            $accessTokenB2B = $this->danaSignature->getAccessToken();

            $headers = [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $accessTokenB2B,
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'ORIGIN'        => config('services.dana.origin'),
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time() . Str::random(6),
                'X-IP-ADDRESS'  => $request->ip(),
                'X-DEVICE-ID'   => 'SANCAKA-DANA-01',
                'CHANNEL-ID'    => '95221'
            ];

            Log::info('[DANA TRANSFER BANK] Mengirim Request...', ['body' => $body]);

            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            $resCode = $result['responseCode'] ?? '500';

            if ($resCode == '2004300') {
                DB::table('dana_transactions')->insert([
                    'affiliate_id'     => $aff->id_pengguna,
                    'type'             => 'TRANSFER_BANK',
                    'reference_no'     => $partnerRef,
                    'phone'            => $request->account_no . " (" . $readableBank . ")",
                    'amount'           => $request->amount,
                    'status'           => 'SUCCESS',
                    'response_payload' => json_encode($result),
                    'created_at'       => now()
                ]);

                return response()->json(['success' => true, 'message' => "Transfer Berhasil!\nRef: $partnerRef"]);

            } elseif (in_array($resCode, ['2024300', '4294300', '5004301'])) {
                DB::table('dana_transactions')->insert([
                    'affiliate_id'     => $aff->id_pengguna,
                    'type'             => 'TRANSFER_BANK',
                    'reference_no'     => $partnerRef,
                    'phone'            => $request->account_no . " (" . $readableBank . ")",
                    'amount'           => $request->amount,
                    'status'           => 'PENDING',
                    'response_payload' => json_encode($result),
                    'created_at'       => now()
                ]);

                return response()->json(['success' => true, 'message' => "⏳ Transaksi Sedang Diproses (Pending)."]);

            } else {
                DB::table('Pengguna')->where('id_pengguna', $aff->id_pengguna)->increment('saldo', $request->amount);

                DB::table('dana_transactions')->insert([
                    'affiliate_id'     => $aff->id_pengguna,
                    'type'             => 'TRANSFER_BANK',
                    'reference_no'     => $partnerRef,
                    'phone'            => $request->account_no . " (" . $readableBank . ")",
                    'amount'           => $request->amount,
                    'status'           => 'FAILED',
                    'response_payload' => json_encode($result),
                    'created_at'       => now()
                ]);

                $errorMsg = $result['responseMessage'] ?? 'Transaksi Gagal';
                Log::error('[DANA TRANSFER BANK] Gagal & Refund', ['res' => $result]);
                return response()->json(['success' => false, 'message' => "Gagal: $errorMsg (Saldo telah dikembalikan)."]);
            }

        } catch (Exception $e) {
            DB::table('Pengguna')->where('id_pengguna', $aff->id_pengguna)->increment('saldo', $request->amount);
            Log::error('[DANA TRANSFER BANK] Exception', ['msg' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Sistem Error saat eksekusi (Saldo telah dikembalikan).'], 500);
        }
    }

    // =========================================================================
    // GAPURA CONSULT PAYMENT METHODS
    // =========================================================================
    public function consultPaymentMethods(Request $request)
    {
        Log::debug('================ [GAPURA DEBUG LOG] CONSULT START ================');
        Log::debug('[GAPURA DEBUG LOG] 1. Request Masuk dari User', [
            'user_id' => Auth::id(),
            'ip'      => $request->ip(),
            'amount'  => $request->amount
        ]);

        try {
            $request->validate([
                'amount' => 'required|numeric|min:100',
            ]);

            $merchantId = config('services.dana.merchant_id');
            $clientId   = config('services.dana.x_partner_id');

            $timestamp  = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
            $externalId = (string) time() . Str::random(8);
            $path       = '/v1.0/payment-gateway/consult-pay.htm';

            $user = Auth::user();
            $buyerName = $user ? ($user->nama_lengkap ?? 'Guest') : 'Guest';
            $buyerId   = $user ? (string) $user->id_pengguna : 'GUEST-' . time();

            $clientIp = $request->ip();
            if (filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $clientIp = '82.25.62.13';
            }

            $body = [
                "merchantId" => $merchantId,
                "amount" => [
                    "value"    => number_format((float)$request->amount, 2, '.', ''),
                    "currency" => "IDR"
                ],
                "additionalInfo" => [
                    "buyer" => [
                        "nickname"       => $buyerName,
                        "externalUserId" => $buyerId,
                    ],
                    "envInfo" => [
                        "sourcePlatform"     => "IPG",
                        "terminalType"       => "SYSTEM",
                        "orderTerminalType"  => "WEB",
                        "clientIp"           => $clientIp,
                        "websiteLanguage"    => "id_ID",
                        "sessionId"          => Session::getId(),
                        "tokenId"            => Str::uuid()->toString(),
                        "osType"             => "Web Browser",
                        "appVersion"         => "1.0",
                        "merchantAppVersion" => "1.0"
                    ]
                ]
            ];

            Log::debug('[GAPURA DEBUG LOG] 2. Body Payload Array:', $body);

            $jsonBody     = json_encode($body, JSON_UNESCAPED_SLASHES);
            Log::debug('[GAPURA DEBUG LOG] 2a. Body Payload JSON (Mentah untuk di-Hash):', ['json' => $jsonBody]);

            $hashedBody   = strtolower(hash('sha256', $jsonBody));
            Log::debug('[GAPURA DEBUG LOG] 2b. Hashed Body (SHA-256):', ['hash' => $hashedBody]);

            $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
            Log::debug('[GAPURA DEBUG LOG] 3. String to Sign GAPURA:', ['str' => $stringToSign]);

            $signature = $this->generateSignature($stringToSign);

            $headers = [
                'Content-Type'  => 'application/json',
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'X-PARTNER-ID'  => $clientId,
                'X-EXTERNAL-ID' => $externalId,
                'CHANNEL-ID'    => '95221',
                'ORIGIN'        => config('app.url'),
            ];

            Log::debug('[GAPURA DEBUG LOG] 4. Headers Request Disiapkan:', $headers);
            Log::debug('[GAPURA DEBUG LOG] 4a. Target URL:', ['url' => config('services.dana.base_url') . $path]);

            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            $httpStatus = $response->status();

            Log::debug('[GAPURA DEBUG LOG] 5. Response Diterima!', [
                'http_status' => $httpStatus,
                'raw_body' => $response->body(),
                'parsed_json' => $result
            ]);

            $resCode = $result['responseCode'] ?? 'UNKNOWN';
            $successCodes = ['2000000', '2005700', '2005400'];

            if (in_array($resCode, $successCodes)) {
                $paymentMethods = $result['paymentInfos'] ?? [];
                Log::debug('[GAPURA DEBUG LOG] 6. SUCCESS. Total Methods found: ' . count($paymentMethods));

                $availableMethods = collect($paymentMethods)->map(function($item) {
                    return [
                        'method' => $item['payMethod'],
                        'option' => $item['payOption'] ?? $item['payMethod'],
                        'promo'  => isset($item['promoInfos']) ? 'Ada Promo' : 'Normal'
                    ];
                });

                return response()->json([
                    'success' => true,
                    'data'    => $availableMethods,
                    'message' => 'Daftar metode pembayaran berhasil diambil.'
                ]);
            }

            Log::error('[GAPURA DEBUG LOG] 7. FAILED RESPONSE CODE: ' . $resCode, [
                'message' => $result['responseMessage'] ?? 'No Message',
                'full_result' => $result
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gateway Error: ' . ($result['responseMessage'] ?? 'Unknown'),
                'code'    => $resCode
            ], 400);

        } catch (Exception $e) {
            Log::critical('[GAPURA DEBUG LOG] 8. CRITICAL EXCEPTION!', [
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // WEBHOOKS & NOTIFICATIONS
    // =========================================================================
    public function handleWebhook(Request $request)
    {
        Log::info('========== DANA WEBHOOK INCOMING ==========', $request->all());

        $head = $request->input('request.head');
        $body = $request->input('request.body');

        if ($head['function'] === 'dana.acquiring.order.finishNotify') {
            $merchantTransId = $body['merchantTransId'];
            $status = $body['acquirementStatus'];

            $trx = DB::table('dana_transactions')->where('reference_no', $merchantTransId)->first();

            if ($trx) {
                DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => $status]);

                if (in_array($status, ['CLOSED', 'FAILED']) && $trx->status === 'SUCCESS') {
                    DB::table('affiliates')->where('id', $trx->affiliate_id)->increment('balance', $trx->amount);

                    DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => 'REFUNDED']);
                    Log::info('[WEBHOOK] Saldo Profit Berhasil Direfund!', ['affiliate' => $trx->affiliate_id]);
                }
            }
        }

        return response()->json(['response' => ['head' => ['resultCode' => 'SUCCESS']]]);
    }

    public function handleNotify(Request $request)
    {
        Log::info('========== DANA WEBHOOK (Transactions Table) ==========');

        $trxIdFromDana = $request->input('partnerReferenceNo') ?? $request->input('originalPartnerReferenceNo');
        $statusDana    = $request->input('latestTransactionStatus');

        $transaction = Transaction::where('reference_id', $trxIdFromDana)->lockForUpdate()->first();

        if (!$transaction) {
            Log::info("Webhook DANA: ID $trxIdFromDana tidak ditemukan di database. Merespon sukses untuk kebutuhan testing Sandbox.");
            return response()->json([
                'responseCode' => '2005600',
                'responseMessage' => 'Successful'
            ])->withHeaders(['X-TIMESTAMP' => Carbon::now()->toIso8601String()]);
        }

        DB::beginTransaction();
        try {
            if ($transaction->status == 'pending') {
                if ($statusDana == '00') {
                    Log::info("Webhook: $trxIdFromDana SUKSES.");

                    $transaction->status = 'success';
                    $transaction->save();

                    $user = User::where('id_pengguna', $transaction->user_id)->first();
                    if ($user) {
                        $user->increment('saldo', $transaction->amount);
                    }

                } elseif ($statusDana == '05') {
                    $transaction->status = 'failed';
                    $transaction->save();
                }
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Webhook Error: " . $e->getMessage());
            return response()->json(['responseCode' => '5005601', 'responseMessage' => 'Internal Server Error'], 500);
        }

        return response()->json(['responseCode' => '2005600', 'responseMessage' => 'Successful'])
                ->withHeaders(['X-TIMESTAMP' => Carbon::now()->toIso8601String()]);
    }

    // =========================================================================
    // STATUS CHECKING & DEBUGGING
    // =========================================================================
    public function checkTopupStatus(Request $request)
    {
        Log::info('[DANA INQUIRY STATUS] Memulai pengecekan status...', [
            'partnerReferenceNo' => $request->reference_no,
            'affiliate_id' => $request->affiliate_id
        ]);

        $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
        $trx = DB::table('dana_transactions')->where('reference_no', $request->reference_no)->first();

        if (!$trx) return response()->json(['success' => false, 'message' => 'Data transaksi tidak ditemukan di database.']);

        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $path = '/v1.0/emoney/topup-status.htm';

        $body = [
            "originalPartnerReferenceNo" => $trx->reference_no,
            "originalReferenceNo"        => "",
            "originalExternalId"         => "",
            "serviceCode"                => "38",
            "additionalInfo"             => (object)[]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        $headers = [
            'Content-Type'   => 'application/json',
            'Authorization'  => 'Bearer ' . $aff->dana_access_token,
            'X-TIMESTAMP'    => $timestamp,
            'X-SIGNATURE'    => $signature,
            'X-PARTNER-ID'   => config('services.dana.x_partner_id'),
            'X-EXTERNAL-ID'  => (string) time() . Str::random(6),
            'CHANNEL-ID'     => '95221'
        ];

        try {
            Log::info('[DANA INQUIRY STATUS] Mengirim Request Status ke DANA', ['body' => $body]);

            $response = Http::withHeaders($headers)->withBody($jsonBody, 'application/json')->post(config('services.dana.base_url') . $path);
            $result = $response->json();
            $resCode = $result['responseCode'] ?? '';

            Log::info('[DANA INQUIRY STATUS] Respon Diterima', ['result' => $result]);

            if (isset($result['responseCode']) && $result['responseCode'] == '2003900') {
                $status = $result['latestTransactionStatus'];

                if ($status == '00') {
                    DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => 'SUCCESS']);
                    return response()->json(['success' => true, 'message' => 'Transaksi BERHASIL (Confirmed by DANA)']);
                } elseif (in_array($status, ['01', '02', '03'])) {
                    return response()->json(['success' => true, 'message' => 'Transaksi masih PENDING di sistem DANA.']);
                } else {
                    DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => 'FAILED']);
                    return response()->json(['success' => false, 'message' => 'Transaksi GAGAL: ' . ($result['transactionStatusDesc'] ?? 'Failed')]);
                }
            } elseif ($resCode == '4043901') {
                DB::table('dana_transactions')->where('id', $trx->id)->update([
                    'status' => 'FAILED',
                    'retry_count' => 5
                ]);
                return response()->json(['success' => false, 'message' => 'Transaksi Tidak Ditemukan di DANA (Silakan coba Topup ulang).']);
            }

            return response()->json(['success' => false, 'message' => 'Gagal cek status: ' . ($result['responseMessage'] ?? 'Unknown Error')]);

        } catch (Exception $e) {
            Log::error('[DANA INQUIRY STATUS] System Error', ['msg' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Sistem Error: ' . $e->getMessage()], 500);
        }
    }

    public function checkDanaGatewayStatus($orderId)
    {
        Log::info("Checking Status for Order: $orderId");

        $body = [
            "partnerReferenceNo" => $orderId,
            "merchantId" => config('services.dana.merchant_id')
        ];

        $method = 'POST';
        $relativePath = '/rest/v1.1/debit/status';
        $timestamp = Carbon::now()->toIso8601String();

        try {
            $signature = $this->danaSignature->generateSignature($method, $relativePath, $body, $timestamp);

            $response = Http::withHeaders([
                'X-PARTNER-ID' => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => Str::random(32),
                'X-TIMESTAMP'  => $timestamp,
                'X-SIGNATURE'  => $signature,
                'Content-Type' => 'application/json',
                'CHANNEL-ID'   => 'MOBILE_WEB',
            ])->post(config('services.dana.base_url') . $relativePath, $body);

            Log::info('Status Check Result:', $response->json());
            return response()->json($response->json());

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function debugDanaStatus($orderId)
    {
        $this->applyDynamicConfig();

        $body = [
            "partnerReferenceNo" => $orderId,
            "merchantId" => config('services.dana.merchant_id')
        ];

        $method = 'POST';
        $relativePath = '/rest/v1.1/debit/status';
        $timestamp = Carbon::now('Asia/Jakarta')->toIso8601String();

        try {
            $signature = $this->danaSignature->generateSignature($method, $relativePath, $body, $timestamp);

            $response = Http::withHeaders([
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time(),
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'Content-Type'  => 'application/json',
                'CHANNEL-ID'    => 'MOBILE_WEB',
            ])->post(config('services.dana.base_url') . $relativePath, $body);

            return response()->json([
                'INFO_SISTEM' => [
                    'MODE_AKTIF' => config('services.dana.dana_env'),
                    'TARGET_URL' => config('services.dana.base_url') . $relativePath,
                    'ORDER_ID'   => $orderId,
                ],
                'RESPONS_DARI_DANA' => $response->json()
            ], 200, [], JSON_PRETTY_PRINT);

        } catch (Exception $e) {
            return response()->json([
                'ERROR_SISTEM' => $e->getMessage()
            ], 500, [], JSON_PRETTY_PRINT);
        }
    }

    public function returnPage(Request $request)
    {
        Log::info('DANA Return Page Hit. Menampilkan halaman sukses (Blade) ke user.');

        // Memanggil file resources/views/pembayaran_suksesdana.blade.php
        return view('pembayaran_suksesdana');
    }
}
