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

    // Properti tambahan untuk kredensial Disbursement
    protected $disbursementUserId;
    protected $disbursementSecretKey;
    protected $disbursementBaseUrl;
    protected $cashOutBaseUrl;

    public function __construct()
    {
        $this->merchantCode = env('DUITKU_MERCHANT_CODE');
        $this->apiKey = env('DUITKU_API_KEY');
        $this->env = env('DUITKU_ENV', 'sandbox');

        // Menentukan Base URL berdasarkan Environment
        $this->baseUrl = $this->env === 'production'
            ? 'https://passport.duitku.com/webapi/api/merchant'
            : 'https://sandbox.duitku.com/webapi/api/merchant';

        // Kredensial khusus Disbursement (User ID & Secret Key berbeda dengan Payment)
        $this->disbursementUserId = env('DUITKU_DISBURSEMENT_USER_ID');
        $this->disbursementSecretKey = env('DUITKU_DISBURSEMENT_SECRET_KEY');

        // Base URL untuk Transfer Online & Clearing
        $this->disbursementBaseUrl = $this->env === 'production'
            ? 'https://passport.duitku.com/webapi/api/disbursement'
            : 'https://sandbox.duitku.com/webapi/api/disbursement';

        // Base URL khusus untuk Cash Out (Pos Indonesia & Indomaret)
        $this->cashOutBaseUrl = $this->env === 'production'
            ? 'https://disbursement.duitku.com/api/cashout'
            : 'https://disbursement-sandbox.duitku.com/api/cashout';
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

    /**
     * ==========================================================
     * VALIDASI ATURAN LIMIT DISBURSEMENT
     * ==========================================================
     */
    private function validateDisbursementLimit($amount, $method, $bankCode = null)
    {
        if ($method === 'ONLINE') {
            // RTOL & E-Wallet (Minimal 10.000)
            if ($amount < 10000) {
                throw new \Exception("Nominal transfer RTOL/E-Wallet minimal Rp 10.000.");
            }
            // Bank Permata (013) Limit 50 Juta, Selain itu 100 Juta
            if ($bankCode === '013' && $amount > 50000000) {
                throw new \Exception("Nominal transfer RTOL untuk Bank Permata maksimal Rp 50.000.000.");
            } elseif ($amount > 100000000) {
                throw new \Exception("Nominal transfer RTOL maksimal Rp 100.000.000.");
            }
        } elseif ($method === 'BIFAST') {
            if ($amount < 10000 || $amount > 250000000) {
                throw new \Exception("Nominal transfer BI FAST harus antara Rp 10.000 hingga Rp 250.000.000.");
            }
        } elseif ($method === 'LLG' || $method === 'SKN') {
            if ($amount < 10000 || $amount > 1000000000) {
                throw new \Exception("Nominal transfer LLG/SKN harus antara Rp 10.000 hingga Rp 1.000.000.000.");
            }
        } elseif ($method === 'RTGS') {
            if ($amount < 100000000) {
                throw new \Exception("Nominal transfer RTGS minimal Rp 100.000.000.");
            }
        } elseif ($method === 'CASHOUT') {
            if ($bankCode === '2010') { // Indomaret
                if ($amount < 50000 || $amount > 1000000 || $amount % 50000 !== 0) {
                    throw new \Exception("Nominal Cash Out Indomaret harus kelipatan Rp 50.000, minimal Rp 50.000, maksimal Rp 1.000.000.");
                }
            } elseif ($bankCode === '2011') { // Pos Indonesia
                if ($amount < 50000 || $amount > 2000000) {
                    throw new \Exception("Nominal Cash Out Pos Indonesia harus antara Rp 50.000 hingga Rp 2.000.000.");
                }
            }
        }
        return true;
    }

    /**
     * ==========================================================
     * FITUR DISBURSEMENT: UTILITAS UMUM
     * ==========================================================
     */
    public function checkDisbursementBalance($email)
    {
        $timestamp = round(microtime(true) * 1000);
        $signature = hash('sha256', $email . $timestamp . $this->disbursementSecretKey);

        $params = [
            'userId'    => (int) $this->disbursementUserId,
            'email'     => $email,
            'timestamp' => $timestamp,
            'signature' => $signature
        ];

        $response = Http::post($this->disbursementBaseUrl . '/checkbalance', $params);
        return $response->json();
    }

    public function getDisbursementBankList($email)
    {
        $timestamp = round(microtime(true) * 1000);
        $signature = hash('sha256', $email . $timestamp . $this->disbursementSecretKey);

        $params = [
            'userId'    => (int) $this->disbursementUserId,
            'email'     => $email,
            'timestamp' => $timestamp,
            'signature' => $signature
        ];

        $response = Http::post($this->disbursementBaseUrl . '/listBank', $params);
        return $response->json();
    }

    public function checkDisbursementStatus($disburseId, $email)
    {
        $timestamp = round(microtime(true) * 1000);
        $signature = hash('sha256', $email . $timestamp . $disburseId . $this->disbursementSecretKey);

        $params = [
            'disburseId' => $disburseId,
            'userId'     => (int) $this->disbursementUserId,
            'email'      => $email,
            'timestamp'  => $timestamp,
            'signature'  => $signature
        ];

        $response = Http::post($this->disbursementBaseUrl . '/inquirystatus', $params);
        return $response->json();
    }

    /**
     * ==========================================================
     * FITUR DISBURSEMENT 1: TRANSFER ONLINE
     * ==========================================================
     */
    public function createOnlineInquiry($amountTransfer, $bankAccount, $bankCode, $email, $purpose, $senderId = null, $senderName = null)
    {
        // Validasi Limit Internal sebelum melempar request ke API
        $this->validateDisbursementLimit($amountTransfer, 'ONLINE', $bankCode);

        $timestamp = round(microtime(true) * 1000);
        $signature = hash('sha256', $email . $timestamp . $bankCode . $bankAccount . $amountTransfer . $purpose . $this->disbursementSecretKey);

        $params = [
            'userId'         => (int) $this->disbursementUserId,
            'amountTransfer' => (int) $amountTransfer,
            'bankAccount'    => $bankAccount,
            'bankCode'       => $bankCode,
            'email'          => $email,
            'purpose'        => $purpose,
            'timestamp'      => $timestamp,
            'senderId'       => $senderId,
            'senderName'     => $senderName,
            'signature'      => $signature
        ];

        $endpoint = $this->env === 'production' ? '/inquiry' : '/inquirysandbox';
        $response = Http::post($this->disbursementBaseUrl . $endpoint, $params);

        return $response->json();
    }

    public function executeOnlineTransfer($disburseId, $amountTransfer, $bankAccount, $bankCode, $email, $accountName, $custRefNumber, $purpose)
    {
        $timestamp = round(microtime(true) * 1000);
        $signature = hash('sha256', $email . $timestamp . $bankCode . $bankAccount . $accountName . $custRefNumber . $amountTransfer . $purpose . $disburseId . $this->disbursementSecretKey);

        $params = [
            'disburseId'     => $disburseId,
            'userId'         => (int) $this->disbursementUserId,
            'email'          => $email,
            'bankCode'       => $bankCode,
            'bankAccount'    => $bankAccount,
            'amountTransfer' => (int) $amountTransfer,
            'accountName'    => $accountName,
            'custRefNumber'  => $custRefNumber,
            'purpose'        => $purpose,
            'timestamp'      => $timestamp,
            'signature'      => $signature
        ];

        $endpoint = $this->env === 'production' ? '/transfer' : '/transfersandbox';
        $response = Http::post($this->disbursementBaseUrl . $endpoint, $params);

        return $response->json();
    }

    /**
     * ==========================================================
     * FITUR DISBURSEMENT 2: CLEARING (LLG, RTGS, H2H, BIFAST)
     * ==========================================================
     */
    public function createClearingInquiry($amountTransfer, $bankAccount, $bankCode, $type, $email, $purpose, $custRefNumber = null, $senderId = null, $senderName = null)
    {
        // Validasi Limit Internal berdasarkan Tipe (RTGS, BIFAST, LLG, H2H)
        $this->validateDisbursementLimit($amountTransfer, $type, $bankCode);

        $timestamp = round(microtime(true) * 1000);
        $signature = hash('sha256', $email . $timestamp . $bankCode . $type . $bankAccount . $amountTransfer . $purpose . $this->disbursementSecretKey);

        $params = [
            'userId'         => (int) $this->disbursementUserId,
            'email'          => $email,
            'bankCode'       => $bankCode,
            'bankAccount'    => $bankAccount,
            'amountTransfer' => (int) $amountTransfer,
            'custRefNumber'  => $custRefNumber,
            'senderId'       => $senderId,
            'senderName'     => $senderName,
            'purpose'        => $purpose,
            'type'           => $type,
            'timestamp'      => $timestamp,
            'signature'      => $signature
        ];

        $endpoint = $this->env === 'production' ? '/inquiryclearing' : '/inquiryclearingsandbox';
        $response = Http::post($this->disbursementBaseUrl . $endpoint, $params);

        return $response->json();
    }

    public function executeClearingTransfer($disburseId, $amountTransfer, $bankAccount, $bankCode, $type, $email, $accountName, $custRefNumber, $purpose)
    {
        $timestamp = round(microtime(true) * 1000);
        $signature = hash('sha256', $email . $timestamp . $bankCode . $type . $bankAccount . $accountName . $custRefNumber . $amountTransfer . $purpose . $disburseId . $this->disbursementSecretKey);

        $params = [
            'disburseId'     => $disburseId,
            'userId'         => (int) $this->disbursementUserId,
            'email'          => $email,
            'bankCode'       => $bankCode,
            'bankAccount'    => $bankAccount,
            'amountTransfer' => (int) $amountTransfer,
            'accountName'    => $accountName,
            'custRefNumber'  => $custRefNumber,
            'type'           => $type,
            'purpose'        => $purpose,
            'timestamp'      => $timestamp,
            'signature'      => $signature
        ];

        $endpoint = $this->env === 'production' ? '/transferclearing' : '/transferclearingsandbox';
        $response = Http::post($this->disbursementBaseUrl . $endpoint, $params);

        return $response->json();
    }

    public function handleClearingCallback(Request $request)
    {
        $disburseId     = $request->input('disburseId');
        $email          = $request->input('email');
        $bankCode       = $request->input('bankCode');
        $bankAccount    = $request->input('bankAccount');
        $accountName    = $request->input('accountName');
        $custRefNumber  = $request->input('custRefNumber');
        $amountTransfer = $request->input('amountTransfer');
        $signature      = $request->input('signature');
        $statusCode     = $request->input('statusCode'); // 00 = Success

        if (!$email || !$bankCode || !$bankAccount || !$accountName || !$custRefNumber || !$amountTransfer || !$disburseId || !$signature) {
            return response()->json(['message' => 'Bad Parameter'], 400);
        }

        $calcSignature = hash('sha256', $email . $bankCode . $bankAccount . $accountName . $custRefNumber . $amountTransfer . $disburseId . $this->disbursementSecretKey);

        if ($signature === $calcSignature) {
            Log::info("Duitku Clearing Callback Diterima: {$disburseId} - Status: {$statusCode}");
            return response('SUCCESS', 200);
        }

        Log::warning("Duitku Clearing Callback Bad Signature: {$disburseId}");
        return response()->json(['message' => 'Bad Signature'], 403);
    }

    /**
     * ==========================================================
     * FITUR DISBURSEMENT 3: CASH OUT (Pos Indonesia & Indomaret)
     * ==========================================================
     */
    public function createCashOutInquiry($amountTransfer, $bankCode, $email, $phoneNumber, $accountName, $accountIdentity, $purpose, $accountAddress = null, $custRefNumber = null, $callbackUrl = null)
    {
        // Validasi Limit Cash Out berdasarkan Tipe Agen (Indomaret '2010', Pos Indonesia '2011')
        $this->validateDisbursementLimit($amountTransfer, 'CASHOUT', $bankCode);

        $timestamp = round(microtime(true) * 1000);
        $signature = hash('sha256', $email . $timestamp . $amountTransfer . $purpose . $this->disbursementSecretKey);

        $params = [
            'userId'          => (int) $this->disbursementUserId,
            'amountTransfer'  => (int) $amountTransfer,
            'custRefNumber'   => $custRefNumber,
            'bankCode'        => $bankCode,
            'accountName'     => $accountName,
            'accountAddress'  => $accountAddress,
            'accountIdentity' => $accountIdentity,
            'email'           => $email,
            'phoneNumber'     => $phoneNumber,
            'purpose'         => $purpose,
            'timestamp'       => $timestamp,
            'callbackUrl'     => $callbackUrl,
            'signature'       => $signature
        ];

        $response = Http::post($this->cashOutBaseUrl . '/inquiry', $params);

        return $response->json();
    }

    public function handleCashOutCallback(Request $request)
    {
        $disburseId     = $request->input('disburseId');
        $email          = $request->input('email');
        $amountTransfer = $request->input('amountTransfer');
        $custRefNumber  = $request->input('custRefNumber');
        $accountName    = $request->input('accountName');
        $phoneNumber    = $request->input('phoneNumber');
        $signature      = $request->input('signature');
        $statusCode     = $request->input('statusCode'); // 00 = Success

        if (!$email || !$phoneNumber || !$accountName || !$custRefNumber || !$amountTransfer || !$disburseId || !$signature) {
            return response()->json(['message' => 'Bad Parameter'], 400);
        }

        $calcSignature = hash('sha256', $email . $disburseId . $custRefNumber . $this->disbursementSecretKey);

        if ($signature === $calcSignature) {
            Log::info("Duitku CashOut Callback Diterima: {$disburseId} - Status: {$statusCode}");
            return response('SUCCESS', 200);
        }

        Log::warning("Duitku CashOut Callback Bad Signature: {$disburseId}");
        return response()->json(['message' => 'Bad Signature'], 403);
    }
}
