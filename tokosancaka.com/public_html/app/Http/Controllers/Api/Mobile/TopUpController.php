<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Api;
use App\Services\DanaSignatureService;
use App\Services\DokuJokulService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class TopUpController extends Controller
{
    protected $danaSignature;

    public function __construct(DanaSignatureService $danaSignature)
    {
        $this->danaSignature = $danaSignature;
        $this->applyDynamicConfig(); // Load config DANA dari Database
    }

    /**
     * =========================================================================
     * API: MENGAMBIL RIWAYAT TOP UP
     * =========================================================================
     */
    public function history(Request $request)
    {
        try {
            $user = Auth::user();
            $query = Transaction::where('type', 'topup');

            if (strtolower($user->role) !== 'admin') {
                $query->where('user_id', $user->id_pengguna);
            }

            $transactions = $query->orderBy('created_at', 'desc')->paginate(15);

            $formattedData = collect($transactions->items())->map(function ($trx) {
                return [
                    'id'             => $trx->id,
                    'user_id'        => $trx->user_id,
                    'reference_id'   => $trx->reference_id,
                    'amount'         => $trx->amount,
                    'status'         => strtolower($trx->status),
                    'payment_method' => $trx->payment_method,
                    'payment_url'    => $trx->payment_url,
                    'created_at'     => $trx->created_at->format('d M Y, H:i'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedData,
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page'    => $transactions->lastPage(),
                    'per_page'     => $transactions->perPage(),
                    'total'        => $transactions->total(),
                ],
                'message' => 'Berhasil mengambil riwayat top up.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('API TopUp History Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan pada server.'], 500);
        }
    }

    /**
     * =========================================================================
     * API: MEMBUAT TRANSAKSI TOP UP BARU (DANA, DOKU, TRIPAY)
     * =========================================================================
     */
    public function store(Request $request)
    {
        Log::info('[API MOBILE] Menerima request Top Up saldo.', $request->all());

        $request->validate([
            'amount'         => 'required|numeric|min:10000',
            'payment_method' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $user = Auth::user();
            $amount = (int) $request->amount;
            $invoiceNumber = 'TOPUP-' . strtoupper(Str::random(10));

            // Bersihkan format hashtag (misal: #DANA jadi DANA) untuk disimpan ke DB
            $paymentMethodRaw = strtoupper(trim($request->payment_method));
            $paymentMethodClean = str_replace('#', '', $paymentMethodRaw);

            // 1. Buat transaksi di database (Tabel Transactions)
            $transaction = Transaction::create([
                'user_id'        => $user->id_pengguna,
                'reference_id'   => $invoiceNumber,
                'amount'         => $amount,
                'type'           => 'topup',
                'status'         => 'pending',
                'payment_method' => $paymentMethodClean,
                'description'    => 'Top up saldo via ' . $paymentMethodClean,
            ]);

            $paymentUrl = null;

            // =================================================================
            // 2. ROUTING PAYMENT GATEWAY BERDASARKAN INPUT APLIKASI
            // =================================================================

            // A. VIA DANA BINDING (AUTO DEBIT)
            if ($paymentMethodRaw === '#DANA_BINDING' || $paymentMethodRaw === 'DANA_BINDING') {
                Log::info("[API MOBILE] Eksekusi DANA Auto Debit (Binding).");

                if (empty($user->dana_access_token)) {
                    throw new Exception("Akun DANA Anda belum terhubung. Silakan hubungkan di menu profil.");
                }

                $danaRes = $this->_createTopUpDanaBinding($transaction, $user);

                if (!isset($danaRes['success']) || !$danaRes['success']) {
                    throw new Exception($danaRes['message'] ?? 'Gagal memproses Auto Debit DANA.');
                }

                // Jika DANA butuh verifikasi PIN, akan ada URL
                $paymentUrl = $danaRes['redirect_url'] ?? null;

                // Jika sukses instan tanpa URL, update status dan kembalikan respon
                if (!$paymentUrl && $danaRes['success']) {
                    $transaction->update(['status' => 'success']);
                    $user->increment('saldo', $amount);
                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Top Up Berhasil! Saldo DANA Anda telah terpotong otomatis.',
                        'data'    => ['reference_id' => $invoiceNumber, 'amount' => $amount, 'payment_url' => null]
                    ], 200);
                }
            }

            // B. VIA DANA GATEWAY BIASA (Checkout Gapura IPG)
            elseif ($paymentMethodRaw === '#DANA' || $paymentMethodRaw === 'DANA') {
                Log::info("[API MOBILE] Eksekusi DANA Payment Gateway.");

                $danaRes = $this->_createTopUpDanaGateway($transaction, $user);

                if (!isset($danaRes['success']) || !$danaRes['success']) {
                    throw new Exception($danaRes['message'] ?? 'Gagal membuat tagihan DANA.');
                }
                $paymentUrl = $danaRes['redirect_url'];
            }

            // C. VIA DOKU JOKUL
            elseif ($paymentMethodRaw === '#DOKU' || $paymentMethodRaw === 'DOKU_JOKUL') {
                Log::info("[API MOBILE] Eksekusi DOKU Jokul.");

                $dokuService = new DokuJokulService();
                $paymentUrl = $dokuService->createPayment($transaction->reference_id, $amount);

                if (empty($paymentUrl)) {
                    throw new Exception("Gagal mendapatkan link pembayaran dari DOKU Jokul.");
                }
            }

            // D. VIA TRIPAY (BCAVA, QRIS, dll)
            else {
                Log::info("[API MOBILE] Eksekusi Tripay untuk metode: " . $paymentMethodClean);

                $orderItems = [['sku' => 'TOPUP', 'name' => 'Top Up Saldo Akun', 'price' => $amount, 'quantity' => 1]];
                $tripayResponse = $this->_createTripayTransactionInternal($transaction, $orderItems, $user);

                if (empty($tripayResponse['success'])) {
                    throw new Exception($tripayResponse['message'] ?? 'Gagal membuat tagihan Tripay.');
                }
                $paymentUrl = $tripayResponse['data']['checkout_url'];
            }

            // =================================================================
            // 3. FINALISASI DATABASE
            // =================================================================
            if (empty($paymentUrl)) {
                throw new Exception("Gagal mendapatkan instruksi pembayaran dari Gateway.");
            }

            $transaction->payment_url = $paymentUrl;
            $transaction->save();
            DB::commit();

            // Kembalikan Response ke Aplikasi Mobile
            return response()->json([
                'success'      => true,
                'message'      => 'Tagihan Top Up berhasil dibuat.',
                'data'         => [
                    'reference_id' => $transaction->reference_id,
                    'amount'       => $transaction->amount,
                    'payment_url'  => $paymentUrl
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[API MOBILE] TopUp Store Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * =========================================================================
     * HELPER: EKSEKUTOR API DANA GATEWAY KHUSUS TOPUP (REVISI GAPURA IPG)
     * =========================================================================
     */
    private function _createTopUpDanaGateway(Transaction $transaction, $user)
    {
        $timestamp   = Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $expiryTime  = Carbon::now('Asia/Jakarta')->addMinutes(60)->format('Y-m-d\TH:i:sP');
        $finalAmount = number_format((float)$transaction->amount, 2, '.', '');

        // Memastikan clean invoice bebas karakter spesial agar tidak error di URL Params
        $cleanInvoice = preg_replace('/[^a-zA-Z0-9]/', '', $transaction->reference_id);

        // Gunakan parameter dari config DANA yang diload dinamis
        $merchantId = config('services.dana.merchant_id');

        // URL Redirect jika sukses/selesai transaksi
        $returnUrl = route('dana.return', ['trx_id' => $cleanInvoice]);

        $bodyArray = [
            "partnerReferenceNo" => $cleanInvoice,
            "merchantId"         => $merchantId,
            "amount"             => [
                "value"    => $finalAmount,
                "currency" => "IDR"
            ],
            "validUpTo"          => $expiryTime,
            "urlParams"          => [
                [
                    "url"        => $returnUrl,
                    "type"       => "PAY_RETURN",
                    "isDeeplink" => "N"
                ],
                [
                    "url"        => url('/dana/notify'),
                    "type"       => "NOTIFICATION",
                    "isDeeplink" => "N"
                ]
            ],
            "payOptionDetails"   => [
                [
                    "payMethod"   => "BALANCE",
                    "payOption"   => "",
                    "transAmount" => [
                        "value"    => $finalAmount,
                        "currency" => "IDR"
                    ]
                ]
            ],
            "additionalInfo"     => [
                "mcc"   => "5732",
                "order" => [
                    "orderTitle"        => "Top Up " . $cleanInvoice,
                    "merchantTransType" => "01",
                    "scenario"          => "REDIRECT",
                    "buyer"             => [
                        "externalUserId"   => (string) ($user->id_pengguna ?? 'GUEST'.rand(100,999)),
                        "externalUserType" => "MERCHANT_USER",
                        "nickname"         => Str::limit($user->nama_lengkap ?? 'Customer', 40),
                    ]
                ],
                "envInfo" => [
                    "sourcePlatform"    => "IPG",
                    "terminalType"      => "SYSTEM",
                    "orderTerminalType" => "WEB",
                ]
            ]
        ];

        $jsonBody = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // --- ENDPOINT GAPURA ---
        $relativePath = '/payment-gateway/v1.0/debit/payment-host-to-host.htm';

        try {
            $accessToken = $this->danaSignature->getAccessToken();
            $signature   = $this->danaSignature->generateSignature('POST', $relativePath, $jsonBody, $timestamp);

            $baseUrl = config('services.dana.base_url');

            $headers = [
                'Authorization' => 'Bearer ' . $accessToken,
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => Str::random(32),
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'Content-Type'  => 'application/json',
                'CHANNEL-ID'    => '95221',
                'ORIGIN'        => config('services.dana.origin'),
            ];

            Log::info('DANA GAPURA Request Payload:', $bodyArray);

            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post($baseUrl . $relativePath);

            $result = $response->json();

            Log::info('DANA GAPURA Response:', $result);

            if (isset($result['responseCode']) && $result['responseCode'] == '2005400') {
                $redirectUrl = $result['webRedirectUrl'] ?? $result['appLinkUrl'] ?? null;
                if ($redirectUrl) {
                    return ['success' => true, 'redirect_url' => $redirectUrl];
                }
            }
            return ['success' => false, 'message' => $result['responseMessage'] ?? 'Unknown DANA Error'];

        } catch (\Exception $e) {
            Log::error('Koneksi DANA Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Koneksi DANA Error: ' . $e->getMessage()];
        }
    }

 /**
     * =========================================================================
     * HELPER: EKSEKUTOR API DANA BINDING (EXPRESS CHECKOUT MOBILE - VIA WEBVIEW)
     * =========================================================================
     */
    private function _createTopUpDanaBinding(Transaction $transaction, $userAccount)
    {
        $trxId = $transaction->reference_id;
        Log::info('LOG LOG: [DANA BINDING] Memulai Express Checkout (1-Click) untuk Top Up: ' . $trxId);

        $timestamp = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $validUpTo = \Carbon\Carbon::now('Asia/Jakarta')->addMinutes(30)->format('Y-m-d\TH:i:sP');
        $amountValue = number_format($transaction->amount, 2, '.', '');
        
        $path = '/rest/redirection/v1.0/debit/payment-host-to-host';

        $body = [
            "partnerReferenceNo" => $trxId,
            "merchantId"         => config('services.dana.merchant_id'),
            "validUpTo"          => $validUpTo,
            "amount"             => ["value" => $amountValue, "currency" => "IDR"],
            "urlParams"          => [
                [
                    "url" => route('dana.return', ['trx_id' => $trxId]),
                    "type" => "PAY_RETURN",
                    "isDeeplink" => "N" // KUNCI 1: Paksa ke "N" agar DANA memberikan Web URL murni
                ],
                [
                    "url" => url('/dana/notify'),
                    "type" => "NOTIFICATION",
                    "isDeeplink" => "N"
                ]
            ],
            "payOptionDetails"   => [
                [
                    "payMethod"   => "BALANCE", 
                    "payOption"   => "BALANCE", 
                    "transAmount" => ["value" => $amountValue, "currency" => "IDR"]
                ]
            ],
            "additionalInfo"     => [
                "supportDeepLinkCheckoutUrl" => "true",
                "productCode"                => "51051000100000000001",
                "mcc"                        => "5732",
                "order" => [
                    "orderTitle"        => substr("Top Up " . $trxId, 0, 64),
                    "merchantTransType" => "01",
                    //"scenario"          => "REDIRECT",
                    "buyer" => [
                        "externalUserId"   => (string) $userAccount->id_pengguna,
                        "externalUserType" => "MERCHANT_USER",
                        "nickname"         => substr(preg_replace('/[^a-zA-Z0-9 ]/', '', $userAccount->nama_lengkap ?? 'Customer'), 0, 64)
                    ]
                ],
                "envInfo" => [
                    "sourcePlatform"    => "IPG",
                    "terminalType"      => "SYSTEM",
                    "orderTerminalType" => "WEB" // KUNCI 2: Pertahankan "WEB"
                ]
            ]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            $accessTokenB2B = $this->danaSignature->getAccessToken();
            $signature      = $this->danaSignature->generateSignature('POST', $path, $jsonBody, $timestamp);
            $baseUrl        = config('services.dana.base_url');

            $headers = [
                'Content-Type'           => 'application/json',
                'Authorization'          => 'Bearer ' . $accessTokenB2B,
                'Authorization-Customer' => 'Bearer ' . $userAccount->dana_access_token, 
                'X-TIMESTAMP'            => $timestamp,
                'X-SIGNATURE'            => $signature,
                'ORIGIN'                 => config('services.dana.origin'),
                'X-PARTNER-ID'           => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID'          => (string) time() . \Illuminate\Support\Str::random(6),
                'X-DEVICE-ID'            => 'SANCAKA-APP-MBL',
                'CHANNEL-ID'             => '95221'
            ];

            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post($baseUrl . $path);

            $result = $response->json();

            // CEK RESPON SUKSES SESUAI DOKUMEN (2005400)
            if (isset($result['responseCode']) && $result['responseCode'] === '2005400') {

                // KUNCI 3: KITA HANYA AMBIL WEB URL-NYA SAJA (webRedirectUrl). JANGAN AMBIL appLinkUrl!
                // Ini memastikan In-App Browser (WebBrowser) di HP bisa merendernya dengan lancar.
                $redirectUrl = $result['webRedirectUrl'] ?? null;
                
                if (!empty($redirectUrl)) {
                    Log::info('LOG LOG: [DANA BINDING] Berhasil generate URL Web Express Checkout.');
                    
                    // Kembalikan URL web ini ke aplikasi mobile
                    return [
                        'success' => true, 
                        'redirect_url' => $redirectUrl
                    ];
                }

                // Fallback jika anehnya DANA tidak memberikan URL Web
                Log::error('LOG LOG: [API MOBILE] Transaksi DANA menggantung. Tidak ada Web URL yang diterbitkan.');
                return ['success' => false, 'message' => 'Gagal: URL Pembayaran Web DANA tidak diterbitkan.'];
            }

            return ['success' => false, 'message' => $result['responseMessage'] ?? 'DANA API Error'];

        } catch (\Exception $e) {
            Log::error('LOG LOG: [DANA BINDING] Fatal Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Koneksi ke sistem DANA gagal. Silakan coba beberapa saat lagi.'];
        }
    }

    /**
     * =========================================================================
     * HELPER: EKSEKUTOR API TRIPAY KHUSUS TOPUP
     * =========================================================================
     */
    private function _createTripayTransactionInternal(Transaction $transaction, array $orderItems, $user): array
    {
        $mode = Api::getValue('TRIPAY_MODE', 'global', 'sandbox');

        if ($mode === 'production') {
            $baseUrl      = 'https://tripay.co.id/api/transaction/create';
            $apiKey       = Api::getValue('TRIPAY_API_KEY', 'production');
            $privateKey   = Api::getValue('TRIPAY_PRIVATE_KEY', 'production');
            $merchantCode = Api::getValue('TRIPAY_MERCHANT_CODE', 'production');
        } else {
            $baseUrl      = 'https://tripay.co.id/api-sandbox/transaction/create';
            $apiKey       = Api::getValue('TRIPAY_API_KEY', 'sandbox');
            $privateKey   = Api::getValue('TRIPAY_PRIVATE_KEY', 'sandbox');
            $merchantCode = Api::getValue('TRIPAY_MERCHANT_CODE', 'sandbox');
        }

        $payload = [
            'method'         => $transaction->payment_method, // Misal: BCAVA, QRIS
            'merchant_ref'   => $transaction->reference_id,
            'amount'         => $transaction->amount,
            'customer_name'  => $user->nama_lengkap ?? 'User Sancaka',
            'customer_email' => $user->email ?? ('user'.$user->id_pengguna.'@tokosancaka.com'),
            'customer_phone' => $user->no_wa ?? '081111111111',
            'order_items'    => $orderItems,
            'return_url'     => url('/'), // Webview Android akan memantau kembalian url ini
            'expired_time'   => time() + (24 * 60 * 60),
            'signature'      => hash_hmac('sha256', $merchantCode . $transaction->reference_id . $transaction->amount, $privateKey),
        ];

        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])->timeout(30)->post($baseUrl, $payload);
            $responseData = $response->json();

            if (!$response->successful() || !isset($responseData['success']) || $responseData['success'] !== true) {
                return ['success' => false, 'message' => $responseData['message'] ?? 'Gagal membuat tagihan Tripay.'];
            }
            return $responseData;
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error API Tripay: ' . $e->getMessage()];
        }
    }

   /**
     * Helper: Dinamisasi Config DANA
     */
    private function applyDynamicConfig()
    {
        $settings = \App\Models\Api::pluck('value', 'key')->toArray();
        $isProduction = ($settings['dana_production_mode'] ?? '0') == '1';

        if ($isProduction) {
            config([
                'services.dana.dana_env'      => 'PRODUCTION',
                'services.dana.base_url'      => 'https://api.saas.dana.id',
                'services.dana.merchant_id'   => $settings['dana_prod_merchant_id'] ?? env('DANA_PROD_MERCHANT_ID'),
                'services.dana.client_id'     => $settings['dana_prod_client_id'] ?? env('DANA_PROD_CLIENT_ID'),
                'services.dana.x_partner_id'  => $settings['dana_prod_client_id'] ?? env('DANA_PROD_CLIENT_ID'),
                'services.dana.private_key'   => $settings['dana_prod_private_key'] ?? env('DANA_PROD_PRIVATE_KEY'),
                'services.dana.client_secret' => $settings['dana_prod_client_secret'] ?? env('DANA_PROD_CLIENT_SECRET'),
                'services.dana.origin'        => env('DANA_ORIGIN', 'https://tokosancaka.com'),
            ]);
        } else {
            config([
                'services.dana.dana_env'      => 'SANDBOX',
                'services.dana.base_url'      => 'https://api.sandbox.dana.id',
                'services.dana.merchant_id'   => $settings['dana_sandbox_merchant_id'] ?? env('DANA_MERCHANT_ID'),
                'services.dana.client_id'     => $settings['dana_sandbox_client_id'] ?? env('DANA_X_PARTNER_ID'),
                'services.dana.x_partner_id'  => $settings['dana_sandbox_client_id'] ?? env('DANA_X_PARTNER_ID'),
                'services.dana.private_key'   => $settings['dana_sandbox_private_key'] ?? env('DANA_PRIVATE_KEY'),
                'services.dana.client_secret' => $settings['dana_sandbox_client_secret'] ?? env('DANA_CLIENT_SECRET'),
                'services.dana.origin'        => env('DANA_ORIGIN', 'https://tokosancaka.com'),
            ]);
        }
    }
}
