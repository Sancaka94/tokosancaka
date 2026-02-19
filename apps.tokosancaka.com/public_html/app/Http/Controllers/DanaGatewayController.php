<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Logging aktif
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Carbon\Carbon; // <--- Pastikan baris ini ada di paling atas file

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

// Services
use App\Services\DokuJokulService;
use App\Services\KiriminAjaService;
use App\Services\DanaSignatureService; // <--- TAMBAHKAN INI
 // Pastikan Model Order diimport

// SDK Models
use Dana\Widget\v1\Model\WidgetPaymentRequest;
use Dana\Widget\v1\Model\Money;
use Dana\Widget\v1\Model\UrlParam;
use Dana\Widget\v1\Model\WidgetPaymentRequestAdditionalInfo;
use Dana\Widget\v1\Model\EnvInfo;
use Dana\Widget\v1\Model\Order as DanaOrder; // Alias biar ga bentrok

// SDK Enums (DATA DARI ANDA)
use Dana\Widget\v1\Enum\PayMethod;
use Dana\Widget\v1\Enum\SourcePlatform;
use Dana\Widget\v1\Enum\TerminalType;
use Dana\Widget\v1\Enum\OrderTerminalType;
use Dana\Widget\v1\Enum\Type; // Untuk UrlParam type (PAY_RETURN)

// Config
use Dana\Configuration;
use Dana\Env;
use Dana\Widget\v1\Api\WidgetApi;

class DanaGatewayController extends Controller
{
    /**
     * KONFIGURASI URL CALLBACK
     * URL ini harus SAMA PERSIS dengan yang didaftarkan di Dashboard DANA Developer.
     * Tidak boleh beda satu karakter pun (termasuk http/https).
     */
    private const FIXED_CALLBACK_URL = 'https://apps.tokosancaka.com/dana/callback';

    /**
     * MAIN HANDLER: MENERIMA SEMUA TAMU DARI DANA
     */
    public function handleCallback(Request $request)
    {
        // 1. Ambil Parameter dari DANA
        $authCode = $request->input('auth_code'); // Untuk Binding
        $status   = $request->input('resultStatus'); // Untuk Payment Redirect
        $state    = $request->input('state'); // <--- KTP/IDENTITAS USER

        Log::info("[DANA GATEWAY] Hit Masuk.", [
            'ip' => $request->ip(),
            'state' => $state,
            'code' => $authCode ? 'YES' : 'NO'
        ]);

        // 2. Validasi State
        if (empty($state)) {
            return redirect('/')->with('error', 'Invalid Request: No State Identifier');
        }

        // 3. Bedah State (Parsing)
        // Format yang kita sepakati: ACTION-USERID-SUBDOMAIN-TENANTID
        // Contoh: BIND_TENANT-5-bakso-2
        $parts = explode('-', $state);

        if (count($parts) < 4) {
            Log::error("[DANA GATEWAY] Format State Salah: $state");
            return redirect('/')->with('error', 'Sesi Kadaluarsa atau Format Salah');
        }

        $action    = $parts[0]; // BIND_TENANT, BIND_MEMBER, PAY
        $userId    = $parts[1]; // User ID atau Affiliate ID
        $subdomain = $parts[2]; // Subdomain asal (misal: 'bakso')
        $tenantId  = $parts[3]; // ID Tenant

        // 4. Bangun URL Pulang (Smart Redirect Base)
        $scheme = $request->secure() ? 'https://' : 'http://';
        $appDomain = env('APP_URL_DOMAIN', 'tokosancaka.com');

        // Base URL: https://bakso.tokosancaka.com
        $tenantBaseUrl = $scheme . $subdomain . '.' . $appDomain;

        // 5. Switch Logic Berdasarkan ACTION
        switch ($action) {
            case 'BIND_TENANT':
                return $this->handleBinding($authCode, $userId, 'TENANT', $tenantBaseUrl);

            case 'BIND_MEMBER':
                return $this->handleBinding($authCode, $userId, 'MEMBER', $tenantBaseUrl);

            case 'PAY':
                // Logic jika DANA me-redirect setelah bayar (Acquiring)
                return $this->handlePaymentRedirect($status, $tenantBaseUrl);

            default:
                Log::warning("[DANA GATEWAY] Unknown Action: $action");
                return redirect($tenantBaseUrl)->with('error', 'Aksi tidak dikenali.');
        }
    }

    /**
     * LOGIC: HANDLE BINDING (SAMBUNG AKUN)
     * Tukar Auth Code -> Access Token -> Simpan ke DB -> Redirect
     */
    private function handleBinding($authCode, $userId, $userType, $baseUrl)
    {
        if (!$authCode) {
            // User menolak binding / klik cancel
            return redirect($baseUrl . '/dashboard?dana_status=cancelled')->with('error', 'Koneksi DANA dibatalkan.');
        }

        // A. Tukar Token ke API DANA
        $tokenResult = $this->exchangeDanaToken($authCode);

        if (!$tokenResult['success']) {
            Log::error("[DANA GATEWAY] Gagal Tukar Token User $userId: " . $tokenResult['message']);
            return redirect($baseUrl . '/dashboard?dana_status=failed')->with('error', 'Gagal menghubungkan DANA: ' . $tokenResult['message']);
        }

        $accessToken = $tokenResult['data']['accessToken'];
        $expiry      = $tokenResult['data']['expiresIn'] ?? null; // Detik

        // B. Simpan ke Database yang Sesuai
        try {
            if ($userType === 'TENANT') {
                // Update tabel USERS (Admin Toko)
                DB::table('users')->where('id', $userId)->update([
                    'dana_access_token' => $accessToken,
                    'dana_token_expiry' => $expiry, // Opsional simpan expiry
                    'updated_at'        => now()
                ]);
                Log::info("[DANA GATEWAY] ✅ Token Saved for TENANT User $userId");
                $redirectPath = '/dashboard';

            } else {
                // Update tabel AFFILIATES (Member)
                DB::table('affiliates')->where('id', $userId)->update([
                    'dana_access_token' => $accessToken,
                    // 'dana_token_expiry' => $expiry, // Jika ada kolomnya
                    'updated_at'        => now()
                ]);
                Log::info("[DANA GATEWAY] ✅ Token Saved for MEMBER User $userId");
                $redirectPath = '/member/dashboard';
            }

            // C. Redirect Sukses
            // Kita kirim parameter query string agar frontend di subdomain bisa menangkap notifikasi
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
        // Status dari DANA biasanya: SUCCESS, PENDING, FAILED
        // Kita kembalikan user ke dashboard/history
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
     * API: /v1.0/access-token/b2b2c.htm
     */
    private function exchangeDanaToken($authCode)
    {
        try {
            $timestamp  = now('Asia/Jakarta')->toIso8601String();
            $clientId   = config('services.dana.x_partner_id');
            $externalId = (string) time(); // Unique Request ID

            // Signature String: clientId + "|" + timestamp (Sesuai Dokumen B2B2C)
            $stringToSign = $clientId . "|" . $timestamp;
            $signature    = $this->generateSignature($stringToSign);

            $body = [
                'grantType' => 'authorization_code',
                'authCode'  => $authCode,
            ];

            // Request ke DANA Sandbox / Production
            // Gunakan URL yang sesuai env
            $apiUrl = 'https://api.sandbox.dana.id/v1.0/access-token/b2b2c.htm';

            $response = Http::withHeaders([
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'X-PARTNER-ID'  => $clientId,
                'X-CLIENT-KEY'  => $clientId, // Kadang butuh ini
                'X-EXTERNAL-ID' => $externalId,
                'Content-Type'  => 'application/json'
            ])->post($apiUrl, $body);

            $result = $response->json();

            // Kode Sukses DANA
            $successCodes = ['2001100', '2007400', '2000000'];

            if (isset($result['responseCode']) && in_array($result['responseCode'], $successCodes)) {
                return ['success' => true, 'data' => $result];
            }

            Log::error("[DANA API] Token Exchange Failed: " . json_encode($result));
            return [
                'success' => false,
                'message' => $result['responseMessage'] ?? 'Unknown API Error'
            ];

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
        $privateKeyContent = config('services.dana.private_key');

        // Bersihkan Key dari spasi/enter yang mungkin berantakan
        $cleanKey = preg_replace('/-{5}(BEGIN|END) PRIVATE KEY-{5}|\r|\n|\s/', '', $privateKeyContent);

        // Format ulang ke PEM standard
        $formattedKey = "-----BEGIN PRIVATE KEY-----\n" . chunk_split($cleanKey, 64, "\n") . "-----END PRIVATE KEY-----";

        $binarySignature = "";

        // Sign menggunakan OpenSSL SHA256
        if (!openssl_sign($stringToSign, $binarySignature, $formattedKey, OPENSSL_ALGO_SHA256)) {
            Log::error("[DANA SIG] OpenSSL Error: " . openssl_error_string());
            return null;
        }

        return base64_encode($binarySignature);
    }

   /**
     * [UPDATE] LOGIC: SINKRONISASI SALDO (SNAP API STYLE)
     * Endpoint: /v1.0/balance-inquiry.htm
     */
    public function syncBalance(Request $request)
    {
        // 1. Ambil User Login
        $user = Auth::user();
        $accessToken = $user->dana_access_token;

        // 2. Cek Token
        if (!$accessToken) {
            return back()->with('error', 'Token DANA tidak ditemukan. Silakan hubungkan akun kembali.');
        }

        try {
            // 3. Persiapan Parameter
            $timestamp = now('Asia/Jakarta')->toIso8601String();
            $path      = '/v1.0/balance-inquiry.htm'; // Path relatif (Penting untuk signature)

            // 4. Body Request
            $body = [
                'partnerReferenceNo' => 'BAL-' . time() . '-' . $user->id,
                'balanceTypes'       => ['BALANCE'],
                'additionalInfo'     => [
                    'accessToken'    => $accessToken
                ]
            ];

            // 5. Generate Signature (Logika SNAP)
            // JSON Encode dengan flags spesifik agar hash match
            $jsonBody     = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            // Hash body menjadi lowercase SHA256
            $hashedBody   = strtolower(hash('sha256', $jsonBody));

            // String to Sign: METHOD:PATH:HASH_BODY:TIMESTAMP
            $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;

            $signature    = $this->generateSignature($stringToSign);

            // 6. URL Lengkap (Sandbox)
            // Ganti ke https://api.dana.id jika Production
            $fullUrl = 'https://api.sandbox.dana.id' . $path;

            // 7. Kirim Request
            $response = Http::withHeaders([
                'X-TIMESTAMP'            => $timestamp,
                'X-SIGNATURE'            => $signature,
                'X-PARTNER-ID'           => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID'          => (string) time(),
                'X-DEVICE-ID'            => 'DANA-DASHBOARD-STATION', // Sesuai kode Anda
                'CHANNEL-ID'             => '95221',                  // Sesuai kode Anda
                'ORIGIN'                 => config('services.dana.origin', 'https://m.dana.id'),
                'Authorization-Customer' => 'Bearer ' . $accessToken,
                'Content-Type'           => 'application/json'
            ])
            ->withBody($jsonBody, 'application/json') // Kirim raw json body agar tidak berubah formatnya
            ->post($fullUrl);

            $result = $response->json();

            Log::info("[DANA SYNC SNAP] User: {$user->id}", $result);

            // 8. Cek Response Code 2001100 (Sukses SNAP)
            if (isset($result['responseCode']) && $result['responseCode'] == '2001100') {

                // Ambil value saldo
                // Struktur biasanya: accountInfos[0]['availableBalance']['value']
                $amountString = $result['accountInfos'][0]['availableBalance']['value'];

                // Konversi ke float/double untuk DB
                $cleanAmount  = floatval($amountString);

                // Update Database User
                $user->update([
                    'dana_balance' => $cleanAmount,
                    'updated_at'   => now()
                ]);

                return back()->with('success', 'Saldo Real DANA Terupdate: Rp ' . number_format($cleanAmount, 0, ',', '.'));
            }

            // Error Handling
            $msg = $result['responseMessage'] ?? 'Unknown Error';
            return back()->with('error', 'Gagal Sinkronisasi: ' . $msg);

        } catch (\Exception $e) {
            Log::error("[DANA SYNC ERROR] " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem saat menghubungi DANA.');
        }
    }


public function checkTopupStatus(Request $request)
{
    // --- [VALIDASI DATA] ---
    $trx = DB::table('dana_transactions')->where('reference_no', $request->reference_no)->first();
    $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();

    if (!$trx || !$aff) return back()->with('error', 'Data transaksi valid tidak ditemukan.');

    // --- [SETUP REQUEST] ---
    $timestamp = now('Asia/Jakarta')->toIso8601String();
    $path = '/rest/v1.0/emoney/topup-status'; // Endpoint Status

    $body = [
        "originalPartnerReferenceNo" => $trx->reference_no,
        "originalReferenceNo"        => "",
        "originalExternalId"         => "",
        "serviceCode"                => "38",
        "additionalInfo"             => (object)[]
    ];

    // --- [SIGNATURE & HEADERS] ---
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
        // Hit API DANA
        $response = Http::timeout(40)
            ->withHeaders($headers)
            ->withBody($jsonBody, 'application/json')
            ->post('https://api.sandbox.dana.id' . $path);

        $result = $response->json();
        $resCode = $result['responseCode'] ?? '500';

        // --- [LOGIC UTAMA PERBAIKAN] ---
        if ($resCode === '2003900') {

            // Ambil status terbaru
            $danaStatus = $result['latestTransactionStatus'] ?? 'Unknown';
            $msgDesc    = $result['transactionStatusDesc'] ?? 'No Description';

            // 1. SUKSES (00)
            if ($danaStatus === '00') {
                DB::table('dana_transactions')->where('id', $trx->id)->update([
                    'status' => 'SUCCESS',
                    'updated_at' => now(),
                    'response_payload' => json_encode($result)
                ]);
                return back()->with('success', '✅ Transaksi SUKSES (Confirmed).');
            }

            // 2. PENDING / IN PROCESS (06, 10, 01) <-- PERBAIKAN DISINI
            // Kita tambahkan '06' agar tidak dianggap gagal
            elseif (in_array($danaStatus, ['06', '10', '01', '02', '03'])) {
                DB::table('dana_transactions')->where('id', $trx->id)->update([
                    'status' => 'PENDING',
                    'updated_at' => now(),
                    'response_payload' => json_encode($result)
                ]);
                return back()->with('warning', "⏳ Transaksi Sedang Diproses (Status: $danaStatus). Harap tunggu.");
            }

            // 3. GAGAL (Selain kode di atas)
            else {
                // Jangan lupa balikin saldo jika tadinya kepotong tapi ternyata gagal
                // (Optional: tergantung logic bisnis Bapak, mau refund otomatis atau manual)
                 DB::transaction(function() use ($trx, $aff, $result) {
                    // Jika status sebelumnya bukan FAILED, kita refund
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
            // Error dari API (Bukan 2003900)
            $errMsg = $result['responseMessage'] ?? 'Unknown Error';
            return back()->with('error', "Gagal Cek Status ($resCode): $errMsg");
        }

    } catch (\Exception $e) {
        Log::error('[DANA STATUS] Error', ['msg' => $e->getMessage()]);
        return back()->with('error', 'Sistem Error: ' . $e->getMessage());
    }
}

    public function customerTopup(Request $request)
{
    // 1. Validasi Input (Standar)
    $request->validate([
        'affiliate_id' => 'required|exists:affiliates,id',
        'phone'        => 'required|numeric',
        'amount'       => 'required|numeric|min:1000',
    ]);

    $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
    if (!$aff) return back()->with('error', 'Affiliate tidak ditemukan.');

    if ($aff->balance < $request->amount) {
        return back()->with('error', 'Saldo tidak mencukupi.');
    }

    // 2. Sanitasi Nomor HP
    $cleanPhone = preg_replace('/[^0-9]/', '', $request->phone);
    if (substr($cleanPhone, 0, 2) !== '62') {
        $cleanPhone = (substr($cleanPhone, 0, 1) === '0') ? '62' . substr($cleanPhone, 1) : '62' . $cleanPhone;
    }

    // 3. Persiapan Request
    $timestamp  = now('Asia/Jakarta')->toIso8601String();
    $partnerRef = date('YmdHis') . mt_rand(1000, 9999);
    $amountStr  = number_format((float)$request->amount, 2, '.', '');
    $path       = '/rest/v1.0/emoney/topup';

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

    // --- [LOG 1: REQUEST DATA] ---
    Log::info('========== [DANA TOPUP START] ==========');
    Log::info('[DANA REQUEST] URL: https://api.sandbox.dana.id' . $path);
    Log::info('[DANA REQUEST] Headers:', $headers);
    Log::info('[DANA REQUEST] Payload:', $body);
    Log::info('[DANA REQUEST] StringToSign: ' . $stringToSign);

    try {
        $response = Http::withHeaders($headers)
            ->timeout(60) // Tambahkan timeout agar tidak gantung
            ->withBody($jsonBody, 'application/json')
            ->post('https://api.sandbox.dana.id' . $path);

        $result = $response->json();

        // --- [LOG 2: RESPONSE DATA] ---
        Log::info('[DANA RESPONSE] Status Code: ' . $response->status());
        Log::info('[DANA RESPONSE] Raw Body: ' . $response->body());

        if ($response->failed()) {
            Log::error('[DANA TOPUP] HTTP Request Failed (Status ' . $response->status() . ')');
        }

        // --- [LOGIKA PENANGANAN RESPONSE] ---
        $resCode   = $result['responseCode'] ?? ($response->status() == 504 ? '504' : '500');
        $resMsg    = $result['responseMessage'] ?? 'Internal Server Error / Gateway Timeout';
        $codeCheck = trim((string)$resCode);

        if ($codeCheck === '2003800') {
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
            Log::info('[DANA TOPUP] Result: ✅ SUCCESS');
            return back()->with('success', '✅ Pencairan Profit Berhasil Diproses!');
        } else {
            // Catat Gagal di DB
            DB::table('dana_transactions')->insert([
                'tenant_id'    => $aff->tenant_id ?? 1,
                'affiliate_id' => $aff->id,
                'type' => 'TOPUP',
                'reference_no' => $partnerRef,
                'phone' => $cleanPhone,
                'amount' => $request->amount,
                'status' => 'FAILED',
                'response_payload' => json_encode($result ?: ['raw_html' => $response->body()]),
                'created_at' => now()
            ]);

            Log::warning('[DANA TOPUP] Result: ❌ FAILED (Code: '.$codeCheck.')');

            // Pesan error ramah user
            $userMsg = match($codeCheck) {
                '504'       => 'Server DANA sedang sibuk (Gateway Timeout), silakan coba 1 menit lagi.',
                '5003800'   => 'Gangguan sistem DANA, coba lagi nanti.',
                '4033814'   => 'Saldo merchant tidak mencukupi, hubungi admin.',
                '4033805'   => 'Nomor DANA tujuan tidak valid atau dibekukan.',
                default     => "Gagal: $resMsg ($codeCheck)"
            };

            return back()
                ->with('error', $userMsg)
                ->with('debug_error', $response->body());
        }

    } catch (\Exception $e) {
        Log::error('[DANA TOPUP] Exception Error: ' . $e->getMessage());
        Log::error('[DANA TOPUP] Trace: ' . $e->getTraceAsString());
        return back()->with('error', 'Sistem Error: ' . $e->getMessage());
    } finally {
        Log::info('========== [DANA TOPUP END] ==========');
    }
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
            return back()->with('success', 'Saldo Real DANA Terupdate!');
        }
        return back()->with('error', 'Gagal: ' . ($result['responseMessage'] ?? 'Error'));
    }

    // =========================================================================
    // FITUR BARU: CONSULT PAY (Mengambil Daftar Metode Pembayaran & Promo)
    // =========================================================================
    public function consultPay(Request $request)
    {
        // 1. Validasi Input (opsional, sesuaikan kebutuhan SancakaPOS)
        $amount = $request->input('amount', '150000.00'); // Contoh nominal

        Log::info("[DANA CONSULT PAY] Meminta daftar metode pembayaran untuk nominal: Rp " . $amount);

        try {
            // 2. Persiapan Parameter SNAP API
            $timestamp = now('Asia/Jakarta')->toIso8601String();
            $path = '/v1.0/payment-gateway/consult-pay.htm';
            $baseUrl = config('services.dana.dana_env') === 'PRODUCTION' ? 'https://api.dana.id' : 'https://api.sandbox.dana.id';

            // 3. Siapkan Payload Sesuai Dokumentasi
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
                        "externalUserId" => "USR-" . time(), // Sesuaikan dengan ID User kamu
                        "userId" => ""
                    ],
                    "envInfo" => [
                        "sessionId" => Str::random(32),
                        "tokenId" => (string) Str::uuid(),
                        "websiteLanguage" => "id_ID",
                        "clientIp" => $request->ip() ?? "127.0.0.1",
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

            // 4. Generate Signature dengan Format SNAP API (METHOD:PATH:HASH_BODY:TIMESTAMP)
            $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $hashedBody = strtolower(hash('sha256', $jsonBody));
            $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;

            $signature = $this->generateSignature($stringToSign);

            // 5. Kirim Request ke DANA
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

            // 6. Evaluasi Hasil (Kode Sukses: 2000000)
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
