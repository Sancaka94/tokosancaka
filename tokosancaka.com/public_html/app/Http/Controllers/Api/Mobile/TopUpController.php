<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Api;
use App\Services\DanaSignatureService;
use App\Services\DokuJokulService; // Tambahkan Doku Service
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
        $this->applyDynamicConfig(); // Load config DANA dari DB
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

            // Gunakan Pengecekan Role
            if ($user->role !== 'admin') {
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
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server.'
            ], 500);
        }
    }

    /**
     * =========================================================================
     * API: MEMBUAT TRANSAKSI TOP UP BARU (DENGAN PAYMENT GATEWAY)
     * =========================================================================
     */
    public function store(Request $request)
    {
        Log::info('[API MOBILE] Menerima request Top Up saldo.');

        $request->validate([
            'amount'         => 'required|numeric|min:10000',
            'payment_method' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $user = Auth::user();
            $amount = (int) $request->amount;
            $invoiceNumber = 'TOPUP-' . strtoupper(Str::random(10));
            $paymentMethod = strtoupper(trim($request->payment_method));

            // 1. Buat transaksi di database (Tabel Transactions)
            $transaction = Transaction::create([
                'user_id'        => $user->id_pengguna,
                'reference_id'   => $invoiceNumber,
                'amount'         => $amount,
                'type'           => 'topup',
                'status'         => 'pending',
                'payment_method' => $paymentMethod,
                'description'    => 'Top up saldo via ' . $paymentMethod,
            ]);

            $paymentUrl = null;

            // =================================================================
            // 2. LOGIKA PAYMENT GATEWAY
            // (Mirip dengan PesananController namun dirampingkan khusus TopUp)
            // =================================================================

            // A. VIA DANA GATEWAY (Termasuk Direct Debit)
            if (Str::contains($paymentMethod, 'DANA')) {
                Log::info("[API MOBILE] TopUp menggunakan metode DANA Gateway.");

                // Gunakan Helper DANA khusus TopUp
                $danaData = $this->_createTopUpPaymentDANA($transaction, $user);

                if (!isset($danaData['success']) || !$danaData['success']) {
                    throw new Exception($danaData['message'] ?? 'Gagal membuat tagihan DANA.');
                }

                $paymentUrl = $danaData['redirect_url'];
            }

            // B. VIA DOKU JOKUL
            elseif ($paymentMethod === '#DOKU' || $paymentMethod === 'DOKU_JOKUL') {
                Log::info("[API MOBILE] TopUp menggunakan metode DOKU Jokul.");

                $dokuService = new DokuJokulService();
                // DOKU Service butuh Invoice dan Amount
                $paymentUrl = $dokuService->createPayment($transaction->reference_id, $amount);

                if (empty($paymentUrl)) {
                    throw new Exception("Gagal membuat tagihan DOKU Jokul.");
                }
            }

            // C. VIA TRIPAY (Metode VA BCA, QRIS, dll)
            else {
                Log::info("[API MOBILE] TopUp menggunakan metode Tripay.");

                $orderItems = [
                    ['sku' => 'TOPUP', 'name' => 'Top Up Saldo Akun', 'price' => $amount, 'quantity' => 1]
                ];

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
                throw new Exception("Gagal mendapatkan link pembayaran dari Payment Gateway.");
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
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * =========================================================================
     * HELPER: EKSEKUTOR API DANA KHUSUS TOPUP
     * =========================================================================
     */
    private function _createTopUpPaymentDANA(Transaction $transaction, $user)
    {
        $merchantIdConf = "216620080014040009735";
        $partnerIdConf  = "2025081520100641466855";

        $cleanInvoice = preg_replace('/[^a-zA-Z0-9]/', '', $transaction->reference_id);
        $timestamp    = Carbon::now('Asia/Jakarta')->toIso8601String();
        $expiryTime   = Carbon::now('Asia/Jakarta')->addMinutes(30)->format('Y-m-d\TH:i:sP');
        $amountValue  = number_format((float)$transaction->amount, 2, '.', '');

        $bodyArray = [
            "partnerReferenceNo" => $cleanInvoice,
            "merchantId"         => $merchantIdConf,
            "amount"             => [
                "value"    => $amountValue,
                "currency" => "IDR"
            ],
            "validUpTo"          => $expiryTime,
            "urlParams"          => [
                ["url" => route('dana.return', ['trx_id' => $cleanInvoice]), "type" => "PAY_RETURN", "isDeeplink" => "Y"],
                ["url" => url('/dana/notify'), "type" => "NOTIFICATION", "isDeeplink" => "N"]
            ],
            "payOptionDetails"   => [
                [
                    "payMethod"   => "BALANCE",
                    "payOption"   => "BALANCE",
                    "transAmount" => ["value" => $amountValue, "currency" => "IDR"],
                    "feeAmount"   => ["value" => "0.00", "currency" => "IDR"]
                ]
            ],
            "additionalInfo"     => [
                "productCode" => "51051000100000000001",
                "mcc"         => "5732",
                "order"       => [
                    "orderTitle"        => substr("Top Up " . $cleanInvoice, 0, 40),
                    "merchantTransType" => "01",
                    "orderMemo"         => substr("Inv " . $cleanInvoice, 0, 40),
                    "createdTime"       => $timestamp,
                    "buyer"             => [
                        "externalUserId"   => (string) ($user->id_pengguna ?? 'GUEST'.rand(100,999)),
                        "externalUserType" => "MERCHANT_USER",
                        "nickname"         => substr(preg_replace('/[^a-zA-Z0-9 ]/', '', $user->nama_lengkap ?? 'Customer'), 0, 20),
                    ],
                    "goods" => [
                        [
                            "name"            => "Saldo Top Up",
                            "merchantGoodsId" => substr("TOPUP" . $cleanInvoice, 0, 40),
                            "description"     => "Top Up Saldo Akun",
                            "category"        => "DIGITAL_GOODS",
                            "price"           => ["value" => $amountValue, "currency" => "IDR"],
                            "unit"            => "pcs",
                            "quantity"        => "1"
                        ]
                    ]
                ],
                "envInfo" => [
                    "sourcePlatform"    => "IPG",
                    "terminalType"      => "SYSTEM",
                    "orderTerminalType" => "APP", // APP format for Mobile
                ]
            ]
        ];

        $jsonBody = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $relativePath = '/rest/redirection/v1.0/debit/payment-host-to-host';

        try {
            $accessToken = $this->danaSignature->getAccessToken();
            $signature   = $this->danaSignature->generateSignature('POST', $relativePath, $jsonBody, $timestamp);

            $headers = [
                'Authorization'  => 'Bearer ' . $accessToken,
                'X-PARTNER-ID'   => $partnerIdConf,
                'X-EXTERNAL-ID'  => Str::random(32),
                'X-TIMESTAMP'    => $timestamp,
                'X-SIGNATURE'    => $signature,
                'Content-Type'   => 'application/json',
                'CHANNEL-ID'     => '95221',
                'ORIGIN'         => config('services.dana.origin'),
            ];

            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $relativePath);

            $result = $response->json();

            if (isset($result['responseCode']) && $result['responseCode'] == '2005400') {
                $redirectUrl = $result['appLinkUrl'] ?? $result['webRedirectUrl'] ?? null;
                if ($redirectUrl) {
                    return ['success' => true, 'redirect_url' => $redirectUrl];
                }
            }

            return ['success' => false, 'message' => $result['responseMessage'] ?? 'Unknown DANA Error'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
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

        $baseUrl      = '';
        $apiKey       = '';
        $privateKey   = '';
        $merchantCode = '';

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

        if (empty($apiKey) || empty($privateKey) || empty($merchantCode)) {
            return ['success' => false, 'message' => 'Konfigurasi Tripay belum lengkap.'];
        }

        $customerEmail = $user->email;
        if (empty($customerEmail) || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $customerEmail = 'customer+' . Str::random(5) . '@tokosancaka.com';
        }

        $payload = [
            'method'         => str_replace('#', '', $transaction->payment_method),
            'merchant_ref'   => $transaction->reference_id,
            'amount'         => $transaction->amount,
            'customer_name'  => $user->nama_lengkap ?? 'User Sancaka',
            'customer_email' => $customerEmail,
            'customer_phone' => $user->no_wa ?? '081111111111',
            'order_items'    => $orderItems,
            'return_url'     => url('/'), // Arahkan kembali jika sudah bayar
            'expired_time'   => time() + (24 * 60 * 60), // 24 jam
            'signature'      => hash_hmac('sha256', $merchantCode . $transaction->reference_id . $transaction->amount, $privateKey),
        ];

        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                ->timeout(30)->post($baseUrl, $payload);

            if (!$response->successful()) {
                $errorMessage = 'Gagal menghubungi server pembayaran (HTTP ' . $response->status() . ').';
                $responseJson = $response->json();

                if (isset($responseJson['message'])) {
                    $errorMessage = 'Tripay Error: ' . $responseJson['message'];
                }
                return ['success' => false, 'message' => $errorMessage];
            }

            $responseData = $response->json();
            if (!isset($responseData['success']) || $responseData['success'] !== true) {
                return ['success' => false, 'message' => $responseData['message'] ?? 'Gagal membuat tagihan pembayaran.'];
            }

            return $responseData;
        } catch (\Exception $e) {
            Log::error('Error saat membuat transaksi Tripay Mobile: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan internal saat memproses pembayaran.'];
        }
    }

    /**
     * Helper: Dinamisasi Config DANA
     */
    private function applyDynamicConfig()
    {
        $danaMode = Api::getValue('dana_production_mode', 'global', '0');
        $isProduction = ($danaMode == '1');

        if ($isProduction) {
            config([
                'services.dana.dana_env'      => 'PRODUCTION',
                'services.dana.base_url'      => 'https://api.saas.dana.id',
                'services.dana.merchant_id'   => Api::getValue('dana_prod_merchant_id', 'production', env('DANA_PROD_MERCHANT_ID')),
                'services.dana.client_id'     => Api::getValue('dana_prod_client_id', 'production', env('DANA_PROD_CLIENT_ID')),
                'services.dana.x_partner_id'  => Api::getValue('dana_prod_client_id', 'production', env('DANA_PROD_CLIENT_ID')),
                'services.dana.private_key'   => Api::getValue('dana_prod_private_key', 'production', env('DANA_PROD_PRIVATE_KEY')),
                'services.dana.public_key'    => Api::getValue('dana_prod_public_key', 'production'),
                'services.dana.client_secret' => Api::getValue('dana_prod_client_secret', 'production', env('DANA_PROD_CLIENT_SECRET')),
            ]);
        } else {
            config([
                'services.dana.dana_env'      => 'SANDBOX',
                'services.dana.base_url'      => 'https://api.sandbox.dana.id',
                'services.dana.merchant_id'   => Api::getValue('dana_sandbox_merchant_id', 'sandbox', env('DANA_MERCHANT_ID')),
                'services.dana.client_id'     => Api::getValue('dana_sandbox_client_id', 'sandbox', env('DANA_X_PARTNER_ID')),
                'services.dana.x_partner_id'  => Api::getValue('dana_sandbox_client_id', 'sandbox', env('DANA_X_PARTNER_ID')),
                'services.dana.private_key'   => Api::getValue('dana_sandbox_private_key', 'sandbox', env('DANA_PRIVATE_KEY')),
                'services.dana.public_key'    => Api::getValue('dana_sandbox_public_key', 'sandbox'),
                'services.dana.client_secret' => Api::getValue('dana_sandbox_client_secret', 'sandbox', env('DANA_CLIENT_SECRET')),
            ]);
        }
    }
}
