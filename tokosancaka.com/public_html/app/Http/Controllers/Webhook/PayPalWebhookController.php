<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Api;

class PayPalWebhookController extends Controller
{
    /**
     * Menangani Webhook dari PayPal
     * Endpoint ini harus di-bypass dari CSRF Token
     */
    public function handleWebhook(Request $request)
    {
        // LOG LOG: Mencatat request masuk untuk debugging (bisa dihapus jika production sudah stabil)
        Log::info('PayPal Webhook Diterima', ['payload' => $request->all()]);

        // 1. Ambil Kredensial berdasarkan Mode Aktif dari Database
        $mode      = Api::getValue('PAYPAL_MODE', 'global', 'sandbox');
        $clientId  = Api::getValue('PAYPAL_CLIENT_ID', $mode);
        $secret    = Api::getValue('PAYPAL_SECRET', $mode);
        $webhookId = Api::getValue('PAYPAL_WEBHOOK_ID', $mode); // Diambil dari DB (Contoh: 8YA33094D2492333M)

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
        // Sesuai docs: sangat penting mengirimkan webhook_event persis seperti yang diterima
        $verificationPayload = [
            'transmission_id'   => $request->header('paypal-transmission-id'),
            'transmission_time' => $request->header('paypal-transmission-time'),
            'cert_url'          => $request->header('paypal-cert-url'),
            'auth_algo'         => $request->header('paypal-auth-algo'),
            'transmission_sig'  => $request->header('paypal-transmission-sig'),
            'webhook_id'        => $webhookId,
            'webhook_event'     => json_decode($request->getContent()) // decode menjadi object agar strukturnya tidak rusak saat di-encode ulang oleh Http Client
        ];

        $verifyResponse = Http::withToken($accessToken)
            ->post("$baseUrl/v1/notifications/verify-webhook-signature", $verificationPayload);

        $verificationResult = $verifyResponse->json('verification_status');

        if ($verificationResult !== 'SUCCESS') {
            Log::warning('PayPal Webhook Error: Signature Tidak Valid / Gagal Verifikasi', $verifyResponse->json());
            // Beri response 400 agar PayPal tahu ada yang salah (Opsional: return 200 jika tidak ingin di-retry oleh PayPal)
            return response()->json(['error' => 'Invalid Signature'], 400);
        }

        // 4. Proses Event Webhook
        $eventType = $request->input('event_type');
        $resource  = $request->input('resource');

        try {
            switch ($eventType) {
                case 'PAYMENT.CAPTURE.COMPLETED':
                    // LOG LOG: Pembayaran berhasil ditarik
                    $orderId = $resource['supplementary_data']['related_ids']['order_id'] ?? null;
                    $captureId = $resource['id'];
                    $amount = $resource['amount']['value'] ?? 0;
                    
                    Log::info("PayPal Payment Berhasil: Capture ID $captureId untuk Order $orderId sebesar $amount");
                    // TODO: Update status pesanan di database Sancaka Express menjadi LUNAS/PAID
                    
                    break;

                case 'PAYMENT.CAPTURE.DENIED':
                case 'PAYMENT.CAPTURE.PENDING':
                    // LOG LOG: Pembayaran tertunda atau ditolak
                    Log::info("PayPal Payment $eventType: " . $resource['id']);
                    // TODO: Update status pesanan menjadi PENDING / FAILED
                    
                    break;

                case 'PAYMENT.CAPTURE.REFUNDED':
                case 'PAYMENT.CAPTURE.REVERSED':
                    // LOG LOG: Dana dikembalikan
                    Log::info("PayPal Payment di-Refund: " . $resource['id']);
                    // TODO: Update status pesanan menjadi REFUNDED
                    
                    break;

                default:
                    Log::info("PayPal Webhook: Event tidak dihandle ($eventType)");
                    break;
            }

            // Sesuai Docs: Harus mengembalikan status HTTP 2xx agar PayPal berhenti melakukan retry
            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error('PayPal Webhook Processing Error: ' . $e->getMessage());
            // Jika ada error internal, kembalikan 500 agar PayPal mencoba lagi (retry) nanti
            return response()->json(['error' => 'Server Error'], 500);
        }
    }
}