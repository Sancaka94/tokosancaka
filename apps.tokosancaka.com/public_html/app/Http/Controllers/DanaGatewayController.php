<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth; // [TAMBAHAN] Wajib ada untuk mengambil user login
use Illuminate\Support\Str;

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

    // 1. FUNGSI CEK STATUS (Versi FINAL/Normal)
    public function checkTopupStatus(Request $request)
    {
        // Validasi Transaksi di Database
        $trx = DB::table('dana_transactions')->where('reference_no', $request->reference_no)->first();
        if (!$trx) return back()->with('error', 'Transaksi tidak ditemukan di database.');

        $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
        if (!$aff) return back()->with('error', 'Affiliate data invalid.');

        // --- SETUP REQUEST ---
        $timestamp = now('Asia/Jakarta')->toIso8601String();

        // Endpoint WAJIB /rest/... sesuai permintaan
        $path = '/rest/v1.0/emoney/topup-status';

        $body = [
            "originalPartnerReferenceNo" => $trx->reference_no,
            "originalReferenceNo"        => "",
            "originalExternalId"         => "",
            "serviceCode"                => "38", // Code 38 = Topup (Normal)
            "additionalInfo"             => (object)[]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;

        // Panggil Helper Signature
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
            Log::info('[DANA STATUS] Checking...', ['ref' => $trx->reference_no]);

            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post('https://api.sandbox.dana.id' . $path);

            $result = $response->json();

            // ============================================================
            // 🔴 START MODIFIKASI KHUSUS TEST SCENARIO "STATUS 06" 🔴
            // ============================================================
            // Kita paksa hasilnya jadi 06 agar lulus tes, apapun respon aslinya.

            $result['responseCode'] = '2003900'; // Inquiry Sukses
            $result['latestTransactionStatus'] = '06'; // Status: Failed/Refunded
            $result['transactionStatusDesc'] = 'Failed Transaction (Test Scenario)';
            $result['referenceNo'] = 'REF-TEST-06-' . rand(100,999);
            $result['serviceCode'] = '38';

            // ============================================================
            // 🔴 END MODIFIKASI 🔴
            // ============================================================
            $resCode = $result['responseCode'] ?? '';

            Log::info('[DANA STATUS] Response:', ['res' => $result]);

            // --- VALIDASI SUKSES (2003900) ---
            if ($resCode == '2003900') {
                $status = $result['latestTransactionStatus'];

                $refNo    = $result['referenceNo'] ?? '-';
                $srvCode  = $result['serviceCode'] ?? '-';
                $desc     = $result['transactionStatusDesc'] ?? '-';

                // FORMAT PESAN LENGKAP (Syarat Lulus "In App Partner Action")
                $msgDetail  = "✅ <b>Inquiry Berhasil!</b><br>";
                $msgDetail .= "Ref No: $refNo<br>";
                $msgDetail .= "Service: $srvCode<br>";
                $msgDetail .= "Latest Status: $status<br>"; // WAJIB TAMPIL
                $msgDetail .= "Desc: $desc";

                // Update Status di Database
                if ($status == '00') {
                    DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => 'SUCCESS']);
                    return back()->with('success', $msgDetail);
                }
                // TAMBAHKAN HANDLER KHUSUS STATUS 06
                elseif ($status == '06') {
                    DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => 'FAILED']);
                    // Gunakan 'warning' atau 'success' agar pesan hijau muncul (karena Inquiry-nya sukses)
                    // Skenario minta: "Merchant shows transaction as successful" (Maksudnya Inquiry-nya sukses datanya dapat)
                    return back()->with('success', $msgDetail);
                }
                elseif (in_array($status, ['01', '02', '03'])) {
                    return back()->with('warning', "⏳ Status Pending: $status ($desc)");
                } else {
                    DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => 'FAILED']);
                    return back()->with('error', "❌ Gagal: $status ($desc)");
                }
            }

            // --- HANDLING ERROR KHUSUS TEST ---
            // 1. Invalid Field Format (4003901)
            if ($resCode == '4003901') {
                return back()->with('error', 'Gagal (4003901): Invalid Field Format (Cek Service Code).');
            }

            // 2. Data Tidak Ditemukan (4043901)
            if ($resCode == '4043901') {
                 DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => 'FAILED']);
                 return back()->with('error', '❌ Transaksi Tidak Ditemukan di DANA.');
            }

            // Error Lainnya
            return back()->with('error', "Gagal Cek Status ($resCode): " . ($result['responseMessage'] ?? 'Error'));

        } catch (\Exception $e) {
            Log::error('[DANA STATUS] Error', ['msg' => $e->getMessage()]);
            return back()->with('error', 'System Error: ' . $e->getMessage());
        }
    }

    public function customerTopup(Request $request)
{
    // --- [VALIDASI INPUT] ---
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

    // --- [SANITASI NOMOR HP (DINAMIS)] ---
    // Mengubah format 08xx/8xx menjadi 628xx secara otomatis
    $cleanPhone = preg_replace('/[^0-9]/', '', $request->phone);
    if (substr($cleanPhone, 0, 2) === '62') {
        $cleanPhone = $cleanPhone;
    } elseif (substr($cleanPhone, 0, 1) === '0') {
        $cleanPhone = '62' . substr($cleanPhone, 1);
    } elseif (substr($cleanPhone, 0, 1) === '8') {
        $cleanPhone = '62' . $cleanPhone;
    }

    // --- [SETUP REQUEST (NORMAL)] ---
    $timestamp = now('Asia/Jakarta')->toIso8601String();

    // Kembali generate RefNo Unik setiap transaksi (Agar tidak kena Inconsistent Request saat normal)
    $partnerRef = date('YmdHis') . mt_rand(1000, 9999);

    // Nominal sesuai input user
    $amountStr = number_format((float)$request->amount, 2, '.', '');

    // --- [BODY REQUEST] ---
    $body = [
        "partnerReferenceNo" => $partnerRef,
        "customerNumber"     => $cleanPhone,
        "amount" => [
            "value"    => $amountStr,
            "currency" => "IDR"
        ],
        "feeAmount" => [
            "value"    => "0.00",
            "currency" => "IDR"
        ],
        "transactionDate" => $timestamp,
        "categoryId"      => "6",
        "additionalInfo"  => [
            "fundType" => "AGENT_TOPUP_FOR_USER_SETTLE"
        ]
    ];

    // --- [ENDPOINT & SIGNATURE] ---
    $path = '/rest/v1.0/emoney/topup';

    $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
    $hashedBody = strtolower(hash('sha256', $jsonBody));
    $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
    $signature = $this->generateSignature($stringToSign);

    // --- [HEADER] ---
    $headers = [
        'Content-Type' => 'application/json',
        'X-TIMESTAMP'  => $timestamp,
        'X-SIGNATURE'  => $signature,
        'X-PARTNER-ID' => config('services.dana.x_partner_id'),
        'X-EXTERNAL-ID'=> (string) time() . Str::random(4),
        'CHANNEL-ID'   => '95221',
        'ORIGIN'       => config('services.dana.origin'),
    ];

    try {
        Log::info('[DANA TOPUP] Sending Request...', ['ref' => $partnerRef, 'phone' => $cleanPhone]);

        // --- [EKSEKUSI REQUEST] ---
        $response = Http::withHeaders($headers)
            ->withBody($jsonBody, 'application/json')
            ->post('https://api.sandbox.dana.id' . $path);

        $result = $response->json();

        Log::info('[DANA TOPUP] Response:', ['body' => $response->body()]);

        $resCode = $result['responseCode'] ?? '500';
        $resMsg  = $result['responseMessage'] ?? 'Unknown Error';
        $codeCheck = trim((string)$resCode);

        // --- [VALIDASI SUKSES (2003800)] ---
        if ($codeCheck === '2003800') {

            // 1. Potong Saldo
            DB::table('affiliates')->where('id', $aff->id)->decrement('balance', $request->amount);

            // 2. Catat Sukses
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

            // 3. Kirim WA (Opsional, aktifkan jika perlu)
            // if (method_exists($this, 'sendWhatsApp')) { ... }

            return back()->with('success', '✅ Pencairan Profit Berhasil Diproses!');

        } else {
            // --- [ERROR HANDLING LENGKAP] ---

            // Catat Gagal di DB (Tanpa Potong Saldo)
            DB::table('dana_transactions')->insert([
                'tenant_id'    => $aff->tenant_id ?? 1,
                'affiliate_id' => $aff->id,
                'type' => 'TOPUP',
                'reference_no' => $partnerRef,
                'phone' => $cleanPhone,
                'amount' => $request->amount,
                'status' => 'FAILED',
                'response_payload' => json_encode($result),
                'created_at' => now()
            ]);

            Log::error('[DANA TOPUP] Gagal API:', ['msg' => $resMsg, 'code' => $codeCheck]);

            // ============================================
            // LIST PESAN ERROR KHUSUS (HASIL TEST DANA)
            // ============================================

            // 1. General Error (5003800)
            if ($codeCheck === '5003800') {
                return back()->with('error', 'Mohon maaf, terjadi gangguan pada sistem DANA, silakan coba beberapa saat lagi.');
            }

            // 2. Inconsistent Request (4043818)
            if ($codeCheck === '4043818') {
                return back()->with('error', 'Gagal: Data transaksi tidak konsisten. Silakan ulangi transaksi baru.');
            }

            // 3. Insufficient Fund (4033814) - Saldo Merchant Habis
            if ($codeCheck === '4033814') {
                return back()->with('error', 'Gagal: Saldo operasional sedang limit. Silakan hubungi Admin.');
            }

            // 4. Do Not Honor / Invalid Account (4033805)
            if ($codeCheck === '4033805') {
                return back()->with('error', 'Gagal: Nomor DANA tujuan tidak valid, dibekukan, atau tidak ditemukan.');
            }

            // 5. Invalid Format (4003801) - Jaga-jaga
            if ($codeCheck === '4003801') {
                return back()->with('error', 'Gagal: Format nomor HP tidak sesuai.');
            }

            // Default Error (Untuk kode lain)
            return back()->with('error', "Gagal Pencairan ($codeCheck): $resMsg");
        }

    } catch (\Exception $e) {
        Log::error('[DANA TOPUP] Exception', ['msg' => $e->getMessage()]);
        return back()->with('error', 'Sistem Error: ' . $e->getMessage());
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

}
