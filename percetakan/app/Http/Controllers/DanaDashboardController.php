<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DanaDashboardController extends Controller
{
    public function index()
    {
        // Menampilkan semua data untuk dashboard admin
        $affiliates = DB::table('affiliates')->orderBy('id', 'DESC')->get();
        return view('dana_dashboard', compact('affiliates'));
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
    Log::info('[DANA CALLBACK] Mendapatkan Auth Code:', $request->all());

    $authCode = $request->input('auth_code');
    $state = $request->input('state');
    $affiliateId = $state ? str_replace('ID-', '', $state) : 11;

    if ($authCode) {
        // Simpan Auth Code-nya dulu ke database
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
            $successCodes = ['2001100', '2007400'];

            if (isset($result['responseCode']) && in_array($result['responseCode'], $successCodes)) {
                // 1. UPDATE TOKEN KE DATABASE
                DB::table('affiliates')->where('id', $affiliateId)->update([
                    'dana_access_token' => $result['accessToken'],
                    'updated_at' => now()
                ]);

                // 2. CATAT KE RIWAYAT TRANSAKSI (AUDIT BINDING)
                DB::table('dana_transactions')->insert([
                    'affiliate_id' => $affiliateId,
                    'type' => 'BINDING',
                    'reference_no' => $externalId,
                    'phone' => '-', // Binding tidak pakai nomor tujuan
                    'amount' => 0,
                    'status' => 'SUCCESS',
                    'response_payload' => json_encode($result),
                    'created_at' => now()
                ]);

                return redirect()->route('dana.dashboard')->with('success', 'Akun Berhasil Terhubung (Status: ' . $result['responseMessage'] . ')');
            }

            // JIKA GAGAL TUKAR TOKEN, TETAP CATAT RIWAYAT GAGALNYA
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

            Log::error('[EXCHANGE FAILED]', $result);
            return redirect()->route('dana.dashboard')->with('error', 'Gagal Tukar Token: ' . ($result['responseMessage'] ?? 'Unknown Error'));

        } catch (\Exception $e) {
            return redirect()->route('dana.dashboard')->with('error', 'Sistem Error: ' . $e->getMessage());
        }
    }

    return redirect()->route('dana.dashboard')->with('error', 'Auth Code Kosong');
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
            return back()->with('success', 'Saldo Riil DANA Terupdate!');
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
            $pesanUser .= "Halo " . $aff->name . ",\n";
            $pesanUser .= "Pencairan profit Anda ke DANA telah sukses.\n\n";
            $pesanUser .= "*Detail:* \n";
            $pesanUser .= "▪️ Nominal: Rp " . number_format($request->amount, 0, ',', '.') . "\n";
            $pesanUser .= "▪️ No. DANA: " . $cleanPhone . "\n";
            $pesanUser .= "▪️ Ref ID: " . $partnerRef . "\n";
            $pesanUser .= "▪️ Waktu: " . now()->format('d/m H:i') . " WIB\n\n";
            $pesanUser .= "Saldo profit Anda telah otomatis terpotong. Terima kasih!";
            
            $this->sendWhatsApp($cleanPhone, $pesanUser);

            // D. Kirim Notifikasi ke ADMIN (Nomor Bos)
            $pesanAdmin = "📢 *LAPORAN TOPUP SUKSES*\n\n";
            $pesanAdmin .= "Affiliate: " . $aff->name . " (ID: " . $aff->id . ")\n";
            $pesanAdmin .= "Nominal: Rp " . number_format($request->amount, 0, ',', '.') . "\n";
            $pesanAdmin .= "Tujuan: " . $cleanPhone . "\n";
            $pesanAdmin .= "Status: Saldo Berhasil Dipotong.";
            
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
    // 1. LOG INPUT REQUEST
    Log::info('[DANA INQUIRY] Start Process', ['affiliate_id' => $request->affiliate_id, 'amount' => $request->amount]);

    $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
    if (!$aff) {
        Log::error('[DANA INQUIRY] Affiliate Not Found', ['id' => $request->affiliate_id]);
        return back()->with('error', 'Affiliate tidak ditemukan.');
    }

    // 2. LOG SANITIZE NOMOR HP
    $rawPhone = $request->phone ?? $aff->whatsapp;
    $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
    if (substr($cleanPhone, 0, 1) === '0') {
        $cleanPhone = '62' . substr($cleanPhone, 1);
    }
    Log::info('[DANA INQUIRY] Phone Sanitized', ['original' => $rawPhone, 'clean' => $cleanPhone]);

    $timestamp = now('Asia/Jakarta')->toIso8601String();
    $path = '/v1.0/emoney/account-inquiry.htm';
    
    $body = [
        "partnerReferenceNo" => "INQ" . time() . Str::random(5),
        "customerNumber"     => $cleanPhone,
        "amount" => [
            "value"    => number_format((float)($request->amount ?? 10000), 2, '.', ''),
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

    // 3. LOG SIGNATURE PROCESS
    $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $hashedBody = strtolower(hash('sha256', $jsonBody));
    $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
    $signature = $this->generateSignature($stringToSign);

    Log::info('[DANA INQUIRY] Signature Generated', [
        'path' => $path,
        'stringToSign' => $stringToSign,
        'signature' => $signature
    ]);

    // 4. LOG FULL REQUEST HEADERS & BODY
    $headers = [
        'Content-Type'  => 'application/json',
        'Authorization' => 'Bearer ' . $aff->dana_access_token,
        'X-TIMESTAMP'   => $timestamp,
        'X-SIGNATURE'   => $signature,
        'ORIGIN'        => config('services.dana.origin'),
        'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
        'X-EXTERNAL-ID' => (string) time() . Str::random(5),
        'X-IP-ADDRESS'  => $request->ip() ?? '127.0.0.1',
        'X-DEVICE-ID'   => '09864ADCASA',
        'CHANNEL-ID'    => '95221'
    ];
    Log::info('[DANA INQUIRY] Sending Request to DANA', ['url' => 'https://api.sandbox.dana.id' . $path, 'headers' => $headers, 'body' => $body]);

    try {
    $response = Http::withHeaders($headers)->withBody($jsonBody, 'application/json')->post('https://api.sandbox.dana.id' . $path);
    $result = $response->json();

    Log::info('[DANA INQUIRY] Response Received', ['status' => $response->status(), 'result' => $result]);

    // PERBAIKAN: Masukkan 2003700 ke dalam daftar kode sukses
    $successInquiryCodes = ['2000000', '2003700'];

   if (isset($result['responseCode']) && in_array($result['responseCode'], $successInquiryCodes)) {
    $customerName = $result['customerName'] ?? ($result['additionalInfo']['customerName'] ?? 'Akun Valid');
    
    // Update nama di tabel affiliates
    DB::table('affiliates')->where('id', $request->affiliate_id)->update(['dana_user_name' => $customerName]);

    // --- CATAT KE TABEL TRANSAKSI ---
    DB::table('dana_transactions')->insert([
        'affiliate_id' => $request->affiliate_id,
        'type' => 'INQUIRY',
        'reference_no' => $body['partnerReferenceNo'],
        'phone' => $cleanPhone,
        'amount' => 0,
        'status' => 'SUCCESS',
        'response_payload' => json_encode($result),
        'created_at' => now()
    ]);

    $pesanInquiry = "🛡️ *VERIFIKASI AKUN DANA*\n\n";
    $pesanInquiry .= "Sistem kami baru saja memverifikasi akun DANA Anda.\n";
    $pesanInquiry .= "Nama Terdaftar: *" . $customerName . "*\n";
    $pesanInquiry .= "Status: Akun Valid & Siap menerima pencairan profit.";

    $this->sendWhatsApp($cleanPhone, $pesanInquiry);

    return back()->with('success', '✅ Inquiry Sukses & Notifikasi WA Terkirim!');
}

    return back()->with('error', 'Gagal Inquiry: ' . ($result['responseMessage'] ?? 'Unknown Error'));

} catch (\Exception $e) {
    Log::error('[DANA INQUIRY] System Exception', ['message' => $e->getMessage()]);
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

}