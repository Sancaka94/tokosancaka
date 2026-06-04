<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Api;

class PayPalGatewayController extends Controller
{
    protected $mode;
    protected $baseUrl;
    protected $clientId;
    protected $secret;

    public function __construct()
    {
        $this->initializeConfig();
    }

    /**
     * Mengambil konfigurasi dinamis berdasarkan Mode (Sandbox / Production)
     */
    private function initializeConfig()
    {
        $this->mode = Api::getValue('PAYPAL_MODE', 'global', 'sandbox');
        $this->clientId = Api::getValue('PAYPAL_CLIENT_ID', $this->mode);
        $this->secret = Api::getValue('PAYPAL_SECRET_1', $this->mode);

        $this->baseUrl = ($this->mode === 'production') 
            ? 'https://api-m.paypal.com' 
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * Generate OAuth 2.0 Access Token
     */
    private function getAccessToken()
    {
        try {
            $response = Http::withBasicAuth($this->clientId, $this->secret)
                ->asForm()
                ->post("{$this->baseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials'
                ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            // Tangkap error otentikasi (401 Unauthorized / 400 Bad Request)
            $this->logPayPalError('Authentication Failed', $response);
            return null;

        } catch (\Exception $e) {
            Log::error('PayPal Auth Exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Create Order API Call (Metode POST + Idempotency Key)
     */
    public function createOrder(array $orderData)
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'error' => 'INVALID_CLIENT',
                'message' => 'Gagal mendapatkan otentikasi PayPal. Periksa Client ID atau Secret.'
            ], 401); // 401 Unauthorized
        }

        // Generate unik Request-Id untuk Idempotency seperti instruksi dokumentasi
        $requestId = Str::uuid()->toString();

        try {
            $response = Http::withHeaders([
                'Authorization'     => "Bearer {$accessToken}",
                'Content-Type'      => 'application/json',
                'Accept'            => 'application/json',
                'PayPal-Request-Id' => $requestId, // Mencegah duplikasi data jika 5xx/Timeout
            ])->post("{$this->baseUrl}/v2/checkout/orders", $orderData);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Parsing HATEOAS Links untuk mendapatkan URL Approval
                $approveUrl = null;
                if (isset($responseData['links'])) {
                    foreach ($responseData['links'] as $link) {
                        if ($link['rel'] === 'approve') {
                            $approveUrl = $link['href'];
                            break;
                        }
                    }
                }

                return response()->json([
                    'success' => true,
                    'status_code' => $response->status(), // Biasanya 201 Created
                    'order_id' => $responseData['id'],
                    'status' => $responseData['status'],
                    'approve_url' => $approveUrl, // Frontend bisa gunakan link ini untuk redirect user
                    'raw_response' => $responseData
                ], $response->status());
            }

            // Menangani Error 4xx dan 5xx sesuai dokumentasi
            return $this->handleErrorResponse($response);

        } catch (\Exception $e) {
            Log::error('PayPal API Timeout/Exception', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => 'INTERNAL_SERVER_ERROR',
                'message' => 'Terjadi kesalahan sistem atau network timeout saat menghubungi PayPal.',
            ], 500);
        }
    }

    /**
     * Standardisasi HTTP Error Parsing berdasar Dokumentasi PayPal
     */
    private function handleErrorResponse($response)
    {
        $statusCode = $response->status();
        $errorData = $response->json();

        // 1. Catat ke Log dengan format yang terstruktur
        $this->logPayPalError("API Call Failed", $response);

        // 2. Format Response untuk dikembalikan ke Frontend/Client
        return response()->json([
            'success' => false,
            'status_code' => $statusCode,
            'error'   => $errorData['name'] ?? 'API_ERROR',
            'message' => $errorData['message'] ?? $this->getDefaultMessageForStatus($statusCode),
            'details' => $errorData['details'] ?? [], // Detail validasi jika ada (misal Error 422/400)
            'debug_id'=> $errorData['debug_id'] ?? null
        ], $statusCode);
    }

    /**
     * Helper Logging Error
     */
    private function logPayPalError($context, $response)
    {
        $statusCode = $response->status();
        $errorData = $response->json();

        Log::error("PayPal [{$statusCode}]: {$context}", [
            'name' => $errorData['name'] ?? 'UNKNOWN',
            'message' => $errorData['message'] ?? 'No message provided',
            'details' => $errorData['details'] ?? [],
            'debug_id' => $errorData['debug_id'] ?? null
        ]);
    }

    /**
     * Pesan bawaan berdasarkan HTTP Status Codes (Fallback)
     */
    private function getDefaultMessageForStatus($statusCode)
    {
        return match ($statusCode) {
            400 => 'Request is not well-formed, syntactically incorrect, or violates schema.',
            401 => 'Authentication failed due to invalid credentials.',
            403 => 'Authorization failed due to insufficient permissions.',
            404 => 'The specified resource does not exist.',
            422 => 'The request action is semantically incorrect or fails business validation.',
            429 => 'Too many requests. Blocked due to rate limiting.',
            500 => 'An internal server error has occurred on PayPal.',
            default => 'Terjadi kesalahan yang tidak diketahui.'
        };
    }
}