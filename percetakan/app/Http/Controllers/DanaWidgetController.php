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
        // KITA "FORCE" LOG AGAR KELIHATAN SEMUA DI POSTMAN
        try {
            // 1. Generate ID Unik (Biar tidak kena error duplikat transaksi)
            $orderId     = 'TEST-' . time(); // Pake prefix TEST biar jelas
            $returnUrl   = route('dana.return');
            $expiryTime  = \Carbon\Carbon::now('Asia/Jakarta')->addMinutes(60)->format('Y-m-d\TH:i:sP');

            // 2. BODY INI 100% COPY-PASTE DARI DOKUMEN GAPURA (REQUEST SAMPLE)
            // Hanya kita ganti ID Merchant & Nominal agar valid di akun Anda.
            $bodyArray = [
                "partnerReferenceNo" => $orderId,
                "merchantId" => config('services.dana.merchant_id'), // Pakai ID asli Anda
                "amount" => [
                    "value" => "10000.00", // Hardcode string biar aman
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
                // BAGIAN INI SANGAT KRUSIAL MENURUT DOKUMEN
                "additionalInfo" => [
                    "mcc" => "5732", // Kode Toko Elektronik (Default Sample)
                    "order" => [
                        "merchantTransType" => "type", // JANGAN UBAH (Sesuai Sample)
                        "orderTitle" => "Payment Gateway Order",
                        "scenario" => "REDIRECT",
                        "goods" => [
                            [
                                "unit" => "Kg",
                                "category" => "travelling/subway",
                                "price" => [
                                    "value" => "10000.00",
                                    "currency" => "IDR"
                                ],
                                "merchantShippingId" => "SHIP-001",
                                "merchantGoodsId" => "GOODS-001",
                                "description" => "Test Item Description",
                                "snapshotUrl" => "http://snap.url.com",
                                "quantity" => "1",
                                "extendInfo" => ""
                            ]
                        ]
                    ],
                    "envInfo" => [
                        "sourcePlatform" => "IPG",
                        "orderOsType" => "IOS", // Ikuti sample
                        "merchantAppVersion" => "1.0",
                        "terminalType" => "SYSTEM",
                        "orderTerminalType" => "WEB",
                        // Kita coba inject parameter debug jika didukung
                        "extendInfo" => "{\"deviceId\":\"test-device-id\"}"
                    ]
                ]
            ];

            $jsonBody = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            
            // Endpoint Wajib Gapura
            $method = 'POST';
            $relativePath = '/payment-gateway/v1.0/debit/payment-host-to-host.htm'; 
            $timestamp = \Carbon\Carbon::now('Asia/Jakarta')->toIso8601String();

            // Signature
            $signature = $this->danaSignature->generateSignature($method, $relativePath, $jsonBody, $timestamp);
            
            $fullUrl = 'https://api.sandbox.dana.id' . $relativePath;
            $externalId = \Illuminate\Support\Str::random(32);

            // 3. KIRIM REQUEST (HEADER SESUAI DOKUMEN)
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-PARTNER-ID' => config('services.dana.client_id'),
                'X-EXTERNAL-ID' => $externalId,
                'X-TIMESTAMP'  => $timestamp,
                'X-SIGNATURE'  => $signature,
                'Content-Type' => 'application/json',
                // PENTING: Dokumen minta '95221' (Angka), bukan text.
                'CHANNEL-ID'   => '95221', 
            ])
            ->withBody($jsonBody, 'application/json')
            ->post($fullUrl);

            // 4. BALIKAN APA ADANYA KE ANDA (RAW JSON)
            // Biar kita lihat error message aslinya.
            return response()->json([
                'status_sent' => $response->status(),
                'headers_sent' => [
                    'channel_id' => '95221',
                    'timestamp' => $timestamp
                ],
                'body_sent' => $bodyArray,
                'dana_response' => $response->json()
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'CRITICAL_ERROR' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
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
}