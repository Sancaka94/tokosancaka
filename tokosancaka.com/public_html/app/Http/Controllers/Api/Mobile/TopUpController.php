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

                // $danaRes = $this->_createTopUpDanaGateway($transaction, $user);

                $danaRes = $this->createPaymentDanaBindingWidget($transaction, $user);
                

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
    
    /* private function _createTopUpDanaGateway(Transaction $transaction, $user)
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
     * HELPER: EKSEKUTOR API DANA GATEWAY KHUSUS TOPUP (CUSTOM CHECKOUT)
     * =========================================================================
     */
    private function _createTopUpDanaGateway(Transaction $transaction, $user)
    {
        $trxId = $transaction->reference_id;
        Log::info('LOG LOG: [API MOBILE - DANA CUSTOM CHECKOUT] Memulai request Create Order untuk: ' . $trxId);

        $timestamp = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        
        // Aturan Sandbox: validUpTo harus <= 30 menit dari request time
        $validUpTo = \Carbon\Carbon::now('Asia/Jakarta')->addMinutes(29)->format('Y-m-d\TH:i:sP');
        $amountValue = number_format((float)$transaction->amount, 2, '.', '');
        
        $path = '/payment-gateway/v1.0/debit/payment-host-to-host.htm';

        $body = [
            "partnerReferenceNo" => (string) $trxId,
            "merchantId"         => config('services.dana.merchant_id'),
            "amount"             => [
                "value"    => $amountValue,
                "currency" => "IDR"
            ],
            "validUpTo"          => $validUpTo,
            "urlParams"          => [
                [
                    // Endpoint callback webview/browser, biarkan pakai web karena akan ditangkap oleh Expo
                    "url"        => route('dana.return', ['trx_id' => $trxId]),
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
                        "value"    => $amountValue,
                        "currency" => "IDR"
                    ]
                ]
            ],
            "additionalInfo"     => [
                "order"   => [
                    "orderTitle" => substr("Top Up " . $trxId, 0, 64),
                    "scenario"   => "API"
                ],
                "mcc"     => "5732", 
                "envInfo" => [
                    "sourcePlatform"    => "IPG",
                    "terminalType"      => "SYSTEM",
                    "orderTerminalType" => "APP" // Khusus Expo, kita set APP
                ]
            ]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            $accessToken = $this->danaSignature->getAccessToken();
            $signature   = $this->danaSignature->generateSignature('POST', $path, $jsonBody, $timestamp);
            $baseUrl     = config('services.dana.base_url');

            $headers = [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'ORIGIN'        => config('services.dana.origin'),
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time() . \Illuminate\Support\Str::random(6),
                'CHANNEL-ID'    => '95221'
            ];

            Log::info('LOG LOG: [API MOBILE - DANA CUSTOM CHECKOUT] Request Body Terkirim:', $body);

            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post($baseUrl . $path);

            $result = $response->json();

            // CEK RESPON SUKSES (2005400)
            if (isset($result['responseCode']) && $result['responseCode'] === '2005400') {

                // Ambil URL (Bisa appLinkUrl jika APP, atau webRedirectUrl jika via WebView)
                $redirectUrl = $result['appLinkUrl'] ?? $result['webRedirectUrl'] ?? null;
                
                if (!empty($redirectUrl)) {
                    Log::info('LOG LOG: [API MOBILE - DANA CUSTOM CHECKOUT] Berhasil generate URL Checkout.');
                    
                    // KEMBALIKAN ARRAY AGAR DITANGKAP OLEH FUNGSI STORE()
                    return [
                        'success'      => true, 
                        'redirect_url' => $redirectUrl
                    ];
                }

                Log::error('LOG LOG: [API MOBILE - DANA CUSTOM CHECKOUT] Transaksi menggantung. URL tidak diterbitkan.', $result);
                return ['success' => false, 'message' => 'Gagal: URL Pembayaran DANA tidak diterbitkan.'];
            }

            // PENANGANAN ERROR DANA
            $errorCode  = $result['responseCode'] ?? 'UNKNOWN';
            $pesanGagal = $result['responseMessage'] ?? 'Terjadi kesalahan pada sistem pembayaran.';

            Log::error("LOG LOG: [API MOBILE - DANA CUSTOM CHECKOUT] Gagal generate URL. Code: $errorCode | Msg: $pesanGagal", $result);
            return ['success' => false, 'message' => "Gagal dari DANA [$errorCode]: $pesanGagal"];

        } catch (\Exception $e) {
            Log::error('LOG LOG: [API MOBILE - DANA CUSTOM CHECKOUT] Fatal Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Koneksi ke sistem DANA gagal. Silakan coba beberapa saat lagi.'];
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
                    "isDeeplink" => "N" 
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
                    "scenario"          => "REDIRECT",
                    "buyer" => [
                        "externalUserId"   => (string) $userAccount->id_pengguna,
                        "externalUserType" => "MERCHANT_USER",
                        "nickname"         => substr(preg_replace('/[^a-zA-Z0-9 ]/', '', $userAccount->nama_lengkap ?? 'Customer'), 0, 64)
                    ]
                ],
                "envInfo" => [
                    "sourcePlatform"    => "IPG",
                    "terminalType"      => "SYSTEM",
                    "orderTerminalType" => "APP"
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

            if (isset($result['responseCode']) && $result['responseCode'] === '2005400') {

                $redirectUrl = $result['webRedirectUrl'] ?? null;
                
                if (!empty($redirectUrl)) {
                    Log::info('LOG LOG: [DANA BINDING] Berhasil generate URL Web Express Checkout.');
                    
                    return [
                        'success' => true, 
                        'redirect_url' => $redirectUrl
                    ];
                }

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

    /**
     * =========================================================================
     * API CANCEL ORDER DANA (REGULAR & WIDGET)
     * =========================================================================
     */
    public function cancelDanaPayment($orderId)
    {
        return $this->processDanaCancel($orderId, '/payment-gateway/v1.0/debit/cancel.htm');
    }

    public function cancelDanaWidgetPayment($orderId)
    {
        return $this->processDanaCancel($orderId, '/v1.0/debit/cancel.htm');
    }

    private function processDanaCancel($orderId, $path)
    {
        Log::info('LOG LOG: [DANA CANCEL] Memulai proses Cancel untuk Order ID: ' . $orderId . ' via ' . $path);
        
        DB::beginTransaction();
        try {
            $user = \Illuminate\Support\Facades\Auth::user();
            $transaction = \App\Models\Transaction::where('reference_id', $orderId)->lockForUpdate()->first();
            
            if (!$transaction) {
                DB::rollBack();
                return $this->respondError('Transaksi tidak ditemukan.', 404);
            }

            // PROTEKSI KEAMANAN (IDOR): Pastikan user hanya bisa membatalkan transaksinya sendiri
            if ($transaction->user_id !== $user->id_pengguna && strtolower($user->role) !== 'admin') {
                DB::rollBack();
                return $this->respondError('Anda tidak memiliki izin untuk membatalkan transaksi ini.', 403);
            }

            if (strtoupper($transaction->status) !== 'PENDING') {
                DB::rollBack();
                Log::warning('LOG LOG: [DANA CANCEL] Ditolak. Transaksi berstatus: ' . $transaction->status);
                return $this->respondError('Hanya transaksi berstatus PENDING yang dapat dibatalkan.', 400);
            }

            $createdAt = \Carbon\Carbon::parse($transaction->created_at)->timezone('Asia/Jakarta');
            if (now('Asia/Jakarta')->diffInMinutes($createdAt) > 30) {
                DB::rollBack();
                Log::warning('LOG LOG: [DANA CANCEL] Ditolak. Waktu lebih dari 30 menit.');
                return $this->respondError('Batas waktu pembatalan (30 menit) telah kedaluwarsa.', 400);
            }

            $this->applyDynamicConfig();
            $merchantId = config('services.dana.merchant_id');
            $timestamp  = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');

            $body = [
                "originalPartnerReferenceNo" => (string) $orderId,
                "merchantId"                 => (string) $merchantId
            ];
            
            $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
            $accessToken = $this->danaSignature->getAccessToken();
            $signature   = $this->danaSignature->generateSignature('POST', $path, $jsonBody, $timestamp);
            $externalId  = date('YmdHis') . mt_rand(10000, 99999);

            $headers = [
                'Content-Type'  => 'application/json',
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'ORIGIN'        => config('services.dana.origin'),
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) $externalId,
                'CHANNEL-ID'    => '95221'
            ];

            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            Log::info('LOG LOG: [DANA CANCEL] Response DANA: ', $result ?? ['raw' => $response->body()]);
            
            if (($result['responseCode'] ?? '') === '2005700') {
                $transaction->update(['status' => 'failed']); 
                DB::commit(); 
                Log::info('LOG LOG: [DANA CANCEL] Berhasil dibatalkan di DANA dan Database.');
                return $this->respondSuccess('Pesanan berhasil dibatalkan secara permanen di DANA.');
            }

            DB::rollBack();
            return $this->respondError('Gagal membatalkan pesanan: ' . ($result['responseMessage'] ?? 'Unknown Error'), 400);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('LOG LOG: [DANA CANCEL] Exception Error: ' . $e->getMessage());
            return $this->respondError('Sistem Error: Terjadi kesalahan koneksi saat membatalkan.', 500);
        }
    }

    /**
     * =========================================================================
     * API REFUND ORDER DANA (REGULAR & WIDGET)
     * =========================================================================
     */
    public function refundDanaPayment($orderId)
    {
        return $this->processDanaRefund($orderId, '/payment-gateway/v1.0/debit/refund.htm');
    }

    public function refundDanaWidgetPayment($orderId)
    {
        return $this->processDanaRefund($orderId, '/v1.0/debit/refund.htm');
    }

    private function processDanaRefund($orderId, $path)
    {
        Log::info('LOG LOG: [DANA REFUND] Memulai proses Refund untuk Order ID: ' . $orderId . ' via ' . $path);
        
        DB::beginTransaction();
        try {
            $userAuth = \Illuminate\Support\Facades\Auth::user();
            $transaction = \App\Models\Transaction::where('reference_id', $orderId)->lockForUpdate()->first();
            
            if (!$transaction) {
                DB::rollBack();
                return $this->respondError('Transaksi tidak ditemukan.', 404);
            }

            // PROTEKSI KEAMANAN (IDOR): Pastikan user hanya bisa refund transaksinya sendiri
            if ($transaction->user_id !== $userAuth->id_pengguna && strtolower($userAuth->role) !== 'admin') {
                DB::rollBack();
                return $this->respondError('Anda tidak memiliki izin untuk memproses refund transaksi ini.', 403);
            }

            if (!in_array(strtoupper($transaction->status), ['SUCCESS', 'PAID'])) {
                DB::rollBack();
                Log::warning('LOG LOG: [DANA REFUND] Ditolak. Transaksi berstatus: ' . $transaction->status);
                return $this->respondError('Hanya transaksi berstatus SUCCESS/PAID yang dapat di-refund.', 400);
            }

            $createdAt = \Carbon\Carbon::parse($transaction->created_at)->timezone('Asia/Jakarta');
            if (now('Asia/Jakarta')->diffInMinutes($createdAt) > 30) {
                DB::rollBack();
                Log::warning('LOG LOG: [DANA REFUND] Ditolak. Waktu transaksi lebih dari 30 menit.');
                return $this->respondError('Batas waktu refund (30 menit) telah kedaluwarsa.', 400);
            }

            // PROTEKSI SALDO MINUS: Ambil state user terbaru dari DB (lock for update opsional)
            $userModel = \App\Models\User::where('id_pengguna', $transaction->user_id)->lockForUpdate()->first();
            if (!$userModel || $userModel->saldo < $transaction->amount) {
                DB::rollBack();
                Log::warning('LOG LOG: [DANA REFUND] Ditolak. Saldo tidak mencukupi (kurang dari amount).');
                return $this->respondError('Gagal Refund: Saldo aplikasi Anda tidak mencukupi (saldo mungkin sudah terpakai).', 400);
            }

            $this->applyDynamicConfig();
            $merchantId = config('services.dana.merchant_id');
            $timestamp  = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
            
            $partnerRefundNo = date('YmdHis') . mt_rand(10000, 99999); 
            $refundAmountValue = number_format((float)$transaction->amount, 2, '.', '');

            $body = [
                "merchantId"                 => (string) $merchantId,
                "originalPartnerReferenceNo" => (string) $orderId,
                "partnerRefundNo"            => (string) $partnerRefundNo,
                "refundAmount" => [
                    "value"    => $refundAmountValue,
                    "currency" => "IDR"
                ]
            ];
            
            $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
            $accessToken = $this->danaSignature->getAccessToken();
            $signature   = $this->danaSignature->generateSignature('POST', $path, $jsonBody, $timestamp);
            $externalId  = date('YmdHis') . mt_rand(10000, 99999);

            $headers = [
                'Content-Type'  => 'application/json',
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'ORIGIN'        => config('services.dana.origin'),
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) $externalId,
                'CHANNEL-ID'    => '95221'
            ];

            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            Log::info('LOG LOG: [DANA REFUND] Response DANA: ', $result ?? ['raw' => $response->body()]);
            
            if (($result['responseCode'] ?? '') === '2005800') {
                // Tarik saldo karena sudah dipastikan cukup pada pengecekan di atas
                $userModel->decrement('saldo', $transaction->amount);
                $transaction->update(['status' => 'refunded']); 
                DB::commit(); 

                Log::info('LOG LOG: [DANA REFUND] Berhasil di-refund! Saldo ditarik.');
                return $this->respondSuccess('Dana berhasil dikembalikan ke akun DANA pelanggan.');
            }

            DB::rollBack();
            return $this->respondError('Gagal memproses refund: ' . ($result['responseMessage'] ?? 'Unknown Error'), 400);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('LOG LOG: [DANA REFUND] Exception Error: ' . $e->getMessage());
            return $this->respondError('Sistem Error: Terjadi kesalahan koneksi saat me-refund.', 500);
        }
    }

    /**
     * =========================================================================
     * HELPER RESPONSE KHUSUS API MOBILE (JSON ONLY)
     * =========================================================================
     */
    private function respondSuccess($message)
    {
        // Sengaja diganti menjadi format boolean (success: true/false) 
        // agar sejalan dengan response history() dan store() Anda sebelumnya
        return response()->json([
            'success' => true,
            'message' => $message
        ], 200);
    }

    private function respondError($message, $statusCode = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $statusCode);
    }

    /**
     * =========================================================================
     * API EXPO: CEK STATUS TRANSAKSI DANA
     * =========================================================================
     */
    public function apiCheckDanaPaymentStatus($orderId)
    {
        try {
            $user = \Illuminate\Support\Facades\Auth::user();
            
            // 1. Pastikan transaksi ada di database lokal dan milik user tersebut
            $transaction = \App\Models\Transaction::where('reference_id', $orderId)
                ->where('user_id', $user->id_pengguna)
                ->first();

            if (!$transaction) {
                return response()->json(['success' => false, 'message' => 'Transaksi tidak ditemukan.'], 404);
            }

            // 2. Jika status di lokal sudah final, cegah hit API DANA (Hemat Quota / Cegah Spam)
            $localStatus = strtoupper($transaction->status);
            if (in_array($localStatus, ['SUCCESS', 'PAID'])) {
                return response()->json(['success' => true, 'status' => 'SUCCESS', 'message' => 'Transaksi sudah berstatus Lunas.']);
            }
            if (in_array($localStatus, ['FAILED', 'REFUNDED', 'CANCELLED'])) {
                return response()->json(['success' => true, 'status' => $localStatus, 'message' => 'Transaksi sudah berstatus ' . $localStatus]);
            }

            // 3. Konfigurasi DANA
            $this->applyDynamicConfig();
            $merchantId = config('services.dana.merchant_id');
            $partnerId  = config('services.dana.x_partner_id');
            $timestamp  = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
            
            // Menyesuaikan endpoint dengan metode pembayaran. 
            // DANA_BINDING menggunakan /rest/v1.1/, sedangkan DANA reguler menggunakan /payment-gateway/v1.0/
            $isBinding = str_contains(strtoupper($transaction->payment_method), 'BINDING');
            $path = $isBinding ? '/rest/v1.1/debit/status' : '/payment-gateway/v1.0/debit/status.htm';

            $body = [
                "originalPartnerReferenceNo" => $orderId,
                "serviceCode"                => "54", 
                "merchantId"                 => $merchantId
            ];
            
            $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);

            // 4. Generate Signature
            $accessToken = $this->danaSignature->getAccessToken();
            $signature   = $this->danaSignature->generateSignature('POST', $path, $jsonBody, $timestamp);

            $headers = [
                'Content-Type'  => 'application/json',
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'ORIGIN'        => config('services.dana.origin'),
                'X-PARTNER-ID'  => $partnerId,
                'X-EXTERNAL-ID' => (string) time() . \Illuminate\Support\Str::random(6),
                'CHANNEL-ID'    => '95221'
            ];

            // 5. Eksekusi Request POST ke DANA
            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            $responseCode = $result['responseCode'] ?? 'UNKNOWN';

            // 6. Evaluasi Respon DANA
            if ($responseCode === '2005500') {
                $statusDana = $result['latestTransactionStatus'] ?? null;
                
                // STATUS: BERHASIL LUNAS
                if ($statusDana === '00') {
                    // Panggil prosesor utama agar saldo user otomatis ditambah & notifikasi terkirim
                    self::processTopUp($orderId, 'PAID', $transaction->amount);
                    return response()->json([
                        'success' => true, 
                        'status'  => 'SUCCESS',
                        'message' => 'Pembayaran berhasil dikonfirmasi! Saldo telah ditambahkan.'
                    ]);
                } 
                
                // STATUS: PENDING
                elseif (in_array($statusDana, ['01', '02', '03'])) {
                    return response()->json([
                        'success' => true, 
                        'status'  => 'PENDING',
                        'message' => 'Menunggu pembayaran. Silakan selesaikan pembayaran di aplikasi DANA.'
                    ]);
                } 
                
                // STATUS: GAGAL / KADALUARSA / CANCEL
                else {
                    $transaction->update(['status' => 'failed']);
                    return response()->json([
                        'success' => true, 
                        'status'  => 'FAILED',
                        'message' => 'Transaksi telah kadaluarsa atau dibatalkan.'
                    ]);
                }
            }

            // Jika API DANA membalas dengan error gateway (Misal: 404 Transaction Not Found)
            if ($responseCode === '4045501') {
                $transaction->update(['status' => 'failed']);
                return response()->json(['success' => true, 'status' => 'FAILED', 'message' => 'Transaksi tidak ditemukan di sistem DANA (Kadaluarsa).']);
            }

            return response()->json([
                'success' => false, 
                'message' => $result['responseMessage'] ?? 'Terjadi kesalahan dari server DANA.'
            ], 400);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('API EXPO DANA Cek Status Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Sistem Error: Terjadi gangguan koneksi jaringan.'], 500);
        }
    }

    public function createPaymentDanaBindingWidget(Transaction $transaction, $userAccount)
    {
        $trxId = $transaction->reference_id;
        Log::info('LOG LOG: [DANA BINDING EXPO] Memulai Express Checkout (1-Click) untuk Top Up: ' . $trxId);

        $timestamp = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $validUpTo = \Carbon\Carbon::now('Asia/Jakarta')->addMinutes(30)->format('Y-m-d\TH:i:sP');
        
        $path = '/rest/redirection/v1.0/debit/payment-host-to-host';

        $amountValue = number_format((float)$transaction->amount, 2, '.', '');

        $body = [
            "partnerReferenceNo" => (string) $trxId,
            "merchantId"         => config('services.dana.merchant_id'),
            "validUpTo"          => $validUpTo, 
            "amount" => [
                "value"    => $amountValue,
                "currency" => "IDR"
            ],
            "urlParams" => [
                [
                    "type"       => "NOTIFICATION",
                    "url"        => url('/dana/notify')
                ],
                [
                    "type"       => "PAY_RETURN",
                    "url"        => route('dana.return', ['trx_id' => $trxId]),
                    "isDeeplink" => "N"
                ],
                [
                    "type"       => "MAIN_APP_PAY_RETURN",
                    "url"        => route('dana.return', ['trx_id' => $trxId]), 
                    "isDeeplink" => "Y"
                ]
            ],
            "payOptionDetails" => [
                [
                    "payMethod"   => "BALANCE",
                    "payOption"   => "BALANCE",
                    "transAmount" => [
                        "value"    => $amountValue,
                        "currency" => "IDR"
                    ]
                ]
            ],
            "additionalInfo" => [
                "order" => [
                    "orderTitle"        => substr("Top Up " . $trxId, 0, 64),
                    "merchantTransType" => "01",
                    "buyer" => [
                        "externalUserId"   => (string) $userAccount->id_pengguna,
                        "externalUserType" => "MERCHANT_USER",
                        "nickname"         => substr(preg_replace('/[^a-zA-Z0-9 ]/', '', $userAccount->nama_lengkap ?? 'Customer'), 0, 64)
                    ],
                    "goods" => [
                        [
                            "merchantGoodsId" => "ITEM-" . $trxId,
                            "description"     => "Top Up Saldo Aplikasi",
                            "category"        => "DIGITAL_GOODS",
                            "price"           => [
                                "value"    => $amountValue,
                                "currency" => "IDR"
                            ],
                            "unit"            => "pcs",
                            "quantity"        => "1",
                            "name"            => "Saldo Top Up"
                        ]
                    ]
                ],
                "mcc"                        => "5732", 
                "envInfo" => [
                    "sourcePlatform"    => "IPG",
                    "terminalType"      => "SYSTEM",
                    "orderTerminalType" => "WEB" 
                ],
                "productCode"                => "51051000100000000001",
                "supportDeepLinkCheckoutUrl" => "true" 
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
                'X-DEVICE-ID'            => 'SANCAKA-APP-MOBILE',
                'CHANNEL-ID'             => '95221'
            ];

            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post($baseUrl . $path);

            $result = $response->json();

            if (isset($result['responseCode']) && $result['responseCode'] === '2005400') {

                $redirectUrl = $result['webRedirectUrl'] ?? null;
                
                if (!empty($redirectUrl)) {
                    Log::info('LOG LOG: [DANA BINDING EXPO] Berhasil generate URL Express Checkout.');
                    
                    // =====================================================================
                    // TAHAP 2: REQUEST APPLY OTT
                    // =====================================================================
                    $pathOtt = '/rest/v1.1/qr/apply-ott';
                    $timestampOtt = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');

                    $bodyOtt = [
                        "userResources" => ["OTT"],
                        "additionalInfo" => [
                            "accessToken" => $userAccount->dana_access_token
                        ]
                    ];
                    $jsonBodyOtt = json_encode($bodyOtt, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    $signatureOtt = $this->danaSignature->generateSignature('POST', $pathOtt, $jsonBodyOtt, $timestampOtt);

                    $headersOtt = [
                        'Content-Type'           => 'application/json',
                        'Authorization'          => 'Bearer ' . $accessTokenB2B, 
                        'Authorization-Customer' => 'Bearer ' . $userAccount->dana_access_token,
                        'X-TIMESTAMP'            => $timestampOtt,
                        'X-SIGNATURE'            => $signatureOtt,
                        'ORIGIN'                 => config('services.dana.origin'),
                        'X-PARTNER-ID'           => config('services.dana.x_partner_id'),
                        'X-EXTERNAL-ID'          => (string) time() . \Illuminate\Support\Str::random(6),
                        'X-DEVICE-ID'            => 'SANCAKA-APP-MOBILE',
                        'CHANNEL-ID'             => '95221'
                    ];

                    $responseOtt = \Illuminate\Support\Facades\Http::withHeaders($headersOtt)
                        ->withBody($jsonBodyOtt, 'application/json')
                        ->post($baseUrl . $pathOtt);

                    $resultOtt = $responseOtt->json();
                    
                    // Jika OTT Berhasil
                    if (isset($resultOtt['responseCode']) && $resultOtt['responseCode'] === '2004900') {
                        $ottToken = $resultOtt['userResources'][0]['value'] ?? null;
                        
                        if ($ottToken) {
                            $separator = str_contains($redirectUrl, '?') ? '&' : '?';
                            $redirectUrl .= $separator . 'ott=' . $ottToken;
                        }
                    } else {
                        Log::warning('LOG LOG: [DANA BINDING EXPO] Gagal Apply OTT, fallback ke URL Checkout biasa.', $resultOtt);
                    }
                    
                    // KEMBALIKAN DALAM BENTUK ARRAY AGAR FUNGSI store() BISA MEMBACANYA
                    return [
                        'success'      => true,
                        'redirect_url' => $redirectUrl
                    ];
                }

                return [
                    'success' => false,
                    'message' => 'Gagal: URL Pembayaran DANA tidak diterbitkan oleh server pusat.'
                ];
            }

            // PENANGANAN ERROR DANA
            $errorCode  = $result['responseCode'] ?? 'UNKNOWN';
            $pesanGagal = $result['responseMessage'] ?? 'Terjadi kesalahan pada sistem pembayaran.';

            Log::error("LOG LOG: [DANA BINDING EXPO] Gagal generate URL. Code: $errorCode | Msg: $pesanGagal");

            return [
                'success' => false,
                'message' => "Gagal dari DANA [$errorCode]: $pesanGagal"
            ];

        } catch (\Exception $e) {
            Log::error('LOG LOG: [DANA BINDING EXPO] Fatal Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Koneksi ke sistem DANA gagal. Silakan coba beberapa saat lagi.'
            ];
        }
    }
}
