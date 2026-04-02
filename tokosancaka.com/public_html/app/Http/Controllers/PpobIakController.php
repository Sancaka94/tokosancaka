<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; // LOG LOG: Tambahkan ini untuk mengaktifkan fitur pencatatan log
use App\Models\TransactionPpobIak;
use App\Models\IakResponseCode; // Jangan lupa import model di atas
use App\Models\IakPricelistPostpaid; // Import di bagian atas
use App\Models\IakPrepaidResponseCode;
use App\Models\IakPricelistPrepaid;
use App\Models\Api; // <-- TAMBAHKAN IMPORT INI UNTUK BACA SETTING DATABASE
use App\Models\User;
use Illuminate\Support\Facades\Cache; // --- TAMBAHAN IDEMPOTENCY ---

class PpobIakController extends Controller
{
    private $prepaidBaseUrl;
    private $postpaidBaseUrl;
    private $username;
    private $apiKey;

    public function __construct()
    {
        // Mengambil environment aktif dari DATABASE (Global Mode)
        $env = Api::getValue('IAK_MODE', 'global', 'development');

        // Setup Base URL sesuai environment dari database (dengan fallback default jika kosong)
        $this->prepaidBaseUrl = Api::getValue('IAK_PREPAID_BASE_URL', $env) ?: ($env === 'production' ? 'https://prepaid.iak.id' : 'https://prepaid.iak.dev');
        $this->postpaidBaseUrl = Api::getValue('IAK_POSTPAID_BASE_URL', $env) ?: ($env === 'production' ? 'https://mobilepulsa.net' : 'https://testpostpaid.mobilepulsa.net');

        // Kredensial sesuai environment dari database
        $this->username = Api::getValue('IAK_USER_HP', $env);
        $this->apiKey = Api::getValue('IAK_API_KEY', $env);
    }

    public function index()
    {
        $transactions = TransactionPpobIak::latest()->take(5)->get();
        $pricelist = IakPricelistPostpaid::where('status', 1)->orderBy('type')->get();

        // Ambil data prabayar yang sudah diupload via admin
        $pricelistPrepaid = IakPricelistPrepaid::where('status', 'Active')->orderBy('type')->orderBy('operator')->get();

        return view('ppob.iak', compact('transactions', 'pricelist', 'pricelistPrepaid'));
    }

    /**
     * Fungsi untuk sinkronisasi Pricelist dari IAK ke Database
     */
    public function syncPricelist()
    {
        // Sign untuk pricelist pasca: md5(username + api_key + 'pl')
        $sign = md5($this->username . $this->apiKey . 'pl');

        Log::info('LOG LOG - Sync Pricelist Request initiated.'); // LOG LOG

        try {
            $response = Http::post($this->postpaidBaseUrl . '/api/v1/bill/check', [
                'commands' => 'pricelist-pasca',
                'username' => $this->username,
                'sign'     => $sign,
                'status'   => 'all'
            ]);

            $result = $response->json();

            if ($response->successful() && isset($result['data']['pasca'])) {
                Log::info('LOG LOG - Sync Pricelist Success. Memasukkan data ke DB...'); // LOG LOG
                foreach ($result['data']['pasca'] as $item) {
                    IakPricelistPostpaid::updateOrCreate(
                        ['code' => $item['code']], // Cek berdasarkan kode produk
                        [
                            'name'     => $item['name'],
                            'status'   => $item['status'],
                            'fee'      => $item['fee'],
                            'komisi'   => $item['komisi'],
                            'type'     => $item['type'],
                            'category' => $item['category'] ?? 'postpaid',
                            'province' => $item['province'] ?? null,
                        ]
                    );
                }
                return back()->with('success', 'Pricelist berhasil diperbarui dari server IAK.');
            }

            Log::error('LOG LOG - Sync Pricelist Failed Response', ['response' => $result]); // LOG LOG
            return back()->with('error', 'Gagal sinkronisasi: ' . ($result['message'] ?? 'Unknown Error'));

        } catch (\Exception $e) {
            Log::error('LOG LOG - Sync Pricelist Exception', ['error' => $e->getMessage()]); // LOG LOG
            return back()->with('error', 'Koneksi error: ' . $e->getMessage());
        }
    }

   public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|string',
            'product_code' => 'required|string',
            'type' => 'required|in:prabayar,pascabayar'
        ]);

        // Jika Pascabayar, lempar ke fungsi inquiry
        if ($request->type === 'pascabayar') {
            return $this->inquiryPostpaid($request);
        }

        // ========================================================
        // LOGIKA PRABAYAR (TOP UP)
        // ========================================================

        // --- TAMBAHAN IDEMPOTENCY: CEK TRANSAKSI KEMBAR 3 MENIT TERAKHIR ---
        $isDuplicate = TransactionPpobIak::where('user_id', auth()->id())
            ->where('customer_id', $request->customer_id)
            ->where('product_code', $request->product_code)
            ->where('created_at', '>=', now()->subMinutes(3))
            ->exists();

        if ($isDuplicate) {
            return back()->with('error', 'Transaksi ke nomor dan produk yang sama sedang diproses/berhasil. Mohon tunggu 3 menit untuk menghindari dobel.');
        }

        // --- TAMBAHAN IDEMPOTENCY: ATOMIC LOCK (MENCEGAH DOUBLE CLICK) ---
        $lockKey = 'topup_' . auth()->id() . '_' . $request->product_code . '_' . $request->customer_id;
        $lock = Cache::lock($lockKey, 10); // Kunci eksekusi selama 10 detik

        if (!$lock->get()) {
            return back()->with('error', 'Transaksi Anda sedang diproses, mohon jangan menekan tombol berkali-kali.');
        }

        try { // --- TAMBAHAN IDEMPOTENCY: BUNGKUS TRY UNTUK LOCK ---
            // --- Cek Saldo User di Backend ---
            $user = auth()->user();
            $product = IakPricelistPrepaid::where('code', $request->product_code)->first();

            if (!$product) {
                return back()->with('error', 'Produk tidak ditemukan di database.');
            }

            if ($user->balance_iak < $product->price) {
                return back()->with('error', 'Maaf, saldo Anda tidak mencukupi untuk transaksi ini.');
            }
            // ------------------------------------------------

            // Format Ref ID (Harus Unik)
            $refId = 'P' . date('ymd') . rand(1000, 9999);

            // Sesuai Dokumentasi: md5(username + api_key + ref_id)
            $sign = md5($this->username . $this->apiKey . $refId);

            // Buat record awal di database dengan status PROCESS
            $transaction = TransactionPpobIak::create([
                'user_id'      => auth()->id(),
                'ref_id'       => $refId,
                'type'         => 'prabayar',
                'customer_id'  => $request->customer_id, // Sudah diformat dari frontend (hp, meter, game id + zone)
                'product_code' => $request->product_code,
                'status'       => 'PROCESS',
            ]);

            Log::info('LOG LOG - Prepaid Top Up Request', ['ref_id' => $refId, 'customer_id' => $request->customer_id, 'product' => $request->product_code]);

            try {
                // Hit API Top Up IAK
                $response = Http::post($this->prepaidBaseUrl . '/api/top-up', [
                    'username'     => $this->username,
                    'customer_id'  => $request->customer_id,
                    'product_code' => $request->product_code,
                    'ref_id'       => $refId,
                    'sign'         => $sign
                ]);

                $result = $response->json();

                // Jika hit API Sukses
                if ($response->successful() && isset($result['data'])) {

                    // Ambil RC (Response Code)
                    $apiCode = $result['data']['rc'] ?? ($result['data']['message'] == 'PROCESS' ? '39' : null);
                    $codeInfo = IakPrepaidResponseCode::where('code', $apiCode)->first();

                    // Mapping Status Sesuai Dokumentasi: 0 = PROCESS, 1 = SUCCESS, 2 = FAILED
                    $statusMap = [0 => 'PROCESS', 1 => 'SUCCESS', 2 => 'FAILED'];
                    $apiStatus = $result['data']['status'] ?? 0;

                    $finalStatus = $codeInfo ? strtoupper($codeInfo->status) : ($statusMap[$apiStatus] ?? 'PROCESS');
                    $finalMessage = $codeInfo ? $codeInfo->description . ' - ' . $codeInfo->solution : ($result['data']['message'] ?? 'Request Terkirim');

                    // Update database dengan response dari IAK
                    $transaction->update([
                        'status'  => $finalStatus,
                        'price'   => $product->price, // Menggunakan harga modal dari database Anda
                        'tr_id'   => $result['data']['tr_id'] ?? null, // Simpan Transaction ID dari IAK
                        'sn'      => $result['data']['sn'] ?? null,
                        'message' => $finalMessage
                    ]);

                    // Jika status langsung gagal (FAILED)
                    if ($finalStatus == 'FAILED') {
                        Log::error('LOG LOG - Prepaid Top Up Failed Status from API', ['ref_id' => $refId, 'message' => $transaction->message]);
                        return back()->with('error', 'Transaksi prabayar gagal: ' . $transaction->message);
                    }

                    // --- Potong saldo user jika status Proses / Sukses ---
                    // (Jika nanti failed via Webhook, saldo akan direfund di fungsi webhook)
                    if ($finalStatus == 'PROCESS' || $finalStatus == 'SUCCESS') {
                        $user->balance_iak -= $product->price;
                        $user->save();
                        Log::info('LOG LOG - Saldo User Terpotong (Top Up)', ['user_id' => $user->id, 'potongan' => $product->price, 'sisa' => $user->balance_iak]);
                    }

                    Log::info('LOG LOG - Prepaid Top Up Processed', ['ref_id' => $refId, 'tr_id' => $transaction->tr_id, 'status' => $finalStatus]);

                    // Redirect menuju halaman Invoice
                    return redirect()->route('ppob.iak.invoice', ['ref_id' => $transaction->ref_id])
                                     ->with('success', 'Transaksi sedang diproses. Mohon tunggu.');
                }

                // Jika API IAK error atau format response salah
                Log::error('LOG LOG - Prepaid Top Up API Error / Invalid Response Format', ['response' => $result]);
                $transaction->update(['status' => 'FAILED', 'message' => $result['data']['message'] ?? 'API Error']);
                return back()->with('error', 'Terjadi kesalahan pada sistem provider: ' . ($result['data']['message'] ?? 'Unknown'));

            } catch (\Exception $e) {
                // Jika koneksi putus / timeout
                Log::error('LOG LOG - Prepaid Top Up Exception', ['ref_id' => $refId, 'error' => $e->getMessage()]);
                $transaction->update(['status' => 'FAILED', 'message' => 'Timeout / Connection Error']);
                return back()->with('error', 'Gagal menghubungi server IAK: ' . $e->getMessage());
            }
        } finally { // --- TAMBAHAN IDEMPOTENCY: LEPASKAN KUNCI ---
            optional($lock)->release();
        }
    }

    // --- FUNGSI BARU: CHECK STATUS PRABAYAR MANUAL ---
    public function checkStatusPrepaid($ref_id)
    {
        $transaction = TransactionPpobIak::where('ref_id', $ref_id)->where('type', 'prabayar')->firstOrFail();
        $sign = md5($this->username . $this->apiKey . $transaction->ref_id);

        Log::info('LOG LOG - Check Status Prepaid Request', ['ref_id' => $ref_id]); // LOG LOG

        try {
            $response = Http::post($this->prepaidBaseUrl . '/api/check-status', [
                'username' => $this->username,
                'ref_id'   => $transaction->ref_id,
                'sign'     => $sign
            ]);

            $result = $response->json();

            if ($response->successful() && isset($result['data'])) {
                $apiCode = $result['data']['rc'] ?? null;
                $codeInfo = IakPrepaidResponseCode::where('code', $apiCode)->first();

                $statusMap = [0 => 'PROCESS', 1 => 'SUCCESS', 2 => 'FAILED'];
                $finalStatus = $codeInfo ? strtoupper($codeInfo->status) : ($statusMap[$result['data']['status']] ?? 'PROCESS');
                $finalMessage = $codeInfo ? $codeInfo->description : ($result['data']['message'] ?? 'Status update');

                // --- LOGIKA REFUND JIKA TRANSAKSI BERUBAH JADI FAILED ---
                if (in_array($transaction->status, ['PROCESS', 'PENDING']) && $finalStatus === 'FAILED') {
                    if ($transaction->user_id) {
                        // Sesuaikan \App\Models\User jika model kamu bernama lain (misal: \App\Models\Pengguna)
                        $userRefund = User::find($transaction->user_id);
                        if ($userRefund) {
                            $userRefund->balance_iak += $transaction->price; // Kembalikan saldo
                            $userRefund->save();
                            Log::info('LOG LOG - Saldo Refunded (Check Status)', ['ref_id' => $transaction->ref_id, 'amount' => $transaction->price]);
                        }
                    }
                }
                // --------------------------------------------------------

                $transaction->update([
                    'status'  => $finalStatus,
                    'sn'      => $result['data']['sn'] ?? $transaction->sn, // Simpan SN / Token jika sukses
                    'price'   => $result['data']['price'] ?? $transaction->price,
                    'message' => $finalMessage
                ]);

                Log::info('LOG LOG - Check Status Prepaid Result', ['ref_id' => $transaction->ref_id, 'status' => $finalStatus, 'sn' => $result['data']['sn'] ?? 'none']); // LOG LOG
                return redirect()->back()->with('success', 'Status transaksi: ' . $finalStatus . '. Pesan: ' . $finalMessage);
            }

            Log::error('LOG LOG - Check Status Prepaid Invalid Response', ['response' => $result]); // LOG LOG
            return redirect()->back()->with('error', 'Gagal mengecek status. API tidak mengembalikan data yang valid.');

        } catch (\Exception $e) {
            Log::error('LOG LOG - Check Status Prepaid Exception', ['error' => $e->getMessage()]); // LOG LOG
            return redirect()->back()->with('error', 'Gagal terhubung ke API saat cek status.');
        }
    }

   // --- ALUR 1: INQUIRY POSTPAID (Mendukung Semua Jenis Tagihan IAK Secara Lengkap) ---
    private function inquiryPostpaid(Request $request)
    {
        // Format: I + Tanggal(6 digit) + Random(4 digit) = Tepat 11 Karakter
        $refId = 'I' . date('ymd') . rand(1000, 9999);
        $sign = md5($this->username . $this->apiKey . $refId);
        $productCode = strtoupper($request->product_code);

        Log::info('LOG LOG - Inquiry Postpaid Request', [
            'ref_id'      => $refId,
            'customer_id' => $request->customer_id,
            'product'     => $productCode,
            'month'       => $request->month,
            'amount'      => $request->amount,
            'identitas'   => $request->nomor_identitas,
            'year'        => $request->year
        ]);

        // 1. Siapkan Payload Default
        $payload = [
            'commands' => 'inq-pasca',
            'username' => $this->username,
            'code'     => $productCode,
            'hp'       => $request->customer_id,
            'ref_id'   => $refId,
            'sign'     => $sign
        ];

        // 2. Parameter Khusus BPJS (Bulan)
        if (in_array($productCode, ['BPJS', 'BPJSTK', 'BPJSTKPU'])) {
            $payload['month'] = $request->month ?? 1;
        }

        // 3. Parameter Khusus E-Samsat (NIK)
        if (str_starts_with($productCode, 'ESAMSAT.')) {
            $payload['nomor_identitas'] = $request->nomor_identitas ?? '';
        }

        // 4. Parameter Khusus Custom Denom / Donasi (Nominal)
        if ($request->filled('amount')) {
            $payload['desc'] = [
                'amount' => (int) $request->amount
            ];
        }

        // 5. Parameter Khusus PBB (Tahun Pajak)
        if (str_starts_with($productCode, 'PBB')) {
            $payload['year'] = $request->year ?? date('Y');
        }

        try {
            // Hit API IAK
            $response = Http::post($this->postpaidBaseUrl . '/api/v1/bill/check', $payload);
            $result = $response->json();

            // Jika Inquiry Sukses (Response code '00')
            if ($response->successful() && isset($result['data']) && $result['data']['response_code'] === '00') {

                Log::info('LOG LOG - Inquiry Postpaid Success', ['tr_id' => $result['data']['tr_id']]);

                $data = $result['data'];
                $desc = $data['desc'] ?? [];

                // Format Keterangan Dasar
                $detailMessage = "Nama: " . ($data['tr_name'] ?? '-') . " | Periode: " . ($data['period'] ?? '-');

                // --- Parsing Rincian Sesuai Tipe Produk ---
                if (is_array($desc)) {
                    // A. BPJS Kesehatan
                    if ($productCode === 'BPJS') {
                        $cabang = $desc['nama_cabang'] ?? '-';
                        $peserta = $desc['jumlah_peserta'] ?? '-';
                        $detailMessage .= " | Cabang: {$cabang} | Peserta: {$peserta} Orang";
                    }
                    // B. BPJS Ketenagakerjaan BPU
                    elseif ($productCode === 'BPJSTK') {
                        $program = $desc['kode_program'] ?? '-';
                        $jkk = number_format($desc['jkk'] ?? 0, 0, ',', '.');
                        $jkm = number_format($desc['jkm'] ?? 0, 0, ',', '.');
                        $jht = number_format($desc['jht'] ?? 0, 0, ',', '.');
                        $detailMessage .= " | Program: {$program} (JKK: Rp{$jkk}, JKM: Rp{$jkm}, JHT: Rp{$jht})";
                    }
                    // C. BPJS Ketenagakerjaan PU
                    elseif ($productCode === 'BPJSTKPU') {
                        $npp = $desc['npp'] ?? '-';
                        $jpk = number_format($desc['jpk'] ?? 0, 0, ',', '.');
                        $jpn = number_format($desc['jpn'] ?? 0, 0, ',', '.');
                        $detailMessage .= " | NPP: {$npp} | JPK: Rp{$jpk} | JPN: Rp{$jpn}";
                    }
                    // D. E-Samsat
                    elseif (str_starts_with($productCode, 'ESAMSAT.')) {
                        $nopol = $desc['nomor_polisi'] ?? '-';
                        $kendaraan = $desc['merek_kb'] ?? '-';
                        $pkb = number_format($desc['biaya_pokok']['PKB'] ?? 0, 0, ',', '.');
                        $detailMessage .= " | Nopol: {$nopol} | Unit: {$kendaraan} | PKB Pokok: Rp{$pkb}";
                    }
                    // E. PBB (Pajak Bumi & Bangunan)
                    elseif (str_starts_with($productCode, 'PBB')) {
                        $lokasi = $desc['lokasi'] ?? '-';
                        $lt = $desc['luas_tanah'] ?? '-';
                        $lb = $desc['luas_gedung'] ?? '-';
                        $thn = $desc['tahun_pajak'] ?? '-';
                        $detailMessage .= " | Lokasi: {$lokasi} | LT/LB: {$lt}/{$lb} | Tahun: {$thn}";
                    }
                    // F. PLN Pascabayar
                    elseif ($productCode === 'PLNPOSTPAID') {
                        $tarif = $desc['tarif'] ?? '-';
                        $daya = $desc['daya'] ?? '-';
                        $lembar = $desc['lembar_tagihan'] ?? 1;
                        $detailMessage .= " | Tarif/Daya: {$tarif} / {$daya}VA | Tagihan: {$lembar} Bulan";
                    }
                    // G. PLN Non Taglist
                    elseif ($productCode === 'PLNNONTAG') {
                        $transaksi = $desc['transaksi'] ?? '-';
                        $noReg = $desc['no_registrasi'] ?? '-';
                        $detailMessage .= " | Trans: {$transaksi} | No. Reg: {$noReg}";
                    }
                    // H. PDAM, Telkom, HP Pascabayar (Telco)
                    elseif (isset($desc['jumlah_tagihan']) || isset($desc['bill_quantity'])) {
                        // Telkom/HP pakai 'jumlah_tagihan', PDAM/TV pakai 'bill_quantity'
                        $jmlTagihan = $desc['jumlah_tagihan'] ?? $desc['bill_quantity'] ?? 1;

                        // Menangkap nama PDAM khusus jika ada
                        if (isset($desc['pdam_name'])) {
                            $detailMessage .= " | PDAM: " . $desc['pdam_name'];
                        }
                        $detailMessage .= " | Total Tagihan: {$jmlTagihan} Bulan";
                    }
                    // I. Gas Negara (PGAS)
                    elseif ($productCode === 'PGAS') {
                        if (isset($desc[0])) { // Multi-bill
                            $usage = $desc[0]['usage'] ?? '-';
                            $detailMessage .= " | Pemakaian: {$usage} (Multi-bill)";
                        } else {
                            $usage = $desc['usage'] ?? '-';
                            $fm = $desc['first_meter'] ?? '-';
                            $lm = $desc['last_meter'] ?? '-';
                            $detailMessage .= " | Pemakaian: {$usage} ({$fm} - {$lm})";
                        }
                    }
                    // J. Multifinance / Asuransi
                    elseif (isset($desc['item_name']) && isset($desc['installment'])) {
                        $item = $desc['item_name'];
                        $tenor = $desc['tenor'] ?? '-';
                        $cicilan = number_format($desc['installment'] ?? 0, 0, ',', '.');
                        $detailMessage .= " | Item: {$item} | Tenor: {$tenor} | Cicilan: Rp{$cicilan}";
                    }
                    // K. Fallback Umum
                    else {
                        if (isset($desc['product_desc'])) {
                            $detailMessage .= " | Produk: " . $desc['product_desc'];
                        } elseif (isset($desc['detail']) && is_string($desc['detail'])) {
                            $detailMessage .= " | Ket: " . $desc['detail'];
                        }
                    }
                } elseif (is_string($desc) && !empty(trim($desc))) {
                    $detailMessage .= " | Ket: " . $desc;
                }

                // Simpan data inquiry ke DB dengan status PROCESS
                $transaction = TransactionPpobIak::create([
                    'user_id'      => auth()->id(),
                    'ref_id'       => $refId,
                    'tr_id'        => $data['tr_id'],
                    'type'         => 'pascabayar',
                    'customer_id'  => $request->customer_id,
                    'product_code' => $productCode,
                    'price'        => $data['price'],
                    'status'       => 'PROCESS',
                    'message'      => $detailMessage
                ]);

                return view('ppob.inquiry', compact('transaction', 'result'));
            }

            Log::error('LOG LOG - Inquiry Postpaid Failed Response', ['response' => $result]);
            return back()->with('error', 'Inquiry Gagal: ' . ($result['data']['message'] ?? 'Tagihan tidak ditemukan atau sudah lunas.'));

        } catch (\Exception $e) {
            Log::error('LOG LOG - Inquiry Postpaid Exception', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal melakukan inquiry: Koneksi ke server terputus.');
        }
    }

    // --- ALUR 2: PAYMENT POSTPAID ---
    public function payPostpaid(Request $request)
    {
        // Cari transaksi berdasarkan tr_id yang dikirim dari halaman konfirmasi
        $transaction = TransactionPpobIak::where('tr_id', $request->tr_id)->firstOrFail();

        // --- TAMBAHAN IDEMPOTENCY: ATOMIC LOCK ---
        $lock = Cache::lock('pay_pasca_' . $transaction->tr_id, 10); // Cegah klik beruntun
        if (!$lock->get()) {
            return redirect()->back()->with('error', 'Pembayaran sedang diproses, mohon tidak menekan tombol berkali-kali.');
        }

        try { // --- TAMBAHAN IDEMPOTENCY: BUNGKUS TRY UNTUK LOCK ---
            // 1. Pengecekan Saldo User di Backend
            $user = auth()->user();
            if ($user->balance_iak < $transaction->price) {
                return redirect()->back()->with('error', 'Maaf, saldo Anda tidak mencukupi untuk membayar tagihan ini.');
            }

            // 2. Sign untuk payment pascabayar menggunakan tr_id
            // Sesuai Dokumentasi: md5(username + api_key + tr_id)
            $sign = md5($this->username . $this->apiKey . $transaction->tr_id);

            Log::info('LOG LOG - Payment Postpaid Request', ['tr_id' => $transaction->tr_id]); // LOG LOG

            try {
                // 3. Hit API Pembayaran IAK
                $response = Http::post($this->postpaidBaseUrl . '/api/v1/bill/check', [
                    'commands' => 'pay-pasca',
                    'username' => $this->username,
                    'tr_id'    => $transaction->tr_id,
                    'sign'     => $sign
                ]);

                $result = $response->json();

                if ($response->successful() && isset($result['data'])) {

                    $rc = $result['data']['response_code'] ?? '';

                    // Menentukan status berdasarkan Response Code (RC)
                    // 00 = SUCCESS, 39 = PENDING / PROCESS, selain itu FAILED
                    if ($rc === '00') {
                        $status = 'SUCCESS';
                    } elseif ($rc === '39') {
                        $status = 'PROCESS';
                    } else {
                        $status = 'FAILED';
                    }

                    $finalMessage = $result['data']['message'] ?? 'Payment response received';

                    // Update data transaksi
                    $transaction->update([
                        'status'  => $status,
                        'sn'      => $result['data']['noref'] ?? null, // noref adalah bukti bayar / SN biller
                        'message' => $finalMessage
                    ]);

                    // Jika Gagal
                    if ($status == 'FAILED') {
                        Log::error('LOG LOG - Payment Postpaid Failed Status', ['tr_id' => $transaction->tr_id, 'message' => $transaction->message]); // LOG LOG
                        return redirect()->route('ppob.index')->with('error', 'Pembayaran gagal: ' . $transaction->message);
                    }

                    // --- Potong Saldo User (Hanya jika PROCESS atau SUCCESS) ---
                    if ($status == 'PROCESS' || $status == 'SUCCESS') {
                        $user->balance_iak -= $transaction->price;
                        $user->save();
                        Log::info('LOG LOG - Saldo User Terpotong (Pay Pasca)', ['user_id' => $user->id, 'potongan' => $transaction->price, 'sisa' => $user->balance_iak]);
                    }

                    Log::info('LOG LOG - Payment Postpaid Success/Process', ['tr_id' => $transaction->tr_id, 'status' => $status]); // LOG LOG

                    // Redirect menuju halaman invoice
                    return redirect()->route('ppob.iak.invoice', ['ref_id' => $transaction->ref_id])
                                     ->with('success', 'Pembayaran Tagihan Berhasil diproses!');
                }

                // Jika format response salah / tidak ada index 'data'
                Log::error('LOG LOG - Payment Postpaid Invalid Response', ['response' => $result]); // LOG LOG
                $transaction->update(['status' => 'FAILED', 'message' => 'Invalid API Response Format']);
                return redirect()->route('ppob.index')->with('error', 'Gagal memproses pembayaran. Response API tidak sesuai.');

            } catch (\Exception $e) {
                // ALUR 3: REQUEST PAYMENT NOT RECEIVED / TIMEOUT
                // Biarkan status tetap PROCESS, agar uang masih di-hold. Sistem harus cek status kemudian.
                Log::error('LOG LOG - Payment Postpaid Exception (Timeout/Connection)', ['tr_id' => $transaction->tr_id, 'error' => $e->getMessage()]); // LOG LOG

                $transaction->update(['message' => 'Timeout: ' . $e->getMessage()]);
                return redirect()->route('ppob.index')->with('error', 'Koneksi terputus. Sistem akan melakukan pengecekan status otomatis.');
            }
        } finally { // --- TAMBAHAN IDEMPOTENCY: LEPASKAN KUNCI ---
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

    // --- FUNGSI BARU: INQUIRY PLN PRABAYAR (DENGAN LOG DETAIL) ---
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
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'name'          => $result['data']['name'] ?? 'Tidak diketahui',
                            'segment_power' => $result['data']['segment_power'] ?? '-',
                            'meter_no'      => $result['data']['meter_no'] ?? '-',
                            'subscriber_id' => $result['data']['subscriber_id'] ?? $customerId
                        ],
                        'message' => 'Inquiry Berhasil'
                    ]);
                } else {
                    // Jika Status 2 = FAILED (misal: INCORRECT DESTINATION NUMBER)
                    Log::warning('3. Hasil: INQUIRY DITOLAK IAK (Nomor Salah/Gangguan)');
                    Log::info('=======================================');
                    return response()->json([
                        'success' => false,
                        // Ambil pesan asli dari IAK dan tampilkan ke layar
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

}
