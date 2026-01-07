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
        Log::info('========== DANA GAPURA PAYMENT (STRICT SAMPLE MATCH) ==========');

        $orderId = 'INV-' . time();
        // Use a realistic amount
        $amount  = '10000.00'; 
        
        $returnUrl = route('dana.return');

        // 1. Calculate Expiry Time (Required by ValidUpTo)
        // Format: YYYY-MM-DDTHH:mm:ss+07:00
        $expiryTime = Carbon::now('Asia/Jakarta')->addMinutes(60)->format('Y-m-d\TH:i:sP');

        // 2. Construct Body EXACTLY like the "Request Sample Gapura Hosted Checkout"
        $bodyArray = [
            "partnerReferenceNo" => $orderId,
            "merchantId" => config('services.dana.merchant_id'),
            
            "amount" => [
                "value" => $amount,
                "currency" => "IDR"
            ],
            
            // [REQUIRED] From documentation
            "validUpTo" => $expiryTime,
            
            // [REQUIRED] URL Parameters
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
            
            // [CRITICAL FIX] Structure MUST match the Sample JSON
            "additionalInfo" => [
                "order" => [
                    "merchantTransType" => "type", // From sample
                    "orderTitle" => "Payment for " . $orderId, // Required field
                    "scenario" => "REDIRECT", // Important for Hosted Checkout
                    "goods" => [
                        [
                            "description" => "Item Digital",
                            "price" => [
                                "value" => $amount,
                                "currency" => "IDR"
                            ],
                            "quantity" => "1",
                            "unit" => "pcs",
                            "merchantGoodsId" => "ITEM-001",
                            "category" => "digital"
                        ]
                    ]
                ],
                "envInfo" => [
                    "sourcePlatform" => "IPG", // From sample
                    "terminalType" => "SYSTEM", // From sample (Was missing before)
                    "orderTerminalType" => "WEB", // From sample
                    "websiteLanguage" => "id_ID"
                ]
            ]
        ];

        $jsonBody = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        Log::info('Request Body: ' . $jsonBody);

        $method = 'POST';
        // Endpoint from your documentation
        $relativePath = '/payment-gateway/v1.0/debit/payment-host-to-host.htm'; 
        
        $timestamp = Carbon::now('Asia/Jakarta')->toIso8601String();

        try {
            $signature = $this->danaSignature->generateSignature($method, $relativePath, $jsonBody, $timestamp);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Signature Error'], 500);
        }

        $fullUrl = 'https://api.sandbox.dana.id' . $relativePath;
        $externalId = Str::random(32);

        try {
            Log::info('Hitting Endpoint: ' . $fullUrl);
            
            $response = Http::withHeaders([
                'X-PARTNER-ID' => config('services.dana.client_id'),
                'X-EXTERNAL-ID' => $externalId,
                'X-TIMESTAMP'  => $timestamp,
                'X-SIGNATURE'  => $signature,
                'Content-Type' => 'application/json',
                'CHANNEL-ID'   => 'MOBILE_WEB', // Standard for Web
            ])
            ->withBody($jsonBody, 'application/json')
            ->post($fullUrl);

            Log::info('DANA Response:', $response->json());
            
            $result = $response->json();

            // Check for Success (2005400)
            if (isset($result['responseCode']) && $result['responseCode'] == '2005400') {
                 Log::info('Success! Redirecting user...');
                 
                 // Get Redirect URL
                 $redirectUrl = $result['webRedirectUrl'] ?? null;
                 
                 if($redirectUrl) {
                    return redirect($redirectUrl);
                 }
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('HTTP Failed: ' . $e->getMessage());
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