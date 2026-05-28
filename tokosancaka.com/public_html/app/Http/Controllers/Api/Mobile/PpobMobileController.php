<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\TransactionPpobIak;
use App\Models\IakResponseCode;
use App\Models\IakPricelistPostpaid;
use App\Models\IakPrepaidResponseCode;
use App\Models\IakPricelistPrepaid;
use App\Models\Api;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use App\Services\DokuJokulService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Services\DanaSignatureService;

class PpobMobileController extends Controller
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

    // ========================================================
    // 1. AMBIL PRICELIST PRABAYAR (PULSA, DATA, PLN, GAME)
    // ========================================================
    public function getProductsPra(Request $request)
    {
        Log::info('LOG LOG - [API Mobile] getProductsPra Payload Masuk:', $request->all());

        $operator = $request->query('operator');
        $type = $request->query('type');
        $nominal = $request->query('nominal');

        if (!empty($operator)) {
            $opUpper = strtoupper($operator);
            if ($opUpper === 'SMARTFREN') {
                $operator = 'Smart';
            } elseif ($opUpper === 'THREE' || $opUpper === 'TRI') {
                $operator = 'Three';
            } elseif ($opUpper === 'TELKOMSEL') {
                $operator = 'Telkomsel';
            }
        }

        $query = IakPricelistPrepaid::whereIn('status', ['Active', 'active', '1', 1]);

        if (!empty($operator)) {
            $query->whereRaw('LOWER(operator) LIKE ?', ['%' . strtolower($operator) . '%']);
        }

        if (!empty($type)) {
            $query->whereRaw('LOWER(type) = ?', [strtolower($type)]);
        }

        if (!empty($nominal)) {
            $query->where(function($q) use ($nominal) {
                $q->where('price', 'LIKE', "%{$nominal}%")
                  ->orWhere('description', 'LIKE', "%{$nominal}%");
            });
        }

        $products = $query->orderBy('price', 'asc')
                          ->select('code', 'operator', 'description as name', 'description', 'price', 'icon_url', 'type')
                          ->get();

        if ($products->count() > 0) {
            return response()->json([
                'success' => true,
                'data'    => $products,
                'message' => null
            ]);
        }

        $debugMsg = "Operator: " . ($operator ?: 'Semua') . ", Type: " . ($type ?: 'Semua');

        return response()->json([
            'success' => false,
            'data'    => [],
            'message' => 'Produk kosong. (' . $debugMsg . ')'
        ]);
    }

    // ========================================================
    // 2. AMBIL PRICELIST PASCABAYAR (TAGIHAN)
    // ========================================================
    public function getPricelistPasca(Request $request)
    {
        Log::info('LOG LOG - [API Mobile] getPricelistPasca Hit');

        $pricelist = IakPricelistPostpaid::where('status', 1)->orderBy('name', 'asc')->get(['code', 'name', 'type']);
        return response()->json([
            'success' => true,
            'data'    => $pricelist,
            'message' => 'Berhasil mengambil data pascabayar'
        ]);
    }

    // ========================================================
    // 3. TRANSAKSI UTAMA (PRABAYAR & INQUIRY PASCABAYAR)
    // ========================================================
    public function store(Request $request)
    {
        Log::info('LOG LOG - [API Mobile] store (Transaksi PPOB) Payload Masuk:', $request->all());

        $request->validate([
            'customer_id' => 'required|string',
            'product_code' => 'required|string',
            'type' => 'required|in:prabayar,pascabayar',
            'whatsapp_number' => 'nullable|string'
        ]);

        if ($request->filled('whatsapp_number')) {
            $wa = preg_replace('/[^0-9]/', '', $request->whatsapp_number);
            if (substr($wa, 0, 2) === '62') $wa = '0' . substr($wa, 2);
            elseif (substr($wa, 0, 1) === '8') $wa = '0' . $wa;
            $request->merge(['whatsapp_number' => $wa]);
        }

        if ($request->type === 'pascabayar') {
            return $this->inquiryPostpaid($request);
        }

        $user = auth()->user();

        $isDuplicate = TransactionPpobIak::where('user_id', $user->id_pengguna)
            ->where('customer_id', $request->customer_id)
            ->where('product_code', $request->product_code)
            ->where('created_at', '>=', now()->subMinutes(3))
            ->exists();

        if ($isDuplicate) {
            Log::warning('LOG LOG - [API Mobile] Trx Prabayar Ditolak: Duplikat dalam 3 menit', $request->all());
            return response()->json(['success' => false, 'message' => 'Transaksi ke nomor & produk yang sama sedang diproses. Tunggu 3 menit.']);
        }

        $lockKey = 'topup_' . $user->id_pengguna . '_' . $request->product_code . '_' . $request->customer_id;
        $lock = Cache::lock($lockKey, 10);

        if (!$lock->get()) {
            Log::warning('LOG LOG - [API Mobile] Trx Prabayar Ditolak: Atomic Lock Active', $request->all());
            return response()->json(['success' => false, 'message' => 'Transaksi sedang diproses, jangan klik berkali-kali.']);
        }

        try {
            $product = IakPricelistPrepaid::where('code', $request->product_code)->first();
            if (!$product) return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.']);

            $paymentMethod = $request->payment_method ?? '';
            $isSaldoOrCash = in_array(strtoupper($paymentMethod), ['#SALDO', 'CASH', 'POTONG SALDO', 'SALDO']) || empty($paymentMethod);
            $isAdmin4 = ($user->id_pengguna == 4);

            $refId = 'P' . date('ymd') . rand(1000, 9999);

            // =====================================================
            // JIKA BUKAN SALDO (PAKAI PAYMENT GATEWAY)
            // =====================================================
            if (!$isSaldoOrCash) {
                $transaction = TransactionPpobIak::create([
                    'user_id'         => $user->id_pengguna,
                    'ref_id'          => $refId,
                    'type'            => 'prabayar',
                    'customer_id'     => $request->customer_id,
                    'product_code'    => $request->product_code,
                    'whatsapp_number' => $request->whatsapp_number,
                    'status'          => 'PENDING',
                    'price'           => $product->price,
                    'message'         => 'Menunggu Pembayaran Gateway'
                ]);

                $amount = (int) $product->price;
                $cleanPaymentMethod = strtoupper(trim(str_replace('#', '', $paymentMethod)));

                // -------------------------------------------------
                // A1. JALUR DOKU JOKUL
                // -------------------------------------------------
                if (in_array($cleanPaymentMethod, ['DOKU', 'DOKU_JOKUL'])) {
                    Log::info("[API MOBILE PPOB] Memproses pembayaran via DOKU Jokul untuk: " . $refId);

                    try {
                        $dokuService = new DokuJokulService();
                        $paymentUrl = $dokuService->createPayment($refId, $amount);

                        if (empty($paymentUrl)) {
                            throw new \Exception('Response payment URL DOKU kosong.');
                        }

                        $transaction->update(['payment_url' => $paymentUrl]);

                        return response()->json([
                            'success' => true,
                            'message' => 'Pesanan berhasil dibuat. Mengalihkan ke DOKU...',
                            'payment_url' => $paymentUrl,
                            'redirect_url' => '/riwayatppob'
                        ]);
                    } catch (\Exception $e) {
                        Log::error('LOG LOG - [API Mobile] Error DOKU Prabayar: ' . $e->getMessage());
                        $transaction->update(['status' => 'FAILED', 'message' => 'Gagal generate link DOKU']);
                        return response()->json(['success' => false, 'message' => 'Gagal membuat transaksi DOKU: ' . $e->getMessage()]);
                    }
                }
               // -------------------------------------------------
                // A2. JALUR DANA (DIRECT, DIRECT DEBIT, BINDING)
                // -------------------------------------------------
                if (in_array($cleanPaymentMethod, ['DANA', 'DANA_BINDING', 'DANA_DIRECT_DEBIT'])) {
                    Log::info("[API MOBILE PPOB] Memproses DANA untuk: " . $refId);

                    $danaResult = $this->processDanaPaymentGateway($transaction, $user, $cleanPaymentMethod, 'prabayar');

                    if (!$danaResult['success']) {
                        $transaction->update(['status' => 'FAILED', 'message' => $danaResult['message']]);
                        return response()->json(['success' => false, 'message' => $danaResult['message']]);
                    }

                    if (!$danaResult['is_instant']) {
                        $transaction->update(['payment_url' => $danaResult['payment_url']]);
                        return response()->json([
                            'success' => true,
                            'message' => 'Pesanan dibuat. Mengalihkan ke DANA...',
                            'payment_url' => $danaResult['payment_url'],
                            'redirect_url' => '/riwayatppob'
                        ]);
                    }

                    // AUTO DEBIT INSTAN BERHASIL -> TEMBAK IAK
                    $transaction->update(['status' => 'PROCESS', 'message' => 'Sedang mengirim pesanan ke pusat...']);

                    $sign = md5($this->username . $this->apiKey . $refId);
                    $responseIak = Http::post($this->prepaidBaseUrl . '/api/top-up', [
                        'username'     => $this->username,
                        'customer_id'  => $request->customer_id,
                        'product_code' => $request->product_code,
                        'ref_id'       => $refId,
                        'sign'         => $sign
                    ]);

                    $resultIak = $responseIak->json();

                    if ($responseIak->successful() && isset($resultIak['data'])) {
                        $apiCode = $resultIak['data']['rc'] ?? ($resultIak['data']['message'] == 'PROCESS' ? '39' : null);
                        $codeInfo = IakPrepaidResponseCode::where('code', $apiCode)->first();
                        $statusMap = [0 => 'PROCESS', 1 => 'SUCCESS', 2 => 'FAILED'];
                        $apiStatus = $resultIak['data']['status'] ?? 0;

                        $finalStatus = $codeInfo ? strtoupper($codeInfo->status) : ($statusMap[$apiStatus] ?? 'PROCESS');
                        $finalMessage = $codeInfo ? $codeInfo->description : ($resultIak['data']['message'] ?? 'Proses');

                        $transaction->update([
                            'status'  => $finalStatus,
                            'tr_id'   => $resultIak['data']['tr_id'] ?? null,
                            'sn'      => $resultIak['data']['sn'] ?? null,
                            'message' => $finalMessage
                        ]);

                        if ($finalStatus == 'FAILED') {
                            return response()->json(['success' => false, 'message' => 'Gagal di Provider (Saldo DANA akan direfund): ' . $transaction->message]);
                        }

                        DB::table('Pengguna')->where('id_pengguna', 4)->decrement('balance_iak', $amount);
                        return response()->json(['success' => true, 'message' => 'Transaksi berhasil diproses otomatis via DANA.', 'data' => $transaction]);
                    }

                    $transaction->update(['status' => 'FAILED', 'message' => $resultIak['data']['message'] ?? 'API Error']);
                    return response()->json(['success' => false, 'message' => 'Error Provider. Saldo DANA akan diproses refund.']);
                }
                // -------------------------------------------------
                // C. JALUR TRIPAY (QRIS, VA, Minimarket, dll)
                // -------------------------------------------------
                else {
                    Log::info("[API MOBILE PPOB] Memproses pembayaran via TRIPAY untuk: " . $refId);

                    $tripayMode = Api::getValue('TRIPAY_MODE', 'global', 'sandbox');
                    $apiKey = Api::getValue('TRIPAY_API_KEY', $tripayMode);
                    $privateKey = Api::getValue('TRIPAY_PRIVATE_KEY', $tripayMode);
                    $merchantCode = Api::getValue('TRIPAY_MERCHANT_CODE', $tripayMode);

                    if (empty($apiKey) || empty($privateKey) || empty($merchantCode)) {
                        return response()->json(['success' => false, 'message' => 'Sistem Error: Konfigurasi Tripay belum disetting.']);
                    }

                    $tripayUrl = $tripayMode === 'production' ? 'https://tripay.co.id/api/transaction/create' : 'https://tripay.co.id/api-sandbox/transaction/create';
                    $signature = hash_hmac('sha256', $merchantCode.$refId.$amount, $privateKey);

                    $responseTripay = Http::withHeaders([
                        'Authorization' => 'Bearer ' . trim($apiKey)
                    ])->post($tripayUrl, [
                        'method'         => $cleanPaymentMethod,
                        'merchant_ref'   => $refId,
                        'amount'         => $amount,
                        'customer_name'  => $user->nama_lengkap ?? 'Member Sancaka',
                        'customer_email' => $user->email ?? 'no-reply@sancaka.com',
                        'customer_phone' => $user->no_hp ?? '081234567890',
                        'order_items'    => [['sku' => $product->code, 'name' => $product->description, 'price' => $amount, 'quantity' => 1]],
                        'return_url'     => route('tripay.return', ['reference' => $refId, 'jenis' => 'ppob']),
                        'signature'      => $signature
                    ]);

                    $resTripay = $responseTripay->json();

                    if ($responseTripay->successful() && isset($resTripay['success']) && $resTripay['success']) {
                        $transaction->update(['payment_url' => $resTripay['data']['checkout_url']]);

                        return response()->json([
                            'success' => true,
                            'message' => 'Pesanan berhasil dibuat. Mengalihkan ke pembayaran...',
                            'payment_url' => $resTripay['data']['checkout_url'],
                            'redirect_url' => '/riwayatppob'
                        ]);
                    } else {
                        $safeLogData = is_array($resTripay) ? $resTripay : ['raw_response' => (string) $resTripay];
                        Log::error('LOG LOG - [API Mobile] Error Tripay Prabayar:', $safeLogData);
                        $transaction->update(['status' => 'FAILED', 'message' => 'Tripay: ' . ($safeLogData['message'] ?? 'Error')]);
                        return response()->json(['success' => false, 'message' => 'Gagal Payment Gateway: ' . ($safeLogData['message'] ?? 'Error')]);
                    }
                }
            }

           // ========================================================
            // SISA KODE DI BAWAH HANYA JALAN JIKA PAKAI SALDO / CASH
            // ========================================================

            $admin = User::find(4);
            if (!$admin) {
                return response()->json(['success' => false, 'message' => 'Sistem Error: Admin utama tidak ditemukan.']);
            }

            $isCash = (strtoupper($paymentMethod) === 'CASH');

            // 1. PENGAMANAN: Jika pilih CASH, pastikan itu Admin
            if ($isCash && !$isAdmin4) {
                return response()->json(['success' => false, 'message' => 'Metode Pembayaran CASH hanya khusus untuk Admin. Silakan gunakan metode bayar Gateway atau Saldo.']);
            }

            // 2. CEK SALDO LOKAL: Jika Potong Saldo (bukan CASH), pastikan saldo user cukup
            if (!$isCash && $user->saldo < $product->price) {
                return response()->json(['success' => false, 'message' => 'Saldo Anda tidak mencukupi.']);
            }

            // 3. CEK SALDO PUSAT: Pastikan balance_iak Admin cukup untuk nembak API IAK
            if ($admin->balance_iak < $product->price) {
                Log::error('LOG LOG - Transaksi Gagal: Saldo IAK Pusat (ID 4) tidak cukup!');
                return response()->json(['success' => false, 'message' => 'Maaf, transaksi gagal diproses saat ini (Gangguan Pusat).']);
            }

            $sign = md5($this->username . $this->apiKey . $refId);

            $transaction = TransactionPpobIak::create([
                'user_id'         => $user->id_pengguna,
                'ref_id'          => $refId,
                'type'            => 'prabayar',
                'customer_id'     => $request->customer_id,
                'product_code'    => $request->product_code,
                'whatsapp_number' => $request->whatsapp_number,
                'status'          => 'PROCESS',
            ]);

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
                $finalMessage = $codeInfo ? $codeInfo->description : ($result['data']['message'] ?? 'Proses');

                $transaction->update([
                    'status'  => $finalStatus,
                    'price'   => $product->price,
                    'tr_id'   => $result['data']['tr_id'] ?? null,
                    'sn'      => $result['data']['sn'] ?? null,
                    'message' => $finalMessage
                ]);

               if ($finalStatus == 'FAILED') {
                    return response()->json(['success' => false, 'message' => 'Gagal: ' . $transaction->message]);
                }

                // FIX 1: Tambahkan 'PENDING' di sini
               if (in_array($finalStatus, ['PROCESS', 'SUCCESS', 'PENDING'])) {
                $harga = (float) $transaction->price;

                // POTONG SALDO LOKAL
                if (!$isCash) {
                    DB::table('Pengguna')
                        ->where('id_pengguna', $user->id_pengguna)
                        ->decrement('saldo', $harga);
                }

                // POTONG SALDO PUSAT (ADMIN ID 4)
                DB::table('Pengguna')
                    ->where('id_pengguna', 4)
                    ->decrement('balance_iak', $harga);

                Log::info("LOG LOG - RAW DB Saldo Terpotong (Pascabayar). User ID: {$user->id_pengguna}, Harga: {$harga}");
            }

                return response()->json([
                    'success' => true,
                    'message' => 'Transaksi berhasil diproses.',
                    'data' => $transaction,
                    'redirect_url' => '/riwayatppob' // Tambahkan baris ini
                ]);
            }

            $transaction->update(['status' => 'FAILED', 'message' => $result['data']['message'] ?? 'API Error']);
            return response()->json(['success' => false, 'message' => 'Error Provider: ' . ($result['data']['message'] ?? 'Unknown')]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Koneksi terputus: ' . $e->getMessage()]);
        } finally {
            optional($lock)->release();
        }
    }

    // ========================================================
    // 4. FUNGSI PRIVATE: INQUIRY PASCABAYAR
    // ========================================================
    private function inquiryPostpaid(Request $request)
    {
        Log::info('LOG LOG - [API Mobile] inquiryPostpaid (Private) Executed:', $request->all());

        $refId = 'I' . date('ymd') . rand(1000, 9999);
        $sign = md5($this->username . $this->apiKey . $refId);
        $productCode = strtoupper($request->product_code);

        $payload = [
            'commands' => 'inq-pasca',
            'username' => $this->username,
            'code'     => $productCode,
            'hp'       => $request->customer_id,
            'ref_id'   => $refId,
            'sign'     => $sign
        ];

        if (in_array($productCode, ['BPJS', 'BPJSTK', 'BPJSTKPU'])) $payload['month'] = $request->month ?? 1;
        if (str_starts_with($productCode, 'ESAMSAT.')) $payload['nomor_identitas'] = $request->nomor_identitas ?? '';
        if ($request->filled('amount')) $payload['desc'] = ['amount' => (int) $request->amount];
        if (str_starts_with($productCode, 'PBB')) $payload['year'] = $request->year ?? date('Y');

        try {
            $user = auth()->user();

            $response = Http::post($this->postpaidBaseUrl . '/api/v1/bill/check', $payload);
            $result = $response->json();

            if ($response->successful() && isset($result['data']) && $result['data']['response_code'] === '00') {
                $data = $result['data'];
                $transaction = TransactionPpobIak::create([
                    'user_id'         => $user->id_pengguna,
                    'ref_id'          => $refId,
                    'tr_id'           => $data['tr_id'],
                    'type'            => 'pascabayar',
                    'customer_id'     => $request->customer_id,
                    'product_code'    => $productCode,
                    'price'           => $data['price'],
                    'whatsapp_number' => $request->whatsapp_number,
                    'status'          => 'PENDING',
                    'message'         => 'Inquiry Sukses (Menunggu Pembayaran)'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Inquiry Tagihan Berhasil',
                    'data'    => array_merge($data, ['internal_ref' => $refId])
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Tagihan tidak ditemukan atau sudah lunas.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Koneksi terputus: ' . $e->getMessage()]);
        }
    }

    // ========================================================
    // 5. SEMUA FUNGSI INQUIRY PRABAYAR (PLN, OVO, GAME)
    // ========================================================
    public function inquiryPln(Request $request)
    {
        Log::info('LOG LOG - [API Mobile] inquiryPln Payload Masuk:', $request->all());
        $sign = md5($this->username . $this->apiKey . $request->customer_id);
        try {
            $response = Http::post($this->prepaidBaseUrl . '/api/inquiry-pln', [
                'username' => $this->username, 'customer_id' => $request->customer_id, 'sign' => $sign
            ]);
            $result = $response->json();
            if ($response->successful() && isset($result['data']) && $result['data']['status'] == '1') {
                return response()->json(['success' => true, 'data' => $result['data']]);
            }
            return response()->json(['success' => false, 'message' => $result['data']['message'] ?? 'ID PLN Salah']);
        } catch (\Exception $e) { return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]); }
    }

    public function inquiryOvo(Request $request)
    {
        Log::info('LOG LOG - [API Mobile] inquiryOvo Payload Masuk:', $request->all());
        $sign = md5($this->username . $this->apiKey . $request->customer_id);
        try {
            $response = Http::post($this->prepaidBaseUrl . '/api/inquiry-ovo', [
                'username' => $this->username, 'customer_id' => $request->customer_id, 'sign' => $sign
            ]);
            $result = $response->json();
            if ($response->successful() && isset($result['data']) && $result['data']['status'] == '1') {
                return response()->json(['success' => true, 'data' => $result['data']]);
            }
            return response()->json(['success' => false, 'message' => $result['data']['message'] ?? 'ID OVO Salah']);
        } catch (\Exception $e) { return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]); }
    }

    public function getGameList(Request $request)
    {
        Log::info('LOG LOG - [API Mobile] getGameList Hit');
        $sign = md5($this->username . $this->apiKey . 'gc');
        try {
            $response = Http::post($this->prepaidBaseUrl . '/api/gamelist', [
                'username' => $this->username, 'sign' => $sign
            ]);
            $result = $response->json();
            if ($response->successful() && isset($result['data']['rc']) && $result['data']['rc'] == '00') {
                return response()->json(['success' => true, 'data' => $result['data']['gamelist'] ?? []]);
            }
            return response()->json(['success' => false, 'message' => 'Gagal load game list']);
        } catch (\Exception $e) { return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]); }
    }

    public function inquiryGameFormat(Request $request)
    {
        Log::info('LOG LOG - [API Mobile] inquiryGameFormat Payload Masuk:', $request->all());
        $sign = md5($this->username . $this->apiKey . $request->game_code);
        try {
            $response = Http::post($this->prepaidBaseUrl . '/api/game/format', [
                'username' => $this->username, 'game_code' => $request->game_code, 'sign' => $sign
            ]);
            $result = $response->json();
            if ($response->successful() && isset($result['data']) && $result['data']['status'] == 1) {
                return response()->json(['success' => true, 'data' => $result['data']]);
            }
            return response()->json(['success' => false, 'message' => 'Format tidak ada']);
        } catch (\Exception $e) { return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]); }
    }

    public function inquiryGameServer(Request $request)
    {
        Log::info('LOG LOG - [API Mobile] inquiryGameServer Payload Masuk:', $request->all());
        $sign = md5($this->username . $this->apiKey . $request->game_code);
        try {
            $response = Http::post($this->prepaidBaseUrl . '/api/inquiry-game-server', [
                'username' => $this->username, 'game_code' => $request->game_code, 'sign' => $sign
            ]);
            $result = $response->json();
            if ($response->successful() && isset($result['data']) && $result['data']['status'] == 1) {
                return response()->json(['success' => true, 'data' => $result['data']]);
            }
            return response()->json(['success' => false, 'message' => 'Server list tidak ada']);
        } catch (\Exception $e) { return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]); }
    }

   // ========================================================
    // --- FUNGSI BARU: RIWAYAT TRANSAKSI & FILTER (ADMIN/USER) ---
    // ========================================================
    public function history(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                \Illuminate\Support\Facades\Log::error('LOG LOG - [API Mobile] Error: User Auth bernilai null');
                return response()->json(['success' => false, 'message' => 'Sesi login tidak valid / Token Kadaluarsa.']);
            }

            $isAdmin = ($user->id_pengguna == 4 || strtolower($user->role) === 'admin');
            $query = TransactionPpobIak::query();

            if (!$isAdmin) {
                $query->where('user_id', $user->id_pengguna);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('ref_id', 'LIKE', "%{$search}%")
                      ->orWhere('customer_id', 'LIKE', "%{$search}%")
                      ->orWhere('product_code', 'LIKE', "%{$search}%")
                      ->orWhere('sn', 'LIKE', "%{$search}%");
                });
            }

            $filterWaktu = $request->query('filter_waktu', 'Bulan Ini');
            $now = \Carbon\Carbon::now();

            if ($filterWaktu == 'Hari Ini') {
                $query->whereDate('created_at', $now->toDateString());
            } elseif ($filterWaktu == 'Kemarin') {
                $query->whereDate('created_at', $now->subDay()->toDateString());
            } elseif ($filterWaktu == 'Bulan Ini') {
                $query->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year);
            } elseif ($filterWaktu == 'Bulan Kemarin') {
                $lastMonth = $now->copy()->subMonth();
                $query->whereMonth('created_at', $lastMonth->month)->whereYear('created_at', $lastMonth->year);
            } elseif ($filterWaktu == 'Tahun Ini') {
                $query->whereYear('created_at', $now->year);
            }

            $transactions = $query->orderBy('created_at', 'desc')->paginate(20);
            $mappedItems = [];

            try {
                $prepaidCodes = $transactions->where('type', 'prabayar')->pluck('product_code')->filter()->unique()->toArray();
                $icons = [];
                if (!empty($prepaidCodes)) {
                    $icons = \App\Models\IakPricelistPrepaid::whereIn('code', $prepaidCodes)
                                ->pluck('icon_url', 'code');
                }

                $usersData = [];
                if ($isAdmin) {
                    $userIds = $transactions->pluck('user_id')->filter()->unique()->toArray();
                    if (!empty($userIds)) {
                        // Mengubah pengambilan data agar mencakup nama_lengkap dan store_name
                        $usersData = \Illuminate\Support\Facades\DB::table('Pengguna')
                                        ->whereIn('id_pengguna', $userIds)
                                        ->get(['id_pengguna', 'nama_lengkap', 'store_name'])
                                        ->keyBy('id_pengguna');
                    }
                }

                foreach ($transactions->items() as $trx) {
                    $item = $trx->toArray();
                    $item['icon_url'] = ($item['type'] == 'prabayar') ? ($icons[$item['product_code']] ?? null) : null;

                    if ($isAdmin) {
                        if (!empty($item['user_id'])) {
                            $userData = $usersData[$item['user_id']] ?? null;

                            $item['sumber_order'] = 'Aplikasi / User';
                            $item['pemesan_id']   = $item['user_id'];
                            $item['pemesan_nama'] = $userData->nama_lengkap ?? 'User Tidak Diketahui';
                            $item['pemesan_toko'] = $userData->store_name ?? '-';

                            // Tetap dipertahankan untuk backward compatibility (jika frontend lama masih membaca field ini)
                            $item['nama_pembeli'] = $item['pemesan_nama'];
                        } else {
                            $item['sumber_order'] = 'Website';
                            $item['pemesan_id']   = null;
                            $item['pemesan_nama'] = 'Website';
                            $item['pemesan_toko'] = '-';

                            // Tetap dipertahankan untuk backward compatibility
                            $item['nama_pembeli'] = 'Website';
                        }
                    }
                    $mappedItems[] = $item;
                }

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('LOG LOG - Gagal ekstrak data riwayat: ' . $e->getMessage());
                $mappedItems = $transactions->items();
            }

            return response()->json([
                'success'  => true,
                'data'     => [
                    'current_page' => $transactions->currentPage(),
                    'data'         => $mappedItems,
                    'last_page'    => $transactions->lastPage(),
                    'total'        => $transactions->total(),
                ],
                'is_admin' => $isAdmin
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('LOG LOG - [API Mobile] Error History Backend: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error Server Sancaka: ' . $e->getMessage()], 500);
        }
    }

    // ========================================================
    // 6. EKSEKUSI PEMBAYARAN PASCABAYAR (SETELAH INQUIRY)
    // ========================================================
    public function payPostpaid(Request $request)
    {
        Log::info('LOG LOG - [API Mobile] payPostpaid Payload Masuk:', $request->all());
        $request->validate(['tr_id' => 'required|string']);

        $transaction = TransactionPpobIak::where('tr_id', $request->tr_id)->first();
        if (!$transaction) return response()->json(['success' => false, 'message' => 'Transaksi tidak ditemukan']);

        $user = auth()->user();

        if ($transaction->user_id != $user->id_pengguna) {
           return response()->json(['success' => false, 'message' => 'Akses ditolak. Transaksi ini bukan milik Anda.']);
        }

        if ($transaction->status === 'SUCCESS') {
            return response()->json(['success' => false, 'message' => 'Tagihan ini sudah berhasil dibayar sebelumnya.']);
        }
        if ($transaction->status === 'PROCESS') {
            return response()->json(['success' => false, 'message' => 'Pembayaran tagihan ini sedang diproses oleh sistem, mohon tunggu.']);
        }

        $paymentMethod = $request->payment_method ?? '';
        $isSaldoOrCash = in_array(strtoupper($paymentMethod), ['#SALDO', 'CASH', 'POTONG SALDO', 'SALDO']) || empty($paymentMethod);
        $isAdmin4 = ($user->id_pengguna == 4);

        // =====================================================
        // JIKA BUKAN SALDO (PAKAI PAYMENT GATEWAY)
        // =====================================================
        if (!$isSaldoOrCash) {
            $amount = (int) $transaction->price;
            $merchantRef = 'PASCA' . $transaction->tr_id;

            // Bersihkan string dari spasi dan tanda #
            $cleanPaymentMethod = strtoupper(trim(str_replace('#', '', $paymentMethod)));

            // -------------------------------------------------
            // A. JALUR DOKU JOKUL
            // -------------------------------------------------
            if (in_array($cleanPaymentMethod, ['DOKU', 'DOKU_JOKUL'])) {
                Log::info("[API MOBILE PPOB] Memproses pascabayar via DOKU Jokul untuk: " . $merchantRef);

                try {
                    $dokuService = new DokuJokulService();
                    $paymentUrl = $dokuService->createPayment($merchantRef, $amount);

                    if (empty($paymentUrl)) {
                        throw new \Exception('Response payment URL DOKU kosong.');
                    }

                    $transaction->update(['status' => 'PENDING', 'message' => 'Menunggu Pembayaran Gateway', 'payment_url' => $paymentUrl]);
                    return response()->json([
                        'success' => true,
                        'message' => 'Silakan selesaikan pembayaran DOKU.',
                        'payment_url' => $paymentUrl,
                        'redirect_url' => '/riwayatppob'
                    ]);
                } catch (\Exception $e) {
                    Log::error('LOG LOG - [API Mobile] Error DOKU Pascabayar: ' . $e->getMessage());
                    return response()->json(['success' => false, 'message' => 'Gagal membuat transaksi DOKU: ' . $e->getMessage()]);
                }
            }
            // -------------------------------------------------
            // B. JALUR DANA (DIRECT, DIRECT DEBIT, BINDING)
            // -------------------------------------------------
            if (in_array($cleanPaymentMethod, ['DANA', 'DANA_BINDING', 'DANA_DIRECT_DEBIT'])) {
                Log::info("[API MOBILE PPOB] Memproses DANA Pascabayar untuk: " . $merchantRef);

                $danaResult = $this->processDanaPaymentGateway($transaction, $user, $cleanPaymentMethod, 'pascabayar');

                if (!$danaResult['success']) {
                    return response()->json(['success' => false, 'message' => $danaResult['message']]);
                }

                if (!$danaResult['is_instant']) {
                    $transaction->update(['status' => 'PENDING', 'message' => 'Menunggu Pembayaran Gateway', 'payment_url' => $danaResult['payment_url']]);
                    return response()->json([
                        'success' => true,
                        'message' => 'Silakan selesaikan pembayaran DANA.',
                        'payment_url' => $danaResult['payment_url'],
                        'redirect_url' => '/riwayatppob'
                    ]);
                }

                // AUTO DEBIT INSTAN BERHASIL -> TEMBAK IAK PASCABAYAR
                $transaction->update(['status' => 'PROCESS', 'message' => 'Sedang mengirim pembayaran ke pusat...']);

                $sign = md5($this->username . $this->apiKey . $transaction->tr_id);
                $responseIak = Http::timeout(45)->post($this->postpaidBaseUrl . '/api/v1/bill/check', [
                    'commands' => 'pay-pasca',
                    'username' => $this->username,
                    'tr_id'    => $transaction->tr_id,
                    'sign'     => $sign
                ]);

                $resultIak = $responseIak->json();

                if ($responseIak->successful() && isset($resultIak['data'])) {
                    $rc = $resultIak['data']['response_code'] ?? '';
                    $status = ($rc === '00') ? 'SUCCESS' : (($rc === '39') ? 'PROCESS' : 'FAILED');

                    if (in_array($status, ['PROCESS', 'SUCCESS'])) {
                        DB::table('Pengguna')->where('id_pengguna', 4)->decrement('balance_iak', (float) $transaction->price);
                    }

                    $transaction->update([
                        'status'  => $status,
                        'sn'      => $resultIak['data']['noref'] ?? null,
                        'message' => $resultIak['data']['message'] ?? 'Payment response received'
                    ]);

                    if ($status == 'FAILED') {
                        return response()->json(['success' => false, 'message' => 'Pembayaran gagal dari pusat (Hubungi Admin untuk Refund DANA): ' . $transaction->message]);
                    }

                    return response()->json(['success' => true, 'message' => 'Pembayaran Tagihan via DANA Berhasil Diproses!']);
                }

                $transaction->update(['status' => 'FAILED', 'message' => 'Invalid API Response dari Pusat']);
                return response()->json(['success' => false, 'message' => 'Gagal memproses pembayaran ke server pusat. Saldo DANA akan diproses refund.']);
            }
            // -------------------------------------------------
            // C. JALUR TRIPAY
            // -------------------------------------------------
            else {
                Log::info("[API MOBILE PPOB] Memproses pascabayar via TRIPAY untuk: " . $merchantRef);

                $tripayMode = Api::getValue('TRIPAY_MODE', 'global', 'sandbox');
                $apiKey = Api::getValue('TRIPAY_API_KEY', $tripayMode);
                $privateKey = Api::getValue('TRIPAY_PRIVATE_KEY', $tripayMode);
                $merchantCode = Api::getValue('TRIPAY_MERCHANT_CODE', $tripayMode);

                if (empty($apiKey) || empty($privateKey) || empty($merchantCode)) {
                    return response()->json(['success' => false, 'message' => 'Sistem Error: Konfigurasi Tripay belum disetting.']);
                }

                $tripayUrl = $tripayMode === 'production' ? 'https://tripay.co.id/api/transaction/create' : 'https://tripay.co.id/api-sandbox/transaction/create';
                $signature = hash_hmac('sha256', $merchantCode.$merchantRef.$amount, $privateKey);

                $responseTripay = Http::withHeaders([
                    'Authorization' => 'Bearer ' . trim($apiKey)
                ])->post($tripayUrl, [
                    'method'         => $cleanPaymentMethod,
                    'merchant_ref'   => $merchantRef,
                    'amount'         => $amount,
                    'customer_name'  => $user->nama_lengkap ?? 'Member Sancaka',
                    'customer_email' => $user->email ?? 'no-reply@sancaka.com',
                    'customer_phone' => $user->no_hp ?? '081234567890',
                    'order_items'    => [['sku' => 'TAGIHAN', 'name' => 'Tagihan ' . $transaction->product_code, 'price' => $amount, 'quantity' => 1]],
                    'return_url'     => env('FRONTEND_URL', url('/')) . '/riwayatppob',
                    'signature'      => $signature
                ]);

                $resTripay = $responseTripay->json();

                if ($responseTripay->successful() && isset($resTripay['success']) && $resTripay['success']) {
                    $transaction->update(['status' => 'PENDING', 'message' => 'Menunggu Pembayaran Gateway', 'payment_url' => $resTripay['data']['checkout_url']]);
                    return response()->json([
                        'success' => true,
                        'message' => 'Silakan selesaikan pembayaran.',
                        'payment_url' => $resTripay['data']['checkout_url'],
                        'redirect_url' => '/riwayatppob'
                    ]);
                } else {
                    $safeLogData = is_array($resTripay) ? $resTripay : ['raw_response' => (string) $resTripay];
                    Log::error('LOG LOG - [API Mobile] Error Tripay Pascabayar:', $safeLogData);
                    return response()->json(['success' => false, 'message' => 'Gagal Payment Gateway: ' . ($safeLogData['message'] ?? 'Error')]);
                }
            }
        }

       // ========================================================
        // SISA KODE DI BAWAH HANYA JALAN JIKA PAKAI SALDO / CASH
        // ========================================================

        $admin = User::find(4);
        if (!$admin) {
            return response()->json(['success' => false, 'message' => 'Sistem Error: Admin utama tidak ditemukan.']);
        }

        $isCash = (strtoupper($paymentMethod) === 'CASH');

        // 1. PENGAMANAN: Jika pilih CASH, pastikan itu Admin
        if ($isCash && !$isAdmin4) {
            return response()->json(['success' => false, 'message' => 'Metode Pembayaran CASH hanya khusus untuk Admin. Silakan gunakan metode bayar Gateway atau Saldo.']);
        }

        // 2. CEK SALDO LOKAL: Jika Potong Saldo (bukan CASH), pastikan saldo user cukup
        if (!$isCash && $user->saldo < $transaction->price) {
            return response()->json(['success' => false, 'message' => 'Saldo Anda tidak mencukupi untuk membayar tagihan ini.']);
        }

        // 3. CEK SALDO PUSAT: Pastikan balance_iak Admin cukup untuk nembak API IAK
        if ($admin->balance_iak < $transaction->price) {
            Log::error('LOG LOG - Transaksi Gagal: Saldo IAK Pusat (ID 4) tidak cukup untuk Pascabayar!');
            return response()->json(['success' => false, 'message' => 'Maaf, transaksi gagal diproses saat ini (Gangguan Pusat).']);
        }

        $lock = Cache::lock('pay_pasca_' . $transaction->tr_id, 10);
        if (!$lock->get()) {
            Log::warning('LOG LOG - [API Mobile] Atomic Lock Bekerja untuk tr_id: ' . $transaction->tr_id);
            return response()->json(['success' => false, 'message' => 'Permintaan sedang diproses, jangan klik berkali-kali.']);
        }

        try {
            $transaction->update(['status' => 'PROCESS', 'message' => 'Sedang mengirim pembayaran ke pusat...']);

            $sign = md5($this->username . $this->apiKey . $transaction->tr_id);
            $response = Http::timeout(45)->post($this->postpaidBaseUrl . '/api/v1/bill/check', [
                'commands' => 'pay-pasca',
                'username' => $this->username,
                'tr_id'    => $transaction->tr_id,
                'sign'     => $sign
            ]);

            $result = $response->json();

           if ($response->successful() && isset($result['data'])) {
                $rc = $result['data']['response_code'] ?? '';
                $status = ($rc === '00') ? 'SUCCESS' : (($rc === '39') ? 'PROCESS' : 'FAILED');

                // FIX 2: Ubah $finalStatus jadi $status, tambah 'PENDING'
                if (in_array($status, ['PROCESS', 'SUCCESS', 'PENDING'])) {

                    // FIX 3: Ubah $product->price menjadi $transaction->price
                    $harga = (float) $transaction->price;

                    // POTONG SALDO LOKAL
                    if (!$isCash) {
                        DB::table('Pengguna')
                            ->where('id_pengguna', $user->id_pengguna)
                            ->decrement('saldo', $harga);
                    }

                    // POTONG SALDO PUSAT (ADMIN ID 4)
                    DB::table('Pengguna')
                        ->where('id_pengguna', 4)
                        ->decrement('balance_iak', $harga);

                    Log::info("LOG LOG - RAW DB Saldo Terpotong (Prabayar). User ID: {$user->id_pengguna}, Harga: {$harga}");
                }

                $transaction->update([
                    'status'  => $status,
                    'sn'      => $result['data']['noref'] ?? null,
                    'message' => $result['data']['message'] ?? 'Payment response received'
                ]);

                if ($status == 'FAILED') {
                    return response()->json(['success' => false, 'message' => 'Pembayaran gagal dari pusat: ' . $transaction->message]);
                }

                return response()->json(['success' => true, 'message' => 'Pembayaran Tagihan Berhasil Diproses!']);
            }

            $transaction->update(['status' => 'FAILED', 'message' => 'Invalid API Response dari Pusat']);
            return response()->json(['success' => false, 'message' => 'Gagal memproses pembayaran ke server pusat.']);

        } catch (\Exception $e) {
            Log::error('LOG LOG - [API Mobile] Timeout / Error Pay Pasca: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Koneksi lambat. Pembayaran sedang diproses di latar belakang. Cek riwayat berkala.']);
        } finally {
            optional($lock)->release();
        }
    }

    private function processDanaPaymentGateway($transaction, $user, $paymentMethod, $transactionType)
    {
        try {

            $danaSignature = app(\App\Services\DanaSignatureService::class);

            $trxId = ($transactionType === 'pascabayar')
                ? 'PASCA' . $transaction->tr_id
                : $transaction->ref_id;

            $amountValue = number_format((float)$transaction->price, 2, '.', '');

            $timestamp  = Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
            $validUpTo = Carbon::now('Asia/Jakarta')->addMinutes(30)->format('Y-m-d\TH:i:sP');

            $path = '/payment-gateway/v1.0/debit/payment-host-to-host.htm';

            $isBinding = ($paymentMethod === 'DANA_BINDING');

            // =========================
            // VALIDASI TOKEN BINDING
            // =========================
            if ($isBinding && empty($user->dana_access_token)) {
                return [
                    'success' => false,
                    'message' => 'Akun DANA belum terhubung.'
                ];
            }

            // =========================
            // CONFIG
            // =========================
            $merchantId = Api::getValue(
                'dana_sandbox_merchant_id',
                'sandbox',
                config('services.dana.merchant_id')
            );

            $partnerId = Api::getValue(
                'dana_sandbox_client_id',
                'sandbox',
                config('services.dana.x_partner_id')
            );

            if (Api::getValue('dana_production_mode', 'global', '0') == '1') {

                $merchantId = Api::getValue(
                    'dana_prod_merchant_id',
                    'production',
                    config('services.dana.merchant_id')
                );

                $partnerId = Api::getValue(
                    'dana_prod_client_id',
                    'production',
                    config('services.dana.x_partner_id')
                );
            }

            // =========================
            // MOBILE / WEB DETECTION
            // =========================
            $isMobile = request()->header('User-Agent') &&
                preg_match('/Android|iPhone|iPad|Mobile/i', request()->header('User-Agent'));

            // Mobile → deeplink
            // Web → normal redirect
            $isDeeplink = $isMobile ? 'Y' : 'N';

            // =========================
            // PAYLOAD
            // =========================
            $bodyArray = [

                "partnerReferenceNo" => $trxId,

                "merchantId" => $merchantId,

                "amount" => [
                    "value" => $amountValue,
                    "currency" => "IDR"
                ],

                "validUpTo" => $validUpTo,

                "urlParams" => [
                    [
                        "url" => env('FRONTEND_URL', url('/')) .
                            '/dana/return?partnerReferenceNo=' . $trxId,

                        "type" => "PAY_RETURN",

                        "isDeeplink" => $isDeeplink
                    ],
                    [
                        "url" => url('/dana/notify'),

                        "type" => "NOTIFICATION",

                        "isDeeplink" => "N"
                    ]
                ],

                // WAJIB ADA
                "payOptionDetails" => [
                    [
                        "payMethod" => "BALANCE",

                        // BIARKAN KOSONG
                        // sesuai sample resmi DANA
                        "payOption" => "",

                        "transAmount" => [
                            "value" => $amountValue,
                            "currency" => "IDR"
                        ]
                    ]
                ],

                "additionalInfo" => [

                    "mcc" => "5732",

                    "order" => [

                        "orderTitle" => substr(
                            "PPOB " . $trxId,
                            0,
                            64
                        ),

                        "merchantTransType" => "01",

                        // redirect biasa
                        "scenario" => $isBinding
                            ? "DIRECT_DEBIT"
                            : "REDIRECT",

                        "buyer" => [
                            "externalUserId" => (string) $user->id_pengguna,

                            "externalUserType" => "MERCHANT_USER",

                            "nickname" => Str::limit(
                                $user->nama_lengkap ?? 'Guest',
                                40
                            ),
                        ]
                    ],

                    "envInfo" => [

                        "sourcePlatform" => "IPG",

                        // SAMA seperti kode yang berhasil
                        "terminalType" => "SYSTEM",

                        "orderTerminalType" => $isMobile
                            ? "APP"
                            : "WEB",
                    ]
                ]
            ];

            // =========================
            // JSON
            // =========================
            $jsonBody = json_encode(
                $bodyArray,
                JSON_UNESCAPED_SLASHES |
                JSON_UNESCAPED_UNICODE
            );

            // =========================
            // ACCESS TOKEN
            // =========================
            $accessToken = $danaSignature->getAccessToken();

            // =========================
            // SIGNATURE
            // =========================
            $signature = $danaSignature->generateSignature(
                'POST',
                $path,
                $jsonBody,
                $timestamp
            );

            // =========================
            // HEADERS
            // =========================
            $headers = [

                'Authorization' => 'Bearer ' . $accessToken,

                'X-PARTNER-ID' => $partnerId,

                'X-EXTERNAL-ID' => Str::random(32),

                'X-TIMESTAMP' => $timestamp,

                'X-SIGNATURE' => $signature,

                'Content-Type' => 'application/json',

                'CHANNEL-ID' => '95221',

                'ORIGIN' => config('services.dana.origin'),
            ];

            // DIRECT DEBIT
            if ($isBinding) {
                $headers['Authorization-Customer']
                    = 'Bearer ' . $user->dana_access_token;
            }

            // =========================
            // REQUEST
            // =========================
            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $path);

            $result = $response->json();

            Log::info('DANA PPOB RESPONSE', [
                'status' => $response->status(),
                'result' => $result
            ]);

            // =========================
            // SUCCESS
            // =========================
            if (
                isset($result['responseCode']) &&
                $result['responseCode'] == '2005400'
            ) {

                $redirectUrl =
                    $result['webRedirectUrl']
                    ?? $result['appLinkUrl']
                    ?? null;

                return [
                    'success' => true,
                    'payment_url' => $redirectUrl,
                    'is_instant' => $isBinding
                ];
            }

            return [
                'success' => false,
                'message' => 'DANA Error [' .
                    ($result['responseCode'] ?? 'N/A') .
                    ']: ' .
                    ($result['responseMessage'] ?? 'Unknown')
            ];

        } catch (\Exception $e) {

            Log::error('DANA PPOB ERROR', [
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Koneksi ke DANA gagal.'
            ];
        }
    }

   /**
     * =========================================================================
     * BRIDGE WEBHOOK: Menjembatani panggilan dari CheckoutController Tripay
     * LENGKAP DENGAN IDEMPOTENCY & CONCURRENCY LOCK
     * =========================================================================
     */
    public static function processPpobCallback($merchantRef, $status = null, $data = [])
    {
        // Fleksibilitas: Jika CheckoutController hanya mengirim 1 array ($data)
        if (is_array($merchantRef) || is_object($merchantRef)) {
            $data = (array) $merchantRef;
            $merchantRef = $data['merchant_ref'] ?? $data['reference'] ?? null;
            $status = $data['status'] ?? 'PAID';
        }

        \Illuminate\Support\Facades\Log::info("LOG LOG: Masuk ke PPOB Callback. Ref: {$merchantRef} | Status: {$status}");

        // 1. CONCURRENCY LOCK: Pakai lockForUpdate() agar aman dari race condition
        // saat webhook masuk bersamaan di milidetik yang sama.
        $transaction = \App\Models\TransactionPpobIak::where('ref_id', $merchantRef)->lockForUpdate()->first();

        if (!$transaction) {
            \Illuminate\Support\Facades\Log::error("LOG LOG: Transaksi PPOB {$merchantRef} tidak ditemukan.");
            return;
        }

        // =====================================================================
        // 2. IDEMPOTENCY CHECK (PENCEGAH DOUBLE EKSEKUSI)
        // =====================================================================
        if ($status === 'PAID') {

            // Jika status di DB sudah PROCESS atau SUCCESS, hentikan eksekusi!
            // Artinya webhook ini adalah duplikat/retry dari Tripay.
            if (in_array($transaction->status, ['SUCCESS', 'PROCESS'])) {
                \Illuminate\Support\Facades\Log::info("LOG LOG: [IDEMPOTENCY] Transaksi {$merchantRef} sudah lunas/diproses (Status saat ini: {$transaction->status}). Skip nembak IAK untuk mencegah double saldo.");
                return;
            }

            \Illuminate\Support\Facades\Log::info("LOG LOG: PPOB Lunas via Gateway, eksekusi nembak API IAK dimulai...");

            // 3. AMBIL KREDENSIAL IAK DARI DATABASE
            $env = \App\Models\Api::getValue('IAK_MODE', 'global', 'development');
            $username = \App\Models\Api::getValue('IAK_USER_HP', $env);
            $apiKey = \App\Models\Api::getValue('IAK_API_KEY', $env);

            $prepaidBaseUrl = \App\Models\Api::getValue('IAK_PREPAID_BASE_URL', $env) ?: ($env === 'production' ? 'https://prepaid.iak.id' : 'https://prepaid.iak.dev');
            $postpaidBaseUrl = \App\Models\Api::getValue('IAK_POSTPAID_BASE_URL', $env) ?: ($env === 'production' ? 'https://mobilepulsa.net' : 'https://testpostpaid.mobilepulsa.net');

            // 4. UBAH STATUS JADI PROCESS (Tandai sedang dieksekusi)
            $transaction->update(['status' => 'PROCESS']);

            // =========================================================
            // 5. EKSEKUSI NEMBAK API IAK
            // =========================================================
            try {
                // A. JIKA PRABAYAR (Pulsa, Data, Token PLN, Game)
                if ($transaction->type === 'prabayar') {
                    $sign = md5($username . $apiKey . $merchantRef);

                    $responseIak = \Illuminate\Support\Facades\Http::post($prepaidBaseUrl . '/api/top-up', [
                        'username'     => $username,
                        'customer_id'  => $transaction->customer_id,
                        'product_code' => $transaction->product_code,
                        'ref_id'       => $merchantRef,
                        'sign'         => $sign
                    ]);

                    $resultIak = $responseIak->json();

                    if ($responseIak->successful() && isset($resultIak['data'])) {
                        $apiCode = $resultIak['data']['rc'] ?? ($resultIak['data']['message'] == 'PROCESS' ? '39' : null);
                        $codeInfo = \App\Models\IakPrepaidResponseCode::where('code', $apiCode)->first();
                        $statusMap = [0 => 'PROCESS', 1 => 'SUCCESS', 2 => 'FAILED'];
                        $apiStatus = $resultIak['data']['status'] ?? 0;

                        $finalStatus = $codeInfo ? strtoupper($codeInfo->status) : ($statusMap[$apiStatus] ?? 'PROCESS');
                        $finalMessage = $codeInfo ? $codeInfo->description : ($resultIak['data']['message'] ?? 'Proses');

                        $transaction->update([
                            'status'  => $finalStatus,
                            'tr_id'   => $resultIak['data']['tr_id'] ?? null,
                            'sn'      => $resultIak['data']['sn'] ?? null,
                            'message' => $finalMessage
                        ]);

                        if (in_array($finalStatus, ['PROCESS', 'SUCCESS', 'PENDING'])) {
                            \Illuminate\Support\Facades\DB::table('Pengguna')->where('id_pengguna', 4)->decrement('balance_iak', (float) $transaction->price);
                            \Illuminate\Support\Facades\Log::info("LOG LOG: IAK Prabayar Sukses Ditembak. Status: {$finalStatus}. Saldo IAK Pusat otomatis dipotong.");
                        }
                    } else {
                        $transaction->update(['status' => 'FAILED', 'message' => $resultIak['data']['message'] ?? 'API Error']);
                        \Illuminate\Support\Facades\Log::error("LOG LOG: Gagal nembak IAK Prabayar", $resultIak ?? []);
                    }
                }

                // B. JIKA PASCABAYAR (Bayar Tagihan)
                else {
                    $sign = md5($username . $apiKey . $transaction->tr_id);

                    $responseIak = \Illuminate\Support\Facades\Http::timeout(45)->post($postpaidBaseUrl . '/api/v1/bill/check', [
                        'commands' => 'pay-pasca',
                        'username' => $username,
                        'tr_id'    => $transaction->tr_id,
                        'sign'     => $sign
                    ]);

                    $resultIak = $responseIak->json();

                    if ($responseIak->successful() && isset($resultIak['data'])) {
                        $rc = $resultIak['data']['response_code'] ?? '';
                        $finalStatus = ($rc === '00') ? 'SUCCESS' : (($rc === '39') ? 'PROCESS' : 'FAILED');

                        $transaction->update([
                            'status'  => $finalStatus,
                            'sn'      => $resultIak['data']['noref'] ?? null,
                            'message' => $resultIak['data']['message'] ?? 'Payment response received'
                        ]);

                        if (in_array($finalStatus, ['PROCESS', 'SUCCESS', 'PENDING'])) {
                            \Illuminate\Support\Facades\DB::table('Pengguna')->where('id_pengguna', 4)->decrement('balance_iak', (float) $transaction->price);
                            \Illuminate\Support\Facades\Log::info("LOG LOG: IAK Pascabayar Sukses Ditembak. Status: {$finalStatus}. Saldo IAK Pusat otomatis dipotong.");
                        }
                    } else {
                        $transaction->update(['status' => 'FAILED', 'message' => 'Invalid API Response dari Pusat']);
                        \Illuminate\Support\Facades\Log::error("LOG LOG: Gagal nembak IAK Pascabayar", $resultIak ?? []);
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("LOG LOG: System Error saat nembak IAK via Webhook: " . $e->getMessage());
                // Set as PROCESS agar aman dan bisa dicheck manual
                $transaction->update(['status' => 'PROCESS', 'message' => 'Pending IAK Request due to Timeout/Error']);
            }

        }
        // =====================================================================
        // IDEMPOTENCY CHECK UNTUK STATUS GAGAL/EXPIRED
        // =====================================================================
        elseif (in_array($status, ['FAILED', 'EXPIRED'])) {

            // Kalau status di DB udah FAILED, SUCCESS, atau PROCESS, abaikan!
            if (in_array($transaction->status, ['SUCCESS', 'PROCESS', 'FAILED'])) {
                \Illuminate\Support\Facades\Log::info("LOG LOG: [IDEMPOTENCY] Mengabaikan status {$status} dari Webhook karena transaksi {$merchantRef} sudah berstatus {$transaction->status}.");
                return;
            }

            $transaction->status = 'FAILED';
            $transaction->save();
            \Illuminate\Support\Facades\Log::info("LOG LOG: Transaksi PPOB digagalkan (Expired/Failed dari Gateway).");
        }
    }
}
