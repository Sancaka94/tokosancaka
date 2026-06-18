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
use App\Services\MidtransSnapService;

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
        // --- [DD: LOG DETAIL REQUEST SAAT TOMBOL SUBMIT DIKLIK] ---
        Log::info('========== [DEBUG TOPUP SUBMIT] ==========');
        Log::info('IP PENGIRIM: ' . $request->ip());
        Log::info('METHOD: ' . $request->method());
        Log::info('FULL URL: ' . $request->fullUrl());
        Log::info('HEADERS: ', $request->headers->all());
        Log::info('PAYLOAD (BODY): ', $request->all());
        Log::info('USER AUTH ID: ' . (\Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::user()->id_pengguna : 'TIDAK LOGIN'));
        Log::info('==========================================');
        // ----------------------------------------------------------

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
                    'payment_method' => 'DANA',
                    'description'    => 'Top up saldo via DANA',
                ]);

                DB::commit();
                return $this->createPaymentDANA($transaction);
            }

            // LOGIKA DANA DIRECT DEBIT (Fitur Baru)
            elseif ($validated['payment_method'] === 'DANA_DIRECT_DEBIT') {

                Log::info('Memulai Top Up DANA Direct Debit untuk ' . $invoiceNumber); // LOG LOG

                $transaction = Transaction::create([
                    'user_id'        => $user->id_pengguna,
                    'reference_id'   => $invoiceNumber,
                    'amount'         => $amount,
                    'type'           => 'topup',
                    'status'         => 'pending',
                    'payment_method' => 'DANA_DIRECT_DEBIT',
                    'description'    => 'Top up saldo via DANA Direct Debit',
                ]);

                DB::commit();

                // Arahkan ke fungsi baru
                return $this->createTopUpPaymentDANA($transaction);
            }


           // ==========================================================
            // --- TAMBAHAN UNTUK MIDTRANS BI-SNAP (VIRTUAL ACCOUNT) ---
            // ==========================================================
            /* elseif (\Illuminate\Support\Str::startsWith($validated['payment_method'], 'MIDTRANS_VA_')) {

                // Ekstrak kode bank dari string (Contoh: "MIDTRANS_VA_BCA" menjadi "bca")
                $bankCode = strtolower(str_replace('MIDTRANS_VA_', '', $validated['payment_method']));

                Log::info('LOG LOG: Memulai Top Up Midtrans VA ' . strtoupper($bankCode) . ' untuk ' . $invoiceNumber);

                $transaction = Transaction::create([
                    'user_id'        => $user->id_pengguna,
                    'reference_id'   => $invoiceNumber,
                    'amount'         => $amount,
                    'type'           => 'topup',
                    'status'         => 'pending',
                    'payment_method' => $validated['payment_method'],
                    'description'    => 'Top up saldo via VA ' . strtoupper($bankCode) . ' (Midtrans)',
                ]);

                DB::commit();

                // Arahkan ke fungsi eksekutor Midtrans VA (dibuat di langkah 2)
                return $this->createPaymentMidtransVA($transaction, $bankCode);
            } */
            // ==========================================================

            // ==========================================================
            // --- LOGIKA MIDTRANS SNAP (TAMPILAN ASLI MIDTRANS) ---
            // ==========================================================
            elseif (\Illuminate\Support\Str::startsWith($validated['payment_method'], 'MIDTRANS')) {

                Log::info('LOG LOG: Memulai Top Up Midtrans Snap untuk ' . $invoiceNumber);

                $transaction = Transaction::create([
                    'user_id'        => $user->id_pengguna,
                    'reference_id'   => $invoiceNumber,
                    'amount'         => $amount,
                    'type'           => 'topup',
                    'status'         => 'pending',
                    'payment_method' => 'MIDTRANS', // Cukup simpan sebagai MIDTRANS
                    'description'    => 'Top up saldo via Midtrans',
                ]);

                DB::commit();

                // Arahkan ke fungsi eksekutor Midtrans Snap
                return $this->createPaymentMidtransSnap($transaction);
            }
            // ==========================================================

            // ==========================================================
            // --- FITUR BARU: OVO, LINKAJA, & JENIUS PAY (DOKU DIRECT API) ---
            // ==========================================================
            elseif (in_array(strtoupper($validated['payment_method']), ['OVO', 'LINKAJA', 'JENIUS_PAY'])) {

                Log::info('LOG LOG: Memulai Top Up ' . strtoupper($validated['payment_method']) . ' untuk ' . $invoiceNumber);

                $transaction = Transaction::create([
                    'user_id'        => $user->id_pengguna,
                    'reference_id'   => $invoiceNumber,
                    'amount'         => $amount,
                    'type'           => 'topup',
                    'status'         => 'pending',
                    'payment_method' => strtoupper($validated['payment_method']),
                    'description'    => 'Top up saldo via ' . strtoupper($validated['payment_method']) . ' (DOKU)',
                ]);

                DB::commit();

                // Persiapan Data Umum
                $customerData = [
                    'name'  => $user->nama_lengkap,
                    'email' => $user->email,
                    'phone' => $user->no_wa
                ];
                $lineItems = [
                    ['name' => 'Top Up Saldo', 'price' => $amount, 'quantity' => 1]
                ];
                $redirectUrl = route('customer.topup.show', ['topup' => $invoiceNumber]);

                // --- 1. PROSES OVO ---
                if (strtoupper($validated['payment_method']) === 'OVO') {
                    // Ambil dari input form 'ovo_id', jika kosong fallback ke nomor WA user
                    $ovoId = $request->ovo_id ?? preg_replace('/[^0-9]/', '', $user->no_wa);
                    
                    $response = $dokuJokulService->createOvoPayment($invoiceNumber, $amount, $ovoId);
                    
                    if ($response['success']) {
                        return redirect()->route('customer.topup.show', ['topup' => $invoiceNumber])
                            ->with('success', '⏳ Silakan buka aplikasi OVO Anda sekarang untuk menyelesaikan pembayaran.');
                    } else {
                        throw new Exception('DOKU OVO Gagal: ' . $response['message']);
                    }
                }

                // --- 2. PROSES LINKAJA ---
                elseif (strtoupper($validated['payment_method']) === 'LINKAJA') {
                    $response = $dokuJokulService->createLinkAjaPayment($invoiceNumber, $amount, $customerData, $lineItems, $redirectUrl);
                    
                    if ($response['success'] && isset($response['data']['emoney_payment']['redirect_url_http'])) {
                        $transaction->payment_url = $response['data']['emoney_payment']['redirect_url_http'];
                        $transaction->save();
                        return redirect()->away($transaction->payment_url);
                    } else {
                        throw new Exception('DOKU LinkAja Gagal: ' . ($response['message'] ?? 'Unknown Error'));
                    }
                }

                // --- 3. PROSES JENIUS PAY ---
                elseif (strtoupper($validated['payment_method']) === 'JENIUS_PAY') {
                    // Ambil dari input form 'jenius_cashtag', jika kosong fallback ke $namalengkap
                    $cashTag = $request->jenius_cashtag ?? '$' . strtolower(str_replace(' ', '', $user->nama_lengkap));
                    
                    $response = $dokuJokulService->createJeniusPayment($invoiceNumber, $amount, $cashTag, $customerData, $lineItems, $redirectUrl);
                    
                    if ($response['success']) {
                        return redirect()->route('customer.topup.show', ['topup' => $invoiceNumber])
                            ->with('success', '⏳ Tagihan telah dikirim! Silakan buka aplikasi Jenius Anda untuk menyelesaikan pembayaran.');
                    } else {
                        throw new Exception('DOKU Jenius Pay Gagal: ' . ($response['message'] ?? 'Unknown Error'));
                    }
                }
            }
            // ==========================================================

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
                } elseif (strtoupper($validated['payment_method']) === 'PAYPAL') {
                    $paymentGateway = 'paypal'; // <-- TAMBAHAN PAYPAL
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

                    // ... logika SAC ID DOKU ...
                    $additionalInfo = [];
                    $store = \App\Models\Store::where('user_id', $user->id_pengguna)->first();
                    if ($store && !empty($store->doku_sac_id)) {
                        $additionalInfo = [
                            'account' => [
                                'id' => $store->doku_sac_id
                            ]
                        ];
                    }

                    $paymentUrl = $DokuJokulService->createPayment(
                        $invoiceNumber,
                        $amount,
                        $customerData,
                        $lineItems,
                        $additionalInfo,
                        $successRedirectUrl
                    );

                    $redirectUrl = $paymentUrl;

                    if (empty($paymentUrl)) {
                        throw new Exception('Gagal membuat transaksi DOKU.');
                    }

                } elseif ($paymentGateway === 'paypal') {
                    // ==========================================================
                    // --- PROSES VIA PAYPAL (BARU) ---
                    // ==========================================================
                    Log::info('Memulai Top Up PayPal untuk ' . $invoiceNumber);
                    
                    // Panggil fungsi eksekutor PayPal TopUp yang dibuat di bawah
                    $paymentUrl = $this->createPaymentPayPalTopUp($transaction, $amount);
                    $redirectUrl = $paymentUrl;

                    if (empty($paymentUrl)) {
                        throw new Exception('Gagal membuat link pembayaran PayPal.');
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
    Log::info('LOG LOG: [BINDING] Memulai proses redirect ke DANA (Debug)...');
    $user = \Illuminate\Support\Facades\Auth::user();

    // Simpan id_pengguna ke session sebagai cadangan pengenal user
    session(['dana_user_id' => $user->id_pengguna]);

    $queryParams = [
        'partnerId'   => config('services.dana.x_partner_id'),
        'merchantId'  => config('services.dana.merchant_id'),
        'timestamp'   => now('Asia/Jakarta')->format('Y-m-d\TH:i:s+07:00'),
        'externalId'  => 'BIND-' . $user->id_pengguna . '-' . time(),
        'channelId'   => 'DANAID',
        'redirectUrl' => 'https://tokosancaka.com/dana/callback',
        'state'       => \Illuminate\Support\Str::random(16),
        'scopes'      => 'QUERY_BALANCE,MINI_DANA,DEFAULT_BASIC_PROFILE',
        'allowRegistration' => 'true',
    ];

    $baseUrl = config('services.dana.dana_env') === 'PRODUCTION'
        ? 'https://m.dana.id/d/portal/oauth'
        : 'https://m.sandbox.dana.id/d/portal/oauth';

    return redirect($baseUrl . "?" . http_build_query($queryParams));
}

public function handleCallback(Request $request)
{
    Log::info('LOG LOG: [DANA CALLBACK] SNAP Apply Token Start...', $request->all());

    $authCode = $request->input('auth_code') ?? $request->input('authCode');

    // Langsung cek Auth user yang sedang login, atau ambil dari session jika redirect memutus Auth
    $userId = null;
    if (\Illuminate\Support\Facades\Auth::check()) {
        $userId = \Illuminate\Support\Facades\Auth::user()->id_pengguna;
    } elseif (session()->has('dana_user_id')) {
        $userId = session('dana_user_id');
    }

    if (!$authCode || !$userId) {
        Log::error('LOG LOG: [DANA CALLBACK] Gagal Ekstraksi ID atau Auth Code Kosong. User ID: ' . ($userId ?? 'NULL'));
        return redirect()->route('customer.topup.index')->with('error', 'Sesi kadaluarsa. Pastikan Anda masih login.');
    }

    try {
        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $clientId = config('services.dana.client_id');

        $stringToSign = $clientId . "|" . $timestamp;
        $signature = $this->generateSignature($stringToSign);

        $body = [
            "grantType"    => "AUTHORIZATION_CODE",
            "authCode"     => $authCode,
            "refreshToken" => "",
            "additionalInfo" => (object)[]
        ];

        $path = '/v1.0/access-token/b2b2c.htm';
        $fullUrl = config('services.dana.base_url') . $path;

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Content-Type'  => 'application/json',
            'X-TIMESTAMP'   => $timestamp,
            'X-CLIENT-KEY'  => $clientId,
            'X-SIGNATURE'   => $signature,
            'X-PARTNER-ID'  => $clientId,
        ])->post($fullUrl, $body);

        $result = $response->json();
        Log::info('LOG LOG: [DANA CALLBACK] Respon Apply Token:', $result);

        // Standar SNAP BI
        $successCodes = ['2000000', '2007400'];

        if (isset($result['responseCode']) && in_array($result['responseCode'], $successCodes)) {
            $accessToken = $result['accessToken'] ?? $result['access_token'] ?? null;

            if ($accessToken) {
                // Update tabel Pengguna
                \Illuminate\Support\Facades\DB::table('Pengguna')->where('id_pengguna', $userId)->update([
                    'dana_access_token' => $accessToken,
                    'dana_auth_code'    => $authCode,
                    //'updated_at'        => now()

                    ]);

                // Bersihkan session
                session()->forget('dana_user_id');

                Log::info("LOG LOG: [DANA CALLBACK] UPDATE DATABASE BERHASIL untuk User ID: $userId");
                return redirect()->route('customer.topup.index')->with('success', '✅ Akun DANA Berhasil Terhubung!');
            }
        }

        Log::error('LOG LOG: [DANA SNAP ERROR]', $result);
        return redirect()->route('customer.topup.index')->with('error', 'DANA Reject: ' . ($result['responseMessage'] ?? 'Unknown Error'));

    } catch (\Exception $e) {
        Log::error('LOG LOG: [DANA CALLBACK] System Error:', ['msg' => $e->getMessage()]);
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
        $token = "cC3LrEd8VwDDRuE6urcj";

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

    // =========================================================================
    // FUNGSI: CEK STATUS TOP UP (CUSTOMER TOP UP INQUIRY STATUS)
    // Sesuai dengan dokumentasi API Service Code: 39
    // =========================================================================
    public function checkTopupStatus(Request $request)
    {
        Log::info('[DANA TOPUP STATUS] Memulai pengecekan status...', [
            'partnerReferenceNo' => $request->reference_no,
            'affiliate_id'       => $request->affiliate_id
        ]);

        $trx = DB::table('dana_transactions')->where('reference_no', $request->reference_no)->first();
        if (!$trx) return back()->with('error', 'Data transaksi tidak ditemukan di database.');

        // 1. Jika sudah sukses/gagal di database, tidak perlu buang kuota hit API
        if (in_array($trx->status, ['SUCCESS', 'FAILED', 'REFUNDED'])) {
            return back()->with('warning', 'Transaksi ini sudah berstatus final (' . $trx->status . ').');
        }

        $timestamp = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        
        // SESUAI DOKUMEN: Path tanpa .htm di belakangnya
        $path = '/rest/v1.0/emoney/topup-status'; 

        // SESUAI DOKUMEN: Body request sederhana
        $body = [
            "originalPartnerReferenceNo" => $trx->reference_no,
            "serviceCode"                => "38" // Service code asli saat transaksi Top Up
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;

        try {
            $signature = $this->generateSignature($stringToSign);
            $accessTokenB2B = $this->danaSignature->getAccessToken();

            $headers = [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $accessTokenB2B,
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time() . \Illuminate\Support\Str::random(6),
                'ORIGIN'        => config('services.dana.origin'),
                'CHANNEL-ID'    => '95221'
            ];

            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            Log::info('[DANA TOPUP STATUS] Respon DANA:', $result);

            $resCode = $result['responseCode'] ?? '500';

            // ========================================================
            // EVALUASI RESPONSE CODE SESUAI DOKUMEN (2003900 = Sukses)
            // ========================================================
            if ($resCode === '2003900') {
                $status = $result['latestTransactionStatus'] ?? null;

                // 00 - Success
                if ($status === '00') {
                    DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => 'SUCCESS']);
                    return back()->with('success', '✅ Transaksi BERHASIL (Terkonfirmasi). Saldo pelanggan telah masuk.');
                } 
                // 01, 02, 03 - Pending
                elseif (in_array($status, ['01', '02', '03'])) {
                    return back()->with('warning', '⏳ Transaksi masih PENDING di sistem DANA.');
                } 
                // 04, 05, 06, 07 - Failed/Refunded/Not Found
                elseif (in_array($status, ['04', '05', '06', '07'])) {
                    // TRANSAKSI GAGAL -> KEMBALIKAN SALDO USER SANCAKA
                    DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => 'FAILED']);
                    DB::table('Pengguna')->where('id_pengguna', $trx->affiliate_id)->increment('saldo', $trx->amount);

                    $desc = $result['transactionStatusDesc'] ?? 'Transaksi Gagal';
                    return back()->with('error', "❌ Transaksi DANA GAGAL ($desc). Saldo Rp " . number_format($trx->amount, 0, ',', '.') . " telah dikembalikan.");
                }
            } 
            // Handle: 4043901 - Transaction Not Found (Transaksi Kadaluarsa/Tidak Terdaftar)
            elseif ($resCode === '4043901') {
                DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => 'FAILED']);
                DB::table('Pengguna')->where('id_pengguna', $trx->affiliate_id)->increment('saldo', $trx->amount);
                return back()->with('error', '❌ Transaksi tidak ditemukan di DANA. Saldo telah dikembalikan.');
            } 
            // Handle Error Lainnya (Too Many Request, Internal Server Error, dll) -> Biarkan PENDING
            else {
                $errMsg = $result['responseMessage'] ?? 'Unknown Error';
                return back()->with('warning', "⚠️ Gagal mengecek status ke DANA: [$resCode] $errMsg. (Status tetap Pending)");
            }

        } catch (\Exception $e) {
            Log::error('[DANA TOPUP STATUS] System Error', ['msg' => $e->getMessage()]);
            return back()->with('error', 'Sistem Error saat mengecek status: ' . $e->getMessage());
        }
    }

   public function bankAccountInquiry(Request $request)
    {
        // 1. Validasi Affiliate yang sedang login (Tetap diperlukan untuk validasi internal aplikasi)
        $aff = DB::table('Pengguna')->where('id_pengguna', $request->affiliate_id)->first();
        if (!$aff) return back()->with('error', 'Pengguna tidak ditemukan.');

        // ==============================================================
        // MENGGUNAKAN DATA AKUN MERCHANT DEPOSIT (DISBURSEMENT B2B)
        // Tidak perlu lagi query ke tabel Pengguna untuk mengambil token admin
        // ==============================================================
        $merchantDepositAccount = config('services.dana.merchant_deposit_account'); 
        $idToko = config('services.dana.id_toko');

        $timestamp = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $path = '/v1.0/emoney/bank-account-inquiry.htm';
        $refNo = "BNK" . time() . Str::random(4);

        $cekBank = DB::table('dana_bank_codes')->where('bank_code', $request->bank_code)->first();
        $readableBank = $cekBank ? $cekBank->bank_name : $request->bank_code;

        $body = [
            "partnerReferenceNo" => $refNo,
            "customerNumber"     => $merchantDepositAccount, // Pakai No Akun Merchant Deposit
            "beneficiaryAccountNumber" => $request->account_no, // Input dari pelanggan
            "amount" => [
                "value"    => number_format((float)$request->amount, 2, '.', ''),
                "currency" => "IDR"
            ],
            "additionalInfo" => [
                "fundType"               => "MERCHANT_WITHDRAW_FOR_CORPORATE",
                "beneficiaryBankCode"    => (string) $request->bank_code,
                "beneficiaryAccountName" => "",
                "merchantId"             => $idToko // Disisipkan sebagai identitas corporate
            ]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
        
        $signature = $this->generateSignature($stringToSign);

        try {
            // LANGSUNG GENERATE TOKEN B2B DARI SERVICE, BUKAN DARI DATABASE
            $accessTokenB2B = $this->danaSignature->getAccessToken();

            $headers = [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $accessTokenB2B,
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'ORIGIN'        => config('services.dana.origin'),
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time() . Str::random(6),
                'X-IP-ADDRESS'  => $request->ip() ?? '82.25.62.13',
                'X-DEVICE-ID'   => 'SANCAKA-DANA-01',
                'CHANNEL-ID'    => '95221'
            ];

            Log::info('[BANK INQUIRY B2B] Sending Request to DANA', ['body' => $body]);

            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            Log::info('[BANK INQUIRY B2B]', ['res' => $result]);

            $resCode = $result['responseCode'] ?? '500';

            DB::table('dana_transactions')->insert([
                'affiliate_id' => $aff->id_pengguna,
                'type' => 'BANK_INQUIRY',
                'reference_no' => $refNo,
                'phone' => $request->account_no . " (" . $readableBank . ")",
                'amount' => $request->amount,
                'status' => ($resCode == '2004200') ? 'SUCCESS' : 'FAILED',
                'response_payload' => json_encode($result),
                'created_at' => now()
            ]);

            if ($resCode == '2004200') {
                $bankName = $result['beneficiaryBankShortName'] ?? $result['beneficiaryBankName'] ?? $readableBank;
                $accName  = $result['beneficiaryAccountName'];

                $report = (object) [
                    'is_success' => true,
                    'message_title' => 'Bank Account Valid',
                    'description' => "Rekening $bankName atas nama $accName valid."
                ];

                return back()->with('success', "Rekening Valid: $accName ($bankName)")
                             ->with('dana_report', $report)
                             ->with('valid_account_name', $accName)
                             ->with('valid_bank_name', $bankName)
                             ->withInput();
            }

            $errMsg = $result['responseMessage'] ?? 'Unknown Error';
            
            if ($resCode == '4034214') $errMsg = "Saldo Merchant DANA Tidak Cukup (Isi Saldo Sancaka Dulu!)";
            if ($resCode == '4034218') $errMsg = "Akun Merchant Inactive (Hubungi Admin DANA)";
            if ($resCode == '4044201') $errMsg = "Rekening Tidak Ditemukan/Salah Bank";
            if ($resCode == '4004201') $errMsg = "Format Kode Bank Tidak Valid (".$request->bank_code.")";
            if ($resCode == '4014202') $errMsg = "Token Otorisasi DANA tidak valid / kadaluarsa.";

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

        $aff = DB::table('Pengguna')->where('id_pengguna', $request->affiliate_id)->first();
        if (!$aff) return back()->with('error', 'Pengguna tidak ditemukan.');

        if ($aff->saldo < $request->amount) {
            return back()->with('error', 'Saldo komisi Anda tidak mencukupi.');
        }

        // Sanitasi nomor pengguna yang sedang login
        $customerNumber = preg_replace('/[^0-9]/', '', $aff->no_wa);
        if (substr($customerNumber, 0, 1) === '0') {
            $customerNumber = '62' . substr($customerNumber, 1);
        }

        // =========================================================
        // MENGGUNAKAN TRANSACTION UNTUK MENCEGAH RACE CONDITION
        // =========================================================
        DB::beginTransaction(); 

        try {
            // POTONG SALDO DIAWAL SECARA AMAN (LOCKING DALAM TRANSACTION)
            DB::table('Pengguna')->where('id_pengguna', $aff->id_pengguna)->decrement('saldo', $request->amount);

            // =========================================================
            // PERSIAPAN DATA DANA
            // =========================================================
            $merchantDepositAccount = config('services.dana.merchant_deposit_account'); 
            $idToko = config('services.dana.id_toko');

            $timestamp  = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
            $path       = '/v1.0/emoney/transfer-bank.htm';
            $partnerRef = "TRF" . time() . Str::random(6);

            $cekBank = DB::table('dana_bank_codes')->where('bank_code', $request->bank_code)->first();
            $readableBank = $cekBank ? $cekBank->bank_name : $request->bank_code;

            $body = [
                "partnerReferenceNo"       => $partnerRef,
                "customerNumber"           => $merchantDepositAccount, 
                "beneficiaryAccountNumber" => (string) $request->account_no,
                "beneficiaryBankCode"      => (string) $request->bank_code,
                "amount" => [
                    "value"    => number_format((float)$request->amount, 2, '.', ''),
                    "currency" => "IDR"
                ],
                "additionalInfo" => [
                    "fundType"               => "MERCHANT_WITHDRAW_FOR_CORPORATE",
                    "beneficiaryAccountName" => (string) $request->account_name,
                    "merchantId"             => $idToko,
                    "notes"                  => "Transfer ke Bank " . $readableBank,
                    "needNotify"             => true
                ]
            ];

            $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
            $hashedBody = strtolower(hash('sha256', $jsonBody));
            $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
            
            $signature = $this->generateSignature($stringToSign);

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

            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            $resCode = $result['responseCode'] ?? '500';

            // =========================================================
            // EVALUASI RESPON DANA
            // =========================================================
            if ($resCode == '2004300') {
                
                // TRANSAKSI BERHASIL: Simpan log dan COMMIT saldo
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

                DB::commit(); // PERMANENKAN PEMOTONGAN SALDO

                $danaRef = $result['referenceNo'] ?? '-';
                $msg = "Transfer Berhasil!\nRef: $partnerRef\nNominal: Rp " . number_format($request->amount, 0, ',', '.');
                return back()->with('success', $msg);

            } elseif (in_array($resCode, ['2024300', '4294300', '5004301'])) {
                
                // TRANSAKSI PENDING: Simpan log dan COMMIT saldo sementara
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

                DB::commit(); // PERMANENKAN PEMOTONGAN SALDO KARENA PENDING

                return redirect()->route('admin.dana.transfer_bank')->with('warning', "⏳ Transaksi Sedang Diproses (Pending).\nMohon cek riwayat saldo secara berkala.");

            } else {
                
                // TRANSAKSI GAGAL: BATALKAN PEMOTONGAN SALDO DENGAN ROLLBACK
                DB::rollBack(); 

                // Setelah rollback dipanggil, saldo user otomatis aman (tidak jadi terpotong).
                // Sekarang kita tetap catat histori kegagalannya ke database.
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
                Log::error('[DANA TRANSFER BANK] Gagal & Refund Auto (Rollback)', ['res' => $result]);
                
                return redirect()->route('admin.dana.transfer_bank')->with('error', "Gagal: $errorMsg\n(Saldo Rp ".number_format($request->amount, 0, ',', '.')." telah dikembalikan).");
            }

        } catch (\Exception $e) {
            
            // JIKA TERJADI ERROR KONEKSI / FATAL ERROR: BATALKAN PEMOTONGAN SALDO
            DB::rollBack(); 
            
            Log::error('[DANA TRANSFER BANK] Exception', ['msg' => $e->getMessage()]);
            return redirect()->route('admin.dana.transfer_bank')->with('error', 'Sistem Error saat eksekusi: ' . $e->getMessage() . "\n(Saldo telah dikembalikan otomatis).");
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
        Log::info('DANA START for Transaction Table: ' . $trxId); // LOG LOG dipertahankan

        // INI YANG SEMPAT HILANG: Deklarasi $user
        $user = Auth::user(); 

        $timestamp = Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $expiryTime = Carbon::now('Asia/Jakarta')->addMinutes(30)->format('Y-m-d\TH:i:sP');

        $merchantId = config('services.dana.merchant_id');
        $amountValue = number_format((float)$transaction->amount, 2, '.', '');

        // Gunakan nullsafe untuk mencegah error jika user tidak ditemukan
        $userId = $user ? (string) $user->id_pengguna : 'GUEST' . rand(100, 999);
        $nickname = $user ? substr(preg_replace('/[^a-zA-Z0-9 ]/', '', $user->nama_lengkap), 0, 40) : 'Customer Sancaka';

        // ====================================================================
        // BODY REQUEST BERSUKU CADANG SESUAI DENGAN PAYLOAD GAPURA YANG SUKSES
        // ====================================================================
        $bodyArray = [
            "partnerReferenceNo" => $trxId,
            "merchantId"         => $merchantId,
            "amount"             => [
                "value"    => $amountValue,
                "currency" => "IDR"
            ],
            "validUpTo"          => $expiryTime,
            "urlParams"          => [
                [
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
            "additionalInfo"     => [
                "mcc"     => "5732",
                "envInfo" => [
                    "sourcePlatform"    => "IPG",
                    "terminalType"      => "SYSTEM",
                    "orderTerminalType" => "WEB"
                ],
                "order"   => [
                    "orderTitle"        => "Top Up " . $trxId,
                    "scenario"          => "REDIRECT",
                    "merchantTransType" => "01",
                    "buyer"             => [
                        "externalUserId"   => $userId,
                        "externalUserType" => "MERCHANT_USER",
                        "nickname"         => $nickname
                    ],
                    "goods"             => [
                        [
                            "name"            => "Saldo Top Up",
                            "merchantGoodsId" => "ITEM" . $trxId,
                            "description"     => "Top Up Saldo Aplikasi",
                            "category"        => "DIGITAL_GOODS",
                            "price"           => [
                                "value"    => $amountValue,
                                "currency" => "IDR"
                            ],
                            "unit"            => "pcs",
                            "quantity"        => "1"
                        ]
                    ]
                ]
            ]
        ];

        $jsonBody = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            $accessToken = $this->danaSignature->getAccessToken();
            $signature = $this->danaSignature->generateSignature('POST', '/payment-gateway/v1.0/debit/payment-host-to-host.htm', $jsonBody, $timestamp);

            $baseUrl = config('services.dana.base_url');

            Log::info('LOG LOG: [UAT DANA TESTING] Mengirim request API H2H payment-host-to-host.htm DANA');

            $headersUAT = [
                'Authorization' => 'Bearer ' . $accessToken,
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => Str::random(32),
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'Content-Type'  => 'application/json',
                'CHANNEL-ID'    => '95221',
                'ORIGIN'        => config('services.dana.origin'),
            ];

            $response = Http::withHeaders($headersUAT)
              ->withBody($jsonBody, 'application/json')
              ->post($baseUrl . '/payment-gateway/v1.0/debit/payment-host-to-host.htm');

            $result = $response->json();

            // Tambahan Log untuk memonitor hasil testing
            Log::info('DANA Create Payment Result:', $result);
            Log::info('LOG LOG: [UAT DANA TESTING] Hasil Response createPaymentDANA:', $result);

            if (isset($result['responseCode']) && $result['responseCode'] == '2005400') {
                $redirectUrl = $result['webRedirectUrl'] ?? $result['appLinkUrl'] ?? null;
                if ($redirectUrl) {
                    $transaction->payment_url = $redirectUrl;
                    $transaction->save();
                    return redirect()->away($redirectUrl);
                }
            }

            Log::error('DANA Gagal:', $result);

            // Output error code ke UI
            $errorCode = $result['responseCode'] ?? 'N/A';
            return back()->with('error', 'Gagal dari DANA: ' . ($result['responseMessage'] ?? 'Unknown') . ' (Code: ' . $errorCode . ')');

        } catch (\Exception $e) {
            Log::error('DANA Error: ' . $e->getMessage());
            return back()->with('error', 'Koneksi DANA Error.');
        }
    }

   /* public function createPaymentDANA(Transaction $transaction)
    {
        $trxId = $transaction->reference_id;
        Log::info('DANA START for Transaction Table: ' . $trxId);

        $user = Auth::user();
        $returnUrl = route('dana.return');
        $timestamp = Carbon::now('Asia/Jakarta')->toIso8601String();
        $expiryTime = Carbon::now('Asia/Jakarta')->addMinutes(60)->format('Y-m-d\TH:i:sP');

        $bodyArray = [
            "partnerReferenceNo" => $trxId,
            //"merchantId" => config('services.dana.merchant_id'),
            "merchantId" => "216110000000000000000",
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

            $baseUrl = config('services.dana.base_url');

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'X-PARTNER-ID' => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => Str::random(32),
                'X-TIMESTAMP' => $timestamp,
                'X-SIGNATURE' => $signature,
                'Content-Type' => 'application/json',
                'CHANNEL-ID' => '95221',
                'ORIGIN' => config('services.dana.origin'),
            ])->withBody($jsonBody, 'application/json')
              ->post($baseUrl . '/payment-gateway/v1.0/debit/payment-host-to-host.htm');

            $result = $response->json();

            if (isset($result['responseCode']) && $result['responseCode'] == '2005400') {
                $redirectUrl = $result['webRedirectUrl'] ?? $result['appLinkUrl'] ?? null;
                if ($redirectUrl) {
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
    }*/

    public function createTopUpPaymentDANA(Transaction $transaction)
    {
        // ====================================================================
        // 1. CONFIGURATION (SYNC ID)
        // ====================================================================
        // Menggunakan ID Valid (2166...) untuk Header & Body agar sinkron
        
        $merchantIdConf = $validId;
        $validId = config('services.dana.valid_id');
        $partnerIdConf = config('services.dana.partner_id_conf');

        // ====================================================================
        // 2. DATA PREPARATION
        // ====================================================================
        $user = Auth::user();
        $cleanInvoice = preg_replace('/[^a-zA-Z0-9]/', '', $transaction->reference_id);
        $timestamp    = Carbon::now('Asia/Jakarta')->toIso8601String();
        // $expiryTime   = Carbon::now('Asia/Jakarta')->addMinutes(60)->format('Y-m-d\TH:i:sP');
        $expiryTime   = Carbon::now('Asia/Jakarta')->addMinutes(30)->format('Y-m-d\TH:i:sP');
        $amountValue  = number_format((float)$transaction->amount, 2, '.', '');

        // ====================================================================
        // 3. BODY REQUEST
        // ====================================================================
        $bodyArray = [
            "partnerReferenceNo" => $cleanInvoice,
            "merchantId"         => $merchantIdConf,
            "amount"             => [
                "value"    => $amountValue,
                "currency" => "IDR"
            ],
            "validUpTo"          => $expiryTime,
            "urlParams"          => [
                // PAY_RETURN BOLEH pakai tanda tanya (?) untuk diproses Smart Hub
                ["url" => route('dana.return', ['trx_id' => $cleanInvoice]), "type" => "PAY_RETURN", "isDeeplink" => "Y"],

                // NOTIFICATION TIDAK BOLEH ada tanda tanya (?). Harus bersih!
                ["url" => url('/dana/notify'), "type" => "NOTIFICATION", "isDeeplink" => "N"]
            ],
            // Opsi Pembayaran (Wajib BALANCE/Saldo agar aman tanpa Token)
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
                        "nickname"         => substr(preg_replace('/[^a-zA-Z0-9 ]/', '', $user->nama_lengkap ?? 'Guest'), 0, 20),
                    ],
                    // Goods Wajib Ada
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
                    "orderTerminalType" => "WEB",
                ]
            ]
        ];

        $jsonBody = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $relativePath = '/rest/redirection/v1.0/debit/payment-host-to-host';

        try {
            // ====================================================================
            // 4. SIGNATURE & HEADERS
            // ====================================================================
            $accessToken = $this->danaSignature->getAccessToken();
            $signature   = $this->danaSignature->generateSignature('POST', $relativePath, $jsonBody, $timestamp);

            $headers = [
                'Authorization'  => 'Bearer ' . $accessToken,
                'X-PARTNER-ID'   => $partnerIdConf, // ID Sinkron dengan Body
                'X-EXTERNAL-ID'  => Str::random(32),
                'X-TIMESTAMP'    => $timestamp,
                'X-SIGNATURE'    => $signature,
                'Content-Type'   => 'application/json',
                'CHANNEL-ID'     => '95221',
                'ORIGIN'         => config('services.dana.origin'),
            ];

            // ====================================================================
            // 5. LOGGING REQUEST (SEBELUM KIRIM)
            // ====================================================================
            Log::info('DANA_REQ_START_TOPUP', [
                'Invoice' => $cleanInvoice,
                'URL'     => config('services.dana.base_url') . $relativePath,
                'Headers' => $headers,
                'Body'    => $bodyArray
            ]);

            // ====================================================================
            // 6. SEND REQUEST
            // ====================================================================
            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $relativePath);

            $result = $response->json();

            // ====================================================================
            // 7. LOGGING RESPONSE (SETELAH TERIMA)
            // ====================================================================
            Log::info('DANA_RES_END_TOPUP', [
                'Invoice'     => $cleanInvoice,
                'Status_Code' => $response->status(),
                'Result'      => $result
            ]);

            // ====================================================================
            // 8. HANDLE SUCCESS / REDIRECT
            // ====================================================================
            if (isset($result['responseCode']) && $result['responseCode'] == '2005400') {
                $redirectUrl = $result['webRedirectUrl'] ?? null;
                if($redirectUrl) {
                    // Potong URL yang masuk ke DB jadi 255 karakter saja agar MySQL tidak crash
                    $transaction->payment_url = substr($redirectUrl, 0, 255);
                    $transaction->save();

                    // USER TETAP DIALIKHAN PAKAI URL ASLI (Halaman DANA aman 100%)
                    return redirect()->away($redirectUrl);
                }
            }

            // Jika Gagal DANA, Log Error dan Kembalikan User
            Log::error('DANA_FAIL_TOPUP', ['Result' => $result]);
            return back()->with('error', 'Gagal memproses DANA: ' . ($result['responseMessage'] ?? 'Unknown Error'));

        } catch (\Exception $e) {
            // Tangkap Error Koneksi / Koding
            Log::error('DANA_EXCEPTION_TOPUP', ['Error' => $e->getMessage()]);
            return back()->with('error', 'Terjadi kesalahan koneksi ke DANA.');
        }
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

public function createPaymentDanaBinding(Transaction $transaction, $userAccount)
    {
        $trxId = $transaction->reference_id;
        Log::info('LOG LOG: [DANA BINDING] Memulai Express Checkout (1-Click) untuk Top Up: ' . $trxId);

        $timestamp = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $validUpTo = \Carbon\Carbon::now('Asia/Jakarta')->addMinutes(30)->format('Y-m-d\TH:i:sP');
        
        $path = '/rest/redirection/v1.0/debit/payment-host-to-host';

        $amountValue = number_format((float)$transaction->amount, 2, '.', '');

        // 3. PAYLOAD DISAMAKAN PERSIS DENGAN DOKUMENTASI & KEBUTUHAN STRICT DANA
        $body = [
            "partnerReferenceNo" => (string) $trxId,
            "merchantId"         => config('services.dana.merchant_id'),
            "validUpTo"          => \Carbon\Carbon::now('Asia/Jakarta')->addMinutes(30)->format('Y-m-d\TH:i:sP'),
            "amount" => [
                "value"    => $amountValue,
                "currency" => "IDR"
            ],
            "urlParams" => [
                [
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
                'X-DEVICE-ID'            => 'SANCAKA-WEB-POS',
                'CHANNEL-ID'             => '95221'
            ];

            Log::info('LOG LOG: [DANA BINDING] ==== DEBUG PAYLOAD ====');
            Log::info('LOG LOG: [DANA BINDING] merchantId: ' . var_export(config('services.dana.merchant_id'), true));
            Log::info('LOG LOG: [DANA BINDING] X-PARTNER-ID: ' . var_export(config('services.dana.x_partner_id'), true));
            Log::info('LOG LOG: [DANA BINDING] id_pengguna: ' . var_export($userAccount->id_pengguna, true));
            Log::info('LOG LOG: [DANA BINDING] dana_access_token: ' . (empty($userAccount->dana_access_token) ? 'KOSONG/NULL' : 'ADA, panjang=' . strlen($userAccount->dana_access_token)));
            Log::info('LOG LOG: [DANA BINDING] Final Body Terkirim: ' . $jsonBody);


            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post($baseUrl . $path);

            $result = $response->json();

            // 6. CEK RESPON SUKSES SESUAI DOKUMEN (2005400)
            if (isset($result['responseCode']) && $result['responseCode'] === '2005400') {

                // KUNCI EXPO: HANYA AMBIL WEB URL-NYA SAJA (webRedirectUrl)
                $redirectUrl = $result['webRedirectUrl'] ?? null;
                
                if (!empty($redirectUrl)) {
                    Log::info('LOG LOG: [DANA BINDING] Berhasil generate URL Express Checkout.');
                    
                    $transaction->update(['payment_url' => $redirectUrl]);
                    
                    // Arahkan user ke halaman DANA (Langsung masuk fase bayar karena ada Authorization-Customer)
                    return redirect()->away($redirectUrl);
                }

                // Fallback jika anehnya DANA sukses tapi tidak memberikan URL Web
                $transaction->update(['status' => 'failed']);
                Log::error('LOG LOG: [WEB BINDING] Transaksi DANA menggantung. Tidak ada Web URL yang diterbitkan.');
                return back()->with('error', 'Gagal: URL Pembayaran DANA tidak diterbitkan.');
            }

            // 7. PENANGANAN ERROR DANA
            $transaction->update(['status' => 'failed']);
            $errorCode  = $result['responseCode'] ?? 'UNKNOWN';
            $pesanGagal = $result['responseMessage'] ?? 'Terjadi kesalahan pada sistem pembayaran.';

            // Log full result untuk mempermudah debugging jika masih ada kode error
            Log::error("LOG LOG: [DANA BINDING] Gagal generate URL. Code: $errorCode | Msg: $pesanGagal", $result);

            return back()->with('error', "Gagal dari DANA [$errorCode]: $pesanGagal");

        } catch (\Exception $e) {
            Log::error('LOG LOG: [DANA BINDING] Fatal Exception: ' . $e->getMessage());
            return back()->with('error', 'Koneksi ke sistem DANA gagal. Silakan coba beberapa saat lagi.');
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
                $user->update(['dana_user_balance' => $amount]);

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

    // ###------------- HALAMAN ini Munculnya di Dashboard Admin ---------------######

    public function transferBankPage()
    {
        // 1. Ambil data bank dari database
        $banks = DB::table('dana_bank_codes')->orderBy('bank_name', 'asc')->get();

        // 2. Ambil riwayat transfer bank DARI SEMUA PELANGGAN (Karena ini Dashboard Admin Corporate)
        $transactions = DB::table('dana_transactions')
            ->where('type', 'TRANSFER_BANK')
            ->orderBy('created_at', 'desc')
            ->paginate(10); // Menampilkan 10 data per halaman

        // 3. Kirim $banks dan $transactions ke file Blade
        return view('admin.dana.transfer-bank', compact('banks', 'transactions'));
    }


    /**
     * =========================================================================
     * EKSEKUTOR PEMBAYARAN MIDTRANS BI-SNAP
     * =========================================================================
     */
    public function createPaymentMidtrans(Transaction $transaction)
    {
        $trxId = $transaction->reference_id;
        Log::info('LOG LOG: MIDTRANS START for Transaction Table: ' . $trxId);

        try {
            $midtransService = app(\App\Services\MidtransSnapService::class);
            $user = Auth::user();

            // Sesuai standar Payload BI-SNAP untuk generate link pembayaran (Host-to-Host)
            $payload = [
                'partnerReferenceNo' => $trxId,
                'amount' => [
                    'value' => number_format($transaction->amount, 2, '.', ''),
                    'currency' => 'IDR'
                ],
                'additionalInfo' => [
                    'orderTitle' => 'Top Up Saldo - ' . $trxId,
                    'buyer' => [
                        'externalUserId' => (string) $user->id_pengguna,
                        'nickname'       => $user->nama_lengkap ?? 'Customer Sancaka'
                    ]
                ]
            ];

            // Panggil Service Midtrans yang mengeksekusi HMAC_SHA512 Signature
            // Endpoint ini menyesuaikan dengan dokumentasi metode pembayaran Midtrans yang ingin Anda tembak
            $response = $midtransService->executeTransaction('POST', '/v1.0/debit/payment-host-to-host', $payload);

            if (isset($response['webRedirectUrl'])) {
                $transaction->payment_url = $response['webRedirectUrl'];
                $transaction->save();

                return redirect()->away($response['webRedirectUrl']);
            }

            Log::error('LOG LOG: Gagal mendapatkan URL Redirect dari Midtrans', $response);
            return back()->with('error', 'Gagal memproses pembayaran Midtrans. Coba metode lain.');

        } catch (\Exception $e) {
            Log::error('LOG LOG: MIDTRANS System Error: ' . $e->getMessage());
            return back()->with('error', 'Koneksi ke Midtrans terputus.');
        }
    }

    /**
     * =========================================================================
     * HANDLER WEBHOOK MIDTRANS
     * =========================================================================
     */
    public function midtransNotify(Request $request)
    {
        Log::info('LOG LOG: MIDTRANS NOTIFICATION HIT:', $request->all());

        try {
            $notification = $request->all();

            // Midtrans mengirimkan order_id dan transaction_status di root payload
            $merchantRef = $notification['order_id'] ?? null;
            $transactionStatus = $notification['transaction_status'] ?? null;
            $grossAmount = $notification['gross_amount'] ?? 0;

            if (!$merchantRef) {
                return response()->json(['message' => 'Invalid Request Data'], 400);
            }

            // Mapping Status Midtrans ke Internal Status Sancaka (PAID / FAILED)
            $internalStatus = 'PENDING';
            if (in_array($transactionStatus, ['capture', 'settlement'])) {
                $internalStatus = 'PAID';
            } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure'])) {
                $internalStatus = 'FAILED';
            }

            // Arahkan ke prosesor pusat yang sama seperti DOKU & Tripay
            return self::processTopUp($merchantRef, $internalStatus, $grossAmount);

        } catch (\Exception $e) {
            Log::error('LOG LOG: MIDTRANS NOTIFY ERROR: ' . $e->getMessage());
            return response()->json(['message' => 'System Error'], 500);
        }
    }

   /**
     * =========================================================================
     * EKSEKUTOR PEMBAYARAN MIDTRANS VIRTUAL ACCOUNT (SNAP FLOW)
     * =========================================================================
     */
    public function createPaymentMidtransVA(Transaction $transaction, $bankCode)
    {
        $trxId = $transaction->reference_id;
        Log::info('LOG LOG: MIDTRANS SNAP VA START for Transaction Table: ' . $trxId . ' Bank: ' . strtoupper($bankCode));

        try {
            // Panggil Service SNAP sing wis diperbaiki
            $midtransService = app(\App\Services\MidtransSnapService::class);
            $user = Auth::user();

            // Eksekusi nggolek Token SNAP & Redirect URL soko Midtrans
            $response = $midtransService->createSnapTransaction(
                $trxId,
                $transaction->amount,
                $bankCode,
                $user->nama_lengkap ?? 'Customer Sancaka',
                $user->no_wa ?? null,
                $user->email ?? null
            );

            // Respon sukses soko SNAP API ngetokno 'token' lan 'redirect_url'
            if (isset($response['redirect_url'])) {
                $redirectUrl = $response['redirect_url'];

                // Simpan URL transaksinya ke DB biar bisa diakses pelanggan sewaktu-waktu
                $transaction->payment_url = $redirectUrl;
                $transaction->save();

                Log::info('LOG LOG: [SNAP] Berhasil Ambil Redirect URL Midtrans', ['url' => $redirectUrl]);

                // Alihkan murni browser user nang halaman pembayaran SNAP Midtrans sing aman!
                return redirect()->away($redirectUrl);
            }

            Log::error('LOG LOG: [SNAP] Gagal membedah respon token', ['response' => $response]);
            return back()->with('error', 'Gagal memproses SNAP Midtrans: Respon tidak valid.');

        } catch (\Exception $e) {
            Log::error('LOG LOG: [SNAP] Controller Exception Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal terhubung ke Midtrans: ' . $e->getMessage());
        }
    }

    /**
     * =========================================================================
     * EKSEKUTOR PEMBAYARAN MIDTRANS SNAP (TAMPILAN BAWAAN MIDTRANS)
     * =========================================================================
     */
    public function createPaymentMidtransSnap(Transaction $transaction)
    {
        Log::info('LOG LOG: Generate Snap Token Midtrans untuk ' . $transaction->reference_id);
        $user = Auth::user();

        try {
            // Ambil konfigurasi Midtrans dari database (sesuai gaya kode Anda)
            $mode = \App\Models\Api::getValue('MIDTRANS_MODE', 'global', 'sandbox');
            $serverKey = \App\Models\Api::getValue('MIDTRANS_SERVER_KEY', $mode);
            $isProduction = ($mode === 'production');

            // Tentukan URL berdasarkan mode
            $baseUrl = $isProduction
                ? 'https://app.midtrans.com/snap/v1/transactions'
                : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

            // Payload standar Snap Midtrans
            $payload = [
                'transaction_details' => [
                    'order_id'     => $transaction->reference_id,
                    'gross_amount' => (int) $transaction->amount, // Wajib diubah ke integer
                ],
                'customer_details' => [
                    'first_name' => $user->nama_lengkap ?? 'Customer',
                    'email'      => $user->email ?? 'email@kosong.com',
                    'phone'      => $user->no_wa ?? '',
                ],

                'callbacks' => [
                    'finish' => url('/customer/topup')
                ]

            ];

            // Tembak API menggunakan fitur bawaan Laravel HTTP Client
            $response = Http::withBasicAuth($serverKey, '')
                            ->post($baseUrl, $payload);

            $result = $response->json();

            // Jika Midtrans mengembalikan URL pembayaran
            if (isset($result['redirect_url'])) {

                // 1. Simpan URL tersebut ke database (agar user bisa klik "Lanjut Bayar" nanti jika keluar)
                $transaction->payment_url = $result['redirect_url'];
                $transaction->save();

                // 2. Alihkan layar pelanggan ke halaman pembayaran resmi Midtrans
                return redirect()->away($result['redirect_url']);
            }

            // Jika gagal mendapatkan token/url
            Log::error('LOG LOG: Midtrans Snap Error', $result);
            return back()->with('error', 'Gagal memproses pembayaran dengan Midtrans. Pesan: ' . ($result['error_messages'][0] ?? 'Kesalahan tidak diketahui.'));

        } catch (\Exception $e) {
            Log::error('LOG LOG: Exception Midtrans Snap: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem saat menghubungi Midtrans.');
        }
    }

    /**
     * =========================================================================
     * HANDLER WEBHOOK DANA (Delegasi dari DanaWebhookController)
     * =========================================================================
     */
    public function handleDanaCallback(array $data)
    {
        $merchantRef = $data['order']['invoice_number'] ?? null;
        $statusRaw   = $data['transaction']['status'] ?? null; // Bisa "SUCCESS", "PAID", dll.
        $amount      = $data['order']['amount'] ?? 0;

        Log::info('Processing DANA Callback di TopUpController...', [
            'ref' => $merchantRef,
            'status' => $statusRaw,
            'amount' => $amount
        ]);

        if (!$merchantRef || !$statusRaw) {
            Log::error('TopUp Callback: Data webhook tidak valid/kosong.');
            return response()->json(['message' => 'Invalid data'], 400);
        }

        // =====================================================================
        // NORMALISASI STATUS: DANA kirim "SUCCESS" atau "00".
        // Fungsi processTopUp di bawah butuh "PAID" untuk mengeksekusi penambahan saldo.
        // =====================================================================
        $internalStatus = in_array(strtoupper($statusRaw), ['SUCCESS', 'PAID', '00']) ? 'PAID' : 'FAILED';
        
        Log::info('LOG LOG: [UAT DANA TESTING] Status DANA RAW: ' . $statusRaw . ' dinormalisasi menjadi Internal Status: ' . $internalStatus);

        try {
            // Panggil prosesor utama
            self::processTopUp($merchantRef, $internalStatus, $amount);

            // =========================================================================
            // --- [BALASAN WEBHOOK KHUSUS UAT DANA - ROW 3] ---
            // Beri tahu DANA bahwa proses sukses sesuai format yang diminta dokumen UAT
            // =========================================================================
            return response()->json([
                'responseCode'    => '2005600',
                'responseMessage' => 'Successful'
            ]);

        } catch (\Exception $e) {
            Log::error('Gagal mengeksekusi TopUp Callback: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Internal Error'], 500);
        }
    }

    /**
     * =========================================================================
     * PROSESOR INTI TOP UP (Versi Lengkap & Aman)
     * =========================================================================
     */
    public static function processTopUp($merchantRef, $status, $amount)
    {
        DB::beginTransaction();
        try {
            // Kita cari di tabel 'transactions'
            $transaction = Transaction::where('reference_id', $merchantRef)->lockForUpdate()->first();

            if (!$transaction) {
                Log::error('LOG LOG: TopUp Callback: Transaksi tidak ditemukan di tabel transactions.', ['ref' => $merchantRef]);
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Not Found'], 404);
            }

            // 1. Cek Idempotency: Jika sudah sukses, jangan diproses lagi
            if ($transaction->status === 'success') {
                Log::info('LOG LOG: TopUp Callback: Transaksi sudah sukses sebelumnya (Idempotent).', ['ref' => $merchantRef]);
                DB::rollBack();
                return response()->json(['success' => true, 'message' => 'Already processed']);
            }

            if ($transaction->payment_method === 'TRANSFER_MANUAL') {
                 Log::warning('LOG LOG: TopUp Callback: Mencoba memproses TRANSFER_MANUAL via webhook.', ['ref' => $merchantRef]);
                 DB::rollBack();
                 return response()->json(['success' => false, 'message' => 'Manual transfer'], 400);
            }

            // 2. Validasi jumlah uang
            if (abs((float)$transaction->amount - (float)$amount) > 0.01) {
                 Log::warning('LOG LOG: TopUp Callback: Jumlah uang tidak cocok.', [
                     'db_amount' => $transaction->amount,
                     'paid_amount' => $amount
                 ]);
                 // Opsional: Anda bisa return error di sini jika tidak mentolerir perbedaan harga
            }

           // ==========================================================
            // 3. LOGIKA PROSES STATUS (UBAH JADI LUNAS & TAMBAH SALDO)
            // ==========================================================
            if ($status === 'PAID') { // Ini sudah dinormalisasi dari SUCCESS menjadi PAID di handleDanaCallback

                Log::info('LOG LOG: Status DANA diterima sebagai LUNAS. Mengeksekusi penambahan saldo...', ['invoice' => $merchantRef]);

                $transaction->status = 'success';
                $transaction->save();

                $user = \App\Models\User::find($transaction->user_id);
                if ($user) {
                    // Eksekusi penambahan saldo di tabel pengguna/users
                    $user->increment('saldo', $transaction->amount);

                    Log::info('LOG LOG: Saldo user BERHASIL ditambah.', [
                        'user_id' => $user->id_pengguna ?? $user->id,
                        'nominal_topup' => $transaction->amount,
                        'saldo_akhir' => $user->saldo
                    ]);

                    // ---> [TAMBAHAN KIRIM EMAIL TOPUP SALDO USER] <---
                    try {
                        $htmlBody = view('emails.transaction_success', [
                            'name' => $user->nama_lengkap,
                            'invoice' => $merchantRef,
                            'type' => 'Top Up Saldo Aplikasi',
                            'amount' => $transaction->amount,
                            'date' => now()->timezone('Asia/Jakarta')->format('d M Y, H:i:s')
                        ])->render();

                        \Illuminate\Support\Facades\Mail::html($htmlBody, function ($message) use ($user, $merchantRef) {
                            $message->to($user->email)
                                    ->subject("✅ Top Up Berhasil - $merchantRef")
                                    ->from(config('mail.from.address', 'admin@tokosancaka.com'), 'Sancaka Server');
                        });
                    } catch (\Exception $e) {
                        Log::error('Gagal kirim email TopUp: ' . $e->getMessage());
                    }

                    // A. Kirim event ke UI Customer
                    try {
                        $message = 'Top up Anda sebesar Rp ' . number_format($transaction->amount, 0, ',', '.') . ' telah berhasil.';
                        event(new \App\Events\SaldoUpdated($user->id_pengguna, $transaction->amount, $user->saldo, $message));
                    } catch (\Exception $e) { Log::error('Gagal broadcast SaldoUpdated: ' . $e->getMessage()); }
                    
                    // B. Kirim notifikasi DB ke Customer
                    try {
                        $dataNotifCustomer = [
                            'tipe'        => 'TopUp',
                            'judul'       => 'Top Up Berhasil',
                            'pesan_utama' => 'Top up saldo Rp ' . number_format($transaction->amount, 0, ',', '.') . ' telah berhasil.',
                            'url'         => route('customer.topup.index'),
                            'icon'        => 'fas fa-check-circle',
                        ];
                        $user->notify(new \App\Notifications\NotifikasiUmum($dataNotifCustomer));
                    } catch (\Exception $e) { Log::error('Gagal kirim notif customer: ' . $e->getMessage()); }

                    // C. Kirim notifikasi DB ke Admin
                    try {
                        $admins = \App\Models\User::where('role', 'admin')->get();
                        if ($admins->isNotEmpty()) {
                            $dataNotifAdmin = [
                                'tipe'        => 'TopUp',
                                'judul'       => 'Top Up Berhasil',
                                'pesan_utama' => ($user->nama_lengkap ?? 'User') . ' berhasil top up Rp ' . number_format($transaction->amount, 0, ',', '.'),
                                'url'         => route('admin.saldo.requests.history'), // Asumsi rute riwayat saldo admin
                                'icon'        => 'fas fa-check-circle',
                            ];
                            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\NotifikasiUmum($dataNotifAdmin));
                        }
                    } catch (\Exception $e) { Log::error('Gagal kirim notif admin: ' . $e->getMessage()); }
                } else {
                    Log::error("LOG LOG: User terkait transaksi TopUp $merchantRef tidak ditemukan di database.");
                }

            } elseif ($status === 'PENDING') {
                Log::info('LOG LOG: TopUp Callback: Transaksi masih PENDING.', ['ref' => $merchantRef]);
            } else {
                // FAILED, EXPIRED, DENY
                $transaction->status = 'failed';
                $transaction->save();
                Log::info('LOG LOG: TopUp Callback: Transaksi digagalkan/expired.', ['ref' => $merchantRef, 'status' => $status]);
            }

            DB::commit();
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::critical('LOG LOG: TopUp Callback CRITICAL ERROR.', [
                'ref' => $merchantRef, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => 'Internal Error'], 500);
        }
    }


    /**
     * =========================================================================
     * BRIDGE WEBHOOK: Menjembatani panggilan dari CheckoutController Tripay
     * =========================================================================
     */
    public static function processTopUpCallback($merchantRef, $status = null, $amount = null)
    {
        // Jika CheckoutController mengirimkan data dalam bentuk array tunggal
        if (is_array($merchantRef) || is_object($merchantRef)) {
            $data = (array) $merchantRef;

            // Ekstrak data dari payload Tripay
            $ref = $data['merchant_ref'] ?? $data['reference'] ?? null;
            $stat = $data['status'] ?? 'PAID';
            $amt = $data['total_amount'] ?? $data['amount'] ?? 0;

            \Illuminate\Support\Facades\Log::info('LOG LOG: Meneruskan array callback Tripay ke prosesor inti', [
                'ref' => $ref, 'status' => $stat, 'amount' => $amt
            ]);

            return self::processTopUp($ref, $stat, $amt);
        }

        // Jika CheckoutController mengirimkan 3 parameter terpisah
        \Illuminate\Support\Facades\Log::info('LOG LOG: Meneruskan parameter callback Tripay ke prosesor inti', [
            'ref' => $merchantRef, 'status' => $status, 'amount' => $amount
        ]);

        return self::processTopUp($merchantRef, $status, $amount);
    }

    public function customerTopup(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'affiliate_id' => 'required|exists:Pengguna,id_pengguna',
            'phone'        => 'required|numeric',
            'amount'       => 'required|numeric|min:1000',
        ]);

        $aff = DB::table('Pengguna')->where('id_pengguna', $request->affiliate_id)->first();
        if (!$aff) return back()->with('error', 'Pengguna tidak ditemukan.');

        if ($aff->saldo < $request->amount) {
            return back()->with('error', 'Saldo komisi Anda tidak mencukupi.');
        }

        // 2. Sanitasi Nomor HP Penerima (Tujuan Top Up)
        $cleanPhone = preg_replace('/[^0-9]/', '', $request->phone);
        if (substr($cleanPhone, 0, 2) !== '62') {
            $cleanPhone = (substr($cleanPhone, 0, 1) === '0') ? '62' . substr($cleanPhone, 1) : '62' . $cleanPhone;
        }

        // Potong saldo internal user di awal
        DB::table('Pengguna')->where('id_pengguna', $aff->id_pengguna)->decrement('saldo', $request->amount);

        // ==============================================================
        // 3. IDENTITAS CORPORATE (DISBURSEMENT B2B)
        // ==============================================================
        $merchantDepositAccount = config('services.dana.merchant_deposit_account'); 
        $idToko = config('services.dana.id_toko');

        $timestamp  = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $partnerRef = "TUP" . time() . \Illuminate\Support\Str::random(4);
        $amountStr  = number_format((float)$request->amount, 2, '.', '');
        $path       = '/rest/v1.0/emoney/topup';

        // 4. SUSUN PAYLOAD SESUAI DOKUMENTASI
        $body = [
            "partnerReferenceNo" => $partnerRef,
            "customerNumber"     => $cleanPhone, // NOMOR HP PENERIMA SALDO
            "amount" => [
                "value"    => $amountStr,
                "currency" => "IDR"
            ],
            "feeAmount" => [
                "value"    => "0.00",
                "currency" => "IDR"
            ],
            "transactionDate" => $timestamp,
            "categoryId"      => "6",
            "additionalInfo"  => [
                "fundType"     => "AGENT_TOPUP_FOR_USER_SETTLE", // Wajib untuk Disbursement Top Up
                "chargeTarget" => "MERCHANT", // Tegaskan potong dari saldo Merchant Corporate
                "merchantId"   => $idToko, // Disisipkan sebagai penanda Corporate
                "accountId"    => $merchantDepositAccount // Opsional tapi aman untuk penanda
            ]
        ];

        $jsonBody     = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody   = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
        
        $signature    = $this->generateSignature($stringToSign);

        try {
            // PENTING: Generate Token B2B Corporate Anda
            $accessTokenB2B = $this->danaSignature->getAccessToken();

            $headers = [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $accessTokenB2B, // WAJIB ADA UNTUK B2B
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time() . \Illuminate\Support\Str::random(6),
                'CHANNEL-ID'    => '95221',
                'ORIGIN'        => config('services.dana.origin'),
            ];

            Log::info('========== [DANA TOPUP CORPORATE START] ==========');
            Log::info('[DANA REQUEST] Payload:', $body);

            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->timeout(60)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            $resCode = $result['responseCode'] ?? ($response->status() == 504 ? '504' : '500');
            $codeCheck = trim((string)$resCode);

            Log::info('[DANA RESPONSE] Result:', $result);

           if ($codeCheck === '2003800') { // 2003800 = SUCCESS berdasarkan dokumentasi
                DB::table('dana_transactions')->insert([
                    'affiliate_id'     => $aff->id_pengguna,
                    'type'             => 'TOPUP_B2B',
                    'reference_no'     => $partnerRef,
                    'phone'            => $cleanPhone,
                    'amount'           => $request->amount,
                    'status'           => 'SUCCESS',
                    'response_payload' => json_encode($result),
                    'created_at'       => now()
                ]);

                // --- PERBAIKAN UNTUK UAT DANA ---
                $danaRef = $result['referenceNo'] ?? '-';
                $trxDate = $result['transactionDate'] ?? $timestamp;
                
                $msg = "✅ Top Up Berhasil!\n\n" .
                       "No. Ref Sancaka: $partnerRef\n" .
                       "No. Ref DANA: $danaRef\n" .
                       "Nominal: Rp " . number_format($request->amount, 0, ',', '.') . "\n" .
                       "No. Pelanggan: $cleanPhone\n" .
                       "Tanggal Transaksi: $trxDate";

                return back()->with('success', $msg);

            } elseif (in_array($codeCheck, ['504', '4293800', '5003801', '2023800'])) {
                // STATUS PENDING (Timeout / Too Many Request)
                DB::table('dana_transactions')->insert([
                    'affiliate_id'     => $aff->id_pengguna,
                    'type'             => 'TOPUP_B2B',
                    'reference_no'     => $partnerRef,
                    'phone'            => $cleanPhone,
                    'amount'           => $request->amount,
                    'status'           => 'PENDING',
                    'response_payload' => json_encode($result),
                    'created_at'       => now()
                ]);

                return back()->with('warning', '⏳ Transaksi sedang diproses (Pending) oleh DANA. Mohon tunggu.');
            
            } else {
                // GAGAL - Kembalikan saldo pengguna
                DB::table('Pengguna')->where('id_pengguna', $aff->id_pengguna)->increment('saldo', $request->amount);

                DB::table('dana_transactions')->insert([
                    'affiliate_id'     => $aff->id_pengguna,
                    'type'             => 'TOPUP_B2B',
                    'reference_no'     => $partnerRef,
                    'phone'            => $cleanPhone,
                    'amount'           => $request->amount,
                    'status'           => 'FAILED',
                    'response_payload' => json_encode($result),
                    'created_at'       => now()
                ]);

                $resMsg = $result['responseMessage'] ?? 'Internal Error';
                $userMsg = match($codeCheck) {
                    '4033814' => 'Saldo Corporate Sancaka tidak mencukupi.',
                    '4033805' => 'Nomor DANA tujuan tidak valid.',
                    '4033818' => 'Nomor DANA tujuan tidak aktif (Inactive).',
                    '4043811' => 'Nomor DANA tujuan tidak ditemukan/diblokir.',
                    default   => "Gagal: $resMsg ($codeCheck)"
                };

                return back()->with('error', $userMsg . "\n(Saldo Anda telah dikembalikan)");
            }

        } catch (\Exception $e) {
            // Sistem Error - Kembalikan saldo pengguna
            DB::table('Pengguna')->where('id_pengguna', $aff->id_pengguna)->increment('saldo', $request->amount);
            Log::error('[DANA TOPUP] Exception: ' . $e->getMessage());
            
            return back()->with('error', 'Koneksi terputus. Saldo Anda telah dikembalikan.');
        } finally {
            Log::info('========== [DANA TOPUP CORPORATE END] ==========');
        }
    }

    // =========================================================================
    // FUNGSI: CEK STATUS TRANSFER BANK (DISBURSEMENT STATUS INQUIRY)
    // =========================================================================
    public function checkTransferStatus($id)
    {
        $trx = DB::table('dana_transactions')->where('id', $id)->first();
        if (!$trx) return back()->with('error', 'Data transaksi tidak ditemukan.');

        // 1. Jika status sudah final, tidak perlu cek ke DANA lagi
        if (in_array($trx->status, ['SUCCESS', 'FAILED', 'REFUNDED'])) {
            return back()->with('warning', 'Transaksi ini sudah berstatus final (' . $trx->status . ').');
        }

        $timestamp = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $path = '/v1.0/emoney/transfer-bank-status.htm';

        // 2. Body Sesuai Dokumentasi DANA SNAP (Disbursement)
        $body = [
            "originalPartnerReferenceNo" => $trx->reference_no,
            "serviceCode"                => "00", // Wajib "00" menurut dokumen
            "additionalInfo"             => [
                "merchantId"             => config('services.dana.id_toko')
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
                'X-EXTERNAL-ID' => (string) time() . \Illuminate\Support\Str::random(6),
                'CHANNEL-ID'    => '95221'
            ];

            Log::info('[DANA DISBURSEMENT STATUS CHECK] Request:', ['body' => $body]);

            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            Log::info('[DANA DISBURSEMENT STATUS CHECK] Respon:', $result);

            $resCode = $result['responseCode'] ?? '500';
            
            // 3. Evaluasi Berdasarkan Dokumentasi (ResponseCode = 2000000)
            if ($resCode === '2000000' || $resCode === '2004400') {
                $status = $result['latestTransactionStatus'] ?? null;

                // 00 - Success
                if ($status === '00') {
                    DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => 'SUCCESS']);
                    return back()->with('success', '✅ Transaksi BERHASIL. Dana sudah masuk ke rekening tujuan.');
                } 
                // 01, 02, 03 - Pending
                elseif (in_array($status, ['01', '02', '03'])) {
                    return back()->with('warning', '⏳ Transaksi masih berstatus PENDING di antrean bank. Silakan cek lagi nanti.');
                } 
                // 04, 05, 06, 07 - Failed/Refunded/Not Found
                elseif (in_array($status, ['04', '05', '06', '07'])) {
                    // TRANSAKSI GAGAL -> KEMBALIKAN SALDO USER
                    DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => 'FAILED']);
                    DB::table('Pengguna')->where('id_pengguna', $trx->affiliate_id)->increment('saldo', $trx->amount);

                    $desc = $result['transactionStatusDesc'] ?? 'Gagal / Dibatalkan';
                    return back()->with('error', "❌ Transaksi dinyatakan GAGAL ($desc). Saldo Rp " . number_format($trx->amount, 0, ',', '.') . " telah dikembalikan.");
                }
            } 
            // 4. Handle Error API (4040001 = Transaction Not Found)
            elseif ($resCode === '4040001') {
                DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => 'FAILED']);
                DB::table('Pengguna')->where('id_pengguna', $trx->affiliate_id)->increment('saldo', $trx->amount);
                return back()->with('error', '❌ Transaksi tidak ditemukan di sistem DANA (Kadaluarsa/Gagal). Saldo dikembalikan.');
            }
            // 5. Handle Error Lainnya (Too Many Request, Server Error, dll) -> TETAP PENDING
            else {
                $errMsg = $result['responseMessage'] ?? 'Unknown Error';
                return back()->with('warning', "⚠️ Gagal mengecek status ke DANA: [$resCode] $errMsg. Transaksi tetap dalam pengawasan (Pending).");
            }

        } catch (\Exception $e) {
            Log::error('[DANA DISBURSEMENT STATUS CHECK] System Error', ['msg' => $e->getMessage()]);
            return back()->with('error', 'Sistem Error saat mengecek status: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // 1. FUNGSI UNTUK MENAMPILKAN HALAMAN & TABEL RIWAYAT TOP UP
    // =========================================================================
    public function topupCorporatePage()
    {
        // Ambil riwayat khusus tipe TOPUP_B2B
        $transactions = DB::table('dana_transactions')
            ->where('type', 'TOPUP_B2B')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.dana.topup_corporate', compact('transactions'));
    }

    // =========================================================================
    // 2. FUNGSI UNTUK MENGHAPUS RIWAYAT TRANSAKSI (CRUD DELETE)
    // =========================================================================
    public function destroyTopupTransaction($id)
    {
        try {
            DB::table('dana_transactions')->where('id', $id)->delete();
            return back()->with('success', 'Riwayat transaksi berhasil dihapus dari sistem.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus transaksi: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // FUNGSI: PENCARIAN PELANGGAN (AJAX UNTUK SELECT2)
    // =========================================================================
    public function searchPengguna(Request $request)
    {
        $search = $request->get('q');
        
        $query = DB::table('Pengguna')
            // Tambahkan kolom bank_name, bank_account_name, dan bank_account_number
            ->select('id_pengguna', 'nama_lengkap', 'no_wa', 'store_name', 'bank_name', 'bank_account_name', 'bank_account_number')
            ->where('status', 'Aktif');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('no_wa', 'like', "%{$search}%")
                  ->orWhere('store_name', 'like', "%{$search}%");
            });
        }

        $pengguna = $query->limit(20)->get();

        // Format data agar sesuai dengan format yang dibaca oleh Select2 dan Javascript
        $formatted = $pengguna->map(function ($item) {
            $store = $item->store_name ? " | Toko: {$item->store_name}" : "";
            return [
                'id' => $item->id_pengguna,
                'text' => "{$item->nama_lengkap} ({$item->no_wa}){$store}",
                'phone' => $item->no_wa,
                
                // Kirim data bank ke Frontend (UI)
                'bank_account_number' => $item->bank_account_number,
                'bank_account_name' => $item->bank_account_name,
                'bank_name' => $item->bank_name
            ];
        });

        return response()->json($formatted);
    }

    // =========================================================================
    // FUNGSI: MENGHAPUS BANYAK RIWAYAT TRANSAKSI SEKALIGUS (BULK DELETE)
    // =========================================================================
    public function bulkDestroyTransaction(Request $request)
    {
        $ids = $request->input('ids');
        
        if (empty($ids)) {
            return back()->with('error', 'Pilih minimal satu transaksi untuk dihapus.');
        }

        try {
            // Karena tabel Transfer Bank dan Top Up menggunakan tabel yang sama
            DB::table('dana_transactions')->whereIn('id', $ids)->delete();
            return back()->with('success', count($ids) . ' riwayat transaksi berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('[BULK DELETE ERROR] ' . $e->getMessage());
            return back()->with('error', 'Gagal menghapus transaksi: ' . $e->getMessage());
        }
    }

    // ############################ API KHUSUS APLIKASI EXPO MOBILE #######################################endregion

    /**
     * =========================================================================
     * [MESIN BARU] API EXPO: CEK REKENING BANK (BANK ACCOUNT INQUIRY)
     * =========================================================================
     */
    public function apiBankAccountInquiry(Request $request)
    {
        Log::info('LOG LOG: [API BANK INQUIRY] Start', $request->all());

        $request->validate([
            'affiliate_id' => 'required',
            'bank_code'    => 'required|string',
            'account_no'   => 'required|string',
            'amount'       => 'required|numeric|min:10000',
        ]);

        $aff = DB::table('Pengguna')->where('id_pengguna', $request->affiliate_id)->first();
        if (!$aff) {
            return response()->json(['success' => false, 'message' => 'Pengguna tidak ditemukan.'], 404);
        }

        // Persiapan Data DANA
        $merchantDepositAccount = config('services.dana.merchant_deposit_account'); 
        $idToko = config('services.dana.id_toko');

        $timestamp = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $path = '/v1.0/emoney/bank-account-inquiry.htm';
        $refNo = "BNK" . time() . Str::random(4);

        $cekBank = DB::table('dana_bank_codes')->where('bank_code', $request->bank_code)->first();
        $readableBank = $cekBank ? $cekBank->bank_name : $request->bank_code;

        $body = [
            "partnerReferenceNo" => $refNo,
            "customerNumber"     => $merchantDepositAccount, 
            "beneficiaryAccountNumber" => $request->account_no, 
            "amount" => [
                "value"    => number_format((float)$request->amount, 2, '.', ''),
                "currency" => "IDR"
            ],
            "additionalInfo" => [
                "fundType"               => "MERCHANT_WITHDRAW_FOR_CORPORATE",
                "beneficiaryBankCode"    => (string) $request->bank_code,
                "beneficiaryAccountName" => "",
                "merchantId"             => $idToko 
            ]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
        
        try {
            $signature = $this->generateSignature($stringToSign);
            $accessTokenB2B = $this->danaSignature->getAccessToken();

            $headers = [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $accessTokenB2B,
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'ORIGIN'        => config('services.dana.origin'),
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time() . Str::random(6),
                'X-IP-ADDRESS'  => $request->ip() ?? '82.25.62.13',
                'X-DEVICE-ID'   => 'SANCAKA-API-01',
                'CHANNEL-ID'    => '95221'
            ];

            $response = Http::withHeaders($headers)
                ->timeout(60)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            $resCode = trim((string)($result['responseCode'] ?? '500'));

            // Catat log ke database
            DB::table('dana_transactions')->insert([
                'affiliate_id'     => $aff->id_pengguna,
                'type'             => 'BANK_INQUIRY',
                'reference_no'     => $refNo,
                'phone'            => $request->account_no . " (" . $readableBank . ")",
                'amount'           => $request->amount,
                'status'           => ($resCode == '2004200') ? 'SUCCESS' : 'FAILED',
                'response_payload' => json_encode($result),
                'created_at'       => now()
            ]);

            // Evaluasi Jika Sukses
            if ($resCode == '2004200') {
                $bankName = $result['beneficiaryBankShortName'] ?? $result['beneficiaryBankName'] ?? $readableBank;
                $accName  = $result['beneficiaryAccountName'];

                return response()->json([
                    'success' => true,
                    'message' => "Rekening Valid",
                    'data'    => [
                        'account_name' => $accName,
                        'bank_name'    => $bankName,
                        'account_no'   => $request->account_no,
                        'amount'       => $request->amount
                    ]
                ]);
            }

            // Evaluasi Jika Gagal
            $errMsg = $result['responseMessage'] ?? 'Unknown Error';
            if ($resCode == '4034214') $errMsg = "Saldo Merchant DANA Sancaka Tidak Cukup.";
            if ($resCode == '4044201') $errMsg = "Rekening Tidak Ditemukan / Salah Pilih Bank.";
            if ($resCode == '4004201') $errMsg = "Format Kode Bank Tidak Valid.";

            return response()->json([
                'success' => false,
                'message' => $errMsg,
                'code'    => $resCode
            ], 400);

        } catch (\Exception $e) {
            Log::error('[API BANK INQUIRY ERROR] ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Sistem Error saat cek rekening.'
            ], 500);
        }
    }

    /**
     * =========================================================================
     * [MESIN BARU] API EXPO: TRANSFER BANK (DISBURSEMENT B2B)
     * =========================================================================
     */
    public function apiTransferToBank(Request $request)
    {
        Log::info('LOG LOG: [API DANA TRANSFER BANK] Start', $request->all());

        // 1. Validasi Input dari Expo
        $request->validate([
            'affiliate_id' => 'required',
            'bank_code'    => 'required|string',
            'account_no'   => 'required|string',
            'account_name' => 'required|string',
            'amount'       => 'required|numeric|min:10000',
        ]);

        $amount = (int) $request->amount;

        // 2. Cek Pengguna & Saldo
        $aff = DB::table('Pengguna')->where('id_pengguna', $request->affiliate_id)->first();
        
        if (!$aff) {
            return response()->json(['success' => false, 'message' => 'Pengguna tidak ditemukan.'], 404);
        }

        if ($aff->saldo < $amount) {
            return response()->json([
                'success' => false, 
                'message' => 'Saldo pengguna tidak mencukupi. Sisa: Rp ' . number_format($aff->saldo, 0, ',', '.')
            ], 400);
        }

        // Sanitasi nomor HP pengguna
        $customerNumber = preg_replace('/[^0-9]/', '', $aff->no_wa);
        if (substr($customerNumber, 0, 1) === '0') {
            $customerNumber = '62' . substr($customerNumber, 1);
        }

        // =========================================================
        // MENGGUNAKAN TRANSACTION UNTUK MENCEGAH RACE CONDITION
        // =========================================================
        DB::beginTransaction(); 

        try {
            // POTONG SALDO DIAWAL SECARA AMAN
            DB::table('Pengguna')->where('id_pengguna', $aff->id_pengguna)->decrement('saldo', $amount);

            // =========================================================
            // PERSIAPAN DATA DANA
            // =========================================================
            $merchantDepositAccount = config('services.dana.merchant_deposit_account'); 
            $idToko = config('services.dana.id_toko');

            $timestamp  = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
            $path       = '/v1.0/emoney/transfer-bank.htm';
            $partnerRef = "TRF" . time() . Str::random(6);

            $cekBank = DB::table('dana_bank_codes')->where('bank_code', $request->bank_code)->first();
            $readableBank = $cekBank ? $cekBank->bank_name : $request->bank_code;

            $body = [
                "partnerReferenceNo"       => $partnerRef,
                "customerNumber"           => $merchantDepositAccount, 
                "beneficiaryAccountNumber" => (string) $request->account_no,
                "beneficiaryBankCode"      => (string) $request->bank_code,
                "amount" => [
                    "value"    => number_format((float)$amount, 2, '.', ''),
                    "currency" => "IDR"
                ],
                "additionalInfo" => [
                    "fundType"               => "MERCHANT_WITHDRAW_FOR_CORPORATE",
                    "beneficiaryAccountName" => (string) $request->account_name,
                    "merchantId"             => $idToko,
                    "notes"                  => "Transfer ke Bank " . $readableBank,
                    "needNotify"             => true
                ]
            ];

            $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
            $hashedBody = strtolower(hash('sha256', $jsonBody));
            $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
            
            $signature = $this->generateSignature($stringToSign);
            $accessTokenB2B = $this->danaSignature->getAccessToken();

            $headers = [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $accessTokenB2B,
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'ORIGIN'        => config('services.dana.origin'),
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time() . Str::random(6),
                'X-IP-ADDRESS'  => $request->ip() ?? '82.25.62.13',
                'X-DEVICE-ID'   => 'SANCAKA-API-01',
                'CHANNEL-ID'    => '95221'
            ];

            Log::info('LOG LOG: [API DANA TRANSFER BANK] Mengirim Request...', ['body' => $body]);

            $response = Http::withHeaders($headers)
                ->timeout(60)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            $resCode = trim((string)($result['responseCode'] ?? '500'));

            Log::info('LOG LOG: [API DANA TRANSFER BANK] Result:', $result ?? ['raw_body' => $response->body()]);

            // =========================================================
            // EVALUASI RESPON DANA
            // =========================================================
            if ($resCode === '2004300') {
                
                // TRANSAKSI BERHASIL: Simpan log dan COMMIT saldo
                DB::table('dana_transactions')->insert([
                    'affiliate_id'     => $aff->id_pengguna,
                    'type'             => 'TRANSFER_BANK',
                    'reference_no'     => $partnerRef,
                    'phone'            => $request->account_no . " (" . $readableBank . ")",
                    'amount'           => $amount,
                    'status'           => 'SUCCESS',
                    'response_payload' => json_encode($result),
                    'created_at'       => now()
                ]);

                DB::commit(); // PERMANENKAN PEMOTONGAN SALDO

                $danaRef = $result['referenceNo'] ?? '-';
                return response()->json([
                    'success' => true,
                    'message' => "Transfer Berhasil!\nRef DANA: $danaRef\nNominal: Rp " . number_format($amount, 0, ',', '.'),
                    'status'  => 'SUCCESS'
                ]);

            } elseif (in_array($resCode, ['2024300', '4294300', '5004301'])) {
                
                // TRANSAKSI PENDING: Simpan log dan COMMIT saldo sementara
                DB::table('dana_transactions')->insert([
                    'affiliate_id'     => $aff->id_pengguna,
                    'type'             => 'TRANSFER_BANK',
                    'reference_no'     => $partnerRef,
                    'phone'            => $request->account_no . " (" . $readableBank . ")",
                    'amount'           => $amount,
                    'status'           => 'PENDING',
                    'response_payload' => json_encode($result),
                    'created_at'       => now()
                ]);

                DB::commit(); // PERMANENKAN PEMOTONGAN SALDO KARENA PENDING

                return response()->json([
                    'success' => true,
                    'message' => "Transaksi Sedang Diproses (Pending) oleh sistem Bank/DANA.",
                    'status'  => 'PENDING'
                ]);

            } else {
                
                // TRANSAKSI GAGAL: BATALKAN PEMOTONGAN SALDO DENGAN ROLLBACK
                DB::rollBack(); 

                DB::table('dana_transactions')->insert([
                    'affiliate_id'     => $aff->id_pengguna,
                    'type'             => 'TRANSFER_BANK',
                    'reference_no'     => $partnerRef,
                    'phone'            => $request->account_no . " (" . $readableBank . ")",
                    'amount'           => $amount,
                    'status'           => 'FAILED',
                    'response_payload' => json_encode($result),
                    'created_at'       => now()
                ]);

                $errorMsg = $result['responseMessage'] ?? 'Transaksi Gagal';
                Log::error('LOG LOG: [API DANA TRANSFER BANK] Gagal & Refund Auto (Rollback)', ['res' => $result]);
                
                return response()->json([
                    'success' => false,
                    'message' => "Gagal: $errorMsg (Saldo telah dikembalikan)",
                    'status'  => 'FAILED'
                ], 400);
            }

        } catch (\Exception $e) {
            
            // JIKA TERJADI ERROR KONEKSI / FATAL ERROR: BATALKAN PEMOTONGAN SALDO
            DB::rollBack(); 
            
            Log::error('LOG LOG: [API DANA TRANSFER BANK] Exception', ['msg' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Sistem Error saat eksekusi. Saldo telah dikembalikan otomatis.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * =========================================================================
     * [MESIN BARU] API EXPO: AMBIL RIWAYAT TRANSFER & INQUIRY BANK
     * =========================================================================
     */
    public function apiTransferBankHistory(Request $request)
    {
        try {
            // Karena ini halaman khusus Admin, kita langsung tarik semua data riwayat
            // Tarik tipe TRANSFER_BANK dan BANK_INQUIRY dari database
            $transactions = DB::table('dana_transactions')
                ->whereIn('type', ['TRANSFER_BANK', 'BANK_INQUIRY'])
                ->orderBy('created_at', 'desc')
                ->limit(100) // Dibatasi 100 data terakhir agar aplikasi HP tidak lemot
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $transactions
            ]);
            
        } catch (\Exception $e) {
            Log::error('LOG LOG: [API EXPO DANA HISTORY] Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Sistem Error saat mengambil data riwayat.'
            ], 500);
        }
    }

    /**
     * =========================================================================
     * [MESIN BARU] API EXPO: PENCARIAN PENGGUNA (AUTO-FILL BANK)
     * =========================================================================
     */
    public function apiSearchPengguna(Request $request)
    {
        $search = $request->get('q');
        
        $query = DB::table('Pengguna')
            ->select('id_pengguna', 'nama_lengkap', 'no_wa', 'store_name', 'bank_name', 'bank_account_name', 'bank_account_number')
            ->where('status', 'Aktif');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('no_wa', 'like', "%{$search}%")
                  ->orWhere('id_pengguna', 'like', "%{$search}%")
                  ->orWhere('store_name', 'like', "%{$search}%");
            });
        }

        $pengguna = $query->limit(20)->get();

        return response()->json([
            'success' => true,
            'data'    => $pengguna
        ]);
    }

    /**
     * =========================================================================
     * [MESIN BARU] API EXPO: HAPUS RIWAYAT TRANSFER BANK (SINGLE & BULK)
     * =========================================================================
     */
    public function apiDestroyTransferBankHistory(Request $request)
    {
        try {
            $ids = $request->input('ids');

            if (empty($ids) || !is_array($ids)) {
                return response()->json(['success' => false, 'message' => 'Tidak ada data yang dipilih.'], 400);
            }

            // Hapus dari database (dana_transactions)
            $deleted = DB::table('dana_transactions')->whereIn('id', $ids)->delete();

            if ($deleted > 0) {
                return response()->json(['success' => true, 'message' => "$deleted riwayat berhasil dihapus."]);
            }

            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan atau sudah dihapus.'], 404);

        } catch (\Exception $e) {
            Log::error('LOG LOG: [API EXPO DELETE HISTORY] Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Sistem Error saat menghapus data.'], 500);
        }
    }

    /**
     * =========================================================================
     * EKSEKUTOR PAYPAL UNTUK TOP UP (DINAMIS)
     * =========================================================================
     */
    private function createPaymentPayPalTopUp(Transaction $transaction, int $amount)
    {
        try {
            $paypalService = app(\App\Http\Controllers\Api\PayPalGatewayController::class);
            $rate = (float) \App\Models\Api::getValue('PAYPAL_USD_RATE', 'global', 16000);
            $usdAmount = round($amount / $rate, 2);

            $items = [[
                'name' => 'Top Up Saldo Sancaka ' . $transaction->reference_id,
                'quantity' => '1',
                'unit_amount' => [
                    'currency_code' => 'USD',
                    'value' => number_format($usdAmount, 2, '.', '')
                ]
            ]];

            $response = $paypalService->createOrder(
                $items,
                $usdAmount,
                $transaction->reference_id, // custom_id (Penting untuk Webhook)
                'CAPTURE',
                route('paypal.capture.return.topup', ['invoice' => $transaction->reference_id]), // Route Return Khusus Topup
                route('customer.topup.create') // Route Cancel
            );

            $result = $response->getData(true);

            if (isset($result['success']) && $result['success'] === true && !empty($result['approve_url'])) {
                return $result['approve_url'];
            }

            \Illuminate\Support\Facades\Log::error('PayPal Create Order Error (TopUp)', $result);
            return null;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Exception PayPal (TopUp): ' . $e->getMessage());
            return null;
        }
    }

    /**
     * =========================================================================
     * TANGKAP RETURN PAYPAL (TOP UP)
     * =========================================================================
     */
    public function capturePaypalReturn(Request $request, $invoice)
    {
        $token = $request->query('token');

        if (!$token) {
            return redirect()->route('customer.topup.create')->with('error', 'Sesi PayPal tidak valid.');
        }

        try {
            $paypalService = app(\App\Http\Controllers\Api\PayPalGatewayController::class);
            $response = $paypalService->captureOrder($token);
            $result = $response->getData(true);

            if (isset($result['success']) && $result['success'] === true && $result['status'] === 'COMPLETED') {
                
                // Panggil prosesor utama top up untuk menambah saldo & catat mutasi DB
                self::processTopUp($invoice, 'PAID', $result);
                
                return redirect()->route('customer.topup.index')
                    ->with('success', 'Top Up via PayPal Berhasil! Saldo Anda telah bertambah.');
            }

            return redirect()->route('customer.topup.create')->with('error', 'Dana belum berhasil ditarik oleh PayPal.');
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("PayPal Capture Error (TopUp): " . $e->getMessage());
            return redirect()->route('customer.topup.create')->with('error', 'Terjadi kesalahan saat memverifikasi PayPal.');
        }
    }

    /**
     * API Query Payment Status (DANA) untuk AJAX SweetAlert
     */
    public function checkDanaPaymentStatus($orderId)
    {
        try {
            // 1. Pastikan transaksi ada di database lokal
            $transaction = \App\Models\Transaction::where('reference_id', $orderId)->first();
            if (!$transaction) {
                return response()->json(['success' => false, 'message' => 'Transaksi tidak ditemukan di database.'], 404);
            }

            // 2. Memuat konfigurasi dinamis (Sandbox/Prod)
            $this->applyDynamicConfig();

            $merchantId = config('services.dana.merchant_id');
            $partnerId  = config('services.dana.x_partner_id');
            $timestamp  = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
            $path       = '/payment-gateway/v1.0/debit/status.htm';

            // 3. Susun Body Request
            $body = [
                "originalPartnerReferenceNo" => $orderId,
                "serviceCode"                => "54", 
                "merchantId"                 => $merchantId
            ];
            
            $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);

            // 4. Generate Signature
            $accessToken = $this->danaSignature->getAccessToken();
            $signature   = $this->danaSignature->generateSignature('POST', $path, $jsonBody, $timestamp);

            // 5. Susun Headers
            $headers = [
                'Content-Type'  => 'application/json',
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'ORIGIN'        => config('services.dana.origin'),
                'X-PARTNER-ID'  => $partnerId,
                'X-EXTERNAL-ID' => (string) time() . \Illuminate\Support\Str::random(6),
                'CHANNEL-ID'    => '95221'
            ];

            // 6. Eksekusi Request POST ke DANA
            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            $responseCode = $result['responseCode'] ?? 'UNKNOWN';
            
            // 7. Evaluasi Respon untuk AJAX
            if ($responseCode === '2005500') {
                $status = $result['latestTransactionStatus'] ?? null;
                
                if ($status === '00') {
                    // --- PENTING: Update database lokal jika status di DANA Lunas ---
                    if ($transaction->status !== 'success') {
                        // Panggil prosesor utama agar saldo user otomatis ditambah
                        self::processTopUp($orderId, 'PAID', $transaction->amount);
                    }
                    
                    // Kembalikan JSON Sukses ke SweetAlert
                    return response()->json(['success' => true, 'status' => 'PAID']);
                    
                } elseif (in_array($status, ['01', '02'])) {
                    // Status masih pending / belum dibayar
                    return response()->json(['success' => true, 'status' => 'PENDING']);
                    
                } else {
                    // Status gagal, expired, cancel
                    return response()->json(['success' => false, 'message' => 'Transaksi gagal/dibatalkan di sistem DANA.']);
                }
            }

            // Jika gagal menembak API DANA (Error Gateway)
            return response()->json([
                'success' => false, 
                'message' => $result['responseMessage'] ?? 'Terjadi kesalahan dari gateway DANA.'
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('DANA Cek Status AJAX Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Sistem Error: Terjadi gangguan koneksi.'], 500);
        }
    }

   /**
     * API Cancel Order DANA
     * Sesuai Dokumentasi Gapura Payment Gateway API (SNAP Service Code: 57)
     */
    public function cancelDanaPayment($orderId)
    {
        Log::info('LOG LOG: [DANA CANCEL] Memulai proses Cancel untuk Order ID: ' . $orderId);
        
        try {
            $transaction = \App\Models\Transaction::where('reference_id', $orderId)->first();
            if (!$transaction) {
                return response()->json(['success' => false, 'message' => 'Transaksi tidak ditemukan.'], 404);
            }

            $this->applyDynamicConfig();
            $merchantId = config('services.dana.merchant_id');
            $timestamp  = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
            $path       = '/payment-gateway/v1.0/debit/cancel.htm';

            // FORMAT STRICT DANA: Hanya menyertakan parameter wajib sesuai sampel dokumen
            $body = [
                "originalPartnerReferenceNo" => (string) $orderId,
                "merchantId"                 => (string) $merchantId
            ];
            
            $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
            Log::info('LOG LOG: [DANA CANCEL] Request Body: ' . $jsonBody);

            $accessToken = $this->danaSignature->getAccessToken();
            $signature   = $this->danaSignature->generateSignature('POST', $path, $jsonBody, $timestamp);

            // FORMAT STRICT DANA: X-EXTERNAL-ID HARUS MURNI ANGKA (Tanpa huruf/strip)
            $externalId = date('YmdHis') . mt_rand(100000000, 999999999);

            $headers = [
                'Content-Type'  => 'application/json',
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'ORIGIN'        => config('services.dana.origin'),
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) $externalId,
                'CHANNEL-ID'    => '95221'
            ];
            
            Log::info('LOG LOG: [DANA CANCEL] Headers Disiapkan: ', $headers);

            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            Log::info('LOG LOG: [DANA CANCEL] Response dari DANA: ', $result ?? ['raw_body' => $response->body()]);
            
            if (($result['responseCode'] ?? '') === '2005700') {
                $transaction->update(['status' => 'failed']); 
                Log::info('LOG LOG: [DANA CANCEL] Berhasil dibatalkan di DANA dan Database.');
                return response()->json(['success' => true, 'message' => 'Pesanan berhasil dibatalkan di DANA.']);
            }

            return response()->json(['success' => false, 'message' => $result['responseMessage'] ?? 'Gagal membatalkan pesanan.']);
            
        } catch (\Exception $e) {
            Log::error('LOG LOG: [DANA CANCEL] Exception Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Sistem Error: Terjadi kesalahan koneksi.'], 500);
        }
    }

    /**
     * API Refund Order DANA
     * Sesuai Dokumentasi Gapura Payment Gateway API (SNAP Service Code: 58)
     */
    public function refundDanaPayment($orderId)
    {
        Log::info('LOG LOG: [DANA REFUND] Memulai proses Refund untuk Order ID: ' . $orderId);
        
        try {
            $transaction = \App\Models\Transaction::where('reference_id', $orderId)->first();
            if (!$transaction) {
                return response()->json(['success' => false, 'message' => 'Transaksi tidak ditemukan.'], 404);
            }

            $this->applyDynamicConfig();
            $merchantId = config('services.dana.merchant_id');
            $timestamp  = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
            $path       = '/payment-gateway/v1.0/debit/refund.htm';
            
            // FORMAT STRICT DANA: Partner Refund No HARUS MURNI ANGKA (Tanpa huruf)
            $partnerRefundNo = date('YmdHis') . mt_rand(100000000, 999999999); 
            
            $refundAmountValue = number_format((float)$transaction->amount, 2, '.', '');

            // FORMAT STRICT DANA: Hanya field yang benar-benar wajib (Sama persis dengan Request Sample PDF [cite: 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119])
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
            Log::info('LOG LOG: [DANA REFUND] Request Body: ' . $jsonBody);

            $accessToken = $this->danaSignature->getAccessToken();
            $signature   = $this->danaSignature->generateSignature('POST', $path, $jsonBody, $timestamp);

            // FORMAT STRICT DANA: X-EXTERNAL-ID HARUS MURNI ANGKA
            $externalId = date('YmdHis') . mt_rand(100000000, 999999999);

            $headers = [
                'Content-Type'  => 'application/json',
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'ORIGIN'        => config('services.dana.origin'),
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) $externalId,
                'CHANNEL-ID'    => '95221'
            ];
            
            Log::info('LOG LOG: [DANA REFUND] Headers Disiapkan: ', $headers);

            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            Log::info('LOG LOG: [DANA REFUND] Response dari DANA: ', $result ?? ['raw_body' => $response->body()]);
            
            if (($result['responseCode'] ?? '') === '2005800') {
                $transaction->update(['status' => 'refunded']); 
                
                $user = \App\Models\User::find($transaction->user_id);
                if ($user) $user->decrement('saldo', $transaction->amount);

                Log::info('LOG LOG: [DANA REFUND] Berhasil di-refund! Saldo ditarik.');
                return response()->json(['success' => true, 'message' => 'Dana berhasil dikembalikan ke akun DANA pelanggan.']);
            }

            return response()->json(['success' => false, 'message' => $result['responseMessage'] ?? 'Gagal memproses refund.']);
            
        } catch (\Exception $e) {
            Log::error('LOG LOG: [DANA REFUND] Exception Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Sistem Error: Terjadi kesalahan koneksi.'], 500);
        }
    }
}
