<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class MandiriGatewayController extends Controller
{
    private $mode;
    private $clientId;
    private $clientSecret;
    private $partnerId;
    private $privateKey;
    private $baseUrl;
    private $authUrl;

   public function __construct()
    {
        // 1. Ambil mode langsung dari Database (Bukan dari config)
        $this->mode = \App\Models\Api::getValue('MANDIRI_MODE', 'global', 'sandbox');

        if (!in_array($this->mode, ['sandbox', 'production'])) {
            $this->mode = 'sandbox';
        }

        // 2. Ambil kredensial sesuai mode dari Database
        $this->clientId     = \App\Models\Api::getValue('MANDIRI_CLIENT_ID', $this->mode);
        $this->clientSecret = \App\Models\Api::getValue('MANDIRI_CLIENT_SECRET', $this->mode);
        $this->partnerId    = \App\Models\Api::getValue('MANDIRI_PARTNER_ID', $this->mode);
        $rawPrivateKey      = \App\Models\Api::getValue('MANDIRI_PRIVATE_KEY', $this->mode);

        if (empty($rawPrivateKey)) {
            throw new Exception("Private Key Mandiri belum diisi di Pengaturan API untuk mode " . strtoupper($this->mode));
        }

        // 3. ZERO-TRUST SANITIZER: Bersihkan Private Key & Format ulang jadi PEM standar
        // Ini mencegah error "Private Key tidak valid" akibat spasi/newline yang rusak saat disimpan ke DB
        $cleanKey = str_replace(
            ["-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----", "\r", "\n", " ", "\"", "'"],
            "",
            $rawPrivateKey
        );
        $this->privateKey = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($cleanKey, 64, "\n", true) . "\n-----END PRIVATE KEY-----";

        // 4. KOREKSI BASE URL (Telah diperbarui untuk Sandbox)
        $this->baseUrl      = $this->mode === 'production'
                                ? 'https://openapi.bankmandiri.co.id'
                                : 'https://sandbox.bankmandiri.co.id'; // Hapus awalan "api."

        $this->authUrl      = $this->mode === 'production'
                                ? 'https://openapi.bankmandiri.co.id'
                                : 'https://sandbox.bankmandiri.co.id'; // Hapus awalan "api."
    }

    /**
     * MENGAMBIL ACCESS TOKEN B2B DENGAN CACHE 14 MENIT
     */
    private function getAccessToken()
    {
        $cacheKey = "mandiri_access_token_{$this->mode}";

        return Cache::remember($cacheKey, now()->addMinutes(14), function () {
            $endpoint = '/openapi/auth/v2.0/access-token/b2b';
            $url = $this->authUrl . $endpoint;

            $timestamp = date('c');
            $stringToSign = $this->clientId . '|' . $timestamp;

            $binarySignature = '';
            $privateKeyResource = openssl_pkey_get_private($this->privateKey);

            if (!$privateKeyResource) {
                throw new Exception("Private Key Mandiri tidak valid.");
            }

            openssl_sign($stringToSign, $binarySignature, $privateKeyResource, OPENSSL_ALGO_SHA256);
            $signature = base64_encode($binarySignature);

            $response = Http::asJson()->withHeaders([
                'X-CLIENT-KEY' => $this->clientId,
                'X-TIMESTAMP'  => $timestamp,
                'X-SIGNATURE'  => $signature,
            ])->post($url, [
                'grantType' => 'client_credentials'
            ]);

            if ($response->successful() && isset($response->json()['accessToken'])) {
                // LOG LOG
                return $response->json()['accessToken'];
            }

            Log::error('Mandiri Get Token Failed', ['response' => $response->body()]);
            throw new Exception('Gagal mendapatkan Access Token Mandiri.');
        });
    }

    /**
     * GENERATOR TANDA TANGAN TRANSAKSI (HMAC-SHA512)
     */
    private function generateTransactionSignature($httpMethod, $endpoint, $accessToken, $jsonBody, $timestamp)
    {
        $minifyBody = empty($jsonBody) ? '' : json_encode(json_decode($jsonBody, true), JSON_UNESCAPED_SLASHES);
        $hashBody = strtolower(bin2hex(hash('sha256', $minifyBody, true)));
        $stringToSign = strtoupper($httpMethod) . ':' . $endpoint . ':' . $accessToken . ':' . $hashBody . ':' . $timestamp;
        $signature = hash_hmac('sha512', $stringToSign, $this->clientSecret, true);

        return base64_encode($signature);
    }

    /**
     * ZERO-TRUST RECURSIVE SANITIZER: Membersihkan Payload secara dinamis
     */
    private function sanitizePayload($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                // Abaikan sanitasi HTML tags pada base64 image (untuk RDN/RDL)
                if (in_array($key, ['selfiePhoto'])) {
                    $data[$key] = $value;
                    continue;
                }
                $data[$key] = $this->sanitizePayload($value);
            }
            return $data;
        }
        // Bersihkan string dari potensi injeksi XSS, kecuali null/boolean
        return is_string($data) ? trim(strip_tags($data)) : $data;
    }

    /**
     * CORE HTTP DISPATCHER
     */
    private function sendRequest($httpMethod, $endpoint, $payload = [])
    {
        $accessToken = $this->getAccessToken();

        // Jika endpoint auth, gunakan authUrl, jika transaksi gunakan baseUrl
        $url = (strpos($endpoint, '/auth/') !== false ? $this->authUrl : $this->baseUrl) . $endpoint;

        $timestamp = date('c');
        $sanitizedPayload = $this->sanitizePayload($payload);
        $jsonBody = empty($sanitizedPayload) ? '' : json_encode($sanitizedPayload);

        $externalId = substr(time() . rand(100000000, 999999999), 0, 19);
        $signature = $this->generateTransactionSignature($httpMethod, $endpoint, $accessToken, $jsonBody, $timestamp);

        // LOG LOG
        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type'  => 'application/json',
            'X-TIMESTAMP'   => $timestamp,
            'X-SIGNATURE'   => $signature,
            'X-PARTNER-ID'  => $this->partnerId,
            'X-EXTERNAL-ID' => $externalId,
            'CHANNEL-ID'    => '12345',
        ];

        $request = Http::withHeaders($headers);
        $response = strtoupper($httpMethod) === 'POST'
            ? $request->send('POST', $url, ['body' => $jsonBody])
            : $request->send('GET', $url);

        return $response->json();
    }

    /* ==============================================================================
     * 23 ENDPOINT API MANDIRI FULL INTEGRATION
     * ============================================================================== */

    // 1. Balance Inquiry
    public function balanceInquiry(Request $request) {
        return response()->json($this->sendRequest('POST', '/openapi/customers/v2.1/balance-inquiry', $request->all()));
    }

    // 2. Bank Statement
    public function bankStatement(Request $request) {
        return response()->json($this->sendRequest('POST', '/openapi/transactions/v2.1/bank-statement', $request->all()));
    }

    // 3. Internal Account Inquiry
    public function internalAccountInquiry(Request $request) {
        return response()->json($this->sendRequest('POST', '/openapi/customers/v2.0/account-inquiry-internal', $request->all()));
    }

    // 4. External Account Inquiry
    public function externalAccountInquiry(Request $request) {
        return response()->json($this->sendRequest('POST', '/openapi/customers/v2.1/account-inquiry-external', $request->all()));
    }

    // 5. Intrabank Transfer
    public function transferIntrabank(Request $request) {
        return response()->json($this->sendRequest('POST', '/openapi/transactions/v2.0/transfer-intrabank', $request->all()));
    }

    // 6. RTGS Transfer
    public function transferRtgs(Request $request) {
        return response()->json($this->sendRequest('POST', '/openapi/transactions/v2.1/transfer-rtgs', $request->all()));
    }

    // 7. SKNBI Transfer
    public function transferSknbi(Request $request) {
        return response()->json($this->sendRequest('POST', '/openapi/transactions/v2.0/transfer-skn', $request->all()));
    }

    // 8. Interbank Transfer
    public function transferInterbank(Request $request) {
        return response()->json($this->sendRequest('POST', '/openapi/transactions/v2.1/transfer-interbank', $request->all()));
    }

    // 9. Transaction Status Inquiry
    public function transactionStatusInquiry(Request $request) {
        return response()->json($this->sendRequest('POST', '/openapi/transactions/v2.1/transfer/status', $request->all()));
    }

    // 10. Bill Payment Inquiry
    public function billPaymentInquiry(Request $request) {
        return response()->json($this->sendRequest('POST', '/openapi/transactions/v2.1/transfer-va/inquiry-intrabank', $request->all()));
    }

    // 11. Bill Payment Transfer
    public function billPaymentTransfer(Request $request) {
        return response()->json($this->sendRequest('POST', '/openapi/transactions/v2.1/transfer-va/payment-intrabank', $request->all()));
    }

    // 12. Register Account RDN
    public function registerAccountRdn(Request $request) {
        return response()->json($this->sendRequest('POST', '/openapi/onboarding/v1.0/registerAccountRDN', $request->all()));
    }

    // 13. Inquiry Status RDN
    public function inquiryStatusRdn(Request $request) {
        return response()->json($this->sendRequest('POST', '/openapi/onboarding/v1.0/inquiryStatusRDN', $request->all()));
    }

    // 14. Register Account RDL / RDB
    public function registerAccountRdl(Request $request) {
        return response()->json($this->sendRequest('POST', '/openapi/onboarding/v1.0/registerAccountRDL', $request->all()));
    }

    // 15. Inquiry Status RDL / RDB
    public function inquiryStatusRdl(Request $request) {
        return response()->json($this->sendRequest('POST', '/openapi/onboarding/v1.0/inquiryStatusRDL', $request->all()));
    }

    // 16. Create Virtual Account
    public function createVirtualAccount(Request $request) {
        return response()->json($this->sendRequest('POST', '/openapi/transaction/v1.0/transfer-va/create-va', $request->all()));
    }

    // 17. Update Virtual Account
    public function updateVirtualAccount(Request $request) {
        return response()->json($this->sendRequest('POST', '/openapi/transaction/v1.0/transfer-va/update-va', $request->all()));
    }

    // 18. Inquiry Virtual Account
    public function inquiryVirtualAccount(Request $request) {
        return response()->json($this->sendRequest('POST', '/openapi/transaction/v1.0/transfer-va/inquiry-va', $request->all()));
    }

    // 19. Get Report Virtual Account
    public function getReportVirtualAccount(Request $request) {
        return response()->json($this->sendRequest('POST', '/openapi/transaction/v1.0/transfer-va/report', $request->all()));
    }

   // 20. Notify Payment Virtual Account (WEBHOOK RECEIVER DARI MANDIRI)
    public function notifyPaymentVirtualAccount(Request $request) {
        try {
            // Bersihkan payload dari potensi XSS
            $sanitizedData = $this->sanitizePayload($request->all());

            // LOG LOG
            Log::info('Mandiri VA Payment Received:', $sanitizedData);

            // --- TAMBAHKAN BLOK INTEGRASI SALDO INI ---
            // Mengambil ID Transaksi internal kita yang dipantulkan kembali oleh Mandiri
            $merchantRef = $sanitizedData['trxId'] ?? null;

            // Mengambil nilai uang yang benar-benar dibayarkan pelanggan
            $paidAmount = $sanitizedData['paidAmount']['value'] ?? 0;

            // Jika ada referensi dan jumlah bayar lebih dari 0, otomatiskan tambah saldo!
            if ($merchantRef && $paidAmount > 0) {
                // Panggil super-method processTopUp milik TopUpController untuk mengeksekusi penambahan saldo
                \App\Http\Controllers\Customer\TopUpController::processTopUp($merchantRef, 'PAID', $paidAmount);
            }
            // ------------------------------------------

            // Sesuai standar dokumentasi Mandiri, kita WAJIB membalas dengan status JSON 2002500 - Successful
            // beserta gema (echo) dari data yang mereka kirimkan dibungkus dalam 'virtualAccountData'
            return response()->json([
                "responseCode" => "2002500",
                "responseMessage" => "Successful",
                "virtualAccountData" => [
                    "partnerServiceId"      => $sanitizedData['partnerServiceId'] ?? "",
                    "customerNo"            => $sanitizedData['customerNo'] ?? "",
                    "virtualAccountNo"      => $sanitizedData['virtualAccountNo'] ?? "",
                    "virtualAccountName"    => $sanitizedData['virtualAccountName'] ?? "",
                    "virtualAccountEmail"   => $sanitizedData['virtualAccountEmail'] ?? "",
                    "virtualAccountPhone"   => $sanitizedData['virtualAccountPhone'] ?? "",
                    "trxId"                 => $sanitizedData['trxId'] ?? "",
                    "paymentRequestId"      => $sanitizedData['paymentRequestId'] ?? "",
                    "hashedSourceAccountNo" => $sanitizedData['hashedSourceAccountNo'] ?? "",
                    "paidAmount"            => $sanitizedData['paidAmount'] ?? [
                        "value" => "0.00",
                        "currency" => "IDR"
                    ],
                    "trxDateTime"           => $sanitizedData['trxDateTime'] ?? date('Ymd\THisO')
                ]
            ], 200);

        } catch (Exception $e) {
            // LOG LOG
            Log::error('Mandiri VA Webhook Error: ' . $e->getMessage());
            return response()->json([
                'responseCode' => '5000000',
                'responseMessage' => 'General Error'
            ], 500);
        }
    }

    // 21. Create Quote
    public function createQuote(Request $request) {
        return response()->json($this->sendRequest('POST', '/openapi/transactions/v2.0/transfer/createquote', $request->all()));
    }

    // 22. Transfer Remittance
    public function transferRemittance(Request $request) {
        return response()->json($this->sendRequest('POST', '/openapi/transactions/v2.0/transfer/remittance', $request->all()));
    }

    // 23. To Cash Transfer
    public function toCashTransfer(Request $request) {
        return response()->json($this->sendRequest('POST', '/openapi/transactions/v2.0/transfer/tocash', $request->all()));
    }
}
