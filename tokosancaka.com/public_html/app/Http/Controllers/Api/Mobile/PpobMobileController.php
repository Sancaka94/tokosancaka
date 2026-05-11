<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\TransactionPpobIak;
use App\Models\IakResponseCode;
use App\Models\IakPricelistPostpaid;
use App\Models\IakPrepaidResponseCode;
use App\Models\IakPricelistPrepaid;
use App\Models\Api;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use App\Services\DokuJokulService;

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
            } elseif ($opUpper === 'THREE') {
                $operator = 'Tri';
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

                // Bersihkan string dari spasi dan tanda #
                $cleanPaymentMethod = strtoupper(trim(str_replace('#', '', $paymentMethod)));

                // -------------------------------------------------
                // A. JALUR DOKU JOKUL
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
                // B. JALUR DANA (Diarahkan ke Web Portal Sancaka)
                // -------------------------------------------------
                elseif ($cleanPaymentMethod === 'DANA') {
                    Log::info("[API MOBILE PPOB] Mengalihkan DANA ke Web Sancaka");

                    $akunParams = $user->no_wa ?? $user->no_hp ?? $user->id_pengguna;
                    $paymentUrl = url('/pembayaran?akun=' . urlencode($akunParams));

                    $transaction->update(['payment_url' => $paymentUrl]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Pesanan berhasil dibuat. Mengalihkan ke pembayaran DANA...',
                        'payment_url' => $paymentUrl,
                        'redirect_url' => '/riwayatppob'
                    ]);
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
                        'return_url'     => env('FRONTEND_URL', url('/')) . '/riwayatppob',
                        'signature'      => $signature
                    ]);

                    $resTripay = $responseTripay->json();

                    if ($responseTripay->successful() && isset($resTripay['success']) && $resTripay['success']) {

                    $transaction->update(['payment_url' => $paymentUrl]);

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
            // SISA KODE DI BAWAH HANYA JALAN JIKA PAKAI SALDO / CASH (KHUSUS ADMIN)
            // ========================================================
            if (!$isAdmin4) {
                return response()->json(['success' => false, 'message' => 'Metode Pembayaran Saldo/Cash hanya khusus untuk Admin. Silakan gunakan metode bayar Gateway (DOKU/DANA).']);
            }

            if ($user->balance_iak < $product->price) {
                return response()->json(['success' => false, 'message' => 'Saldo Anda tidak mencukupi.']);
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

                if (in_array($finalStatus, ['PROCESS', 'SUCCESS'])) {
                    $user->balance_iak -= $product->price;
                    $user->save();
                }

                return response()->json(['success' => true, 'message' => 'Transaksi berhasil diproses.', 'data' => $transaction]);
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
    // --- LENGKAP: IAK + DIGI + FILTER TAB + FIX PAGINASI ---
    // ========================================================
    public function history(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                \Illuminate\Support\Facades\Log::error('LOG LOG - [API Mobile] Error: User Auth bernilai null');
                return response()->json(['success' => false, 'message' => 'Sesi login tidak valid / Token Kadaluarsa.']);
            }

            // Cek apakah user adalah Admin (ID 4 atau role admin)
            $isAdmin = ($user->id_pengguna == 4 || strtolower($user->role) === 'admin');

            // Tangkap filter Tab dari React Native (Default: prabayar)
            $typeFilter = $request->query('type', 'prabayar');

            // =========================================================
            // 1. BUILD QUERY UNTUK PPOB IAK
            // =========================================================
            $queryIak = \Illuminate\Support\Facades\DB::table('transaction_ppob_iaks')
                ->select(
                    'id',
                    'user_id',
                    'ref_id',
                    'type',
                    'customer_id',
                    'product_code',
                    'price',
                    'status',
                    'message',
                    'sn',
                    'payment_url',
                    'created_at',
                    'tr_id',
                    \Illuminate\Support\Facades\DB::raw("'IAK' as provider")
                )
                ->where('type', '=', $typeFilter); // Filter Tab

            // =========================================================
            // 2. BUILD QUERY UNTUK PPOB DIGIFLAZZ
            // =========================================================
            $queryDigi = \Illuminate\Support\Facades\DB::table('ppob_transactions')
                ->select(
                    'id',
                    'user_id',
                    'order_id as ref_id',
                    \Illuminate\Support\Facades\DB::raw("IF(`desc` LIKE '%postpaid%', 'pascabayar', 'prabayar') as type"),
                    'customer_no as customer_id',
                    'buyer_sku_code as product_code',
                    'selling_price as price',
                    'status',
                    'message',
                    'sn',
                    'payment_url',
                    'created_at',
                    \Illuminate\Support\Facades\DB::raw("NULL as tr_id"),
                    \Illuminate\Support\Facades\DB::raw("'DIGIFLAZZ' as provider")
                )
                ->whereRaw("IF(`desc` LIKE '%postpaid%', 'pascabayar', 'prabayar') = ?", [$typeFilter]); // Filter Tab

            // =========================================================
            // 3. TERAPKAN FILTER HAK AKSES (ADMIN BISA LIHAT SEMUA)
            // =========================================================
            if (!$isAdmin) {
                $queryIak->where('user_id', $user->id_pengguna);
                $queryDigi->where('user_id', $user->id_pengguna);
            }

            // =========================================================
            // 4. TERAPKAN FILTER PENCARIAN
            // =========================================================
            if ($request->filled('search')) {
                $search = $request->search;
                $queryIak->where(function($q) use ($search) {
                    $q->where('ref_id', 'LIKE', "%{$search}%")
                      ->orWhere('customer_id', 'LIKE', "%{$search}%")
                      ->orWhere('product_code', 'LIKE', "%{$search}%")
                      ->orWhere('sn', 'LIKE', "%{$search}%");
                });

                $queryDigi->where(function($q) use ($search) {
                    $q->where('order_id', 'LIKE', "%{$search}%")
                      ->orWhere('customer_no', 'LIKE', "%{$search}%")
                      ->orWhere('buyer_sku_code', 'LIKE', "%{$search}%")
                      ->orWhere('sn', 'LIKE', "%{$search}%");
                });
            }

            // =========================================================
            // 5. TERAPKAN FILTER WAKTU
            // =========================================================
            $filterWaktu = $request->query('filter_waktu', 'Bulan Ini');
            $now = \Carbon\Carbon::now();

            if ($filterWaktu == 'Hari Ini') {
                $queryIak->whereDate('created_at', $now->toDateString());
                $queryDigi->whereDate('created_at', $now->toDateString());
            } elseif ($filterWaktu == 'Kemarin') {
                $queryIak->whereDate('created_at', $now->subDay()->toDateString());
                $queryDigi->whereDate('created_at', $now->subDay()->toDateString());
            } elseif ($filterWaktu == 'Bulan Ini') {
                $queryIak->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year);
                $queryDigi->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year);
            } elseif ($filterWaktu == 'Bulan Kemarin') {
                $lastMonth = $now->copy()->subMonth();
                $queryIak->whereMonth('created_at', $lastMonth->month)->whereYear('created_at', $lastMonth->year);
                $queryDigi->whereMonth('created_at', $lastMonth->month)->whereYear('created_at', $lastMonth->year);
            } elseif ($filterWaktu == 'Tahun Ini') {
                $queryIak->whereYear('created_at', $now->year);
                $queryDigi->whereYear('created_at', $now->year);
            }

            // =========================================================
            // 6. GABUNGKAN LALU BUNGKUS DENGAN SUBQUERY (FIX LARAVEL BUG)
            // =========================================================
            $unionQuery = $queryIak->unionAll($queryDigi);

            // Harus dibungkus subquery agar paginate() menghitung tabel Digi juga
            $transactions = \Illuminate\Support\Facades\DB::table(\Illuminate\Support\Facades\DB::raw("({$unionQuery->toSql()}) as combined_table"))
                ->mergeBindings($unionQuery)
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            $mappedItems = [];

            try {
                $collection = collect($transactions->items());

                // Ekstrak ikon prabayar (Menggunakan IAK sebagai master ikon)
                $prepaidCodes = $collection->where('type', 'prabayar')->pluck('product_code')->filter()->unique()->toArray();
                $icons = [];
                if (!empty($prepaidCodes)) {
                    $icons = \App\Models\IakPricelistPrepaid::whereIn('code', $prepaidCodes)->pluck('icon_url', 'code')->toArray();
                }

                // Ambil data nama pengguna khusus untuk Admin
                $usersData = [];
                if ($isAdmin) {
                    $userIds = $collection->pluck('user_id')->filter()->unique()->toArray();
                    if (!empty($userIds)) {
                        $usersData = \Illuminate\Support\Facades\DB::table('Pengguna')
                                        ->whereIn('id_pengguna', $userIds)
                                        ->pluck('nama_lengkap', 'id_pengguna')
                                        ->toArray();
                    }
                }

                // Proses Mapping Akhir
                foreach ($transactions->items() as $trx) {
                    $item = (array) $trx;

                    // Sisipkan Icon
                    $item['icon_url'] = ($item['type'] == 'prabayar') ? ($icons[$item['product_code']] ?? null) : null;

                    // Sisipkan Nama Pembeli jika Admin
                    if ($isAdmin) {
                        $namaUser = $item['user_id'] ? ($usersData[$item['user_id']] ?? 'User ID ' . $item['user_id']) : 'Guest / Web';
                        $item['nama_pembeli'] = $namaUser;
                    }

                    $mappedItems[] = $item;
                }

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('LOG LOG - Gagal ekstrak data riwayat tambahan: ' . $e->getMessage());
                $mappedItems = json_decode(json_encode($transactions->items()), true);
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
            // B. JALUR DANA (Diarahkan ke Web Portal Sancaka)
            // -------------------------------------------------
            elseif ($cleanPaymentMethod === 'DANA') {
                $akunParams = $user->no_wa ?? $user->no_hp ?? $user->id_pengguna;
                $paymentUrl = url('/pembayaran?akun=' . urlencode($akunParams));
                $transaction->update(['status' => 'PENDING', 'message' => 'Menunggu Pembayaran Gateway', 'payment_url' => $paymentUrl]);

                return response()->json([
                    'success' => true,
                    'message' => 'Silakan selesaikan pembayaran DANA.',
                    'payment_url' => $paymentUrl,
                    'redirect_url' => '/riwayatppob'
                ]);
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
        // SISA KODE DI BAWAH HANYA JALAN JIKA PAKAI SALDO / CASH (KHUSUS ADMIN)
        // ========================================================
        if (!$isAdmin4) {
            return response()->json(['success' => false, 'message' => 'Metode Pembayaran Saldo/Cash hanya khusus untuk Admin. Silakan gunakan metode bayar Gateway (DOKU/DANA).']);
        }

        if ($user->balance_iak < $transaction->price) {
            return response()->json(['success' => false, 'message' => 'Saldo Anda tidak mencukupi untuk membayar tagihan ini.']);
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

                if (in_array($status, ['PROCESS', 'SUCCESS'])) {
                    $user->balance_iak -= $transaction->price;
                    $user->save();
                    Log::info('LOG LOG - Saldo Berhasil Dipotong: Rp ' . $transaction->price);
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
}
