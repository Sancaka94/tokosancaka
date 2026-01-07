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

    // =========================================================================
    // DANA DISBURSEMENT (KIRIM UANG) - TESTING SCENARIOS
    // =========================================================================

    // [SCENARIO 1] CHECK ACCOUNT VALIDITY (Wajib untuk Checklist Dashboard)
    // Route: /dana/test-inquiry
    // [SCENARIO 1] CHECK ACCOUNT VALIDITY (REVISI)
    public function disburseAccountInquiry()
    {
        Log::info('========== DANA ACCOUNT INQUIRY TEST (NO FUND TYPE) ==========');

        // REVISI 1: Gunakan format 08... kembali
        $phoneNumber = '085745808809'; 
        
        $bodyArray = [
            "partnerReferenceNo" => 'INQ-' . time(),
            "amount" => [
                "value" => "1000.00",
                "currency" => "IDR"
            ],
            "customerNumber" => $phoneNumber,
            
            // REVISI 2: Hapus additionalInfo/fundType dulu
            // "additionalInfo" => [
            //    "fundType" => "TRANS_TO_USER"
            // ]
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
}