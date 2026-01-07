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
        // Setup Data
        // Kita pakai prefix baru 'TRY-' biar fresh
        $orderId     = 'TRY-' . time();
        $returnUrl   = route('dana.return');
        
        // Waktu: Format ISO8601 Wajib ada Timezone (+07:00)
        $expiryTime  = \Carbon\Carbon::now('Asia/Jakarta')->addMinutes(60)->format('Y-m-d\TH:i:sP');

        // BODY "JALAN TENGAH"
        // 1. Ada MCC (Wajib biar ga error 400)
        // 2. Tidak ada Goods (Biar ga error 500 karena salah hitung harga)
        $bodyArray = [
            "partnerReferenceNo" => $orderId,
            "merchantId" => config('services.dana.merchant_id'),
            "amount" => [
                "value" => "10000.00", // String, 2 desimal
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
                // [PENTING] Kembalikan MCC ini. 
                // Tanpa ini, kena 400 Invalid Field.
                "mcc" => "5732", 
                
                "order" => [
                    "orderTitle" => "Invoice " . $orderId,
                    "merchantTransType" => "01", // Kita coba "01" (Sales)
                    "scenario" => "REDIRECT",
                    // Goods kita kosongkan dulu untuk menghindari Error 500
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
                // Tetap gunakan 95221 karena ini sudah terbukti lolos validasi header
                'CHANNEL-ID'   => '95221', 
            ])
            ->withBody($jsonBody, 'application/json')
            ->post($fullUrl);

            return response()->json($response->json());

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
}