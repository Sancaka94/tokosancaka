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

    /**
     * FUNGSI 1: MEMBUAT PEMBAYARAN (WIDGET PAYMENT)
     * Endpoint: /rest/redirection/v1.0/debit/payment-host-to-host
     * Sumber: image_985920.png
     */
    public function createPayment(Request $request)
    {
        // 1. Setup Data
        // Gunakan 'INV-' agar terlihat profesional
        $orderId     = 'INV-' . time();
        $returnUrl   = route('dana.return');
        $expiryTime  = \Carbon\Carbon::now('Asia/Jakarta')->addMinutes(60)->format('Y-m-d\TH:i:sP');

        // 2. Body "WINNING FORMULA" (MCC Ada, Goods Hilang)
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
                "mcc" => "5732", // Wajib
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
        $timestamp = \Carbon\Carbon::now('Asia/Jakarta')->toIso8601String();

        try {
            $signature = $this->danaSignature->generateSignature($method, $relativePath, $jsonBody, $timestamp);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Signature Error'], 500);
        }

        $fullUrl = 'https://api.sandbox.dana.id' . $relativePath;
        $externalId = \Illuminate\Support\Str::random(32);

        try {
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

            $result = $response->json();

            // [PRODUCTION MODE] Redirect User ke DANA
            if (isset($result['responseCode']) && $result['responseCode'] == '2005400') {
                 $redirectUrl = $result['webRedirectUrl'] ?? null;
                 if($redirectUrl) {
                    return redirect($redirectUrl); // <--- INI PERUBAHANNYA
                 }
            }

            // Kalau gagal, baru tampilkan JSON error
            return response()->json($result);

        } catch (\Exception $e) {
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
                'X-PARTNER-ID' => config('services.dana.client_id'),
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

    // [SCENARIO 2] EXECUTE TOP UP (Wajib untuk Checklist Dashboard)
    // Route: /dana/test-topup
    public function disburseTopUp()
    {
        Log::info('========== DANA TOPUP TEST ==========');

        $phoneNumber = '6285745808809'; // Ganti dengan No HP Sandbox Anda
        $orderId     = 'TOPUP-' . time();

        $bodyArray = [
            "partnerReferenceNo" => $orderId,
            "amount" => [
                "value" => "1000.00",
                "currency" => "IDR"
            ],
            // Fee Amount Wajib Ada
            "feeAmount" => [
                "value" => "0.00",
                "currency" => "IDR"
            ],
            "customerNumber" => $phoneNumber,
            "additionalInfo" => [
                "fundType" => "TRANS_TO_USER"
            ]
        ];

        Log::info("CREATED ORDER ID: " . $orderId);

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

    public function initiateBinding(Request $request)
    {
        Log::info('========== DANA BINDING INITIATED ==========');

        // 1. Setup Data Dasar
        $clientId    = config('services.dana.client_id');
        $redirectUrl = route('dana.callback'); // URL Callback kita
        $state       = \Illuminate\Support\Str::random(16); // CSRF Protection
        $timestamp   = \Carbon\Carbon::now('Asia/Jakarta')->toIso8601String();

        // 2. Setup Seamless Data (Agar No HP User otomatis terisi)
        // Di Production, ambil dari Auth::user()->phone
        // Di Sandbox, GUNAKAN NOMOR MAGIC: 08123456789
        $userPhone   = '08123456789'; 
        $userExtId   = 'USER-' . time(); // ID User di database Anda

        $seamlessDataArray = [
            "mobileNumber" => $userPhone,
            "bizScenario"  => "PAYMENT", // Sesuai sample
            "externalUid"  => $userExtId,
            "verifiedTime" => $timestamp
        ];
        
        // Convert ke JSON String
        $seamlessDataStr = json_encode($seamlessDataArray);

        // 3. Generate Seamless Signature
        // Proses: Sign(SHA256withRSA) -> Base64
        $seamlessSign = $this->generateSeamlessSign($seamlessDataStr);

        // 4. Susun Query Parameters
        $queryParams = [
            'partnerId'     => $clientId,
            'timestamp'     => $timestamp,
            'externalId'    => $userExtId,
            'channelId'     => '95221', // Channel ID Web
            'merchantId'    => config('services.dana.merchant_id'),
            'redirectUrl'   => $redirectUrl,
            'state'         => $state,
            'scopes'        => 'DEFAULT_BASIC_PROFILE,QUERY_BALANCE,MINI_DANA,AGREEMENT_PAY', // Scopes lengkap
            'seamlessData'  => $seamlessDataStr,
            'seamlessSign'  => $seamlessSign,
            'allowRegistration' => 'true'
        ];

        // 5. Build Full URL
        // Sandbox Base URL untuk Web Redirect biasanya berbeda dengan API
        // Tapi untuk Widget API biasanya: https://m.sandbox.dana.id/d/portal/oauth
        // ATAU cek dokumentasi spesifik URL entry pointnya. 
        // Berdasarkan endpoint docs: GET /v1.0/get-auth-code
        // Kita tembak ke API Gateway dulu, nanti dia yang kasih redirect.
        
        $baseUrl = 'https://api.sandbox.dana.id/v1.0/get-auth-code';
        $fullRedirectUrl = $baseUrl . '?' . http_build_query($queryParams);

        Log::info("Generated Binding URL: " . $fullRedirectUrl);

        // Redirect User ke DANA
        return redirect($fullRedirectUrl);
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

    private function generateSeamlessSign($dataString)
    {
        // 1. Ambil Key Mentah
        $rawKey = config('services.dana.private_key');

        if (empty($rawKey)) {
            throw new \Exception("DANA Private Key belum diset di .env atau config/services.php");
        }

        // 2. BERSIHKAN KEY (Hapus Header, Footer, Spasi, Newline jika ada)
        // Kita buat jadi satu baris string murni dulu biar gampang diformat ulang
        $cleanKey = str_replace(
            ["-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----", "-----BEGIN RSA PRIVATE KEY-----", "-----END RSA PRIVATE KEY-----", "\r", "\n", " "],
            "",
            $rawKey
        );

        // 3. FORMAT ULANG JADI PEM STANDARD (Wajib 64 karakter per baris)
        $formattedKey = "-----BEGIN PRIVATE KEY-----\n" . 
                        wordwrap($cleanKey, 64, "\n", true) . 
                        "\n-----END PRIVATE KEY-----";

        // 4. VALIDASI KEY SEBELUM DIPAKAI
        // Ini akan mengecek apakah key valid. Jika error, kita tahu masalahnya di key.
        $privateKeyResource = openssl_pkey_get_private($formattedKey);

        if (!$privateKeyResource) {
            // Ambil detail error OpenSSL untuk debugging
            $errors = "";
            while ($msg = openssl_error_string()) {
                $errors .= $msg . "; ";
            }
            Log::error("OpenSSL Key Error: " . $errors);
            throw new \Exception("Format Private Key Salah! Cek Log untuk detail.");
        }

        // 5. SIGNING
        $binarySignature = '';
        if (!openssl_sign($dataString, $binarySignature, $privateKeyResource, OPENSSL_ALGO_SHA256)) {
            throw new \Exception("Gagal melakukan signing data.");
        }

        // 6. Encode Base64
        return base64_encode($binarySignature);
    }
}