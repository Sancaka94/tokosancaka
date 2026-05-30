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

    public function store(Request $request, DokuJokulService $dokuJokulService)
    {
        Log::info('LOG LOG: ========== [DEBUG DIRECT TOPUP DANA SUBMIT] ==========');
        Log::info('LOG LOG: PAYLOAD (BODY): ', $request->all());

        $validated = $request->validate([
            'dana_number'    => 'required|numeric',
            'amount'         => 'required|numeric|min:10000',
            'payment_method' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $user = Auth::user();
            $amount = (int) $validated['amount'];
            $danaNumber = $this->normalizePhone($validated['dana_number']);
            $invoiceNumber = 'DANATOPUP-' . strtoupper(Str::random(10));
            $paymentMethod = strtoupper($validated['payment_method']);

            // ====================================================================
            // LOGIKA 1: PEMBAYARAN VIA POTONG SALDO (TANPA WEBHOOK)
            // ====================================================================
            if (in_array($paymentMethod, ['SALDO', 'POTONG SALDO', 'POTONG_SALDO'])) {
                
                // Cek Saldo User
                if ($user->saldo < $amount) {
                    DB::rollBack();
                    return back()->with('error', 'Saldo Anda tidak mencukupi. Sisa saldo: Rp ' . number_format($user->saldo, 0, ',', '.'))->withInput();
                }

                // Catat ke DB (Status PROCESSING)
                DB::table('dana_transaction_topup')->insert([
                    'user_id'        => $user->id_pengguna,
                    'reference_id'   => $invoiceNumber,
                    'target_phone'   => $danaNumber,
                    'amount'         => $amount,
                    'payment_method' => $paymentMethod,
                    'status'         => 'PROCESSING',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);

                // Potong Saldo
                DB::table('Pengguna')->where('id_pengguna', $user->id_pengguna)->decrement('saldo', $amount);

                // Langsung Tembak API DANA
                $danaResult = $this->executeDanaB2B($invoiceNumber, $danaNumber, $amount);

                if ($danaResult['success']) {
                    DB::commit();
                    return redirect()->route('customer.topupdana.success', ['invoice' => $invoiceNumber])
                                     ->with('success', 'Top Up DANA berhasil menggunakan Saldo Sancaka!');
                } else {
                    // JIKA GAGAL: Refund saldo kembali ke user (Auto-Refund)
                    DB::table('Pengguna')->where('id_pengguna', $user->id_pengguna)->increment('saldo', $amount);
                    DB::table('dana_transaction_topup')->where('reference_id', $invoiceNumber)->update(['status' => 'FAILED_DANA']);
                    DB::commit();
                    return back()->with('error', 'Gagal memproses ke DANA: ' . $danaResult['message'])->withInput();
                }
            }

            // ====================================================================
            // LOGIKA 2: PEMBAYARAN VIA PAYMENT GATEWAY (TRIPAY/DOKU)
            // ====================================================================
            
            DB::table('dana_transaction_topup')->insert([
                'user_id'        => $user->id_pengguna,
                'reference_id'   => $invoiceNumber,
                'target_phone'   => $danaNumber,
                'amount'         => $amount,
                'payment_method' => $validated['payment_method'],
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

            if ($validated['payment_method'] === 'DOKU_JOKUL') {
                Log::info('LOG LOG: Memulai Generate DOKU Jokul untuk ' . $invoiceNumber);
                $lineItems = [['name' => 'Top Up DANA ' . $danaNumber, 'price' => $amount, 'quantity' => 1]];
                $successRedirectUrl = route('customer.topupdana.success', ['invoice' => $invoiceNumber]);
                
                $paymentUrl = $dokuJokulService->createPayment(
                    $invoiceNumber, $amount, $customerData, $lineItems, [], $successRedirectUrl
                );

                if (empty($paymentUrl)) throw new Exception('Gagal membuat link DOKU.');
                return redirect()->away($paymentUrl);
            } 
            else {
                Log::info('LOG LOG: Memulai Generate Tripay untuk ' . $invoiceNumber);
                $apiKey       = config('tripay.api_key');
                $privateKey   = config('tripay.private_key');
                $merchantCode = config('tripay.merchant_code');
                $mode         = \App\Models\Api::getValue('TRIPAY_MODE', 'global', 'sandbox');

                $payload = [
                    'method'         => $validated['payment_method'],
                    'merchant_ref'   => $invoiceNumber,
                    'amount'         => $amount,
                    'customer_name'  => $customerData['name'],
                    'customer_email' => $customerData['email'],
                    'customer_phone' => $customerData['phone'],
                    'order_items'    => [['sku' => 'DANA', 'name' => 'Top Up DANA ' . $danaNumber, 'price' => $amount, 'quantity' => 1]],
                    'signature'      => hash_hmac('sha256', $merchantCode.$invoiceNumber.$amount, $privateKey),
                ];

                $baseUrl = $mode === 'production' 
                    ? 'https://tripay.co.id/api/transaction/create' 
                    : 'https://tripay.co.id/api-sandbox/transaction/create';

                $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])->post($baseUrl, $payload);
                
                if ($response->successful() && isset($response->json()['success']) && $response->json()['success'] === true) {
                    $checkoutUrl = $response->json()['data']['checkout_url'];
                    return redirect()->away($checkoutUrl);
                }

                throw new Exception('Gagal membuat transaksi di Tripay.');
            }

        } catch (\Exception $e) {
            DB::rollBack();
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

        $merchantDepositAccount = '20070000103315239788'; 
        $idToko                 = '216660001394664338723';
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

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);

        try {
            // PERBAIKAN: Mengirim 4 argumen sesuai yang diminta oleh DanaSignatureService
            $signature = $this->danaSignature->generateSignature('POST', $path, $jsonBody, $timestamp);
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
                DB::table('dana_transaction_topup')->where('reference_id', $merchantRef)->update([
                    'status' => 'SUCCESS',
                    'updated_at' => now()
                ]);
                Log::info("LOG LOG: SUCCESS! Saldo DANA berhasil masuk ke nomor {$targetPhone}");
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
        $merchantRef = $request->input('reference') ?? $request->input('merchant_ref');
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
        
        $merchantDepositAccount = '20070000103315239788'; 
        $idToko                 = '216660001394664338723';
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
            $signature = $this->danaSignature->generateSignature($stringToSign);
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
        
        $merchantDepositAccount = '20070000103315239788'; 
        $idToko                 = '216660001394664338723';
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
            $signature = $this->danaSignature->generateSignature($stringToSign);
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
     * FUNGSI CEK STATUS TOP UP LANGSUNG KE API DANA
     * =========================================================================
     */
    public function checkStatus(Request $request)
    {
        $request->validate(['reference_id' => 'required']);
        
        // 1. Cari data di tabel yang benar
        $trx = DB::table('dana_transaction_topup')->where('reference_id', $request->reference_id)->first();

        if (!$trx) {
            return back()->with('error', 'Data transaksi tidak ditemukan di database topup.');
        }

        // 2. Siapkan Payload Query ke DANA
        $timestamp = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $path      = '/rest/v1.0/emoney/query'; // Endpoint query B2B Topup DANA
        
        $body = [
            "partnerReferenceNo" => $trx->reference_id,
            "merchantId"         => '216660001394664338723'
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);

        try {
            // 3. Generate Signature & Tembak API
            $signature = $this->danaSignature->generateSignature('POST', $path, $jsonBody, $timestamp);
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

            Log::info("LOG LOG: Hasil Query Status DANA untuk {$trx->reference_id}: ", $result);

            // 4. Update Status Berdasarkan Jawaban DANA
            if ($codeCheck === '2003800' || ($result['transactionStatus'] ?? '') === 'SUCCESS') {
                DB::table('dana_transaction_topup')->where('reference_id', $trx->reference_id)->update([
                    'status' => 'SUCCESS',
                    'updated_at' => now()
                ]);
                return back()->with('success', "Status dari DANA: SUKSES (Dana telah masuk).");
            } 
            elseif (in_array($codeCheck, ['504', '4293800', '2023800']) || ($result['transactionStatus'] ?? '') === 'PENDING') {
                return back()->with('warning', 'Status dari DANA: MASIH DIPROSES (Pending). Silakan cek lagi nanti.');
            } 
            else {
                DB::table('dana_transaction_topup')->where('reference_id', $trx->reference_id)->update([
                    'status' => 'FAILED_DANA',
                    'updated_at' => now()
                ]);
                return back()->with('error', "Status dari DANA: GAGAL (Kode: {$codeCheck} - " . ($result['responseMessage'] ?? 'Unknown Error') . ")");
            }
        } catch (\Exception $e) {
            Log::error('LOG LOG: Exception cek status DANA: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem saat mengecek status ke DANA.');
        }
    }
}