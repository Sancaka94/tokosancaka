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

   /**
     * 1. START BINDING (MODIFIKASI UNTUK CENTRAL GATEWAY)
     * Mengarahkan Member ke DANA dengan State khusus
     */
    public function startBinding(Request $request)
    {
        // A. Ambil Data Member yang Login
        $member = Auth::guard('member')->user();

        if (!$member) {
            return redirect()->route('member.login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // B. Deteksi Data Identitas
        $memberId  = $member->id;
        $tenantId  = $member->tenant_id ?? 1;

        // Ambil Subdomain saat ini (misal: 'member' atau 'mitra1')
        // Agar nanti Gateway Pusat bisa memulangkan user ke sini
        $currentSubdomain = explode('.', $request->getHost())[0];

        // ---------------------------------------------------------------------
        // C. FORMAT STATE KHUSUS GATEWAY PUSAT (PENTING!)
        // Format: ACTION - ID_MEMBER - SUBDOMAIN - TENANT_ID
        // ---------------------------------------------------------------------
        $state = "BIND_MEMBER-{$memberId}-{$currentSubdomain}-{$tenantId}";

        // D. Konfigurasi URL
        $partnerId  = config('services.dana.x_partner_id');

        // Redirect URL WAJIB ke Controller Pusat (DanaGatewayController)
        // Jangan ubah URL ini, harus sama persis dengan yang di Dashboard DANA
        $centralCallbackUrl = 'https://apps.tokosancaka.com/dana/callback';

        $encodedRedirect = urlencode($centralCallbackUrl);
        $requestId = \Illuminate\Support\Str::uuid();

        // E. Generate URL DANA
        $danaUrl = "https://m.sandbox.dana.id/d/portal/oauth?partnerId={$partnerId}&scopes=QUERY_BALANCE,MINI_DANA,DEFAULT_BASIC_PROFILE&requestId={$requestId}&redirectUrl={$encodedRedirect}&state={$state}&terminalType=WEB";

        Log::info("[MEMBER BINDING] Redirecting to DANA via Central Gateway. State: $state");

        return redirect()->away($danaUrl);
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
    // --- [VALIDASI INPUT] ---
    $request->validate([
        'affiliate_id' => 'required|exists:affiliates,id',
        'phone'        => 'required|numeric',
        'amount'       => 'required|numeric|min:1',
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
    "partnerReferenceNo" => "1771397079",
    "customerNumber"     => "6281298055138",
    "amount" => [
        "value"    => "1.00",
        "currency" => "IDR"
    ],
    "feeAmount" => [
        "value"    => "1.00",
        "currency" => "IDR"
    ],
    // Hardcoded sesuai instruksi untuk trigger timeout
    "transactionDate" => "2030-05-01T00:46:43+07:00",
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
    $path = '/rest/v1.0/emoney/topup-status';

    // --- [BODY] SESUAI DOKUMENTASI ---
    $body = [
        "originalPartnerReferenceNo" => $trx->reference_no, // Required
        "originalReferenceNo"        => "", // Opsional, bisa kosong jika belum ada
        "originalExternalId"         => "", // Opsional
        "serviceCode"                => "XX", // Wajib "38" untuk Topup
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

        // TAMBAHKAN INI UNTUK MENANGKAP HASIL TEST:
        elseif ($resCode == '4003901') {
            return back()->with('error', 'Test Berhasil: Invalid Field Format (4003901) terdeteksi!');
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

    // Fungsi untuk mendapatkan Private Key dalam format yang benar
    /**
     * Handle Store Deposit Request
     */
    public function storeDeposit(Request $request)
    {
        // 1. LOG REQUEST MASUK
        $user = Auth::guard('member')->user();
        Log::info("[DEPOSIT-LOG] Request Masuk", [
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'payload' => $request->all()
        ]);

        $request->validate([
            'amount' => 'required|numeric|min:1000',
            'payment_method' => 'nullable|in:BANK_TRANSFER,DANA',
        ]);

        $method = $request->payment_method ?? 'BANK_TRANSFER';

        // 2. LOGIC CABANG
        if ($method === 'DANA') {
            return $this->processDanaPayment($request, $user);
        } else {
            return $this->processBankTransfer($request, $user);
        }
    }

    /**
     * PROSES DANA (SDK)
     */
    private function processDanaPayment($request, $member)
    {
        Log::info("[DEPOSIT-LOG] Masuk Flow DANA", ['member_id' => $member->id]);

        // CEK TOKEN
        if (empty($member->dana_access_token)) {
            Log::warning("[DEPOSIT-LOG] DANA Token Missing", ['member_id' => $member->id]);
            return back()->with('error', 'Silakan hubungkan akun DANA Anda terlebih dahulu.');
        }

        // --- PERUBAHAN DI SINI ---
        // Gunakan nilai dari Input UI/Blade
        $realAmount = $request->amount;

        Log::info("[DEPOSIT-LOG] Set Amount Real", ['amount' => $realAmount]);
        // -------------------------

        // Config Check
        $merchantId = config('services.dana.merchant_id');
        if (empty($merchantId)) {
            Log::error("[DEPOSIT-LOG] Config Error: Merchant ID Kosong");
            return back()->with('error', 'Config Error: Merchant ID Missing.');
        }

        // Init DB Transaction Record
        $refNo = 'DEP-' . time() . mt_rand(100, 999);

        Log::info("[DEPOSIT-LOG] Membuat Record DB (INIT)", ['ref_no' => $refNo]);

        DB::table('dana_transactions')->insert([
            'tenant_id'    => $member->tenant_id ?? 1,
            'affiliate_id' => $member->id,
            'type'         => 'DEPOSIT',
            'reference_no' => $refNo,
            'phone'        => $member->whatsapp ?? '',
            'amount'       => $realAmount,
            'status'       => 'INIT',
            'created_at'   => now()
        ]);

        try {
            // 1. Config SDK
            Log::info("[DEPOSIT-LOG] Menginisialisasi SDK Configuration");
            $config = new Configuration();
            $config->setApiKey('PRIVATE_KEY', config('services.dana.private_key'));
            $config->setApiKey('X_PARTNER_ID', config('services.dana.x_partner_id'));
            $config->setApiKey('ORIGIN', config('services.dana.origin'));
            $config->setApiKey('DANA_ENV', Env::SANDBOX); // Pastikan Env class terimport

            $apiInstance = new WidgetApi(null, $config);

            // 2. Order
            $orderObj = new DanaOrder();
            $orderObj->setOrderTitle("Deposit Saldo");
            $orderObj->setOrderMemo("Topup ID " . $member->id);

            // 3. EnvInfo
            $envInfo = new EnvInfo();
            $envInfo->setSourcePlatform("IPG");
            $envInfo->setTerminalType("WEB");
            $envInfo->setWebsiteLanguage("ID");
            $envInfo->setClientIp("82.25.62.13"); // IPv4 Only (Hardcoded for dev/sandbox often required)

            // 4. Additional Info
            $addInfo = new WidgetPaymentRequestAdditionalInfo();
            $addInfo->setProductCode("51051000100000000001");
            // $addInfo->setMcc("5411"); // Opsional
            $addInfo->setOrder($orderObj);
            $addInfo->setEnvInfo($envInfo);

            // 5. Request Object
            $paymentRequest = new WidgetPaymentRequest();
            $paymentRequest->setMerchantId($merchantId);
            $paymentRequest->setPartnerReferenceNo($refNo);

            // Amount (Strict 2 Decimal)
            $amountString = number_format($realAmount, 2, '.', '');
            $money = new Money();
            $money->setValue($amountString);
            $money->setCurrency("IDR");
            $paymentRequest->setAmount($money);

            // Redirect URL
            //$urlParam->setUrl('https://apps.tokosancaka.com/member/dashboard');
            //$urlParam->setUrl(url('/member/dashboard'));
            //$urlParam->setType("PAY_RETURN");
            //urlParam->setIsDeeplink("Y");
            //$paymentRequest->setUrlParams([$urlParam]);
            //$paymentRequest->setAdditionalInfo($addInfo);

            // ... di dalam function processDanaPayment ...

            // 1. INISIALISASI OBJEK DULU (Wajib ada baris ini)
            $urlParam = new UrlParam();

            // 2. SET URL (Gunakan Logic Multi-Tenant)
            // Laravel 'route()' otomatis mendeteksi domain/subdomain yang sedang dipakai user.
            // Jadi jika user akses dari 'toko-a.apps...', route() akan menghasilkan 'toko-a.apps...' juga.
            $returnUrl = route('member.dashboard');

            // Paksa HTTPS (DANA wajib HTTPS)
            if (!str_contains($returnUrl, 'https://')) {
                $returnUrl = str_replace('http://', 'https://', $returnUrl);
            }

            // Log untuk memastikan URL sudah benar sebelum dikirim
            Log::info('[DANA RETURN URL]', ['url' => $returnUrl]);

            $urlParam->setUrl($returnUrl);
            $urlParam->setType("PAY_RETURN");
            $urlParam->setIsDeeplink("Y");

            // Masukkan ke Payment Request
            $paymentRequest->setUrlParams([$urlParam]);
            $paymentRequest->setAdditionalInfo($addInfo);

            // ... lanjut kirim ke SDK ...

            // LOG PAYLOAD SDK SEBELUM KIRIM
            // Kita coba convert object ke array jika method tersedia, atau log parameter kunci
            Log::info("[DEPOSIT-LOG] SDK Request Payload Siap", [
                'merchant_id' => $merchantId,
                'ref_no' => $refNo,
                'amount' => $amountString,
                'add_info' => json_encode($addInfo) // Log struktur object jika memungkinkan
            ]);

            // 6. EKSEKUSI API
            Log::info("[DEPOSIT-LOG] Mengirim Request ke DANA...");
            $result = $apiInstance->widgetPayment($paymentRequest);
            Log::info("[DEPOSIT-LOG] Response Diterima dari DANA", ['response' => (array)$result]);

            // 7. HANDLE RESPONSE
            $redirectUrl = null;
            if (method_exists($result, 'getWebRedirectUrl')) {
                $redirectUrl = $result->getWebRedirectUrl();
            } elseif (isset($result->webRedirectUrl)) {
                $redirectUrl = $result->webRedirectUrl;
            }

            if ($redirectUrl) {
                Log::info("[DEPOSIT-LOG] Redirect URL ditemukan. Update DB PENDING.", ['url' => $redirectUrl]);

                DB::table('dana_transactions')
                    ->where('reference_no', $refNo)
                    ->update([
                        'status' => 'PENDING',
                        'response_payload' => json_encode($result),
                        'updated_at' => now()
                    ]);

                return redirect($redirectUrl);
            } else {
                Log::error("[DEPOSIT-LOG] Redirect URL NULL", ['full_response' => json_encode($result)]);
                throw new \Exception("Empty Redirect URL form DANA Response.");
            }

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            $logContext = ['file' => $e->getFile(), 'line' => $e->getLine()];

            if (method_exists($e, 'getResponseBody')) {
                $body = $e->getResponseBody();
                Log::error("[DEPOSIT-LOG] SDK API ERROR BODY", (array)$body);

                if ($body && isset($body->responseMessage)) {
                    $code = $body->responseCode ?? '';
                    $errorMsg = "DANA ($code): " . $body->responseMessage;
                }
            }

            Log::error('[DEPOSIT-LOG] EXCEPTION THROWN: ' . $errorMsg, $logContext);

            DB::table('dana_transactions')
                ->where('reference_no', $refNo)
                ->update([
                    'status' => 'FAILED',
                    'response_payload' => $errorMsg,
                    'updated_at' => now()
                ]);

            return back()->with('error', $errorMsg);
        }
    }

    /**
     * HELPER MANUAL REQUEST (DIRECT API)
     * Menggantikan SDK yang bermasalah.
     */
    private function sendSnapRequest($path, $method, $body, $timestamp)
    {
        Log::info("[DEPOSIT-LOG] Memulai Manual Request (sendSnapRequest)", [
            'path' => $path,
            'method' => $method
        ]);

        // 1. Minify JSON
        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        Log::debug("[DEPOSIT-LOG] Minified JSON Body", ['json' => $jsonBody]);

        // 2. Generate Signature
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = $method . "|" . $path . "||" . $hashedBody . "|" . $timestamp;

        Log::debug("[DEPOSIT-LOG] String To Sign Generated", ['string' => $stringToSign]);

        // 3. Ambil Key
        $privateKeyContent = config('services.dana.private_key');
        if (!$privateKeyContent) {
            Log::critical("[DEPOSIT-LOG] Private Key ENV Missing!");
            throw new \Exception("Private Key DANA belum disetting di .env");
        }

        // Format Key ke PEM
        $cleanKey = preg_replace('/-{5}(BEGIN|END) PRIVATE KEY-{5}|\r|\n|\s/', '', $privateKeyContent);
        $formattedKey = "-----BEGIN PRIVATE KEY-----\n" . chunk_split($cleanKey, 64, "\n") . "-----END PRIVATE KEY-----";

        // 4. Sign
        $binarySig = "";
        if (!openssl_sign($stringToSign, $binarySig, $formattedKey, OPENSSL_ALGO_SHA256)) {
             $opensslError = openssl_error_string();
             Log::error("[DEPOSIT-LOG] OpenSSL Sign Failed", ['error' => $opensslError]);
             throw new \Exception("Gagal membuat Signature: " . $opensslError);
        }
        $signature = base64_encode($binarySig);
        Log::debug("[DEPOSIT-LOG] Signature Generated", ['signature_fragment' => substr($signature, 0, 20) . '...']);

        // 5. Header
        $headers = [
            'Content-Type'  => 'application/json',
            'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
            'X-EXTERNAL-ID' => (string) Str::uuid(),
            'X-TIMESTAMP'   => $timestamp,
            'X-SIGNATURE'   => $signature,
            'CHANNEL-ID'    => '95221',
            'ORIGIN'        => config('services.dana.origin'),
        ];

        Log::info("[DEPOSIT-LOG] Headers Prepared", $headers);

        // 6. Kirim
        $baseUrl = config('services.dana.base_url', 'https://api.sandbox.dana.id');
        $fullUrl = $baseUrl . $path;

        Log::info("[DEPOSIT-LOG] Sending HTTP Request...", ['url' => $fullUrl]);

        $response = Http::withHeaders($headers)
                        ->withBody($jsonBody, 'application/json')
                        ->post($fullUrl);

        // Log Response
        Log::info("[DEPOSIT-LOG] HTTP Response Status: " . $response->status());
        Log::info("[DEPOSIT-LOG] HTTP Response Body: ", ['body' => $response->json()]);

        if ($response->failed() && empty($response->json())) {
             Log::error("[DEPOSIT-LOG] HTTP Failed Empty JSON");
             throw new \Exception("DANA HTTP Error: " . $response->status());
        }

        return $response->json();
    }

    /**
     * PROSES BANK TRANSFER MANUAL
     */
    private function processBankTransfer($request, $member)
    {
        Log::info("[DEPOSIT-LOG] Masuk Flow BANK_TRANSFER", ['member_id' => $member->id]);

        $uniqueCode     = mt_rand(111, 999);
        $amountOriginal = $request->amount;
        $amountTotal    = $amountOriginal + $uniqueCode;
        $refNo          = 'DEP-B-' . date('ymd') . rand(1000, 9999);

        Log::info("[DEPOSIT-LOG] Kalkulasi Bank Transfer", [
            'original' => $amountOriginal,
            'unique' => $uniqueCode,
            'total' => $amountTotal,
            'ref' => $refNo
        ]);

        DB::beginTransaction();
        try {
            $topup = new TopUp(); // Pastikan model TopUp diimpor
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
            Log::info("[DEPOSIT-LOG] Transaksi Bank Disimpan di DB", ['id' => $topup->id]);

            $formattedTotal = number_format($amountTotal, 0, ',', '.');

            // WA Notif
            $msg = "📥 *TIKET DEPOSIT*\n\nRef: {$refNo}\nSilakan transfer TEPAT: *Rp {$formattedTotal}*";

            if (method_exists($this, 'sendWhatsApp')) {
                Log::info("[DEPOSIT-LOG] Mengirim WA Notifikasi...");
                $this->sendWhatsApp($member->whatsapp, $msg);
            } else {
                Log::warning("[DEPOSIT-LOG] Method sendWhatsApp tidak ditemukan.");
            }

            return back()->with('success', "Tiket Dibuat! Transfer Rp {$formattedTotal}");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[DEPOSIT-LOG] Gagal Bank Transfer: " . $e->getMessage());
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }


    // -------------------------------------------------------------------------
    // CEK STATUS TRANSAKSI MANUAL (RESCUE)
    // -------------------------------------------------------------------------
    public function checkAcquiringStatus(Request $request)
    {
        // 1. Ambil RefNo dari Input (DEP-xxxxx)
        $refNo = $request->reference_no;
        Log::info('[MANUAL CHECK] Memulai pengecekan...', ['ref' => $refNo]);

        // 2. Cari Data di Database
        $trx = DB::table('dana_transactions')->where('reference_no', $refNo)->first();
        if (!$trx) return back()->with('error', 'Transaksi tidak ditemukan di database.');

        // 3. Setup Request ke DANA (Acquiring Order Query)
        $timestamp = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');

        $payload = [
            "request" => [
                "head" => [
                    "version"      => "2.0",
                    "function"     => "dana.acquiring.order.query",
                    "clientId"     => config('services.dana.x_partner_id'),
                    "clientSecret" => config('services.dana.client_secret'),
                    "reqTime"      => $timestamp,
                    "reqMsgId"     => (string) Str::uuid(),
                    "reserve"      => "{}"
                ],
                "body" => [
                    "merchantId" => config('services.dana.merchant_id'),
                    "merchantTransId" => $refNo // Kunci Pencarian
                ]
            ]
        ];

        // 4. Generate Signature
        $jsonToSign = json_encode($payload['request'], JSON_UNESCAPED_SLASHES);
        $signature  = $this->generateSignature($jsonToSign);

        try {
            // 5. Kirim Request
            $response = Http::post('https://api.sandbox.dana.id/dana/acquiring/order/query.htm', [
                "request"   => $payload['request'],
                "signature" => $signature
            ]);

            $result = $response->json();
            Log::info('[MANUAL CHECK] Response:', ['res' => $result]);

            // 6. Cek Status dari DANA
            $resBody = $result['response']['body'] ?? [];
            $resStatus = $resBody['resultInfo']['resultStatus'] ?? 'F';
            $orderStatus = $resBody['acquirementStatus'] ?? 'UNKNOWN'; // SUCCESS / CLOSED / FAILED

            // JIKA SUKSES BAYAR (Status di DANA: SUCCESS atau FINISHED)
            if ($resStatus == 'S' && ($orderStatus == 'SUCCESS' || $orderStatus == 'FINISHED')) {

                // Cek apakah di DB kita masih PENDING/INIT?
                if ($trx->status != 'SUCCESS') {
                    DB::beginTransaction();
                    try {
                        // A. Update Status Transaksi
                        DB::table('dana_transactions')->where('id', $trx->id)->update([
                            'status' => 'SUCCESS',
                            'response_payload' => json_encode($result),
                            'updated_at' => now()
                        ]);

                        // B. Tambah Saldo Member
                        DB::table('affiliates')->where('id', $trx->affiliate_id)->increment('balance', $trx->amount);

                        // C. Kurangi Saldo DANA User (Estimasi/Pencatatan)
                        DB::table('affiliates')->where('id', $trx->affiliate_id)->decrement('dana_user_balance', $trx->amount);

                        DB::commit();
                        return back()->with('success', '✅ Transaksi berhasil diverifikasi & Saldo Masuk!');
                    } catch (\Exception $e) {
                        DB::rollBack();
                        return back()->with('error', 'Database Error: ' . $e->getMessage());
                    }
                } else {
                    return back()->with('warning', 'Transaksi ini sudah Sukses sebelumnya.');
                }
            } else {
                return back()->with('error', 'Status di DANA: ' . $orderStatus);
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Sistem Error: ' . $e->getMessage());
        }
    }


}
