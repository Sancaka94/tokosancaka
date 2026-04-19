<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Services\DokuJokulService;
use App\Services\DanaSignatureService;

class PembayaranController extends Controller
{
    protected $danaSignature;

    // Inject Service DANA
    public function __construct(DanaSignatureService $danaSignature)
    {
        $this->danaSignature = $danaSignature;
    }

    public function index(Request $request)
    {
        $akun = $request->input('akun');

        if (!$akun) {
            return view('pembayaran.index');
        }

        $user = User::where('no_wa', $akun)->orWhere('email', $akun)->first();

        if ($user) {
            $userId = $user->id_pengguna ?? $user->id;

            $invoices = Order::where('user_id', $userId)
                             ->whereIn('status', ['pending', 'unpaid'])
                             ->whereRaw('UPPER(payment_method) = ?', ['GATEWAY'])
                             ->orderBy('created_at', 'desc')
                             ->get();

            // AMBIL LIST METODE BAYAR TRIPAY (Persis seperti CheckoutController)
            $currentMode = \App\Models\Api::getValue('TRIPAY_MODE', 'global', 'sandbox');
            $cacheKey = 'tripay_channels_list_' . $currentMode;

            $tripayChannels = Cache::remember($cacheKey, 60 * 24, function () use ($currentMode) {
                if ($currentMode === 'production') {
                    $baseUrl = 'https://tripay.co.id/api';
                    $apiKey  = \App\Models\Api::getValue('TRIPAY_API_KEY', 'production');
                } else {
                    $baseUrl = 'https://tripay.co.id/api-sandbox';
                    $apiKey  = \App\Models\Api::getValue('TRIPAY_API_KEY', 'sandbox');
                }

                try {
                    $response = Http::withToken($apiKey)->timeout(10)->get($baseUrl . '/merchant/payment-channel');
                    if ($response->successful()) {
                        return $response->json()['data'] ?? [];
                    }
                } catch (\Exception $e) {
                    // Silent Error
                }
                return [];
            });

            return view('pembayaran.index', compact('user', 'invoices', 'userId', 'tripayChannels'));
        }

        return view('pembayaran.index');
    }

    /**
     * Memproses API Gateway berdasarkan pilihan user di Modal Web
     */
    public function proses(Request $request, $invoice_number)
    {
        $request->validate([
            'payment_method' => 'required|string'
        ]);

        $order = Order::with('user', 'items.product')->where('invoice_number', $invoice_number)->first();

        if (!$order || !in_array(strtolower($order->status), ['pending', 'unpaid'])) {
            return back()->with('error', 'Tagihan tidak valid atau sudah dibayar/dibatalkan.');
        }

        // 1. UPDATE METODE DARI 'GATEWAY' KE METODE ASLI (misal: 'DANA' atau 'BRIVA')
        $method = strtoupper($request->payment_method);
        $order->payment_method = $method;
        $order->save();

        $grand_total = $order->total_amount;
        $user = $order->user;

        // 2. SIAPKAN PAYLOAD ITEM UNTUK API
        $orderItemsPayload = [];
        $calculatedTotalItems = 0;
        foreach ($order->items as $item) {
            $price = (int) $item->price;
            $qty = (int) $item->quantity;
            $orderItemsPayload[] = [
                'sku'      => $item->product_id ?? 'ITEM',
                'name'     => $item->product->name ?? 'Produk Sancaka',
                'price'    => $price,
                'quantity' => $qty
            ];
            $calculatedTotalItems += ($price * $qty);
        }

        // =======================================================
        // 3. ROUTING KE API GATEWAY MASING-MASING
        // =======================================================

        // ---> A. DANA API
        if ($method === 'DANA') {
            return $this->createPaymentDANA($order);
        }

        // ---> B. DOKU JOKUL API
        elseif ($method === 'DOKU_JOKUL') {
            try {
                $dokuService = new DokuJokulService();
                $customerData = [
                    'name'  => $user->nama_lengkap ?? $user->name ?? 'Pelanggan',
                    'email' => $user->email ?? 'no-email@sancaka.com',
                    'phone' => $user->no_wa ?? $user->phone ?? '0000000'
                ];

                $paymentUrl = $dokuService->createPayment(
                    $order->invoice_number,
                    $grand_total,
                    $customerData,
                    $orderItemsPayload,
                    [] // Additional Info (Routing di-hold di Admin)
                );

                if ($paymentUrl) {
                    $order->payment_url = $paymentUrl;
                    $order->save();
                    return redirect()->away($paymentUrl);
                }
                throw new \Exception('Gagal generate link DOKU.');
            } catch (\Exception $e) {
                Log::error('DOKU_FAIL Web Portal: ' . $e->getMessage());
                return back()->with('error', 'Gagal memproses pembayaran DOKU.');
            }
        }

        // ---> C. TRIPAY API
        else {
            $tripayResult = $this->_createTripayTransaction(
                $order,
                $method,
                $grand_total,
                $user->nama_lengkap ?? 'Pelanggan',
                $user->email ?? 'no-email@sancaka.com',
                $user->no_wa ?? '-',
                $orderItemsPayload,
                $calculatedTotalItems
            );

            if ($tripayResult['success']) {
                $paymentUrl = $tripayResult['data']['checkout_url'] ?? $tripayResult['data']['pay_url'] ?? null;
                $order->payment_url = $paymentUrl;
                $order->pay_code = $tripayResult['data']['pay_code'] ?? null;
                $order->qr_url = $tripayResult['data']['qr_url'] ?? null;
                $order->save();

                return redirect()->away($paymentUrl);
            } else {
                return back()->with('error', $tripayResult['message']);
            }
        }
    }


    /**
     * =========================================================================
     * HELPER: CREATE TRIPAY TRANSACTION
     * =========================================================================
     */
    private function _createTripayTransaction($order, $methodChannel, $amount, $custName, $custEmail, $custPhone, $items, $calculatedTotalItems)
    {
        $mode = \App\Models\Api::getValue('TRIPAY_MODE', 'global', 'sandbox');

        if ($mode === 'production') {
            $baseUrl      = 'https://tripay.co.id/api/transaction/create';
            $apiKey       = \App\Models\Api::getValue('TRIPAY_API_KEY', 'production');
            $privateKey   = \App\Models\Api::getValue('TRIPAY_PRIVATE_KEY', 'production');
            $merchantCode = \App\Models\Api::getValue('TRIPAY_MERCHANT_CODE', 'production');
        } else {
            $baseUrl      = 'https://tripay.co.id/api-sandbox/transaction/create';
            $apiKey       = \App\Models\Api::getValue('TRIPAY_API_KEY', 'sandbox');
            $privateKey   = \App\Models\Api::getValue('TRIPAY_PRIVATE_KEY', 'sandbox');
            $merchantCode = \App\Models\Api::getValue('TRIPAY_MERCHANT_CODE', 'sandbox');
        }

        if (empty($apiKey) || empty($privateKey) || empty($merchantCode)) {
            return ['success' => false, 'message' => 'Konfigurasi Tripay belum lengkap.'];
        }

        $amount = (int) $amount;

        // Cegah Error "Total amount mismatch"
        if ($calculatedTotalItems !== $amount) {
            $items = [[
                'sku'      => 'INV-' . $order->invoice_number,
                'name'     => 'Pembayaran Invoice #' . $order->invoice_number,
                'price'    => $amount,
                'quantity' => 1
            ]];
        }

        $signature = hash_hmac('sha256', $merchantCode . $order->invoice_number . $amount, $privateKey);

        $payload = [
            'method'         => $methodChannel,
            'merchant_ref'   => $order->invoice_number,
            'amount'         => $amount,
            'customer_name'  => $custName,
            'customer_email' => $custEmail,
            'customer_phone' => $custPhone,
            'order_items'    => $items,
            'return_url'     => url('/mobile-payment-success'),
            'expired_time'   => (time() + (24 * 60 * 60)),
            'signature'      => $signature
        ];

        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                            ->timeout(30)->withoutVerifying()->post($baseUrl, $payload);

            $body = $response->json();

            if ($response->successful() && ($body['success'] ?? false) === true) {
                return ['success' => true, 'data' => $body['data']];
            }
            Log::error('Tripay Web Error:', ['response' => $body]);
            return ['success' => false, 'message' => $body['message'] ?? 'Gagal transaksi Tripay.'];
        } catch (\Exception $e) {
            Log::error("Tripay Exception: " . $e->getMessage());
            return ['success' => false, 'message' => 'Koneksi ke gateway bermasalah.'];
        }
    }


    /**
     * =========================================================================
     * HELPER: CREATE DANA TRANSACTION
     * =========================================================================
     */
    public function createPaymentDANA(Order $order)
    {
        $validId = "216620080014040009735";
        $merchantIdConf = $validId;
        $partnerIdConf  = "2025081520100641466855";

        $cleanInvoice = preg_replace('/[^a-zA-Z0-9]/', '', $order->invoice_number);
        $timestamp    = Carbon::now('Asia/Jakarta')->toIso8601String();
        $expiryTime   = Carbon::now('Asia/Jakarta')->addMinutes(60)->format('Y-m-d\TH:i:sP');
        $amountValue  = number_format((float)$order->total_amount, 2, '.', '');

        $bodyArray = [
            "partnerReferenceNo" => $cleanInvoice,
            "merchantId"         => $merchantIdConf,
            "amount"             => ["value" => $amountValue, "currency" => "IDR"],
            "validUpTo"          => $expiryTime,
            "urlParams"          => [
                ["url" => route('dana.return'), "type" => "PAY_RETURN", "isDeeplink" => "Y"],
                ["url" => route('dana.notify'), "type" => "NOTIFICATION", "isDeeplink" => "Y"]
            ],
            "payOptionDetails"   => [[
                "payMethod"   => "BALANCE",
                "payOption"   => "BALANCE",
                "transAmount" => ["value" => $amountValue, "currency" => "IDR"],
                "feeAmount"   => ["value" => "0.00", "currency" => "IDR"]
            ]],
            "additionalInfo"     => [
                "productCode" => "51051000100000000001",
                "mcc"         => "5732",
                "order"       => [
                    "orderTitle"        => substr("Pay " . $cleanInvoice, 0, 40),
                    "merchantTransType" => "01",
                    "orderMemo"         => substr("Inv " . $cleanInvoice, 0, 40),
                    "createdTime"       => $timestamp,
                    "buyer"             => [
                        "externalUserId"   => (string) ($order->user_id ?? 'GUEST'.rand(100,999)),
                        "externalUserType" => "MERCHANT_USER",
                        "nickname"         => substr(preg_replace('/[^a-zA-Z0-9 ]/', '', $order->user->nama_lengkap ?? 'Guest'), 0, 20),
                    ],
                    "goods" => [[
                        "merchantGoodsId" => substr("ITEM" . $cleanInvoice, 0, 40),
                        "description"     => "Pembayaran Order",
                        "category"        => "DIGITAL_GOODS",
                        "price"           => ["value" => $amountValue, "currency" => "IDR"],
                        "unit"            => "pcs",
                        "quantity"        => "1"
                    ]]
                ],
                "envInfo" => [
                    "sourcePlatform" => "IPG",
                    "terminalType" => "SYSTEM",
                    "orderTerminalType" => "WEB",
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

            $response = Http::withHeaders($headers)->withBody($jsonBody, 'application/json')
                            ->post(config('services.dana.base_url') . $relativePath);

            $result = $response->json();

            if (isset($result['responseCode']) && $result['responseCode'] == '2005400') {
                $redirectUrl = $result['webRedirectUrl'] ?? null;
                if($redirectUrl) {
                    $order->payment_url = $redirectUrl;
                    $order->save();
                    return redirect()->away($redirectUrl);
                }
            }

            Log::error('DANA_FAIL Web Portal', ['Result' => $result]);
            return back()->with('error', 'Gagal memproses pembayaran DANA: ' . ($result['responseMessage'] ?? 'Unknown Error'));

        } catch (\Exception $e) {
            Log::error('DANA_EXCEPTION Web Portal', ['Error' => $e->getMessage()]);
            return back()->with('error', 'Terjadi kesalahan koneksi ke DANA.');
        }
    }
}
