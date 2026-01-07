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
        Log::info('========== DANA WIDGET PAYMENT START ==========');

        $orderId = 'INV-' . time();
        $amount  = '1000.00'; 
        $returnUrl = route('dana.return');

        // 1. Setup Array Body
        $bodyArray = [
            "partnerReferenceNo" => $orderId,
            "amount" => [
                "value" => $amount,
                "currency" => "IDR"
            ],
            "payOptionDetails" => [
                "payMethod" => "DANA_WALLET",
                "transType" => "PAGE",
            ],
            "additionalInfo" => [
                "origin" => "IS_WIDGET"
            ],
            "urlParams" => [
                "url" => $returnUrl,
                "type" => "NOTIFICATION"
            ]
        ];

        // [FIX PENTING] 
        // Encode manual agar URL tidak berubah jadi https:\/\/
        // Kita gunakan string hasil encode ini untuk SIGN dan KIRIM. Harus konsisten 100%.
        $jsonBody = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        Log::info('Request Body (Raw String): ' . $jsonBody);

        $method = 'POST';
        // Path sesuai dokumentasi image_985920.png
        $relativePath = '/rest/redirection/v1.0/debit/payment-host-to-host'; 
        
        $timestamp = Carbon::now()->toIso8601String();

        try {
            // Generate Signature pakai string JSON yang sudah dipastikan formatnya
            $signature = $this->danaSignature->generateSignature($method, $relativePath, $jsonBody, $timestamp);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }

        $fullUrl = config('services.dana.base_url') . $relativePath;
        $externalId = Str::random(32);

        Log::info('Hitting Endpoint: ' . $fullUrl);

        try {
            // [FIX PENTING] Kirim sebagai "Body Raw" (String), jangan biarkan Http Client meng-encode ulang
            $response = Http::withHeaders([
                'X-PARTNER-ID' => config('services.dana.client_id'),
                'X-EXTERNAL-ID' => $externalId,
                'X-TIMESTAMP'  => $timestamp,
                'X-SIGNATURE'  => $signature,
                'Content-Type' => 'application/json',
                'CHANNEL-ID'   => 'MOBILE_WEB', 
            ])
            ->withBody($jsonBody, 'application/json') // Kirim string yang sama persis
            ->post($fullUrl);

            Log::info('DANA Response Code: ' . $response->status());
            
            $result = $response->json();

            // Cek sukses (Biasanya 2000000 atau 2005300)
            if (isset($result['responseCode']) && substr($result['responseCode'], 0, 3) == '200') {
                 Log::info('Success! Redirecting user...');
                 $redirectUrl = $result['webRedirectUrl'] ?? $result['redirectUrl'];
                 return redirect($redirectUrl);
            }

            Log::warning('DANA Failed:', $result);
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