<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\DanaSignatureService;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DanaPaymentController extends Controller
{
    protected $signatureService;

    public function __construct(DanaSignatureService $signatureService)
    {
        $this->signatureService = $signatureService;
    }

    public function createOrder()
    {
        // Setup Data
        $method = 'POST';
        // Contoh path API DANA (sesuaikan dengan endpoint yang ingin dituju, misal v1.0/order)
        $relativePath = '/v1.0/order/create'; 
        
        // Format Timestamp ISO 8601
        $timestamp = Carbon::now()->toIso8601String();
        
        // Body Request (Sesuai dokumentasi DANA untuk create order)
        $body = [
            'partnerReferenceNo' => 'ORDER-' . Str::random(10),
            'amount' => [
                'value' => '10000.00',
                'currency' => 'IDR'
            ],
            // ... tambahkan parameter lain sesuai kebutuhan
        ];

        // Generate Signature
        try {
            $signature = $this->signatureService->generateSignature(
                $method, 
                $relativePath, 
                $body, 
                $timestamp
            );
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        // Kirim Request ke DANA
        $response = Http::withHeaders([
            'X-PARTNER-ID' => config('services.dana.client_id'),
            'X-EXTERNAL-ID' => Str::random(16), // ID unik per request
            'X-TIMESTAMP'  => $timestamp,
            'X-SIGNATURE'  => $signature,
            'Content-Type' => 'application/json',
        ])->post(config('services.dana.base_url') . $relativePath, $body);

        return $response->json();
    }
}