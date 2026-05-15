<?php

namespace App\Http\Controllers\Customer;

use App\Events\AdminNotificationEvent;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Cache; // Untuk menyimpan data sementara biar gak lemot
use App\Models\Api; // Untuk ambil settingan database
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

use App\Services\DokuJokulService;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage; // Ditambahkan untuk 'uploadProof'
use App\Events\SaldoUpdated; // <-- DITAMBAHKAN
use Illuminate\Support\Facades\Notification; // <-- DITAMBAHKAN
use App\Notifications\NotifikasiUmum; // <-- DITAMBAHKAN

use Carbon\Carbon;
use App\Services\DanaSignatureService;

class TopUpController extends Controller
{
    protected $danaSignature;

    public function __construct(DanaSignatureService $danaSignature)
    {
        $this->danaSignature = $danaSignature;
        $this->applyDynamicConfig(); // <-- Panggil Config Dinamis
    }

    /**
     * Menampilkan riwayat transaksi top up.
     */
    public function index()
    {
        $user = Auth::user();

        // Mengambil dari relasi transactions (pastikan relasi ada di Model User)
        $transactions = $user->transactions()
                            ->where('type', 'topup')
                            ->latest()
                            ->paginate(15);

        return view('customer.topup.index', compact('transactions'));
    }

    /**
     * Menampilkan halaman form top up dengan Metode Pembayaran Dinamis.
     */
    public function create()
    {
        // 1. Ambil Channel dari Tripay (Otomatis)
        $tripayChannels = $this->getTripayChannels();

        // 2. Kelompokkan berdasarkan Group (Virtual Account, E-Wallet, dll)
        $groupedChannels = collect($tripayChannels)->groupBy('group');

        return view('customer.topup.create', compact('groupedChannels'));
    }

    /**
     * Helper: Ambil Channel Pembayaran dari API Tripay
     * Disimpan di Cache selama 24 jam agar website cepat (tidak loading terus ke Tripay)
     */
    private function getTripayChannels()
    {
        $mode = Api::getValue('TRIPAY_MODE', 'global', 'sandbox');

        // Gunakan Cache agar tidak nembak API setiap kali refresh halaman
        return Cache::remember('tripay_channels_' . $mode, 60 * 24, function () use ($mode) {

            $apiKey = ($mode === 'production')
                ? Api::getValue('TRIPAY_API_KEY', 'production')
                : Api::getValue('TRIPAY_API_KEY', 'sandbox');

            $baseUrl = ($mode === 'production')
                ? 'https://tripay.co.id/api/merchant/payment-channel'
                : 'https://tripay.co.id/api-sandbox/merchant/payment-channel';

            if (empty($apiKey)) return [];

            try {
                $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])->get($baseUrl);

                if ($response->successful()) {
                    return $response->json()['data'] ?? [];
                }
            } catch (\Exception $e) {
                Log::error('Gagal ambil channel Tripay: ' . $e->getMessage());
            }

            return [];
        });
    }

    /**
     * =========================================================================
     * FUNGSI STORE (Alur Baru: Upload Bukti di Halaman Show)
     * =========================================================================
     */
    public function store(Request $request, DokuJokulService $dokuJokulService)
    {
        $validated = $request->validate([
            'amount'            => 'required|numeric|min:10000',
            'payment_method'    => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $user = Auth::user();
            $amount = (int) $validated['amount'];
            $invoiceNumber = 'TOPUP-' . strtoupper(Str::random(10));

            // ==========================================================
            // Logika untuk TRANSFER MANUAL (Alur Baru)
            // ==========================================================
            if ($validated['payment_method'] === 'TRANSFER_MANUAL') {

                Log::info('Memulai Top Up Manual untuk ' . $invoiceNumber);

                $transaction = Transaction::create([
                    'user_id'            => $user->id_pengguna, // Sesuaikan dengan primary key User Anda
                    'amount'             => $amount,
                    'type'               => 'topup',
                    'status'             => 'pending',
                    'payment_method'     => $validated['payment_method'],
                    'description'        => 'Top up saldo via Transfer Manual',
                    'reference_id'       => $invoiceNumber,
                    'payment_proof_path' => null, // Path bukti transfer dikosongkan dulu
                    'payment_url'        => null,
                ]);

                DB::commit();

                // Langsung redirect ke halaman 'show' agar customer bisa lihat No. Rekening
                return redirect()->route('customer.topup.show', ['topup' => $transaction->reference_id])
                                 ->with('success', 'Silakan lakukan transfer dan upload bukti pembayaran Anda di halaman ini.');

            }
            // 2. LOGIKA DANA DIRECT
            elseif ($validated['payment_method'] === 'DANA' || $validated['payment_method'] === 'NETWORK_PAY_PG_DANA') {

                $transaction = Transaction::create([
                    'user_id'        => $user->id_pengguna,
                    'reference_id'   => $invoiceNumber,
                    'amount'         => $amount,
                    'type'           => 'topup',
                    'status'         => 'pending',
                    'description'    => 'Top up saldo via DANA',
                ]);

                DB::commit();
                return $this->createPaymentDANA($transaction);
            }
            // 3. Logika DOKU & TRIPAY
            else {

                $redirectUrl = null;

                $transaction = Transaction::create([
                    'user_id'        => $user->id_pengguna,
                    'amount'         => $amount,
                    'type'           => 'topup',
                    'status'         => 'pending',
                    'payment_method' => $validated['payment_method'],
                    'description'    => 'Top up saldo via ' . $validated['payment_method'],
                    'reference_id'   => $invoiceNumber,
                ]);

                // Notifikasi Admin (Opsional untuk PG)
                $message = $user->nama_lengkap . ' meminta top up sebesar Rp ' . number_format($amount);
                $url = route('admin.saldo.requests.index');
                event(new AdminNotificationEvent('Permintaan Top Up Baru!', $message, $url));


                $paymentUrl = null;
                $paymentGateway = 'tripay'; // Default

                if (strtoupper($validated['payment_method']) === 'DOKU_JOKUL') {
                    $paymentGateway = 'doku';
                }

                $customerData = [
                    'name'  => $user->nama_lengkap,
                    'email' => $user->email,
                    'phone' => $user->no_wa
                ];

                if ($paymentGateway === 'doku') {
                    // --- PROSES VIA DOKU JOKUL ---
                    Log::info('Memulai Top Up DOKU (Jokul) untuk ' . $invoiceNumber);

                    $DokuJokulService = $dokuJokulService;
                    $lineItems = [
                        ['name' => 'Top Up Saldo', 'price' => $amount, 'quantity' => 1]
                    ];
                    $successRedirectUrl = route('customer.topup.show', ['topup' => $invoiceNumber]);

                    $paymentUrl = $DokuJokulService->createPayment(
                        $invoiceNumber,
                        $amount,
                        $customerData,
                        $lineItems,
                        [], // additionalInfo
                        $successRedirectUrl // redirectUrl
                    );

                    $redirectUrl = $paymentUrl;

                    if (empty($paymentUrl)) {
                        throw new Exception('Gagal membuat transaksi DOKU.');
                    }

                } else {
                    // --- PROSES VIA TRIPAY ---
                    Log::info('Memulai Top Up Tripay untuk ' . $invoiceNumber);

                    $apiKey       = config('tripay.api_key');
                    $privateKey   = config('tripay.private_key');
                    $merchantCode = config('tripay.merchant_code');
                    $mode = \App\Models\Api::getValue('TRIPAY_MODE', 'global', 'sandbox');

                    $payload = [
                        'method'         => $validated['payment_method'],
                        'merchant_ref'   => $invoiceNumber,
                        'amount'         => $amount,
                        'customer_name'  => $customerData['name'],
                        'customer_email' => $customerData['email'],
                        'customer_phone' => $customerData['phone'],
                        'order_items'    => [
                            ['sku' => 'TOPUP', 'name' => 'Top Up Saldo', 'price' => $amount, 'quantity' => 1,],
                        ],
                        'expired_time'   => time() + (1 * 60 * 60), // 1 Jam
                        'signature'      => hash_hmac('sha256', $merchantCode.$invoiceNumber.$amount, $privateKey),
                    ];

                    $baseUrl = $mode === 'production'
                        ? 'https://tripay.co.id/api/transaction/create'
                        : 'https://tripay.co.id/api-sandbox/transaction/create';

                    $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                                    ->timeout(30)
                                    ->post($baseUrl, $payload);

                    if ($response->successful() && isset($response->json()['success']) && $response->json()['success'] === true) {
                        $tripayData = $response->json()['data'];
                        $redirectUrl = $tripayData['checkout_url'] ?? null;

                        if (empty($redirectUrl)) {
                             $redirectUrl = route('customer.topup.show', ['topup' => $transaction->reference_id]);
                        }

                        $paymentUrl = $tripayData['pay_code']   ??
                                      $tripayData['qr_url']     ??
                                      $redirectUrl              ??
                                      null;

                    } else {
                        Log::error('Gagal membuat transaksi di Tripay', $response->json());
                        throw new Exception('Gagal membuat transaksi di Tripay: ' . ($response->json()['message'] ?? 'Error tidak diketahui'));
                    }
                }

                // 4. SIMPAN URL/KODE BAYAR & COMMIT
                $transaction->payment_url = $paymentUrl;
                $transaction->save();


                DB::commit();

                // 5. ARAHKAN KE HALAMAN PEMBAYARAN
                if (!empty($redirectUrl)) {
                    return redirect()->away($redirectUrl);
                }

                // Fallback jika tidak ada URL
                return redirect()->route('customer.topup.show', ['topup' => $transaction->reference_id]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal memproses Top Up: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Menampilkan detail transaksi (termasuk yang pending untuk dibayar).
     */
    public function show($topup)
    {
        $user = Auth::user();

        $topUp = Transaction::where('reference_id', $topup)
                            ->where('user_id', $user->id_pengguna) // Sesuaikan
                            ->where('type', 'topup')
                            ->firstOrFail();

        return view('customer.topup.show', compact('topUp'));
    }

    /**
     * Meng-upload bukti bayar untuk transaksi manual yang pending.
     */
    public function uploadProof(Request $request, $reference_id)
    {
        $validated = $request->validate([
            'proof_of_payment' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg',
                'max:2048',
            ],
        ]);

        DB::beginTransaction();
        try {
            $user = Auth::user();

            // Cari transaksi yang pending & milik user ini
            $transaction = Transaction::where('reference_id', $reference_id)
                ->where('user_id', $user->id_pengguna)
                ->where('description', 'LIKE', '%Transfer Manual%')
                ->where('status', 'pending')
                ->firstOrFail();

            // Hapus bukti lama jika ada (Opsional, tapi bagus)
            if ($transaction->payment_proof_path) {
                Storage::disk('public')->delete($transaction->payment_proof_path);
            }

            // Simpan file baru
            $filePath = $request->file('proof_of_payment')->store('proofs_of_payment', 'public');
            $transaction->payment_proof_path = $filePath;
            $transaction->save();

            // KIRIM NOTIFIKASI KE ADMIN
            try {
                $admins = User::where('role', 'admin')->get();
                if ($admins->isNotEmpty()) {
                    $dataNotifAdmin = [
                        'tipe'        => 'TopUp',
                        'judul'       => 'Konfirmasi Top Up Manual',
                        'pesan_utama' => $user->nama_lengkap . ' telah mengunggah bukti bayar Rp ' . number_format($transaction->amount),
                        'url'         => route('admin.saldo.requests.index'),
                        'icon'        => 'fas fa-upload',
                    ];
                    Notification::send($admins, new NotifikasiUmum($dataNotifAdmin));
                }
            } catch (Exception $e) {
                Log::error('Gagal kirim notif admin (uploadProof): ' . $e->getMessage());
            }

            DB::commit();
            return back()->with('success', 'Bukti transfer Anda telah terkirim dan sedang menunggu konfirmasi admin.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal upload bukti Top Up: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * =========================================================================
     * HANDLER WEBHOOK DOKU (JOKUL)
     * =========================================================================
     */
    public function handleDokuCallback(array $data)
    {
        $merchantRef = $data['order']['invoice_number'];
        $status = $data['transaction']['status']; // Seharusnya 'SUCCESS'

        Log::info('Processing DOKU Callback (di TopUpController)...', [
            'ref' => $merchantRef, 'status' => $status
        ]);

        $internalStatus = ($status === 'SUCCESS') ? 'PAID' : 'FAILED';

        return self::processTopUp($merchantRef, $internalStatus, $data['order']['amount']);
    }

    /**
     * =========================================================================
     * HANDLER WEBHOOK TRIPAY
     * =========================================================================
     */
    public static function processTopUpCallback($merchantRef, $status, $amount)
    {
        Log::info('Processing Tripay Callback (di TopUpController)...', [
            'ref' => $merchantRef, 'status' => $status
        ]);

        return self::processTopUp($merchantRef, $status, $amount);
    }

    /**
     * =========================================================================
     * PROSESOR INTI TOP UP (Dipakai oleh DOKU & TRIPAY)
     * =========================================================================
     */
    private static function processTopUp($merchantRef, $status, $amount)
    {
        DB::beginTransaction();
        try {
            $transaction = Transaction::where('reference_id', $merchantRef)->lockForUpdate()->first();

            if (!$transaction) {
                Log::error('TopUp Callback: Transaksi tidak ditemukan.', ['ref' => $merchantRef]);
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Not Found'], 404);
            }

            if ($transaction->status !== 'pending') {
                Log::info('TopUp Callback: Transaksi sudah diproses.', ['ref' => $merchantRef, 'status' => $transaction->status]);
                DB::rollBack();
                return response()->json(['success' => true, 'message' => 'Already processed']);
            }

            if ($transaction->payment_method === 'TRANSFER_MANUAL') {
                 Log::warning('TopUp Callback: Mencoba memproses TRANSFER_MANUAL via webhook.', ['ref' => $merchantRef]);
                 DB::rollBack();
                 return response()->json(['success' => false, 'message' => 'Manual transfer'], 400);
            }

            // Validasi jumlah
            if ($transaction->amount != $amount) {
                 Log::warning('TopUp Callback: Jumlah tidak cocok.', [
                     'db_amount' => $transaction->amount,
                     'paid_amount' => $amount
                 ]);
            }

            if ($status === 'PAID') { // PAID (Tripay) atau SUCCESS (DOKU)

                $transaction->status = 'success';
                $transaction->save();

                $user = User::find($transaction->user_id);
                if ($user) {
                    $user->increment('saldo', $transaction->amount); // Tambah saldo utama

                    Log::info('TopUp Callback: Saldo user berhasil ditambah.', [
                        'user_id' => $user->id_pengguna, // Sesuaikan
                        'amount' => $transaction->amount
                    ]);

                    // 1. Kirim event ke UI Customer
                    try {
                        $message = 'Top up Anda sebesar ' . number_format($transaction->amount) . ' telah berhasil.';
                        event(new SaldoUpdated($user, $transaction->amount, $user->saldo, $message));
                    } catch (Exception $e) {
                        Log::error('Gagal broadcast SaldoUpdated: ' . $e->getMessage());
                    }

                    // 2. Kirim notifikasi DB ke Customer
                    try {
                        $dataNotifCustomer = [
                            'tipe'        => 'TopUp',
                            'judul'       => 'Top Up Berhasil',
                            'pesan_utama' => 'Top up saldo Rp ' . number_format($transaction->amount) . ' telah berhasil.',
                            'url'         => route('customer.topup.index'),
                            'icon'        => 'fas fa-check-circle',
                        ];
                        $user->notify(new NotifikasiUmum($dataNotifCustomer));
                    } catch (Exception $e) {
                        Log::error('Gagal kirim notif customer (topup success): ' . $e->getMessage());
                    }

                    // 3. Kirim notifikasi DB ke Admin
                    try {
                        $admins = User::where('role', 'admin')->get();
                        if ($admins->isNotEmpty()) {
                            $dataNotifAdmin = [
                                'tipe'        => 'TopUp',
                                'judul'       => 'Top Up Otomatis Berhasil',
                                'pesan_utama' => $user->nama_lengkap . ' berhasil top up via PG Rp ' . number_format($transaction->amount),
                                'url'         => route('admin.saldo.requests.history'),
                                'icon'        => 'fas fa-check-circle',
                            ];
                            Notification::send($admins, new NotifikasiUmum($dataNotifAdmin));
                        }
                    } catch (Exception $e) {
                        Log::error('Gagal kirim notif admin (topup success): ' . $e->getMessage());
                    }

                } else {
                    Log::error('TopUp Callback: User tidak ditemukan!', ['user_id' => $transaction->user_id]);
                }

            } else { // FAILED, EXPIRED, dll.
                $transaction->status = 'failed';
                $transaction->save();
                Log::info('TopUp Callback: Transaksi gagal.', ['ref' => $merchantRef, 'status' => $status]);
            }

            DB::commit();
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::critical('TopUp Callback: CRITICAL ERROR.', [
                'ref' => $merchantRef, 'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => 'Internal Error'], 500);
        }
    }

    /**
     * Method BARU untuk mengecek status via API (untuk polling).
     */
    public function checkStatus($reference_id)
    {
        $transaction = Transaction::where('reference_id', $reference_id)
                                ->where('user_id', auth()->id())
                                ->first(['status']); // Hanya ambil kolom status

        if (!$transaction) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json(['status' => $transaction->status]);
    }

    // 1. START BINDING
    public function startBinding(Request $request)
    {
        Log::info('[BINDING] Memulai proses redirect ke DANA Portal...');

        $affiliateId = $request->affiliate_id ?? 11;

        $queryParams = [
            'partnerId'   => config('services.dana.x_partner_id'),
            'timestamp'   => now('Asia/Jakarta')->toIso8601String(),
            'externalId'  => 'BIND-' . $affiliateId . '-' . time(),
            'merchantId'  => config('services.dana.merchant_id'),
            'redirectUrl' => config('services.dana.redirect_url_oauth'),
            'state'       => 'ID-' . $affiliateId,
            'scopes'      => 'QUERY_BALANCE,MINI_DANA,DEFAULT_BASIC_PROFILE',
        ];

        // URL Dinamis
        $baseUrl = config('services.dana.dana_env') === 'PRODUCTION' ? 'https://m.dana.id' : 'https://m.sandbox.dana.id';
        return redirect($baseUrl . "/d/portal/oauth?" . http_build_query($queryParams));
    }

    public function handleCallback(Request $request)
    {
        Log::info('[DANA CALLBACK] Mendapatkan Auth Code:', $request->all());

        $authCode = $request->input('auth_code');
        $state = $request->input('state');
        $affiliateId = $state ? str_replace('ID-', '', $state) : 11;

        if (!$authCode) {
            return redirect()->route('member.dashboard')->with('error', 'Auth Code Kosong');
        }

        DB::table('affiliates')->where('id', $affiliateId)->update([
            'dana_auth_code' => $authCode,
            'updated_at' => now()
        ]);

        try {
            $timestamp = now('Asia/Jakarta')->toIso8601String();
            $clientId = config('services.dana.x_partner_id');
            $externalId = (string) time();

            $stringToSign = $clientId . "|" . $timestamp;
            $signature = $this->generateSignature($stringToSign);

            $path = '/v1.0/access-token/b2b2c.htm';
            $body = [
                'grantType' => 'authorization_code',
                'authCode' => $authCode,
                'additionalInfo' => (object)[]
            ];

            // URL Dinamis
            $response = Http::withHeaders([
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'X-PARTNER-ID'  => $clientId,
                'X-CLIENT-KEY'  => $clientId,
                'X-EXTERNAL-ID' => $externalId,
                'Content-Type'  => 'application/json'
            ])->post(config('services.dana.base_url') . $path, $body);

            $result = $response->json();
            $successCodes = ['2001100', '2007400'];

            if (isset($result['responseCode']) && in_array($result['responseCode'], $successCodes)) {
                DB::table('affiliates')->where('id', $affiliateId)->update([
                    'dana_access_token' => $result['accessToken'],
                    'updated_at' => now()
                ]);

                try {
                    DB::table('dana_transactions')->insert([
                        'affiliate_id' => $affiliateId,
                        'type' => 'BINDING',
                        'reference_no' => $externalId,
                        'phone' => '-',
                        'amount' => 0,
                        'status' => 'SUCCESS',
                        'response_payload' => json_encode($result),
                        'created_at' => now()
                    ]);
                } catch (\Exception $dbEx) {
                    Log::error('[DANA CALLBACK] Gagal simpan log transaksi: ' . $dbEx->getMessage());
                }

                return redirect()->route('member.dashboard')->with('success', '✅ Akun Berhasil Terhubung!');
            }

            try {
                DB::table('dana_transactions')->insert([
                    'affiliate_id' => $affiliateId,
                    'type' => 'BINDING',
                    'reference_no' => $externalId,
                    'phone' => '-',
                    'amount' => 0,
                    'status' => 'FAILED',
                    'response_payload' => json_encode($result),
                    'created_at' => now()
                ]);
            } catch (\Exception $dbEx) {
                Log::error('[DANA CALLBACK] Gagal simpan log error: ' . $dbEx->getMessage());
            }

            Log::error('[EXCHANGE FAILED]', $result);
            return redirect()->route('member.dashboard')->with('error', 'Gagal Tukar Token: ' . ($result['responseMessage'] ?? 'Unknown Error'));

        } catch (\Exception $e) {
            Log::error('[DANA CALLBACK] System Error:', ['msg' => $e->getMessage()]);
            return redirect()->route('member.dashboard')->with('error', 'Sistem Error: ' . $e->getMessage());
        }
    }

    // 3. CEK SALDO USER
    public function checkBalance(Request $request)
    {
        $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
        $accessToken = $request->access_token ?? $aff->dana_access_token;

        if (!$accessToken) return back()->with('error', 'Token Kosong.');

        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $path = '/v1.0/balance-inquiry.htm';
        $body = [
            'partnerReferenceNo' => 'BAL' . time(),
            'balanceTypes' => ['BALANCE'],
            'additionalInfo' => ['accessToken' => $accessToken]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        // URL Dinamis
        $response = Http::withHeaders([
            'X-TIMESTAMP'   => $timestamp,
            'X-SIGNATURE'   => $signature,
            'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
            'X-EXTERNAL-ID' => (string) time(),
            'X-DEVICE-ID'   => 'DANA-DASHBOARD-STATION',
            'CHANNEL-ID'    => '95221',
            'ORIGIN'        => config('services.dana.origin'),
            'Authorization-Customer' => 'Bearer ' . $accessToken,
            'Content-Type'  => 'application/json'
        ])->withBody($jsonBody, 'application/json')->post(config('services.dana.base_url') . $path);

        $result = $response->json();

        if (isset($result['responseCode']) && $result['responseCode'] == '2001100') {
            $amount = $result['accountInfos'][0]['availableBalance']['value'];
            DB::table('affiliates')->where('id', $request->affiliate_id)->update(['dana_user_balance' => $amount, 'updated_at' => now()]);
            return back()->with('success', 'Saldo Real DANA Terupdate!');
        }
        return back()->with('error', 'Gagal: ' . ($result['responseMessage'] ?? 'Error'));
    }

    // 4. CEK SALDO MERCHANT
    public function checkMerchantBalance(Request $request)
    {
        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $payload = ["request" => ["head" => ["version" => "2.0", "function" => "dana.merchant.queryMerchantResource", "clientId" => config('services.dana.x_partner_id'), "clientSecret" => config('services.dana.client_secret'), "reqTime" => $timestamp, "reqMsgId" => (string) Str::uuid(), "reserve" => "{}"], "body" => ["requestMerchantId" => config('services.dana.merchant_id'), "merchantResourceInfoList" => ["MERCHANT_DEPOSIT_BALANCE"]]]];

        $jsonToSign = json_encode($payload['request'], JSON_UNESCAPED_SLASHES);
        $signature = $this->generateSignature($jsonToSign);

        // URL Dinamis
        $response = Http::post(config('services.dana.base_url') . '/dana/merchant/queryMerchantResource.htm', ["request" => $payload['request'], "signature" => $signature]);
        $res = $response->json();

        if (isset($res['response']['body']['resultInfo']['resultStatus']) && $res['response']['body']['resultInfo']['resultStatus'] === 'S') {
            $val = json_decode($res['response']['body']['merchantResourceInformations'][0]['value'], true);
            DB::table('affiliates')->where('id', $request->affiliate_id)->update(['dana_merchant_balance' => $val['amount']]);
            return back()->with('success', 'Saldo Merchant Terupdate!');
        }
        return back()->with('error', 'Gagal Cek Merchant');
    }

    private function generateSignature($stringToSign) {
        $privateKey = config('services.dana.private_key');
        $binarySignature = "";
        openssl_sign($stringToSign, $binarySignature, $privateKey, OPENSSL_ALGO_SHA256);
        return base64_encode($binarySignature);
    }

    public function topupSaldo(Request $request)
    {
        Log::info('[DANA TOPUP] --- MEMULAI PROSES TOPUP ---', ['affiliate_id' => $request->affiliate_id]);

        $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();

        if (!$aff || $aff->balance < $request->amount) {
            Log::warning('[DANA TOPUP] Saldo Tidak Cukup atau Affiliate Tidak Ditemukan');
            return back()->with('error', 'Gagal: Saldo profit tidak mencukupi.');
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $request->phone ?? $aff->whatsapp);
        if (substr($cleanPhone, 0, 1) === '0') $cleanPhone = '62' . substr($cleanPhone, 1);

        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $path = '/v1.0/emoney/customer-top-up.htm';
        $partnerRef = 'TP' . time() . Str::random(4);

        $body = [
            'partnerReferenceNo' => $partnerRef,
            'amount' => [
                'value' => number_format((float)$request->amount, 2, '.', ''),
                'currency' => 'IDR'
            ],
            'beneficiaryAccountNo' => $cleanPhone,
            'additionalInfo' => (object)[]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        $headers = [
            'X-TIMESTAMP'   => $timestamp,
            'X-SIGNATURE'   => $signature,
            'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
            'X-EXTERNAL-ID' => (string) time() . Str::random(4),
            'X-DEVICE-ID'   => 'DANA-DASHBOARD-STATION',
            'CHANNEL-ID'    => '95221',
            'Content-Type'  => 'application/json',
            'Authorization-Customer' => 'Bearer ' . $aff->dana_access_token
        ];

        try {
            Log::info('[DANA TOPUP] Mengirim Request...', ['headers' => $headers]);

            // URL Dinamis
            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();

            Log::info('[DANA TOPUP] Respon Diterima', ['status' => $response->status(), 'result' => $result]);

            if ($response->successful()) {
                DB::table('affiliates')->where('id', $aff->id)->decrement('balance', $request->amount);

                DB::table('dana_transactions')->insert([
                    'affiliate_id' => $aff->id,
                    'type' => 'TOPUP',
                    'reference_no' => $partnerRef,
                    'phone' => $cleanPhone,
                    'amount' => $request->amount,
                    'status' => 'SUCCESS',
                    'response_payload' => json_encode($result),
                    'created_at' => now()
                ]);

                $pesanUser = "✅ *PENCAIRAN PROFIT BERHASIL*\n\n";
                $pesanUser .= "Halo " . $aff->name . ",\n";
                $pesanUser .= "Pencairan profit Anda ke DANA telah sukses.\n\n";
                $pesanUser .= "*Detail:* \n";
                $pesanUser .= "▪️ Nominal: Rp " . number_format($request->amount, 0, ',', '.') . "\n";
                $pesanUser .= "▪️ No. DANA: " . $cleanPhone . "\n";
                $pesanUser .= "▪️ Ref ID: " . $partnerRef . "\n";
                $pesanUser .= "▪️ Waktu: " . now()->format('d/m H:i') . " WIB\n\n";
                $pesanUser .= "Saldo profit Anda telah otomatis terpotong. Terima kasih!";

                $this->sendWhatsApp($cleanPhone, $pesanUser);

                $pesanAdmin = "📢 *LAPORAN TOPUP SUKSES*\n\n";
                $pesanAdmin .= "Affiliate: " . $aff->name . " (ID: " . $aff->id . ")\n";
                $pesanAdmin .= "Nominal: Rp " . number_format($request->amount, 0, ',', '.') . "\n";
                $pesanAdmin .= "Tujuan: " . $cleanPhone . "\n";
                $pesanAdmin .= "Status: Saldo Berhasil Dipotong.";

                $this->sendWhatsApp('6285745808809', $pesanAdmin);

                Log::info('[DANA TOPUP] BERHASIL & WA TERKIRIM');

                return back()->with('success', '💸 Topup Berhasil, Saldo Dipotong, dan WA Terkirim!');
            }

            return back()->with('error', 'Gagal dari DANA: ' . ($result['responseMessage'] ?? 'Respon Server Error'));

        } catch (\Exception $e) {
            Log::error('[DANA TOPUP] Exception!', ['msg' => $e->getMessage()]);
            return back()->with('error', 'Sistem Error: ' . $e->getMessage());
        }
    }

    public function accountInquiry(Request $request)
    {
        Log::info('[DANA INQUIRY] Start Process', [
            'affiliate_id' => $request->affiliate_id,
            'amount' => $request->amount,
            'ip' => $request->ip()
        ]);

        $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
        if (!$aff) {
            Log::error('[DANA INQUIRY] Affiliate Not Found', ['id' => $request->affiliate_id]);
            return back()->with('error', 'Affiliate tidak ditemukan.');
        }

        $rawPhone = $request->phone ?? $aff->whatsapp;
        $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
        if (substr($cleanPhone, 0, 1) === '0') {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        }
        Log::info('[DANA INQUIRY] Phone Sanitized', ['original' => $rawPhone, 'clean' => $cleanPhone]);

        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $path = '/v1.0/emoney/account-inquiry.htm';
        $amountValue = $request->amount ?? 10000;

        $body = [
            "partnerReferenceNo" => "INQ" . time() . Str::random(5),
            "customerNumber"     => $cleanPhone,
            "amount" => [
                "value"    => number_format((float)$amountValue, 2, '.', ''),
                "currency" => "IDR"
            ],
            "transactionDate" => $timestamp,
            "additionalInfo"  => [
                "fundType"           => "AGENT_TOPUP_FOR_USER_SETTLE",
                "externalDivisionId" => "",
                "chargeTarget"       => "MERCHANT",
                "customerId"         => ""
            ]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        Log::info('[DANA INQUIRY] Security Detail', [
            'path' => $path,
            'stringToSign' => $stringToSign,
            'signature' => $signature
        ]);

        $headers = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $aff->dana_access_token,
            'X-TIMESTAMP'   => $timestamp,
            'X-SIGNATURE'   => $signature,
            'ORIGIN'        => config('services.dana.origin'),
            'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
            'X-EXTERNAL-ID' => (string) time() . Str::random(5),
            'X-IP-ADDRESS'  => $request->ip() ?? '127.0.0.1',
            'X-DEVICE-ID'   => 'DANA-DASHBOARD-01',
            'CHANNEL-ID'    => '95221'
        ];

        try {
            Log::info('[DANA INQUIRY] Sending Request to DANA', ['body' => $body]);

            // URL Dinamis
            $response = Http::withHeaders($headers)->withBody($jsonBody, 'application/json')->post(config('services.dana.base_url') . $path);
            $result = $response->json();

            Log::info('[DANA INQUIRY] Response Received', ['status' => $response->status(), 'result' => $result]);

            $resCode = $result['responseCode'] ?? '5003700';
            $resMsg = $result['responseMessage'] ?? 'Unexpected response';

            DB::table('dana_transactions')->insert([
                'affiliate_id' => $request->affiliate_id,
                'type' => 'INQUIRY',
                'reference_no' => $body['partnerReferenceNo'],
                'phone' => $cleanPhone,
                'amount' => $amountValue,
                'status' => in_array($resCode, ['2000000', '2003700']) ? 'SUCCESS' : 'FAILED',
                'response_payload' => json_encode($result),
                'created_at' => now()
            ]);

            $responseMapping = [
                '2003700' => '✅ SUCCESS: Account Inquiry processed.',
                '4003700' => '❌ FAILED: Bad Request (General).',
                '4003701' => '❌ FAILED: Invalid Field Format.',
                '4003702' => '❌ FAILED: Invalid Mandatory Field.',
                '4013700' => '❌ UNAUTHORIZED: General Auth Error.',
                '4013701' => '❌ UNAUTHORIZED: Invalid B2B Token.',
                '4013702' => '❌ UNAUTHORIZED: Invalid Customer Token.',
                '4033702' => '⚠️ TEST CASE: Exceeds Amount Limit (21jt).',
                '4033705' => '❌ FAILED: Do Not Honor (Abnormal Status).',
                '4033714' => '❌ FAILED: Insufficient Funds (Merchant).',
                '4033718' => '❌ FAILED: Inactive Account.',
                '4043711' => '❌ FAILED: Invalid Account/Not Found.',
                '4293700' => '❌ FAILED: Too Many Requests.',
                '5003701' => '❌ FAILED: Internal Server Error.',
            ];

            $displayMsg = $responseMapping[$resCode] ?? "[$resCode] $resMsg";

            if (in_array($resCode, ['2000000', '2003700'])) {
                $customerName = $result['additionalInfo']['customerName'] ?? 'Akun Valid';

                DB::table('affiliates')->where('id', $request->affiliate_id)->update([
                    'dana_user_name' => $customerName,
                    'updated_at' => now()
                ]);

                $pesanUser = "🛡️ *Sancaka DANA Center - Verifikasi*\n\n";
                $pesanUser .= "Halo *" . $aff->name . "*,\n";
                $pesanUser .= "Akun DANA Anda berhasil diverifikasi.\n\n";
                $pesanUser .= "▪️ Nama: *" . $customerName . "*\n";
                $pesanUser .= "▪️ No. DANA: " . $cleanPhone . "\n";
                $pesanUser .= "▪️ Status: ✅ *AKUN VALID*\n\n";
                $pesanUser .= "Terima kasih!";
                $this->sendWhatsApp($cleanPhone, $pesanUser);

                return back()->with('success', $displayMsg);
            }

            $pesanAdmin = "📢 *DANA INQUIRY NOTIFICATION*\n\n";
            $pesanAdmin .= "▪️ Affiliate: " . $aff->name . "\n";
            $pesanAdmin .= "▪️ Target: " . $cleanPhone . "\n";
            $pesanAdmin .= "▪️ Nominal: Rp " . number_format($amountValue, 0, ',', '.') . "\n";
            $pesanAdmin .= "▪️ Result: " . $displayMsg . "\n";
            $pesanAdmin .= "▪️ Waktu: " . now()->format('H:i:s') . " WIB";
            $this->sendWhatsApp('6285745808809', $pesanAdmin);

            return back()->with('error', $displayMsg);

        } catch (\Exception $e) {
            Log::error('[DANA INQUIRY] Exception!', ['message' => $e->getMessage()]);
            return back()->with('error', 'Sistem Error: ' . $e->getMessage());
        }
    }

    public function handleWebhook(Request $request)
    {
        Log::info('========== DANA WEBHOOK INCOMING ==========', $request->all());

        $head = $request->input('request.head');
        $body = $request->input('request.body');

        if ($head['function'] === 'dana.acquiring.order.finishNotify') {
            $merchantTransId = $body['merchantTransId'];
            $status = $body['acquirementStatus']; // Contoh: CLOSED, FAILED, SUCCESS

            $trx = DB::table('dana_transactions')->where('reference_no', $merchantTransId)->first();

            if ($trx) {
                DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => $status]);

                if (in_array($status, ['CLOSED', 'FAILED']) && $trx->status === 'SUCCESS') {
                    DB::table('affiliates')->where('id', $trx->affiliate_id)->increment('balance', $trx->amount);

                    DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => 'REFUNDED']);
                    Log::info('[WEBHOOK] Saldo Profit Berhasil Direfund!', ['affiliate' => $trx->affiliate_id]);
                }
            }
        }

        return response()->json(['response' => ['head' => ['resultCode' => 'SUCCESS']]]);
    }

    private function sendWhatsApp($to, $message)
    {
        $token = "ynMyPswSKr14wdtXMJF7";

        $to = preg_replace('/[^0-9]/', '', $to);
        if (substr($to, 0, 1) === '0') $to = '62' . substr($to, 1);

        Log::info('[FONTE] Mengirim pesan ke ' . $to);

        try {
            $response = Http::withHeaders([
                'Authorization' => $token
            ])->post('https://api.fonnte.com/send', [
                'target' => $to,
                'message' => $message,
                'countryCode' => '62',
            ]);

            Log::info('[FONTE] Respon:', $response->json());
            return $response->json();
        } catch (\Exception $e) {
            Log::error('[FONTE] Error: ' . $e->getMessage());
            return false;
        }
    }

    public function customerTopup(Request $request)
    {
        Log::info('[DANA TOPUP] --- MEMULAI PROSES TOPUP ---', [
            'affiliate_id' => $request->affiliate_id,
            'target_phone' => $request->phone,
            'amount' => $request->amount,
            'ip' => $request->ip()
        ]);

        $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
        if (!$aff) {
            Log::error('[DANA TOPUP] Affiliate Tidak Ditemukan', ['id' => $request->affiliate_id]);
            return back()->with('error', 'Affiliate tidak terdaftar di sistem.');
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $request->phone);

        if (substr($cleanPhone, 0, 2) === '62') {
            // Biarkan
        } elseif (substr($cleanPhone, 0, 1) === '0') {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        } elseif (substr($cleanPhone, 0, 1) === '8') {
            $cleanPhone = '62' . $cleanPhone;
        }

        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $partnerRef = (string) time() . Str::random(8);
        $valStr = number_format((float)$request->amount, 2, '.', '');

        $body = [
            "partnerReferenceNo" => $partnerRef,
            "customerNumber"     => $cleanPhone,
            "amount" => [
                "value"    => $valStr,
                "currency" => "IDR"
            ],
            "feeAmount" => [
                "value"    => "0.00",
                "currency" => "IDR"
            ],
            "transactionDate" => $timestamp,
            "sessionId"       => (string) Str::uuid(),
            "categoryId"      => "6",
            "notes"           => "Topup Sancaka",
            "additionalInfo"  => [
                    "fundType"           => "AGENT_TOPUP_FOR_USER_SETTLE",
                    "externalDivisionId" => "",
                    "chargeTarget"       => "MERCHANT",
                    "customerId"         => ""
                ]
        ];

        $path = '/v1.0/emoney/topup.htm';
        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        $headers = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $aff->dana_access_token,
            'X-TIMESTAMP'   => $timestamp,
            'X-SIGNATURE'   => $signature,
            'ORIGIN'        => config('services.dana.origin'),
            'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
            'X-EXTERNAL-ID' => (string) time() . Str::random(6),
            'X-IP-ADDRESS'  => $request->ip(),
            'X-DEVICE-ID'   => 'SANCAKA-DANA-01',
            'CHANNEL-ID'    => '95221'
        ];

        try {
            Log::info('[DANA TOPUP] Mengirim Request ke DANA API', ['body' => $body]);

            // URL Dinamis
            $response = Http::withHeaders($headers)->withBody($jsonBody, 'application/json')->post(config('services.dana.base_url') . $path);
            $result = $response->json();

            $resCode = $result['responseCode'] ?? '5003801';

            $library = DB::table('dana_response_codes')
                        ->where('response_code', $resCode)
                        ->where('category', 'TOPUP')
                        ->first();

            if (!$library) {
                $isSuccessCode = in_array($resCode, ['2000000', '2003800']);

                DB::table('dana_response_codes')->insert([
                    'response_code' => $resCode,
                    'category'      => 'TOPUP',
                    'message_title' => $isSuccessCode ? 'Transaction Success' : 'New Code Detected',
                    'description'   => $result['responseMessage'] ?? 'Auto Generated',
                    'solution'      => 'Cek Dokumentasi DANA',
                    'is_success'    => $isSuccessCode,
                    'created_at'    => now()
                ]);

                $library = DB::table('dana_response_codes')
                            ->where('response_code', $resCode)
                            ->where('category', 'TOPUP')
                            ->first();
            }

            DB::table('dana_transactions')->insert([
                'affiliate_id' => $aff->id,
                'type' => 'TOPUP',
                'reference_no' => $partnerRef,
                'phone' => $cleanPhone,
                'amount' => $request->amount,
                'status' => $library->is_success ? 'SUCCESS' : 'FAILED',
                'response_payload' => json_encode($result),
                'created_at' => now()
            ]);

            $waToken = "ynMyPswSKr14wdtXMJF7";
            $adminWA = "6285745808809";

            if ($library->is_success) {
                DB::table('affiliates')->where('id', $aff->id)->decrement('balance', $request->amount);

                $msgUser = "✅ *TOPUP BERHASIL*\n\nHalo *{$aff->name}*,\nTopup DANA ke {$cleanPhone} senilai Rp " . number_format($request->amount) . " berhasil.\n\nRef ID: {$partnerRef}\nWaktu: " . now()->format('d/m H:i') . " WIB\nTerima kasih!";
                $this->sendWhatsApp($cleanPhone, $msgUser, $waToken);

                Log::info('[DANA TOPUP] Berhasil', ['code' => $resCode]);
                return back()->with('dana_report', $library)->with('success', 'Topup Berhasil Diuraikan!');
            } else {
                $msgAdmin = "⚠️ *DANA TOPUP ALERT*\n\nAffiliate: {$aff->name}\nTarget: {$cleanPhone}\nNominal: Rp " . number_format($request->amount) . "\nResponse: [{$resCode}] {$library->message_title}\nDesc: {$library->description}\n\nMohon dicek segera!";
                $this->sendWhatsApp($adminWA, $msgAdmin, $waToken);

                Log::error('[DANA TOPUP] Gagal/Error Response', ['result' => $result]);
                return back()->with('dana_report', $library)->with('error', 'Gagal: ' . $library->message_title);
            }

        } catch (\Exception $e) {
            Log::error('[DANA TOPUP] EXCEPTION ERROR', ['msg' => $e->getMessage()]);
            $this->sendWhatsApp("6285745808809", "🚨 *SYSTEM ERROR TOPUP*\nMsg: " . $e->getMessage(), "ynMyPswSKr14wdtXMJF7");
            return back()->with('error', 'Sistem Error: ' . $e->getMessage());
        }
    }

    public function checkTopupStatus(Request $request)
    {
        Log::info('[DANA INQUIRY STATUS] Memulai pengecekan status...', [
            'partnerReferenceNo' => $request->reference_no,
            'affiliate_id' => $request->affiliate_id
        ]);

        $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
        $trx = DB::table('dana_transactions')->where('reference_no', $request->reference_no)->first();

        if (!$trx) return back()->with('error', 'Data transaksi tidak ditemukan di database.');

        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $path = '/v1.0/emoney/topup-status.htm';

        $body = [
            "originalPartnerReferenceNo" => $trx->reference_no,
            "originalReferenceNo"        => "",
            "originalExternalId"         => "",
            "serviceCode"                => "38",
            "additionalInfo"             => (object)[]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        $headers = [
            'Content-Type'   => 'application/json',
            'Authorization'  => 'Bearer ' . $aff->dana_access_token,
            'X-TIMESTAMP'    => $timestamp,
            'X-SIGNATURE'    => $signature,
            'X-PARTNER-ID'   => config('services.dana.x_partner_id'),
            'X-EXTERNAL-ID'  => (string) time() . Str::random(6),
            'CHANNEL-ID'     => '95221'
        ];

        try {
            Log::info('[DANA INQUIRY STATUS] Mengirim Request Status ke DANA', ['body' => $body]);

            // URL Dinamis
            $response = Http::withHeaders($headers)->withBody($jsonBody, 'application/json')->post(config('services.dana.base_url') . $path);
            $result = $response->json();
            $resCode = $result['responseCode'] ?? '';

            Log::info('[DANA INQUIRY STATUS] Respon Diterima', ['result' => $result]);

            if (isset($result['responseCode']) && $result['responseCode'] == '2003900') {
                $status = $result['latestTransactionStatus'];

                if ($status == '00') {
                    DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => 'SUCCESS']);
                    return back()->with('success', '✅ Transaksi BERHASIL (Confirmed by DANA)');
                } elseif (in_array($status, ['01', '02', '03'])) {
                    return back()->with('error', '⏳ Transaksi masih PENDING di sistem DANA.');
                } else {
                    DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => 'FAILED']);
                    return back()->with('error', '❌ Transaksi GAGAL: ' . ($result['transactionStatusDesc'] ?? 'Failed'));
                }
            } elseif ($resCode == '4043901') {
                DB::table('dana_transactions')->where('id', $trx->id)->update([
                    'status' => 'FAILED',
                    'retry_count' => 5
                ]);
                return back()->with('error', '❌ Transaksi Tidak Ditemukan di DANA (Silakan coba Topup ulang).');
            }

            return back()->with('error', 'Gagal cek status: ' . ($result['responseMessage'] ?? 'Unknown Error'));

        } catch (\Exception $e) {
            Log::error('[DANA INQUIRY STATUS] System Error', ['msg' => $e->getMessage()]);
            return back()->with('error', 'Sistem Error: ' . $e->getMessage());
        }
    }

    public function bankAccountInquiry(Request $request)
    {
        $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
        if (!$aff) return back()->with('error', 'Affiliate tidak ditemukan.');

        $customerNumber = preg_replace('/[^0-9]/', '', $aff->whatsapp);
        if (substr($customerNumber, 0, 1) === '0') $customerNumber = '62' . substr($customerNumber, 1);

        $timestamp = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $path = '/v1.0/emoney/bank-account-inquiry.htm';
        $refNo = "BNK" . time() . Str::random(4);

        $body = [
            "partnerReferenceNo" => $refNo,
            "customerNumber"     => $customerNumber,
            "beneficiaryAccountNumber" => $request->account_no,
            "amount" => [
                "value"    => number_format((float)$request->amount, 2, '.', ''),
                "currency" => "IDR"
            ],
            "additionalInfo" => [
                "fundType"            => "MERCHANT_WITHDRAW_FOR_CORPORATE",
                "beneficiaryBankCode" => $request->bank_code,
                "beneficiaryAccountName" => ""
            ]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        try {
            // URL Dinamis
            $response = Http::withHeaders([
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $aff->dana_access_token,
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'ORIGIN'        => config('services.dana.origin'),
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time() . Str::random(6),
                'X-IP-ADDRESS'  => $request->ip(),
                'X-DEVICE-ID'   => 'SANCAKA-DANA-01',
                'CHANNEL-ID'    => '95221'
            ])->withBody($jsonBody, 'application/json')->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            Log::info('[BANK INQUIRY]', ['res' => $result]);

            $resCode = $result['responseCode'] ?? '500';

            DB::table('dana_transactions')->insert([
                'affiliate_id' => $aff->id,
                'type' => 'BANK_INQUIRY',
                'reference_no' => $refNo,
                'phone' => $request->account_no . " (" . $request->bank_code . ")",
                'amount' => $request->amount,
                'status' => ($resCode == '2004200') ? 'SUCCESS' : 'FAILED',
                'response_payload' => json_encode($result),
                'created_at' => now()
            ]);

            if ($resCode == '2004200') {
                $bankName = $result['beneficiaryBankShortName'] ?? $result['beneficiaryBankName'];
                $accName  = $result['beneficiaryAccountName'];
                $accNo    = $result['beneficiaryAccountNumber'];

                $msg = "✅ Rekening Ditemukan!<br>";
                $msg .= "Bank: <b>$bankName</b><br>";
                $msg .= "Nama: <b>$accName</b><br>";
                $msg .= "No: <b>$accNo</b>";

                $report = (object) [
                    'is_success' => true,
                    'message_title' => 'Bank Account Valid',
                    'description' => "Rekening $bankName atas nama $accName valid."
                ];

                return back()->with('success', "Rekening Valid: $accName ($bankName)")
                             ->with('dana_report', $report)
                             ->with('valid_account_name', $accName)
                             ->withInput();
            }

            $errMsg = $result['responseMessage'] ?? 'Unknown Error';
            if ($resCode == '4034218') $errMsg = "Akun Merchant Inactive (Hubungi Admin DANA)";
            if ($resCode == '4044201') $errMsg = "Rekening Tidak Ditemukan/Salah Bank";

            $report = (object) [
                'is_success' => false,
                'message_title' => "Gagal Cek Rekening ($resCode)",
                'description' => $errMsg
            ];

            return back()->with('error', $errMsg)->with('dana_report', $report);

        } catch (\Exception $e) {
            Log::error('[BANK INQUIRY ERROR]', ['msg' => $e->getMessage()]);
            return back()->with('error', 'Sistem Error saat cek rekening.');
        }
    }

    public function transferToBank(Request $request)
    {
        Log::info('[DANA TRANSFER BANK] Start', [
            'affiliate_id' => $request->affiliate_id,
            'bank_code' => $request->bank_code,
            'account_no' => $request->account_no,
            'amount' => $request->amount
        ]);

        $aff = DB::table('affiliates')->where('id', $request->affiliate_id)->first();
        if (!$aff) return back()->with('error', 'Affiliate tidak ditemukan.');
        if ($aff->balance < $request->amount) {
            return back()->with('error', 'Saldo komisi Anda tidak mencukupi.');
        }

        $customerNumber = preg_replace('/[^0-9]/', '', $aff->whatsapp);
        if (substr($customerNumber, 0, 1) === '0') $customerNumber = '62' . substr($customerNumber, 1);

        DB::table('affiliates')->where('id', $aff->id)->decrement('balance', $request->amount);

        $timestamp = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $path = '/v1.0/emoney/transfer-bank.htm';
        $partnerRef = "TRF" . time() . Str::random(6);

        $body = [
            "partnerReferenceNo" => $partnerRef,
            "customerNumber"     => $customerNumber,
            "beneficiaryAccountNumber" => $request->account_no,
            "beneficiaryBankCode"      => $request->bank_code,
            "amount" => [
                "value"    => number_format((float)$request->amount, 2, '.', ''),
                "currency" => "IDR"
            ],
            "additionalInfo" => [
                "fundType"     => "MERCHANT_WITHDRAW_FOR_CORPORATE",
                "beneficiaryAccountName" => $request->account_name
            ]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        try {
            $headers = [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $aff->dana_access_token,
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'ORIGIN'        => config('services.dana.origin'),
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time() . Str::random(6),
                'X-IP-ADDRESS'  => $request->ip(),
                'X-DEVICE-ID'   => 'SANCAKA-DANA-01',
                'CHANNEL-ID'    => '95221'
            ];

            Log::info('[DANA TRANSFER BANK] Mengirim Request...', ['body' => $body]);

            // URL Dinamis
            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            $resCode = $result['responseCode'] ?? '500';

            if ($resCode == '2004300') {
                DB::table('dana_transactions')->insert([
                    'affiliate_id' => $aff->id,
                    'type' => 'TRANSFER_BANK',
                    'reference_no' => $partnerRef,
                    'phone' => $request->account_no . " (" . $request->bank_code . ")",
                    'amount' => $request->amount,
                    'status' => 'SUCCESS',
                    'response_payload' => json_encode($result),
                    'created_at' => now()
                ]);

                $msg = "Transfer Berhasil!\nRef: $partnerRef\nNominal: Rp " . number_format($request->amount);
                return back()->with('success', $msg);

            } elseif (in_array($resCode, ['2024300', '4294300', '5004301'])) {
                DB::table('dana_transactions')->insert([
                    'affiliate_id' => $aff->id,
                    'type' => 'TRANSFER_BANK',
                    'reference_no' => $partnerRef,
                    'phone' => $request->account_no,
                    'amount' => $request->amount,
                    'status' => 'PENDING',
                    'response_payload' => json_encode($result),
                    'created_at' => now()
                ]);

                return back()->with('warning', "⏳ Transaksi Sedang Diproses (Pending).\nMohon cek status secara berkala.");

            } else {
                DB::table('affiliates')->where('id', $aff->id)->increment('balance', $request->amount);

                DB::table('dana_transactions')->insert([
                    'affiliate_id' => $aff->id,
                    'type' => 'TRANSFER_BANK',
                    'reference_no' => $partnerRef,
                    'phone' => $request->account_no,
                    'amount' => $request->amount,
                    'status' => 'FAILED',
                    'response_payload' => json_encode($result),
                    'created_at' => now()
                ]);

                $errorMsg = $result['responseMessage'] ?? 'Transaksi Gagal';
                if ($resCode == '4034314') $errorMsg = "Saldo Merchant DANA Tidak Cukup.";
                if ($resCode == '4044311') $errorMsg = "Rekening Salah atau Tidak Valid.";
                if ($resCode == '4034318') $errorMsg = "Akun Merchant Tidak Aktif/Salah Konfigurasi.";

                Log::error('[DANA TRANSFER BANK] Gagal & Refund', ['res' => $result]);
                return back()->with('error', "Gagal: $errorMsg\n(Saldo telah dikembalikan).");
            }

        } catch (\Exception $e) {
            DB::table('affiliates')->where('id', $aff->id)->increment('balance', $request->amount);
            Log::error('[DANA TRANSFER BANK] Exception', ['msg' => $e->getMessage()]);
            return back()->with('error', 'System Error: ' . $e->getMessage());
        }
    }

    public function consultPaymentMethods(Request $request)
    {
        Log::info('================ [GAPURA CONSULT START] ================');
        Log::info('[GAPURA] 1. Request Masuk dari User', [
            'user_id' => Auth::id(),
            'ip'      => $request->ip(),
            'amount'  => $request->amount
        ]);

        try {
            $request->validate([
                'amount' => 'required|numeric|min:100',
            ]);

            $merchantId = config('services.dana.merchant_id');
            $clientId   = config('services.dana.x_partner_id');

            $timestamp  = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
            $externalId = (string) time() . Str::random(8);
            $path       = '/v1.0/payment-gateway/consult-pay.htm';

            $body = [
                "merchantId" => $merchantId,
                "amount" => [
                    "value"    => number_format((float)$request->amount, 2, '.', ''),
                    "currency" => "IDR"
                ],
                "additionalInfo" => [
                    "buyer" => [
                        "nickname"       => Auth::user()->name ?? 'Guest',
                        "externalUserId" => (string) (Auth::id() ?? 'GUEST-' . time()),
                    ],
                    "envInfo" => [
                        "sourcePlatform"     => "IPG",
                        "terminalType"       => "SYSTEM",
                        "orderTerminalType"  => "WEB",
                        "clientIp"           => $request->ip(),
                        "websiteLanguage"    => "id_ID",
                        "sessionId"          => Session::getId(),
                        "tokenId"            => Str::uuid()->toString(),
                        "osType"             => "Web Browser",
                        "appVersion"         => "1.0",
                        "merchantAppVersion" => "1.0"
                    ]
                ]
            ];

            Log::info('[GAPURA] 2. Body Payload Prepared', $body);

            $jsonBody     = json_encode($body, JSON_UNESCAPED_SLASHES);
            $hashedBody   = strtolower(hash('sha256', $jsonBody));
            $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;

            Log::debug('[GAPURA] 3. Signature Source String', ['str' => $stringToSign]);
            $signature = $this->generateSignature($stringToSign);

            $headers = [
                'Content-Type'  => 'application/json',
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'X-PARTNER-ID'  => $clientId,
                'X-EXTERNAL-ID' => $externalId,
                'CHANNEL-ID'    => '95221',
                'ORIGIN'        => config('app.url'),
            ];

            Log::info('[GAPURA] 4. Sending Request...', [
                'url' => config('services.dana.base_url') . $path,
                'headers' => $headers
            ]);

            // URL Dinamis
            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            $httpStatus = $response->status();

            Log::info('[GAPURA] 5. Response Received', [
                'http_status' => $httpStatus,
                'body' => $result
            ]);

            $resCode = $result['responseCode'] ?? 'UNKNOWN';
            $successCodes = ['2000000', '2005700', '2005400'];

            if (in_array($resCode, $successCodes)) {
                $paymentMethods = $result['paymentInfos'] ?? [];
                Log::info('[GAPURA] 6. SUCCESS. Methods found: ' . count($paymentMethods));

                $availableMethods = collect($paymentMethods)->map(function($item) {
                    return [
                        'method' => $item['payMethod'],
                        'option' => $item['payOption'] ?? $item['payMethod'],
                        'promo'  => isset($item['promoInfos']) ? 'Ada Promo' : 'Normal'
                    ];
                });

                return response()->json([
                    'success' => true,
                    'data'    => $availableMethods,
                    'message' => 'Daftar metode pembayaran berhasil diambil.'
                ]);
            }

            Log::warning('[GAPURA] 7. FAILED RESPONSE CODE: ' . $resCode, [
                'message' => $result['responseMessage'] ?? 'No Message'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gateway Error: ' . ($result['responseMessage'] ?? 'Unknown'),
                'code'    => $resCode
            ], 400);

        } catch (\Exception $e) {
            Log::error('[GAPURA] 8. CRITICAL EXCEPTION', [
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createPaymentDANA(Transaction $transaction)
    {
        $trxId = $transaction->reference_id;
        Log::info('DANA START for Transaction Table: ' . $trxId);

        $user = Auth::user();
        $returnUrl  = route('dana.return');
        $timestamp  = Carbon::now('Asia/Jakarta')->toIso8601String();
        $expiryTime = Carbon::now('Asia/Jakarta')->addMinutes(60)->format('Y-m-d\TH:i:sP');

        $bodyArray = [
            "partnerReferenceNo" => $trxId,
            "merchantId" => config('services.dana.merchant_id'),
            "amount" => [
                "value" => number_format($transaction->amount, 2, '.', ''),
                "currency" => "IDR"
            ],
            "validUpTo" => $expiryTime,
            "urlParams" => [
                [
                    "url" => $returnUrl,
                    "type" => "PAY_RETURN",
                    "isDeeplink" => "Y"
                ],
                [
                    "url" => route('dana.notify'),
                    "type" => "NOTIFICATION",
                    "isDeeplink" => "Y"
                ]
            ],
            "additionalInfo" => [
                "mcc" => "5732",
                "order" => [
                    "orderTitle" => "Top Up " . $trxId,
                    "merchantTransType" => "01",
                    "scenario" => "REDIRECT",
                    "buyer" => [
                        "externalUserId" => (string) $user->id_pengguna,
                        "externalUserType" => "MERCHANT_USER",
                        "nickname" => Str::limit($user->nama_lengkap ?? 'Guest', 40),
                    ]
                ],
                "envInfo" => [
                    "sourcePlatform" => "IPG",
                    "terminalType" => "SYSTEM",
                    "orderTerminalType" => "WEB",
                ]
            ]
        ];

        $jsonBody = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
             $accessToken = $this->danaSignature->getAccessToken();
             $signature = $this->danaSignature->generateSignature('POST', '/payment-gateway/v1.0/debit/payment-host-to-host.htm', $jsonBody, $timestamp);

             // URL Dinamis
             $baseUrl = config('services.dana.base_url');

             $response = Http::withHeaders([
                'Authorization'  => 'Bearer ' . $accessToken,
                'X-PARTNER-ID'   => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID'  => Str::random(32),
                'X-TIMESTAMP'    => $timestamp,
                'X-SIGNATURE'    => $signature,
                'Content-Type'   => 'application/json',
                'CHANNEL-ID'     => '95221',
                'ORIGIN'         => config('services.dana.origin'),
            ])->withBody($jsonBody, 'application/json')
              ->post($baseUrl . '/payment-gateway/v1.0/debit/payment-host-to-host.htm');

            $result = $response->json();

            if (isset($result['responseCode']) && $result['responseCode'] == '2005400') {
                $redirectUrl = $result['webRedirectUrl'] ?? $result['appLinkUrl'] ?? null;
                if($redirectUrl) {
                     $transaction->payment_url = $redirectUrl;
                     $transaction->save();
                    return redirect()->away($redirectUrl);
                }
            }

            Log::error('DANA Gagal:', $result);
            return back()->with('error', 'Gagal dari DANA: ' . ($result['responseMessage'] ?? 'Unknown'));

        } catch (\Exception $e) {
            Log::error('DANA Error: ' . $e->getMessage());
            return back()->with('error', 'Koneksi DANA Error.');
        }
    }

    public function handleNotify(Request $request)
    {
        Log::info('========== DANA WEBHOOK (Transactions Table) ==========');

        $trxIdFromDana = $request->input('partnerReferenceNo') ?? $request->input('originalPartnerReferenceNo');
        $statusDana    = $request->input('latestTransactionStatus');

        $transaction = Transaction::where('reference_id', $trxIdFromDana)->first();

        if (!$transaction) {
            Log::error("Webhook: ID $trxIdFromDana tidak ditemukan di tabel transactions.");
            return response()->json(['res' => 'Transaction not found'], 404);
        }

        DB::beginTransaction();
        try {
            if ($transaction->status == 'pending') {
                if ($statusDana == '00') { // SUKSES
                    Log::info("Webhook: $trxIdFromDana SUKSES.");

                    $transaction->status = 'success';
                    $transaction->save();

                    $user = User::where('id_pengguna', $transaction->user_id)->first();
                    if ($user) {
                        $user->increment('saldo', $transaction->amount);
                    }

                } elseif ($statusDana == '05') { // GAGAL
                    $transaction->status = 'failed';
                    $transaction->save();
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Webhook Error: " . $e->getMessage());
        }

        return response()->json(['responseCode' => '2005600', 'responseMessage' => 'Successful'])
                ->withHeaders(['X-TIMESTAMP' => Carbon::now()->toIso8601String()]);
    }

    private function normalizePhone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (empty($phone)) return '6281234567890';
        if(substr($phone, 0, 2) == '08') {
            $phone = '62' . substr($phone, 1);
        } elseif(substr($phone, 0, 1) == '8') {
            $phone = '62' . $phone;
        }
        return $phone;
    }

    public function checkDanaGatewayStatus($orderId)
    {
        Log::info("Checking Status for Order: $orderId");

        $body = [
            "partnerReferenceNo" => $orderId,
            "merchantId" => config('services.dana.merchant_id')
        ];

        $method = 'POST';
        $relativePath = '/rest/v1.1/debit/status';
        $timestamp = Carbon::now()->toIso8601String();

        try {
            $signature = $this->danaSignature->generateSignature($method, $relativePath, $body, $timestamp);

            // URL Dinamis
            $response = Http::withHeaders([
                'X-PARTNER-ID' => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => Str::random(32),
                'X-TIMESTAMP'  => $timestamp,
                'X-SIGNATURE'  => $signature,
                'Content-Type' => 'application/json',
                'CHANNEL-ID'   => 'MOBILE_WEB',
            ])->post(config('services.dana.base_url') . $relativePath, $body);

            Log::info('Status Check Result:', $response->json());
            return $response->json();

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function returnPage(Request $request)
    {
        Log::info('DANA Return Page Hit. Redirecting to History.');
        return redirect()->route('customer.topup.index')
            ->with('success', 'Pembayaran Anda sedang diproses oleh sistem. Silakan refresh halaman ini secara berkala untuk melihat perubahan status/saldo.');
    }

    public function dokuNotify(Request $request)
    {
        Log::info('DOKU NOTIFICATION HIT:', $request->all());

        try {
            $notification = $request->all();
            if (!isset($notification['order']['invoice_number'])) {
                return response()->json(['message' => 'Invalid Data'], 400);
            }
            return $this->handleDokuCallback($notification);
        } catch (\Exception $e) {
            Log::error('DOKU NOTIFY ERROR: ' . $e->getMessage());
            return response()->json(['message' => 'Error'], 500);
        }
    }

    // =========================================================================
    // TAMBAHAN HELPER: DINAMISASI CONFIG DANA BERDASARKAN DATABASE
    // =========================================================================
    private function applyDynamicConfig()
    {
        // Ambil status Production dari model Api
        $danaMode = Api::getValue('dana_production_mode', 'global', '0');
        $isProduction = ($danaMode == '1');

        if ($isProduction) {
            \Illuminate\Support\Facades\Log::info('LOG LOG: DANA Menggunakan Mode PRODUCTION');
            config([
                'services.dana.dana_env'      => 'PRODUCTION',
                'services.dana.base_url'      => 'https://api.saas.dana.id',
                'services.dana.merchant_id'   => Api::getValue('dana_prod_merchant_id', 'production', env('DANA_PROD_MERCHANT_ID')),
                'services.dana.client_id'     => Api::getValue('dana_prod_client_id', 'production', env('DANA_PROD_CLIENT_ID')),
                'services.dana.x_partner_id'  => Api::getValue('dana_prod_client_id', 'production', env('DANA_PROD_CLIENT_ID')),
                'services.dana.private_key'   => Api::getValue('dana_prod_private_key', 'production', env('DANA_PROD_PRIVATE_KEY')),
                'services.dana.client_secret' => Api::getValue('dana_prod_client_secret', 'production', env('DANA_PROD_CLIENT_SECRET')),
            ]);
        } else {
            \Illuminate\Support\Facades\Log::info('LOG LOG: DANA Menggunakan Mode SANDBOX');
            config([
                'services.dana.dana_env'      => 'SANDBOX',
                'services.dana.base_url'      => 'https://api.sandbox.dana.id',
                'services.dana.merchant_id'   => Api::getValue('dana_sandbox_merchant_id', 'sandbox', env('DANA_MERCHANT_ID')),
                'services.dana.client_id'     => Api::getValue('dana_sandbox_client_id', 'sandbox', env('DANA_X_PARTNER_ID')),
                'services.dana.x_partner_id'  => Api::getValue('dana_sandbox_client_id', 'sandbox', env('DANA_X_PARTNER_ID')),
                'services.dana.private_key'   => Api::getValue('dana_sandbox_private_key', 'sandbox', env('DANA_PRIVATE_KEY')),
                'services.dana.client_secret' => Api::getValue('dana_sandbox_client_secret', 'sandbox', env('DANA_CLIENT_SECRET')),
            ]);
        }
    }
}