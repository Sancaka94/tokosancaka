<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant; // <--- WAJIB TAMBAH
use App\Models\Affiliate; // <--- BARIS INI WAJIB ADA
use Illuminate\Support\Str;

class DanaDashboardController extends Controller
{
    protected $tenantId;

public function index(Request $request)
{
    $host = $request->getHost();
    $subdomain = explode('.', $host)[0];
    $isMainDomain = ($subdomain === 'tokosancaka' || $subdomain === 'app' || $subdomain === 'localhost');

    $tenant = \App\Models\Tenant::where('subdomain', $subdomain)->first();
    $user = auth()->user();

    // Logika Filter Data
    if ($user->role === 'super_admin' && $isMainDomain) {
        // Super Admin: Lihat semua data dengan pagination 15 data per halaman
        $transactions = DB::table('dana_transactions')
            ->orderBy('id', 'DESC')
            ->paginate(15);

        $affiliates = DB::table('affiliates')->get();
    } else {
        // Admin Toko: Hanya data miliknya dengan pagination
        $transactions = DB::table('dana_transactions')
            ->where('tenant_id', $user->tenant_id)
            ->orderBy('id', 'DESC')
            ->paginate(10); // Lebih sedikit agar rapi di dashboard toko

        $affiliates = DB::table('affiliates')
            ->where('tenant_id', $user->tenant_id)
            ->get();
    }

    return view('dana_dashboard', compact('transactions', 'affiliates'));
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
    Log::info('[DANA CALLBACK] Masuk...', $request->all());

    $authCode = $request->input('authCode');
    $stateRaw = $request->input('state');

    // 1. VALIDASI DATA MENTAH
    if (!$authCode || !$stateRaw) {
        Log::error('[DANA CALLBACK] Gagal: AuthCode atau State kosong.');
        return redirect('https://apps.tokosancaka.com')->with('error', 'Callback DANA Invalid (Data Kosong).');
    }

    // 2. BONGKAR STATE
    // Format Harapan: TIPE - ID_USER - SUBDOMAIN - TENANT_ID
    $parts = explode('-', $stateRaw);

    // Default Fallback jika parsing gagal
    $userType  = $parts[0] ?? 'UNKNOWN';
    $userId    = $parts[1] ?? 0;
    $subdomain = $parts[2] ?? 'apps'; // Default ke 'apps' jika kosong
    $tenantId  = $parts[3] ?? 1;

    // Tentukan Base Domain (Sesuaikan dengan domain Anda)
    $rootDomain = 'tokosancaka.com';

    // Tentukan Path Dashboard
    $dashboardPath = '/member/dashboard'; // Default Member
    if ($userType === 'ADMIN') {
        $dashboardPath = '/admin/dashboard'; // Sesuaikan path admin filament
    }

    // RAKIT URL TARGET SEKARANG (Agar jika error, kita tetap bisa lempar balik kesini)
    // Hasil: https://apps.tokosancaka.com/member/dashboard
    $targetUrl = "https://{$subdomain}.{$rootDomain}{$dashboardPath}";

    // Cek Validitas Format State
    if (count($parts) < 4) {
        Log::error("[DANA CALLBACK] Format State Salah: $stateRaw");
        return redirect($targetUrl)->with('error', 'Gagal Verifikasi: Format Data Invalid.');
    }

    // 3. TENTUKAN TABEL
    $tableName = ($userType === 'ADMIN') ? 'users' : 'affiliates';

    // 4. REQUEST TOKEN KE DANA
    try {
        $timestamp  = now('Asia/Jakarta')->toIso8601String();
        $clientId   = config('services.dana.x_partner_id');
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
        ])->post('https://api.sandbox.dana.id' . $path, $body);

        $result = $response->json();
        $successCodes = ['2001100', '2007400'];

        // JIKA SUKSES
        if (isset($result['responseCode']) && in_array($result['responseCode'], $successCodes)) {

            // Update Database
            $affected = DB::table($tableName)
                ->where('id', $userId)
                ->where('tenant_id', $tenantId)
                ->update([
                    'dana_access_token' => $result['accessToken'],
                    'dana_connected_at' => now(),
                    'updated_at' => now()
                ]);

            if ($affected === 0) {
                Log::warning("[DANA CALLBACK] ID $userId Tenant $tenantId tidak ditemukan di tabel $tableName");
            }

            // Catat Log Transaksi (Opsional, gunakan try-catch agar tidak memutus flow)
            try {
                DB::table('dana_transactions')->insert([
                    'affiliate_id' => ($userType === 'MEMBER') ? $userId : null,
                    'type'         => 'BINDING',
                    'reference_no' => $externalId,
                    'phone'        => '-',
                    'amount'       => 0,
                    'status'       => 'SUCCESS',
                    'response_payload' => json_encode($result),
                    'created_at'   => now()
                ]);
            } catch (\Exception $e) {}

            Log::info("[DANA CALLBACK] Berhasil! Redirecting ke: $targetUrl");
            return redirect($targetUrl)->with('success', '✅ Akun DANA Berhasil Terhubung!');
        }

        // JIKA GAGAL DAPAT TOKEN
        Log::error('[DANA CALLBACK] DANA Reject:', $result);
        return redirect($targetUrl)->with('error', 'Gagal menghubungkan DANA: ' . ($result['responseMessage'] ?? 'Unknown Error'));

    } catch (\Exception $e) {
        Log::error('[DANA CALLBACK] System Error:', ['msg' => $e->getMessage()]);
        // Tetap lempar ke Dashboard (jangan ke root /)
        return redirect($targetUrl)->with('error', 'Terjadi Kesalahan Sistem saat verifikasi.');
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

   public function topupSaldo(Request $request)
{
    Log::info('[DANA TOPUP] --- MEMULAI PROSES TOPUP ---', ['affiliate_id' => $request->affiliate_id]);

    // 1. Ambil data affiliate
    $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();

    // 2. Validasi Saldo Profit
    if (!$aff || $aff->balance < $request->amount) {
        Log::warning('[DANA TOPUP] Saldo Tidak Cukup atau Affiliate Tidak Ditemukan');
        return back()->with('error', 'Gagal: Saldo profit tidak mencukupi.');
    }

    // 3. Sanitasi Nomor HP (Ubah 08xx jadi 628xx)
    $cleanPhone = preg_replace('/[^0-9]/', '', $request->phone ?? $aff->whatsapp);
    if (substr($cleanPhone, 0, 1) === '0') $cleanPhone = '62' . substr($cleanPhone, 1);

    // 4. Siapkan Data Request
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

    // 5. Definisikan Headers
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
            ->post('https://api.sandbox.dana.id' . $path);

        $result = $response->json();

        Log::info('[DANA TOPUP] Respon Diterima', ['status' => $response->status(), 'result' => $result]);

        // 6. Cek Keberhasilan (Status 200 di Sandbox seringkali result-nya null)
        if ($response->successful()) {

            // A. Potong Saldo Profit Affiliate
            DB::table('affiliates')->where('id', $aff->id)->decrement('balance', $request->amount);

            // B. Catat ke Audit Log Transaksi
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

            // C. Kirim Notifikasi WhatsApp ke USER (Affiliate)
            $pesanUser = "✅ *PENCAIRAN PROFIT BERHASIL*\n\n";
            $pesanUser = "Halo " . $aff->name . ",\n";
            $pesanUser = "Pencairan profit Anda ke DANA telah sukses.\n\n";
            $pesanUser = "*Detail:* \n";
            $pesanUser = "▪️ Nominal: Rp " . number_format($request->amount, 0, ',', '.') . "\n";
            $pesanUser = "▪️ No. DANA: " . $cleanPhone . "\n";
            $pesanUser = "▪️ Ref ID: " . $partnerRef . "\n";
            $pesanUser = "▪️ Waktu: " . now()->format('d/m H:i') . " WIB\n\n";
            $pesanUser = "Saldo profit Anda telah otomatis terpotong. Terima kasih!";

            $this->sendWhatsApp($cleanPhone, $pesanUser);

            // D. Kirim Notifikasi ke ADMIN (Nomor Bos)
            $pesanAdmin = "📢 *LAPORAN TOPUP SUKSES*\n\n";
            $pesanAdmin = "Affiliate: " . $aff->name . " (ID: " . $aff->id . ")\n";
            $pesanAdmin = "Nominal: Rp " . number_format($request->amount, 0, ',', '.') . "\n";
            $pesanAdmin = "Tujuan: " . $cleanPhone . "\n";
            $pesanAdmin = "Status: Saldo Berhasil Dipotong.";

            $this->sendWhatsApp('6285745808809', $pesanAdmin); // Nomor Admin Bos

            Log::info('[DANA TOPUP] BERHASIL & WA TERKIRIM');

            return back()->with('success', '💸 Topup Berhasil, Saldo Dipotong, dan WA Terkirim!');
        }

        return back()->with('error', 'Gagal dari DANA: ' . ($result['responseMessage'] ?? 'Respon Server Error'));

    } catch (\Exception $e) {
        Log::error('[DANA TOPUP] Exception!', ['msg' => $e->getMessage()]);
        return back()->with('error', 'Sistem Error: ' . $e->getMessage());
    }
}

public function accountInquiry(Request $request)
{
    // --- [LOG 1] INPUT REQUEST ---
    Log::info('[DANA INQUIRY] Start Process', [
        'affiliate_id' => $request->affiliate_id,
        'amount' => $request->amount,
        'ip' => $request->ip()
    ]);

    $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
    if (!$aff) {
        Log::error('[DANA INQUIRY] Affiliate Not Found', ['id' => $request->affiliate_id]);
        return back()->with('error', 'Affiliate tidak ditemukan.');
    }

    // --- [LOG 2] SANITASI NOMOR HP ---
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

    // --- [LOG 3] SIGNATURE PROCESS ---
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
        'X-IP-ADDRESS'  => $request->ip() ?? '127.0.0.1',
        'X-DEVICE-ID'   => 'DANA-DASHBOARD-01',
        'CHANNEL-ID'    => '95221'
    ];

    try {
        // --- [LOG 4] SENDING REQUEST ---
        Log::info('[DANA INQUIRY] Sending Request to DANA', ['body' => $body]);

        $response = Http::withHeaders($headers)->withBody($jsonBody, 'application/json')->post('https://api.sandbox.dana.id' . $path);
        $result = $response->json();

        // --- [LOG 5] RESPONSE RECEIVED ---
        Log::info('[DANA INQUIRY] Response Received', ['status' => $response->status(), 'result' => $result]);

        $resCode = $result['responseCode'] ?? '5003700';
        $resMsg = $result['responseMessage'] ?? 'Unexpected response';

        // --- [DATABASE 1] CATAT KE TABEL TRANSAKSI (AUDIT LOG) ---
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

        // --- [MAPPING] RESPOND CODE SESUAI DOKUMENTASI BOS ---
        $responseMapping = [
            '2003700' => '✅ SUCCESS: Account Inquiry processed.',
            '4003700' => '❌ FAILED: Bad Request (General).',
            '4003701' => '❌ FAILED: Invalid Field Format.',
            '4003702' => '❌ FAILED: Invalid Mandatory Field.',
            '4013700' => '❌ UNAUTHORIZED: General Auth Error.',
            '4013701' => '❌ UNAUTHORIZED: Invalid B2B Token.',
            '4013702' => '❌ UNAUTHORIZED: Invalid Customer Token.',
            '4033702' => '⚠️ TEST CASE: Exceeds Amount Limit (21jt).',
            '4033705' => '❌ FAILED: Do Not Honor (Abnormal Status).',
            '4033714' => '❌ FAILED: Insufficient Funds (Merchant).',
            '4033718' => '❌ FAILED: Inactive Account.',
            '4043711' => '❌ FAILED: Invalid Account/Not Found.',
            '4293700' => '❌ FAILED: Too Many Requests.',
            '5003701' => '❌ FAILED: Internal Server Error.',
        ];

        $displayMsg = $responseMapping[$resCode] ?? "[$resCode] $resMsg";

        // --- [DATABASE 2] UPDATE NAMA KE TABEL AFFILIATES ---
        if (in_array($resCode, ['2000000', '2003700'])) {
            $customerName = $result['additionalInfo']['customerName'] ?? 'Akun Valid';

            DB::table('affiliates')->where('id', $request->affiliate_id)->update([
                'dana_user_name' => $customerName,
                'updated_at' => now()
            ]);

            // --- [FONNTE 1] WA KE USER ---
            $pesanUser = "🛡️ *Sancaka DANA Center - Verifikasi*\n\n";
            $pesanUser .= "Halo *" . $aff->name . "*,\n";
            $pesanUser .= "Akun DANA Anda berhasil diverifikasi.\n\n";
            $pesanUser .= "▪️ Nama: *" . $customerName . "*\n";
            $pesanUser .= "▪️ No. DANA: " . $cleanPhone . "\n";
            $pesanUser .= "▪️ Status: ✅ *AKUN VALID*\n\n";
            $pesanUser .= "Terima kasih!";
            $this->sendWhatsApp($cleanPhone, $pesanUser);

            return back()->with('success', $displayMsg);
        }

        // --- [FONNTE 2] WA KE ADMIN (NOMOR BOS) UNTUK ERROR/TEST CASE ---
        $pesanAdmin = "📢 *DANA INQUIRY NOTIFICATION*\n\n";
        $pesanAdmin .= "▪️ Affiliate: " . $aff->name . "\n";
        $pesanAdmin .= "▪️ Target: " . $cleanPhone . "\n";
        $pesanAdmin .= "▪️ Nominal: Rp " . number_format($amountValue, 0, ',', '.') . "\n";
        $pesanAdmin .= "▪️ Result: " . $displayMsg . "\n";
        $pesanAdmin .= "▪️ Waktu: " . now()->format('H:i:s') . " WIB";
        $this->sendWhatsApp('6285745808809', $pesanAdmin);

        return back()->with('error', $displayMsg);

    } catch (\Exception $e) {
        // --- [LOG 6] EXCEPTION ERROR ---
        Log::error('[DANA INQUIRY] Exception!', ['message' => $e->getMessage()]);
        return back()->with('error', 'Sistem Error: ' . $e->getMessage());
    }
}

public function handleWebhook(Request $request)
{
    Log::info('========== DANA WEBHOOK INCOMING ==========', $request->all());

    $head = $request->input('request.head');
    $body = $request->input('request.body');

    if ($head['function'] === 'dana.acquiring.order.finishNotify') {
        $merchantTransId = $body['merchantTransId'];
        $status = $body['acquirementStatus']; // Contoh: CLOSED, FAILED, SUCCESS

        // Ambil data transaksi dari audit log kita
        $trx = DB::table('dana_transactions')->where('reference_no', $merchantTransId)->first();

        if ($trx) {
            // Update Status di Audit Log
            DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => $status]);

            // LOGIKA REFUND: Jika statusnya CLOSED (timeout) atau FAILED
            if (in_array($status, ['CLOSED', 'FAILED']) && $trx->status === 'SUCCESS') {
                DB::table('affiliates')->where('id', $trx->affiliate_id)->increment('balance', $trx->amount);

                // Tandai di audit log bahwa ini sudah direfund
                DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => 'REFUNDED']);
                Log::info('[WEBHOOK] Saldo Profit Berhasil Direfund!', ['affiliate' => $trx->affiliate_id]);
            }
        }
    }

    return response()->json(['response' => ['head' => ['resultCode' => 'SUCCESS']]]);
}

private function sendWhatsApp($to, $message)
{
    $token = "ynMyPswSKr14wdtXMJF7"; // Ganti dengan token Fonte bos

    // Pastikan nomor format 62...
    $to = preg_replace('/[^0-9]/', '', $to);
    if (substr($to, 0, 1) === '0') $to = '62' . substr($to, 1);

    Log::info('[FONTE] Mengirim pesan ke ' . $to);

    try {
        $response = Http::withHeaders([
            'Authorization' => $token
        ])->post('https://api.fonnte.com/send', [
            'target' => $to,
            'message' => $message,
            'countryCode' => '62', // optional
        ]);

        Log::info('[FONTE] Respon:', $response->json());
        return $response->json();
    } catch (\Exception $e) {
        Log::error('[FONTE] Error: ' . $e->getMessage());
        return false;
    }
}

public function customerTopup(Request $request)
{
    // --- [LOG 1] START PROCESS ---
    Log::info('[DANA TOPUP] --- MEMULAI PROSES TOPUP ---', [
        'affiliate_id' => $request->affiliate_id,
        'target_phone' => $request->phone,
        'amount' => $request->amount,
        'ip' => $request->ip()
    ]);

    // Ambil Data Affiliate
    $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
    if (!$aff) {
        Log::error('[DANA TOPUP] Affiliate Tidak Ditemukan', ['id' => $request->affiliate_id]);
        return back()->with('error', 'Affiliate tidak terdaftar di sistem.');
    }

    // --- [LOG 2] SANITASI NOMOR & NOMINAL ---
    $cleanPhone = preg_replace('/[^0-9]/', '', $request->phone);
    if (substr($cleanPhone, 0, 1) === '0') { $cleanPhone = '62' . substr($cleanPhone, 1); }

    $timestamp = now('Asia/Jakarta')->toIso8601String();
    $partnerRef = (string) time() . Str::random(8); // Sesuai partnerReferenceNo di dokumen
    $valStr = number_format((float)$request->amount, 2, '.', '');

    // --- [BODY: HARUS SESUAI DOKUMEN BOS] ---
    $body = [
    "partnerReferenceNo" => $partnerRef,
    "customerNumber"     => $cleanPhone,
    "amount" => [
        "value"    => $valStr,
        "currency" => "IDR"
    ],
    "feeAmount" => [
        "value"    => "0.00", // Di Sandbox real, fee biasanya 0.00
        "currency" => "IDR"
    ],
    "transactionDate" => $timestamp,
    "sessionId"       => (string) Str::uuid(),
    "categoryId"      => "6",
    "notes"           => "Topup Sancaka",
    "additionalInfo"  => [
        // PAKAI INI SAJA (Standar Merchant Disbursement)
        "accountType"  => "NAME_DEPOSIT",
        "fundType"     => "AGENT_TOPUP_FOR_USER_SETTLE",
        "chargeTarget" => "MERCHANT" // Ganti DIVISION ke MERCHANT
        // externalDivisionId dan customerId DIHAPUS karena pemicu PARAM_ILLEGAL
    ]
];

    // --- [LOG 4] SIGNATURE & SECURITY ---
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

        $response = Http::withHeaders($headers)->withBody($jsonBody, 'application/json')->post('https://api.sandbox.dana.id' . $path);
        $result = $response->json();

        $resCode = $result['responseCode'] ?? '5003801';

        // --- [SMART MATCHING] COCOKKAN DENGAN DATABASE dana_response_codes ---
        $library = DB::table('dana_response_codes')
                    ->where('response_code', $resCode)
                    ->where('category', 'TOPUP')
                    ->first();

        // Jika kode baru, catat otomatis
        if (!$library) {
            DB::table('dana_response_codes')->insert([
                'response_code' => $resCode,
                'category'      => 'TOPUP',
                'message_title' => 'New Code Detected',
                'description'   => $result['responseMessage'] ?? 'Unknown Error',
                'solution'      => 'Cek Dokumentasi DANA',
                'is_success'    => false,
                'created_at'    => now()
            ]);
            $library = DB::table('dana_response_codes')->where('response_code', $resCode)->where('category', 'TOPUP')->first();
        }

        // --- [DATABASE] CATAT KE dana_transactions ---
        DB::table('dana_transactions')->insert([
            'affiliate_id' => $aff->id,
            'type' => 'TOPUP',
            'reference_no' => $partnerRef,
            'phone' => $cleanPhone,
            'amount' => $request->amount,
            'status' => $library->is_success ? 'SUCCESS' : 'FAILED',
            'response_payload' => json_encode($result),
            'created_at' => now()
        ]);

        // --- [FONNTE] SETUP WHATSAPP ---
        $waToken = "ynMyPswSKr14wdtXMJF7";
        $adminWA = "6285745808809";

        if ($library->is_success) {
            // Update balance jika sukses (Opsional)
            DB::table('affiliates')->where('id', $aff->id)->decrement('balance', $request->amount);

            // WA ke User
            $msgUser = "✅ *TOPUP BERHASIL*\n\nHalo *{$aff->name}*,\nTopup DANA ke {$cleanPhone} senilai Rp " . number_format($request->amount) . " berhasil.\n\nRef ID: {$partnerRef}\nWaktu: " . now()->format('d/m H:i') . " WIB\nTerima kasih!";
            $this->sendWhatsApp($cleanPhone, $msgUser, $waToken);

            Log::info('[DANA TOPUP] Berhasil', ['code' => $resCode]);
            return back()->with('dana_report', $library)->with('success', 'Topup Berhasil Diuraikan!');
        } else {
            // WA ke Admin (Error/Test Case)
            $msgAdmin = "⚠️ *DANA TOPUP ALERT*\n\nAffiliate: {$aff->name}\nTarget: {$cleanPhone}\nNominal: Rp " . number_format($request->amount) . "\nResponse: [{$resCode}] {$library->message_title}\nDesc: {$library->description}\n\nMohon dicek segera!";
            $this->sendWhatsApp($adminWA, $msgAdmin, $waToken);

            Log::error('[DANA TOPUP] Gagal/Error Response', ['result' => $result]);
            return back()->with('dana_report', $library)->with('error', 'Gagal: ' . $library->message_title);
        }

    } catch (\Exception $e) {
        Log::error('[DANA TOPUP] EXCEPTION ERROR', ['msg' => $e->getMessage()]);

        // WA Error ke Admin
        $this->sendWhatsApp("6285745808809", "🚨 *SYSTEM ERROR TOPUP*\nMsg: " . $e->getMessage(), "ynMyPswSKr14wdtXMJF7");

        return back()->with('error', 'Sistem Error: ' . $e->getMessage());
    }
}


}
