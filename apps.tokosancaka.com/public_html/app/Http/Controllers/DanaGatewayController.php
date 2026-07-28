<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Carbon\Carbon;

// Models
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\OrderAttachment;
use App\Models\Coupon;
use App\Models\Affiliate;
use App\Models\Store;
use App\Models\TopUp;
use App\Models\User;
use App\Models\Api; // <-- PENTING: Untuk mengambil config dinamis dari DB

// Services
use App\Services\DokuJokulService;
use App\Services\KiriminAjaService;
use App\Services\DanaSignatureService;

// SDK Models (DANA)
use Dana\Widget\v1\Model\WidgetPaymentRequest;
use Dana\Widget\v1\Model\Money;
use Dana\Widget\v1\Model\UrlParam;
use Dana\Widget\v1\Model\WidgetPaymentRequestAdditionalInfo;
use Dana\Widget\v1\Model\EnvInfo;
use Dana\Widget\v1\Model\Order as DanaOrder;

use Dana\Widget\v1\Enum\PayMethod;
use Dana\Widget\v1\Enum\SourcePlatform;
use Dana\Widget\v1\Enum\TerminalType;
use Dana\Widget\v1\Enum\OrderTerminalType;
use Dana\Widget\v1\Enum\Type;

use Dana\Configuration;
use Dana\Env;
use Dana\Widget\v1\Api\WidgetApi;

class DanaGatewayController extends Controller
{
    /**
     * KONFIGURASI URL CALLBACK CENTRAL
     * URL ini harus SAMA PERSIS dengan yang didaftarkan di Dashboard DANA Developer.
     */
    private const CENTRAL_CALLBACK_URL = 'https://apps.tokosancaka.com/dana/callback';

    /**
     * Konstruktor: Terapkan Config Dinamis setiap kali Controller dipanggil
     */
    public function __construct()
    {
        $this->applyDynamicConfig();
    }

    /**
     * =========================================================================
     * HELPER: SETTING DINAMIS DARI DATABASE
     * =========================================================================
     */
    private function applyDynamicConfig()
    {
        $danaMode = Api::getValue('dana_production_mode', 'global', '0');
        $isProduction = ($danaMode == '1');

        if ($isProduction) {
            config([
                'services.dana.dana_env'      => 'PRODUCTION',
                'services.dana.base_url'      => 'https://api.saas.dana.id',
                'services.dana.merchant_id'   => Api::getValue('dana_prod_merchant_id', 'production', env('DANA_PROD_MERCHANT_ID')),
                'services.dana.client_id'     => Api::getValue('dana_prod_client_id', 'production', env('DANA_PROD_CLIENT_ID')),
                'services.dana.x_partner_id'  => Api::getValue('dana_prod_client_id', 'production', env('DANA_PROD_CLIENT_ID')),
                'services.dana.private_key'   => Api::getValue('dana_prod_private_key', 'production', env('DANA_PROD_PRIVATE_KEY')),
                'services.dana.public_key'    => Api::getValue('dana_prod_public_key', 'production'),
                'services.dana.client_secret' => Api::getValue('dana_prod_client_secret', 'production', env('DANA_PROD_CLIENT_SECRET')),
                'services.dana.origin'        => url('/')
            ]);
        } else {
            config([
                'services.dana.dana_env'      => 'SANDBOX',
                'services.dana.base_url'      => 'https://api.sandbox.dana.id',
                'services.dana.merchant_id'   => Api::getValue('dana_sandbox_merchant_id', 'sandbox', env('DANA_MERCHANT_ID')),
                'services.dana.client_id'     => Api::getValue('dana_sandbox_client_id', 'sandbox', env('DANA_X_PARTNER_ID')),
                'services.dana.x_partner_id'  => Api::getValue('dana_sandbox_client_id', 'sandbox', env('DANA_X_PARTNER_ID')),
                'services.dana.private_key'   => Api::getValue('dana_sandbox_private_key', 'sandbox', env('DANA_PRIVATE_KEY')),
                'services.dana.public_key'    => Api::getValue('dana_sandbox_public_key', 'sandbox'),
                'services.dana.client_secret' => Api::getValue('dana_sandbox_client_secret', 'sandbox', env('DANA_CLIENT_SECRET')),
                'services.dana.origin'        => url('/')
            ]);
        }
    }

    /**
     * =========================================================================
     * AWAL BINDING DANA (OAUTH2 BI-SNAP)
     * =========================================================================
     */
    public function startBinding(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->back()->with('error', 'Silakan login terlebih dahulu untuk menghubungkan DANA.');
        }

        $subdomain = explode('.', $request->getHost())[0];
        $tenantId  = $user->tenant_id ?? 1;

        $state = "BIND_TENANT-{$user->id}-{$subdomain}-{$tenantId}";
        $redirectUrl = self::CENTRAL_CALLBACK_URL;

        $queryParams = [
            'clientId'    => config('services.dana.client_id'), // Standar baru: clientId
            'redirectUrl' => $redirectUrl,
            'scopes'      => 'AGREEMENT_PAY,QUERY_BALANCE,DEFAULT_BASIC_PROFILE', // Wajib AGREEMENT_PAY
            'state'       => $state,
            'terminalType'=> 'WEB'
        ];

        $baseUrl = config('services.dana.dana_env') === 'PRODUCTION'
            ? 'https://m.dana.id/d/portal/oauth'
            : 'https://m.sandbox.dana.id/d/portal/oauth';

        $danaUrl = $baseUrl . '?' . http_build_query($queryParams);

        Log::info('[DANA BINDING START] Mengarahkan user ke: ' . $danaUrl);

        return redirect()->away($danaUrl);
    }

    /**
     * =========================================================================
     * MAIN HANDLER: MENERIMA SEMUA TAMU DARI DANA
     * =========================================================================
     */
    public function handleCallback(Request $request)
    {
        $authCode = $request->input('auth_code'); // Untuk Binding
        $status   = $request->input('resultStatus'); // Untuk Payment Redirect
        $state    = $request->input('state'); // KTP/IDENTITAS USER

        Log::info("[DANA GATEWAY] Hit Masuk.", [
            'ip' => $request->ip(),
            'state' => $state,
            'code' => $authCode ? 'YES' : 'NO'
        ]);

        if (empty($state)) {
            return redirect('/')->with('error', 'Invalid Request: No State Identifier');
        }

        $parts = explode('-', $state);

        if (count($parts) < 4) {
            Log::error("[DANA GATEWAY] Format State Salah: $state");
            return redirect('/')->with('error', 'Sesi Kadaluarsa atau Format Salah');
        }

        $action    = $parts[0]; // BIND_TENANT, BIND_MEMBER, PAY
        $userId    = $parts[1]; // User ID atau Affiliate ID
        $subdomain = $parts[2]; // Subdomain asal
        $tenantId  = $parts[3]; // ID Tenant

        $scheme = $request->secure() ? 'https://' : 'http://';
        $appDomain = env('APP_URL_DOMAIN', 'tokosancaka.com');
        $tenantBaseUrl = $scheme . $subdomain . '.' . $appDomain;

        switch ($action) {
            case 'BIND_TENANT':
                return $this->handleBinding($authCode, $userId, 'TENANT', $tenantBaseUrl);

            case 'BIND_MEMBER':
                return $this->handleBinding($authCode, $userId, 'MEMBER', $tenantBaseUrl);

            case 'PAY':
                return $this->handlePaymentRedirect($status, $tenantBaseUrl);

            default:
                Log::warning("[DANA GATEWAY] Unknown Action: $action");
                return redirect($tenantBaseUrl)->with('error', 'Aksi tidak dikenali.');
        }
    }

    /**
     * LOGIC: HANDLE BINDING (SAMBUNG AKUN)
     */
    private function handleBinding($authCode, $userId, $userType, $baseUrl)
    {
        if (!$authCode) {
            return redirect($baseUrl . '/dashboard?dana_status=cancelled')->with('error', 'Koneksi DANA dibatalkan.');
        }

        $tokenResult = $this->exchangeDanaToken($authCode);

        if (!$tokenResult['success']) {
            Log::error("[DANA GATEWAY] Gagal Tukar Token User $userId: " . $tokenResult['message']);
            return redirect($baseUrl . '/dashboard?dana_status=failed')->with('error', 'Gagal menghubungkan DANA: ' . $tokenResult['message']);
        }

        $accessToken = $tokenResult['data']['accessToken'];
        $expiry      = $tokenResult['data']['expiresIn'] ?? null;

        try {
            if ($userType === 'TENANT') {
                DB::table('users')->where('id', $userId)->update([
                    'dana_access_token' => $accessToken,
                    'dana_token_expiry' => $expiry,
                    'updated_at'        => now()
                ]);
                Log::info("[DANA GATEWAY] ✅ Token Saved for TENANT User $userId");
                $redirectPath = '/dashboard';
            } else {
                DB::table('affiliates')->where('id', $userId)->update([
                    'dana_access_token' => $accessToken,
                    'updated_at'        => now()
                ]);
                Log::info("[DANA GATEWAY] ✅ Token Saved for MEMBER User $userId");
                $redirectPath = '/member/dashboard';
            }

            return redirect($baseUrl . $redirectPath . '?dana_status=success&msg=' . urlencode('Akun DANA Berhasil Terhubung!'));

        } catch (\Exception $e) {
            Log::error("[DANA GATEWAY] DB Error: " . $e->getMessage());
            return redirect($baseUrl . '/dashboard?dana_status=error')->with('error', 'Database Error.');
        }
    }

    /**
     * LOGIC: HANDLE REDIRECT SETELAH BAYAR
     */
    private function handlePaymentRedirect($status, $baseUrl)
    {
        if ($status == 'SUCCESS') {
            return redirect($baseUrl . '/dashboard?payment_status=success')->with('success', 'Pembayaran Berhasil!');
        } elseif ($status == 'PENDING') {
            return redirect($baseUrl . '/dashboard?payment_status=pending')->with('warning', 'Pembayaran sedang diproses.');
        } else {
            return redirect($baseUrl . '/dashboard?payment_status=failed')->with('error', 'Pembayaran Gagal.');
        }
    }

    /**
     * HELPER: TUKAR AUTH CODE JADI ACCESS TOKEN
     */
    private function exchangeDanaToken($authCode)
    {
        try {
            $timestamp  = now('Asia/Jakarta')->toIso8601String();
            $clientId   = config('services.dana.client_id');
            $externalId = (string) time();

            $stringToSign = $clientId . "|" . $timestamp;
            $signature    = $this->generateSignature($stringToSign);

            $body = [
                'grantType' => 'authorization_code',
                'authCode'  => $authCode,
            ];

            // Tembak API dinamis (Prod/Sandbox)
            $apiUrl = config('services.dana.base_url') . '/v1.0/access-token/b2b2c.htm';

            $response = Http::withHeaders([
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'X-PARTNER-ID'  => $clientId,
                'X-CLIENT-KEY'  => $clientId,
                'X-EXTERNAL-ID' => $externalId,
                'Content-Type'  => 'application/json'
            ])->post($apiUrl, $body);

            $result = $response->json();
            $successCodes = ['2001100', '2007400', '2000000'];

            if (isset($result['responseCode']) && in_array($result['responseCode'], $successCodes)) {
                return ['success' => true, 'data' => $result];
            }

            Log::error("[DANA API] Token Exchange Failed: " . json_encode($result));
            return ['success' => false, 'message' => $result['responseMessage'] ?? 'Unknown API Error'];

        } catch (\Exception $e) {
            Log::error("[DANA API] Exception: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * HELPER: GENERATE RSA-SHA256 SIGNATURE
     */
    private function generateSignature($stringToSign)
    {
        $rawKey = config('services.dana.private_key');

        if (empty($rawKey)) {
            Log::error('[DANA DEBUG LOG] ERROR: Private Key dari config KOSONG!');
            throw new \Exception("Private Key kosong. Pastikan Pengaturan Database API sudah terisi.");
        }

        $cleanKey = preg_replace('/-{5}(BEGIN|END) PRIVATE KEY-{5}|\r|\n|\s/', '', $rawKey);
        $formattedKey = "-----BEGIN PRIVATE KEY-----\n" . chunk_split($cleanKey, 64, "\n") . "-----END PRIVATE KEY-----";

        $binarySignature = "";
        if (!openssl_sign($stringToSign, $binarySignature, $formattedKey, OPENSSL_ALGO_SHA256)) {
            Log::error("[DANA SIG] OpenSSL Error: " . openssl_error_string());
            return null;
        }

        return base64_encode($binarySignature);
    }

    /**
     * =========================================================================
     * SINKRONISASI SALDO (SNAP API STYLE)
     * =========================================================================
     */
    public function syncBalance(Request $request)
    {
        $user = Auth::user();
        $accessToken = $user->dana_access_token ?? null;

        if (!$accessToken) {
            return back()->with('error', 'Token DANA tidak ditemukan. Silakan hubungkan akun kembali.');
        }

        try {
            $timestamp = now('Asia/Jakarta')->toIso8601String();
            $path      = '/v1.0/balance-inquiry.htm';

            $body = [
                'partnerReferenceNo' => 'BAL-' . time() . '-' . $user->id,
                'balanceTypes'       => ['BALANCE'],
                'additionalInfo'     => [
                    'accessToken'    => $accessToken
                ]
            ];

            $jsonBody     = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $hashedBody   = strtolower(hash('sha256', $jsonBody));
            $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
            $signature    = $this->generateSignature($stringToSign);

            $fullUrl = config('services.dana.base_url') . $path;

            $response = Http::withHeaders([
                'X-TIMESTAMP'            => $timestamp,
                'X-SIGNATURE'            => $signature,
                'X-PARTNER-ID'           => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID'          => (string) time(),
                'X-DEVICE-ID'            => 'DANA-DASHBOARD-STATION',
                'CHANNEL-ID'             => '95221',
                'ORIGIN'                 => config('services.dana.origin', 'https://m.dana.id'),
                'Authorization-Customer' => 'Bearer ' . $accessToken,
                'Content-Type'           => 'application/json'
            ])->withBody($jsonBody, 'application/json')->post($fullUrl);

            $result = $response->json();
            Log::info("[DANA SYNC SNAP] User: {$user->id}", $result);

            if (isset($result['responseCode']) && $result['responseCode'] == '2001100') {
                $amountString = $result['accountInfos'][0]['availableBalance']['value'];
                $cleanAmount  = floatval($amountString);

                $user->update([
                    'dana_balance' => $cleanAmount,
                    'updated_at'   => now()
                ]);

                return back()->with('success', 'Saldo Real DANA Terupdate: Rp ' . number_format($cleanAmount, 0, ',', '.'));
            }

            $msg = $result['responseMessage'] ?? 'Unknown Error';
            return back()->with('error', 'Gagal Sinkronisasi: ' . $msg);

        } catch (\Exception $e) {
            Log::error("[DANA SYNC ERROR] " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem saat menghubungi DANA.');
        }
    }

    /**
     * =========================================================================
     * CEK STATUS TOPUP
     * =========================================================================
     */
    public function checkTopupStatus(Request $request)
    {
        $trx = DB::table('dana_transactions')->where('reference_no', $request->reference_no)->first();
        $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();

        if (!$trx || !$aff) return back()->with('error', 'Data transaksi valid tidak ditemukan.');

        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $path = '/rest/v1.0/emoney/topup-status';

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
            'Content-Type' => 'application/json',
            'X-TIMESTAMP'  => $timestamp,
            'X-SIGNATURE'  => $signature,
            'X-PARTNER-ID' => config('services.dana.x_partner_id'),
            'X-EXTERNAL-ID'=> (string) time() . \Illuminate\Support\Str::random(6),
            'CHANNEL-ID'   => '95221',
            'ORIGIN'       => config('services.dana.origin'),
        ];

        try {
            $response = Http::timeout(40)
                ->withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            $resCode = $result['responseCode'] ?? '500';

            if ($resCode === '2003900') {
                $danaStatus = $result['latestTransactionStatus'] ?? 'Unknown';
                $msgDesc    = $result['transactionStatusDesc'] ?? 'No Description';

                if ($danaStatus === '00') {
                    DB::table('dana_transactions')->where('id', $trx->id)->update([
                        'status' => 'SUCCESS',
                        'updated_at' => now(),
                        'response_payload' => json_encode($result)
                    ]);
                    return back()->with('success', '✅ Transaksi SUKSES (Confirmed).');
                }
                elseif (in_array($danaStatus, ['06', '10', '01', '02', '03'])) {
                    DB::table('dana_transactions')->where('id', $trx->id)->update([
                        'status' => 'PENDING',
                        'updated_at' => now(),
                        'response_payload' => json_encode($result)
                    ]);
                    return back()->with('warning', "⏳ Transaksi Sedang Diproses (Status: $danaStatus). Harap tunggu.");
                }
                else {
                    DB::transaction(function() use ($trx, $aff, $result) {
                        if ($trx->status !== 'FAILED' && $trx->status !== 'SUCCESS') {
                            DB::table('affiliates')->where('id', $aff->id)->increment('balance', $trx->amount);
                        }
                        DB::table('dana_transactions')->where('id', $trx->id)->update([
                            'status' => 'FAILED',
                            'updated_at' => now(),
                            'response_payload' => json_encode($result)
                        ]);
                    });
                    return back()->with('error', "❌ Transaksi GAGAL ($danaStatus): $msgDesc");
                }
            } else {
                $errMsg = $result['responseMessage'] ?? 'Unknown Error';
                return back()->with('error', "Gagal Cek Status ($resCode): $errMsg");
            }

        } catch (\Exception $e) {
            Log::error('[DANA STATUS] Error', ['msg' => $e->getMessage()]);
            return back()->with('error', 'Sistem Error: ' . $e->getMessage());
        }
    }

    /**
     * =========================================================================
     * TOPUP CUSTOMER
     * =========================================================================
     */
    public function customerTopup(Request $request)
    {
        $request->validate([
            'affiliate_id' => 'required|exists:affiliates,id',
            'phone'        => 'required|numeric',
            'amount'       => 'required|numeric|min:1000',
        ]);

        $cleanPhone = preg_replace('/[^0-9]/', '', $request->phone);
        if (substr($cleanPhone, 0, 2) !== '62') {
            $cleanPhone = (substr($cleanPhone, 0, 1) === '0') ? '62' . substr($cleanPhone, 1) : '62' . $cleanPhone;
        }

        $amount = (float)$request->amount;
        $timestamp  = now('Asia/Jakarta')->toIso8601String();
        $partnerRef = date('YmdHis') . mt_rand(1000, 9999);
        $amountStr  = number_format($amount, 2, '.', '');
        $path       = '/rest/v1.0/emoney/topup';

        // Mencegah Race Condition
        DB::beginTransaction();

        try {
            $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->lockForUpdate()->first();

            if (!$aff || $aff->balance < $amount) {
                DB::rollBack();
                return back()->with('error', 'Saldo affiliate tidak mencukupi.');
            }

            // Potong saldo di awal
            DB::table('affiliates')->where('id', $aff->id)->decrement('balance', $amount);

            $body = [
                "partnerReferenceNo" => $partnerRef,
                "customerNumber"     => $cleanPhone,
                "amount" => ["value" => $amountStr, "currency" => "IDR"],
                "feeAmount" => ["value" => "0.00", "currency" => "IDR"],
                "transactionDate" => $timestamp,
                "categoryId"      => "6",
                "additionalInfo"  => ["fundType" => "AGENT_TOPUP_FOR_USER_SETTLE"]
            ];

            $jsonBody     = json_encode($body, JSON_UNESCAPED_SLASHES);
            $hashedBody   = strtolower(hash('sha256', $jsonBody));
            $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
            $signature    = $this->generateSignature($stringToSign);

            $headers = [
                'Content-Type'  => 'application/json',
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time() . Str::random(4),
                'CHANNEL-ID'    => '95221',
                'ORIGIN'        => config('services.dana.origin'),
            ];

            Log::info('========== [DANA TOPUP START] ==========');
            Log::info('[DANA REQUEST] URL: ' . config('services.dana.base_url') . $path);

            $response = Http::withHeaders($headers)
                ->timeout(60)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            $resCode = $result['responseCode'] ?? ($response->status() == 504 ? '504' : '500');
            $codeCheck = trim((string)$resCode);

            Log::info('[DANA RESPONSE] Result: ', $result ?? ['raw' => $response->body()]);

            if ($codeCheck === '2003800') {
                DB::table('dana_transactions')->insert([
                    'tenant_id'    => $aff->tenant_id ?? 1,
                    'affiliate_id' => $aff->id,
                    'type'         => 'TOPUP',
                    'reference_no' => $partnerRef,
                    'phone'        => $cleanPhone,
                    'amount'       => $amount,
                    'status'       => 'SUCCESS',
                    'response_payload' => json_encode($result),
                    'created_at'   => now()
                ]);

                DB::commit();
                return back()->with('success', '✅ Pencairan Profit Berhasil Diproses!');
            }
            elseif (in_array($codeCheck, ['504', '2023800', '5003801'])) {
                DB::table('dana_transactions')->insert([
                    'tenant_id'    => $aff->tenant_id ?? 1,
                    'affiliate_id' => $aff->id,
                    'type'         => 'TOPUP',
                    'reference_no' => $partnerRef,
                    'phone'        => $cleanPhone,
                    'amount'       => $amount,
                    'status'       => 'PENDING',
                    'response_payload' => json_encode($result),
                    'created_at'   => now()
                ]);

                DB::commit();
                return back()->with('warning', '⏳ Transaksi sedang diproses (Pending) oleh DANA. Mohon tunggu.');
            }
            else {
                DB::rollBack(); // FATAL ERROR DANA -> SALDO OTOMATIS AMAN

                DB::table('dana_transactions')->insert([
                    'tenant_id'    => $aff->tenant_id ?? 1,
                    'affiliate_id' => $aff->id,
                    'type'         => 'TOPUP',
                    'reference_no' => $partnerRef,
                    'phone'        => $cleanPhone,
                    'amount'       => $amount,
                    'status'       => 'FAILED',
                    'response_payload' => json_encode($result ?: ['raw_html' => $response->body()]),
                    'created_at'   => now()
                ]);

                $resMsg = $result['responseMessage'] ?? 'Gateway Error';
                $userMsg = match($codeCheck) {
                    '4033814'   => 'Saldo merchant DANA tidak mencukupi, hubungi admin.',
                    '4033805'   => 'Nomor DANA tujuan tidak valid atau dibekukan.',
                    '4043811'   => 'Nomor DANA tujuan tidak ditemukan.',
                    default     => "Gagal: $resMsg ($codeCheck)"
                };

                return back()->with('error', $userMsg . "\n(Saldo Anda telah dikembalikan)");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[DANA TOPUP] Exception Error: ' . $e->getMessage());
            return back()->with('error', 'Sistem Error Koneksi. Saldo dikembalikan.');
        } finally {
            Log::info('========== [DANA TOPUP END] ==========');
        }
    }

    /**
     * =========================================================================
     * CEK DAFTAR METODE PEMBAYARAN DAN PROMO DARI DANA
     * =========================================================================
     */
    public function consultPay(Request $request)
    {
        $amount = $request->input('amount', '150000.00');
        Log::info("[DANA CONSULT PAY] Meminta daftar metode pembayaran untuk nominal: Rp " . $amount);

        try {
            $timestamp = now('Asia/Jakarta')->toIso8601String();
            $path = '/v1.0/payment-gateway/consult-pay.htm';
            $baseUrl = config('services.dana.base_url');

            // Proteksi IPv6 agar sistem DANA tidak crash
            $clientIp = $request->ip();
            if (filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) || $clientIp == '127.0.0.1') {
                $clientIp = $_SERVER['SERVER_ADDR'] ?? '82.25.62.13';
            }

            $body = [
                "merchantId" => config('services.dana.merchant_id'),
                "amount" => [
                    "value" => number_format((float)$amount, 2, '.', ''),
                    "currency" => "IDR"
                ],
                "externalStoreId" => "toko-pelanggan",
                "additionalInfo" => [
                    "buyer" => [
                        "externalUserType" => "",
                        "nickname" => "",
                        "externalUserId" => "USR-" . time(),
                        "userId" => ""
                    ],
                    "envInfo" => [
                        "sessionId" => Str::random(32),
                        "tokenId" => (string) Str::uuid(),
                        "websiteLanguage" => "id_ID",
                        "clientIp" => $clientIp,
                        "osType" => "Windows.PC",
                        "appVersion" => "1.0",
                        "sdkVersion" => "1.0",
                        "sourcePlatform" => "IPG",
                        "orderOsType" => "WEB",
                        "merchantAppVersion" => "1.0",
                        "terminalType" => "SYSTEM",
                        "orderTerminalType" => "WEB",
                        "extendInfo" => json_encode(["deviceId" => Str::random(16)])
                    ],
                    "merchantTransType" => "DEFAULT"
                ]
            ];

            $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $hashedBody = strtolower(hash('sha256', $jsonBody));
            $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;

            $signature = $this->generateSignature($stringToSign);

            $response = Http::withHeaders([
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time() . Str::random(6),
                'Content-Type'  => 'application/json',
                'CHANNEL-ID'    => '95221',
                'ORIGIN'        => config('services.dana.origin'),
            ])->withBody($jsonBody, 'application/json')->post($baseUrl . $path);

            $result = $response->json();

            if (isset($result['responseCode']) && $result['responseCode'] === '2000000') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Berhasil mengambil daftar metode pembayaran.',
                    'payment_methods' => $result['paymentInfos'] ?? [],
                    'raw_data' => $result
                ]);
            } else {
                Log::warning("[DANA CONSULT PAY] Gagal mengambil data.", ['result' => $result]);
                return response()->json([
                    'status' => 'failed',
                    'message' => "Consult Pay Error: " . ($result['responseMessage'] ?? 'Unknown Error'),
                    'error_code' => $result['responseCode'] ?? null
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error("[DANA CONSULT PAY ERROR] " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }
}
