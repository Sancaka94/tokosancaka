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

// --- [IMPORT DANA SDK] ---
use Dana\Widget\v1\Model\WidgetPaymentRequest;
use Dana\Widget\v1\Model\Money;
use Dana\Widget\v1\Model\UrlParam;
use Dana\Widget\v1\Model\WidgetPaymentRequestAdditionalInfo;
use Dana\Widget\v1\Model\EnvInfo;
use Dana\Widget\v1\Model\Order as DanaOrder;
use Dana\Configuration;
use Dana\Env;
use Dana\Widget\v1\Api\WidgetApi;

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

            // 1.5 LOGIKA AUTO-DEBIT (POTONG SALDO DANA AKUN TERHUBUNG)
            elseif ($validated['payment_method'] === 'DANA_BINDING') {

                // Pastikan akun benar-benar sudah bind (Ambil dari $user Auth langsung)
                if (empty($user->dana_access_token)) {
                    throw new \Exception("Akun DANA Anda belum terhubung. Silakan hubungkan terlebih dahulu di Profil / Pengaturan.");
                }

                Log::info("Memproses Potong Saldo DANA untuk " . $invoiceNumber . " (User ID: " . $user->id_pengguna . ")");

                $transaction = Transaction::create([
                    'user_id'        => $user->id_pengguna,
                    'reference_id'   => $invoiceNumber,
                    'amount'         => $amount,
                    'type'           => 'topup',
                    'status'         => 'pending',
                    'payment_method' => 'DANA_BINDING',
                    'description'    => 'Top up saldo via Saldo DANA Terhubung (Auto Debit)',
                ]);

                DB::commit();
                // Passing $user langsung karena token ada di situ
                return $this->createPaymentDanaBinding($transaction, $user);
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

    public function startBinding(Request $request)
    {
        Log::info('[BINDING] Memulai proses redirect ke DANA Portal...');

        $user = \Illuminate\Support\Facades\Auth::user();
        $affiliateId = $user->id_pengguna;

        // 1. TENTUKAN IDENTITAS TENANT/FOLDER
        // Ganti 'percetakan' sesuai dengan dynamic path tenant Anda jika diperlukan
        $tenantPath = 'percetakan';

        $queryParams = [
            'partnerId'   => config('services.dana.x_partner_id'),
            'merchantId'  => config('services.dana.merchant_id'),
            'timestamp'   => now('Asia/Jakarta')->toIso8601String(),
            'externalId'  => 'BIND-' . $affiliateId . '-' . time(),
            'channelId'   => 'DANAID',

            // 2. HARDCODE SESUAI PORTAL DANA (JANGAN DIUBAH)
            'redirectUrl' => 'https://apps.tokosancaka.com/dana/callback',

            // 3. TITIPKAN PATH TENANT DI STATE (Maksimal 32 Karakter)
            'state'       => 'ID-' . $affiliateId . '-' . $tenantPath,
            'scopes'      => 'QUERY_BALANCE,MINI_DANA,DEFAULT_BASIC_PROFILE',
        ];

        $baseUrl = config('services.dana.dana_env') === 'PRODUCTION' ? 'https://m.dana.id' : 'https://m.sandbox.dana.id';
        return redirect($baseUrl . "/d/portal/oauth?" . http_build_query($queryParams));
    }

   public function handleCallback(Request $request)
{
    Log::info('[DANA CALLBACK] SNAP Apply Token Start...', $request->all());

    $authCode = $request->input('auth_code') ?? $request->input('authCode');
    $stateRaw = $request->input('state');

    $parts = explode('-', $stateRaw);
    $affiliateId = $parts[1] ?? null;

    if (!$authCode || !$affiliateId) {
        return redirect()->route('customer.topup.index')->with('error', 'Auth Code/State Invalid.');
    }

    try {
        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $clientId = config('services.dana.client_id');

        // --- 1. GENERATE SNAP SIGNATURE ---
        // Sesuai Dokumen: stringToSign = client_ID + “|” + X-TIMESTAMP
        $stringToSign = $clientId . "|" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        // --- 2. PREPARE SNAP BODY ---
        $body = [
            "grantType"    => "AUTHORIZATION_CODE",
            "authCode"     => $authCode,
            "refreshToken" => "",
            "additionalInfo" => (object)[]
        ];

        $path = '/v1.0/access-token/b2b2c.htm';
        $fullUrl = config('services.dana.base_url') . $path;

        // --- 3. SEND REQUEST SESUAI SNAP HEADERS ---
        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'X-TIMESTAMP'   => $timestamp,
            'X-CLIENT-KEY'  => $clientId, // Wajib di SNAP
            'X-SIGNATURE'   => $signature,
            'X-PARTNER-ID'  => $clientId, // Biasanya sama dengan Client ID
        ])->post($fullUrl, $body);

        $result = $response->json();

        // SNAP Success Code untuk Apply Token adalah 2007400 (Sesuai Dokumen Anda)
        if (isset($result['responseCode']) && $result['responseCode'] == '2007400') {

            // Simpan ke DB Tenant
            DB::table('Pengguna')->where('id_pengguna', $affiliateId)->update([
                'dana_access_token' => $result['accessToken'],
                'dana_auth_code'    => $authCode,
                'updated_at'        => now()
            ]);

            // Sync ke DB Pusat (mysql_second)
            try {
                DB::connection('mysql_second')->table('Pengguna')->where('id_pengguna', $affiliateId)->update([
                    'dana_access_token' => $result['accessToken'],
                    'dana_auth_code'    => $authCode,
                    'updated_at'        => now()
                ]);
            } catch (\Exception $e) {
                Log::error('[DANA SYNC] Gagal ke DB Pusat: ' . $e->getMessage());
            }

            Log::info("[DANA CALLBACK] SNAP Berhasil untuk User: $affiliateId");
            return redirect()->route('customer.topup.index')->with('success', '✅ Akun DANA Berhasil Terhubung!');
        }

        // JIKA GAGAL
        Log::error('[DANA SNAP ERROR]', $result);
        return redirect()->route('customer.topup.index')->with('error', 'DANA Reject: ' . ($result['responseMessage'] ?? 'Unknown Error'));

    } catch (\Exception $e) {
        Log::error('[DANA CALLBACK] System Error:', ['msg' => $e->getMessage()]);
        return redirect()->route('customer.topup.index')->with('error', 'Terjadi kesalahan sistem.');
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
        \Illuminate\Support\Facades\Log::debug('=== [DANA DEBUG LOG] START GENERATE SIGNATURE ===');
        \Illuminate\Support\Facades\Log::debug('[DANA DEBUG LOG] 1. String To Sign (Mentah):', ['string' => $stringToSign]);

        // 1. Ambil Key dari Config (hasil dinamisasi)
        $rawKey = config('services.dana.private_key');

        if (empty($rawKey)) {
            \Illuminate\Support\Facades\Log::error('[DANA DEBUG LOG] ERROR: Private Key dari config KOSONG!');
            throw new \Exception("Private Key kosong. Pastikan DANA_PRIVATE_KEY di .env atau Pengaturan Database sudah terisi.");
        }

        \Illuminate\Support\Facades\Log::debug('[DANA DEBUG LOG] 2. Raw Key Berhasil Diambil', ['panjang_karakter' => strlen($rawKey)]);

        // 2. Bersihkan Key dari header/footer/spasi/newline/tanda kutip yang berantakan
        $cleanKey = str_replace(
            ["-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----", "\r", "\n", " ", "\"", "'"],
            "",
            $rawKey
        );
        \Illuminate\Support\Facades\Log::debug('[DANA DEBUG LOG] 3. Clean Key Berhasil Dibuat', ['panjang_karakter' => strlen($cleanKey)]);

        // 3. Format ulang menjadi PEM standar (64 karakter per baris)
        $formattedKey = "-----BEGIN PRIVATE KEY-----\n" .
                        wordwrap($cleanKey, 64, "\n", true) .
                        "\n-----END PRIVATE KEY-----";

        // 4. Validasi Key ke OpenSSL Resource
        $privateKeyResource = openssl_pkey_get_private($formattedKey);
        if (!$privateKeyResource) {
            \Illuminate\Support\Facades\Log::error('[DANA DEBUG LOG] ERROR: Gagal load OpenSSL Resource!', [
                'preview_key' => substr($formattedKey, 0, 50) . '...' // Hanya log sebagian agar tidak bocor full di log
            ]);
            throw new \Exception("Format Private Key salah atau korup. Tidak dapat diproses oleh OpenSSL.");
        }

        \Illuminate\Support\Facades\Log::debug('[DANA DEBUG LOG] 4. OpenSSL Resource Valid. Memulai proses SHA256...');

        // 5. Sign Data (SHA256)
        $binarySignature = "";
        $isSignSuccess = openssl_sign($stringToSign, $binarySignature, $privateKeyResource, OPENSSL_ALGO_SHA256);

        if (!$isSignSuccess) {
            $sslError = openssl_error_string();
            \Illuminate\Support\Facades\Log::error('[DANA DEBUG LOG] ERROR: OpenSSL Sign Failed.', ['ssl_error' => $sslError]);
            throw new \Exception("OpenSSL Sign Failed. Detail: " . $sslError);
        }

        $finalBase64Signature = base64_encode($binarySignature);

        \Illuminate\Support\Facades\Log::debug('[DANA DEBUG LOG] 5. Signature Berhasil Dibuat!', [
            'signature_result' => $finalBase64Signature
        ]);
        \Illuminate\Support\Facades\Log::debug('=== [DANA DEBUG LOG] END GENERATE SIGNATURE ===');

        return $finalBase64Signature;
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
            'X-IP-ADDRESS'  => $request->ip() ?? '82.25.62.13',
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
        // PERBAIKAN: Gunakan tabel Pengguna dan id_pengguna
        $aff = DB::table('Pengguna')->where('id_pengguna', $request->affiliate_id)->first();
        if (!$aff) return back()->with('error', 'Pengguna tidak ditemukan.');

        // PERBAIKAN: Ganti whatsapp menjadi no_wa
        $customerNumber = preg_replace('/[^0-9]/', '', $aff->no_wa);
        if (substr($customerNumber, 0, 1) === '0') $customerNumber = '62' . substr($customerNumber, 1);

        $timestamp = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $path = '/v1.0/emoney/bank-account-inquiry.htm';
        $refNo = "BNK" . time() . Str::random(4);

        // AMBIL DATA BANK DARI DATABASE UNTUK DISPLAY NAMA BANK
        $cekBank = DB::table('dana_bank_codes')->where('bank_code', $request->bank_code)->first();
        $readableBank = $cekBank ? $cekBank->bank_name : $request->bank_code;

        // PAYLOAD
        $body = [
            "partnerReferenceNo" => $refNo,
            "customerNumber"     => $customerNumber,
            "beneficiaryAccountNumber" => $request->account_no,
            "amount" => [
                "value"    => number_format((float)$request->amount, 2, '.', ''),
                "currency" => "IDR"
            ],
            "additionalInfo" => [
                "fundType"               => "MERCHANT_WITHDRAW_FOR_CORPORATE",
                "beneficiaryBankCode"    => (string) $request->bank_code,
                "beneficiaryAccountName" => "",

            ]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        try {
            $accessTokenB2B = $this->danaSignature->getAccessToken();

            Log::info('[BANK INQUIRY] Sending Request to DANA', ['body' => $body]);

            // Siapkan headers standar
            $headers = [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $accessTokenB2B,
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'ORIGIN'        => config('services.dana.origin'),
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time() . Str::random(6),
                'X-IP-ADDRESS'  => $request->ip(),
                'X-DEVICE-ID'   => 'SANCAKA-DANA-01',
                'CHANNEL-ID'    => '95221'
            ];

            // HANYA MASUKKAN Authorization-Customer JIKA TOKENNYA ADA/TIDAK KOSONG
            if (!empty($aff->dana_access_token)) {
                $headers['Authorization-Customer'] = 'Bearer ' . $aff->dana_access_token;
            }

            // URL Dinamis
            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            Log::info('[BANK INQUIRY]', ['res' => $result]);

            $resCode = $result['responseCode'] ?? '500';

            DB::table('dana_transactions')->insert([
                'affiliate_id' => $aff->id_pengguna,
                'type' => 'BANK_INQUIRY',
                'reference_no' => $refNo,
                // Kolom phone akan mencatat nama bank secara rapi: "12345678 (Bank BCA)"
                'phone' => $request->account_no . " (" . $readableBank . ")",
                'amount' => $request->amount,
                'status' => ($resCode == '2004200') ? 'SUCCESS' : 'FAILED',
                'response_payload' => json_encode($result),
                'created_at' => now()
            ]);

            if ($resCode == '2004200') {
                $bankName = $result['beneficiaryBankShortName'] ?? $result['beneficiaryBankName'] ?? $readableBank;
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
                             ->with('valid_bank_name', $bankName) // Melempar session untuk UI Transfer
                             ->withInput();
            }

            $errMsg = $result['responseMessage'] ?? 'Unknown Error';
            if ($resCode == '4034218') $errMsg = "Akun Merchant Inactive (Hubungi Admin DANA)";
            if ($resCode == '4044201') $errMsg = "Rekening Tidak Ditemukan/Salah Bank";
            if ($resCode == '4004201') $errMsg = "Format Kode Bank Tidak Valid (".$request->bank_code.")";

            $report = (object) [
                'is_success' => false,
                'message_title' => "Gagal Cek Rekening ($resCode)",
                'description' => $errMsg
            ];

            return back()->with('error', $errMsg)->with('dana_report', $report)->withInput();

        } catch (\Exception $e) {
            Log::error('[BANK INQUIRY ERROR]', ['msg' => $e->getMessage()]);
            return back()->with('error', 'Sistem Error saat cek rekening.')->withInput();
        }
    }

    public function transferToBank(Request $request)
    {
        Log::info('[DANA TRANSFER BANK] Start', [
            'affiliate_id' => $request->affiliate_id,
            'bank_code'    => $request->bank_code,
            'account_no'   => $request->account_no,
            'amount'       => $request->amount
        ]);

        // PERBAIKAN: Gunakan tabel Pengguna dan id_pengguna
        $aff = DB::table('Pengguna')->where('id_pengguna', $request->affiliate_id)->first();
        if (!$aff) return back()->with('error', 'Pengguna tidak ditemukan.');

        // PERBAIKAN: Cek menggunakan kolom saldo
        if ($aff->saldo < $request->amount) {
            return back()->with('error', 'Saldo Anda tidak mencukupi.');
        }

        // PERBAIKAN: Format No WA untuk parameter DANA
        $customerNumber = preg_replace('/[^0-9]/', '', $aff->no_wa);
        if (substr($customerNumber, 0, 1) === '0') $customerNumber = '62' . substr($customerNumber, 1);

        // POTONG SALDO DIAWAL SEBELUM HIT API
        DB::table('Pengguna')->where('id_pengguna', $aff->id_pengguna)->decrement('saldo', $request->amount);

        $timestamp  = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $path       = '/v1.0/emoney/transfer-bank.htm';
        // $partnerRef = "TRF" . time() . Str::random(6);
        $partnerRef = "TEST_INCONSISTENT_999";

        // AMBIL DATA BANK DARI DATABASE
        $cekBank = DB::table('dana_bank_codes')->where('bank_code', $request->bank_code)->first();
        $readableBank = $cekBank ? $cekBank->bank_name : $request->bank_code;

        // PAYLOAD SESUAI DOKUMENTASI DANA TRANSFER TO BANK
        $body = [
            "partnerReferenceNo"       => $partnerRef,
            "customerNumber"           => $customerNumber,
            "beneficiaryAccountNumber" => (string) $request->account_no,
            // DI TRANSFER API, BENEFICIARY BANK CODE ADA DI ROOT, BUKAN DI ADDITIONAL INFO
            "beneficiaryBankCode"      => (string) $request->bank_code,
            "amount" => [
                "value"    => number_format((float)$request->amount, 2, '.', ''),
                "currency" => "IDR"
            ],
            "additionalInfo" => [
                "fundType"               => "MERCHANT_WITHDRAW_FOR_CORPORATE",
                "beneficiaryAccountName" => (string) $request->account_name,
                // "needNotify"             => true
            ]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
        $signature = $this->generateSignature($stringToSign);


        try {

        $accessTokenB2B = $this->danaSignature->getAccessToken();

            $headers = [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $accessTokenB2B,
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

            // EKSEKUSI API
            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            $resCode = $result['responseCode'] ?? '500';

            // KONDISI 1: SUCCESS BERHASIL INSTAN
            if ($resCode == '2004300') {
                DB::table('dana_transactions')->insert([
                    'affiliate_id'     => $aff->id_pengguna,
                    'type'             => 'TRANSFER_BANK',
                    'reference_no'     => $partnerRef,
                    'phone'            => $request->account_no . " (" . $readableBank . ")",
                    'amount'           => $request->amount,
                    'status'           => 'SUCCESS',
                    'response_payload' => json_encode($result),
                    'created_at'       => now()
                ]);

                $msg = "Transfer Berhasil!\nRef: $partnerRef\nNominal: Rp " . number_format($request->amount, 0, ',', '.');
                return back()->with('success', $msg);

            // KONDISI 2: TRANSAKSI PENDING (DELAY DARI BANK / LIMIT REQUEST)
            } elseif (in_array($resCode, ['2024300', '4294300', '5004301'])) {
                DB::table('dana_transactions')->insert([
                    'affiliate_id'     => $aff->id_pengguna,
                    'type'             => 'TRANSFER_BANK',
                    'reference_no'     => $partnerRef,
                    'phone'            => $request->account_no . " (" . $readableBank . ")",
                    'amount'           => $request->amount,
                    'status'           => 'PENDING',
                    'response_payload' => json_encode($result),
                    'created_at'       => now()
                ]);

                return back()->with('warning', "⏳ Transaksi Sedang Diproses (Pending).\nMohon cek riwayat saldo secara berkala.");

            // KONDISI 3: TRANSAKSI GAGAL
            } else {
                // REFUND SALDO JIKA TRANSFER GAGAL
                DB::table('Pengguna')->where('id_pengguna', $aff->id_pengguna)->increment('saldo', $request->amount);

                DB::table('dana_transactions')->insert([
                    'affiliate_id'     => $aff->id_pengguna,
                    'type'             => 'TRANSFER_BANK',
                    'reference_no'     => $partnerRef,
                    'phone'            => $request->account_no . " (" . $readableBank . ")",
                    'amount'           => $request->amount,
                    'status'           => 'FAILED',
                    'response_payload' => json_encode($result),
                    'created_at'       => now()
                ]);

                $errorMsg = $result['responseMessage'] ?? 'Transaksi Gagal';
                if ($resCode == '4034314') $errorMsg = "Saldo Merchant DANA Pusat Tidak Mencukupi.";
                if ($resCode == '4044311') $errorMsg = "Rekening Salah atau Tidak Valid.";
                if ($resCode == '4034318') $errorMsg = "Akun Merchant Tidak Aktif/Salah Konfigurasi.";
                if ($resCode == '4004301') $errorMsg = "Format/Data Pengiriman Tidak Sesuai Standar DANA.";
                if ($resCode == '4044318') $errorMsg = "Inconsistent Request: Referensi transaksi duplikat dengan nominal berbeda.";

                Log::error('[DANA TRANSFER BANK] Gagal & Refund', ['res' => $result]);
                return back()->with('error', "Gagal: $errorMsg\n(Saldo Rp ".number_format($request->amount, 0, ',', '.')." telah dikembalikan).");
            }

        } catch (\Exception $e) {
            // REFUND SALDO JIKA SISTEM/KONEKSI ERROR
            DB::table('Pengguna')->where('id_pengguna', $aff->id_pengguna)->increment('saldo', $request->amount);

            Log::error('[DANA TRANSFER BANK] Exception', ['msg' => $e->getMessage()]);
            return back()->with('error', 'Sistem Error saat eksekusi: ' . $e->getMessage() . "\n(Saldo telah dikembalikan).");
        }
    }

    public function consultPaymentMethods(Request $request)
    {
        \Illuminate\Support\Facades\Log::debug('================ [GAPURA DEBUG LOG] CONSULT START ================');
        \Illuminate\Support\Facades\Log::debug('[GAPURA DEBUG LOG] 1. Request Masuk dari User', [
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

            // 1. PERBAIKAN NAMA USER SESUAI TABEL PENGGUNA
            $user = Auth::user();
            $buyerName = $user ? ($user->nama_lengkap ?? 'Guest') : 'Guest';
            $buyerId   = $user ? (string) $user->id_pengguna : 'GUEST-' . time();

            // 2. PERBAIKAN FORMAT IP (CEGAH IPV6 AGAR DANA TIDAK CRASH)
            $clientIp = $request->ip();
            if (filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $clientIp = '82.25.62.13'; // Fallback ke IPv4 lokal jika user memakai IPv6
            }

            $body = [
                "merchantId" => $merchantId,
                "amount" => [
                    "value"    => number_format((float)$request->amount, 2, '.', ''),
                    "currency" => "IDR"
                ],
                "additionalInfo" => [
                    "buyer" => [
                        "nickname"       => $buyerName,
                        "externalUserId" => $buyerId,
                    ],
                    "envInfo" => [
                        "sourcePlatform"     => "IPG",
                        "terminalType"       => "SYSTEM",
                        "orderTerminalType"  => "WEB",
                        "clientIp"           => $clientIp, // Gunakan IP yang sudah disanitasi
                        "websiteLanguage"    => "id_ID",
                        "sessionId"          => Session::getId(),
                        "tokenId"            => Str::uuid()->toString(),
                        "osType"             => "Web Browser",
                        "appVersion"         => "1.0",
                        "merchantAppVersion" => "1.0"
                    ]
                ]
            ];

            \Illuminate\Support\Facades\Log::debug('[GAPURA DEBUG LOG] 2. Body Payload Array:', $body);

            $jsonBody     = json_encode($body, JSON_UNESCAPED_SLASHES);
            \Illuminate\Support\Facades\Log::debug('[GAPURA DEBUG LOG] 2a. Body Payload JSON (Mentah untuk di-Hash):', ['json' => $jsonBody]);

            $hashedBody   = strtolower(hash('sha256', $jsonBody));
            \Illuminate\Support\Facades\Log::debug('[GAPURA DEBUG LOG] 2b. Hashed Body (SHA-256):', ['hash' => $hashedBody]);

            $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
            \Illuminate\Support\Facades\Log::debug('[GAPURA DEBUG LOG] 3. String to Sign GAPURA:', ['str' => $stringToSign]);

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

            \Illuminate\Support\Facades\Log::debug('[GAPURA DEBUG LOG] 4. Headers Request Disiapkan:', $headers);
            \Illuminate\Support\Facades\Log::debug('[GAPURA DEBUG LOG] 4a. Target URL:', ['url' => config('services.dana.base_url') . $path]);

            // URL Dinamis
            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            $httpStatus = $response->status();

            \Illuminate\Support\Facades\Log::debug('[GAPURA DEBUG LOG] 5. Response Diterima!', [
                'http_status' => $httpStatus,
                'raw_body' => $response->body(),
                'parsed_json' => $result
            ]);

            $resCode = $result['responseCode'] ?? 'UNKNOWN';
            $successCodes = ['2000000', '2005700', '2005400'];

            if (in_array($resCode, $successCodes)) {
                $paymentMethods = $result['paymentInfos'] ?? [];
                \Illuminate\Support\Facades\Log::debug('[GAPURA DEBUG LOG] 6. SUCCESS. Total Methods found: ' . count($paymentMethods));

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

            \Illuminate\Support\Facades\Log::error('[GAPURA DEBUG LOG] 7. FAILED RESPONSE CODE: ' . $resCode, [
                'message' => $result['responseMessage'] ?? 'No Message',
                'full_result' => $result
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gateway Error: ' . ($result['responseMessage'] ?? 'Unknown'),
                'code'    => $resCode
            ], 400);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::critical('[GAPURA DEBUG LOG] 8. CRITICAL EXCEPTION!', [
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
                    "isDeeplink" => "N"
                ],
                [
                    "url" => route('dana.notify'),
                    "type" => "NOTIFICATION",
                    "isDeeplink" => "N"
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
                'services.dana.public_key'    => Api::getValue('dana_prod_public_key', 'production'), // <-- TAMBAHKAN BARIS INI
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
                'services.dana.public_key'    => Api::getValue('dana_sandbox_public_key', 'sandbox'), // <-- TAMBAHKAN BARIS INI
                'services.dana.client_secret' => Api::getValue('dana_sandbox_client_secret', 'sandbox', env('DANA_CLIENT_SECRET')),
            ]);
        }
    }

    /**
     * EKSEKUSI POTONG SALDO DANA (AUTO DEBIT / DIRECT DEBIT)
     */
    public function createPaymentDanaBinding(Transaction $transaction, $userAccount)
    {
        $trxId = $transaction->reference_id;
        Log::info('[DANA BINDING] Memulai Auto-Debit untuk Top Up: ' . $trxId);

        $timestamp  = Carbon::now('Asia/Jakarta')->toIso8601String();
        $path = '/v1.0/debit/payment.htm'; // Endpoint khusus direct debit

        // Payload Direct Debit
        $body = [
            "partnerReferenceNo" => $trxId,
            "merchantId" => config('services.dana.merchant_id'),
            "amount" => [
                "value" => number_format($transaction->amount, 2, '.', ''),
                "currency" => "IDR"
            ],
            "chargeToken" => "", // Dikosongkan karena pakai Authorization-Customer (Token OAuth)
            "additionalInfo" => (object)[]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            $accessTokenB2B = $this->danaSignature->getAccessToken();
            $signature = $this->danaSignature->generateSignature('POST', $path, $jsonBody, $timestamp);
            $baseUrl = config('services.dana.base_url');

            // Eksekusi Potong Saldo API
            $response = Http::withHeaders([
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $accessTokenB2B, // Token B2B Sancaka
                // PENTING: Ambil token dari user account (Tabel Pengguna)
                'Authorization-Customer' => 'Bearer ' . $userAccount->dana_access_token,
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'ORIGIN'        => config('services.dana.origin'),
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time() . Str::random(6),
                'X-DEVICE-ID'   => 'SANCAKA-WEB-POS',
                'CHANNEL-ID'    => '95221'
            ])
            ->withBody($jsonBody, 'application/json')
            ->post($baseUrl . $path);

            $result = $response->json();
            Log::info('[DANA BINDING] Respon Potong Saldo: ', $result);

            // Cek Jika Potong Saldo BERHASIL (Kode 2000000)
            if (isset($result['responseCode']) && $result['responseCode'] == '2000000') {

                // 1. Update Transaksi Sancaka
                $transaction->update([
                    'status' => 'success',
                    'payment_status' => 'paid',
                    'note' => "[AUTO-DEBIT] Saldo DANA berhasil dipotong otomatis."
                ]);

                // 2. Tambah Saldo Utama User
                $userAccount->increment('saldo', $transaction->amount);

                // 3. Notifikasi
                event(new SaldoUpdated($userAccount, $transaction->amount, $userAccount->saldo, 'Top up Saldo DANA Anda berhasil.'));

                return redirect()->route('customer.topup.index')
                    ->with('success', '🎉 Pembayaran Berhasil! Saldo DANA Anda telah terpotong secara otomatis.');
            }

            // Cek Jika butuh verifikasi tambahan/OTP dari DANA (Kode 2005400)
            elseif (isset($result['responseCode']) && $result['responseCode'] == '2005400' && !empty($result['webRedirectUrl'])) {
                $transaction->update(['payment_url' => $result['webRedirectUrl']]);
                return redirect()->away($result['webRedirectUrl']);
            }

            // Jika Gagal (Misal: Saldo DANA kurang)
            else {
                $transaction->update(['status' => 'failed']);
                $pesanGagal = $result['responseMessage'] ?? 'Saldo DANA tidak mencukupi atau Token Kadaluarsa.';
                return back()->with('error', 'Gagal memotong saldo: ' . $pesanGagal);
            }

        } catch (\Exception $e) {
            Log::error('[DANA BINDING] Fatal Error: ' . $e->getMessage());
            return back()->with('error', 'Koneksi ke DANA terputus. Silakan coba lagi.');
        }
    }

    /**
     * =========================================================================
     * FITUR: CEK SALDO DANA USER PENGGUNA (REAL-TIME)
     * =========================================================================
     */
    public function checkMyDanaBalance()
    {
        $user = Auth::user();

        // Pastikan kolom akses token sesuai dengan tabel User/Pengguna Anda
        $accessToken = $user->dana_access_token;

        if (empty($accessToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Akun DANA belum terhubung. Silakan hubungkan terlebih dahulu.'
            ]);
        }

        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $path = '/v1.0/balance-inquiry.htm';
        $body = [
            'partnerReferenceNo' => 'BAL' . time() . Str::random(5),
            'balanceTypes' => ['BALANCE'],
            'additionalInfo' => [
                'accessToken' => $accessToken
            ]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;

        // Menggunakan fungsi generateSignature yang sudah ada di Controller ini
        $signature = $this->generateSignature($stringToSign);

        try {
            Log::info('[DANA BALANCE CHECK] Meminta info saldo user ID: ' . $user->id_pengguna);

            $response = Http::withHeaders([
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time(),
                'X-DEVICE-ID'   => 'CUSTOMER-WEB-STATION',
                'CHANNEL-ID'    => '95221',
                'ORIGIN'        => config('services.dana.origin'),
                'Authorization-Customer' => 'Bearer ' . $accessToken,
                'Content-Type'  => 'application/json'
            ])->withBody($jsonBody, 'application/json')->post(config('services.dana.base_url') . $path);

            $result = $response->json();

            // Jika Berhasil (Response Code DANA 2001100 = Success Inquiry)
            if (isset($result['responseCode']) && $result['responseCode'] == '2001100') {
                $amount = $result['accountInfos'][0]['availableBalance']['value'] ?? 0;

                // (Opsional) Jika Anda punya kolom dana_user_balance di tabel users, Anda bisa menyimpannya
                // $user->update(['dana_user_balance' => $amount]);

                return response()->json([
                    'success' => true,
                    'balance' => $amount,
                    'formatted_balance' => 'Rp ' . number_format($amount, 0, ',', '.'),
                    'message' => 'Berhasil mengambil saldo DANA.'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil saldo: ' . ($result['responseMessage'] ?? 'Token mungkin kadaluarsa.')
            ]);

        } catch (\Exception $e) {
            Log::error('[DANA BALANCE CHECK] Error System: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Sistem Error saat mengecek saldo.'
            ], 500);
        }
    }

    /**
     * =========================================================================
     * DEBUGGING: Cek Status DANA via URL Browser
     * =========================================================================
     */
    public function debugDanaStatus($orderId)
    {
        // 1. Pastikan config dinamis (Sandbox/Prod) otomatis ter-load
        $this->applyDynamicConfig();

        // 2. Siapkan Payload (Sesuai dengan fungsi checkDanaGatewayStatus Anda)
        $body = [
            "partnerReferenceNo" => $orderId,
            "merchantId" => config('services.dana.merchant_id')
        ];

        $method = 'POST';
        $relativePath = '/rest/v1.1/debit/status';
        $timestamp = \Carbon\Carbon::now('Asia/Jakarta')->toIso8601String();

        try {
            // 3. Generate Signature
            $signature = $this->danaSignature->generateSignature($method, $relativePath, $body, $timestamp);

            // 4. Hit API DANA
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time(),
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'Content-Type'  => 'application/json',
                'CHANNEL-ID'    => 'MOBILE_WEB',
            ])->post(config('services.dana.base_url') . $relativePath, $body);

            // 5. Cetak respons langsung ke layar browser dengan rapi
            return response()->json([
                'INFO_SISTEM' => [
                    'MODE_AKTIF' => config('services.dana.dana_env'),
                    'TARGET_URL' => config('services.dana.base_url') . $relativePath,
                    'ORDER_ID'   => $orderId,
                ],
                'RESPONS_DARI_DANA' => $response->json()
            ], 200, [], JSON_PRETTY_PRINT);

        } catch (\Exception $e) {
            return response()->json([
                'ERROR_SISTEM' => $e->getMessage()
            ], 500, [], JSON_PRETTY_PRINT);
        }
    }

    public function transferBankPage()
    {
        // Ambil data bank dari database dan urutkan berdasarkan abjad
        $banks = DB::table('dana_bank_codes')->orderBy('bank_name', 'asc')->get();

        return view('customer.dana.transfer-bank', compact('banks'));
    }

}
