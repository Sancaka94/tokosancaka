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

            $this->logPayPalError('Authentication Failed', $response);
            return null;

        } catch (\Exception $e) {
            Log::error('PayPal Auth Exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * ==========================================
     * COLLECTION: ORDERS v2 API
     * ==========================================
     */

    /**
     * 1. Create Order (POST /v2/checkout/orders)
     * Ditambahkan dukungan payment_source & experience_context sesuai dokumentasi
     */
    public function createOrder(array $items, $amount, $invoiceNumber, $intent = 'CAPTURE', $returnUrl = null, $cancelUrl = null)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) return $this->unauthorizedResponse();

        $requestId = Str::uuid()->toString();
        
        // Format angka desimal wajib 2 digit di belakang koma untuk PayPal (misal: 10.00)
        $formattedAmount = number_format($amount, 2, '.', '');

        // Format standar payload Create Order
        $payload = [
            "intent" => strtoupper($intent), // CAPTURE atau AUTHORIZE
            "purchase_units" => [
                [
                    "reference_id" => "order_" . time(),
                    "custom_id" => $invoiceNumber, // Sangat krusial untuk webhook
                    "amount" => [
                        "currency_code" => "USD", // Sesuaikan mata uang
                        "value" => $formattedAmount,
                        // BOK INI YANG SEBELUMNYA KURANG:
                        "breakdown" => [
                            "item_total" => [
                                "currency_code" => "USD",
                                "value" => $formattedAmount
                            ]
                        ]
                    ],
                    "items" => $items 
                ]
            ],
            // Injeksi UX Halaman Pembayaran PayPal
            "payment_source" => [
                "paypal" => [
                    "experience_context" => [
                        "brand_name" => "Sancaka Express", // Tampil di atas form login PayPal
                        "shipping_preference" => "NO_SHIPPING", 
                        "user_action" => "PAY_NOW", // Langsung bayar tanpa review 2x di web kita
                        "return_url" => $returnUrl ?? url('/payment/success'),
                        "cancel_url" => $cancelUrl ?? url('/payment/cancel')
                    ]
                ]
            ]
        ];

        try {
            $response = Http::withHeaders([
                'Authorization'     => "Bearer {$accessToken}",
                'Content-Type'      => 'application/json',
                'PayPal-Request-Id' => $requestId, 
            ])->post("{$this->baseUrl}/v2/checkout/orders", $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Cari URL persetujuan (Approve URL)
                $approveUrl = collect($responseData['links'])->firstWhere('rel', 'approve')['href'] 
                           ?? collect($responseData['links'])->firstWhere('rel', 'payer-action')['href'] 
                           ?? null;

                return response()->json([
                    'success'      => true,
                    'order_id'     => $responseData['id'],
                    'status'       => $responseData['status'],
                    'approve_url'  => $approveUrl,
                    'raw_response' => $responseData
                ], 201);
            }

            return $this->handleErrorResponse($response);

        } catch (\Exception $e) {
            return $this->exceptionResponse('Create Order Exception', $e);
        }
    }

    /**
     * 2. Confirm Payment Source (POST /v2/checkout/orders/{id}/confirm-payment-source)
     * Mengizinkan pembeli mengganti atau mengonfirmasi sumber dana spesifik (Venmo, Card, dll)
     */
    public function confirmPaymentSource($orderId, $paymentSourceData)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) return $this->unauthorizedResponse();

        try {
            // $paymentSourceData berisi spesifikasi 'paypal', 'venmo', atau 'card'
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type'  => 'application/json',
            ])->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/confirm-payment-source", [
                "payment_source" => $paymentSourceData
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'details' => $response->json()
                ], 200);
            }

            return $this->handleErrorResponse($response);

        } catch (\Exception $e) {
            return $this->exceptionResponse('Confirm Payment Source Exception', $e);
        }
    }

    /**
     * 3. Capture Order (POST /v2/checkout/orders/{id}/capture)
     */
    public function captureOrder($orderId)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) return $this->unauthorizedResponse();

        $requestId = Str::uuid()->toString();

        try {
            $response = Http::withHeaders([
                'Authorization'     => "Bearer {$accessToken}",
                'Content-Type'      => 'application/json',
                'PayPal-Request-Id' => $requestId,
            ])->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture");

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'status'  => $response->json('status'),
                    'details' => $response->json()
                ], 200);
            }

            return $this->handleErrorResponse($response);

        } catch (\Exception $e) {
            return $this->exceptionResponse('Capture Order Exception', $e);
        }
    }

    /**
     * 4. Authorize Order (POST /v2/checkout/orders/{id}/authorize)
     */
    public function authorizeOrder($orderId)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) return $this->unauthorizedResponse();

        $requestId = Str::uuid()->toString();

        try {
            $response = Http::withHeaders([
                'Authorization'     => "Bearer {$accessToken}",
                'Content-Type'      => 'application/json',
                'PayPal-Request-Id' => $requestId,
            ])->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/authorize");

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'status'  => $response->json('status'),
                    'details' => $response->json()
                ], 200);
            }

            return $this->handleErrorResponse($response);

        } catch (\Exception $e) {
            return $this->exceptionResponse('Authorize Order Exception', $e);
        }
    }

    /**
     * 5. Capture Authorized Payment (POST /v2/payments/authorizations/{auth_id}/capture)
     * Mengambil dana yang ditahan (Authorize) dari endpoint pembayaran spesifik
     */
    public function captureAuthorizedPayment($authorizationId)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) return $this->unauthorizedResponse();

        $requestId = Str::uuid()->toString();

        try {
            $response = Http::withHeaders([
                'Authorization'     => "Bearer {$accessToken}",
                'Content-Type'      => 'application/json',
                'PayPal-Request-Id' => $requestId,
            ])->post("{$this->baseUrl}/v2/payments/authorizations/{$authorizationId}/capture", json_decode('{}', true));

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'status'  => $response->json('status'),
                    'details' => $response->json()
                ], 200);
            }

            return $this->handleErrorResponse($response);

        } catch (\Exception $e) {
            return $this->exceptionResponse('Capture Auth Payment Exception', $e);
        }
    }

    /**
     * 6. Show Order Details (GET /v2/checkout/orders/{id})
     */
    public function showOrderDetails($orderId)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) return $this->unauthorizedResponse();

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type'  => 'application/json',
            ])->get("{$this->baseUrl}/v2/checkout/orders/{$orderId}");

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'details' => $response->json()
                ], 200);
            }

            return $this->handleErrorResponse($response);

        } catch (\Exception $e) {
            return $this->exceptionResponse('Show Order Exception', $e);
        }
    }

    /**
     * 7. Shipment Tracking (POST /v2/checkout/orders/{id}/track)
     */
    public function addShipmentTracking($orderId, array $trackingData)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) return $this->unauthorizedResponse();

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type'  => 'application/json',
            ])->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/track", $trackingData);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'details' => $response->json()
                ], 201);
            }

            return $this->handleErrorResponse($response);

        } catch (\Exception $e) {
            return $this->exceptionResponse('Tracking Order Exception', $e);
        }
    }

    /**
     * ==========================================
     * ERROR HANDLING & HELPERS
     * ==========================================
     */
    private function handleErrorResponse($response)
    {
        $statusCode = $response->status();
        $errorData = $response->json();

        $this->logPayPalError("API Call Failed", $response);

        return response()->json([
            'success'     => false,
            'status_code' => $statusCode,
            'error'       => $errorData['name'] ?? 'API_ERROR',
            'message'     => $errorData['message'] ?? $this->getDefaultMessageForStatus($statusCode),
            'details'     => $errorData['details'] ?? [],
            'debug_id'    => $errorData['debug_id'] ?? null
        ], $statusCode);
    }

    private function logPayPalError($context, $response)
    {
        $errorData = $response->json() ?? [];
        Log::error("PayPal [{$response->status()}]: {$context}", [
            'name'     => $errorData['name'] ?? 'UNKNOWN',
            'message'  => $errorData['message'] ?? 'No message provided',
            'details'  => $errorData['details'] ?? [],
            'debug_id' => $errorData['debug_id'] ?? null
        ]);
    }

    private function unauthorizedResponse()
    {
        return response()->json([
            'success' => false,
            'error'   => 'INVALID_CLIENT',
            'message' => 'Gagal mendapatkan otentikasi PayPal. Periksa Client ID atau Secret.'
        ], 401);
    }

    private function exceptionResponse($context, \Exception $e)
    {
        Log::error("PayPal {$context}", ['message' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'error'   => 'INTERNAL_SERVER_ERROR',
            'message' => 'Terjadi kesalahan sistem atau network timeout saat menghubungi PayPal.',
        ], 500);
    }

    private function getDefaultMessageForStatus($statusCode)
    {
        return match ($statusCode) {
            400 => 'Request tidak valid secara sintaksis.',
            401 => 'Gagal otentikasi. Kredensial mungkin salah atau kadaluarsa.',
            403 => 'Tidak memiliki izin untuk mengakses resource ini.',
            404 => 'Resource yang diminta tidak ditemukan.',
            422 => 'Gagal validasi bisnis dari PayPal.',
            429 => 'Terlalu banyak request. Terkena Rate Limit.',
            500 => 'Internal Server Error pada PayPal.',
            default => 'Terjadi kesalahan yang tidak diketahui.'
        };
    }
}