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

        Log::info("LOG LOG: [BCA Init] BcaController diinisialisasi.", ['mode' => $this->mode]);
    }

    // ========================================================================
    // A. CORE AUTHENTICATION & SIGNATURE SNAP API
    // ========================================================================

    /**
     * Mendapatkan Access Token (SNAP API) dengan Auto-Cache
     */
    public function getSnapToken(): string
    {
        $cacheKey = 'bca_snap_token_v3_' . $this->mode;

        if (Cache::has($cacheKey)) {
            Log::info("LOG LOG: [BCA SNAP] Menggunakan Access Token dari Cache.");
            return Cache::get($cacheKey);
        }

        try {
            Log::info("LOG LOG: [BCA SNAP] Request Access Token baru ke BCA...");
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
                Log::error("LOG LOG: [BCA SNAP] Gagal mendapatkan Token. Body: " . $response->body());
                throw new Exception('Gagal mendapatkan SNAP Access Token: ' . $response->body());
            }

            $data = $response->json();
            $token = $data['accessToken'];
            $expiresIn = (int) $data['expiresIn'];

            // Simpan token di cache (dikurangi 60 detik untuk safety margin)
            Cache::put($cacheKey, $token, now()->addSeconds($expiresIn - 60));

            Log::info("LOG LOG: [BCA SNAP] Access Token berhasil didapatkan dan disimpan di Cache.");
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
            Log::error("LOG LOG: [BCA Signature] Private key BCA tidak valid atau tidak ditemukan saat Generate Asymmetric Signature.");
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

        Log::debug("LOG LOG: [BCA Signature] Symmetric signature berhasil di-generate untuk URL: {$relativeUrl}");
        return base64_encode($signatureBinary);
    }

    /**
     * MASTER FUNGSI: Eksekusi Request API SNAP terpusat
     */
    public function sendSnapRequest(string $method, string $relativeUrl, array $body = [], string $partnerId = '')
    {
        try {
            Log::info("LOG LOG: [BCA SNAP Request] Memulai Request {$method} ke {$relativeUrl}", ['body' => $body]);

            $accessToken = $this->getSnapToken();
            $timestamp   = now()->format('Y-m-d\TH:i:sP');
            $externalId  = date('YmdHis') . rand(100000, 999999); // Unique ID per hari sesuai dok BCA

            // X-PARTNER-ID wajib diisi dengan Merchant ID atau nama Partner
            $xPartnerId  = !empty($partnerId) ? $partnerId : ($body['merchantId'] ?? $this->clientId);

            $signature = $this->generateSnapSymmetricSignature($method, $relativeUrl, $accessToken, $body, $timestamp);

            $headers = [
                'Authorization'  => 'Bearer ' . $accessToken,
                'Content-Type'   => 'application/json',
                'X-TIMESTAMP'    => $timestamp,
                'X-SIGNATURE'    => $signature,
                'ORIGIN'         => request()->getHost(),
                'X-EXTERNAL-ID'  => $externalId,
                'CHANNEL-ID'     => '95251', // Ketentuan BCA selalu menggunakan 95251
                'X-PARTNER-ID'   => $xPartnerId,
            ];

            $request = Http::withHeaders($headers);

            if (strtoupper($method) === 'POST') {
                $response = $request->post($this->baseUrl . $relativeUrl, $body);
            } else {
                $response = $request->get($this->baseUrl . $relativeUrl, $body);
            }

            // Log Error jika gagal
            if ($response->failed()) {
                Log::error("LOG LOG: [BCA SNAP] API Error pada {$relativeUrl}: " . $response->body(), [
                    'status'  => $response->status(),
                    'headers' => $headers
                ]);
            } else {
                Log::info("LOG LOG: [BCA SNAP Request] Success Response dari {$relativeUrl}", ['response' => $response->json()]);
            }

            return $response->json();

        } catch (Exception $e) {
            Log::error("LOG LOG: [BCA SNAP] System Crash: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
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
        Log::info("LOG LOG: [BCA QRIS MPM] Menjalankan Generate QRIS MPM", ['input_data' => $data]);
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
        Log::info("LOG LOG: [BCA QRIS MPM] Menjalankan Query/Inquiry Status QRIS MPM", ['input_data' => $data]);
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
        Log::info("LOG LOG: [BCA QRIS MPM] Menjalankan Refund QRIS MPM", ['input_data' => $data]);
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
        Log::info("LOG LOG: [BCA QRIS CPM] Menjalankan Proses Pembayaran QRIS CPM", ['input_data' => $data]);
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
        Log::info("LOG LOG: [BCA QRIS CPM] Menjalankan Cancel Pembayaran QRIS CPM", ['input_data' => $data]);
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
        Log::info("LOG LOG: [BCA Webhook] Mulai Verifikasi Signature Webhook Masuk", ['url' => $request->getRequestUri()]);

        $bcaSignature = $request->header('X-SIGNATURE');
        $timestamp    = $request->header('X-TIMESTAMP');
        $httpMethod   = $request->method();
        $relativeUrl  = $request->getRequestUri();
        $accessToken  = str_replace('Bearer ', '', $request->header('Authorization', ''));

        $requestBody = $request->all();

        $expectedSignature = $this->generateSnapSymmetricSignature($httpMethod, $relativeUrl, $accessToken, $requestBody, $timestamp);

        $isValid = hash_equals($expectedSignature, $bcaSignature);

        if ($isValid) {
            Log::info("LOG LOG: [BCA Webhook] Verifikasi Berhasil! Signature Match.");
        } else {
            Log::error("LOG LOG: [BCA Webhook] Verifikasi GAGAL! Signature Tidak Cocok.", [
                'expected' => $expectedSignature,
                'received' => $bcaSignature
            ]);
        }

        return $isValid;
    }

    /**
     * Endpoint khusus untuk alat Debugging BCA Sandbox di Panel Admin
     */
    public function generateDebugTools(Request $request)
    {
        try {
            $type = $request->input('type');
            
            if ($type === 'token') {
                Cache::forget('bca_snap_token_v3_' . $this->mode); // Paksa hapus cache lama
                $token = $this->getSnapToken(); // Fungsi Anda akan memicu LOG LOG
                return response()->json(['success' => true, 'token' => $token]);
            }

            if ($type === 'signature') {
                $token = $request->input('token');
                $timestamp = $request->input('timestamp');
                $url = $request->input('url');
                $rawBody = $request->input('bodyData');
                $body = !empty($rawBody) ? json_decode($rawBody, true) : [];
                
                if (json_last_error() !== JSON_ERROR_NONE && !empty($rawBody)) {
                    return response()->json(['success' => false, 'message' => 'Format Payload JSON tidak valid!']);
                }

                $signature = $this->generateSnapSymmetricSignature('POST', $url, $token, $body, $timestamp);
                return response()->json(['success' => true, 'signature' => $signature]);
            }

            // === LOGIKA BARU: EKSEKUSI API LANGSUNG KE BCA ===
            if ($type === 'execute') {
                $token = $request->input('token');
                $timestamp = $request->input('timestamp');
                $signature = $request->input('signature');
                $url = $request->input('url');
                
                $rawBody = $request->input('bodyData');
                $bodyArray = !empty($rawBody) ? json_decode($rawBody, true) : [];

                // Siapkan header sesuai standar SNAP BCA
                $headers = [
                    'Authorization'  => 'Bearer ' . $token,
                    'Content-Type'   => 'application/json',
                    'X-TIMESTAMP'    => $timestamp,
                    'X-SIGNATURE'    => $signature,
                    'ORIGIN'         => request()->getHost(),
                    'X-EXTERNAL-ID'  => date('YmdHis') . rand(100000, 999999),
                    'CHANNEL-ID'     => '95251',
                    'X-PARTNER-ID'   => $bodyArray['merchantId'] ?? '123456789', // Ambil dari payload
                ];

                // URL berdasarkan environment
                $bcaUrl = ($this->mode === 'production') ? 'https://api.bca.co.id' : 'https://sandbox.bca.co.id';
                $fullUrl = $bcaUrl . $url;

                // Tembak API
                $response = Http::withHeaders($headers)->post($fullUrl, $bodyArray);

                return response()->json([
                    'success'  => true,
                    'response' => $response->json() // Tampilkan balasan mentah JSON dari BCA
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Invalid type action']);
        } catch (Exception $e) {
            Log::error("LOG LOG: [BCA Debug API] Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
