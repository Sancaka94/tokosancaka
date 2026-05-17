<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\TransactionPpobIak;
use App\Models\IakResponseCode;
use App\Models\IakPricelistPostpaid;
use App\Models\IakPrepaidResponseCode;
use App\Models\IakPricelistPrepaid;
use App\Models\Api;
use App\Models\User; // Pastikan model User mengarah ke tabel Pengguna
use Illuminate\Support\Facades\Cache;
use App\Services\FonnteService;
use App\Services\DokuJokulService;
use Illuminate\Support\Facades\Hash; // PENTING: Untuk verifikasi PIN

class PpobIakController extends Controller
{
    private $prepaidBaseUrl;
    private $postpaidBaseUrl;
    private $username;
    private $apiKey;

    public function __construct()
    {
        $env = Api::getValue('IAK_MODE', 'global', 'development');
        $this->prepaidBaseUrl = Api::getValue('IAK_PREPAID_BASE_URL', $env) ?: ($env === 'production' ? 'https://prepaid.iak.id' : 'https://prepaid.iak.dev');
        $this->postpaidBaseUrl = Api::getValue('IAK_POSTPAID_BASE_URL', $env) ?: ($env === 'production' ? 'https://mobilepulsa.net' : 'https://testpostpaid.mobilepulsa.net');
        $this->username = Api::getValue('IAK_USER_HP', $env);
        $this->apiKey = Api::getValue('IAK_API_KEY', $env);
    }

    public function index()
    {
        $transactions = TransactionPpobIak::latest()->take(5)->get();
        $pricelist = IakPricelistPostpaid::where('status', 1)->orderBy('type')->get();
        $pricelistPrepaid = IakPricelistPrepaid::where('status', 'Active')->orderBy('type')->orderBy('operator')->get();

        return view('ppob.iak', compact('transactions', 'pricelist', 'pricelistPrepaid'));
    }

    // ====================================================================
    // FUNGSI BARU: AJAX VERIFIKASI WA DAN PIN UNTUK MUNCULKAN SALDO
    // ====================================================================
    public function verifyPinAndBalance(Request $request)
    {
        $request->validate([
            'no_wa' => 'required|string',
            'pin'   => 'required|string'
        ]);

        // Format WA
        $wa = preg_replace('/[^0-9]/', '', $request->no_wa);
        if (substr($wa, 0, 2) === '62') $wa = '0' . substr($wa, 2);
        elseif (substr($wa, 0, 1) === '8') $wa = '0' . $wa;

        $user = User::where('no_wa', $wa)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Nomor WA tidak terdaftar di sistem.']);
        }
        if (empty($user->pin)) {
            return response()->json(['success' => false, 'message' => 'Akun ini belum mengatur PIN Keamanan.']);
        }
        if (!Hash::check($request->pin, $user->pin)) {
            return response()->json(['success' => false, 'message' => 'PIN yang Anda masukkan salah.']);
        }

        return response()->json([
            'success'      => true,
            'saldo'        => (float) $user->saldo,
            'saldo_format' => number_format($user->saldo, 0, ',', '.'),
            'nama'         => $user->nama_lengkap
        ]);
    }
    // ====================================================================

    public function store(Request $request)
    {
        $request->validate([
            'customer_id'     => 'required|string',
            'product_code'    => 'required|string',
            'type'            => 'required|in:prabayar,pascabayar',
            'whatsapp_number' => 'nullable|string',
            'payment_method'  => 'required|string'
        ]);

        // Format WA Struk
        if ($request->filled('whatsapp_number')) {
            $wa = preg_replace('/[^0-9]/', '', $request->whatsapp_number);
            if (substr($wa, 0, 2) === '62') $wa = '0' . substr($wa, 2);
            elseif (substr($wa, 0, 1) === '8') $wa = '0' . $wa;
            $request->merge(['whatsapp_number' => $wa]);
        }

        if ($request->type === 'pascabayar') {
            return $this->inquiryPostpaid($request);
        }

        $lockKey = 'topup_' . (auth()->id() ?? 'guest') . '_' . $request->product_code . '_' . $request->customer_id;
        $lock = Cache::lock($lockKey, 10);
        if (!$lock->get()) {
            return back()->with('error', 'Transaksi sedang diproses, mohon jangan klik berkali-kali.');
        }

        try {
            $product = IakPricelistPrepaid::where('code', $request->product_code)->first();
            if (!$product) return back()->with('error', 'Produk tidak ditemukan di database.');

            $amount = (int) $product->price;
            $refId = 'P' . date('ymd') . rand(1000, 9999);

            $paymentMethod = strtoupper(trim($request->payment_method));
            $isSaldo = ($paymentMethod === 'SALDO');
            $userPayment = auth()->user(); // Fallback awal

            // =====================================================
            // VALIDASI BACKEND JIKA POTONG SALDO
            // =====================================================
            if ($isSaldo) {
                if (!$request->filled('wa_pembayaran') || !$request->filled('pin_pembayaran')) {
                    return back()->with('error', 'Nomor WA dan PIN Pembayaran wajib diisi untuk potong saldo.');
                }

                $waBayar = preg_replace('/[^0-9]/', '', $request->wa_pembayaran);
                if (substr($waBayar, 0, 2) === '62') $waBayar = '0' . substr($waBayar, 2);
                elseif (substr($waBayar, 0, 1) === '8') $waBayar = '0' . $waBayar;

                $userPayment = User::where('no_wa', $waBayar)->first();
                if (!$userPayment) return back()->with('error', 'Nomor WA pembayaran tidak terdaftar.');
                if (!Hash::check($request->pin_pembayaran, $userPayment->pin)) return back()->with('error', 'PIN Pembayaran salah!');
                if ($userPayment->saldo < $amount) return back()->with('error', 'Saldo tidak mencukupi untuk transaksi ini.');
            }

            // Dapatkan ID User Final
            $userIdToSave = $userPayment ? $userPayment->id_pengguna : null;

            // =====================================================
            // EKSEKUSI PEMBAYARAN GATEWAY
            // =====================================================
            if (!$isSaldo) {
                $transaction = TransactionPpobIak::create([
                    'user_id'         => $userIdToSave,
                    'ref_id'          => $refId,
                    'type'            => 'prabayar',
                    'customer_id'     => $request->customer_id,
                    'product_code'    => $request->product_code,
                    'whatsapp_number' => $request->whatsapp_number,
                    'status'          => 'PENDING',
                    'price'           => $amount,
                    'message'         => 'Menunggu Pembayaran Gateway'
                ]);

                if (in_array($paymentMethod, ['DOKU', 'DOKU_JOKUL'])) {
                    $dokuService = new DokuJokulService();
                    $paymentUrl = $dokuService->createPayment($refId, $amount);
                    $transaction->update(['payment_url' => $paymentUrl]);
                    return redirect()->away($paymentUrl);
                } elseif ($paymentMethod === 'DANA') {
                    $akunParams = $userPayment->no_wa ?? $userPayment->no_hp ?? $userIdToSave;
                    $paymentUrl = url('/pembayaran?akun=' . urlencode($akunParams));
                    $transaction->update(['payment_url' => $paymentUrl]);
                    return redirect()->away($paymentUrl);
                } else {
                    // TRIPAY LOGIC
                    $tripayMode = Api::getValue('TRIPAY_MODE', 'global', 'sandbox');
                    $apiKey = Api::getValue('TRIPAY_API_KEY', $tripayMode);
                    $privateKey = Api::getValue('TRIPAY_PRIVATE_KEY', $tripayMode);
                    $merchantCode = Api::getValue('TRIPAY_MERCHANT_CODE', $tripayMode);
                    $signature = hash_hmac('sha256', $merchantCode.$refId.$amount, $privateKey);

                    $tripayUrl = $tripayMode === 'production' ? 'https://tripay.co.id/api/transaction/create' : 'https://tripay.co.id/api-sandbox/transaction/create';
                    $responseTripay = Http::withHeaders(['Authorization' => 'Bearer ' . trim($apiKey)])
                        ->post($tripayUrl, [
                            'method'         => $paymentMethod,
                            'merchant_ref'   => $refId,
                            'amount'         => $amount,
                            'customer_name'  => $userPayment->nama_lengkap ?? 'Member Sancaka',
                            'customer_email' => $userPayment->email ?? 'no-reply@sancaka.com',
                            'customer_phone' => $userPayment->no_hp ?? '081234567890',
                            'order_items'    => [['sku' => $product->code, 'name' => $product->description, 'price' => $amount, 'quantity' => 1]],
                            'return_url'     => route('ppob.index'),
                            'signature'      => $signature
                        ]);

                    $resTripay = $responseTripay->json();
                    if ($responseTripay->successful() && isset($resTripay['success']) && $resTripay['success']) {
                        $transaction->update(['payment_url' => $resTripay['data']['checkout_url']]);
                        return redirect()->away($resTripay['data']['checkout_url']);
                    } else {
                        $transaction->update(['status' => 'FAILED', 'message' => 'Gagal membuat tagihan Tripay']);
                        return back()->with('error', 'Gagal Payment Gateway: ' . ($resTripay['message'] ?? 'Error'));
                    }
                }
            }

            // =====================================================
            // EKSEKUSI PEMBAYARAN POTONG SALDO (TEMBAK IAK)
            // =====================================================
            $sign = md5($this->username . $this->apiKey . $refId);
            $transaction = TransactionPpobIak::create([
                'user_id'         => $userIdToSave,
                'ref_id'          => $refId,
                'type'            => 'prabayar',
                'customer_id'     => $request->customer_id,
                'product_code'    => $request->product_code,
                'whatsapp_number' => $request->whatsapp_number,
                'status'          => 'PROCESS',
                'price'           => $amount
            ]);

            try {
                $response = Http::post($this->prepaidBaseUrl . '/api/top-up', [
                    'username'     => $this->username,
                    'customer_id'  => $request->customer_id,
                    'product_code' => $request->product_code,
                    'ref_id'       => $refId,
                    'sign'         => $sign
                ]);

                $result = $response->json();

                if ($response->successful() && isset($result['data'])) {
                    $apiCode = $result['data']['rc'] ?? ($result['data']['message'] == 'PROCESS' ? '39' : null);
                    $codeInfo = IakPrepaidResponseCode::where('code', $apiCode)->first();
                    $statusMap = [0 => 'PROCESS', 1 => 'SUCCESS', 2 => 'FAILED'];

                    $apiStatus = $result['data']['status'] ?? 0;
                    $finalStatus = $codeInfo ? strtoupper($codeInfo->status) : ($statusMap[$apiStatus] ?? 'PROCESS');
                    $finalMessage = $codeInfo ? $codeInfo->description . ' - ' . $codeInfo->solution : ($result['data']['message'] ?? 'Request Terkirim');

                    $transaction->update([
                        'status'  => $finalStatus,
                        'tr_id'   => $result['data']['tr_id'] ?? null,
                        'sn'      => $result['data']['sn'] ?? null,
                        'message' => $finalMessage
                    ]);

                    if ($finalStatus == 'FAILED') return back()->with('error', 'Transaksi prabayar gagal: ' . $transaction->message);

                    // --- POTONG SALDO FINAL ---
                    if (in_array($finalStatus, ['PROCESS', 'SUCCESS'])) {
                        $userPayment->decrement('saldo', $amount);
                    }

                    return redirect()->route('ppob.iak.invoice', ['ref_id' => $transaction->ref_id])->with('success', 'Transaksi sedang diproses.');
                }
                $transaction->update(['status' => 'FAILED', 'message' => $result['data']['message'] ?? 'API Error']);
                return back()->with('error', 'Terjadi kesalahan pada sistem provider.');
            } catch (\Exception $e) {
                $transaction->update(['status' => 'FAILED', 'message' => 'Timeout Error']);
                return back()->with('error', 'Gagal terhubung ke API IAK.');
            }
        } finally {
            optional($lock)->release();
        }
    }

    public function payPostpaid(Request $request)
    {
        $transaction = TransactionPpobIak::where('tr_id', $request->tr_id)->firstOrFail();

        $lock = Cache::lock('pay_pasca_' . $transaction->tr_id, 10);
        if (!$lock->get()) return redirect()->back()->with('error', 'Pembayaran sedang diproses, mohon tunggu.');

        try {
            if ($transaction->status === 'SUCCESS') return redirect()->route('ppob.index')->with('error', 'Tagihan ini sudah lunas.');

            $paymentMethod = strtoupper(trim($request->payment_method));
            $isSaldo = ($paymentMethod === 'SALDO');
            $userPayment = auth()->user();
            $amount = (int) $transaction->price;
            $merchantRef = 'PASCA' . $transaction->tr_id;

            // =====================================================
            // VALIDASI BACKEND JIKA POTONG SALDO
            // =====================================================
            if ($isSaldo) {
                if (!$request->filled('wa_pembayaran') || !$request->filled('pin_pembayaran')) {
                    return back()->with('error', 'Nomor WA dan PIN Pembayaran wajib diisi untuk potong saldo.');
                }
                $waBayar = preg_replace('/[^0-9]/', '', $request->wa_pembayaran);
                if (substr($waBayar, 0, 2) === '62') $waBayar = '0' . substr($waBayar, 2);
                elseif (substr($waBayar, 0, 1) === '8') $waBayar = '0' . $waBayar;

                $userPayment = User::where('no_wa', $waBayar)->first();
                if (!$userPayment) return back()->with('error', 'Nomor WA pembayaran tidak terdaftar.');
                if (!Hash::check($request->pin_pembayaran, $userPayment->pin)) return back()->with('error', 'PIN Pembayaran salah!');
                if ($userPayment->saldo < $amount) return back()->with('error', 'Saldo tidak mencukupi untuk tagihan ini.');

                // Update kepemilikan transaksi ke user yang membayar
                $transaction->user_id = $userPayment->id_pengguna;
                $transaction->save();
            }

            // =====================================================
            // JALUR PAYMENT GATEWAY
            // =====================================================
            if (!$isSaldo) {
                if (in_array($paymentMethod, ['DOKU', 'DOKU_JOKUL'])) {
                    $dokuService = new DokuJokulService();
                    $paymentUrl = $dokuService->createPayment($merchantRef, $amount);
                    $transaction->update(['status' => 'PENDING', 'message' => 'Menunggu Pembayaran Gateway', 'payment_url' => $paymentUrl]);
                    return redirect()->away($paymentUrl);
                } elseif ($paymentMethod === 'DANA') {
                    $akunParams = $userPayment->no_wa ?? $userPayment->no_hp ?? $transaction->user_id;
                    $paymentUrl = url('/pembayaran?akun=' . urlencode($akunParams));
                    $transaction->update(['status' => 'PENDING', 'message' => 'Menunggu Pembayaran Gateway', 'payment_url' => $paymentUrl]);
                    return redirect()->away($paymentUrl);
                } else {
                    // TRIPAY LOGIC
                    $tripayMode = Api::getValue('TRIPAY_MODE', 'global', 'sandbox');
                    $apiKey = Api::getValue('TRIPAY_API_KEY', $tripayMode);
                    $privateKey = Api::getValue('TRIPAY_PRIVATE_KEY', $tripayMode);
                    $merchantCode = Api::getValue('TRIPAY_MERCHANT_CODE', $tripayMode);
                    $signature = hash_hmac('sha256', $merchantCode.$merchantRef.$amount, $privateKey);
                    $tripayUrl = $tripayMode === 'production' ? 'https://tripay.co.id/api/transaction/create' : 'https://tripay.co.id/api-sandbox/transaction/create';

                    $responseTripay = Http::withHeaders(['Authorization' => 'Bearer ' . trim($apiKey)])
                        ->post($tripayUrl, [
                            'method'         => $paymentMethod,
                            'merchant_ref'   => $merchantRef,
                            'amount'         => $amount,
                            'customer_name'  => $userPayment->nama_lengkap ?? 'Member Sancaka',
                            'customer_email' => $userPayment->email ?? 'no-reply@sancaka.com',
                            'customer_phone' => $userPayment->no_hp ?? '081234567890',
                            'order_items'    => [['sku' => 'TAGIHAN', 'name' => 'Tagihan ' . $transaction->product_code, 'price' => $amount, 'quantity' => 1]],
                            'return_url'     => route('ppob.index'),
                            'signature'      => $signature
                        ]);

                    $resTripay = $responseTripay->json();
                    if ($responseTripay->successful() && isset($resTripay['success']) && $resTripay['success']) {
                        $transaction->update(['status' => 'PENDING', 'payment_url' => $resTripay['data']['checkout_url']]);
                        return redirect()->away($resTripay['data']['checkout_url']);
                    }
                    return back()->with('error', 'Gagal Payment Gateway: ' . ($resTripay['message'] ?? 'Error'));
                }
            }

            // =====================================================
            // JALUR POTONG SALDO (TEMBAK IAK PASCABAYAR)
            // =====================================================
            $sign = md5($this->username . $this->apiKey . $transaction->tr_id);
            try {
                $transaction->update(['status' => 'PROCESS', 'message' => 'Sedang mengirim pembayaran...']);
                $response = Http::post($this->postpaidBaseUrl . '/api/v1/bill/check', [
                    'commands' => 'pay-pasca',
                    'username' => $this->username,
                    'tr_id'    => $transaction->tr_id,
                    'sign'     => $sign
                ]);

                $result = $response->json();
                if ($response->successful() && isset($result['data'])) {
                    $rc = $result['data']['response_code'] ?? '';
                    $status = ($rc === '00') ? 'SUCCESS' : (($rc === '39') ? 'PROCESS' : 'FAILED');

                    $transaction->update([
                        'status'  => $status,
                        'sn'      => $result['data']['noref'] ?? null,
                        'message' => $result['data']['message'] ?? 'Payment response received'
                    ]);

                    if ($status == 'FAILED') return redirect()->route('ppob.index')->with('error', 'Pembayaran gagal: ' . $transaction->message);

                    // --- POTONG SALDO FINAL ---
                    if (in_array($status, ['PROCESS', 'SUCCESS'])) {
                        $userPayment->decrement('saldo', $transaction->price);
                    }

                    return redirect()->route('ppob.iak.invoice', ['ref_id' => $transaction->ref_id])->with('success', 'Tagihan Berhasil diproses!');
                }
                $transaction->update(['status' => 'FAILED', 'message' => 'Invalid API Response']);
                return redirect()->route('ppob.index')->with('error', 'Gagal memproses pembayaran ke provider.');

            } catch (\Exception $e) {
                $transaction->update(['message' => 'Timeout Error']);
                return redirect()->route('ppob.index')->with('error', 'Koneksi terputus ke provider.');
            }
        } finally {
            optional($lock)->release();
        }
    }

    // --- ALUR 3 & 4: CHECK STATUS POSTPAID ---
    public function checkStatusPostpaid($tr_id)
    {
        $transaction = TransactionPpobIak::where('tr_id', $tr_id)->firstOrFail();

        // Sesuai dokumentasi baru: signature = md5(username + api_key + 'cs')
        $sign = md5($this->username . $this->apiKey . 'cs');

        Log::info('LOG LOG - Check Status Postpaid Request', ['tr_id' => $tr_id, 'ref_id' => $transaction->ref_id]);

        try {
            $response = Http::post($this->postpaidBaseUrl . '/api/v1/bill/check', [
                'commands' => 'checkstatus', // Sesuai docs: commands = checkstatus
                'username' => $this->username,
                'ref_id'   => $transaction->ref_id,
                'sign'     => $sign
            ]);

            $result = $response->json();

            if ($response->successful() && isset($result['data'])) {
                // Mapping status khusus untuk Pascabayar
                // 0: PENDING (Request belum diterima/masih ngantri)
                // 1: SUCCESS
                // 2: FAILED
                // 3: PROCESS (Sedang diproses)
                $apiStatus = $result['data']['status'] ?? 3;
                $statusMap = [
                    0 => 'PENDING',
                    1 => 'SUCCESS',
                    2 => 'FAILED',
                    3 => 'PROCESS'
                ];
                $finalStatus = $statusMap[$apiStatus] ?? 'PROCESS';
                $finalMessage = $result['data']['message'] ?? 'Status di-refresh.';

                // --- LOGIKA REFUND JIKA TRANSAKSI BERUBAH JADI FAILED ---
                if (in_array($transaction->status, ['PROCESS', 'PENDING']) && $finalStatus === 'FAILED') {
                    if ($transaction->user_id) {
                        // Sesuaikan \App\Models\User jika model kamu bernama lain
                        $userRefund = User::find($transaction->user_id);
                        if ($userRefund) {
                            $userRefund->balance_iak += $transaction->price; // Kembalikan saldo
                            $userRefund->save();
                            Log::info('LOG LOG - Saldo Refunded (Check Status Pasca)', ['ref_id' => $transaction->ref_id, 'amount' => $transaction->price]);
                        }
                    }
                }
                // --------------------------------------------------------

                $transaction->update([
                    'status'  => $finalStatus,
                    'sn'      => $result['data']['noref'] ?? $transaction->sn, // Jika sukses biasanya dapat noref (SN)
                    'message' => $finalMessage
                ]);

                // 👇 TAMBAHKAN INI 👇
                if ($finalStatus === 'SUCCESS' && !empty($result['data']['noref'])) {
                    $this->_sendExpoPushNotification($transaction, $result['data']['noref']);
                }

                Log::info('LOG LOG - Check Status Postpaid Result', ['ref_id' => $transaction->ref_id, 'status' => $finalStatus]);
                return redirect()->back()->with('success', 'Status tagihan berhasil di-refresh: ' . $finalMessage);
            }

            Log::error('LOG LOG - Check Status Postpaid Invalid Response', ['response' => $result]);
            return redirect()->back()->with('error', 'Gagal mengecek status. Response API tidak valid.');

        } catch (\Exception $e) {
            Log::error('LOG LOG - Check Status Postpaid Exception', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Gagal terhubung ke API saat cek status.');
        }
    }

    // --- UPDATE WEBHOOK UNTUK PRABAYAR ---
    public function webhook(Request $request)
    {
        $data = $request->input('data');
        Log::info('LOG LOG - Webhook Incoming Data', ['payload' => $data]); // LOG LOG

        if (!$data || !isset($data['ref_id'])) {
            return response()->json(['message' => 'Invalid payload format'], 400);
        }

        $refId  = $data['ref_id'];
        $status = $data['status']; // 0 = process, 1 = success, 2 = failed
        $apiCode = $data['rc'] ?? null; // Response Code dari IAK
        $sn     = $data['sn'] ?? null;
        $price  = $data['price'] ?? 0;
        $sign   = $data['sign'] ?? null;

        $expectedSign = md5($this->username . $this->apiKey . $refId);

        // --- LOG SIGNATURE CHECK ---
        Log::info('LOG LOG - Webhook Signature Check', [
            'received_sign' => $sign,
            'expected_sign' => $expectedSign,
            'ref_id_tested' => $refId
        ]);
        // ---------------------------

        // Pastikan $sign ada dan cocok dengan expectedSign
        if (!$sign || $sign !== $expectedSign) {
            Log::warning('LOG LOG - Webhook ALERT: Invalid Signature detected!', [
                'received' => $sign,
                'expected' => $expectedSign,
                'ip' => $request->ip()
            ]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // ========================================================
        // --- TAMBAHAN IDEMPOTENCY WEBHOOK ---
        // ========================================================
        // 1. ATOMIC LOCK: Cegah Race Condition jika ada 2 webhook datang di milidetik yang sama
        $lockKey = 'webhook_lock_' . $refId;
        $lock = Cache::lock($lockKey, 10); // Kunci selama 10 detik

        if (!$lock->get()) {
            // Return 200 agar IAK mengira sudah sukses dan tidak nge-hit ulang terus-terusan
            Log::info('LOG LOG - Webhook Duplicate Hit Blocked by Lock', ['ref_id' => $refId]);
            return response()->json(['message' => 'Webhook is already being processed'], 200);
        }

        try {
            $transaction = TransactionPpobIak::where('ref_id', $refId)->first();
            if (!$transaction) {
                return response()->json(['message' => 'Transaction not found'], 404);
            }

            // 2. CEK STATUS FINAL: Cegah proses ulang jika webhook telat datang dan trx sudah sukses/gagal duluan
            if (in_array($transaction->status, ['SUCCESS', 'FAILED'])) {
                Log::info('LOG LOG - Webhook Ignored (Already Finalized)', ['ref_id' => $refId, 'current_status' => $transaction->status]);
                return response()->json(['message' => 'Transaction already finalized previously'], 200);
            }

        $transaction = TransactionPpobIak::where('ref_id', $refId)->first();
        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        // Cek response code berdasarkan tipe transaksi
        if ($transaction->type === 'prabayar') {
            $codeInfo = IakPrepaidResponseCode::where('code', $apiCode)->first();
        } else {
            $codeInfo = IakResponseCode::where('code', $apiCode)->first();
        }

        $statusMap = [0 => 'PROCESS', 1 => 'SUCCESS', 2 => 'FAILED'];
        $finalStatus = $codeInfo ? strtoupper($codeInfo->status) : ($statusMap[$status] ?? 'PROCESS');
        $finalMessage = $codeInfo ? $codeInfo->description : ($data['message'] ?? 'Status updated by Webhook');

        // --- LOGIKA REFUND JIKA TRANSAKSI BERUBAH JADI FAILED VIA WEBHOOK ---
        if (in_array($transaction->status, ['PROCESS', 'PENDING']) && $finalStatus === 'FAILED') {
            if ($transaction->user_id) {
                // Sesuaikan \App\Models\User jika model kamu bernama lain (misal: \App\Models\Pengguna)
                $userRefund = User::find($transaction->user_id);
                if ($userRefund) {
                    $userRefund->increment('balance_iak', $transaction->price);
                    $userRefund->save();
                    Log::info('LOG LOG - Saldo Refunded (Webhook)', ['ref_id' => $refId, 'amount' => $transaction->price]);
                }
            }
        }
        // --------------------------------------------------------------------

        $transaction->update([
                'status'  => $finalStatus,
                'sn'      => $sn ?: $transaction->sn,
                'price'   => $price > 0 ? $price : $transaction->price,
                'message' => $finalMessage
            ]);

            // ========================================================
            // --- LOGIKA KIRIM WA VIA FONNTE (DI DALAM WEBHOOK) ---
            // ========================================================
            if ($finalStatus === 'SUCCESS' && !empty($sn)) {

            $this->_sendExpoPushNotification($transaction, $sn);

                $phone = $transaction->whatsapp_number;
                if (!empty($phone)) {
                    $fonnteToken = Api::getValue('FONNTE_API_KEY', 'global');
                    if (!empty($fonnteToken)) {
                        $pesanWa = "*TRANSAKSI PPOB BERHASIL* ✅\n\n"
                                 . "Ref ID: {$transaction->ref_id}\n"
                                 . "Layanan: {$transaction->product_code}\n"
                                 . "Tujuan: {$transaction->customer_id}\n"
                                 . "Harga: Rp " . number_format($transaction->price, 0, ',', '.') . "\n"
                                 . "Status: {$finalStatus}\n\n"
                                 . "*SN / TOKEN:*\n{$sn}\n\n"
                                 . "_Terima kasih telah menggunakan layanan Sancaka._";

                        try {
                            // Hit API Fonnte tanpa parameter countryCode agar tidak bentrok dengan nomor 08xxx
                            $responseFonnte = Http::withHeaders([
                                'Authorization' => $fonnteToken
                            ])->post('https://api.fonnte.com/send', [
                                'target' => $phone,
                                'message' => $pesanWa
                            ]);

                            Log::info('LOG LOG - Fonnte WA (Webhook) Sent', [
                                'target' => $phone,
                                'response' => $responseFonnte->json()
                            ]);
                        } catch (\Exception $e) {
                            Log::error('LOG LOG - Fonnte WA (Webhook) Error', ['error' => $e->getMessage()]);
                        }
                    } else {
                        Log::warning('LOG LOG - Fonnte Skipped (Webhook): Token belum diatur.');
                    }
                } else {
                    Log::warning('LOG LOG - Fonnte Skipped (Webhook): Nomor WA pembeli kosong di database.');
                }
            }

            Log::info('LOG LOG - Webhook Processed Successfully', ['ref_id' => $refId, 'finalStatus' => $finalStatus, 'sn' => $sn]);
            return response()->json(['message' => 'Callback received successfully'], 200);

        } finally {
            // Lepaskan lock agar antrean memori bersih
            optional($lock)->release();
        }
    } // <--- Ini adalah kurung kurawal penutup fungsi webhook()

    // --- FUNGSI UNTUK MENAMPILKAN INVOICE ---
    public function invoice($ref_id)
    {
        // Tarik data transaksi berdasarkan ref_id
        $transaction = TransactionPpobIak::where('ref_id', $ref_id)->firstOrFail();

        return view('ppob.invoice', compact('transaction'));
    }

    // --- FUNGSI BARU: INQUIRY PLN PRABAYAR (DENGAN LOG & RESPONSE LENGKAP) ---
    public function inquiryPln(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|string'
        ]);

        $customerId = $request->customer_id;

        // Sesuai dokumentasi: md5(username+api_key+customer_id)
        $sign = md5($this->username . $this->apiKey . $customerId);

        Log::info('========== START INQUIRY PLN ==========');
        Log::info('1. Request Payload to IAK:', [
            'endpoint'    => $this->prepaidBaseUrl . '/api/inquiry-pln',
            'username'    => $this->username,
            'customer_id' => $customerId,
            'sign'        => $sign
        ]);

        try {
            $response = Http::post($this->prepaidBaseUrl . '/api/inquiry-pln', [
                'username'    => $this->username,
                'customer_id' => $customerId,
                'sign'        => $sign
            ]);

            $result = $response->json();

            // Log mentah hasil balasan dari IAK
            Log::info('2. Raw Response from IAK:', $result ?? ['raw_body' => $response->body()]);

            // Cek jika response sukses dan ada blok data
            if ($response->successful() && isset($result['data'])) {
                // Status 1 = SUCCESS
                if ($result['data']['status'] == '1' || $result['data']['status'] == 1) {
                    Log::info('3. Hasil: INQUIRY SUKSES');
                    Log::info('=======================================');

                    // RESPONSE LENGKAP SESUAI DOKUMENTASI IAK
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'status'        => $result['data']['status'],
                            'customer_id'   => $result['data']['customer_id'] ?? $customerId,
                            'meter_no'      => $result['data']['meter_no'] ?? '-',
                            'subscriber_id' => $result['data']['subscriber_id'] ?? '-',
                            'name'          => trim($result['data']['name'] ?? 'Tidak diketahui'),
                            'segment_power' => trim($result['data']['segment_power'] ?? '-'),
                            'message'       => $result['data']['message'] ?? 'SUCCESS',
                            'rc'            => $result['data']['rc'] ?? '00'
                        ],
                        'message' => 'Inquiry Berhasil'
                    ]);
                } else {
                    // Jika Status 2 = FAILED (misal: INCORRECT DESTINATION NUMBER)
                    Log::warning('3. Hasil: INQUIRY DITOLAK IAK (Nomor Salah/Gangguan)');
                    Log::info('=======================================');
                    return response()->json([
                        'success' => false,
                        'message' => $result['data']['message'] ?? 'Nomor Pelanggan PLN Tidak Valid / Tidak Ditemukan'
                    ]);
                }
            }

            Log::error('3. Hasil: FORMAT RESPONSE IAK TIDAK DIKENAL', ['status_code' => $response->status()]);
            Log::info('=======================================');
            return response()->json([
                'success' => false,
                'message' => 'Gagal terhubung atau Respon API IAK tidak valid.'
            ]);

        } catch (\Exception $e) {
            Log::error('3. Hasil: KONEKSI TIMEOUT / EXCEPTION', ['error' => $e->getMessage()]);
            Log::info('=======================================');
            return response()->json([
                'success' => false,
                'message' => 'Koneksi ke server terputus: ' . $e->getMessage()
            ]);
        }
    }

    // --- FUNGSI BARU: INQUIRY OVO ---
    public function inquiryOvo(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|string'
        ]);

        $customerId = $request->customer_id;

        // Sesuai dokumentasi: md5(username+api_key+customer_id)
        $sign = md5($this->username . $this->apiKey . $customerId);

        Log::info('LOG LOG - Inquiry OVO Request', ['customer_id' => $customerId]);

        try {
            $response = Http::post($this->prepaidBaseUrl . '/api/inquiry-ovo', [
                'username'    => $this->username,
                'customer_id' => $customerId,
                'sign'        => $sign
            ]);

            $result = $response->json();

            if ($response->successful() && isset($result['data'])) {
                if ($result['data']['status'] == '1') {
                    Log::info('LOG LOG - Inquiry OVO Success', ['data' => $result['data']]);
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'name'          => $result['data']['name'] ?? 'Tidak diketahui',
                            'customer_id'   => $result['data']['customer_id'] ?? $customerId
                        ],
                        'message' => 'Inquiry OVO Berhasil'
                    ]);
                } else {
                    Log::error('LOG LOG - Inquiry OVO Failed Status', ['response' => $result]);
                    return response()->json([
                        'success' => false,
                        'message' => $result['data']['message'] ?? 'Nomor OVO Tidak Valid / Tidak Ditemukan'
                    ]);
                }
            }

            Log::error('LOG LOG - Inquiry OVO Invalid Response', ['response' => $result]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal terhubung atau Respon API IAK tidak valid.'
            ]);

        } catch (\Exception $e) {
            Log::error('LOG LOG - Inquiry OVO Exception', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Koneksi ke server terputus: ' . $e->getMessage()
            ]);
        }
    }

    // --- FUNGSI BARU: INQUIRY GAME FORMAT (Cek format inputan ID Player) ---
    public function inquiryGameFormat(Request $request)
    {
        $request->validate([
            'game_code' => 'required|string'
        ]);

        $gameCode = $request->game_code;

        // Sesuai dokumentasi: md5(username+api_key+game_code)
        $sign = md5($this->username . $this->apiKey . $gameCode);

        Log::info('LOG LOG - Inquiry Game Format Request', ['game_code' => $gameCode]);

        try {
            $response = Http::post($this->prepaidBaseUrl . '/api/game/format', [
                'username'  => $this->username,
                'game_code' => $gameCode,
                'sign'      => $sign
            ]);

            $result = $response->json();

            if ($response->successful() && isset($result['data'])) {
                if ($result['data']['status'] == 1 || $result['data']['status'] == '1') {
                    Log::info('LOG LOG - Inquiry Game Format Success', ['data' => $result['data']]);
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'formatGameId' => $result['data']['formatGameId'] ?? ''
                        ],
                        'message' => $result['data']['message'] ?? 'Inquiry Format Berhasil'
                    ]);
                } else {
                    Log::error('LOG LOG - Inquiry Game Format Failed', ['response' => $result]);
                    return response()->json([
                        'success' => false,
                        'message' => $result['data']['message'] ?? 'Format Game tidak ditemukan (Mungkin tidak butuh inquiry)'
                    ]);
                }
            }

            Log::error('LOG LOG - Inquiry Game Format Invalid Response', ['response' => $result]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal terhubung atau Respon API IAK tidak valid.'
            ]);

        } catch (\Exception $e) {
            Log::error('LOG LOG - Inquiry Game Format Exception', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Koneksi ke server terputus: ' . $e->getMessage()
            ]);
        }
    }

    // --- FUNGSI BARU: INQUIRY GAME SERVER (Tarik list Server ID Game) ---
    public function inquiryGameServer(Request $request)
    {
        $request->validate([
            'game_code' => 'required|string'
        ]);

        $gameCode = $request->game_code;

        // Sesuai dokumentasi: md5(username+api_key+game_code)
        $sign = md5($this->username . $this->apiKey . $gameCode);

        Log::info('LOG LOG - Inquiry Game Server Request', ['game_code' => $gameCode]);

        try {
            $response = Http::post($this->prepaidBaseUrl . '/api/inquiry-game-server', [
                'username'  => $this->username,
                'game_code' => $gameCode,
                'sign'      => $sign
            ]);

            $result = $response->json();

            if ($response->successful() && isset($result['data'])) {
                if ($result['data']['status'] == 1 || $result['data']['status'] == '1') {
                    Log::info('LOG LOG - Inquiry Game Server Success', ['data' => $result['data']]);
                    return response()->json([
                        'success' => true,
                        'data' => [
                            // Mengembalikan array of object berisi {name, value}
                            'servers' => $result['data']['servers'] ?? []
                        ],
                        'message' => $result['data']['message'] ?? 'Inquiry Server Berhasil'
                    ]);
                } else {
                    Log::error('LOG LOG - Inquiry Game Server Failed', ['response' => $result]);
                    return response()->json([
                        'success' => false,
                        'message' => $result['data']['message'] ?? 'Game tidak memiliki list server otomatis'
                    ]);
                }
            }

            Log::error('LOG LOG - Inquiry Game Server Invalid Response', ['response' => $result]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal terhubung atau Respon API IAK tidak valid.'
            ]);

        } catch (\Exception $e) {
            Log::error('LOG LOG - Inquiry Game Server Exception', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Koneksi ke server terputus: ' . $e->getMessage()
            ]);
        }
    }

    // --- FUNGSI BARU: GET GAME CODE LIST (Daftar Game) ---
    public function getGameList(Request $request)
    {
        // Sesuai dokumentasi: md5(username+api_key+'gc')
        $sign = md5($this->username . $this->apiKey . 'gc');

        Log::info('LOG LOG - Get Game List Request initiated.');

        try {
            $response = Http::post($this->prepaidBaseUrl . '/api/gamelist', [
                'username' => $this->username,
                'sign'     => $sign
            ]);

            $result = $response->json();

            if ($response->successful() && isset($result['data'])) {
                // rc "00" berarti sukses mengambil data
                if (isset($result['data']['rc']) && $result['data']['rc'] == '00') {
                    Log::info('LOG LOG - Get Game List Success', ['total_games' => count($result['data']['gamelist'] ?? [])]);
                    return response()->json([
                        'success' => true,
                        'data'    => $result['data']['gamelist'] ?? [],
                        'message' => $result['data']['message'] ?? 'Berhasil mengambil daftar game'
                    ]);
                } else {
                    Log::error('LOG LOG - Get Game List Failed Status', ['response' => $result]);
                    return response()->json([
                        'success' => false,
                        'message' => $result['data']['message'] ?? 'Gagal mengambil daftar game dari API'
                    ]);
                }
            }

            Log::error('LOG LOG - Get Game List Invalid Response', ['response' => $result]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal terhubung atau Respon API IAK tidak valid.'
            ]);

        } catch (\Exception $e) {
            Log::error('LOG LOG - Get Game List Exception', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Koneksi ke server terputus: ' . $e->getMessage()
            ]);
        }
    }

    public function history()
    {
        // Ambil riwayat transaksi user yang sedang login, urutkan dari yang terbaru
        $transactions = TransactionPpobIak::where('user_id', auth()->id())
                            ->orderBy('created_at', 'desc')
                            ->paginate(15);

        return view('ppob.history', compact('transactions'));
    }

    // ========================================================
    // --- FUNGSI BARU: KIRIM WA MANUAL DARI RIWAYAT ---
    // ========================================================
    public function sendWa(Request $request, $ref_id)
    {
        $request->validate([
            'target_wa' => 'required|string'
        ]);

        $transaction = TransactionPpobIak::where('ref_id', $ref_id)->firstOrFail();
        $target = $request->target_wa;

        // Bersihkan format nomor
        $target = preg_replace('/[^0-9]/', '', $target);
        if (substr($target, 0, 2) === '62') {
            $target = '0' . substr($target, 2);
        } elseif (substr($target, 0, 1) === '8') {
            $target = '0' . $target;
        }

        $fonnteToken = Api::getValue('FONNTE_API_KEY', 'global');
        if (empty($fonnteToken)) {
            return back()->with('error', 'Token Fonnte belum dikonfigurasi di Pengaturan API.');
        }

        // Setup Ikon Status
        $icon = '⏳';
        if ($transaction->status === 'SUCCESS') $icon = '✅';
        elseif ($transaction->status === 'FAILED') $icon = '❌';

        $pesanWa = "*RINCIAN TRANSAKSI PPOB*\n\n"
                 . "Tgl: " . $transaction->created_at->format('d/m/Y H:i') . "\n"
                 . "Ref ID: {$transaction->ref_id}\n"
                 . "Layanan: {$transaction->product_code}\n"
                 . "Tujuan: {$transaction->customer_id}\n"
                 . "Harga: Rp " . number_format($transaction->price, 0, ',', '.') . "\n"
                 . "Status: {$transaction->status} {$icon}\n";

        if (!empty($transaction->sn)) {
            $pesanWa .= "\n*SN / TOKEN:*\n*{$transaction->sn}*\n";
        }

        $pesanWa .= "\n_Terima kasih telah menggunakan layanan kami._";

        try {
            $response = Http::withHeaders([
                'Authorization' => $fonnteToken
            ])->post('https://api.fonnte.com/send', [
                'target'      => $target,
                'message'     => $pesanWa,
                'countryCode' => '62'
            ]);

            $resJson = $response->json();

            if (isset($resJson['status']) && $resJson['status'] == true) {
                // Simpan/Update nomor WA di transaksi agar tidak perlu input ulang nantinya
                $transaction->update(['whatsapp_number' => $target]);
                return back()->with('success', 'Rincian transaksi berhasil dikirim ke WhatsApp: ' . $target);
            } else {
                return back()->with('error', 'Fonnte menolak pengiriman: ' . ($resJson['reason'] ?? 'Unknown Error'));
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi ke server Fonnte terputus: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // FUNGSI KHUSUS UNTUK KIRIM NOTIFIKASI EXPO (MOBILE APP)
    // =========================================================================
    private function _sendExpoPushNotification($trx, $sn)
    {
        try {
            $pushMessages = [];

            // 1. Pesan untuk User/Agen (Pemilik Transaksi)
            if ($trx->user_id) {
                $user = \Illuminate\Support\Facades\DB::table('Pengguna')->where('id_pengguna', $trx->user_id)->first();
                if ($user && !empty($user->expo_token)) {
                    $pushMessages[] = [
                        'to' => $user->expo_token,
                        'title' => 'Transaksi PPOB Berhasil! 📱',
                        'body' => "Pengisian {$trx->product_code} ke {$trx->customer_id} sukses. SN: {$sn}",
                        'sound' => 'default',
                    ];
                }
            }

            // 2. Pesan untuk Admin Utama (ID 4)
            $admin = \Illuminate\Support\Facades\DB::table('Pengguna')->where('id_pengguna', 4)->first();
            if ($admin && !empty($admin->expo_token)) {
                $pushMessages[] = [
                    'to' => $admin->expo_token,
                    'title' => 'PPOB Terjual (IAK)! 💰',
                    'body' => "Trx {$trx->ref_id} ({$trx->product_code}) ke {$trx->customer_id} sukses.",
                    'sound' => 'default',
                ];
            }

            // 3. Tembak Semua Notifikasi ke Expo Sekaligus
            if (!empty($pushMessages)) {
                \Illuminate\Support\Facades\Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->post('https://exp.host/--/api/v2/push/send', $pushMessages);

                Log::info("✅ [EXPO PUSH IAK] Notifikasi berhasil dikirim untuk Ref ID: {$trx->ref_id}");
            }
        } catch (\Exception $e) {
            Log::error("❌ [EXPO PUSH IAK] Gagal kirim notifikasi: " . $e->getMessage());
        }
    }

   // --- FUNGSI BARU: SINKRONISASI PRICELIST PRABAYAR (WIPE & FRESH BULK INSERT) ---
    public function syncPricelistPrepaid()
    {
        // Setup Signature: md5(username + api_key + 'pl')
        $sign = md5($this->username . $this->apiKey . 'pl');

        Log::info('LOG LOG - Sync Pricelist Prepaid Request initiated.'); // LOG LOG

        try {
            // Hit API IAK Prabayar untuk menarik semua katalog produk
            $response = Http::post($this->prepaidBaseUrl . '/api/pricelist', [
                'username' => $this->username,
                'sign'     => $sign,
                'status'   => 'all'
            ]);

            $result = $response->json();

            if ($response->successful() && isset($result['data']['pricelist'])) {
                Log::info('LOG LOG - Sync Pricelist Prepaid Success. Mengosongkan DB lokal dan mempersiapkan data fresh...'); // LOG LOG

                // 1. HAPUS SEMUA DATABASE LOKAL TERLEBIH DAHULU
                IakPricelistPrepaid::truncate();

                $now = now();
                $insertData = [];

                // 2. MAPPING DATA SESUAI STRUKTUR TABEL DATABASE SANCAKA
                foreach ($result['data']['pricelist'] as $item) {
                    $insertData[] = [
                        'operator'    => $item['product_description'] ?? '-',
                        'code'        => $item['product_code'],
                        'description' => $item['product_nominal'] ?? '-',
                        'price'       => $item['product_price'] ?? 0,
                        'status'      => (isset($item['status']) && $item['status'] === 'active') ? 'Active' : 'Inactive',
                        'type'        => $item['product_type'] ?? 'umum',
                        'icon_url'    => $item['icon_url'] ?? '-',
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ];
                }

                // 3. INPUT BARU SECARA MASSAL (Bagi per 500 baris agar tidak overload)
                foreach (array_chunk($insertData, 500) as $chunk) {
                    IakPricelistPrepaid::insert($chunk);
                }

                Log::info('LOG LOG - Sync Pricelist Prepaid Fresh Insert Completed.', ['total' => count($insertData)]); // LOG LOG

                return back()->with('success', "Database dikosongkan! Berhasil memasukkan " . count($insertData) . " data produk prabayar terbaru dari IAK.");
            }

            Log::error('LOG LOG - Sync Pricelist Prepaid Failed Response', ['response' => $result]); // LOG LOG
            return back()->with('error', 'Gagal sinkronisasi: ' . ($result['data']['message'] ?? 'Unknown Error'));

        } catch (\Exception $e) {
            Log::error('LOG LOG - Sync Pricelist Prepaid Exception', ['error' => $e->getMessage()]); // LOG LOG
            return back()->with('error', 'Koneksi error: ' . $e->getMessage());
        }
    }

}
