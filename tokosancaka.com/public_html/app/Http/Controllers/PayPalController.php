<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Api; // Panggil model Api Anda

class PayPalController extends Controller
{
    // Mengambil konfigurasi PayPal secara dinamis dari database sesuai kode Anda
    private function getPayPalConfig()
    {
        $mode = Api::getValue('PAYPAL_MODE', 'global', 'sandbox');
        
        return [
            'mode'      => $mode,
            'client_id' => Api::getValue('PAYPAL_CLIENT_ID', $mode),
            // Menggunakan secret_1 sebagai Client Secret standar API REST PayPal
            'secret'    => Api::getValue('PAYPAL_SECRET_1', $mode), 
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
            
            // Kalkulasi nominal dari keranjang di sini
            $orderPayload = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => 'USD',
                            'value' => '100.00' 
                        ]
                    ]
                ]
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
            $config = $this->getPayPalConfig();
            $accessToken = $this->generateAccessToken($config);

            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($config['base_url'] . "/v2/checkout/orders/{$orderId}/capture");

            $data = $response->json();
            Log::info("LOG LOG: Pembayaran berhasil di-capture");
            
            return response()->json($data);
        } catch (\Exception $e) {
            Log::error("LOG LOG: Gagal capture order", ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}