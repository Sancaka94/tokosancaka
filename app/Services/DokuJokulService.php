<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; 
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;
// Import Model untuk type-hinting
use App\Models\User;
use App\Models\Store;

/**
 * =========================================================================
 * Doku Service (Menangani 2 API Berbeda)
 * =========================================================================
 *
 * Service ini menangani:
 * 1. DOKU CHECKOUT API (createPayment) - Untuk pelanggan bayar
 * 2. DOKU SAC MERCHANT API (createSubAccount, getBalance, payout) - Untuk kelola akun toko
 *
 */
class DokuJokulService
{
    // Kredensial untuk API Checkout (Top Up & Pembayaran Marketplace)
    protected $clientId;
    protected $secretKey;
    protected $baseUrlCheckout;
    
    // Kredensial untuk API SAC Merchant (Dompet Toko & Payout)
    protected $sacClientId;
    protected $sacSecretKey;
    protected $baseUrlSac;
    protected $sacIdUtama; // SAC ID Milik Admin Sancaka

    public function __construct()
    {
        // =================================================================
        // ✅ MEMBACA DARI config/doku.php
        // =================================================================

        // 1. Kredensial CHECKOUT (dari config 'doku.client_id')
        $this->clientId = config('doku.client_id');
        $this->secretKey = config('doku.secret_key');
        
        // 2. Kredensial SAC MERCHANT (dari config 'doku.sac_client_id')
        $this->sacClientId = config('doku.sac_client_id'); 
        $this->sacSecretKey = config('doku.sac_secret_key');
        
        // 3. SAC ID Utama Admin (Penampung Dana)
        $this->sacIdUtama = config('doku.main_sac_id'); 

        // --- Membaca URL dari config ---
        $productionUrl = config('doku.production_url');
        $sandboxUrl = config('doku.sandbox_url');
        
        // Membaca mode dari config (bukan env() langsung)
        $mode = config('doku.mode'); 
        
        $baseUrl = ($mode === 'production') 
            ? $productionUrl
            : $sandboxUrl;
            
        // Terapkan ke KEDUA URL
        $this->baseUrlCheckout = $baseUrl;
        $this->baseUrlSac = $baseUrl;
    }

    // ========================================================================
    // FUNGSI API CHECKOUT (UNTUK PELANGGAN BAYAR)
    // ========================================================================

    /**
     * Membuat link pembayaran (payment.url)
     * Dipanggil oleh CheckoutController
     */
    public function createPayment($invoiceNumber, $amount, $customerData = [], $lineItems = [], $additionalInfo = [], $redirectUrl = null)
    {
        $endpoint = '/checkout/v1/payment';
        $url = $this->baseUrlCheckout . $endpoint; // Menggunakan URL Checkout

        // 1. Siapkan Request Body (JSON)
        $requestBody = [
            'order' => [
                'amount' => (int) $amount,
                'invoice_number' => $invoiceNumber,
            ],
            'customer' => [
                'name' => $customerData['name'] ?? 'Customer',
                'email' => $customerData['email'] ?? 'customer@example.com',
                'phone' => $customerData['phone'] ?? null,
            ],
            'payment' => [
                'payment_due_date' => 60 // 60 menit
            ]
        ];
        
        // Tambahkan Redirect URL jika ada
        if ($redirectUrl) {
            $requestBody['payment']['redirect_url'] = $redirectUrl;
        }
        
        if (!empty($lineItems)) {
            $requestBody['order']['line_items'] = $lineItems;
        }
        
        // Alur Escrow: $additionalInfo sengaja dikosongkan oleh CheckoutController
        // agar uang masuk ke Akun Utama Admin.
        if (!empty($additionalInfo) && isset($additionalInfo['account']['id'])) {
            $requestBody['additional_info'] = $additionalInfo;
        }
        
        $requestId = (string) Str::uuid();
        $requestTimestamp = now()->utc()->format('Y-m-d\TH:i:s\Z'); // Format 'Z'
        
        // 2. Buat Signature (Protokol Checkout - Longgar)
        $signature = $this->generateSignatureCheckout(
            json_encode($requestBody),
            $requestId, 
            $requestTimestamp, 
            $endpoint
        );

        Log::info('DOKU CHECKOUT Request:', [
            'url' => $url, 'body' => $requestBody, 'timestamp' => $requestTimestamp
        ]);
        
        try {
            // 3. Kirim API Request
            $response = Http::withHeaders([
                'Client-Id' => $this->clientId,
                'Request-Id' => $requestId,
                'Request-Timestamp' => $requestTimestamp,
                'Signature' => $signature,
            ])
            ->timeout(30)
            ->withBody(json_encode($requestBody), 'application/json') // Kirim sebagai raw string
            ->post($url);

            // 4. Proses Respon
            if ($response->successful() && isset($response->json()['response']['payment']['url'])) {
                Log::info("DOKU CHECKOUT: Create Payment Sukses", $response->json());
                return $response->json()['response']['payment']['url']; 
            } else {
                Log::error("DOKU CHECKOUT: Create Payment Gagal", $response->json() ?? ['body' => $response->body()]);
                return null;
            }

        } catch (Exception $e) {
            Log::error('DOKU CHECKOUT: Exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Helper untuk Generate Signature HMACSHA256 (Protokol CHECKOUT - Longgar)
     */
    private function generateSignatureCheckout($requestBodyString, $requestId, $requestTimestamp, $requestTarget)
    {
        $digest = base64_encode(hash('sha256', $requestBodyString, true));
        
        // String-to-Sign (Protokol Longgar: "Digest:" SAJA)
        $stringToSign = "Client-Id:" . $this->clientId . "\n"
                      . "Request-Id:" . $requestId . "\n"
                      . "Request-Timestamp:" . $requestTimestamp . "\n"
                      . "Request-Target:" . $requestTarget . "\n"
                      . "Digest:" . $digest; // <-- Beda di sini
        
        $hmac = hash_hmac('sha256', $stringToSign, $this->secretKey, true);
        $signature = base64_encode($hmac);
        
        return "HMACSHA256=" . $signature;
    }


    // ========================================================================
    // FUNGSI API SUB ACCOUNT (SAC) MERCHANT
    // ========================================================================

    /**
     * FUNGSI 1: Membuat Sub Account (Protokol SAC MERCHANT)
     */
    public function createSubAccount(User $user, Store $store, $phone)
    {
        $endpoint = '/sac-merchant/v1/accounts';
        $url = $this->baseUrlSac . $endpoint; 

        // Body HANYA berisi objek 'account'
        $body = [
            'account' => [
                'email' => $user->email,
                'type' => 'STANDARD',
                'name' => $store->name
            ]
        ];
        $bodyJson = json_encode($body);

        try {
            // 1. Buat Header (Protokol SAC - Ketat)
            $headers = $this->_generateHeadersSac($endpoint, $bodyJson);
            
            Log::info('DOKU SAC Request (MERCHANT)', ['url' => $url, 'headers' => $headers, 'body' => $bodyJson]);

            // 2. Kirim Request
            $response = Http::withHeaders($headers)
                ->withBody($bodyJson, 'application/json')
                ->post($url);

            $responseData = $response->json();
            Log::info('DOKU SAC Response (MERCHANT)', $responseData ?? ['message' => $response->body()]);

            // 3. Proses Respon SAC MERCHANT
            if ($response->successful() && isset($responseData['account']['id'])) {
                return [
                    'success' => true,
                    'data' => [
                        'profileId' => $responseData['account']['id'] // Mengirim 'account.id'
                    ]
                ];
            } else {
                $errorMessage = $responseData['error']['message'] ?? $responseData['responseMessage'] ?? 'Unknown SAC Merchant Error';
                Log::error('DOKU SAC Gagal (MERCHANT)', ['message' => $errorMessage, 'body' => $responseData]);
                return ['success' => false, 'message' => $errorMessage];
            }

        } catch (Exception $e) {
            Log::error('DOKU SAC Exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * FUNGSI 2: Mengambil Saldo (Protokol SAC)
     */
    public function getBalance(string $subAccountId)
    {
        $endpoint = '/sac-merchant/v1/balances/' . $subAccountId;
        $url = $this->baseUrlSac . $endpoint;
        $bodyJson = ""; // GET request tidak memiliki body

        try {
            // Header untuk GET (Tanpa Content-Type dan Digest)
            $headers = $this->_generateHeadersSac($endpoint, $bodyJson);
            
            Log::info('DOKU Get Balance Request', ['url' => $url, 'headers' => $headers]);

            $response = Http::withHeaders($headers)->get($url);
            $responseData = $response->json();

            if ($response->successful() && isset($responseData['balance'])) {
                Log::info('DOKU Get Balance Berhasil', $responseData);
                return ['success' => true, 'data' => $responseData];
            } else {
                Log::error('DOKU Get Balance Gagal', ['status' => $response->status(), 'body' => $responseData]);
                $errorMessage = $responseData['error']['message'] ?? 'Gagal mengambil saldo.';
                return ['success' => false, 'message' => $errorMessage];
            }
        } catch (Exception $e) {
            Log::error('DOKU Get Balance Exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * FUNGSI 3: Mengirim Payout (Withdrawal) ke rekening bank.
     */
    public function sendPayout(string $sac_id, int $amount, string $invoice_number, array $beneficiary): array
    {
        $endpoint = '/sac-merchant/v1/payouts';
        $url = $this->baseUrlSac . $endpoint;

        $body = [
            'account' => [
                'id' => $sac_id
            ],
            'payout' => [
                'amount' => $amount,
                'invoice_number' => $invoice_number
            ],
            'beneficiary' => [
                'bank_code' => $beneficiary['bank_code'],
                'bank_account_number' => $beneficiary['bank_account_number'],
                'bank_account_name' => $beneficiary['bank_account_name']
            ]
        ];
        
        $bodyJson = json_encode($body);

        try {
            $headers = $this->_generateHeadersSac($endpoint, $bodyJson);
            Log::info('DOKU SAC Payout Request', ['url' => $url, 'body' => $bodyJson]);

            $response = Http::withHeaders($headers)
                ->withBody($bodyJson, 'application/json')
                ->post($url);
            
            $responseData = $response->json();
            Log::info('DOKU SAC Payout Response', $responseData ?? ['message' => $response->body()]);

            if ($response->successful() && isset($responseData['payout']['status'])) {
                return ['success' => true, 'data' => $responseData];
            } else {
                $errorMessage = $responseData['error']['message'] ?? 'Payout Gagal';
                Log::error('DOKU SAC Payout Gagal', ['message' => $errorMessage, 'body' => $responseData]);
                return ['success' => false, 'message' => $errorMessage];
            }
        } catch (Exception $e) {
            Log::error('DOKU SAC Payout Exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * FUNGSI 4: Transfer dana antar Sub Account (jika diperlukan).
     * (Ini adalah implementasi alternatif, kita gunakan transferToSubAccount
     * sebagai gantinya, tapi kita simpan ini jika diperlukan)
     */
    public function transferIntra(string $origin_sac_id, string $destination_sac_id, int $amount): array
    {
        $endpoint = '/sac-merchant/v1/transfers';
        $url = $this->baseUrlSac . $endpoint;

        $body = [
            'transfer' => [
                'origin' => $origin_sac_id,
                'destination' => $destination_sac_id,
                'amount' => $amount,
                'invoice_number' => 'TRANSFER-' . Str::uuid() // Buat invoice unik
            ]
        ];
        
        $bodyJson = json_encode($body);

        try {
            $headers = $this->_generateHeadersSac($endpoint, $bodyJson);
            Log::info('DOKU SAC Transfer Request', ['url' => $url, 'body' => $bodyJson]);

            $response = Http::withHeaders($headers)
                ->withBody($bodyJson, 'application/json')
                ->post($url);
            
            $responseData = $response->json();
            Log::info('DOKU SAC Transfer Response', $responseData ?? ['message' => $response->body()]);

            if ($response->successful() && isset($responseData['transfer']['status'])) {
                return ['success' => true, 'data' => $responseData];
            } else {
                $errorMessage = $responseData['error']['message'] ?? 'Transfer Gagal';
                Log::error('DOKU SAC Transfer Gagal', ['message' => $errorMessage, 'body' => $responseData]);
                return ['success' => false, 'message' => $errorMessage];
            }
        } catch (Exception $e) {
            Log::error('DOKU SAC Transfer Exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * =================================================================
     * FUNGSI 5: PAYOUT (TRANSFER DARI MAIN KE SUB-ACCOUNT)
     * =================================================================
     * ✅ PERBAIKAN TOTAL: Menggunakan API Section 4 (Intra Transfer)
     * Sesuai dokumentasi yang Anda kirimkan.
     */
    public function transferToSubAccount($payoutRefId, $targetSacId, $amount, $description)
    {
        // ✅ PERBAIKAN 1: Gunakan endpoint "Transfer Intra Sub Account"
        // Sesuai Section 4 dokumentasi Anda.
        $endpoint = '/sac-merchant/v1/transfers';
        $url = $this->baseUrlSac . $endpoint; // <-- Pake base URL SAC
        
        // Payout SELALU dari Akun Utama Admin
        $sourceSacId = $this->sacIdUtama; 

        if(empty($sourceSacId)) {
            Log::critical('DOKU PAYOUT GAGAL: SAC_ID_UTAMA (Admin) belum di-setting di .env');
            return ['status' => 'FAILED', 'message' => 'Konfigurasi Akun Payout Admin belum di-set.'];
        }

        // ✅ PERBAIKAN 2: Payload untuk /sac-merchant/v1/transfers
        // Sesuai Section 4 dokumentasi Anda.
        $body = [
            'transfer' => [
                'origin' => $sourceSacId,
                'destination' => $targetSacId,
                'amount' => (int) $amount,
                'invoice_number' => $payoutRefId
                // 'description' TIDAK ADA di dokumentasi Section 4,
                // jadi kita HAPUS untuk menghindari 'Unknown Error'
            ]
        ];
        $bodyJson = json_encode($body);
        
        try {
            // ✅ PERBAIKAN 3: Gunakan helper "_generateHeadersSac"
            // karena ini adalah API SAC Merchant
            $headers = $this->_generateHeadersSac($endpoint, $bodyJson);

            Log::info('Doku Payout (SAC Transfer) Request', ['ref_id' => $payoutRefId, 'body' => $body, 'headers' => $headers]);

            $response = Http::withHeaders($headers)
                ->withBody($bodyJson, 'application/json')
                ->post($url);

            $responseBody = $response->json();
            Log::info('Doku Payout (SAC Transfer) Response:', ['ref_id' => $payoutRefId, 'body' => $responseBody]);

            // ✅ PERBAIKAN 4: Cek respon sukses dari /sac-merchant/v1/transfers
            // Sesuai Section 4 dokumentasi Anda
            if ($response->successful() && !empty($responseBody['transfer']['status']) && $responseBody['transfer']['status'] == 'SUCCESS') {
                return [
                    'status' => 'SUCCESS',
                    'transaction_id' => $responseBody['transfer']['invoice_number'] ?? $payoutRefId,
                    'message' => 'Pencairan dana berhasil.'
                ];
            }

            // Menangani error dari Doku
            $errorMessage = $responseBody['error']['message'] ?? ($responseBody['message'] ?? 'Gagal memproses payout.');
            Log::error('Doku Payout (Transfer) Failed:', ['ref_id' => $payoutRefId, 'response' => $responseBody]);
            return ['status' => 'FAILED', 'message' => $errorMessage];

        } catch (Exception $e) {
            Log::error('Doku Payout (Transfer) Exception:', ['ref_id' => $payoutRefId, 'error' => $e->getMessage()]);
            return ['status' => 'FAILED', 'message' => $e->getMessage()];
        }
    }

    /**
     * Helper untuk Generate Headers (Protokol SAC MERCHANT - KETAT PADA ATURAN)
     * Ini adalah logika final yang berhasil untuk POST dan GET
     */
    private function _generateHeadersSac(string $endpoint, string $bodyJson): array
    {
        $requestId = (string) Str::uuid();
        $timestamp = Carbon::now('UTC')->format('Y-m-d\TH:i:s\Z');
        
        // ==========================================================
        // === ✅ PERBAIKKAN DARI LOG ANDA: Gunakan Kredensial SAC ===
        // ==========================================================
        
        // 1. Siapkan header dasar
        $headers = [
            'Client-Id' => $this->sacClientId, // <-- PERBAIKAN
            'Request-Id' => $requestId,
            'Request-Timestamp' => $timestamp,
        ];

        // 2. Buat String-to-Sign dasar (4 baris)
        $signatureComponent = 
            "Client-Id:" . $this->sacClientId . "\n" . // <-- PERBAIKAN
            "Request-Id:" . $requestId . "\n" .
            "Request-Timestamp:" . $timestamp . "\n" .
            "Request-Target:" . $endpoint;

        // 3. HANYA jika ada body (POST), tambahkan Digest dan Content-Type
        if (!empty($bodyJson)) {
            // Ini request POST (Create, Payout, Transfer)
            $digest = base64_encode(hash('sha256', $bodyJson, true));
            
            // 3a. Tambahkan Digest (baris ke-5) ke string-to-sign
            $signatureComponent .= "\n" . "Digest:" . $digest;
            
            // 3b. Tambahkan Content-Type ke header
            $headers['Content-Type'] = 'application/json';

        } else {
            // Ini request GET (getBalance)
            // String-to-sign TETAP 4 baris (tidak ditambah Digest)
            // Header TETAP 3 header (tidak ditambah Content-Type)
        }

        // 4. Buat Signature dari string-to-sign yang sudah benar
        $signature = base64_encode(hash_hmac('sha256', $signatureComponent, $this->sacSecretKey, true)); // <-- PERBAIKAN
        $headers['Signature'] = "HMACSHA256=" . $signature;
        
        return $headers;
    }
}