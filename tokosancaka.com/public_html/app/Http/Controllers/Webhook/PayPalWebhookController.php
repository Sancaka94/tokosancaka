<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Api;

// Import CheckoutController untuk memanggil processOrderCallback
use App\Http\Controllers\CheckoutController;

class PayPalWebhookController extends Controller
{
    /**
     * Menangani Webhook dari PayPal
     * Endpoint ini harus di-bypass dari CSRF Token (routes/api.php atau VerifyCsrfToken.php)
     */
    public function handleWebhook(Request $request)
    {
        // LOG LOG: Mencatat request masuk untuk debugging
        Log::info('LOG LOG: PayPal Webhook Diterima', ['event' => $request->input('event_type')]);

        // 1. Ambil Kredensial berdasarkan Mode Aktif dari Database
        $mode      = Api::getValue('PAYPAL_MODE', 'global', 'sandbox');
        $clientId  = Api::getValue('PAYPAL_CLIENT_ID', $mode);
        $secret    = Api::getValue('PAYPAL_SECRET_1', $mode); // Pastikan mengambil secret_1
        $webhookId = Api::getValue('PAYPAL_WEBHOOK_ID', $mode); 

        $baseUrl = ($mode === 'production') ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

        // 2. Dapatkan Access Token dari PayPal
        $tokenResponse = Http::withBasicAuth($clientId, $secret)
            ->asForm()
            ->post("$baseUrl/v1/oauth2/token", [
                'grant_type' => 'client_credentials'
            ]);

        if (!$tokenResponse->successful()) {
            Log::error('PayPal Webhook Error: Gagal mendapatkan Access Token', $tokenResponse->json());
            return response()->json(['error' => 'Internal Authorization Failed'], 500);
        }

        $accessToken = $tokenResponse->json('access_token');

        // 3. Verifikasi Autentisitas Webhook (Postback Method)
        $verificationPayload = [
            'transmission_id'   => $request->header('paypal-transmission-id'),
            'transmission_time' => $request->header('paypal-transmission-time'),
            'cert_url'          => $request->header('paypal-cert-url'),
            'auth_algo'         => $request->header('paypal-auth-algo'),
            'transmission_sig'  => $request->header('paypal-transmission-sig'),
            'webhook_id'        => $webhookId,
            'webhook_event'     => json_decode($request->getContent()) 
        ];

        // Bypass verifikasi sementara JIKA webhook_id belum diset (hanya untuk testing awal)
        if ($webhookId) {
            $verifyResponse = Http::withToken($accessToken)
                ->post("$baseUrl/v1/notifications/verify-webhook-signature", $verificationPayload);

            $verificationResult = $verifyResponse->json('verification_status');

            if ($verificationResult !== 'SUCCESS') {
                Log::warning('PayPal Webhook Error: Signature Tidak Valid', $verifyResponse->json());
                // Return 200 agar PayPal tidak spam retry jika ini adalah error setting dari kita
                return response()->json(['error' => 'Invalid Signature'], 200); 
            }
        } else {
            Log::warning('LOG LOG: Verifikasi Signature Webhook dilewati karena Webhook ID belum diisi di database.');
        }

        // 4. Proses Event Webhook
        $eventType = $request->input('event_type');
        $resource  = $request->input('resource');

        try {
            switch ($eventType) {
                case 'CHECKOUT.ORDER.APPROVED':
                    // LOG LOG: Buyer sudah klik bayar, saatnya sistem Sancaka menarik dana (Capture)
                    $orderId = $resource['id'];
                    Log::info("LOG LOG: PayPal Order Approved (ID: $orderId). Melakukan Auto-Capture dana...");

                    // Panggil layanan PayPal untuk mencairkan uang ke akun Sancaka
                    $paypalService = app(\App\Http\Controllers\Api\PayPalGatewayController::class);
                    $captureResponse = $paypalService->captureOrder($orderId);
                    $captureResult = $captureResponse->getData(true);

                    // Jika penarikan dana berhasil
                    if (isset($captureResult['success']) && $captureResult['success'] === true) {
                        Log::info("LOG LOG: Capture sukses! Dana berhasil masuk ke PayPal.");
                        
                        // Ambil nomor invoice (berada di dalam array purchase_units untuk event ini)
                        $invoiceNumber = $resource['purchase_units'][0]['custom_id'] ?? null;
                        
                        if ($invoiceNumber) {
                            Log::info("LOG LOG: Webhook PAYPAL Lunas! Meneruskan invoice $invoiceNumber ke CheckoutController");
                            app(CheckoutController::class)->processOrderCallback($invoiceNumber, 'PAID', $captureResult);
                        } else {
                            Log::error("PayPal Webhook Auto-Capture: custom_id (Nomor Invoice) tidak ditemukan.");
                        }
                    } else {
                        Log::error("LOG LOG: Gagal melakukan Auto-Capture pada Order $orderId", $captureResult);
                    }
                    break;

                case 'PAYMENT.CAPTURE.COMPLETED':
                    // Jika dana ditarik lewat jalur lain (misal dari frontend saat user di-redirect balik)
                    $captureId = $resource['id'];
                    $amount = $resource['amount']['value'] ?? 0;
                    $invoiceNumber = $resource['custom_id'] ?? null;
                    
                    Log::info("PayPal Payment Berhasil: Capture ID $captureId sebesar USD $amount");

                    if ($invoiceNumber) {
                        // Mesin CheckoutController punya pelindung anti double-booking. 
                        // Jadi aman meski terpanggil 2 kali.
                        app(CheckoutController::class)->processOrderCallback($invoiceNumber, 'PAID', $resource);
                    }
                    break;

                case 'PAYMENT.CAPTURE.DENIED':
                case 'PAYMENT.CAPTURE.PENDING':
                    Log::info("PayPal Payment $eventType: " . $resource['id']);
                    break;

                case 'PAYMENT.CAPTURE.REFUNDED':
                case 'PAYMENT.CAPTURE.REVERSED':
                    Log::info("PayPal Payment di-Refund: " . $resource['id']);
                    break;

                default:
                    Log::info("PayPal Webhook: Event tidak dihandle ($eventType)");
                    break;
            }

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error('PayPal Webhook Processing Error: ' . $e->getMessage());
            return response()->json(['error' => 'Server Error'], 500);
        }
    }
}