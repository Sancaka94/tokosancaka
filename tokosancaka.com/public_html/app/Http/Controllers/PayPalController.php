<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Api;
use App\Models\Pesanan;
use App\Models\PesananAutokirim;
use App\Models\AutoKirim;

class PayPalController extends Controller
{
    private function getPayPalConfig()
    {
        $mode = Api::getValue('PAYPAL_MODE', 'global', 'sandbox');

        return [
            'mode'      => $mode,
            'client_id' => Api::getValue('PAYPAL_CLIENT_ID', $mode),
            'secret'    => Api::getValue('PAYPAL_SECRET_1', $mode) ?: Api::getValue('PAYPAL_SECRET', $mode),
            'base_url'  => $mode === 'production'
                           ? 'https://api-m.paypal.com'
                           : 'https://api-m.sandbox.paypal.com'
        ];
    }

    private function generateAccessToken($config)
    {
        $response = Http::asForm()
            ->withBasicAuth($config['client_id'], $config['secret'])
            ->post($config['base_url'] . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials'
            ]);

        return $response->json('access_token');
    }

    public function createOrder(Request $request)
    {
        try {
            Log::info("LOG LOG: Memulai pembuatan order PayPal dari database config");
            $config = $this->getPayPalConfig();
            $accessToken = $this->generateAccessToken($config);

            $amountUsd = $request->input('amount', '100.00');
            $nomorInvoice = $request->input('nomor_invoice');

            $purchaseUnit = [
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => number_format((float) $amountUsd, 2, '.', '')
                ]
            ];

            if (!empty($nomorInvoice)) {
                $purchaseUnit['reference_id'] = $nomorInvoice;
                $purchaseUnit['custom_id'] = $nomorInvoice;
            }

            $orderPayload = [
                'intent' => 'CAPTURE',
                'purchase_units' => [$purchaseUnit]
            ];

            $response = Http::withToken($accessToken)
                ->post($config['base_url'] . '/v2/checkout/orders', $orderPayload);

            $data = $response->json();
            Log::info("LOG LOG: Order berhasil dibuat", ['order_id' => $data['id'] ?? '']);

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error("LOG LOG: Gagal membuat order", ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function captureOrder(Request $request, $orderId)
    {
        try {
            Log::info("LOG LOG: Menangkap (capture) order {$orderId}");
            $data = $this->captureAndProcess($orderId, $request->input('nomor_invoice'));
            Log::info("LOG LOG: Pembayaran berhasil di-capture");

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error("LOG LOG: Gagal capture order", ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Eksekusi Capture PayPal + Tembak API KiriminAja / Autokirim jika COMPLETED
     */
    public function captureAndProcess($orderId, $fallbackInvoice = null)
    {
        $config = $this->getPayPalConfig();
        $accessToken = $this->generateAccessToken($config);

        $response = Http::withToken($accessToken)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->withBody('{}', 'application/json')
            ->post($config['base_url'] . "/v2/checkout/orders/{$orderId}/capture");

        $data = $response->json();

        // Jika sudah pernah di-capture sebelumnya, cek detail order agar tetap terproses
        if (($data['details'][0]['issue'] ?? '') === 'ORDER_ALREADY_CAPTURED') {
            $checkRes = Http::withToken($accessToken)
                ->get($config['base_url'] . "/v2/checkout/orders/{$orderId}");
            $data = $checkRes->json();
        }

        if (($data['status'] ?? '') === 'COMPLETED') {
            $nomorInvoice = $data['purchase_units'][0]['reference_id']
                ?? $data['purchase_units'][0]['custom_id']
                ?? $fallbackInvoice;

            if ($nomorInvoice) {
                $this->triggerLogisticsBooking($nomorInvoice, $orderId);
            }
        }

        return $data;
    }

    /**
     * Webhook Backup PayPal (Event: PAYMENT.CAPTURE.COMPLETED)
     */
    public function webhook(Request $request)
    {
        $payload = $request->all();
        $eventType = $payload['event_type'] ?? '';
        Log::info("LOG LOG: Webhook PayPal diterima", ['event_type' => $eventType]);

        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            $resource = $payload['resource'] ?? [];
            $nomorInvoice = $resource['custom_id']
                ?? $resource['invoice_id']
                ?? null;

            $paypalOrderId = $resource['supplementary_data']['related_ids']['order_id']
                ?? ($resource['id'] ?? 'WEBHOOK');

            if ($nomorInvoice) {
                $this->triggerLogisticsBooking($nomorInvoice, $paypalOrderId);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Hubungkan ke PesananController (KiriminAja) atau PesananAutokirimController
     */
    private function triggerLogisticsBooking($nomorInvoice, $paypalOrderId)
    {
        // 1. Cek Pesanan Reguler (KiriminAja)
        $pesanan = Pesanan::where('nomor_invoice', $nomorInvoice)->first();
        if ($pesanan) {
            if (empty($pesanan->payment_method) || strtoupper($pesanan->payment_method) !== 'PAYPAL') {
                $pesanan->payment_method = 'PAYPAL';
                $pesanan->save();
            }

            \App\Http\Controllers\Admin\PesananController::processPesananCallback(
                $pesanan->nomor_invoice,
                'PAID',
                [
                    'source'          => 'PAYPAL_API',
                    'paypal_order_id' => $paypalOrderId
                ]
            );
            return;
        }

        // 2. Cek Pesanan Autokirim
        $autokirim = PesananAutokirim::where('order_id', $nomorInvoice)->first();
        if ($autokirim && empty($autokirim->awb_number) && strtolower($autokirim->status) !== 'booking_created') {
            try {
                $origin = AutoKirim::where('district_id', $autokirim->pengirim_district_id)
                    ->orWhere('zip', trim($autokirim->pengirim_kodepos))
                    ->first();
                $destination = AutoKirim::where('district_id', $autokirim->penerima_district_id)
                    ->orWhere('zip', trim($autokirim->penerima_kodepos))
                    ->first();

                if ($origin && $destination) {
                    $autokirimCtrl = new \App\Http\Controllers\PesananAutokirimController();
                    $awbResult = $autokirimCtrl->_executeAutokirimApi($autokirim, $origin, $destination, null);

                    $autokirim->awb_number = $awbResult['awb'] ?? null;
                    $autokirim->tlc_code = $awbResult['reff_2'] ?? null;
                    $autokirim->reff_1 = $awbResult['reff_1'] ?? null;
                    $autokirim->pickup_point_code = $awbResult['pickup'] ?? null;
                    $autokirim->status = 'booking_created';
                    $autokirim->metode_pembayaran = 'PAYPAL';
                    $autokirim->save();
                }
            } catch (\Exception $e) {
                Log::error("LOG LOG: Gagal tembak Autokirim dari PayPal ({$nomorInvoice}): " . $e->getMessage());
            }
        }
    }
}
