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


class MemberAuthController extends Controller
{
    /**
     * 1. Tampilkan Halaman Form Login
     */
    public function showLoginForm()
    {
        // Cek jika sudah login, langsung lempar ke dashboard
        if (Auth::guard('member')->check()) {
            return redirect()->route('member.dashboard');
        }

        return view('member.auth.login');
    }

    /**
     * 2. Proses Eksekusi Login
     */
    public function login(Request $request)
    {
        // Validasi Input
        $request->validate([
            'whatsapp' => 'required|numeric',
            'pin'      => 'required|string',
        ]);

        // Siapkan Credentials
        // PENTING: Key 'password' di sini wajib ada karena Auth Laravel
        // akan menggunakan value ini untuk dicocokkan dengan hash di database.
        // Walaupun kolom database namanya 'pin', input user tetap kita labeli 'password' di array ini.
        $credentials = [
            'whatsapp' => $request->whatsapp,
            'password' => $request->pin,
            'is_active' => 1 // Opsional: Pastikan hanya member aktif yg bisa masuk
        ];

        // Coba Login menggunakan Guard 'member'
        // $request->filled('remember') mengecek checkbox "Ingat Saya"
        if (Auth::guard('member')->attempt($credentials, $request->filled('remember'))) {

            // Regenerasi Session ID untuk keamanan (Fixation Attack)
            $request->session()->regenerate();

            // Redirect ke dashboard member
            return redirect()->intended(route('member.dashboard'));
        }

        // Jika Gagal Login (Balik ke halaman login dengan error)
        return back()->withErrors([
            'whatsapp' => 'Nomor WhatsApp atau PIN salah, atau akun dinonaktifkan.',
        ])->withInput($request->only('whatsapp'));
    }

    /**
     * 3. Halaman Dashboard Member
     */
    public function dashboard(Request $request)
{
    $member = Auth::guard('member')->user();

    // 1. Ambil Riwayat Pesanan (Tetap)
    $orders = Order::where('customer_phone', $member->whatsapp)
                   ->orderBy('created_at', 'desc')
                   ->take(10)
                   ->get();

    // 2. Query Riwayat Transaksi dengan Filter
    $query = DB::table('dana_transactions')->where('affiliate_id', $member->id);

    if ($request->filled('type')) {
        $query->where('type', $request->type);
    }

    if ($request->filled('start_date') && $request->filled('end_date')) {
        $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
    }

    $transactions = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

    return view('member.dashboard', compact('member', 'orders', 'transactions'));
}

    /**
     * 4. Proses Logout
     */
    public function logout(Request $request)
    {
        // Logout hanya dari guard 'member'
        Auth::guard('member')->logout();

        // Invalidate session
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirect ke halaman login member
        return redirect()->route('member.login');
    }

   // 1. START BINDING (LOGIKA AWAL BOS)
    public function startBinding(Request $request)
    {
        // [LOG 1] Tanda mulai proses
        Log::info('================================================================');
        Log::info('[BINDING] 🚀 1. MEMULAI PROSES REDIRECT KE DANA PORTAL');
        Log::info('================================================================');

        // Log info dasar user
        Log::info('[BINDING] Client IP: ' . $request->ip());
        Log::info('[BINDING] User Agent: ' . $request->header('User-Agent'));

        // ---------------------------------------------------------------------
        // 1. AMBIL DATA DINAMIS (MEMBER, TENANT, SUBDOMAIN)
        // ---------------------------------------------------------------------

        // Cek Auth Guard Member
        $member = Auth::guard('member')->user();

        // Fallback jika testing tanpa login (Not Recommended for Production)
        $memberId = $member ? $member->id : ($request->affiliate_id ?? 11);
        $tenantId = $member ? $member->tenant_id : ($request->tenant_id ?? 1);
        $memberName = $member ? $member->name : 'Guest/Test';

        // Deteksi Subdomain dari Route Parameter (sesuai setup routing Anda)
        // Jika null/kosong, default ke 'apps' (pusat)
        $currentSubdomain = $request->route('subdomain') ?? 'apps';

        Log::info("[BINDING] 👤 User Info:", [
            'name' => $memberName,
            'id' => $memberId,
            'tenant_id' => $tenantId,
            'origin_subdomain' => $currentSubdomain
        ]);

        // ---------------------------------------------------------------------
        // 2. FORMAT STATE (PENTING UNTUK CALLBACK)
        // Format: TIPE - ID_MEMBER - SUBDOMAIN - TENANT_ID
        // Contoh: MEMBER-11-apps-1
        // ---------------------------------------------------------------------
        $state = "MEMBER-{$memberId}-{$currentSubdomain}-{$tenantId}";

        // [LOG 2] Pengecekan Config
        $partnerId   = config('services.dana.x_partner_id');
        $merchantId  = config('services.dana.merchant_id');

        // Kita kunci Redirect URL ke Pusat (Apps) agar tidak perlu whitelist banyak subdomain di DANA
        $redirectUrl = 'https://apps.tokosancaka.com/dana/callback';

        Log::info('[BINDING] ⚙️ Config & State:', [
            'partnerId' => $partnerId ? '✅ OK' : '❌ NULL',
            'merchantId' => $merchantId ? '✅ OK' : '❌ NULL',
            'redirectUrl' => $redirectUrl,
            'generated_state' => $state
        ]);

        // Generate External ID Unik
        $timestamp  = now('Asia/Jakarta')->toIso8601String();
        $externalId = 'BIND-' . $state . '-' . time();

        // ---------------------------------------------------------------------
        // 3. SUSUN PARAMETER
        // ---------------------------------------------------------------------
        $queryParams = [
            'partnerId'   => $partnerId,
            'timestamp'   => $timestamp,
            'externalId'  => $externalId,
            'merchantId'  => $merchantId,
            'redirectUrl' => $redirectUrl,
            'state'       => $state, // <--- Membawa semua info penting
            'scopes'      => 'QUERY_BALANCE,MINI_DANA,DEFAULT_BASIC_PROFILE',
        ];

        // [LOG 3] Log Payload
        Log::info('[BINDING] 📦 Payload Parameter:', $queryParams);

        // Build URL
        $baseUrl = "https://m.sandbox.dana.id/d/portal/oauth";
        $finalRedirectUrl = $baseUrl . "?" . http_build_query($queryParams);

        // [LOG 4] Log URL Final
        Log::info('[BINDING] 🔗 GENERATED URL (Siap Redirect):');
        Log::info($finalRedirectUrl);

        Log::info('[BINDING] ✅ Proses controller selesai, melempar user ke DANA...');
        Log::info('================================================================');

        return redirect($finalRedirectUrl);
    }

   public function handleCallback(Request $request)
{
    Log::info('[DANA CALLBACK] Mendapatkan Auth Code:', $request->all());

    $authCode = $request->input('auth_code');
    $state = $request->input('state');
    // Ambil ID Affiliate dari state, default ke 11 jika tidak ada
    $affiliateId = $state ? str_replace('ID-', '', $state) : 11;

    if (!$authCode) {
        return redirect()->route('member.dashboard')->with('error', 'Auth Code Kosong');
    }

    // 1. Simpan Auth Code-nya dulu ke database sebagai jejak awal
    DB::table('affiliates')->where('id', $affiliateId)->update([
        'dana_auth_code' => $authCode,
        'updated_at' => now()
    ]);

    try {
        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $clientId = config('services.dana.x_partner_id');
        $externalId = (string) time();

        // Signature B2B2C: ClientID|Timestamp
        $stringToSign = $clientId . "|" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        $path = '/v1.0/access-token/b2b2c.htm';
        $body = [
            'grantType' => 'authorization_code',
            'authCode' => $authCode,
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
        // Kode sukses DANA Sandbox seringkali 2007400 untuk B2B2C
        $successCodes = ['2001100', '2007400'];

        if (isset($result['responseCode']) && in_array($result['responseCode'], $successCodes)) {
            // A. UPDATE TOKEN KE DATABASE (Prioritas Utama)
            DB::table('affiliates')->where('id', $affiliateId)->update([
                'dana_access_token' => $result['accessToken'],
                'updated_at' => now()
            ]);

            // B. CATAT KE RIWAYAT TRANSAKSI (Gunakan try-catch agar tidak crash jika DB error)
            try {
                DB::table('dana_transactions')->insert([
                    'tenant_id' => $tenantId, // Gunakan variabel ini (hasil pecahan state)                    'affiliate_id' => $affiliateId,
                    'type' => 'BINDING',
                    'reference_no' => $externalId,
                    'phone' => '-',
                    'amount' => 0,
                    'status' => 'SUCCESS',
                    'response_payload' => json_encode($result),
                    'created_at' => now()
                ]);
            } catch (\Exception $dbEx) {
                Log::error('[DANA CALLBACK] Gagal simpan log transaksi: ' . $dbEx->getMessage());
            }

            return redirect()->route('member.dashboard')->with('success', '✅ Akun Berhasil Terhubung!');
        }

        // JIKA GAGAL TUKAR TOKEN
        try {
            DB::table('dana_transactions')->insert([
                'affiliate_id' => $affiliateId,
                'type' => 'BINDING',
                'reference_no' => $externalId,
                'phone' => '-',
                'amount' => 0,
                'status' => 'FAILED',
                'response_payload' => json_encode($result),
                'created_at' => now()
            ]);
        } catch (\Exception $dbEx) {
            Log::error('[DANA CALLBACK] Gagal simpan log error: ' . $dbEx->getMessage());
        }

        Log::error('[EXCHANGE FAILED]', $result);
        return redirect()->route('member.dashboard')->with('error', 'Gagal Tukar Token: ' . ($result['responseMessage'] ?? 'Unknown Error'));

    } catch (\Exception $e) {
        Log::error('[DANA CALLBACK] System Error:', ['msg' => $e->getMessage()]);
        return redirect()->route('member.dashboard')->with('error', 'Sistem Error: ' . $e->getMessage());
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
    // 1. Hapus karakter selain angka (spasi, strip, +, dll hilang)
    $cleanPhone = preg_replace('/[^0-9]/', '', $request->phone);

    // 2. Normalisasi awalan agar selalu 62
    if (substr($cleanPhone, 0, 2) === '62') {
        // Jika input sudah 628xxxxx -> Biarkan, sudah benar
    } elseif (substr($cleanPhone, 0, 1) === '0') {
        // Jika input 08xxxxx -> Hapus '0' depan, ganti '62'
        $cleanPhone = '62' . substr($cleanPhone, 1);
    } elseif (substr($cleanPhone, 0, 1) === '8') {
        // Jika input 8xxxxx -> Tambahkan '62' di depan
        $cleanPhone = '62' . $cleanPhone;
    }

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
            "fundType"           => "AGENT_TOPUP_FOR_USER_SETTLE",
            "externalDivisionId" => "",
            "chargeTarget"       => "MERCHANT",
            "customerId"         => ""
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

        // --- [SMART MATCHING] ---
        $library = DB::table('dana_response_codes')
                    ->where('response_code', $resCode)
                    ->where('category', 'TOPUP')
                    ->first();

        // Jika kode baru, catat otomatis
        if (!$library) {
            // [FIX LOGIC] Tentukan status sukses berdasarkan kode yang dikenal
            // 2000000 = Sukses Umum, 2003800 = Sukses Topup
            $isSuccessCode = in_array($resCode, ['2000000', '2003800']);

            DB::table('dana_response_codes')->insert([
                'response_code' => $resCode,
                'category'      => 'TOPUP',
                'message_title' => $isSuccessCode ? 'Transaction Success' : 'New Code Detected',
                'description'   => $result['responseMessage'] ?? 'Auto Generated',
                'solution'      => 'Cek Dokumentasi DANA',
                'is_success'    => $isSuccessCode, // <--- GUNAKAN VARIABEL INI
                'created_at'    => now()
            ]);

            // Refresh data library
            $library = DB::table('dana_response_codes')
                        ->where('response_code', $resCode)
                        ->where('category', 'TOPUP')
                        ->first();
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

public function checkTopupStatus(Request $request)
{
    // --- [LOG 1] START INQUIRY ---
    Log::info('[DANA INQUIRY STATUS] Memulai pengecekan status...', [
        'partnerReferenceNo' => $request->reference_no,
        'affiliate_id' => $request->affiliate_id
    ]);

    $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
    $trx = DB::table('dana_transactions')->where('reference_no', $request->reference_no)->first();

    if (!$trx) return back()->with('error', 'Data transaksi tidak ditemukan di database.');

    $timestamp = now('Asia/Jakarta')->toIso8601String();
    $path = '/v1.0/emoney/topup-status.htm';

    // --- [BODY] SESUAI DOKUMENTASI ---
    $body = [
        "originalPartnerReferenceNo" => $trx->reference_no, // Required
        "originalReferenceNo"        => "", // Opsional, bisa kosong jika belum ada
        "originalExternalId"         => "", // Opsional
        "serviceCode"                => "38", // Wajib "38" untuk Topup
        "additionalInfo"             => (object)[]
    ];

    $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
    $hashedBody = strtolower(hash('sha256', $jsonBody));
    $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
    $signature = $this->generateSignature($stringToSign);

    $headers = [
        'Content-Type'   => 'application/json', // Required
        'Authorization'  => 'Bearer ' . $aff->dana_access_token,
        'X-TIMESTAMP'    => $timestamp, // Required
        'X-SIGNATURE'    => $signature, // Required
        'X-PARTNER-ID'   => config('services.dana.x_partner_id'), // Required
        'X-EXTERNAL-ID'  => (string) time() . Str::random(6), // Required
        'CHANNEL-ID'     => '95221' // Required
    ];

    try {
        // --- [LOG 2] SENDING REQUEST ---
        Log::info('[DANA INQUIRY STATUS] Mengirim Request Status ke DANA', ['body' => $body]);

        $response = Http::withHeaders($headers)->withBody($jsonBody, 'application/json')->post('https://api.sandbox.dana.id' . $path);
        $result = $response->json();
        $resCode = $result['responseCode'] ?? ''; // <--- TAMBAHKAN BARIS INI


        // --- [LOG 3] RESPONSE RECEIVED ---
        Log::info('[DANA INQUIRY STATUS] Respon Diterima', ['result' => $result]);

        if (isset($result['responseCode']) && $result['responseCode'] == '2003900') {
            $status = $result['latestTransactionStatus']; // 00, 01, dll

            // --- [LOG 4] MAPPING STATUS ---
            if ($status == '00') {
                // SUCCESS: Mark as Success
                DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => 'SUCCESS']);
                return back()->with('success', '✅ Transaksi BERHASIL (Confirmed by DANA)');
            } elseif (in_array($status, ['01', '02', '03'])) {
                // PENDING: Hold money & retry
                return back()->with('error', '⏳ Transaksi masih PENDING di sistem DANA.');
            } else {
                // FAILED/CANCELLED: Mark as Failed
                DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => 'FAILED']);
                return back()->with('error', '❌ Transaksi GAGAL: ' . ($result['transactionStatusDesc'] ?? 'Failed'));
            }
        }

        // TAMBAHKAN LOGIKA INI BOS:
    elseif ($resCode == '4043901') {
        // Jika DANA bilang tidak ketemu, tandai Gagal Permanen
        DB::table('dana_transactions')->where('id', $trx->id)->update([
            'status' => 'FAILED',
            'retry_count' => 5 // Hentikan Auto-Retry
        ]);
        return back()->with('error', '❌ Transaksi Tidak Ditemukan di DANA (Silakan coba Topup ulang).');
    }

        return back()->with('error', 'Gagal cek status: ' . ($result['responseMessage'] ?? 'Unknown Error'));

    } catch (\Exception $e) {
        Log::error('[DANA INQUIRY STATUS] System Error', ['msg' => $e->getMessage()]);
        return back()->with('error', 'Sistem Error: ' . $e->getMessage());
    }
}

// -------------------------------------------------------------------------
    // BANK ACCOUNT INQUIRY (CEK REKENING BANK) - PERBAIKAN UTAMA
    // -------------------------------------------------------------------------

    public function bankAccountInquiry(Request $request)
    {
        // 1. Ambil Data Affiliate (Dinamis dari input form)
        $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
        if (!$aff) return back()->with('error', 'Affiliate tidak ditemukan.');

        // 2. Sanitasi Nomor Customer (DANA mengharuskan format 62...)
        // Ini adalah nomor "pengirim" (si affiliate), bukan nomor rekening tujuan
        $customerNumber = preg_replace('/[^0-9]/', '', $aff->whatsapp);
        if (substr($customerNumber, 0, 1) === '0') $customerNumber = '62' . substr($customerNumber, 1);

        // 3. Setup Request
        $timestamp = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $path = '/v1.0/emoney/bank-account-inquiry.htm';
        $refNo = "BNK" . time() . Str::random(4);

        $body = [
            "partnerReferenceNo" => $refNo,
            "customerNumber"     => $customerNumber,
            "beneficiaryAccountNumber" => $request->account_no, // Rekening Tujuan
            "amount" => [
                "value"    => number_format((float)$request->amount, 2, '.', ''),
                "currency" => "IDR"
            ],
            "additionalInfo" => [
                "fundType"            => "MERCHANT_WITHDRAW_FOR_CORPORATE",
                "beneficiaryBankCode" => $request->bank_code, // 014 (BCA), 114 (Jatim), dll
                "beneficiaryAccountName" => "" // Biarkan kosong agar diisi response DANA
            ]
        ];

        // 4. Signature
        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        // 5. Kirim Request
        try {
            $response = Http::withHeaders([
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
            ])->withBody($jsonBody, 'application/json')->post('https://api.sandbox.dana.id' . $path);

            $result = $response->json();
            Log::info('[BANK INQUIRY]', ['res' => $result]);

            $resCode = $result['responseCode'] ?? '500';

            // Catat Log ke DB (Audit Trail)
            DB::table('dana_transactions')->insert([
                'affiliate_id' => $aff->id,
                'type' => 'BANK_INQUIRY',
                'reference_no' => $refNo,
                'phone' => $request->account_no . " (" . $request->bank_code . ")",
                'amount' => $request->amount,
                'status' => ($resCode == '2004200') ? 'SUCCESS' : 'FAILED',
                'response_payload' => json_encode($result),
                'created_at' => now()
            ]);

            // RESPON SUKSES (2004200)
            if ($resCode == '2004200') {
                $bankName = $result['beneficiaryBankShortName'] ?? $result['beneficiaryBankName'];
                $accName  = $result['beneficiaryAccountName'];
                $accNo    = $result['beneficiaryAccountNumber'];

                $msg = "✅ Rekening Ditemukan!<br>";
                $msg .= "Bank: <b>$bankName</b><br>";
                $msg .= "Nama: <b>$accName</b><br>";
                $msg .= "No: <b>$accNo</b>";

                // Gunakan 'dana_report' agar tampil cantik di dashboard (sesuai blade Anda)
                $report = (object) [
                    'is_success' => true,
                    'message_title' => 'Bank Account Valid',
                    'description' => "Rekening $bankName atas nama $accName valid."
                ];

                return back()->with('success', "Rekening Valid: $accName ($bankName)")
                             ->with('dana_report', $report)
                             ->with('valid_account_name', $accName) // <--- TAMBAHKAN INI (Kirim Nama Asli)
                             ->withInput(); // <--- WAJIB ADA INI
            }

            // RESPON GAGAL
            $errMsg = $result['responseMessage'] ?? 'Unknown Error';

            // Handle Error Spesifik
            if ($resCode == '4034218') $errMsg = "Akun Merchant Inactive (Hubungi Admin DANA)";
            if ($resCode == '4044201') $errMsg = "Rekening Tidak Ditemukan/Salah Bank";

            $report = (object) [
                'is_success' => false,
                'message_title' => "Gagal Cek Rekening ($resCode)",
                'description' => $errMsg
            ];

            return back()->with('error', $errMsg)->with('dana_report', $report);

        } catch (\Exception $e) {
            Log::error('[BANK INQUIRY ERROR]', ['msg' => $e->getMessage()]);
            return back()->with('error', 'Sistem Error saat cek rekening.');
        }
    }

    public function transferToBank(Request $request)
    {
        // --- [LOG 1] MULAI PROSES ---
        Log::info('[DANA TRANSFER BANK] Start', [
            'affiliate_id' => $request->affiliate_id,
            'bank_code' => $request->bank_code,
            'account_no' => $request->account_no,
            'amount' => $request->amount
        ]);

        // 1. Validasi Affiliate
        $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();

        if (!$aff) return back()->with('error', 'Affiliate tidak ditemukan.');

        // 2. Cek Kecukupan Saldo
        if ($aff->balance < $request->amount) {
            return back()->with('error', 'Saldo komisi Anda tidak mencukupi.');
        }

        // 3. Sanitasi Nomor Customer (Pengirim/Merchant)
        $customerNumber = preg_replace('/[^0-9]/', '', $aff->whatsapp);
        if (substr($customerNumber, 0, 1) === '0') $customerNumber = '62' . substr($customerNumber, 1);

        // 4. POTONG SALDO DULUAN (SAFETY FIRST)
        // Mencegah double transfer. Jika gagal nanti kita refund.
        DB::table('affiliates')->where('id', $aff->id)->decrement('balance', $request->amount);

        // 5. Setup Request DANA
        $timestamp = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $path = '/v1.0/emoney/transfer-bank.htm';
        $partnerRef = "TRF" . time() . Str::random(6); // Unique ID

        $body = [
            "partnerReferenceNo" => $partnerRef,
            "customerNumber"     => $customerNumber,
            // "accountType"        => "SETTLEMENT_ACCOUNT",
            "beneficiaryAccountNumber" => $request->account_no,
            "beneficiaryBankCode"      => $request->bank_code,
            "amount" => [
                "value"    => number_format((float)$request->amount, 2, '.', ''),
                "currency" => "IDR"
            ],
            "additionalInfo" => [
                "fundType"     => "MERCHANT_WITHDRAW_FOR_CORPORATE",
                // "chargeTarget" => "MERCHANT",
                // PENTING: Gunakan nama asli dari hasil Inquiry sebelumnya (Input Hidden)
                "beneficiaryAccountName" => $request->account_name
            ]
        ];

        // 6. Generate Signature
        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        // 7. Kirim Request
        try {
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

            Log::info('[DANA TRANSFER BANK] Mengirim Request...', ['body' => $body]);

            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post('https://api.sandbox.dana.id' . $path);

            $result = $response->json();
            $resCode = $result['responseCode'] ?? '500';

            // --- [LOGIC HANDLING RESPONSE] ---

            // KONDISI A: SUKSES (2004300)
            if ($resCode == '2004300') {
                // Catat Log Transaksi Sukses
                DB::table('dana_transactions')->insert([
                    'affiliate_id' => $aff->id,
                    'type' => 'TRANSFER_BANK',
                    'reference_no' => $partnerRef,
                    'phone' => $request->account_no . " (" . $request->bank_code . ")",
                    'amount' => $request->amount,
                    'status' => 'SUCCESS',
                    'response_payload' => json_encode($result),
                    'created_at' => now()
                ]);

                // Pesan Sukses dengan \n (Baris Baru)
                $msg = "Transfer Berhasil!\nRef: $partnerRef\nNominal: Rp " . number_format($request->amount);

                return back()->with('success', $msg);
            }

            // KONDISI B: PENDING (2024300, 4294300, 5004301)
            // Uang tetap ditahan (tidak direfund), user diminta cek berkala
            elseif (in_array($resCode, ['2024300', '4294300', '5004301'])) {
                DB::table('dana_transactions')->insert([
                    'affiliate_id' => $aff->id,
                    'type' => 'TRANSFER_BANK',
                    'reference_no' => $partnerRef,
                    'phone' => $request->account_no,
                    'amount' => $request->amount,
                    'status' => 'PENDING',
                    'response_payload' => json_encode($result),
                    'created_at' => now()
                ]);

                return back()->with('warning', "⏳ Transaksi Sedang Diproses (Pending).\nMohon cek status secara berkala.");
            }

            // KONDISI C: GAGAL (KEMBALIKAN SALDO / REFUND)
            else {
                // REFUND SALDO
                DB::table('affiliates')->where('id', $aff->id)->increment('balance', $request->amount);

                // Catat Log Gagal
                DB::table('dana_transactions')->insert([
                    'affiliate_id' => $aff->id,
                    'type' => 'TRANSFER_BANK',
                    'reference_no' => $partnerRef,
                    'phone' => $request->account_no,
                    'amount' => $request->amount,
                    'status' => 'FAILED',
                    'response_payload' => json_encode($result),
                    'created_at' => now()
                ]);

                // Mapping Pesan Error
                $errorMsg = $result['responseMessage'] ?? 'Transaksi Gagal';
                if ($resCode == '4034314') $errorMsg = "Saldo Merchant DANA Tidak Cukup.";
                if ($resCode == '4044311') $errorMsg = "Rekening Salah atau Tidak Valid.";
                if ($resCode == '4034318') $errorMsg = "Akun Merchant Tidak Aktif/Salah Konfigurasi.";

                Log::error('[DANA TRANSFER BANK] Gagal & Refund', ['res' => $result]);
                return back()->with('error', "Gagal: $errorMsg\n(Saldo telah dikembalikan).");
            }

        } catch (\Exception $e) {
            // SYSTEM ERROR -> REFUND JUGA
            DB::table('affiliates')->where('id', $aff->id)->increment('balance', $request->amount);

            Log::error('[DANA TRANSFER BANK] Exception', ['msg' => $e->getMessage()]);
            return back()->with('error', 'System Error: ' . $e->getMessage());
        }
    }


}
