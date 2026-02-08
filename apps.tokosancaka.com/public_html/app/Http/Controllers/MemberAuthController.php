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
// use App\Models\Order;
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

// TAMBAHKAN INI UNTUK SDK DANA
use Dana\Configuration;
use Dana\Env;
use Dana\Widget\v1\Api\WidgetApi;
use Dana\Widget\v1\Model\WidgetPaymentRequest;
use Dana\Widget\v1\Model\Money;
use Dana\Widget\v1\Model\WidgetPaymentRequestAdditionalInfo;
// use Dana\Widget\v1\Model\WidgetPaymentRequestAdditionalInfoOrder;
use Dana\Widget\v1\Model\UrlParam;
// Class Penting dari Snippet Bapak:
// use Dana\Widget\v1\Model\WidgetPaymentRequestAdditionalInfo;
use Dana\Widget\v1\Model\EnvInfo;
use Dana\Widget\v1\Model\Order;

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
    //if (!$aff || $aff->balance < $request->amount) {
    //    Log::warning('[DANA TOPUP] Saldo Tidak Cukup atau Affiliate Tidak Ditemukan');
    //    return back()->with('error', 'Gagal: Saldo profit tidak mencukupi.');
    //}

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

    Log::info('[DEBUG RAW BODY]', ['body' => $response->body()]); // <--- TAMBAHKAN INI

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

        // --- MULAI KODE PERBAIKAN ---
        $result = $response->json();

        // [DEBUG] Cek Body Asli untuk memastikan kenapa null
        Log::info('[DANA TOPUP] Raw Body', ['body' => $response->body()]);
        Log::info('[DANA TOPUP] Respon Diterima', ['status' => $response->status(), 'result' => $result]);

        // LOGIKA BARU: Cek Status 200 DAN pastikan ada responseCode Sukses dari DANA
        $isSuccess = false;
        if ($response->successful() && isset($result['responseCode'])) {
            // 2000000 = Sukses Umum, 2003800 = Sukses Topup
            if (in_array($result['responseCode'], ['2000000', '2003800'])) {
                $isSuccess = true;
            }
        }

        // JIKA BENAR-BENAR SUKSES
        if ($isSuccess) {

            // A. Potong Saldo Profit Affiliate
            DB::table('affiliates')->where('id', $aff->id)->decrement('balance', $request->amount);

            // B. Catat ke Audit Log Transaksi
            DB::table('dana_transactions')->insert([
                'tenant_id'    => $aff->tenant_id,
                'affiliate_id' => $aff->id,
                'type' => 'TOPUP',
                'reference_no' => $partnerRef,
                'phone' => $cleanPhone,
                'amount' => $request->amount,
                'status' => 'SUCCESS',
                'response_payload' => json_encode($result),
                'created_at' => now()
            ]);

            // C. Kirim Notifikasi WhatsApp ke USER
            $pesanUser = "✅ *PENCAIRAN PROFIT BERHASIL*\n\nHalo " . $aff->name . ",\nPencairan profit Anda ke DANA telah sukses.\n\n*Detail:* \n▪️ Nominal: Rp " . number_format($request->amount, 0, ',', '.') . "\n▪️ No. DANA: " . $cleanPhone . "\n▪️ Ref ID: " . $partnerRef . "\n▪️ Waktu: " . now()->format('d/m H:i') . " WIB\n\nSaldo profit Anda telah otomatis terpotong. Terima kasih!";
            $this->sendWhatsApp($cleanPhone, $pesanUser);

            // D. Kirim Notifikasi ke ADMIN
            $pesanAdmin = "📢 *LAPORAN TOPUP SUKSES*\n\nAffiliate: " . $aff->name . " (ID: " . $aff->id . ")\nNominal: Rp " . number_format($request->amount, 0, ',', '.') . "\nTujuan: " . $cleanPhone . "\nStatus: Saldo Berhasil Dipotong.";
            $this->sendWhatsApp('6285745808809', $pesanAdmin);

            Log::info('[DANA TOPUP] BERHASIL & WA TERKIRIM');
            return back()->with('success', '💸 Topup Berhasil, Saldo Dipotong, dan WA Terkirim!');

        } else {
            // JIKA GAGAL / RESPON NULL
            $errCode = $result['responseCode'] ?? 'NULL/EMPTY';
            $errMsg  = $result['responseMessage'] ?? 'Tidak ada data respon dari DANA (Sandbox Error)';

            Log::error('[DANA TOPUP] Gagal/Null Response', ['res' => $result]);

            // Catat Gagal di DB (Opsional)
             DB::table('dana_transactions')->insert([
                'tenant_id'    => $aff->tenant_id,
                'affiliate_id' => $aff->id,
                'type' => 'TOPUP',
                'reference_no' => $partnerRef,
                'phone' => $cleanPhone,
                'amount' => $request->amount,
                'status' => 'FAILED',
                'response_payload' => json_encode($result), // Bisa jadi null
                'created_at' => now()
            ]);

            return back()->with('error', "Gagal dari DANA: $errMsg ($errCode)");
        }
        // --- SELESAI KODE PERBAIKAN ---

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
        'tenant_id'    => $aff->tenant_id, // <--- TAMBAHKAN INI
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
            'tenant_id'    => $aff->tenant_id, // <--- TAMBAHKAN INI
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
    // --- [0] VALIDASI INPUT ---
    $request->validate([
        'affiliate_id' => 'required|exists:affiliates,id',
        'phone'        => 'required|numeric',
        'amount'       => 'required|numeric|min:1000', // Minimal topup wajar
    ]);

    // Konfigurasi Statis (Sebaiknya pindahkan ke .env nanti)
    $waToken = config('services.fonnte.token', 'ynMyPswSKr14wdtXMJF7'); // Gunakan config atau default
    $adminWA = config('services.admin_wa', '6285745808809');

    // --- [LOG 1] START PROCESS ---
    // Ambil Data Affiliate dulu untuk logging yang akurat
    $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();

    if (!$aff) {
        return back()->with('error', 'Affiliate tidak ditemukan.');
    }

    Log::info('[DANA TOPUP] --- MEMULAI PROSES TOPUP ---', [
        'tenant_id'    => $aff->tenant_id ?? null,
        'affiliate_id' => $aff->id,
        'aff_name'     => $aff->name,
        'target_phone' => $request->phone,
        'amount'       => $request->amount,
        'current_bal'  => $aff->balance, // Log saldo awal
        'ip'           => $request->ip()
    ]);

    // --- [CRITICAL] CEK SALDO AFFILIATE ---
    if ($aff->balance < $request->amount) {
        Log::warning('[DANA TOPUP] Saldo Tidak Cukup', ['aff_id' => $aff->id, 'balance' => $aff->balance, 'req' => $request->amount]);
        return back()->with('error', 'Saldo tidak mencukupi untuk melakukan transaksi ini.');
    }

    try {
        // --- [LOG 2] SANITASI NOMOR ---
        $cleanPhone = preg_replace('/[^0-9]/', '', $request->phone);
        if (substr($cleanPhone, 0, 2) === '62') {
            // Do nothing
        } elseif (substr($cleanPhone, 0, 1) === '0') {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        } elseif (substr($cleanPhone, 0, 1) === '8') {
            $cleanPhone = '62' . $cleanPhone;
        }

        // --- SETUP VARIABEL TIME & REF ---
        $timestamp  = now('Asia/Jakarta')->toIso8601String();
        // Gunakan UUID atau kombinasi unik agar tidak duplicate transaction
        $partnerRef = date('YmdHis') . mt_rand(1000, 9999);
        $valStr     = number_format((float)$request->amount, 2, '.', '');

        // --- [BODY REQUEST] ---
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
            "categoryId"      => "6", // Kategori Pulsa/Data/E-money
            "notes"           => "Topup Sancaka System",
            "additionalInfo"  => [
                "fundType"           => "AGENT_TOPUP_FOR_USER_SETTLE",
                "externalDivisionId" => "SANCAKA_DIV", // Boleh diisi identifier divisi
                "chargeTarget"       => "MERCHANT",
                "customerId"         => ""
            ]
        ];

        // --- [LOG 3] SIGNATURE & SECURITY ---
        $path = '/v1.0/emoney/topup.htm';

        // Encode JSON persis seperti yang akan dikirim (UNESCAPED_SLASHES penting untuk URL)
        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);

        // Hashing body (SHA256)
        $hashedBody = strtolower(hash('sha256', $jsonBody));

        // String to Sign format DANA
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;

        // Generate Signature (Pastikan fungsi ini menggunakan Private Key yang benar)
        $signature = $this->generateSignature($stringToSign);

        $headers = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . ($aff->dana_access_token ?? config('services.dana.default_token')), // Fallback jika aff tidak punya token khusus
            'X-TIMESTAMP'   => $timestamp,
            'X-SIGNATURE'   => $signature,
            'ORIGIN'        => config('services.dana.origin', 'https://sancaka.com'),
            'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
            'X-EXTERNAL-ID' => (string) time() . Str::random(6),
            'X-IP-ADDRESS'  => $request->ip(),
            'X-DEVICE-ID'   => 'SANCAKA-POS-01', // Sebaiknya unik per device jika ada
            'CHANNEL-ID'    => '95221'
        ];

        Log::info('[DANA TOPUP] Mengirim Request...', ['ref' => $partnerRef, 'amount' => $valStr]);

        // --- [EXECUTE API] ---
        // Gunakan URL dari Config/Env agar mudah switch Sandbox/Prod
        $baseUrl = config('services.dana.base_url', 'https://api.sandbox.dana.id');
        $response = Http::withHeaders($headers)
                        ->withBody($jsonBody, 'application/json')
                        ->post($baseUrl . $path);

        $result = $response->json();
        $resCode = $result['responseCode'] ?? '5003801'; // Default Error jika response kosong

        // --- [SMART MATCHING RESPONSE] ---
        $library = DB::table('dana_response_codes')
            ->where('response_code', $resCode)
            ->where('category', 'TOPUP')
            ->first();

        // Auto-Insert Library jika kode baru
        if (!$library) {
            // List kode sukses DANA (Cek dokumentasi terbaru)
            // 2000000: Success General
            // 2003800: Success Topup
            $successCodes = ['2000000', '2003800'];
            $isSuccessCode = in_array($resCode, $successCodes);

            DB::table('dana_response_codes')->insert([
                'tenant_id'     => $aff->tenant_id ?? null,
                'response_code' => $resCode,
                'category'      => 'TOPUP',
                'message_title' => $isSuccessCode ? 'Transaction Success' : 'New DANA Code',
                'description'   => $result['responseMessage'] ?? 'System Generated Description',
                'solution'      => 'Cek Dokumentasi DANA',
                'is_success'    => $isSuccessCode,
                'created_at'    => now()
            ]);

            // Fetch ulang data yang baru diinsert
            $library = DB::table('dana_response_codes')
                ->where('response_code', $resCode)
                ->where('category', 'TOPUP')
                ->first();
        }

        // --- [LOGIC TRANSAKSI] ---
        if ($library->is_success) {
            // 1. Catat Transaksi
            DB::table('dana_transactions')->insert([
                'tenant_id'        => $aff->tenant_id ?? null,
                'affiliate_id'     => $aff->id,
                'type'             => 'TOPUP',
                'reference_no'     => $partnerRef,
                'phone'            => $cleanPhone,
                'amount'           => $request->amount,
                'status'           => 'SUCCESS',
                'response_payload' => json_encode($result),
                'created_at'       => now()
            ]);

            // 2. Potong Saldo Affiliate (Hanya jika sukses)
            DB::table('affiliates')->where('id', $aff->id)->decrement('balance', $request->amount);

            // 3. WA Notifikasi Sukses
            $msgUser = "✅ *TOPUP BERHASIL*\n\nHalo *{$aff->name}*,\nTopup DANA ke {$cleanPhone} senilai Rp " . number_format($request->amount) . " berhasil.\n\nRef ID: {$partnerRef}\nWaktu: " . now()->format('d/m H:i') . " WIB\n\n_Sancaka System_";

            // Pastikan method sendWhatsApp ada/bisa dipanggil
            if (method_exists($this, 'sendWhatsApp')) {
                $this->sendWhatsApp($cleanPhone, $msgUser, $waToken);
            }

            Log::info('[DANA TOPUP] SUKSES & SALDO DIPOTONG', ['ref' => $partnerRef]);
            return back()->with('dana_report', $library)->with('success', 'Topup Berhasil!');

        } else {
            // Transaksi Gagal dari DANA
            DB::table('dana_transactions')->insert([
                'tenant_id'        => $aff->tenant_id ?? null,
                'affiliate_id'     => $aff->id,
                'type'             => 'TOPUP',
                'reference_no'     => $partnerRef,
                'phone'            => $cleanPhone,
                'amount'           => $request->amount,
                'status'           => 'FAILED',
                'response_payload' => json_encode($result),
                'created_at'       => now()
            ]);

            // WA Notifikasi Gagal ke Admin
            $msgAdmin = "⚠️ *GAGAL TOPUP DANA*\n\nAffiliate: {$aff->name}\nTarget: {$cleanPhone}\nNominal: Rp " . number_format($request->amount) . "\nResponse: [{$resCode}] {$library->message_title}\nDesc: {$library->description}";

            if (method_exists($this, 'sendWhatsApp')) {
                $this->sendWhatsApp($adminWA, $msgAdmin, $waToken);
            }

            Log::error('[DANA TOPUP] Gagal Response API', ['result' => $result]);
            return back()->with('dana_report', $library)->with('error', 'Gagal: ' . $library->description);
        }

    } catch (\Exception $e) {
        Log::error('[DANA TOPUP] EXCEPTION', ['msg' => $e->getMessage(), 'line' => $e->getLine()]);

        // WA Error System ke Admin
        if (isset($adminWA) && method_exists($this, 'sendWhatsApp')) {
             $this->sendWhatsApp($adminWA, "🚨 *SYSTEM ERROR TOPUP*\nMsg: " . $e->getMessage(), $waToken);
        }

        return back()->with('error', 'Terjadi Kesalahan Sistem: ' . $e->getMessage());
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
                'tenant_id'    => $aff->tenant_id, // <--- TAMBAHKAN INI
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
            'tenant_id'    => $aff->tenant_id, // <--- TAMBAHKAN
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
                    'tenant_id'    => $aff->tenant_id, // <--- TAMBAHKAN
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
                    'tenant_id'    => $aff->tenant_id, // <--- TAMBAHKAN
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
                    'tenant_id'    => $aff->tenant_id, // <--- TAMBAHKAN
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

    // -------------------------------------------------------------------------
    // DEPOSIT VIA DANA (TARIK SALDO DANA USER -> MASUK KE SYSTEM)
    // -------------------------------------------------------------------------

    public function depositViaDana(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'amount' => 'required|numeric|min:1000',
        ]);

        $member = Auth::guard('member')->user();

        // Cek apakah akun sudah terhubung
        if (!$member->dana_access_token) {
            return back()->with('error', 'Silakan hubungkan akun DANA Anda terlebih dahulu.');
        }

        // [LOG 1] Mulai Transaksi
        $refNo = 'DEP-DANA-' . time() . mt_rand(100, 999);
        Log::info('[DEPOSIT DANA] Request Baru', [
            'affiliate_id' => $member->id,
            'amount' => $request->amount,
            'ref' => $refNo
        ]);

        // 2. Setup Request ke DANA (Acquiring Order)
        $timestamp = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');

        // Body Request V2.0 (Acquiring)
        $payload = [
            "request" => [
                "head" => [
                    "version"      => "2.0",
                    "function"     => "dana.acquiring.order.create",
                    "clientId"     => config('services.dana.x_partner_id'),
                    "clientSecret" => config('services.dana.client_secret'),
                    "reqTime"      => $timestamp,
                    "reqMsgId"     => (string) Str::uuid(),
                    "reserve"      => "{}"
                ],
                "body" => [
                    "merchantId" => config('services.dana.merchant_id'),
                    "merchantTransId" => $refNo,
                    "order" => [
                        "orderTitle" => "Topup Saldo Sancaka",
                        "orderAmount" => [
                            "currency" => "IDR",
                            "value" => (string) number_format($request->amount, 0, '', '') // Format string tanpa desimal/koma
                        ],
                        "merchantTransType" => "01", // 01 = Transaction
                        "orderMemo" => "Deposit Saldo Member ID: " . $member->id
                    ],
                    "merchantTransType" => "01",
                    "envInfo" => [
                        "sourcePlatform" => "IPG",
                        "terminalType"   => "SYSTEM"
                    ],
                    // PENTING: Menggunakan Token User agar dia tidak perlu login ulang, cuma PIN
                    "userCredential" => [
                        "accessToken" => $member->dana_access_token
                    ]
                ]
            ]
        ];

        // 3. Generate Signature V2
        $jsonToSign = json_encode($payload['request'], JSON_UNESCAPED_SLASHES);
        $signature  = $this->generateSignature($jsonToSign); // Pastikan function generateSignature mendukung V2/V1 logic

        try {
            // 4. Catat Dulu ke Database (PENDING)
            DB::table('dana_transactions')->insert([
                'tenant_id'        => $member->tenant_id ?? 1,
                'affiliate_id'     => $member->id,
                'type'             => 'DEPOSIT', // Tipe baru: DEPOSIT
                'reference_no'     => $refNo,
                'phone'            => $member->whatsapp, // Nomor User
                'amount'           => $request->amount,
                'status'           => 'PENDING',
                'response_payload' => null,
                'created_at'       => now()
            ]);

            Log::info('[DEPOSIT DANA] Mengirim Request Create Order...');

            // 5. Kirim Request ke DANA
            $response = Http::post('https://api.sandbox.dana.id/dana/acquiring/order/create.htm', [
                "request"   => $payload['request'],
                "signature" => $signature
            ]);

            $result = $response->json();
            Log::info('[DEPOSIT DANA] Response:', ['res' => $result]);

            // 6. Cek Response
            $resBody = $result['response']['body'] ?? [];
            $resStatus = $resBody['resultInfo']['resultStatus'] ?? 'F';

            if ($resStatus == 'S') { // S = Success Creating Order

                // Ambil URL Pembayaran (Checkout URL)
                // DANA akan mengembalikan URL dimana user harus memasukkan PIN
                $checkoutUrl = $resBody['checkoutUrl'];

                // Update Log dengan Payload
                DB::table('dana_transactions')
                    ->where('reference_no', $refNo)
                    ->update(['response_payload' => json_encode($result)]);

                // REDIRECT USER KE DANA UNTUK PIN
                return redirect($checkoutUrl);

            } else {
                // Gagal Create Order
                $errMsg = $resBody['resultInfo']['resultMsg'] ?? 'Gagal membuat order DANA.';

                DB::table('dana_transactions')
                    ->where('reference_no', $refNo)
                    ->update(['status' => 'FAILED', 'response_payload' => json_encode($result)]);

                return back()->with('error', 'Gagal Deposit: ' . $errMsg);
            }

        } catch (\Exception $e) {
            Log::error('[DEPOSIT DANA] Exception', ['msg' => $e->getMessage()]);
            return back()->with('error', 'Sistem Error: ' . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    //  TAMBAHKAN KODE INI (START)
    // -------------------------------------------------------------------------

    // --- CONFIG HARDCODE (Agar Stabil) ---
    private $clientId     = "2025081520100641466855"; // X-PARTNER-ID
    private $merchantId   = "216620080014040009735";
    private $clientSecret = "1df385deaa6ed3c0b8fa1d20fa304545904b2e4232fbf088dabe853c22d08f63";
    private $baseUrl      = "https://api.sandbox.dana.id";

    // Private Key (Format 1 Baris)
    private $rawPrivateKey = "MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDNVB5kzP1G9sggIGAyNzIHaK9fY5pmP2HUhDsYY0eSrljlgksAOVHgaCION0vZ4679ZRQXWZciJqZLXAhJE8Iyna9RNL4bM2qDk3RvMR3xnaDRA97FofxL99fMFXl2vVn4k6Az3PGZtSKjGOtb1E02F/iJckZVO3jBacVKbUUS6e8Dut8wScw0R5VLAurNIvLxFoYJa3mPkVmx77fkL9S0qTbu/cRLayhguiPzg/P9DlQYa5ah7lT92P+79dSBp7TxrQbbm6Yic1WfsS3deREV1qp30om2frp5lyOpcrxcs+5dGV0viRV41bg4LOFjD1uIc7YiXEJn8ZIW37K1ZvJrAgMBAAECggEAA91U8x2+mKLVcnFZjihmyyfnwRpdUhZYT4krmZJoyvR4HN2+bqMljN044t6ckV3NMdzAq43Wn+BtWdbCGyoBijVYkuU0vMtTcmWIl/0rLJyEZdq2Sy740i84gxFWZ2s58clJhyBd9cAohjxWVbShvWZnGaMqerkzVSSZ/4Qd/DSdVxU2+YuooLq3QgVasmlZkSy4W720Q2Op6NS8joq0LRHxQRRbvl9J99zs+3cTtSfVK3nLOixhiLu0O/keek8yZ6Kw98Rms/od1TWDY0ivo24y0ABfnWOOy6f/+v3MzKq2ghvFIX0ft6Z79EDt839AjJXW82l5E085J7qY66kKhQKBgQDnAb1iVLL6ycR3RqBCR0MYBdJC8uNdgxw/vi6+fic7MAYY9/FsdDVQr0do4tTCkIwjcHoOPGwrwYl3xnTzDSgd5cX0wU0hbBXrSfN+zZjkwf+8eec+mIvMBV3UMe2kJ/Z8aWvtUmhqVK9fgAqggiFNGmIAjmxJPi3iBdl9Qvrm1QKBgQDjiymT8cSl9bMqUQxG0ggfTFXlZFiVBlmk5qYEcbSaz247Hqo2sLR5it4qHxiWV/QqXabhVYFkQcLTd3Qgj9t8TwWOvSYN69gBxW3dYqsptYVQ8lywjKKt3WKVGSKOgqslMwXnJTHZ/PycBDigDP1nmhczmx0DEQFVltW3n+GUPwKBgCSAzeBf6fhfMcB3VJOklyGQqe0SXINGWIxqDRDk9mYP7Ka9Z1Tv+AzL5cjZLy2fkcV33JGrUpyHdKWMoqZVieVPjbxjX0DMx5nqkaOT8XkUfsjVqojlqhGPN4h0a0zpU7XNItTZlM5Ym23H2eYLKh/470uPNeVNAgsZSYjVsLgRAoGAJuEaY5sF3M2UpYBftqIgnShv7NgugpgpLRH0AAJlt6YF0bg1oU6kJ7hgqZXSn627nJmP8CSqDTVnUrawcvfhquXdrzwGio5nxDW1xgQb9u57Lw+aYthE26xeMdevneYZ1CtZsNscH4EosIfQHRjbG56qpDi2xlVbgwJY1h1NcAUCgYB28OEqvgeYcu2YJfcn66kgd/eTNPiHrGxDL6zhU7MDOl07Cm7AaRFeyLuYrHchI2cbGSc5ssZNYjf5Fp9mh6XrNR/qAr2HmcN0nJdx1gTNIP2bYRxzrqLqfxoHSKmORMh4BCS+saRwkmMdIFzXdNVOL5vXkAGZnIBgAJ/9t+HC0w==";

    public function storeDeposit(Request $request)
    {
        $request->validate([
            'amount'         => 'required|numeric|min:1000',
            'payment_method' => 'nullable|in:BANK_TRANSFER,DANA',
        ]);

        $member = Auth::guard('member')->user();
        $method = $request->payment_method ?? 'BANK_TRANSFER';

        // =================================================================
        // OPSI 1: VIA DANA (SDK FIX)
        // =================================================================
        if ($method === 'DANA') {

            if (empty($member->dana_access_token)) {
                return back()->with('error', 'Silakan hubungkan akun DANA Anda terlebih dahulu.');
            }

            // A. Cek Config
            $merchantId = config('services.dana.merchant_id');
            if (empty($merchantId)) {
                return back()->with('error', 'Config Error: Merchant ID Missing.');
            }

            // B. Idempotency Check
            $existingTx = DB::table('dana_transactions')
                ->where('affiliate_id', $member->id)
                ->where('type', 'DEPOSIT')
                ->whereIn('status', ['INIT', 'PENDING'])
                ->where('amount', $request->amount)
                ->where('created_at', '>=', now()->subMinutes(10))
                ->orderBy('created_at', 'desc')
                ->first();

            if ($existingTx) {
                $refNo = $existingTx->reference_no;
                if (!empty($existingTx->response_payload)) {
                    $savedData = json_decode($existingTx->response_payload, true);
                    $url = $savedData['webRedirectUrl'] ?? $savedData['checkoutUrl'] ?? null;
                    if ($url) return redirect($url);
                }
            } else {
                $refNo = 'DEP-' . time() . mt_rand(100, 999);
                DB::table('dana_transactions')->insert([
                    'tenant_id'    => $member->tenant_id ?? 1,
                    'affiliate_id' => $member->id,
                    'type'         => 'DEPOSIT',
                    'reference_no' => $refNo,
                    'phone'        => $member->whatsapp ?? '',
                    'amount'       => $request->amount,
                    'status'       => 'INIT',
                    'created_at'   => now()
                ]);
            }

            try {
                // 1. Setup Config SDK
                $config = new Configuration();
                $config->setApiKey('PRIVATE_KEY', config('services.dana.private_key'));
                $config->setApiKey('X_PARTNER_ID', config('services.dana.x_partner_id'));
                $config->setApiKey('ORIGIN', config('services.dana.origin'));
                $config->setApiKey('DANA_ENV', Env::SANDBOX);

                $apiInstance = new WidgetApi(null, $config);

                // 2. Siapkan Objek-Objek Pendukung (Sesuai Struktur Class Bapak)

                // --- A. ORDER OBJECT ---
                $orderObj = new Order();
                $orderObj->setOrderTitle("Deposit Saldo");
                $orderObj->setOrderMemo("Topup User ID: " . $member->id);
                // $orderObj->setMerchantTransType("01"); // Opsional jika diminta

                // --- B. ENV INFO OBJECT (WAJIB UTK FRAUD DETECTION) ---
                $envInfo = new EnvInfo();
                $envInfo->setSourcePlatform("IPG");
                $envInfo->setTerminalType("WEB");
                $envInfo->setOrderTerminalType("APP"); // Sesuai sample manual bapak
                $envInfo->setWebsiteLanguage("ID");
                $envInfo->setClientIp($request->ip() ?? '127.0.0.1');

                // --- C. ADDITIONAL INFO OBJECT ---
                $addInfo = new WidgetPaymentRequestAdditionalInfo();
                $addInfo->setProductCode("DIGITAL_PRODUCT"); // Atau kode panjang tadi "510510..."
                $addInfo->setMcc("5411"); // Merchant Category Code (Wajib isi string, misal 5411 Grocery atau kosongkan "")
                $addInfo->setOrder($orderObj); // Masukkan object Order
                $addInfo->setEnvInfo($envInfo); // Masukkan object EnvInfo

                // 3. Susun Request Utama
                $paymentRequest = new WidgetPaymentRequest();
                $paymentRequest->setMerchantId($merchantId);
                $paymentRequest->setPartnerReferenceNo($refNo);

                // Set Amount
                $money = new Money();
                $money->setValue(number_format($request->amount, 2, '.', ''));
                $money->setCurrency("IDR");
                $paymentRequest->setAmount($money);

                // Set Redirect URL
                $urlParam = new UrlParam();
                $urlParam->setUrl(url('/member/dashboard'));
                $urlParam->setType("PAY_RETURN");
                $urlParam->setIsDeeplink("Y");
                $paymentRequest->setUrlParams([$urlParam]);

                // Set Expiry (1 Jam)
                $paymentRequest->setValidUpTo(now()->addHour()->format('Y-m-d\TH:i:sP'));

                // Masukkan Additional Info Lengkap
                $paymentRequest->setAdditionalInfo($addInfo);

                // 4. EKSEKUSI
                Log::info("[DANA SDK] Sending Request Ref: " . $refNo);
                $result = $apiInstance->widgetPayment($paymentRequest);

                // 5. Ambil URL Redirect
                $redirectUrl = null;
                if (method_exists($result, 'getWebRedirectUrl')) {
                    $redirectUrl = $result->getWebRedirectUrl();
                } elseif (isset($result->webRedirectUrl)) {
                    $redirectUrl = $result->webRedirectUrl;
                }

                if ($redirectUrl) {
                    DB::table('dana_transactions')
                        ->where('reference_no', $refNo)
                        ->update([
                            'status' => 'PENDING',
                            'response_payload' => json_encode($result),
                            'updated_at' => now()
                        ]);
                    return redirect($redirectUrl);
                } else {
                    throw new \Exception("Gagal mendapatkan link pembayaran (Empty URL).");
                }

            } catch (\Exception $e) {
                // Debugging Error Body
                $errorMsg = $e->getMessage();
                if (method_exists($e, 'getResponseBody')) {
                    $body = $e->getResponseBody();
                    Log::error("[DANA SDK ERROR BODY]", (array)$body);
                    if ($body && isset($body->responseMessage)) {
                        $errorMsg = "DANA: " . $body->responseMessage;
                    }
                }

                DB::table('dana_transactions')
                    ->where('reference_no', $refNo)
                    ->update(['status' => 'FAILED', 'response_payload' => $errorMsg, 'updated_at' => now()]);

                Log::error('[DANA SDK EXCEPTION] ' . $errorMsg);
                return back()->with('error', $errorMsg);
            }
        }

        else {
            return $this->processBankTransfer($request, $member);
        }
    }

    /**
     * HELPER MANUAL REQUEST (DIRECT API)
     * Menggantikan SDK yang bermasalah.
     */
    private function sendSnapRequest($path, $method, $body, $timestamp)
    {
        // 1. Minify JSON
        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // 2. Generate Signature
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = $method . "|" . $path . "||" . $hashedBody . "|" . $timestamp;

        // 3. Ambil Key
        $privateKeyContent = config('services.dana.private_key');
        if (!$privateKeyContent) throw new \Exception("Private Key DANA belum disetting di .env");

        // Format Key ke PEM
        $cleanKey = preg_replace('/-{5}(BEGIN|END) PRIVATE KEY-{5}|\r|\n|\s/', '', $privateKeyContent);
        $formattedKey = "-----BEGIN PRIVATE KEY-----\n" . chunk_split($cleanKey, 64, "\n") . "-----END PRIVATE KEY-----";

        // 4. Sign
        $binarySig = "";
        if (!openssl_sign($stringToSign, $binarySig, $formattedKey, OPENSSL_ALGO_SHA256)) {
             throw new \Exception("Gagal membuat Signature (OpenSSL Error): " . openssl_error_string());
        }
        $signature = base64_encode($binarySig);

        // 5. Header
        $headers = [
            'Content-Type'  => 'application/json',
            'X-PARTNER-ID'  => config('services.dana.x_partner_id'), // Client ID
            'X-EXTERNAL-ID' => (string) Str::uuid(),
            'X-TIMESTAMP'   => $timestamp,
            'X-SIGNATURE'   => $signature,
            'CHANNEL-ID'    => '95221',
            'ORIGIN'        => config('services.dana.origin'),
        ];

        // 6. Kirim
        $baseUrl = config('services.dana.base_url', 'https://api.sandbox.dana.id');
        $response = Http::withHeaders($headers)
                        ->withBody($jsonBody, 'application/json')
                        ->post($baseUrl . $path);

        // Log Debug
        Log::info("[DANA MANUAL REQUEST]", ['url' => $baseUrl.$path, 'body' => $body]);
        Log::info("[DANA MANUAL RESPONSE]", ['status' => $response->status(), 'body' => $response->json()]);

        if ($response->failed() && empty($response->json())) {
             throw new \Exception("DANA HTTP Error: " . $response->status());
        }

        return $response->json();
    }

    /**
     * PROSES BANK TRANSFER MANUAL
     */
    private function processBankTransfer($request, $member)
    {
        $uniqueCode     = mt_rand(111, 999);
        $amountOriginal = $request->amount;
        $amountTotal    = $amountOriginal + $uniqueCode;
        $refNo          = 'DEP-B-' . date('ymd') . rand(1000, 9999);

        DB::beginTransaction();
        try {
            $topup = new TopUp();
            $topup->tenant_id      = $member->tenant_id ?? 1;
            $topup->affiliate_id   = $member->id;
            $topup->reference_no   = $refNo;
            $topup->amount         = $amountOriginal;
            $topup->unique_code    = $uniqueCode;
            $topup->total_amount   = $amountTotal;
            $topup->status         = 'PENDING';
            $topup->payment_method = 'BANK_TRANSFER';
            $topup->created_at     = now();
            $topup->save();

            DB::commit();

            $formattedTotal = number_format($amountTotal, 0, ',', '.');

            // WA Notif
            $msg = "📥 *TIKET DEPOSIT*\n\nRef: {$refNo}\nSilakan transfer TEPAT: *Rp {$formattedTotal}*";
            if (method_exists($this, 'sendWhatsApp')) {
                $this->sendWhatsApp($member->whatsapp, $msg);
            }

            return back()->with('success', "Tiket Dibuat! Transfer Rp {$formattedTotal}");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }
}
