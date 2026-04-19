<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\Pesanan;
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
            Log::info('User ditemukan:', ['user_id' => $user->id_pengguna ?? 'Tidak ada']);

            $userId = $user->id_pengguna; // Pastikan menggunakan id_pengguna
            $excludedMethods = ['CASH', 'COD', 'CODBARANG', 'POTONG SALDO'];

            // 1. CARI TAGIHAN BELANJA TOKO (ORDERS)
            $invoices = Order::where('user_id', $userId)
                             ->whereIn('status', ['pending', 'unpaid'])
                             ->whereNotIn(\Illuminate\Support\Facades\DB::raw('UPPER(payment_method)'), $excludedMethods)
                             ->orderBy('created_at', 'desc')
                             ->get();

            // 2. CARI TAGIHAN TOP UP (TRANSACTIONS)
            $topups = Transaction::where('user_id', $userId)
                             ->where('type', 'topup')
                             ->where('status', 'pending')
                             ->orderBy('created_at', 'desc')
                             ->get();

            // 3. CARI TAGIHAN EKSPEDISI SANCAKA EXPRESS (PESANAN)
            // Menggunakan kolom 'status_pesanan' dan mengecualikan metode 'POTONG SALDO' dll.
            $ekspedisi = Pesanan::where('customer_id', $userId)
                             ->whereIn('status_pesanan', ['pending', 'unpaid', 'Belum Bayar', 'BELUM BAYAR'])
                             ->whereNotIn(\Illuminate\Support\Facades\DB::raw('UPPER(payment_method)'), $excludedMethods)
                             ->orderBy('created_at', 'desc')
                             ->get();

            Log::info('Total tagihan pending ditemukan:', [
                'orders' => $invoices->count(),
                'topups' => $topups->count(),
                'ekspedisi' => $ekspedisi->count()
            ]);

            // AMBIL LIST METODE BAYAR TRIPAY
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
                    Log::error('Exception saat get Tripay Channels:', ['error' => $e->getMessage()]);
                }
                return [];
            });

            return view('pembayaran.index', compact('user', 'invoices', 'topups', 'ekspedisi', 'userId', 'tripayChannels'));
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

        $method = strtoupper($request->payment_method);

        // ====================================================================
        // DETEKSI TIPE TRANSAKSI 100% AKURAT DARI DATABASE (TANPA PREFIX)
        // ====================================================================
        $isTopup = false;
        $isEkspedisi = false;
        $isOrder = false;
        $model = null;

        // 1. Cek di tabel Transactions (Top Up)
        $trx = Transaction::where('reference_id', $invoice_number)->first();
        if ($trx) {
            $isTopup = true;
            $model = $trx;
        }
        else {
            // 2. Cek di tabel Pesanan (Ekspedisi Sancaka Express)
            $pesanan = Pesanan::where('nomor_invoice', $invoice_number)->first();
            if ($pesanan) {
                $isEkspedisi = true;
                $model = $pesanan;
            }
            else {
                // 3. Cek di tabel Orders (Belanja Toko)
                $order = Order::with('items.product')->where('invoice_number', $invoice_number)->first();
                if ($order) {
                    $isOrder = true;
                    $model = $order;
                }
            }
        }

        // Jika invoice tidak dikenali di sistem manapun
        if (!$model) {
            Log::warning('Tagihan fiktif / tidak ditemukan', ['invoice' => $invoice_number]);
            return back()->with('error', 'Tagihan tidak ditemukan di sistem.');
        }

        // ====================================================================
        // A. PROSES JIKA INI TRANSAKSI TOP UP
        // ====================================================================
        if ($isTopup) {
            if (strtolower($model->status) !== 'pending') {
                return back()->with('error', 'Tagihan Top Up ini tidak valid atau sudah dibayar.');
            }

            $user = User::where('id_pengguna', $model->user_id)->first();
            $grand_total = $model->amount;

            $orderItemsPayload = [
                ['sku' => 'TOPUP', 'name' => 'Top Up Saldo Sancaka', 'price' => (int) $grand_total, 'quantity' => 1]
            ];
            $calculatedTotalItems = (int) $grand_total;
        }

        // ====================================================================
        // B. PROSES JIKA INI TAGIHAN EKSPEDISI (PESANAN)
        // ====================================================================
        elseif ($isEkspedisi) {
            if (!in_array(strtolower($model->status), ['pending', 'unpaid', 'belum bayar'])) {
                return back()->with('error', 'Tagihan Pengiriman ini tidak valid atau sudah dibayar.');
            }

            $user = User::where('id_pengguna', $model->customer_id)->first();
            if (!$user) {
                $user = (object) [
                    'nama_lengkap' => $model->sender_name ?? 'Pelanggan Sancaka',
                    'no_wa' => $model->sender_phone ?? '0800000000',
                    'email' => 'no-email@sancaka.com'
                ];
            }

            $grand_total = $model->price ?? ($model->shipping_cost + $model->insurance_cost + $model->cod_fee);

            $orderItemsPayload = [
                ['sku' => 'SHIPPING', 'name' => 'Ongkos Kirim Sancaka Express', 'price' => (int) $grand_total, 'quantity' => 1]
            ];
            $calculatedTotalItems = (int) $grand_total;
        }

        // ====================================================================
        // C. PROSES JIKA INI PESANAN BELANJA (ORDER MARKETPLACE)
        // ====================================================================
        else {
            if (!in_array(strtolower($model->status), ['pending', 'unpaid'])) {
                return back()->with('error', 'Tagihan belanja ini tidak valid atau sudah dibayar.');
            }

            $user = User::where('id_pengguna', $model->user_id)->first();
            $grand_total = $model->total_amount;
            $orderItemsPayload = [];
            $calculatedTotalItems = 0;

            foreach ($model->items as $item) {
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

            if ($model->shipping_cost > 0) {
                $orderItemsPayload[] = ['sku' => 'SHIPPING_FEE', 'name' => 'Ongkos Kirim', 'price' => (int) $model->shipping_cost, 'quantity' => 1];
                $calculatedTotalItems += (int) $model->shipping_cost;
            }
            if ($model->insurance_cost > 0) {
                $orderItemsPayload[] = ['sku' => 'INSURANCE_FEE', 'name' => 'Biaya Asuransi', 'price' => (int) $model->insurance_cost, 'quantity' => 1];
                $calculatedTotalItems += (int) $model->insurance_cost;
            }
            if ($model->cod_fee > 0) {
                $orderItemsPayload[] = ['sku' => 'COD_FEE', 'name' => 'Biaya Layanan COD', 'price' => (int) $model->cod_fee, 'quantity' => 1];
                $calculatedTotalItems += (int) $model->cod_fee;
            }
        }

        // =======================================================
        // CEK RE-USE PAYMENT URL (Mencegah Limit Gateway)
        // =======================================================
        $currentMethod = $isTopup
            ? str_replace('Top up saldo via ', '', $model->description ?? '')
            : $model->payment_method;

        if ($currentMethod === $method && !empty($model->payment_url) && !str_contains($model->payment_url, 'tokosancaka.com/pembayaran')) {
            Log::info('Menggunakan ulang Payment URL', ['invoice' => $invoice_number, 'url' => $model->payment_url]);
            return redirect()->away($model->payment_url);
        }

        // =======================================================
        // UPDATE METODE PEMBAYARAN KE DATABASE
        // =======================================================
        if ($isTopup) {
            $model->description = 'Top up saldo via ' . $method;
        } else {
            $model->payment_method = $method;

            // Opsional: Jika tabel Pesanan butuh update status_pesanan saat ganti metode
            if ($isEkspedisi) { $model->status_pesanan = 'Menunggu Pembayaran'; }
        }
        $model->save();


        // =======================================================
        // 3. ROUTING KE API GATEWAY MASING-MASING
        // =======================================================

        // ---> A. DANA API
        if ($method === 'DANA') {
            Log::info('Routing ke Gateway DANA', ['invoice' => $invoice_number]);
            return $this->createPaymentDANA($invoice_number, $grand_total, $user, $model);
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
                    $invoice_number,
                    $grand_total,
                    $customerData,
                    $orderItemsPayload,
                    []
                );

                if ($paymentUrl) {
                    $model->payment_url = $paymentUrl;
                    $model->save();
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
            Log::info('Routing ke Gateway TRIPAY', ['invoice' => $invoice_number, 'channel' => $method]);
            $tripayResult = $this->_createTripayTransaction(
                $invoice_number,
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
                $model->payment_url = $paymentUrl;

                // Kolom pay_code & qr_url hanya di tabel Orders
                if ($isOrder) {
                    $model->pay_code = $tripayResult['data']['pay_code'] ?? null;
                    $model->qr_url = $tripayResult['data']['qr_url'] ?? null;
                }
                $model->save();

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
    private function _createTripayTransaction($invoice_number, $methodChannel, $amount, $custName, $custEmail, $custPhone, $items, $calculatedTotalItems)
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

        if ($calculatedTotalItems !== $amount) {
            $items = [[
                'sku'      => 'INV-' . $invoice_number,
                'name'     => 'Pembayaran Invoice #' . $invoice_number,
                'price'    => $amount,
                'quantity' => 1
            ]];
        }

        $signature = hash_hmac('sha256', $merchantCode . $invoice_number . $amount, $privateKey);

        $payload = [
            'method'         => $methodChannel,
            'merchant_ref'   => $invoice_number,
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
            Log::error('Tripay Web Error Response:', ['response' => $body]);
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
    public function createPaymentDANA($invoice_number, $grand_total, $user, $model)
    {
        $validId = "216620080014040009735";
        $merchantIdConf = $validId;
        $partnerIdConf  = "2025081520100641466855";

        $cleanInvoice = preg_replace('/[^a-zA-Z0-9]/', '', $invoice_number);
        $timestamp    = Carbon::now('Asia/Jakarta')->toIso8601String();
        $expiryTime   = Carbon::now('Asia/Jakarta')->addMinutes(60)->format('Y-m-d\TH:i:sP');
        $amountValue  = number_format((float)$grand_total, 2, '.', '');

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
                        "externalUserId"   => (string) ($user->id_pengguna ?? 'GUEST'.rand(100,999)),
                        "externalUserType" => "MERCHANT_USER",
                        "nickname"         => substr(preg_replace('/[^a-zA-Z0-9 ]/', '', $user->nama_lengkap ?? 'Guest'), 0, 20),
                    ],
                    "goods" => [[
                        "merchantGoodsId" => substr("ITEM" . $cleanInvoice, 0, 40),
                        "description"     => "Pembayaran Sancaka",
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
                    $model->payment_url = $redirectUrl;
                    $model->save();
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
