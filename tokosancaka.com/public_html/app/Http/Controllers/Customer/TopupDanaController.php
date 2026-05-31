<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Services\DanaSignatureService;
use Exception;
use Carbon\Carbon;
use App\Services\DokuJokulService;

class TopupDanaController extends Controller
{
    protected $danaSignature;

    public function __construct(DanaSignatureService $danaSignature)
    {
        $this->danaSignature = $danaSignature;
        $this->applyDynamicConfig();
    }

    public function create()
    {
        // Ambil riwayat top up khusus user yang sedang login
        $transactions = DB::table('dana_transaction_topup')
            ->where('user_id', Auth::user()->id_pengguna)
            ->orderBy('created_at', 'desc')
            ->paginate(10); // Tampilkan 10 data per halaman

        return view('customer.topup.topup-dana', compact('transactions'));
    }

   /**
     * Memproses pesanan dari Frontend
     */
    public function store(Request $request, DokuJokulService $dokuJokulService)
    {
        Log::info('LOG LOG: ========== [DEBUG DIRECT TOPUP DANA SUBMIT] ==========');

        $validated = $request->validate([
            'dana_number'    => 'required|numeric',
            'amount'         => 'required|numeric|min:10000',
            'payment_method' => 'required|string',
        ]);

        try {
            $user = Auth::user();
            $amount = (int) $validated['amount']; // Contoh: 10000

            // 1. TAMBAHKAN LOGIKA ADMIN FEE DI SINI
            $adminFee = 2000;
            $totalAmount = $amount + $adminFee; // Contoh: 12000

            $danaNumber = $this->normalizePhone($validated['dana_number']);
            $invoiceNumber = 'DANATOPUP-' . strtoupper(Str::random(10));
            $paymentMethod = strtoupper($validated['payment_method']);
            // $signature = $this->generateSignature($stringToSign);
            // $accessTokenB2B = $this->danaSignature->getAccessToken();

            // =========================================================================
            // 1. LOGIKA POTONG SALDO (DI-MIRROR 100% DARI CUSTOMERTOPUP)
            // =========================================================================
            if (in_array($paymentMethod, ['SALDO', 'POTONG SALDO', 'POTONG_SALDO'])) {
                
                // Cek kecukupan saldo user (PAKAI TOTAL AMOUNT)
                if ($user->saldo < $totalAmount) {
                    return back()->with('error', 'Saldo komisi Anda tidak mencukupi. Sisa saldo: Rp ' . number_format($user->saldo, 0, ',', '.'))->withInput();
                }

                // POTONG SALDO DIAWAL (PAKAI TOTAL AMOUNT)
                DB::table('Pengguna')->where('id_pengguna', $user->id_pengguna)->decrement('saldo', $totalAmount);

                // C. IDENTITAS CORPORATE (DISBURSEMENT B2B)
                $merchantDepositAccount = config('services.dana.merchant_deposit_account');
                $idToko = config('services.dana.id_toko');

                $timestamp  = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
                $amountStr  = number_format((float)$amount, 2, '.', '');
                $path       = '/rest/v1.0/emoney/topup';

                // D. SUSUN PAYLOAD SESUAI DOKUMENTASI
                $body = [
                    "partnerReferenceNo" => $invoiceNumber,
                    "customerNumber"     => $danaNumber, // NOMOR HP PENERIMA SALDO
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
                        "fundType"     => "AGENT_TOPUP_FOR_USER_SETTLE", // Wajib untuk Disbursement
                        "chargeTarget" => "MERCHANT", // Tegaskan potong dari saldo Merchant Corporate
                        "merchantId"   => $idToko,
                        "accountId"    => $merchantDepositAccount
                    ]
                ];

                $jsonBody     = json_encode($body, JSON_UNESCAPED_SLASHES);
                $hashedBody   = strtolower(hash('sha256', $jsonBody));
                $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;

                try {
                    // Generate Token B2B & Signature
                    $signature = $this->generateSignature($stringToSign);
                    $accessTokenB2B = $this->danaSignature->getAccessToken();


                    $headers = [
                        'Content-Type'  => 'application/json',
                        'Authorization' => 'Bearer ' . $accessTokenB2B,
                        'X-TIMESTAMP'   => $timestamp,
                        'X-SIGNATURE'   => $signature,
                        'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                        'X-EXTERNAL-ID' => (string) time() . Str::random(6),
                        'CHANNEL-ID'    => '95221',
                        'ORIGIN'        => config('services.dana.origin'),
                    ];

                    Log::info('========== [DANA TOPUP DIRECT START] ==========');
                    Log::info('[DANA REQUEST] Payload:', $body);

                    $response = Http::withHeaders($headers)
                        ->timeout(60)
                        ->withBody($jsonBody, 'application/json')
                        ->post(config('services.dana.base_url') . $path);

                    $result = $response->json();
                    $resCode = $result['responseCode'] ?? ($response->status() == 504 ? '504' : '500');
                    $codeCheck = trim((string)$resCode);

                    Log::info('[DANA RESPONSE] Result:', $result ?? ['raw_body' => $response->body()]);

                    if ($codeCheck === '2003800') { 
                        // SUKSES
                        DB::table('dana_transaction_topup')->insert([
                            'user_id'          => $user->id_pengguna,
                            'target_phone'     => $danaNumber,
                            'amount'           => $amount,
                            'type'             => 'TOPUP_B2B',
                            'reference_id'     => $invoiceNumber,
                            'payment_method'   => $paymentMethod,
                            'status'           => 'SUCCESS',
                            'response_payload' => json_encode($result),
                            'created_at'       => now(),
                            'updated_at'       => now()
                        ]);

                        return redirect()->route('customer.topupdana.success', ['invoice' => $invoiceNumber])
                                         ->with('success', "✅ Top Up Berhasil!\nNo. DANA: $danaNumber\nNominal: Rp " . number_format($amount, 0, ',', '.'));

                    } elseif (in_array($codeCheck, ['504', '4293800', '5003801', '2023800'])) {
                        // STATUS PENDING (Timeout / Too Many Request)
                        DB::table('dana_transaction_topup')->insert([
                            'user_id'          => $user->id_pengguna,
                            'target_phone'     => $danaNumber,
                            'amount'           => $amount,
                            'type'             => 'TOPUP_B2B',
                            'reference_id'     => $invoiceNumber,
                            'payment_method'   => $paymentMethod,
                            'status'           => 'PENDING_DANA',
                            'response_payload' => json_encode($result),
                            'created_at'       => now(),
                            'updated_at'       => now()
                        ]);

                        return back()->with('warning', '⏳ Transaksi sedang diproses (Pending) oleh DANA. Mohon tunggu.');

                    } else {
                        // GAGAL - Kembalikan saldo pengguna
                        DB::table('Pengguna')->where('id_pengguna', $user->id_pengguna)->increment('saldo', $amount);

                        DB::table('dana_transaction_topup')->insert([
                            'user_id'          => $user->id_pengguna,
                            'target_phone'     => $danaNumber,
                            'amount'           => $amount,
                            'type'             => 'TOPUP_B2B',
                            'reference_id'     => $invoiceNumber,
                            'payment_method'   => $paymentMethod,
                            'status'           => 'FAILED_DANA',
                            'response_payload' => json_encode($result),
                            'created_at'       => now(),
                            'updated_at'       => now()
                        ]);

                        $userMsg = match($codeCheck) {
                            '4033814' => 'Saldo Corporate Sancaka tidak mencukupi.',
                            '4033805' => 'Nomor DANA tujuan tidak valid.',
                            '4033818' => 'Nomor DANA tujuan tidak aktif (Inactive).',
                            '4043811' => 'Nomor DANA tujuan tidak ditemukan/diblokir.',
                            default   => "Gagal: " . ($result['responseMessage'] ?? 'Error') . " ($codeCheck)"
                        };

                        return back()->with('error', $userMsg . "\n(Saldo Anda telah dikembalikan)");
                    }

                } catch (\Exception $e) {
                    // Sistem Error - Kembalikan saldo pengguna
                    DB::table('Pengguna')->where('id_pengguna', $user->id_pengguna)->increment('saldo', $amount);
                    Log::error('[DANA TOPUP DIRECT] Exception: ' . $e->getMessage());
                    return back()->with('error', 'Koneksi terputus. Saldo Anda telah dikembalikan.');
                }
            }


            // =========================================================================
            // 2. LOGIKA PAYMENT GATEWAY (BUTUH WEBHOOK TRIPAY / DOKU)
            // =========================================================================
            
            // Simpan ke database dengan status PENDING_PAYMENT
            DB::beginTransaction();
            DB::table('dana_transaction_topup')->insert([
                'user_id'        => $user->id_pengguna,
                'reference_id'   => $invoiceNumber,
                'target_phone'   => $danaNumber,
                'amount'         => $amount,       // 10000
                'admin_fee'      => $adminFee,     // 2000
                'total_amount'   => $totalAmount,  // 12000
                'payment_method' => $paymentMethod, // Simpan dalam format Uppercase
                'status'         => 'PENDING_PAYMENT',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            DB::commit();

            $customerData = [
                'name'  => $user->nama_lengkap ?? 'Customer',
                'email' => $user->email ?? 'customer@tokosancaka.com',
                'phone' => $user->no_wa ?? '081234567890'
            ];

            // PROSES VIA DOKU
            if ($paymentMethod === 'DOKU_JOKUL') {
                Log::info('LOG LOG: Memulai Generate DOKU Jokul untuk ' . $invoiceNumber);

                // UBAH PRICE JADI $totalAmount
                $lineItems = [['name' => 'Top Up DANA ' . $danaNumber, 'price' => $totalAmount, 'quantity' => 1]];
                $successRedirectUrl = route('customer.topupdana.success', ['invoice' => $invoiceNumber]);
                
                // UBAH AMOUNT JADI $totalAmount
                $paymentUrl = $dokuJokulService->createPayment(
                    $invoiceNumber, $totalAmount, $customerData, $lineItems, [], $successRedirectUrl
                );

                if (empty($paymentUrl)) throw new Exception('Gagal membuat link DOKU.');
                return redirect()->away($paymentUrl);
            } 
            // PROSES VIA TRIPAY
            else {
                Log::info('LOG LOG: Memulai Generate Tripay untuk ' . $invoiceNumber);

                $apiKey       = config('tripay.api_key');
                $privateKey   = config('tripay.private_key');
                $merchantCode = config('tripay.merchant_code');
                $mode         = \App\Models\Api::getValue('TRIPAY_MODE', 'global', 'sandbox');

                $payload = [
                    'method'         => $validated['payment_method'], // Kirim sesuai input form aslinya
                    'merchant_ref'   => $invoiceNumber,
                    'amount'         => $totalAmount, // ✅ UBAH MENJADI $totalAmount
                    'customer_name'  => $customerData['name'],
                    'customer_email' => $customerData['email'],
                    'customer_phone' => $customerData['phone'],
                    'order_items'    => [['sku' => 'DANA', 'name' => 'Top Up DANA ' . $danaNumber, 'price' => $totalAmount, 'quantity' => 1]], 
                    'signature'      => hash_hmac('sha256', $merchantCode.$invoiceNumber.$totalAmount, $privateKey), // ✅ SEKARANG SUDAH SINKRON
                ];

                $baseUrl = $mode === 'production' 
                    ? 'https://tripay.co.id/api/transaction/create' 
                    : 'https://tripay.co.id/api-sandbox/transaction/create';

                $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])->post($baseUrl, $payload);
                
                // TAMBAHKAN INI UNTUK DEBUG
                if (!$response->successful()) {
                    Log::error('LOG LOG: Respon Detail Tripay: ' . $response->body());
                }
                
                if ($response->successful() && isset($response->json()['success']) && $response->json()['success'] === true) {
                    $checkoutUrl = $response->json()['data']['checkout_url'];
                    return redirect()->away($checkoutUrl);
                }

                throw new Exception('Gagal membuat transaksi di Tripay.');
            }

        } catch (\Exception $e) {
            Log::error('LOG LOG: Gagal memproses Direct Top Up DANA: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage())->withInput();
        }
    }

   /**
     * =========================================================================
     * HELPER CENTRAL: EKSEKUSI API DANA B2B DISBURSEMENT
     * =========================================================================
     */
    private function executeDanaB2B($merchantRef, $targetPhone, $amount)
    {
        Log::info("LOG LOG: Memulai tembak API DANA untuk Ref: $merchantRef ke nomor: $targetPhone");

        $merchantDepositAccount = config('services.dana.merchant_deposit_account');
        $idToko = config('services.dana.id_toko');
        $timestamp              = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $partnerRef             = "B2BTUP" . time() . Str::random(4);
        $amountStr              = number_format((float)$amount, 2, '.', '');
        $path                   = '/rest/v1.0/emoney/topup';

        $body = [
            "partnerReferenceNo" => $partnerRef,
            "customerNumber"     => $targetPhone,
            "amount" => ["value" => $amountStr, "currency" => "IDR"],
            "feeAmount" => ["value" => "0.00", "currency" => "IDR"],
            "transactionDate" => $timestamp,
            "categoryId"      => "6",
            "additionalInfo"  => [
                "fundType"     => "AGENT_TOPUP_FOR_USER_SETTLE",
                "chargeTarget" => "MERCHANT",
                "merchantId"   => $idToko,
                "accountId"    => $merchantDepositAccount
            ]
        ];

        $jsonBody     = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody   = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;

        try {
            // Gunakan fungsi internal
            $signature = $this->generateSignature($stringToSign);
            $accessTokenB2B = $this->danaSignature->getAccessToken();

            $headers = [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $accessTokenB2B,
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time() . Str::random(6),
                'CHANNEL-ID'    => '95221',
                'ORIGIN'        => config('services.dana.origin'),
            ];

            $response = Http::withHeaders($headers)
                ->timeout(60)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            $codeCheck = trim((string)($result['responseCode'] ?? '500'));

            if ($codeCheck === '2003800') {
                // AMBIL REFERENCE NUMBER DARI DANA
                $danaRef = $result['referenceNo'] ?? null; 

                // UPDATE STATUS DAN SIMPAN DANA REF KE DATABASE
                DB::table('dana_transaction_topup')->where('reference_id', $merchantRef)->update([
                    'status' => 'SUCCESS',
                    'dana_reference' => $danaRef,
                    'response_payload' => json_encode($result), // Simpan seluruh respons agar bisa dicek detailnya
                    'updated_at' => now()
                ]);

                Log::info("LOG LOG: SUCCESS! Saldo DANA berhasil masuk. DANA Ref: " . $danaRef);
                return ['success' => true, 'message' => 'Success'];
            } else {
                $statusUpdate = in_array($codeCheck, ['504', '4293800', '2023800']) ? 'PENDING_DANA' : 'FAILED_DANA';
                DB::table('dana_transaction_topup')->where('reference_id', $merchantRef)->update([
                    'status' => $statusUpdate,
                    'updated_at' => now()
                ]);
                Log::error("LOG LOG: GAGAL TOPUP API DANA. Code: $codeCheck.");
                return ['success' => false, 'message' => $result['responseMessage'] ?? 'Unknown Error DANA'];
            }

        } catch (\Exception $e) {
            Log::error('LOG LOG: Exception saat tembak API DANA: ' . $e->getMessage());
            DB::table('dana_transaction_topup')->where('reference_id', $merchantRef)->update([
                'status' => 'FAILED_SYSTEM',
                'updated_at' => now()
            ]);
            return ['success' => false, 'message' => 'Terjadi kendala pada jaringan sistem ke DANA'];
        }
    }


    /**
     * Webhook Tripay/DOKU. Dipanggil otomatis oleh Payment Gateway saat lunas.
     */
    public function handlePaymentCallback(Request $request)
    {
        Log::info('LOG LOG: ========== WEBHOOK DIRECT TOP UP DANA DITERIMA ==========');
        
        // Asumsi data ini sudah di-parsing sesuai Payment Gateway kamu (Tripay/Doku)
        // Sesuaikan cara pengambilan merchantRef & status dengan format payload Tripay/DOKU kamu
        $merchantRef = $request->input('merchant_ref') ?? $request->input('reference');
        $status      = strtoupper($request->input('status')); // Contoh: 'PAID' atau 'SUCCESS'

        Log::info("LOG LOG: Webhook Info. Ref: $merchantRef, Status: $status");

        if ($status !== 'PAID' && $status !== 'SUCCESS') {
            Log::info("LOG LOG: Status pembayaran belum lunas/gagal, abaikan.");
            return response()->json(['success' => true, 'message' => 'Ignored']);
        }

        $trx = DB::table('dana_transaction_topup')->where('reference_id', $merchantRef)->first();

        if (!$trx) {
            Log::error("LOG LOG: Transaksi $merchantRef tidak ditemukan di dana_transaction_topup");
            return response()->json(['success' => false, 'message' => 'Not Found'], 404);
        }

        if ($trx->status === 'SUCCESS') {
            Log::info("LOG LOG: Transaksi $merchantRef sudah diproses sebelumnya (Idempotent).");
            return response()->json(['success' => true, 'message' => 'Already Processed']);
        }

        // ==============================================================
        // EKSEKUSI TOP UP KE DANA (DISBURSEMENT B2B)
        // ==============================================================
        Log::info("LOG LOG: Pembayaran valid. Memulai Top Up otomatis ke nomor DANA: " . $trx->target_phone);
        
        $merchantDepositAccount = config('services.dana.merchant_deposit_account');
        $idToko                 = config('services.dana.id_toko');
        $timestamp              = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $partnerRef             = "B2BTUP" . time() . Str::random(4);
        $amountStr              = number_format((float)$trx->amount, 2, '.', '');
        $path                   = '/rest/v1.0/emoney/topup';

        $body = [
            "partnerReferenceNo" => $partnerRef,
            "customerNumber"     => $trx->target_phone,
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
                "fundType"     => "AGENT_TOPUP_FOR_USER_SETTLE",
                "chargeTarget" => "MERCHANT",
                "merchantId"   => $idToko,
                "accountId"    => $merchantDepositAccount
            ]
        ];

        $jsonBody     = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody   = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;

        try {
            // Memanggil fungsi generate signature dari service DANA yang kamu buat
            $signature = $this->generateSignature($stringToSign);
            $accessTokenB2B = $this->danaSignature->getAccessToken();

            $headers = [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $accessTokenB2B,
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time() . Str::random(6),
                'CHANNEL-ID'    => '95221',
                'ORIGIN'        => config('services.dana.origin'),
            ];

            Log::info('LOG LOG: Mengirim payload TopUp API DANA', $body);

            $response = Http::withHeaders($headers)
                ->timeout(60)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            $codeCheck = trim((string)($result['responseCode'] ?? '500'));

            Log::info('LOG LOG: Respon DANA API: ', $result);

            if ($codeCheck === '2003800') {
                // UPDATE STATUS JADI SUCCESS
                DB::table('dana_transaction_topup')->where('reference_id', $merchantRef)->update([
                    'status' => 'SUCCESS',
                    'updated_at' => now()
                ]);
                Log::info("LOG LOG: SUCCESS! Saldo DANA berhasil masuk ke nomor {$trx->target_phone}");
            } else {
                // UPDATE STATUS JADI FAILED/PENDING TERGANTUNG KODE
                $statusUpdate = in_array($codeCheck, ['504', '4293800', '2023800']) ? 'PENDING_DANA' : 'FAILED_DANA';
                DB::table('dana_transaction_topup')->where('reference_id', $merchantRef)->update([
                    'status' => $statusUpdate,
                    'updated_at' => now()
                ]);
                Log::error("LOG LOG: GAGAL TOPUP. Code: $codeCheck. Lakukan pengecekan manual.");
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('LOG LOG: Webhook Exception: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Internal Server Error'], 500);
        }
    }


    /**
     * =========================================================================
     * INI FUNGSI BARU UNTUK MENANGKAP WEBHOOK DARI DOKU (VIA DOKU HUB)
     * =========================================================================
     */
    public function handleDokuCallback(array $data)
    {
        Log::info('LOG LOG: ========== WEBHOOK DOKU TOP UP DANA DITERIMA ==========');

        // Parse format bawaan DOKU Jokul dari array $data
        $merchantRef = $data['order']['invoice_number'] ?? null;
        $status      = strtoupper($data['transaction']['status'] ?? ''); 

        Log::info("LOG LOG: Webhook DOKU Info. Ref: $merchantRef, Status: $status");

        if ($status !== 'SUCCESS') {
            Log::info("LOG LOG: Status pembayaran DOKU belum sukses, abaikan.");
            return response()->json(['success' => true, 'message' => 'Ignored - Not Success']);
        }

        $trx = DB::table('dana_transaction_topup')->where('reference_id', $merchantRef)->first();

        if (!$trx) {
            Log::error("LOG LOG: Transaksi $merchantRef tidak ditemukan di dana_transaction_topup");
            return response()->json(['success' => false, 'message' => 'Not Found'], 404);
        }

        if ($trx->status === 'SUCCESS') {
            Log::info("LOG LOG: Transaksi $merchantRef sudah diproses sebelumnya (Idempotent).");
            return response()->json(['success' => true, 'message' => 'Already Processed']);
        }

        // ==============================================================
        // EKSEKUSI TOP UP KE DANA (DISBURSEMENT B2B)
        // ==============================================================
        Log::info("LOG LOG: Pembayaran valid. Memulai Top Up otomatis ke nomor DANA: " . $trx->target_phone);
        
        $merchantDepositAccount = config('services.dana.merchant_deposit_account');
        $idToko                 = config('services.dana.id_toko');
        $timestamp              = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $partnerRef             = "B2BTUP" . time() . Str::random(4);
        $amountStr              = number_format((float)$trx->amount, 2, '.', '');
        $path                   = '/rest/v1.0/emoney/topup';

        $body = [
            "partnerReferenceNo" => $partnerRef,
            "customerNumber"     => $trx->target_phone,
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
                "fundType"     => "AGENT_TOPUP_FOR_USER_SETTLE",
                "chargeTarget" => "MERCHANT",
                "merchantId"   => $idToko,
                "accountId"    => $merchantDepositAccount
            ]
        ];

        $jsonBody     = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody   = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;

        try {
            // Memanggil fungsi generate signature dari service DANA yang kamu buat
            $signature = $this->generateSignature($stringToSign);
            $accessTokenB2B = $this->danaSignature->getAccessToken();

            $headers = [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $accessTokenB2B,
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time() . Str::random(6),
                'CHANNEL-ID'    => '95221',
                'ORIGIN'        => config('services.dana.origin'),
            ];

            Log::info('LOG LOG: Mengirim payload TopUp API DANA via Webhook DOKU', $body);

            $response = Http::withHeaders($headers)
                ->timeout(60)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            $codeCheck = trim((string)($result['responseCode'] ?? '500'));

            Log::info('LOG LOG: Respon DANA API: ', $result);

            if ($codeCheck === '2003800') {
                // UPDATE STATUS JADI SUCCESS
                DB::table('dana_transaction_topup')->where('reference_id', $merchantRef)->update([
                    'status' => 'SUCCESS',
                    'updated_at' => now()
                ]);
                Log::info("LOG LOG: SUCCESS! Saldo DANA berhasil masuk ke nomor {$trx->target_phone}");
            } else {
                // UPDATE STATUS JADI FAILED/PENDING TERGANTUNG KODE
                $statusUpdate = in_array($codeCheck, ['504', '4293800', '2023800']) ? 'PENDING_DANA' : 'FAILED_DANA';
                DB::table('dana_transaction_topup')->where('reference_id', $merchantRef)->update([
                    'status' => $statusUpdate,
                    'updated_at' => now()
                ]);
                Log::error("LOG LOG: GAGAL TOPUP. Code: $codeCheck. Lakukan pengecekan manual.");
            }

            return response()->json(['success' => true, 'message' => 'Top Up DANA Processed']);

        } catch (\Exception $e) {
            Log::error('LOG LOG: DOKU Webhook Exception: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Internal Server Error'], 500);
        }
    }


    private function normalizePhone($phone) 
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (substr($phone, 0, 2) == '08') return '62' . substr($phone, 1);
        if (substr($phone, 0, 1) == '8') return '62' . $phone;
        return $phone;
    }

   /**
     * =========================================================================
     * FUNGSI CEK STATUS TOP UP LANGSUNG KE API DANA (REVISI ENDPOINT TOPUP-STATUS)
     * =========================================================================
     */
    public function checkStatus(Request $request)
    {
        $request->validate(['reference_id' => 'required']);
        
        $trx = DB::table('dana_transaction_topup')->where('reference_id', $request->reference_id)->first();

        if (!$trx) {
            return back()->with('error', 'Data transaksi tidak ditemukan di database topup.');
        }

        if (in_array($trx->status, ['SUCCESS', 'FAILED_DANA', 'FAILED_SYSTEM', 'FAILED'])) {
            return back()->with('warning', 'Transaksi ini sudah berstatus final (' . $trx->status . '). Tidak perlu dicek lagi.');
        }

        // 1. PASTIKAN PATH DAN BODY SESUAI DOKUMENTASI DANA TOP UP
        $timestamp = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $path      = '/rest/v1.0/emoney/topup-status'; // <-- REVISI DI SINI
        
        $body = [
            "originalPartnerReferenceNo" => $trx->reference_id,
            "serviceCode"                => "38" // Wajib "38" untuk Top Up sesuai dokumen
        ];

        $jsonBody     = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody   = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;

        try {
            // Gunakan fungsi internal
            $signature = $this->generateSignature($stringToSign);
            $accessTokenB2B = $this->danaSignature->getAccessToken();

            $headers = [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $accessTokenB2B,
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time() . Str::random(6),
                'CHANNEL-ID'    => '95221',
                'ORIGIN'        => config('services.dana.origin'),
            ];

            $response = Http::withHeaders($headers)
                ->timeout(60)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            $resCode = trim((string)($result['responseCode'] ?? '500'));

            Log::info("LOG LOG: Hasil Cek Status DANA untuk {$trx->reference_id}: ", $result ?? ['raw_body' => $response->body()]);

            // 3. Evaluasi Response Code (2003900 adalah kode sukses untuk pengecekan)
            if ($resCode === '2003900') {
                $statusDana = $result['latestTransactionStatus'] ?? null;

                // 00 - TRANSAKSI SUKSES (Uang Masuk)
                if ($statusDana === '00') {
                    DB::table('dana_transaction_topup')->where('reference_id', $trx->reference_id)->update([
                        'status' => 'SUCCESS',
                        'updated_at' => now()
                    ]);
                    return back()->with('success', 'Status DANA: SUKSES (Dana telah masuk ke tujuan).');
                } 
                // 01, 02, 03 - TRANSAKSI PENDING (Masih Proses)
                elseif (in_array($statusDana, ['01', '02', '03'])) {
                    return back()->with('warning', 'Status DANA: PENDING (Masih diproses sistem bank/DANA). Silakan cek lagi nanti.');
                } 
                // 04, 05, 06, 07 - TRANSAKSI GAGAL FINAL
                elseif (in_array($statusDana, ['04', '05', '06', '07'])) {
                    
                    DB::table('dana_transaction_topup')->where('reference_id', $trx->reference_id)->update([
                        'status' => 'FAILED_DANA',
                        'updated_at' => now()
                    ]);
                    
                    // Eksekusi Auto-Refund jika metode bayarnya adalah Potong Saldo
                    if (in_array($trx->payment_method, ['POTONG SALDO', 'SALDO', 'POTONG_SALDO'])) {
                        DB::table('Pengguna')->where('id_pengguna', $trx->user_id)->increment('saldo', $trx->amount);
                        Log::info("LOG LOG: Auto-Refund Rp {$trx->amount} berhasil dikembalikan ke User ID {$trx->user_id} karena transaksi GAGAL FINAL.");
                    }
                    
                    $desc = $result['transactionStatusDesc'] ?? 'Transaksi Gagal / Ditolak';
                    return back()->with('error', "Status DANA: GAGAL ($desc). Saldo Anda telah dikembalikan otomatis.");
                }
            } 
            // 4. Jika Transaksi Tidak Ditemukan (4043901) = Berarti Gagal / Expired
            elseif ($resCode === '4043901') {
                DB::table('dana_transaction_topup')->where('reference_id', $trx->reference_id)->update([
                    'status' => 'FAILED_DANA',
                    'updated_at' => now()
                ]);
                
                // Eksekusi Auto-Refund jika potong saldo
                if (in_array($trx->payment_method, ['POTONG SALDO', 'SALDO', 'POTONG_SALDO'])) {
                    DB::table('Pengguna')->where('id_pengguna', $trx->user_id)->increment('saldo', $trx->amount);
                }
                return back()->with('error', 'Status DANA: GAGAL (Transaksi tidak ditemukan / Kadaluarsa). Saldo telah dikembalikan.');
            } 
            // 5. Gangguan Server DANA / Timeout
            else {
                $errMsg = $result['responseMessage'] ?? 'Unknown Error';
                return back()->with('warning', "Gagal mengecek status ke DANA: [$resCode - $errMsg]. Sistem DANA sedang sibuk.");
            }

        } catch (\Exception $e) {
            Log::error('LOG LOG: Exception cek status DANA: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan jaringan saat mengecek status ke DANA.');
        }
    }

    // =========================================================================
    // HELPER 1: DINAMISASI CONFIG DANA BERDASARKAN DATABASE
    // =========================================================================
    private function applyDynamicConfig()
    {
        $danaMode = \App\Models\Api::getValue('dana_production_mode', 'global', '0');
        $isProduction = ($danaMode == '1');

        if ($isProduction) {
            config([
                'services.dana.dana_env'      => 'PRODUCTION',
                'services.dana.base_url'      => 'https://api.saas.dana.id',
                'services.dana.merchant_id'   => \App\Models\Api::getValue('dana_prod_merchant_id', 'production', env('DANA_PROD_MERCHANT_ID')),
                'services.dana.client_id'     => \App\Models\Api::getValue('dana_prod_client_id', 'production', env('DANA_PROD_CLIENT_ID')),
                'services.dana.x_partner_id'  => \App\Models\Api::getValue('dana_prod_client_id', 'production', env('DANA_PROD_CLIENT_ID')),
                'services.dana.private_key'   => \App\Models\Api::getValue('dana_prod_private_key', 'production', env('DANA_PROD_PRIVATE_KEY')),
                'services.dana.public_key'    => \App\Models\Api::getValue('dana_prod_public_key', 'production'),
                'services.dana.client_secret' => \App\Models\Api::getValue('dana_prod_client_secret', 'production', env('DANA_PROD_CLIENT_SECRET')),
            ]);
        } else {
            config([
                'services.dana.dana_env'      => 'SANDBOX',
                'services.dana.base_url'      => 'https://api.sandbox.dana.id',
                'services.dana.merchant_id'   => \App\Models\Api::getValue('dana_sandbox_merchant_id', 'sandbox', env('DANA_MERCHANT_ID')),
                'services.dana.client_id'     => \App\Models\Api::getValue('dana_sandbox_client_id', 'sandbox', env('DANA_X_PARTNER_ID')),
                'services.dana.x_partner_id'  => \App\Models\Api::getValue('dana_sandbox_client_id', 'sandbox', env('DANA_X_PARTNER_ID')),
                'services.dana.private_key'   => \App\Models\Api::getValue('dana_sandbox_private_key', 'sandbox', env('DANA_PRIVATE_KEY')),
                'services.dana.public_key'    => \App\Models\Api::getValue('dana_sandbox_public_key', 'sandbox'),
                'services.dana.client_secret' => \App\Models\Api::getValue('dana_sandbox_client_secret', 'sandbox', env('DANA_CLIENT_SECRET')),
            ]);
        }
    }

    // =========================================================================
    // HELPER 2: GENERATE SIGNATURE OPENSSL (SAMA PERSIS DENGAN TOPUPCONTROLLER)
    // =========================================================================
    private function generateSignature($stringToSign) {
        $rawKey = config('services.dana.private_key');
        if (empty($rawKey)) throw new \Exception("Private Key kosong.");

        $cleanKey = str_replace(["-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----", "\r", "\n", " ", "\"", "'"], "", $rawKey);
        $formattedKey = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($cleanKey, 64, "\n", true) . "\n-----END PRIVATE KEY-----";

        $privateKeyResource = openssl_pkey_get_private($formattedKey);
        if (!$privateKeyResource) throw new \Exception("Format Private Key salah.");

        $binarySignature = "";
        $isSignSuccess = openssl_sign($stringToSign, $binarySignature, $privateKeyResource, OPENSSL_ALGO_SHA256);

        if (!$isSignSuccess) throw new \Exception("OpenSSL Sign Failed.");

        return base64_encode($binarySignature);
    }

  // =========================================================================
    // FUNGSI: MENGHAPUS BANYAK RIWAYAT TRANSAKSI SEKALIGUS (BULK DELETE)
    // =========================================================================
    public function bulkDestroyTransaction(Request $request)
    {
        Log::info('LOG LOG: ========== [START BULK DELETE DANA TOPUP] ==========');
        
        $ids = $request->input('ids');
        Log::info('LOG LOG: Raw input IDs yang diterima dari request:', ['raw_ids' => $ids]);

        // 1. Jika data kosong sama sekali, kembalikan error
        if (empty($ids)) {
            Log::warning('LOG LOG: Bulk Delete dibatalkan karena Input IDs kosong.');
            return back()->with('error', 'Pilih minimal satu transaksi untuk dihapus.');
        }

        // 2. Jika data yang masuk berupa teks gabungan (contoh: "1,2,3"), pecah menjadi array
        if (is_string($ids)) {
            Log::info('LOG LOG: Input terdeteksi sebagai string tunggal. Memecah string menjadi array...');
            $ids = explode(',', $ids);
        }

        // 3. Jika data masuk sebagai array tapi elemen pertamanya adalah teks gabungan ["1,2,3"]
        if (is_array($ids) && count($ids) === 1 && strpos($ids[0], ',') !== false) {
            Log::info('LOG LOG: Input terdeteksi sebagai array dengan 1 elemen string gabungan. Memecah string...');
            $ids = explode(',', $ids[0]);
        }

        // 4. Bersihkan spasi kosong dari array ID
        $cleanIds = array_filter(array_map('trim', $ids));
        Log::info('LOG LOG: Data IDs setelah diproses dan dibersihkan (Clean IDs):', ['clean_ids' => $cleanIds]);

        try {
            // EKSEKUSI HAPUS KE TABEL YANG BENAR & HITUNG YANG TERHAPUS
            Log::info('LOG LOG: Mulai mengeksekusi query DELETE pada tabel dana_transaction_topup...');
            $deletedCount = DB::table('dana_transaction_topup')->whereIn('id', $cleanIds)->delete();

            // 5. Cek apakah ada data yang benar-benar terhapus
            if ($deletedCount > 0) {
                Log::info("LOG LOG: SUCCESS! Berhasil menghapus $deletedCount baris data dari database.");
                Log::info('LOG LOG: ========== [END BULK DELETE DANA TOPUP] ==========');
                return back()->with('success', $deletedCount . ' riwayat transaksi berhasil dihapus secara permanen.');
            } else {
                Log::warning('LOG LOG: WARNING! Query dieksekusi tanpa error, tetapi 0 data terhapus. (ID kemungkinan tidak cocok dengan data di tabel dana_transaction_topup).');
                Log::info('LOG LOG: ========== [END BULK DELETE DANA TOPUP] ==========');
                return back()->with('error', 'Data gagal dihapus. Kemungkinan data sudah tidak ada di database.');
            }

        } catch (\Exception $e) {
            Log::error('LOG LOG: [BULK DELETE ERROR] Terjadi exception saat menghapus: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            Log::info('LOG LOG: ========== [END BULK DELETE DANA TOPUP WITH ERROR] ==========');
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // FUNGSI UNTUK MENGHAPUS RIWAYAT TRANSAKSI SATUAN (CRUD DELETE)
    // =========================================================================
    public function destroyTopupTransaction($id)
    {
        try {
            // PERBAIKAN: Ubah nama tabel menjadi 'dana_transaction_topup'
            DB::table('dana_transaction_topup')->where('id', $id)->delete();
            
            return back()->with('success', 'Riwayat transaksi berhasil dihapus dari sistem.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus transaksi: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // =========================================================================
    // [MESIN BARU - KHUSUS EXPO/API] MERESPONS DALAM FORMAT JSON
    // =========================================================================
    // =========================================================================


  // =========================================================================
    // 1. GET TRANSAKSI DENGAN FILTER & PROTEKSI ROLE (ADMIN VS USER)
    // =========================================================================
    public function apiGetTransactions(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Sesi berakhir.'], 401);
        }

        // Ambil parameter filter dari request (jika ada)
        $filterStatus = $request->input('status', 'SEMUA');
        $filterTanggal = $request->input('tanggal', null);

        // Mulai Query
        $query = DB::table('dana_transaction_topup')->orderBy('created_at', 'desc');

        // PROTEKSI SUPER KETAT: Cek apakah user adalah Admin
        $isAdmin = (strtolower($user->role) === 'admin' || (string)$user->id_pengguna === '4');

        if (!$isAdmin) {
            // JIKA BUKAN ADMIN: Kunci query HANYA untuk id_pengguna milik user yang login
            $query->where('user_id', $user->id_pengguna);
        }

        // Terapkan Filter Status
        if ($filterStatus !== 'SEMUA') {
            if ($filterStatus === 'PENDING') {
                $query->whereIn('status', ['PENDING_PAYMENT', 'PENDING_DANA']);
            } elseif ($filterStatus === 'FAILED') {
                $query->whereIn('status', ['FAILED_DANA', 'FAILED_SYSTEM', 'FAILED']);
            } else {
                $query->where('status', $filterStatus);
            }
        }

        // (Opsional) Terapkan Filter Tanggal YYYY-MM-DD
        if ($filterTanggal) {
            $query->whereDate('created_at', $filterTanggal);
        }

        // Ambil data (Bisa pakai paginate(15) kalau datanya mau di-load bertahap)
        $transactions = $query->get();

        return response()->json([
            'success' => true,
            'is_admin' => $isAdmin, // Kirim flag ke frontend agar frontend tau ini admin atau bukan
            'data' => $transactions
        ]);
    }

    // =========================================================================
    // 2. DELETE TRANSAKSI SATUAN DENGAN PROTEKSI KEPEMILIKAN
    // =========================================================================
    public function apiDestroyTopupTransaction(Request $request, $id)
    {
        try {
            $user = $request->user();
            $isAdmin = (strtolower($user->role) === 'admin' || (string)$user->id_pengguna === '4');

            $trx = DB::table('dana_transaction_topup')->where('id', $id)->first();

            if (!$trx) {
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
            }

            // CEK KEPEMILIKAN JIKA BUKAN ADMIN
            if (!$isAdmin && $trx->user_id != $user->id_pengguna) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak! Ini bukan transaksi Anda.'], 403);
            }

            DB::table('dana_transaction_topup')->where('id', $id)->delete();
            return response()->json(['success' => true, 'message' => 'Riwayat transaksi berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus: ' . $e->getMessage()], 500);
        }
    }

    public function apiStore(Request $request, DokuJokulService $dokuJokulService)
    {
        Log::info('LOG LOG: ========== [DEBUG DIRECT TOPUP DANA SUBMIT - API EXPO] ==========');

        $validated = $request->validate([
            'dana_number'    => 'required|numeric',
            'amount'         => 'required|numeric|min:10000',
            'payment_method' => 'required|string',
        ]);

       try {
            // Ambil dari Auth (Web) atau Request (API)
            $user = Auth::user() ?? $request->user();

            if (!$user) {
                return back()->with('error', 'Sesi Anda telah habis. Silakan login kembali.');
            }

            $amount = (int) $validated['amount']; // Contoh: 10000

            // 1. TAMBAHKAN LOGIKA ADMIN FEE DI SINI
            $adminFee = 2000;
            $totalAmount = $amount + $adminFee; // Contoh: 12000

            $danaNumber = $this->normalizePhone($validated['dana_number']);
            $invoiceNumber = 'DANATOPUP-' . strtoupper(Str::random(10));
            $paymentMethod = strtoupper($validated['payment_method']);

            // 1. LOGIKA POTONG SALDO
            if (in_array($paymentMethod, ['SALDO', 'POTONG SALDO', 'POTONG_SALDO'])) {
                
                if ($user->saldo < $totalAmount) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'Saldo komisi Anda tidak mencukupi. Sisa saldo: Rp ' . number_format($user->saldo, 0, ',', '.')
                    ], 400);
                }

                DB::table('Pengguna')->where('id_pengguna', $user->id_pengguna)->decrement('saldo', $totalAmount);

                $merchantDepositAccount = config('services.dana.merchant_deposit_account');
                $idToko = config('services.dana.id_toko');

                $timestamp  = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
                $amountStr  = number_format((float)$amount, 2, '.', '');
                $path       = '/rest/v1.0/emoney/topup';

                $body = [
                    "partnerReferenceNo" => $invoiceNumber,
                    "customerNumber"     => $danaNumber,
                    "amount" => ["value" => $amountStr, "currency" => "IDR"],
                    "feeAmount" => ["value" => "0.00", "currency" => "IDR"],
                    "transactionDate" => $timestamp,
                    "categoryId"      => "6",
                    "additionalInfo"  => [
                        "fundType"     => "AGENT_TOPUP_FOR_USER_SETTLE",
                        "chargeTarget" => "MERCHANT",
                        "merchantId"   => $idToko,
                        "accountId"    => $merchantDepositAccount
                    ]
                ];

                $jsonBody     = json_encode($body, JSON_UNESCAPED_SLASHES);
                $hashedBody   = strtolower(hash('sha256', $jsonBody));
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
                        'X-EXTERNAL-ID' => (string) time() . Str::random(6),
                        'CHANNEL-ID'    => '95221',
                        'ORIGIN'        => config('services.dana.origin'),
                    ];

                    Log::info('========== [DANA TOPUP DIRECT START - API] ==========');
                    Log::info('[DANA REQUEST] Payload:', $body);

                    $response = Http::withHeaders($headers)
                        ->timeout(60)
                        ->withBody($jsonBody, 'application/json')
                        ->post(config('services.dana.base_url') . $path);

                    $result = $response->json();
                    $resCode = $result['responseCode'] ?? ($response->status() == 504 ? '504' : '500');
                    $codeCheck = trim((string)$resCode);

                    Log::info('[DANA RESPONSE] Result:', $result ?? ['raw_body' => $response->body()]);

                    if ($codeCheck === '2003800') { 
                        DB::table('dana_transaction_topup')->insert([
                            'user_id'          => $user->id_pengguna,
                            'target_phone'     => $danaNumber,
                            'amount'           => $amount,
                            'type'             => 'TOPUP_B2B',
                            'reference_id'     => $invoiceNumber,
                            'dana_reference'   => $result['referenceNo'] ?? null,
                            'payment_method'   => $paymentMethod,
                            'status'           => 'SUCCESS',
                            'response_payload' => json_encode($result),
                            'created_at'       => now(),
                            'updated_at'       => now()
                        ]);

                        return response()->json([
                            'success' => true,
                            'message' => "Top Up Berhasil!",
                            'data' => [
                                'invoice' => $invoiceNumber,
                                'dana_number' => $danaNumber,
                                'amount' => $amount
                            ]
                        ]);

                    } elseif (in_array($codeCheck, ['504', '4293800', '5003801', '2023800'])) {
                        DB::table('dana_transaction_topup')->insert([
                            'user_id'          => $user->id_pengguna,
                            'target_phone'     => $danaNumber,
                            'amount'           => $amount,
                            'type'             => 'TOPUP_B2B',
                            'reference_id'     => $invoiceNumber,
                            'payment_method'   => $paymentMethod,
                            'status'           => 'PENDING_DANA',
                            'response_payload' => json_encode($result),
                            'created_at'       => now(),
                            'updated_at'       => now()
                        ]);

                        return response()->json([
                            'success' => true, 
                            'message' => 'Transaksi sedang diproses (Pending) oleh DANA. Mohon tunggu.',
                            'status' => 'PENDING_DANA'
                        ]);

                    } else {
                        DB::table('Pengguna')->where('id_pengguna', $user->id_pengguna)->increment('saldo', $amount);

                        DB::table('dana_transaction_topup')->insert([
                            'user_id'          => $user->id_pengguna,
                            'target_phone'     => $danaNumber,
                            'amount'           => $amount,
                            'type'             => 'TOPUP_B2B',
                            'reference_id'     => $invoiceNumber,
                            'payment_method'   => $paymentMethod,
                            'status'           => 'FAILED_DANA',
                            'response_payload' => json_encode($result),
                            'created_at'       => now(),
                            'updated_at'       => now()
                        ]);

                        $userMsg = match($codeCheck) {
                            '4033814' => 'Saldo Corporate Sancaka tidak mencukupi.',
                            '4033805' => 'Nomor DANA tujuan tidak valid.',
                            '4033818' => 'Nomor DANA tujuan tidak aktif (Inactive).',
                            '4043811' => 'Nomor DANA tujuan tidak ditemukan/diblokir.',
                            default   => "Gagal: " . ($result['responseMessage'] ?? 'Error') . " ($codeCheck)"
                        };

                        return response()->json([
                            'success' => false, 
                            'message' => $userMsg . " (Saldo Anda telah dikembalikan)"
                        ], 400);
                    }

                } catch (\Exception $e) {
                    DB::table('Pengguna')->where('id_pengguna', $user->id_pengguna)->increment('saldo', $amount);
                    Log::error('[DANA TOPUP DIRECT API] Exception: ' . $e->getMessage());
                    return response()->json(['success' => false, 'message' => 'Koneksi terputus. Saldo Anda telah dikembalikan.'], 500);
                }
            }


            // 2. LOGIKA PAYMENT GATEWAY (BUTUH WEBHOOK TRIPAY / DOKU)
            DB::beginTransaction();
            DB::table('dana_transaction_topup')->insert([
                'user_id'        => $user->id_pengguna,
                'reference_id'   => $invoiceNumber,
                'target_phone'   => $danaNumber,
                'amount'         => $amount,
                'admin_fee'      => $adminFee,
                'total_amount'   => $totalAmount,
                'payment_method' => $paymentMethod,
                'status'         => 'PENDING_PAYMENT',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            DB::commit();

            $customerData = [
                'name'  => $user->nama_lengkap ?? 'Customer',
                'email' => $user->email ?? 'customer@tokosancaka.com',
                'phone' => $user->no_wa ?? '081234567890'
            ];

            // PROSES VIA DOKU
            if ($paymentMethod === 'DOKU_JOKUL') {
                Log::info('LOG LOG: Memulai Generate DOKU Jokul API untuk ' . $invoiceNumber);

                $lineItems = [['name' => 'Top Up DANA ' . $danaNumber, 'price' => $totalAmount, 'quantity' => 1]];
                $successRedirectUrl = route('customer.topupdana.success', ['invoice' => $invoiceNumber]);
                
                $paymentUrl = $dokuJokulService->createPayment(
                    $invoiceNumber, $totalAmount, $customerData, $lineItems, [], $successRedirectUrl
                );

                if (empty($paymentUrl)) throw new Exception('Gagal membuat link DOKU.');
                
                return response()->json([
                    'success' => true, 
                    'payment_url' => $paymentUrl, 
                    'invoice' => $invoiceNumber
                ]);
            } 
            // PROSES VIA TRIPAY
            else {
                Log::info('LOG LOG: Memulai Generate Tripay API untuk ' . $invoiceNumber);

                $apiKey       = config('tripay.api_key');
                $privateKey   = config('tripay.private_key');
                $merchantCode = config('tripay.merchant_code');
                $mode         = \App\Models\Api::getValue('TRIPAY_MODE', 'global', 'sandbox');

                $payload = [
                    'method'         => $validated['payment_method'],
                    'merchant_ref'   => $invoiceNumber,
                    'amount'         => $totalAmount,
                    'customer_name'  => $customerData['name'],
                    'customer_email' => $customerData['email'],
                    'customer_phone' => $customerData['phone'],
                    'order_items'    => [['sku' => 'DANA', 'name' => 'Top Up DANA ' . $danaNumber, 'price' => $totalAmount, 'quantity' => 1]], 
                    'signature'      => hash_hmac('sha256', $merchantCode.$invoiceNumber.$totalAmount, $privateKey),
                ];

                $baseUrl = $mode === 'production' 
                    ? 'https://tripay.co.id/api/transaction/create' 
                    : 'https://tripay.co.id/api-sandbox/transaction/create';

                $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])->post($baseUrl, $payload);
                
                if (!$response->successful()) {
                    Log::error('LOG LOG: Respon Detail Tripay API: ' . $response->body());
                }
                
                if ($response->successful() && isset($response->json()['success']) && $response->json()['success'] === true) {
                    $checkoutUrl = $response->json()['data']['checkout_url'];
                    return response()->json([
                        'success' => true, 
                        'payment_url' => $checkoutUrl, 
                        'invoice' => $invoiceNumber
                    ]);
                }

                throw new Exception('Gagal membuat transaksi di Tripay.');
            }

        } catch (\Exception $e) {
            Log::error('LOG LOG: Gagal memproses Direct Top Up DANA API: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiCheckStatus(Request $request)
    {
        $request->validate(['reference_id' => 'required']);
        
        $trx = DB::table('dana_transaction_topup')->where('reference_id', $request->reference_id)->first();

        if (!$trx) {
            return response()->json(['success' => false, 'message' => 'Data transaksi tidak ditemukan di database topup.'], 404);
        }

        if (in_array($trx->status, ['SUCCESS', 'FAILED_DANA', 'FAILED_SYSTEM', 'FAILED'])) {
            return response()->json([
                'success' => true, 
                'message' => 'Transaksi ini sudah berstatus final.', 
                'status' => $trx->status
            ]);
        }

        $timestamp = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $path      = '/rest/v1.0/emoney/topup-status';
        
        $body = [
            "originalPartnerReferenceNo" => $trx->reference_id,
            "serviceCode"                => "38"
        ];

        $jsonBody     = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody   = strtolower(hash('sha256', $jsonBody));
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
                'X-EXTERNAL-ID' => (string) time() . Str::random(6),
                'CHANNEL-ID'    => '95221',
                'ORIGIN'        => config('services.dana.origin'),
            ];

            $response = Http::withHeaders($headers)
                ->timeout(60)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();
            $resCode = trim((string)($result['responseCode'] ?? '500'));

            Log::info("LOG LOG: Hasil Cek Status DANA API untuk {$trx->reference_id}: ", $result ?? ['raw_body' => $response->body()]);

            if ($resCode === '2003900') {
                $statusDana = $result['latestTransactionStatus'] ?? null;

                if ($statusDana === '00') {
                    DB::table('dana_transaction_topup')->where('reference_id', $trx->reference_id)->update([
                        'status' => 'SUCCESS',
                        'updated_at' => now()
                    ]);
                    return response()->json(['success' => true, 'message' => 'Status DANA: SUKSES (Dana telah masuk ke tujuan).', 'status' => 'SUCCESS']);
                } 
                elseif (in_array($statusDana, ['01', '02', '03'])) {
                    return response()->json(['success' => true, 'message' => 'Status DANA: PENDING (Masih diproses sistem bank/DANA).', 'status' => 'PENDING']);
                } 
                elseif (in_array($statusDana, ['04', '05', '06', '07'])) {
                    DB::table('dana_transaction_topup')->where('reference_id', $trx->reference_id)->update([
                        'status' => 'FAILED_DANA',
                        'updated_at' => now()
                    ]);
                    
                    if (in_array($trx->payment_method, ['POTONG SALDO', 'SALDO', 'POTONG_SALDO'])) {
                        DB::table('Pengguna')->where('id_pengguna', $trx->user_id)->increment('saldo', $trx->amount);
                        Log::info("LOG LOG: Auto-Refund Rp {$trx->amount} berhasil dikembalikan ke User ID {$trx->user_id} karena transaksi GAGAL FINAL.");
                    }
                    
                    $desc = $result['transactionStatusDesc'] ?? 'Transaksi Gagal / Ditolak';
                    return response()->json(['success' => false, 'message' => "Status DANA: GAGAL ($desc). Saldo Anda telah dikembalikan otomatis.", 'status' => 'FAILED_DANA']);
                }
            } 
            elseif ($resCode === '4043901') {
                DB::table('dana_transaction_topup')->where('reference_id', $trx->reference_id)->update([
                    'status' => 'FAILED_DANA',
                    'updated_at' => now()
                ]);
                
                if (in_array($trx->payment_method, ['POTONG SALDO', 'SALDO', 'POTONG_SALDO'])) {
                    DB::table('Pengguna')->where('id_pengguna', $trx->user_id)->increment('saldo', $trx->amount);
                }
                return response()->json(['success' => false, 'message' => 'Status DANA: GAGAL (Transaksi tidak ditemukan / Kadaluarsa). Saldo telah dikembalikan.', 'status' => 'FAILED_DANA']);
            } 
            else {
                $errMsg = $result['responseMessage'] ?? 'Unknown Error';
                return response()->json(['success' => false, 'message' => "Gagal mengecek status ke DANA: [$resCode - $errMsg]. Sistem DANA sedang sibuk."]);
            }

        } catch (\Exception $e) {
            Log::error('LOG LOG: Exception cek status DANA API: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan jaringan saat mengecek status ke DANA.'], 500);
        }
    }

    public function apiBulkDestroyTransaction(Request $request)
    {
        Log::info('LOG LOG: ========== [START BULK DELETE DANA TOPUP API] ==========');
        
        $ids = $request->input('ids');

        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'Pilih minimal satu transaksi untuk dihapus.'], 400);
        }

        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        if (is_array($ids) && count($ids) === 1 && strpos($ids[0], ',') !== false) {
            $ids = explode(',', $ids[0]);
        }

        $cleanIds = array_filter(array_map('trim', $ids));

        try {
            $deletedCount = DB::table('dana_transaction_topup')->whereIn('id', $cleanIds)->delete();

            if ($deletedCount > 0) {
                return response()->json(['success' => true, 'message' => $deletedCount . ' riwayat transaksi berhasil dihapus secara permanen.']);
            } else {
                return response()->json(['success' => false, 'message' => 'Data gagal dihapus. Kemungkinan data sudah tidak ada di database.'], 404);
            }

        } catch (\Exception $e) {
            Log::error('LOG LOG: [BULK DELETE API ERROR]: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()], 500);
        }
    }

}