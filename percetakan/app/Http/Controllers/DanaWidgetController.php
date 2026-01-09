<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\DanaSignatureService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DanaWidgetController extends Controller
{
    protected $danaSignature;

    public function __construct(DanaSignatureService $danaSignature)
    {
        $this->danaSignature = $danaSignature;
    }

  public function createPayment(Request $request)
{
    \Illuminate\Support\Facades\Log::info('DANA_H2H_START: Memulai pembuatan order.');

    // 1. Setup Data menggunakan Config Baru
    $orderId     = 'INV-' . time();
    $returnUrl   = route('dana.return');
    $timestamp   = \Carbon\Carbon::now('Asia/Jakarta')->toIso8601String();
    $expiryTime  = \Carbon\Carbon::now('Asia/Jakarta')->addMinutes(60)->format('Y-m-d\TH:i:sP');

    \Illuminate\Support\Facades\Log::info('DANA_H2H_SETUP', [
        'order_id' => $orderId,
        'merchant_id' => config('services.dana.merchant_id')
    ]);

    // 2. Body "WINNING FORMULA" (Struktur yang kmrn jalan)
    $bodyArray = [
        "partnerReferenceNo" => $orderId,
        "merchantId" => config('services.dana.merchant_id'),
        "amount" => [
            "value" => "10000.00",
            "currency" => "IDR"
        ],
        "validUpTo" => $expiryTime,
        "urlParams" => [
            [
                "url" => $returnUrl,
                "type" => "PAY_RETURN",
                "isDeeplink" => "Y"
            ],
            [
                "url" => $returnUrl,
                "type" => "NOTIFICATION",
                "isDeeplink" => "Y"
            ]
        ],
        "additionalInfo" => [
            "mcc" => "5732", 
            "order" => [
                "orderTitle" => "Invoice " . $orderId,
                "merchantTransType" => "01",
                "scenario" => "REDIRECT",
            ],
            "envInfo" => [
                "sourcePlatform" => "IPG",
                "terminalType" => "SYSTEM",
                "orderTerminalType" => "WEB",
            ]
        ]
    ];

    $jsonBody = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $method = 'POST';
    $relativePath = '/payment-gateway/v1.0/debit/payment-host-to-host.htm'; 

    // 3. Signature Generation
    try {
        // Ambil Access Token dulu (B2B) karena config baru mewajibkan Bearer Token
        $accessToken = $this->danaSignature->getAccessToken();
        
        // Generate signature (Pastikan service Anda sudah update menggunakan RSA Asymmetric)
        $signature = $this->danaSignature->generateSignature($method, $relativePath, $jsonBody, $timestamp);
        
        \Illuminate\Support\Facades\Log::info('DANA_H2H_SIGNATURE_SUCCESS');
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('DANA_H2H_SIGNATURE_FAILED', ['msg' => $e->getMessage()]);
        return response()->json(['error' => 'Signature/Auth Error'], 500);
    }

    // 4. Hit API DANA Sandbox
    $fullUrl = (config('services.dana.dana_env') == 'PRODUCTION' ? 'https://api.dana.id' : 'https://api.sandbox.dana.id') . $relativePath;
    $externalId = \Illuminate\Support\Str::random(32);

    try {
        \Illuminate\Support\Facades\Log::info('DANA_H2H_SENDING_REQUEST', ['url' => $fullUrl]);

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization'  => 'Bearer ' . $accessToken, // Tambahkan Bearer sesuai spek baru
            'X-PARTNER-ID'   => config('services.dana.x_partner_id'),
            'X-EXTERNAL-ID'  => $externalId,
            'X-TIMESTAMP'    => $timestamp,
            'X-SIGNATURE'    => $signature,
            'Content-Type'   => 'application/json',
            'CHANNEL-ID'     => '95221', 
            'ORIGIN'         => config('services.dana.origin'),
        ])
        ->withBody($jsonBody, 'application/json')
        ->post($fullUrl);

        $result = $response->json();

        \Illuminate\Support\Facades\Log::info('DANA_H2H_RESPONSE_RECEIVED', [
            'status' => $response->status(),
            'body' => $result
        ]);

        // 5. Redirect User ke DANA jika Sukses
        if (isset($result['responseCode']) && $result['responseCode'] == '2005400') {
            $redirectUrl = $result['webRedirectUrl'] ?? null;
            if($redirectUrl) {
                \Illuminate\Support\Facades\Log::info('DANA_H2H_REDIRECTING', ['url' => $redirectUrl]);
                return redirect($redirectUrl);
            }
        }

        \Illuminate\Support\Facades\Log::error('DANA_H2H_FAILED_PROCESS', $result);
        return response()->json($result, $response->status());

    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('DANA_H2H_HTTP_ERROR', ['msg' => $e->getMessage()]);
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
    

    /**
     * FUNGSI 2: CEK STATUS PEMBAYARAN (QUERY PAYMENT)
     * Endpoint: /rest/v1.1/debit/status
     * Sumber: image_985920.png
     */
    public function checkStatus($orderId)
    {
        Log::info("Checking Status for Order: $orderId");

        // Body untuk Check Status biasanya hanya butuh Partner Reference No
        $body = [
            "partnerReferenceNo" => $orderId,
            "merchantId" => config('services.dana.merchant_id')
        ];

        $method = 'POST';
        // Menggunakan endpoint dari image_985920.png (Query Payment)
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
            
            return $response->json();

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    // Halaman Return (User kembali setelah bayar)
    public function returnPage(Request $request)
    {
        $status = $request->query('status');
        $orderId = $request->query('originalPartnerReferenceNo');
        
        return "<h1>Status Pembayaran: $status</h1><p>Order ID: $orderId</p>
                <br><a href='".route('dana.status', $orderId)."'>Cek Status Detail via API</a>";
    }

    public function handleNotify(Request $request)
    {
        Log::info('========== DANA WEBHOOK INCOMING ==========');
        Log::info('Headers:', $request->headers->all());
        Log::info('Body:', $request->all());

        // 1. Ambil Data Penting dari Body
        $orderId = $request->input('originalPartnerReferenceNo'); // Order ID kita (misal: INV-1767...)
        $status  = $request->input('latestTransactionStatus');    // 00 = Success, 05 = Cancel
        $amount  = $request->input('amount.value');               // Nominal

        // 2. Cek Signature (Opsional tapi disarankan untuk Production)
        // Di Sandbox, kita bisa skip dulu atau log saja.
        $incomingSignature = $request->header('X-SIGNATURE');
        
        // 3. Update Status di Database Anda
        // Logika sederhana:
        if ($status == '00') {
            Log::info("Order $orderId BERHASIL dibayar (Rp $amount).");
            
            // TODO: Update database Anda di sini
            // $order = Order::where('invoice_number', $orderId)->first();
            // if ($order) {
            //     $order->status = 'PAID';
            //     $order->save();
            // }

        } elseif ($status == '05') {
            Log::warning("Order $orderId DIBATALKAN/EXPIRED.");
            
            // TODO: Update database jadi Cancelled
            // $order->status = 'CANCELLED';
            // $order->save();
        } else {
            Log::warning("Status Transaksi Lainnya: $status");
        }

        // 4. Return Response Wajib DANA
        // Kode 2005600 artinya kita sukses menerima notifikasi
        return response()->json([
            'responseCode' => '2005600',
            'responseMessage' => 'Successful'
        ])->withHeaders([
            // DANA kadang mewajibkan timestamp di header response
            'X-TIMESTAMP' => \Carbon\Carbon::now('Asia/Jakarta')->toIso8601String()
        ]);
    }

 
    public function disburseAccountInquiry()
    {
        Log::info('========== DANA ACCOUNT INQUIRY TEST (FORMAT 8...) ==========');

        // REVISI: Coba hapus '0' atau '62' di depan. Langsung angka 8.
        // Asumsi nomor asli: 085745808809 -> Jadi: 85745808809
        $phoneNumber = '85745808809'; 
        
        $bodyArray = [
            "partnerReferenceNo" => 'INQ-' . time(),
            "amount" => [
                "value" => "1000.00",
                "currency" => "IDR"
            ],
            "customerNumber" => $phoneNumber,
            
            // WAJIB DIKEMBALIKAN (Biar ga error 500)
            "additionalInfo" => [
                "fundType" => "TRANS_TO_USER"
            ]
        ];

        return $this->sendDanaRequest('POST', '/v1.0/emoney/account-inquiry.htm', $bodyArray);
    }

    // =========================================================================
    // DISBURSEMENT TOP UP - SCENARIO TESTING MACHINE
    // =========================================================================
    public function disburseTopUp()
    {
        Log::info('========== DANA TOPUP SCENARIO TEST ==========');

        // =====================================================================
        // [AREA EDIT DISINI] - GANTI NILAI INI UNTUK SETIAP CHECKLIST
        // =====================================================================
        
        // PILIH SKENARIO: 'SUCCESS', 'NO_SALDO', 'REPEAT_FAIL', 'ERROR_GENERAL'
        $scenario = 'SUCCESS'; 

        // 1. SET NOMOR HP (Gunakan Magic Number Sandbox)
        $phoneNumber = '08123456789'; 

        // 2. SET ORDER ID & AMOUNT SESUAI SKENARIO
        if ($scenario == 'SUCCESS') {
            // Skenario 1: Sukses Normal
            $orderId = 'TOPUP-' . time(); 
            $amount  = '1000.00'; 

        } elseif ($scenario == 'NO_SALDO') {
            // Skenario 2: Error Insufficient Fund (Saldo Kurang)
            $orderId = 'FAIL-SALDO-' . time();
            $amount  = '999999999999.00'; // Nominal Raksasa (Biar saldo merchant kurang)

        } elseif ($scenario == 'REPEAT_FAIL') {
            // Skenario 3: Error Inconsistent Request (Order ID Sama, Data Beda)
            // CARA PAKAI: Jalankan sekali, lalu ubah amount, jalankan lagi tanpa ubah ID
            $orderId = 'FIXED-ID-TEST-123'; // <--- ID INI JANGAN UBAH
            $amount  = '2000.00'; // Ubah jadi 3000.00 pada request kedua

        } elseif ($scenario == 'ERROR_GENERAL') {
            // Skenario 4: General Error / Internal Server Error
            $orderId = 'ERR-' . time();
            $amount  = '1000.00';
            $phoneNumber = '000000'; // Nomor Ngawur
        }
        
        // =====================================================================
        // JANGAN UBAH KODE DI BAWAH INI
        // =====================================================================

        Log::info("TESTING SCENARIO: $scenario | OrderID: $orderId | Amount: $amount");

        $bodyArray = [
            "partnerReferenceNo" => $orderId,
            "amount" => [
                "value" => $amount,
                "currency" => "IDR"
            ],
            "feeAmount" => [
                "value" => "0.00",
                "currency" => "IDR"
            ],
            "customerNumber" => $phoneNumber,
            "additionalInfo" => [
                "fundType" => "TRANS_TO_USER"
            ]
        ];

        return $this->sendDanaRequest('POST', '/v1.0/emoney/topup.htm', $bodyArray);
    }

    // [SCENARIO 3] CHECK STATUS (Wajib untuk Checklist Dashboard)
    // Route: /dana/test-status?order_id=TOPUP-xxxx
    public function disburseCheckStatus(Request $request)
    {
        Log::info('========== DANA CHECK STATUS TEST ==========');

        $originalOrderId = $request->query('order_id');

        if (!$originalOrderId) {
            return response()->json(['error' => 'Harap masukkan parameter ?order_id=TOPUP-xxxx di URL'], 400);
        }

        $bodyArray = [
            "partnerReferenceNo" => 'CHK-' . time(), 
            "originalPartnerReferenceNo" => $originalOrderId, 
            "merchantId" => config('services.dana.merchant_id'),
        ];

        return $this->sendDanaRequest('POST', '/v1.0/emoney/topup-status.htm', $bodyArray);
    }

    // =========================================================================
    // HELPER FUNCTION (Agar kodingan tidak berulang-ulang)
    // =========================================================================
    // HELPER: Mengirim Request ke DANA (Versi dengan Log Response)
    private function sendDanaRequest($method, $relativePath, $bodyArray)
    {
        $jsonBody = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $timestamp = \Carbon\Carbon::now('Asia/Jakarta')->toIso8601String();

        try {
            $signature = $this->danaSignature->generateSignature($method, $relativePath, $jsonBody, $timestamp);
            $fullUrl = 'https://api.sandbox.dana.id' . $relativePath;
            $externalId = \Illuminate\Support\Str::random(32);

            Log::info("Hitting Endpoint: $relativePath");
            Log::info("Request Body: $jsonBody"); // Log apa yang dikirim

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-PARTNER-ID' => config('services.dana.client_id'),
                'X-EXTERNAL-ID' => $externalId,
                'X-TIMESTAMP'  => $timestamp,
                'X-SIGNATURE'  => $signature,
                'Content-Type' => 'application/json',
                'CHANNEL-ID'   => '95221', 
            ])
            ->withBody($jsonBody, 'application/json')
            ->post($fullUrl);

            // [TAMBAHAN] Log Balasan dari DANA
            Log::info("DANA Response Code: " . $response->status());
            Log::info("DANA Response Body: " . $response->body());

            return response()->json($response->json());

        } catch (\Exception $e) {
            Log::error("DANA HTTP Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // ACCOUNT BINDING (OAUTH 2.0)
    // =========================================================================

    // =========================================================================
    // ACCOUNT BINDING (FINAL VERSION - V2 DEEPLINK)
    // =========================================================================

    public function initiateBinding(Request $request)
    {
        Log::info('========== DANA BINDING INITIATED (V2 SEAMLESS) ==========');

        $clientId    = config('services.dana.client_id');
        $redirectUrl = route('dana.callback'); 
        $state       = \Illuminate\Support\Str::random(16); 
        $timestamp   = \Carbon\Carbon::now('Asia/Jakarta')->toIso8601String();

        // 1. DATA USER UNTUK SEAMLESS (AUTO-FILL)
        // Gunakan nomor akun Sandbox Anda dengan format 62
        // Contoh: 085745808809 -> 6285745808809
        $userPhone   = '6285745808809'; 
        $userExtId   = 'USER-' . time();

        // Struktur Seamless Data sesuai contoh Anda
        $seamlessDataArray = [
            "mobileNumber" => $userPhone, // Perhatikan: mobileNumber (bukan mobile)
            "bizScenario"  => "PAYMENT",
            "verifiedTime" => $timestamp,
            "externalUid"  => $userExtId,
            // "deviceId"     => "1234567890" // Opsional, boleh dikosongkan jika tidak ada
        ];
        
        // Encode JSON (Pastikan urutan tidak berubah, JSON mentah yang disign)
        $seamlessDataStr = json_encode($seamlessDataArray);

        // 2. GENERATE TANDA TANGAN (SIGNATURE)
        // PENTING: DANA memvalidasi ini. Jika key/algoritma salah = Error "Terjadi Kesalahan"
        try {
            $seamlessSign = $this->generateSeamlessSign($seamlessDataStr);
        } catch (\Exception $e) {
            Log::error("Signing Error: " . $e->getMessage());
            return response()->json(['error' => 'Gagal membuat tanda tangan: ' . $e->getMessage()], 500);
        }

        // 3. PARAMETER URL (Sesuai contoh URL Anda)
        $queryParams = [
            'timestamp'     => $timestamp,
            'partnerId'     => $clientId,
            'externalId'    => $userExtId,
            'channelId'     => '95221', // ID untuk Web/Wap
            'state'         => $state,
            'scopes'        => 'AGREEMENT_PAY,MINI_DANA,QUERY_BALANCE,DEFAULT_BASIC_PROFILE', // Scope lengkap
            'redirectUrl'   => $redirectUrl,
            'seamlessData'  => $seamlessDataStr,
            'seamlessSign'  => $seamlessSign,
            'merchantId'    => config('services.dana.merchant_id'),
            'lang'          => 'id',
            'allowRegistration' => 'true'
        ];

        // 4. ENDPOINT V2 (SANDBOX)
        // Menggunakan /n/link/binding sesuai referensi Anda
        $baseUrl = 'https://m.sandbox.dana.id/n/link/binding'; 
        
        $fullRedirectUrl = $baseUrl . '?' . http_build_query($queryParams);

        Log::info("Generated Binding URL V2: " . $fullRedirectUrl);

        return redirect($fullRedirectUrl);
    }

    // HELPER: GENERATE SIGNATURE (SHA256withRSA)
    // Fungsi ini sudah dilengkapi pembersih format Key agar tidak error "cannot be coerced"
    private function generateSeamlessSign($dataString)
    {
        // 1. Ambil Key dari Config
        $rawKey = config('services.dana.private_key');
        
        if (!$rawKey) {
            throw new \Exception("Private Key kosong. Cek .env Anda.");
        }

        // 2. Bersihkan Key dari header/footer/spasi/newline yang berantakan
        $cleanKey = str_replace(
            ["-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----", "\r", "\n", " "],
            "",
            $rawKey
        );

        // 3. Format ulang menjadi PEM standar (64 karakter per baris)
        $formattedKey = "-----BEGIN PRIVATE KEY-----\n" . 
                        wordwrap($cleanKey, 64, "\n", true) . 
                        "\n-----END PRIVATE KEY-----";

        // 4. Validasi Key
        $privateKeyResource = openssl_pkey_get_private($formattedKey);
        if (!$privateKeyResource) {
            throw new \Exception("Format Private Key Salah. Pastikan key di .env benar.");
        }

        // 5. Sign Data (SHA256)
        $binarySignature = '';
        if (!openssl_sign($dataString, $binarySignature, $privateKeyResource, OPENSSL_ALGO_SHA256)) {
            throw new \Exception("OpenSSL Sign Failed.");
        }

        // 6. Encode Base64 (Tanpa URL Encode di sini, karena http_build_query akan melakukannya otomatis)
        return base64_encode($binarySignature);
    }

    // Callback Sementara (Hanya untuk dump hasil)
    public function handleCallback(Request $request)
    {
        Log::info('========== DANA BINDING CALLBACK ==========');
        Log::info($request->all());

        return response()->json([
            'message' => 'Callback received',
            'data' => $request->all()
        ]);
    }

    // =========================================================================
    // BALANCE INQUIRY (CEK SALDO USER)
    // =========================================================================
    public function balanceInquiry()
    {
        Log::info('========== DANA BALANCE INQUIRY TEST ==========');

        // [WAJIB] Masukkan ACCESS TOKEN user yang valid di sini
        // Token ini didapat setelah menukar Auth Code (dari proses Binding).
        // Jika belum punya, kode ini akan return error 401/400.
        $accessToken = 'MASUKKAN_ACCESS_TOKEN_DISINI'; 

        $partnerRef = 'BAL-' . time();

        // BODY REQUEST (Sesuai contoh Anda)
        $bodyArray = [
            "partnerReferenceNo" => $partnerRef,
            "balanceTypes"       => ["BALANCE"],
            "additionalInfo"     => [
                "accessToken" => $accessToken
            ]
        ];

        $jsonBody = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        $method = 'POST';
        $relativePath = '/v1.0/balance-inquiry.htm'; 
        $timestamp = \Carbon\Carbon::now('Asia/Jakarta')->toIso8601String();

        try {
            // Generate Signature
            $signature = $this->danaSignature->generateSignature($method, $relativePath, $jsonBody, $timestamp);
            
            $fullUrl = 'https://api.sandbox.dana.id' . $relativePath;
            $externalId = \Illuminate\Support\Str::random(32);

            Log::info("Hitting Endpoint: $relativePath");
            Log::info("Using Access Token: " . substr($accessToken, 0, 10) . "...");

            // KIRIM REQUEST DENGAN HEADER KHUSUS
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-PARTNER-ID'  => config('services.dana.client_id'),
                'X-EXTERNAL-ID' => $externalId,
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'Content-Type'  => 'application/json',
                'CHANNEL-ID'    => '95221',
                
                // [HEADER WAJIB UNTUK CEK SALDO]
                'Authorization-Customer' => 'Bearer ' . $accessToken,
                'X-DEVICE-ID'   => 'DEVICE-' . time(), // ID Unik Device
            ])
            ->withBody($jsonBody, 'application/json')
            ->post($fullUrl);

            Log::info("Response Code: " . $response->status());
            Log::info("Response Body: " . $response->body());

            return response()->json($response->json());

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
}