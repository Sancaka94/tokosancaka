<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str; // 🔥 Pastikan Str di-import
use Exception;

// --- MODEL ---
use App\Models\User;
use App\Models\PpobTransaction;

// --- SERVICES ---
// Pastikan kedua service ini ada
use App\Services\DokuJokulService;
use App\Services\DigiflazzService;

class PpobCheckoutController extends Controller
{
    /**
     * 1. PREPARE: Tambah Item ke Keranjang (Session)
     */
    public function prepare(Request $request)
    {
        try {
            $data = $request->validate([
                'sku'         => 'required',
                'name'        => 'required',
                'price'       => 'required|numeric',
                'customer_no' => 'required',
                'ref_id'      => 'nullable', // Ref ID Inquiry (untuk Pasca)
                'desc'        => 'nullable'
            ]);

            $tempId = uniqid('item_');

            $newItem = [
                'id'          => $tempId,
                'sku'         => $data['sku'],
                'name'        => $data['name'],
                'price'       => (int) $data['price'],
                'customer_no' => $data['customer_no'],
                // Jika Pasca, ref_id dari Inquiry (INQ-...) wajib dibawa
                'ref_id'      => $data['ref_id'] ?? 'PRE-' . time() . rand(100,999),
                'desc'        => $data['desc'] ?? [],
                'quantity'    => 1,
                'is_ppob'     => true
            ];

            $cart = session()->get('ppob_cart', []);
            $cart[] = $newItem;
            session()->put('ppob_cart', $cart);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * 2. INDEX: Tampilkan Halaman Checkout
     */
    public function index()
    {
        $cart = session()->get('ppob_cart', []);

        if (empty($cart)) {
            return redirect()->route('customer.dashboard')->with('error', 'Tidak ada transaksi yang diproses.');
        }

        $totalPrice = array_sum(array_column($cart, 'price'));
        $user = Auth::user();
        $paymentChannels = [];

        // --- A. SALDO AKUN ---
        $paymentChannels['saldo'] = [
            'code'        => 'SALDO',
            'name'        => 'Saldo Akun',
            'description' => 'Sisa: Rp ' . number_format($user->saldo ?? 0),
            'balance'     => $user->saldo ?? 0,
            'active'      => true,
            'icon_url'    => 'https://cdn-icons-png.flaticon.com/512/217/217853.png'
        ];

        // --- B. TRIPAY CHANNELS (API) ---
        try {
            $apiKey  = config('tripay.api_key');
            $mode    = config('tripay.mode');
            $baseUrl = ($mode === 'production')
                ? 'https://tripay.co.id/api/merchant/payment-channel'
                : 'https://tripay.co.id/api-sandbox/merchant/payment-channel';

            $res = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                        ->timeout(3)
                        ->withoutVerifying()
                        ->get($baseUrl);

            if ($res->successful() && isset($res->json()['success']) && $res->json()['success'] === true) {
                foreach($res->json()['data'] as $ch) {
                    if($ch['active'] && in_array($ch['group'], ['QRIS', 'Virtual Account', 'E-Wallet'])) {
                        $paymentChannels['tripay'][] = $ch;
                    }
                }
            }
        } catch (Exception $e) {
            Log::error('Tripay Error: ' . $e->getMessage());
        }

        // --- C. DOKU (MANUAL) ---
        $paymentChannels['doku'] = [
            ['code' => 'DOKU_CC', 'name' => 'Kartu Kredit', 'icon_url' => 'https://cdn-icons-png.flaticon.com/512/6963/6963703.png'],
            ['code' => 'DOKU_VA', 'name' => 'DOMPET SANCAKA', 'icon_url' => 'https://cdn-icons-png.flaticon.com/512/2331/2331922.png'],
        ];

        // 👇 TAMBAHKAN KODE INI 👇
        // --- D. PAYMENT GATEWAY LAINNYA ---
        $paymentChannels['lainnya'] = [
            ['code' => 'MIDTRANS', 'name' => 'Midtrans (GoPay, ShopeePay, dll)', 'icon_url' => 'https://cdn-icons-png.flaticon.com/512/825/825503.png'],
            ['code' => 'DANA', 'name' => 'DANA Direct', 'icon_url' => 'https://cdn-icons-png.flaticon.com/512/825/825454.png'],
            ['code' => 'PAYPAL', 'name' => 'PayPal', 'icon_url' => 'https://cdn-icons-png.flaticon.com/512/174/174861.png'],
        ];

        // 🔥 GENERATE IDEMPOTENCY KEY UNIK
        $idempotencyKey = (string) Str::uuid();

        // 🔥 PASSING KE VIEW
        return view('customer.ppob.checkout', compact('cart', 'totalPrice', 'user', 'paymentChannels', 'idempotencyKey'));
    }

    /**
     * 3. REMOVE ITEM
     */
    public function removeItem($id)
    {
        $cart = session()->get('ppob_cart', []);
        $newCart = array_filter($cart, function($item) use ($id) {
            return $item['id'] !== $id;
        });
        session()->put('ppob_cart', array_values($newCart));
        return redirect()->route('ppob.checkout.index')->with('success', 'Item berhasil dihapus.');
    }

    /**
     * 4. CLEAR CART
     */
    public function clearCart()
    {
        session()->forget('ppob_cart');
        return redirect()->route('customer.dashboard')->with('success', 'Transaksi dibatalkan.');
    }

    /**
     * 5. STORE: Proses Pembayaran (INTEGRASI DIGIFLAZZ DI SINI)
     */
    public function store(Request $request)
    {

        // 🔥 0. CEK IDEMPOTENCY KEY
        // Mencegah user melakukan refresh halaman setelah submit, atau klik ganda
        $key = $request->input('idempotency_key');
        if ($key && PpobTransaction::where('idempotency_key', $key)->exists()) {
            return redirect()->route('customer.ppob.history')
                ->with('warning', 'Transaksi PPOB ini sudah diproses sebelumnya (Mencegah Double Pembayaran).');
        }

        $request->validate(['payment_method' => 'required']);

        $cart = session()->get('ppob_cart', []);
        if (empty($cart)) return redirect()->route('customer.dashboard');

        $user = Auth::user();
        $totalPrice = array_sum(array_column($cart, 'price'));

        DB::beginTransaction();
        try {
            // Validasi Saldo
            if ($request->payment_method === 'saldo' && $user->saldo < $totalPrice) {
                throw new Exception('Saldo tidak mencukupi untuk total transaksi Rp ' . number_format($totalPrice));
            }

            $groupRefId = 'INV-' . time() . rand(100,999);
            $createdTransactions = []; // Array untuk menampung data transaksi yang dibuat

            // --- LOOPING INSERT DATABASE ---
            foreach ($cart as $item) {
                // LOGIKA PENTING: Penentuan Order ID / Ref ID
                // Jika Pascabayar (ref_id diawali INQ), kita HARUS pakai ID itu lagi sebagai Order ID
                // agar Digiflazz bisa memproses 'pay-pasca' dengan ID yang sama.
                $isPasca = isset($item['ref_id']) && str_starts_with($item['ref_id'], 'INQ');

                if ($isPasca) {
                    $orderId = $item['ref_id']; // Pakai ID Inquiry yang lama
                } else {
                    $orderId = 'TRX-' . time() . rand(1000, 9999) . rand(10,99); // Buat baru untuk Prabayar
                }

                $trx = new PpobTransaction();
                $trx->user_id        = $user->id_pengguna;
                $trx->order_id       = $orderId;
                $trx->group_order_id = $groupRefId;
                $trx->buyer_sku_code = $item['sku'];
                $trx->customer_no    = $item['customer_no'];
                $trx->selling_price  = $item['price'];
                $trx->price          = 0; // Modal (akan diupdate callback/response)
                $trx->profit         = 0;
                $trx->payment_method = $request->payment_method;
                $trx->desc           = json_encode($item['desc'] ?? []);

                // Status Awal
                $trx->status         = ($request->payment_method === 'saldo') ? 'Processing' : 'Pending';
                $trx->message        = 'Menunggu Pembayaran';

                // 🔥 SIMPAN IDEMPOTENCY KEY KE SETIAP ITEM TRANSAKSI
                // Semua item dalam satu kali checkout memiliki key yang sama
                $trx->idempotency_key = $key;

                $trx->save();

                // Simpan info untuk diproses setelah ini
                $createdTransactions[] = [
                    'order_id' => $orderId,
                    'sku'      => $item['sku'],
                    'cust_no'  => $item['customer_no'],
                    'is_pasca' => $isPasca,
                    'price'    => $item['price']
                ];
            }

        // 👇 KODE YANG KAMU PASTE DIMULAI DARI SINI 👇
            $paymentMethodRaw = strtoupper($request->payment_method);
            $paymentUrl = null;

            // 1. SALDO
            if ($paymentMethodRaw === 'SALDO') {
                $user->decrement('saldo', $totalPrice);
                $digiflazz = new DigiflazzService();

                foreach ($createdTransactions as $trxItem) {
                    $response = $trxItem['is_pasca']
                        ? $digiflazz->payPasca($trxItem['sku'], $trxItem['cust_no'], $trxItem['order_id'])
                        : $digiflazz->transaction($trxItem['sku'], $trxItem['cust_no'], $trxItem['order_id']);

                    if (isset($response['data']['status']) && in_array($response['data']['status'], ['Gagal', 'Failed'])) {
                        PpobTransaction::where('order_id', $trxItem['order_id'])->update([
                            'status' => 'Failed', 'message' => $response['data']['message'] ?? 'Gagal dari Provider'
                        ]);
                        $user->increment('saldo', $trxItem['price']);
                    }
                }
                DB::commit();
                session()->forget('ppob_cart');
                return redirect()->route('customer.ppob.history')->with('success', 'Transaksi sedang diproses!');
            }
            // 2. DOKU
            elseif (\Illuminate\Support\Str::startsWith($paymentMethodRaw, 'DOKU')) {
                $dokuService = new DokuJokulService();
                $customerData = ['name' => $user->nama_lengkap, 'email' => $user->email, 'phone' => $user->no_wa];
                $dokuItems = [];
                foreach($cart as $c) { $dokuItems[] = ['name' => $c['name'], 'quantity' => 1, 'price' => $c['price'], 'sku' => $c['sku']]; }

                $paymentUrl = $dokuService->createPayment($groupRefId, $totalPrice, $customerData, $dokuItems);
                if (!$paymentUrl) throw new Exception('Gagal generate link pembayaran DOKU.');
            }
            // 3. MIDTRANS
            elseif ($paymentMethodRaw === 'MIDTRANS') {
                $paymentUrl = $this->_createPaymentMidtransPpob($groupRefId, $totalPrice, $user);
            }
            // 4. PAYPAL
            elseif ($paymentMethodRaw === 'PAYPAL') {
                $paymentUrl = $this->_createPaymentPaypalPpob($groupRefId, $totalPrice, $user);
            }
            // 5. DANA
            elseif (in_array($paymentMethodRaw, ['DANA', 'NETWORK_PAY_PG_DANA', 'DANA_BINDING'])) {
                $paymentUrl = $this->_createPaymentDanaPpob($groupRefId, $totalPrice, $user);
            }
            // 6. TRIPAY (Default E-Wallet & VA)
            else {
                $orderItemsPayload = [];
                foreach($cart as $c) { $orderItemsPayload[] = ['sku' => $c['sku'], 'name' => substr($c['name'], 0, 250), 'price' => $c['price'], 'quantity' => 1]; }

                // Buat Object Palsu (Mock) agar sesuai dengan parameter Checkout Tripay Anda
                $dummyOrder = new \stdClass(); $dummyOrder->invoice_number = $groupRefId; $dummyOrder->user_id = $user->id_pengguna;

                $tripayResult = $this->_createTripayTransaction($dummyOrder, $request->payment_method, $totalPrice, $user->nama_lengkap, $user->email, $user->no_wa ?? '0812345678', $orderItemsPayload);
                if ($tripayResult['success']) {
                    $paymentUrl = $tripayResult['data']['checkout_url'] ?? $tripayResult['data']['pay_url'] ?? $tripayResult['data']['qr_url'];
                } else {
                    throw new Exception('Tripay Error: ' . ($tripayResult['message'] ?? 'Connection Failed'));
                }
            }

            // --- EKSEKUSI REDIRECT UNTUK SEMUA PAYMENT GATEWAY ONLINE ---
            if ($paymentUrl) {
                PpobTransaction::where('group_order_id', $groupRefId)->update(['payment_url' => $paymentUrl]);
                DB::commit();
                session()->forget('ppob_cart');
                return redirect()->away($paymentUrl);
            }
            // 👆 KODE YANG KAMU PASTE BERAKHIR DI SINI 👆

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('PPOB Checkout Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal memproses transaksi: ' . $e->getMessage());
        }
    }

    public function invoice($invoice)
    {
        $user = Auth::user();
        $transaction = PpobTransaction::where('order_id', $invoice)
                        ->where('user_id', $user->id_pengguna)
                        ->firstOrFail();

        return view('customer.ppob.invoice', compact('transaction'));
    }

    /**
     * =========================================================================
     * HELPER PAYMENT GATEWAY KHUSUS PPOB
     * =========================================================================
     */
    private function _createPaymentMidtransPpob($invoice, $amount, $user)
    {
        $mode = \App\Models\Api::getValue('MIDTRANS_MODE', 'global', 'sandbox');
        $serverKey = \App\Models\Api::getValue('MIDTRANS_SERVER_KEY', $mode);
        $baseUrl = ($mode === 'production') ? 'https://app.midtrans.com/snap/v1/transactions' : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

        $payload = [
            'transaction_details' => ['order_id' => $invoice, 'gross_amount' => (int) $amount],
            'customer_details' => ['first_name' => $user->nama_lengkap ?? 'Customer', 'email' => $user->email ?? 'guest@sancaka.com', 'phone' => $user->no_wa ?? ''],
            'callbacks' => ['finish' => route('customer.ppob.history')]
        ];

        $response = Http::withBasicAuth($serverKey, '')->post($baseUrl, $payload);
        $result = $response->json();
        if (isset($result['redirect_url'])) return $result['redirect_url'];
        throw new \Exception('Gagal memuat Midtrans: ' . ($result['error_messages'][0] ?? 'System Error'));
    }

    private function _createPaymentPaypalPpob($invoice, $amount, $user)
    {
        $paypalService = app(\App\Http\Controllers\Api\PayPalGatewayController::class);
        $usdAmount = round($amount / 16000, 2); // Asumsi Kurs 16rb
        $items = [['name' => 'PPOB Order ' . $invoice, 'quantity' => '1', 'unit_amount' => ['currency_code' => 'USD', 'value' => number_format($usdAmount, 2, '.', '')]]];
        $response = $paypalService->createOrder($items, $usdAmount, $invoice, 'CAPTURE', route('paypal.capture.return', ['invoice' => $invoice]), route('ppob.checkout.index'));
        $result = $response->getData(true);
        if (isset($result['success']) && $result['success'] === true && !empty($result['approve_url'])) return $result['approve_url'];
        throw new \Exception('Gagal memuat PayPal.');
    }

    private function _createPaymentDanaPpob($invoice, $amount, $user)
    {
        $danaSignature = app(\App\Services\DanaSignatureService::class);
        $isProd = (\App\Models\Api::getValue('dana_production_mode', 'global', '0') == '1');
        $env = $isProd ? 'prod' : 'sandbox';

        $merchantIdConf = \App\Models\Api::getValue("dana_{$env}_merchant_id", $env);
        $partnerIdConf  = \App\Models\Api::getValue("dana_{$env}_client_id", $env);
        $baseUrl        = $isProd ? 'https://api.saas.dana.id' : 'https://api.sandbox.dana.id';

        config([
            'services.dana.merchant_id'   => $merchantIdConf, 'services.dana.client_id' => $partnerIdConf,
            'services.dana.x_partner_id'  => $partnerIdConf, 'services.dana.private_key' => \App\Models\Api::getValue("dana_{$env}_private_key", $env),
            'services.dana.public_key'    => \App\Models\Api::getValue("dana_{$env}_public_key", $env),
            'services.dana.client_secret' => \App\Models\Api::getValue("dana_{$env}_client_secret", $env),
            'services.dana.base_url'      => $baseUrl,
        ]);

        $path = '/payment-gateway/v1.0/debit/payment-host-to-host.htm';
        $timestamp = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $validUpTo = \Carbon\Carbon::now('Asia/Jakarta')->addMinutes(30)->format('Y-m-d\TH:i:sP');

        $body = [
            "partnerReferenceNo" => $invoice, "merchantId" => $merchantIdConf, "validUpTo" => $validUpTo,
            "amount" => ["value" => number_format((float)$amount, 2, '.', ''), "currency" => "IDR"],
            "urlParams" => [
                ["url" => route('customer.ppob.history'), "type" => "PAY_RETURN", "isDeeplink" => "N"],
                ["url" => url('/dana/notify'), "type" => "NOTIFICATION", "isDeeplink" => "N"]
            ],
            "additionalInfo" => [
                "order" => ["orderTitle" => "PPOB " . $invoice, "scenario" => "REDIRECT", "merchantTransType" => "01"],
                "mcc" => "5732", "envInfo" => ["sourcePlatform" => "IPG", "terminalType" => "SYSTEM"]
            ]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = $danaSignature->generateSignature('POST', $path, $jsonBody, $timestamp);
        $token = $danaSignature->getAccessToken();

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $token, 'X-PARTNER-ID' => $partnerIdConf, 'X-EXTERNAL-ID' => \Illuminate\Support\Str::random(32),
            'X-TIMESTAMP' => $timestamp, 'X-SIGNATURE' => $signature, 'Content-Type' => 'application/json', 'CHANNEL-ID' => '95221', 'ORIGIN' => url('/')
        ])->post($baseUrl . $path, $jsonBody);

        $result = $response->json();
        if (isset($result['responseCode']) && $result['responseCode'] == '2005400' && !empty($result['webRedirectUrl'])) return substr($result['webRedirectUrl'], 0, 255);
        throw new \Exception('Gagal mendapatkan link DANA.');
    }
}
