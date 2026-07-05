<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiDuitkuController extends Controller
{
    protected $merchantCode;
    protected $apiKey;
    protected $env;
    protected $baseUrl;

    public function __construct()
    {
        $this->merchantCode = env('DUITKU_MERCHANT_CODE');
        $this->apiKey = env('DUITKU_API_KEY');
        $this->env = env('DUITKU_ENV', 'sandbox');

        // Menentukan Base URL berdasarkan Environment
        $this->baseUrl = $this->env === 'production'
            ? 'https://passport.duitku.com/webapi/api/merchant'
            : 'https://sandbox.duitku.com/webapi/api/merchant';
    }

    /**
     * Mendapatkan daftar metode pembayaran yang aktif
     */
    public function getPaymentMethods($amount = 10000)
    {
        $datetime = date('Y-m-d H:i:s');
        $stringToSign = $this->merchantCode . $amount . $datetime;
        $signature = hash_hmac('sha256', $stringToSign, $this->apiKey);

        $params = [
            'merchantcode' => $this->merchantCode,
            'amount' => $amount,
            'datetime' => $datetime,
            'signature' => $signature
        ];

        $response = Http::post($this->baseUrl . '/paymentmethod/getpaymentmethod', $params);

        return $response->json();
    }

    /**
     * Membuat permintaan transaksi (Inquiry)
     */
    public function createTransaction($orderId, $amount, $paymentMethod, $customerDetail, $itemDetails, $productDetails, $returnUrl = null, $callbackUrl = null)
    {
        $stringToSign = $this->merchantCode . $orderId . $amount;
        $signature = hash_hmac('sha256', $stringToSign, $this->apiKey);

        $params = [
            'merchantCode' => $this->merchantCode,
            'paymentAmount' => $amount,
            'paymentMethod' => $paymentMethod,
            'merchantOrderId' => $orderId,
            'productDetails' => $productDetails,
            'email' => $customerDetail['email'],
            'phoneNumber' => $customerDetail['phoneNumber'] ?? '',
            'customerVaName' => $customerDetail['firstName'] . ' ' . $customerDetail['lastName'],
            'itemDetails' => $itemDetails,
            'customerDetail' => $customerDetail,

            // Dinamis dengan fallback default ke routing aplikasi Sancaka
            'callbackUrl' => $callbackUrl ?? url('/api/duitku/callback'),
            'returnUrl' => $returnUrl ?? route('customer.topup.show', ['topup' => $orderId]),

            'signature' => $signature,
            'expiryPeriod' => 1440 // Opsional: dalam menit (24 Jam)
        ];

        $response = Http::post($this->baseUrl . '/v2/inquiry', $params);

        if ($response->successful()) {
            return $response->json(); // Mengembalikan paymentUrl, vaNumber, dll.
        }

        Log::error('Duitku Create Transaction Error: ' . $response->body());
        return false;
    }

    /**
     * Memeriksa status transaksi
     */
    public function checkTransaction($orderId)
    {
        $stringToSign = $this->merchantCode . $orderId;
        $signature = hash_hmac('sha256', $stringToSign, $this->apiKey);

        $params = [
            'merchantCode' => $this->merchantCode,
            'merchantOrderId' => $orderId,
            'signature' => $signature
        ];

        // Duitku Check Transaction menggunakan x-www-form-urlencoded
        $response = Http::asForm()->post($this->baseUrl . '/transactionStatus', $params);

        return $response->json();
    }

    /**
     * Menangani Webhook/Callback dari Duitku
     */
    public function handleCallback(Request $request)
    {
        $merchantCode = $request->input('merchantCode');
        $amount = $request->input('amount');
        $merchantOrderId = $request->input('merchantOrderId');
        $signature = $request->input('signature');
        $resultCode = $request->input('resultCode'); // 00 = Success, 01 = Failed
        $reference = $request->input('reference');

        if (!$merchantCode || !$amount || !$merchantOrderId || !$signature) {
            return response()->json(['message' => 'Bad Parameter'], 400);
        }

        // Validasi Signature Callback
        $stringToSign = $merchantCode . $amount . $merchantOrderId;
        $calcSignature = hash_hmac('sha256', $stringToSign, $this->apiKey);

        if ($signature === $calcSignature) {

            // ==========================================================
            // LOGIKA UPDATE DATABASE SANCAKA
            // ==========================================================
            try {
                if ($resultCode == '00') {
                    Log::info("Pembayaran Duitku Sukses: Order {$merchantOrderId}");
                    // Panggil prosesor inti TopUpController untuk menambah saldo
                    \App\Http\Controllers\Customer\TopUpController::processTopUp($merchantOrderId, 'PAID', $amount);
                } else {
                    Log::info("Pembayaran Duitku Gagal/Dibatalkan: Order {$merchantOrderId}");
                    // Panggil prosesor inti untuk menggagalkan transaksi
                    \App\Http\Controllers\Customer\TopUpController::processTopUp($merchantOrderId, 'FAILED', $amount);
                }
            } catch (\Exception $e) {
                Log::error('Duitku Callback Execution Error: ' . $e->getMessage());
                return response()->json(['message' => 'Internal Server Error'], 500);
            }

            // Duitku mewajibkan HTTP 200 OK untuk menandakan callback berhasil diterima
            return response()->json(['message' => 'Success'], 200);
        }

        Log::warning('Duitku Callback Bad Signature', $request->all());
        return response()->json(['message' => 'Bad Signature'], 403);
    }
}
