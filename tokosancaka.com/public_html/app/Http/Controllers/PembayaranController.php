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

        Log::info('Mencari data tagihan untuk akun:', ['akun' => $akun]);

        $user = User::where('no_wa', $akun)->orWhere('email', $akun)->first();

        if ($user) {
            Log::info('User ditemukan:', ['user_id' => $user->id]);

            $userId = $user->id_pengguna ?? $user->id;

            $invoices = Order::where('user_id', $userId)
                             ->whereIn('status', ['pending', 'unpaid'])
                             ->whereNotIn(\Illuminate\Support\Facades\DB::raw('UPPER(payment_method)'), ['CASH', 'COD', 'CODBARANG', 'POTONG SALDO'])
                             ->orderBy('created_at', 'desc')
                             ->get();

            Log::info('Total tagihan pending ditemukan:', ['jumlah' => $invoices->count()]);

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
                    Log::info('Fetching Tripay Channels dari API Gateway', ['mode' => $currentMode]);
                    $response = Http::withToken($apiKey)->timeout(10)->get($baseUrl . '/merchant/payment-channel');

                    if ($response->successful()) {
                        Log::info('Berhasil mendapatkan Tripay Channels');
                        return $response->json()['data'] ?? [];
                    }
                    Log::warning('Gagal mendapatkan Tripay Channels, Status:', ['status' => $response->status()]);
                } catch (\Exception $e) {
                    Log::error('Exception saat get Tripay Channels:', ['error' => $e->getMessage()]);
                }
                return [];
            });

            return view('pembayaran.index', compact('user', 'invoices', 'userId', 'tripayChannels'));
        }

        Log::warning('User tidak ditemukan saat pencarian tagihan', ['akun' => $akun]);
        return view('pembayaran.index');
    }

    /**
     * Memproses API Gateway berdasarkan pilihan user di Modal Web
     */
    public function proses(Request $request, $invoice_number)
    {
        Log::info('Memulai proses Generate URL Pembayaran', [
            'invoice' => $invoice_number,
            'requested_method' => $request->payment_method
        ]);

        $request->validate([
            'payment_method' => 'required|string'
        ]);

        $order = Order::with('user', 'items.product')->where('invoice_number', $invoice_number)->first();

        if (!$order || !in_array(strtolower($order->status), ['pending', 'unpaid'])) {
            Log::warning('Tagihan tidak valid / sudah lunas / dibatalkan', ['invoice' => $invoice_number]);
            return back()->with('error', 'Tagihan tidak valid atau sudah dibayar/dibatalkan.');
        }

        // 1. UPDATE METODE DARI 'GATEWAY' KE METODE ASLI
        $method = strtoupper($request->payment_method);

        // Jika user klik metode yang sama dan URL sudah ada, langsung lempar ke URL tersebut!
        // Validasi Ekstra: Pastikan URL bukan kembali ke web internal (mencegah redirect loop)
        if ($order->payment_method === $method && !empty($order->payment_url) && !str_contains($order->payment_url, 'tokosancaka.com/pembayaran')) {
            Log::info('Menggunakan ulang Payment URL yang sudah ada', [
                'invoice' => $invoice_number,
                'method' => $method,
                'url' => $order->payment_url
            ]);
            return redirect()->away($order->payment_url);
        }

        $order->payment_method = $method;
        $order->save();

        $grand_total = $order->total_amount;
        $user = $order->user;

        // 2. SIAPKAN PAYLOAD ITEM UNTUK API
        Log::info('Menyiapkan payload order items', ['invoice' => $invoice_number]);
        $orderItemsPayload = [];
        $calculatedTotalItems = 0;

        // Masukkan Produk
        foreach ($order->items as $item) {
            $price = (int) $item->price;
            $qty = (int) $item->quantity;
            $orderItemsPayload[] = [
                'sku'      => (string) ($item->product_id ?? 'ITEM'),
                'name'     => substr($item->product->name ?? 'Produk Sancaka', 0, 50),
                'price'    => $price,
                'quantity' => $qty
            ];
            $calculatedTotalItems += ($price * $qty);
        }

        // TAMBAHKAN ONGKOS KIRIM
        if ($order->shipping_cost > 0) {
            $orderItemsPayload[] = [
                'sku'      => 'SHIPPING_FEE',
                'name'     => 'Ongkos Kirim',
                'price'    => (int) $order->shipping_cost,
                'quantity' => 1
            ];
            $calculatedTotalItems += (int) $order->shipping_cost;
        }

        // TAMBAHKAN ASURANSI
        if ($order->insurance_cost > 0) {
            $orderItemsPayload[] = [
                'sku'      => 'INSURANCE_FEE',
                'name'     => 'Biaya Asuransi',
                'price'    => (int) $order->insurance_cost,
                'quantity' => 1
            ];
            $calculatedTotalItems += (int) $order->insurance_cost;
        }

        // TAMBAHKAN BIAYA COD
        if ($order->cod_fee > 0) {
            $orderItemsPayload[] = [
                'sku'      => 'COD_FEE',
                'name'     => 'Biaya Layanan COD',
                'price'    => (int) $order->cod_fee,
                'quantity' => 1
            ];
            $calculatedTotalItems += (int) $order->cod_fee;
        }

        Log::info('Payload items selesai dibuat', [
            'invoice' => $invoice_number,
            'calculated_total' => $calculatedTotalItems,
            'grand_total_db' => $grand_total
        ]);

        // =======================================================
        // 3. ROUTING KE API GATEWAY MASING-MASING
        // =======================================================

        // ---> A. DANA API
        if ($method === 'DANA') {
            Log::info('Routing ke Gateway DANA', ['invoice' => $invoice_number]);
            return $this->createPaymentDANA($order);
        }

        // ---> B. DOKU JOKUL API
        elseif ($method === 'DOKU_JOKUL') {
            Log::info('Routing ke Gateway DOKU JOKUL', ['invoice' => $invoice_number]);
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
                    []
                );

                if ($paymentUrl) {
                    Log::info('Berhasil generate link DOKU', ['invoice' => $invoice_number, 'url' => $paymentUrl]);
                    $order->payment_url = $paymentUrl;
                    $order->save();
                    return redirect()->away($paymentUrl);
                }
                throw new \Exception('Gagal generate link DOKU, response return null.');
            } catch (\Exception $e) {
                Log::error('DOKU_FAIL Web Portal: ' . $e->getMessage(), ['invoice' => $invoice_number]);
                return back()->with('error', 'Gagal memproses pembayaran DOKU.');
            }
        }

        // ---> C. TRIPAY API
        else {
            Log::info('Routing ke Gateway TRIPAY', ['invoice' => $invoice_number, 'channel' => $method]);
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

                Log::info('Berhasil generate link TRIPAY', ['invoice' => $invoice_number, 'url' => $paymentUrl]);

                $order->payment_url = $paymentUrl;
                $order->pay_code = $tripayResult['data']['pay_code'] ?? null;
                $order->qr_url = $tripayResult['data']['qr_url'] ?? null;
                $order->save();

                return redirect()->away($paymentUrl);
            } else {
                Log::warning('Gagal generate transaksi TRIPAY', ['invoice' => $invoice_number, 'message' => $tripayResult['message']]);
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

        Log::info('Inisiasi Tripay Request', ['mode' => $mode, 'invoice' => $order->invoice_number]);

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
            Log::error('Konfigurasi Tripay tidak lengkap', ['invoice' => $order->invoice_number]);
            return ['success' => false, 'message' => 'Konfigurasi Tripay belum lengkap.'];
        }

        $amount = (int) $amount;

        // Cegah Error "Total amount mismatch"
        if ($calculatedTotalItems !== $amount) {
            Log::warning('Total amount mismatch di Tripay, menggunakan fallback 1 Item Invoice', [
                'calculated' => $calculatedTotalItems,
                'amount_db' => $amount
            ]);
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
            Log::info('Mengirim Payload ke API Tripay', ['url' => $baseUrl, 'merchant_ref' => $order->invoice_number]);
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                            ->timeout(30)->withoutVerifying()->post($baseUrl, $payload);

            $body = $response->json();

            if ($response->successful() && ($body['success'] ?? false) === true) {
                Log::info('Response API Tripay SUKSES', ['reference' => $body['data']['reference'] ?? '-']);
                return ['success' => true, 'data' => $body['data']];
            }

            Log::error('Tripay Web Error Response:', ['response' => $body]);
            return ['success' => false, 'message' => $body['message'] ?? 'Gagal transaksi Tripay.'];
        } catch (\Exception $e) {
            Log::error("Tripay Exception: " . $e->getMessage(), ['invoice' => $order->invoice_number]);
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
        Log::info('Inisiasi DANA Request', ['invoice' => $order->invoice_number]);

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
            Log::info('Generate Signature & Access Token DANA', ['invoice' => $order->invoice_number]);
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

            Log::info('Mengirim API Request ke DANA Host-to-Host', ['url' => config('services.dana.base_url') . $relativePath]);
            $response = Http::withHeaders($headers)->withBody($jsonBody, 'application/json')
                            ->post(config('services.dana.base_url') . $relativePath);

            $result = $response->json();

            if (isset($result['responseCode']) && $result['responseCode'] == '2005400') {
                $redirectUrl = $result['webRedirectUrl'] ?? null;
                if($redirectUrl) {
                    Log::info('Berhasil generate WebRedirectUrl DANA', ['invoice' => $order->invoice_number, 'url' => $redirectUrl]);
                    $order->payment_url = $redirectUrl;
                    $order->save();
                    return redirect()->away($redirectUrl);
                }
            }

            Log::error('DANA_FAIL Web Portal', ['Result' => $result, 'invoice' => $order->invoice_number]);
            return back()->with('error', 'Gagal memproses pembayaran DANA: ' . ($result['responseMessage'] ?? 'Unknown Error'));

        } catch (\Exception $e) {
            Log::error('DANA_EXCEPTION Web Portal', ['Error' => $e->getMessage(), 'invoice' => $order->invoice_number]);
            return back()->with('error', 'Terjadi kesalahan koneksi ke DANA.');
        }
    }
}
