<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Models\Api;
use Exception;

class BcaController extends Controller
{
    public string $mode;
    public string $baseUrl;
    public string $clientId;
    public string $clientSecret;
    public string $apiKey;
    public string $apiSecret;
    public string $privateKey;

    public function __construct()
    {
        // 1. Ambil mode global BCA (sandbox / production)
        $this->mode = Api::getValue('BCA_MODE', 'global', 'sandbox');

        // 2. Tentukan Base URL
        $this->baseUrl = ($this->mode === 'production')
            ? 'https://api.bca.co.id'
            : 'https://sandbox.bca.co.id';

        // 3. Ambil kredensial sesuai mode aktif secara dinamis dari DB
        $this->clientId     = Api::getValue('BCA_CLIENT_ID', $this->mode);
        $this->clientSecret = Api::getValue('BCA_CLIENT_SECRET', $this->mode);
        $this->apiKey       = Api::getValue('BCA_API_KEY', $this->mode);
        $this->apiSecret    = Api::getValue('BCA_API_SECRET', $this->mode);

        // Pastikan format Private Key dibaca dengan benar oleh OpenSSL
        $rawPrivateKey      = Api::getValue('BCA_PRIVATE_KEY', $this->mode);
        $this->privateKey   = str_replace(['\r\n', '\n'], "\n", $rawPrivateKey);
    }

    // ========================================================================
    // A. CORE AUTHENTICATION & SIGNATURE SNAP API
    // ========================================================================

    /**
     * Mendapatkan Access Token (SNAP API) dengan Auto-Cache
     */
    public function getSnapToken(): string
    {
        $cacheKey = 'bca_snap_token_' . $this->mode;

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $timestamp = now()->format('Y-m-d\TH:i:sP');
            $signature = $this->generateSnapAsymmetricSignature($timestamp);

            $response = Http::withHeaders([
                'X-TIMESTAMP'  => $timestamp,
                'X-CLIENT-KEY' => $this->clientId,
                'X-SIGNATURE'  => $signature,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/openapi/v1.0/access-token/b2b', [
                'grantType' => 'client_credentials'
            ]);

            if ($response->failed()) {
                throw new Exception('Gagal mendapatkan SNAP Access Token: ' . $response->body());
            }

            $data = $response->json();
            $token = $data['accessToken'];
            $expiresIn = (int) $data['expiresIn'];

            // Simpan token di cache (dikurangi 60 detik untuk safety margin)
            Cache::put($cacheKey, $token, now()->addSeconds($expiresIn - 60));
            return $token;

        } catch (Exception $e) {
            Log::error("LOG LOG: [BCA SNAP] Gagal GET TOKEN: " . $e->getMessage());
            throw $e;
        }
    }

    private function generateSnapAsymmetricSignature(string $timestamp): string
    {
        $stringToSign = $this->clientId . "|" . $timestamp;
        $signature = '';

        $privateKeyId = openssl_pkey_get_private($this->privateKey);
        if (!$privateKeyId) {
            throw new Exception('Private key BCA tidak valid atau tidak ditemukan.');
        }

        openssl_sign($stringToSign, $signature, $privateKeyId, OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    }

    private function generateSnapSymmetricSignature(string $httpMethod, string $relativeUrl, string $accessToken, array $requestBody, string $timestamp): string
    {
        $minifiedBody = empty($requestBody) ? '' : json_encode($requestBody, JSON_UNESCAPED_SLASHES);
        $bodyHash = strtolower(hash('sha256', $minifiedBody));
        $stringToSign = strtoupper($httpMethod) . ":" . $relativeUrl . ":" . $accessToken . ":" . $bodyHash . ":" . $timestamp;

        $signatureBinary = hash_hmac('sha512', $stringToSign, $this->clientSecret, true);
        return base64_encode($signatureBinary);
    }

    /**
     * MASTER FUNGSI: Eksekusi Request API SNAP terpusat
     */
    public function sendSnapRequest(string $method, string $relativeUrl, array $body = [], string $partnerId = '')
    {
        try {
            $accessToken = $this->getSnapToken();
            $timestamp   = now()->format('Y-m-d\TH:i:sP');
            $externalId  = date('YmdHis') . rand(100000, 999999); // Unique ID per hari sesuai dok BCA

            // X-PARTNER-ID wajib diisi dengan Merchant ID atau nama Partner
            $xPartnerId  = !empty($partnerId) ? $partnerId : ($body['merchantId'] ?? $this->clientId);

            $signature = $this->generateSnapSymmetricSignature($method, $relativeUrl, $accessToken, $body, $timestamp);

            $request = Http::withHeaders([
                'Authorization'  => 'Bearer ' . $accessToken,
                'Content-Type'   => 'application/json',
                'X-TIMESTAMP'    => $timestamp,
                'X-SIGNATURE'    => $signature,
                'ORIGIN'         => request()->getHost(),
                'X-EXTERNAL-ID'  => $externalId,
                'CHANNEL-ID'     => '95251', // Ketentuan BCA selalu menggunakan 95251
                'X-PARTNER-ID'   => $xPartnerId,
            ]);

            if (strtoupper($method) === 'POST') {
                $response = $request->post($this->baseUrl . $relativeUrl, $body);
            } else {
                $response = $request->get($this->baseUrl . $relativeUrl, $body);
            }

            // Log Error jika gagal
            if ($response->failed()) {
                Log::error("LOG LOG: [BCA SNAP] API Error pada {$relativeUrl}: " . $response->body());
            }

            return $response->json();

        } catch (Exception $e) {
            Log::error("LOG LOG: [BCA SNAP] System Crash: " . $e->getMessage());
            return [
                'responseCode' => '5000000',
                'responseMessage' => 'Internal System Error: ' . $e->getMessage()
            ];
        }
    }

    // ========================================================================
    // B. QRIS MPM (MERCHANT PRESENTED MODE) - Generate QR untuk discan User
    // ========================================================================

    /**
     * 1. Generate QRIS MPM
     * Memunculkan gambar / string QR untuk ditampilkan ke pelanggan
     */
    public function generateQrisMpm(array $data)
    {
        $url = '/openapi/v1.0/qr/qr-mpm-generate';

        // Sesuaikan parameter berdasarkan input Anda
        $payload = [
            'partnerReferenceNo' => $data['partnerReferenceNo'] ?? uniqid('INV-'),
            'amount' => [
                'value'    => number_format((float) $data['amount'], 2, '.', ''), // format 10000.00
                'currency' => 'IDR'
            ],
            'merchantId'    => $data['merchantId'] ?? '',
            'subMerchantId' => $data['subMerchantId'] ?? '',
            'terminalId'    => $data['terminalId'] ?? '',
            'validityPeriod'=> $data['validityPeriod'] ?? now()->addMinutes(60)->format('Y-m-d\TH:i:sP'),
            'additionalInfo'=> [
                'convenienceFee'       => '0.00',
                'partnerMerchantType'  => $data['partnerMerchantType'] ?? '',
                'terminalLocationName' => $data['terminalLocationName'] ?? '',
                'qrOption'             => $data['qrOption'] ?? 'C' // C=Content, I=Image, A=All
            ]
        ];

        return $this->sendSnapRequest('POST', $url, $payload, $payload['merchantId']);
    }

    /**
     * 2. Inquiry QRIS MPM
     * Mengecek status transaksi dari QR yang telah digenerate (Sukses/Pending/Gagal)
     */
    public function queryQrisMpm(array $data)
    {
        $url = '/openapi/v1.0/qr/qr-mpm-query';

        $payload = [
            'originalPartnerReferenceNo' => $data['originalPartnerReferenceNo'],
            'originalReferenceNo'        => $data['originalReferenceNo'], // Diambil dari response Generate QR
            'serviceCode'                => '47', // Fixed service code BCA untuk QR Generate
            'merchantId'                 => $data['merchantId'] ?? '',
            'subMerchantId'              => $data['subMerchantId'] ?? '',
            'additionalInfo' => [
                'terminalId'          => $data['terminalId'] ?? '',
                'partnerMerchantType' => $data['partnerMerchantType'] ?? ''
            ]
        ];

        return $this->sendSnapRequest('POST', $url, $payload, $payload['merchantId']);
    }

    /**
     * 3. Refund QRIS MPM
     * Mengembalikan dana transaksi yang sudah sukses (Belum disupport di Sandbox BCA)
     */
    public function refundQrisMpm(array $data)
    {
        $url = '/openapi/v1.0/qr/qr-mpm-refund';

        $payload = [
            'merchantId'                 => $data['merchantId'] ?? '',
            'subMerchantId'              => $data['subMerchantId'] ?? '',
            'originalPartnerReferenceNo' => $data['originalPartnerReferenceNo'],
            'originalReferenceNo'        => $data['originalReferenceNo'],
            'partnerRefundNo'            => $data['partnerRefundNo'], // RRN QRIS (issuerReferenceNumber)
            'refundAmount' => [
                'value'    => number_format((float) $data['refundAmount'], 2, '.', ''),
                'currency' => 'IDR'
            ],
            'additionalInfo' => [
                'terminalId'          => $data['terminalId'] ?? '',
                'transactionDate'     => $data['transactionDate'] ?? '', // Diambil dari paidTime saat Inquiry
                'partnerMerchantType' => $data['partnerMerchantType'] ?? '',
                'issuerName'          => $data['issuerName'] ?? 'BCA'
            ]
        ];

        return $this->sendSnapRequest('POST', $url, $payload, $payload['merchantId']);
    }

    // ========================================================================
    // C. QRIS CPM (CUSTOMER PRESENTED MODE) - Kasir / Sistem Scan QR User
    // ========================================================================

    /**
     * 1. Process Payment QRIS CPM
     * Menembak QR String / Barcode yang discan dari HP customer untuk memotong saldonya
     */
    public function processQrisCpm(array $data)
    {
        $url = '/openapi/v1.0/qr/qr-cpm-payment';

        $payload = [
            'partnerReferenceNo' => $data['partnerReferenceNo'] ?? uniqid('PAY-'),
            'qrContent'          => $data['qrContent'], // String QR hasil scan dari HP Pelanggan
            'amount' => [
                'value'    => number_format((float) $data['amount'], 2, '.', ''),
                'currency' => 'IDR'
            ],
            'merchantId'    => $data['merchantId'] ?? '',
            'subMerchantId' => $data['subMerchantId'] ?? '',
            'acquirerName'  => 'BCA',
            'terminalId'    => $data['terminalId'] ?? '',
            'additionalInfo'=> [
                'convenienceFee'      => '0.00',
                'partnerMerchantType' => $data['partnerMerchantType'] ?? ''
            ]
        ];

        return $this->sendSnapRequest('POST', $url, $payload, $payload['merchantId']);
    }

    /**
     * 2. Cancel Payment QRIS CPM
     * Membatalkan transaksi CPM
     */
    public function cancelQrisCpm(array $data)
    {
        $url = '/openapi/v1.0/qr/qr-cpm-cancel';

        $payload = [
            'originalPartnerReferenceNo' => $data['originalPartnerReferenceNo'],
            'merchantId'                 => $data['merchantId'] ?? '',
            'subMerchantId'              => $data['subMerchantId'] ?? '',
            'additionalInfo' => [
                'terminalId'          => $data['terminalId'] ?? '',
                'partnerMerchantType' => $data['partnerMerchantType'] ?? ''
            ]
        ];

        return $this->sendSnapRequest('POST', $url, $payload, $payload['merchantId']);
    }

    // ========================================================================
    // D. HELPER UNTUK WEBHOOK / NOTIFIKASI DARI BCA
    // ========================================================================

    /**
     * Validasi Signature dari BCA Notification (Webhook)
     * Dapat dipanggil oleh controller Webhook Anda
     */
    public function verifyBcaWebhookSignature(Request $request): bool
    {
        $bcaSignature = $request->header('X-SIGNATURE');
        $timestamp    = $request->header('X-TIMESTAMP');
        $httpMethod   = $request->method();
        $relativeUrl  = $request->getRequestUri();
        $accessToken  = str_replace('Bearer ', '', $request->header('Authorization', ''));

        $requestBody = $request->all();

        $expectedSignature = $this->generateSnapSymmetricSignature($httpMethod, $relativeUrl, $accessToken, $requestBody, $timestamp);

        return hash_equals($expectedSignature, $bcaSignature);
    }
}
