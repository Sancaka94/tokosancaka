<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\DanaSignatureService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log; // Wajib import Log

class DanaWidgetController extends Controller
{
    protected $danaSignature;

    public function __construct(DanaSignatureService $danaSignature)
    {
        $this->danaSignature = $danaSignature;
    }

    public function createPayment(Request $request)
    {
        // 1. Log Awal - Menandakan proses dimulai
        Log::info('========== DANA CREATE PAYMENT START ==========');

        $orderId = 'INV-' . time();
        $amount  = '1000.00'; 
        $returnUrl = route('dana.return');

        // 2. Setup Body
        $body = [
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

        // LOG BODY
        Log::info('DANA Request Body:', $body);

        // 3. Setup Signature
        $method = 'POST';
        // Pastikan path ini sesuai dokumentasi DANA Anda. 
        // Jika SNAP, biasanya: /v1.0/debit/payment.host
        $relativePath = '/v1.0/debit/payment.host'; 
        
        $timestamp = Carbon::now()->toIso8601String();

        try {
            $signature = $this->danaSignature->generateSignature(
                $method, 
                $relativePath, 
                $body, 
                $timestamp
            );
            
            // LOG SIGNATURE BERHASIL
            Log::info('Signature Generated:', ['signature' => $signature]);

        } catch (\Exception $e) {
            // LOG ERROR SIGNATURE
            Log::error('Signature Generation Failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Gagal membuat signature: ' . $e->getMessage()], 500);
        }

        // 4. Kirim Request
        $fullUrl = config('services.dana.base_url') . $relativePath;
        $clientId = config('services.dana.client_id');
        $externalId = Str::random(32);

        // LOG HEADER & URL SEBELUM KIRIM
        Log::info('Sending Request to DANA:', [
            'url' => $fullUrl,
            'client_id' => $clientId,
            'external_id' => $externalId,
            'timestamp' => $timestamp
        ]);

        try {
            $response = Http::withHeaders([
                'X-PARTNER-ID' => $clientId,
                'X-EXTERNAL-ID' => $externalId,
                'X-TIMESTAMP'  => $timestamp,
                'X-SIGNATURE'  => $signature,
                'Content-Type' => 'application/json',
                'CHANNEL-ID'   => 'MOBILE_WEB', 
            ])->post($fullUrl, $body);

            // 5. DEBUG RESPONSE MENTAH (RAW)
            // Ini akan mencatat apapun balasan dari DANA, baik sukses maupun error
            Log::info('DANA Raw Response Code: ' . $response->status());
            Log::info('DANA Raw Response Body: ' . $response->body());

            // Coba parsing ke JSON
            $result = $response->json();

            // Jika JSON kosong/null, berarti respon bukan JSON (mungkin HTML error 404/500)
            if (is_null($result)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Respon dari DANA bukan JSON yang valid.',
                    'raw_body' => $response->body(),
                    'http_code' => $response->status()
                ], 500);
            }

            // Cek Logic Sukses SNAP (Code: 2005300)
            if (isset($result['responseCode']) && $result['responseCode'] == '2005300') {
                 Log::info('DANA Success Redirecting user...');
                 $redirectUrl = $result['webRedirectUrl'];
                 return redirect($redirectUrl);
            }

            // Jika DANA merespon tapi kode bukan sukses
            Log::warning('DANA Transaction Failed/Rejected:', $result);
            return response()->json($result);

        } catch (\Exception $e) {
            // LOG ERROR KONEKSI (Misal: DNS error, Timeout, SSL error)
            Log::error('HTTP Request Failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'FATAL_ERROR', 
                'message' => $e->getMessage(),
                'hint' => 'Cek koneksi internet server atau URL endpoint DANA.'
            ], 500);
        }
    }

    public function returnPage(Request $request)
    {
        Log::info('User Returned from DANA:', $request->all());
        
        $status = $request->query('status');
        $orderId = $request->query('originalPartnerReferenceNo');
        $danaRef = $request->query('originalReferenceNo');

        if ($status == 'SUCCESS') {
            return "<h1>Pembayaran Berhasil!</h1><p>Order ID: $orderId</p><p>DANA Ref: $danaRef</p>";
        } else {
            return "<h1>Pembayaran Gagal / Dibatalkan</h1><p>Status: $status</p>";
        }
    }
}