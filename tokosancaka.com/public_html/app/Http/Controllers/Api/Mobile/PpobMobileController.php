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
        $type = $request->query('type'); // pulsa / data
        $nominal = $request->query('nominal');

        // ========================================================
        // FITUR BARU: TRANSLATOR NAMA OPERATOR (HP -> Database)
        // ========================================================
        if (!empty($operator)) {
            $opUpper = strtoupper($operator);
            if ($opUpper === 'SMARTFREN') {
                $operator = 'Smart';
            } elseif ($opUpper === 'THREE') {
                $operator = 'Tri'; // Jaga-jaga kalau di database nulisnya Tri
            } elseif ($opUpper === 'TELKOMSEL') {
                $operator = 'Telkomsel';
            }
        }
        // ========================================================

        $query = IakPricelistPrepaid::whereIn('status', ['Active', 'active', '1', 1]);

        // PERBAIKAN 1: Bikin pencarian operator lebih luwes (pakai raw query LOWER)
        if (!empty($operator)) {
            $query->whereRaw('LOWER(operator) LIKE ?', ['%' . strtolower($operator) . '%']);
        }

        // PERBAIKAN 2: Bikin pencarian type lebih luwes
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

        // Debugging Message: Biar tahu nilai apa yang sebenarnya dicari
        $debugMsg = "Operator: " . ($operator ?: 'Semua') . ", Type: " . ($type ?: 'Semua');

        return response()->json([
            'success' => false, // Ganti jadi false biar di React Native ke-trigger error handler
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

        // Format WA
        if ($request->filled('whatsapp_number')) {
            $wa = preg_replace('/[^0-9]/', '', $request->whatsapp_number);
            if (substr($wa, 0, 2) === '62') $wa = '0' . substr($wa, 2);
            elseif (substr($wa, 0, 1) === '8') $wa = '0' . $wa;
            $request->merge(['whatsapp_number' => $wa]);
        }

        // Lempar ke fungsi Inquiry jika Pascabayar
        if ($request->type === 'pascabayar') {
            return $this->inquiryPostpaid($request);
        }

        // --- LOGIKA PRABAYAR (TOP UP) ---
        $user = auth()->user();

        // Idempotency (Cegah Dobel)
        $isDuplicate = TransactionPpobIak::where('user_id', $user->id)
            ->where('customer_id', $request->customer_id)
            ->where('product_code', $request->product_code)
            ->where('created_at', '>=', now()->subMinutes(3))
            ->exists();

        if ($isDuplicate) {
            Log::warning('LOG LOG - [API Mobile] Trx Prabayar Ditolak: Duplikat dalam 3 menit', $request->all());
            return response()->json(['success' => false, 'message' => 'Transaksi ke nomor & produk yang sama sedang diproses. Tunggu 3 menit.']);
        }

        // Atomic Lock
        $lockKey = 'topup_' . $user->id . '_' . $request->product_code . '_' . $request->customer_id;
        $lock = Cache::lock($lockKey, 10);

        if (!$lock->get()) {
            Log::warning('LOG LOG - [API Mobile] Trx Prabayar Ditolak: Atomic Lock Active', $request->all());
            return response()->json(['success' => false, 'message' => 'Transaksi sedang diproses, jangan klik berkali-kali.']);
        }

        try {
            $product = IakPricelistPrepaid::where('code', $request->product_code)->first();
            if (!$product) return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.']);
            if ($user->balance_iak < $product->price) return response()->json(['success' => false, 'message' => 'Saldo Anda tidak mencukupi.']);

            $refId = 'P' . date('ymd') . rand(1000, 9999);
            $sign = md5($this->username . $this->apiKey . $refId);

            $transaction = TransactionPpobIak::create([
                'user_id'         => $user->id,
                'ref_id'          => $refId,
                'type'            => 'prabayar',
                'customer_id'     => $request->customer_id,
                'product_code'    => $request->product_code,
                'whatsapp_number' => $request->whatsapp_number,
                'status'          => 'PROCESS',
            ]);

            // Hit API IAK
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

                // Potong Saldo
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

        // Parameter Khusus (Sesuai web)
        if (in_array($productCode, ['BPJS', 'BPJSTK', 'BPJSTKPU'])) $payload['month'] = $request->month ?? 1;
        if (str_starts_with($productCode, 'ESAMSAT.')) $payload['nomor_identitas'] = $request->nomor_identitas ?? '';
        if ($request->filled('amount')) $payload['desc'] = ['amount' => (int) $request->amount];
        if (str_starts_with($productCode, 'PBB')) $payload['year'] = $request->year ?? date('Y');

        try {
            $response = Http::post($this->postpaidBaseUrl . '/api/v1/bill/check', $payload);
            $result = $response->json();

            if ($response->successful() && isset($result['data']) && $result['data']['response_code'] === '00') {
                $data = $result['data'];
                $transaction = TransactionPpobIak::create([
                    'user_id'         => auth()->id(),
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
        Log::info('LOG LOG - [API Mobile] history Payload Masuk:', $request->all());

        try {
            // Pastikan user terdeteksi
            $user = auth()->user();

            if (!$user) {
                Log::error('LOG LOG - [API Mobile] Error: User Auth bernilai null (Cek Middleware Sanctum)');
                return response()->json(['success' => false, 'message' => 'Sesi login tidak valid / Token Kadaluarsa.']);
            }

            // KITA MATIKAN SEMENTARA 'with(user)' BIAR NGGAK CRASH KALAU RELASI BELUM ADA
            $query = TransactionPpobIak::query();

            // LOGIKA ROLE AKSES
            // Jika BUKAN Admin (Misal Admin itu ID 4), HANYA tampilkan data miliknya sendiri
            if ($user->id != 4) {
                $query->where('user_id', $user->id);
            }

            // 1. FILTER PENCARIAN (Search Input)
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('ref_id', 'LIKE', "%{$search}%")
                      ->orWhere('customer_id', 'LIKE', "%{$search}%")
                      ->orWhere('product_code', 'LIKE', "%{$search}%")
                      ->orWhere('sn', 'LIKE', "%{$search}%");
                });
            }

            // 2. FILTER WAKTU
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

            // =======================================================
            // FITUR BARU: TEMPELKAN ICON_URL KE DALAM JSON RESPONSE
            // =======================================================
            $prepaidCodes = $transactions->where('type', 'prabayar')->pluck('product_code')->unique();

            // Ambil semua logo yang cocok dari tabel produk prabayar
            $icons = \App\Models\IakPricelistPrepaid::whereIn('code', $prepaidCodes)->pluck('icon_url', 'code');

            // Sisipkan URL logo ke masing-masing transaksi
            $transactions->getCollection()->transform(function ($trx) use ($icons) {
                $trx->icon_url = $trx->type == 'prabayar' ? ($icons[$trx->product_code] ?? null) : null;
                return $trx;
            });
            // =======================================================

            return response()->json([
                'success'  => true,
                'data'     => $transactions,
                'is_admin' => ($user->id == 4)
            ]);

        } catch (\Exception $e) {
            // TANGKAP ERROR DAN CATAT KE LOG
            Log::error('LOG LOG - [API Mobile] Error History Backend: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error Server Sancaka: ' . $e->getMessage()
            ], 500);
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

        // =======================================================
        // 🚨 IDEMPOTENCY 1: CEK STATUS TRANSAKSI
        // Cegah user bayar tagihan yang sama berkali-kali!
        // =======================================================
        if ($transaction->status === 'SUCCESS') {
            return response()->json(['success' => false, 'message' => 'Tagihan ini sudah berhasil dibayar sebelumnya.']);
        }
        if ($transaction->status === 'PROCESS') {
            return response()->json(['success' => false, 'message' => 'Pembayaran tagihan ini sedang diproses oleh sistem, mohon tunggu.']);
        }

        $user = auth()->user();
        if ($user->balance_iak < $transaction->price) {
            return response()->json(['success' => false, 'message' => 'Saldo Anda tidak mencukupi untuk membayar tagihan ini.']);
        }

        // =======================================================
        // 🚨 IDEMPOTENCY 2: ATOMIC LOCK MEMORY (Cegah Klik Beruntun/Milidetik)
        // Kunci proses ini selama 10 detik untuk tr_id yang sama
        // =======================================================
        $lock = Cache::lock('pay_pasca_' . $transaction->tr_id, 10);
        if (!$lock->get()) {
            Log::warning('LOG LOG - [API Mobile] Atomic Lock Bekerja untuk tr_id: ' . $transaction->tr_id);
            return response()->json(['success' => false, 'message' => 'Permintaan sedang diproses, jangan klik berkali-kali.']);
        }

        try {
            // Ubah status jadi PROCESS dulu biar aman kalau tiba-tiba server mati di tengah jalan
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
                // 00 = SUCCESS, 39 = PROCESS
                $status = ($rc === '00') ? 'SUCCESS' : (($rc === '39') ? 'PROCESS' : 'FAILED');

                // Potong saldo HANYA JIKA statusnya SUCCESS atau PROCESS
                // (Kalau gagal, jangan potong saldo!)
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

            // JIKA API IAK ERROR / KEMBALIKAN HTML BUKAN JSON
            $transaction->update(['status' => 'FAILED', 'message' => 'Invalid API Response dari Pusat']);
            return response()->json(['success' => false, 'message' => 'Gagal memproses pembayaran ke server pusat.']);

        } catch (\Exception $e) {
            // JIKA TIMEOUT / KONEKSI PUTUS (BIARKAN STATUS TETAP 'PROCESS', JANGAN FAILED!)
            // Nanti admin bisa cek status manual, atau webhook yang menyelesaikan. Saldo sudah aman terkunci.
            Log::error('LOG LOG - [API Mobile] Timeout / Error Pay Pasca: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Koneksi lambat. Pembayaran sedang diproses di latar belakang. Cek riwayat berkala.']);
        } finally {
            optional($lock)->release();
        }
    }

}
