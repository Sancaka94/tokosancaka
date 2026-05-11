<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DigiflazzService;
use App\Models\PpobProduct;
use App\Models\PpobTransaction;
use App\Models\User;
use App\Services\FonnteService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Services\DokuJokulService;

class PpobDigiflazController extends Controller
{
    protected $digiflazz;

    public function __construct(DigiflazzService $digiflazz)
    {
        $this->digiflazz = $digiflazz;
    }

    // =================================================================
    // 1. GET PRODUK BERDASARKAN KATEGORI
    // =================================================================
    public function getProductsByCategory($slug)
    {
        // Mapping keyword database
        $categoryMap = [
            'pulsa' => 'Pulsa', 'data' => 'Data', 'pln-token' => 'PLN',
            'games' => 'Games', 'voucher' => 'Voucher', 'e-money' => 'E-Money',
            'sms-telpon' => 'SMS', 'masa-aktif' => 'Masa Aktif',
            'pln-pasca' => 'PLN Postpaid', 'pdam' => 'PDAM', 'bpjs-kes' => 'BPJS',
        ];

        $dbCategoryKeyword = $categoryMap[$slug] ?? str_replace('-', ' ', $slug);

        $products = PpobProduct::where(function($query) use ($dbCategoryKeyword) {
                $query->where('category', 'LIKE', "%{$dbCategoryKeyword}%")
                      ->orWhere('brand', 'LIKE', "%{$dbCategoryKeyword}%");
            })
            ->where('seller_product_status', 1)
            ->where('buyer_product_status', 1)
            ->orderBy('price', 'asc')
            ->get(['id', 'product_name', 'brand', 'buyer_sku_code', 'price', 'sell_price', 'desc', 'type']);

        return response()->json([
            'success' => true,
            'category' => $dbCategoryKeyword,
            'data' => $products
        ]);
    }

    // =================================================================
    // 2. CEK TAGIHAN PASCABAYAR
    // =================================================================
    public function checkBill(Request $request)
    {
        $request->validate([
            'customer_no' => 'required',
            'sku' => 'required'
        ]);

        $customerNo = $request->input('customer_no');
        $inputSku   = $request->input('sku');
        $finalSku   = $inputSku;
        $refId      = 'INQ-' . time() . rand(100,999);

        // Pencarian Cerdas Jika SKU bukan kode unik
        if (!Str::startsWith($inputSku, 'post') && !preg_match('/^[A-Z0-9]+$/', $inputSku)) {
            $keyword = str_replace('-', ' ', $inputSku);
            $product = PpobProduct::whereRaw("CONCAT(IFNULL(brand,''), ' ', IFNULL(product_name,''), ' ', IFNULL(category,'')) LIKE ?", ["%{$keyword}%"])
            ->where('seller_product_status', true)->orderBy('id', 'desc')->first();

            if (!$product) {
                $firstWord = explode(' ', $keyword)[0];
                $product = PpobProduct::where(function($q) use ($firstWord) {
                        $q->where('brand', 'LIKE', "%{$firstWord}%")->orWhere('category', 'LIKE', "%{$firstWord}%");
                    })->where('seller_product_status', true)->first();
            }

            if ($product) $finalSku = $product->buyer_sku_code;
        }

        $activeProduct = PpobProduct::where('buyer_sku_code', $finalSku)->first();

        if (!$activeProduct || $activeProduct->seller_product_status != true) {
            return response()->json(['success' => false, 'message' => "Layanan tidak aktif atau tidak ditemukan."]);
        }

        try {
            $response = $this->digiflazz->inquiryPasca($finalSku, $customerNo, $refId);

            if (isset($response['data']) && in_array($response['data']['rc'] ?? '', ['00', 'Sukses', 'Pending'])) {
                $data = $response['data'];
                $tagihanPenyedia = (float) ($data['price'] ?? $data['selling_price'] ?? $data['amount'] ?? 0);
                $adminFeePenyedia = (float) ($data['admin'] ?? 0);

                $marginAgen = ($activeProduct->sell_price > $activeProduct->price)
                              ? ($activeProduct->sell_price - $activeProduct->price) : 2500;
                $totalTagihanUser = $tagihanPenyedia + $adminFeePenyedia + $marginAgen;

                return response()->json([
                    'success' => true,
                    'data' => [
                        'customer_name' => $data['customer_name'] ?? $data['name'],
                        'customer_no'   => $data['customer_no'],
                        'product_name'  => $activeProduct->product_name,
                        'total_tagihan' => $totalTagihanUser,
                        'buyer_sku_code'=> $finalSku,
                        'ref_id'        => $data['ref_id'] ?? $refId,
                    ]
                ]);
            }

            return response()->json(['success' => false, 'message' => $response['data']['message'] ?? 'Tagihan tidak ditemukan.']);

        } catch (\Exception $e) {
            Log::error("Inquiry Pasca Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal koneksi ke provider.'], 500);
        }
    }

   // =================================================================
    // 3. PROSES TRANSAKSI (STORE)
    // =================================================================
    public function processTransaction(Request $request)
    {
        $request->validate([
            'buyer_sku_code' => 'required',
            'customer_no' => 'required',
            'customer_wa' => 'required|string|min:8|max:20',
            'idempotency_key' => 'required|string|max:36',
            'selling_price' => 'nullable|numeric',
            'ref_id' => 'nullable|string',
            'payment_method' => 'nullable|string' // Pastikan ini diterima
        ]);

        $user = $request->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

        $isPasca = $request->has('ref_id') && !empty($request->input('ref_id'));
        $sku = $request->buyer_sku_code;
        $product = PpobProduct::where('buyer_sku_code', $sku)->first();

        // Fallback untuk pascabayar jika SKU unik dari request tidak match 100%
        if (!$product && $isPasca) {
            $product = PpobProduct::where('category', 'LIKE', '%Postpaid%')->orWhere('brand', 'PLN PASCABAYAR')->first();
        }

        if (!$product) return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan di sistem.']);

        // Set Harga Jual dan Modal
        if ($isPasca) {
            $sellingPrice = $request->input('selling_price');
            if(!$sellingPrice) return response()->json(['success' => false, 'message' => 'Harga Tagihan tidak valid.']);
            $estimasiProfit = 2500;
            $priceToDeduct = $sellingPrice;
            $modalPrice = $sellingPrice - $estimasiProfit;
        } else {
            $sellingPrice = $product->sell_price;
            $priceToDeduct = $product->sell_price;
            $modalPrice = $product->price;
        }

        // Idempotency Check (Mencegah double tap pada API)
        $idempotencyKey = 'ppob_lock:' . $request->customer_no . ':' . $sku . ':' . $request->idempotency_key;
        if (Cache::has($idempotencyKey)) {
            return response()->json(['success' => false, 'message' => 'Transaksi sedang diproses. Mohon tunggu.']);
        }
        Cache::put($idempotencyKey, true, 300); // Kunci selama 5 menit

        $trxRefId = $isPasca ? $request->input('ref_id') : 'TRX-' . time() . rand(100,999);
        $paymentMethod = strtoupper(trim(str_replace('#', '', $request->payment_method ?? 'SALDO')));
        $isSaldoOrCash = in_array($paymentMethod, ['SALDO', 'CASH', 'POTONG SALDO']);

        // =================================================================
        // A. JALUR PAYMENT GATEWAY (TRIPAY, DOKU, DANA)
        // =================================================================
        if (!$isSaldoOrCash) {
            // Simpan Transaksi sebagai Pending (Belum potong saldo, belum tembak Digiflazz)
            $trx = PpobTransaction::create([
                'user_id' => $user->id_pengguna,
                'order_id' => $trxRefId,
                'buyer_sku_code' => $sku,
                'customer_no' => $request->customer_no,
                'customer_wa' => $this->_sanitizePhoneNumber($request->customer_wa),
                'price' => $modalPrice,
                'selling_price' => $sellingPrice,
                'profit' => $sellingPrice - $modalPrice,
                'status' => 'Pending',
                'message' => 'Menunggu Pembayaran Gateway',
                'desc' => json_encode(['type' => $isPasca ? 'postpaid' : 'prepaid'])
            ]);

            $amount = (int) $sellingPrice;

            // 1. DOKU JOKUL
            if (in_array($paymentMethod, ['DOKU', 'DOKU_JOKUL'])) {
                try {
                    // Karena sudah di-use di atas, panggil langsung nama class-nya
                    $dokuService = new DokuJokulService();
                    $paymentUrl = $dokuService->createPayment($trxRefId, $amount);
                    if (empty($paymentUrl)) throw new \Exception('Response URL DOKU kosong.');

                    $trx->update(['payment_url' => $paymentUrl]);
                    return response()->json([
                        'success' => true,
                        'message' => 'Pesanan dibuat. Mengalihkan ke DOKU...',
                        'data' => ['payment_url' => $paymentUrl]
                    ]);
                } catch (\Exception $e) {
                    $trx->update(['status' => 'Gagal', 'message' => 'Gagal generate DOKU']);
                    return response()->json(['success' => false, 'message' => 'Gagal DOKU: ' . $e->getMessage()]);
                }
            }

            // 2. DANA DIRECT (Portal Web)
            elseif ($paymentMethod === 'DANA') {
                $akunParams = $user->no_wa ?? $user->no_hp ?? $user->id_pengguna;
                $paymentUrl = url('/pembayaran?akun=' . urlencode($akunParams));
                $trx->update(['payment_url' => $paymentUrl]);
                return response()->json([
                    'success' => true,
                    'message' => 'Pesanan dibuat. Mengalihkan ke DANA...',
                    'data' => ['payment_url' => $paymentUrl]
                ]);
            }

            // 3. TRIPAY
            else {
                $tripayMode = \App\Models\Api::getValue('TRIPAY_MODE', 'global', 'sandbox');
                $apiKey = \App\Models\Api::getValue('TRIPAY_API_KEY', $tripayMode);
                $privateKey = \App\Models\Api::getValue('TRIPAY_PRIVATE_KEY', $tripayMode);
                $merchantCode = \App\Models\Api::getValue('TRIPAY_MERCHANT_CODE', $tripayMode);

                if (empty($apiKey) || empty($privateKey) || empty($merchantCode)) {
                    return response()->json(['success' => false, 'message' => 'Konfigurasi Tripay belum disetting.']);
                }

                $tripayUrl = $tripayMode === 'production' ? 'https://tripay.co.id/api/transaction/create' : 'https://tripay.co.id/api-sandbox/transaction/create';
                $signature = hash_hmac('sha256', $merchantCode.$trxRefId.$amount, $privateKey);

                $responseTripay = \Illuminate\Support\Facades\Http::withHeaders([
                    'Authorization' => 'Bearer ' . trim($apiKey)
                ])->post($tripayUrl, [
                    'method'         => $paymentMethod,
                    'merchant_ref'   => $trxRefId,
                    'amount'         => $amount,
                    'customer_name'  => $user->nama_lengkap ?? 'Member',
                    'customer_email' => $user->email ?? 'no-reply@sancaka.com',
                    'customer_phone' => $user->no_hp ?? '081234567890',
                    'order_items'    => [['sku' => $sku, 'name' => $product->product_name ?? 'PPOB', 'price' => $amount, 'quantity' => 1]],
                    'return_url'     => env('FRONTEND_URL', url('/')) . '/riwayatppob',
                    'signature'      => $signature
                ]);

                $resTripay = $responseTripay->json();

                if ($responseTripay->successful() && isset($resTripay['success']) && $resTripay['success']) {
                    $trx->update(['payment_url' => $resTripay['data']['checkout_url']]);
                    return response()->json([
                        'success' => true,
                        'message' => 'Mengalihkan ke pembayaran...',
                        'data' => ['payment_url' => $resTripay['data']['checkout_url']]
                    ]);
                } else {
                    $trx->update(['status' => 'Gagal', 'message' => 'Tripay: ' . ($resTripay['message'] ?? 'Error')]);
                    return response()->json(['success' => false, 'message' => 'Gagal Tripay: ' . ($resTripay['message'] ?? 'Error')]);
                }
            }
        }

        // =================================================================
        // B. JALUR SALDO / CASH (LANGSUNG TEMBAK DIGIFLAZZ)
        // =================================================================
        if ($user->saldo < $priceToDeduct) {
            return response()->json(['success' => false, 'message' => 'Saldo tidak cukup. Silakan Top Up.']);
        }

        DB::beginTransaction();
        try {
            // Potong Saldo
            $user->decrement('saldo', $priceToDeduct);

            // Simpan Transaksi ke Database
            $trx = PpobTransaction::create([
                'user_id' => $user->id_pengguna,
                'order_id' => $trxRefId,
                'buyer_sku_code' => $sku,
                'customer_no' => $request->customer_no,
                'customer_wa' => $this->_sanitizePhoneNumber($request->customer_wa),
                'price' => $modalPrice,
                'selling_price' => $sellingPrice,
                'profit' => $sellingPrice - $modalPrice,
                'status' => 'Pending',
                'message' => 'Sedang diproses...',
                'desc' => json_encode(['type' => $isPasca ? 'postpaid' : 'prepaid'])
            ]);

            $command = $isPasca ? 'pay-pasca' : null;
            $response = $this->digiflazz->transaction($sku, $request->customer_no, $trxRefId, 0, $command);

            $status = $response['data']['status'] ?? 'Gagal';
            $sn = $response['data']['sn'] ?? '';
            $msg = $response['data']['message'] ?? '-';

            if ($status !== 'Gagal') {
                $updateData = ['status' => $status, 'sn' => $sn, 'message' => $msg];

                // Update Modal Asli dari Digiflazz
                if (isset($response['data']['price']) && $response['data']['price'] > 0) {
                    $realModal = $response['data']['price'];
                    $updateData['price'] = $realModal;
                    $updateData['profit'] = $trx->selling_price - $realModal;
                }

                $trx->update($updateData);

                // Jika transaksi instan sukses, langsung kirim WA
                if ($status === 'Sukses' || $status === 'Success') {
                    $this->_sendWhatsappNotificationSN($trx, $sn);
                }

                DB::commit();
                return response()->json(['success' => true, 'message' => 'Transaksi Berhasil Diproses!', 'data' => $trx]);
            } else {
                // Refund Saldo Jika Gagal di Awal
                $user->increment('saldo', $priceToDeduct);
                $trx->update(['status' => 'Gagal', 'message' => $msg]);
                DB::commit();

                return response()->json(['success' => false, 'message' => 'Transaksi Gagal: ' . $msg]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('API PPOB Store Exception: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error Sistem: ' . $e->getMessage()], 500);
        }
    }

    // =================================================================
    // HELPER FUNCTIONS (KIRIM WHATSAPP & FORMAT NOMOR)
    // =================================================================

    /**
     * Membersihkan dan memformat nomor HP menjadi 62xxxx.
     */
    private function _sanitizePhoneNumber($phone)
    {
        if (empty($phone)) return null;

        $phone = (string) $phone;
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (empty($phone)) return null;

        if (Str::startsWith($phone, '08')) {
            return '62' . substr($phone, 1);
        }
        if (Str::startsWith($phone, '62')) {
            return $phone;
        }
        if (strlen($phone) > 8 && !Str::startsWith($phone, '0')) {
            return '62' . $phone;
        }
        return $phone;
    }

    /**
     * Mengirim notifikasi WA berisi SN transaksi PPOB via Fonnte
     */
    private function _sendWhatsappNotificationSN(PpobTransaction $trx, string $sn)
    {
        try {
            $user = User::find($trx->user_id);

            if (!$user) {
                 Log::error("Data Agent tidak ditemukan untuk user_id: " . $trx->user_id);
                 return false;
            }

            // -------------------------------------------------------------
            // LOGIKA PENCARIAN NOMOR WA CUSTOMER
            // -------------------------------------------------------------
            $rawCustomerWa = $trx->customer_wa;

            // 1. Cek di dalam kolom 'desc' (Format JSON) jika kolom customer_wa kosong
            if (empty($rawCustomerWa) && !empty($trx->desc)) {
                $descJson = json_decode($trx->desc, true);
                if (isset($descJson['wa'])) {
                    $rawCustomerWa = $descJson['wa'];
                }
            }

            // 2. Jika masih kosong, cek apakah 'customer_no' itu nomor HP (Khusus Pulsa/Data)
            if (empty($rawCustomerWa)) {
                if (Str::startsWith($trx->customer_no, '08') || Str::startsWith($trx->customer_no, '62')) {
                    $rawCustomerWa = $trx->customer_no;
                }
            }

            // Sanitize nomor
            $customerWa = $this->_sanitizePhoneNumber($rawCustomerWa);
            $agentWa = $this->_sanitizePhoneNumber($user->no_wa ?? $user->no_hp);
            // -------------------------------------------------------------

            $fmt = function($val) { return number_format($val, 0, ',', '.'); };

            // --- DATA TOKO AGENT ---
            $storeName = $user->store_name ?? 'Sancaka Express';
            $storeAddress = $user->address_detail ?? 'Kantor Pusat Sancaka Express';
            $storePhone = $this->_sanitizePhoneNumber($user->no_wa ?? null) ?? '628819435180';

            // ===============================================
            // 1. SUSUN PESAN UNTUK AGENT (PENJUAL)
            // ===============================================
            $messageAgent = "[NOTIF AGENT - SN] Transaksi {$trx->order_id} Sukses.\n\n" .
            "*✅ Transaksi PPOB Sukses!*\n" .
            "------------------------------------\n" .
            "Produk: {$trx->buyer_sku_code}\n" .
            "Tujuan: {$trx->customer_no}\n" .
            "Harga Jual: Rp {$fmt($trx->selling_price)}\n" .
            "*Serial Number (SN):*\n" .
            "*{$sn}*\n" .
            "------------------------------------\n" .
            "Saldo Baru: Rp " . $fmt($user->saldo ?? 0);

            // ===============================================
            // 2. SUSUN PESAN UNTUK CUSTOMER (PEMBELI)
            // ===============================================
            $messageCustomer = "*Halo Pelanggan {$storeName} 👋*\n\n" .
            "Transaksi PPOB Anda telah Berhasil diproses!\n\n" .
            "*✅ DETAIL TRANSAKSI*\n" .
            "------------------------------------\n" .
            "Produk: {$trx->buyer_sku_code}\n" .
            "Nomor Tujuan: {$trx->customer_no}\n" .
            "Harga Jual: Rp {$fmt($trx->selling_price)}\n" .
            "*Serial Number (SN):*\n" .
            "*{$sn}*\n" .
            "------------------------------------\n\n" .
            "Terima kasih telah bertransaksi.\n" .
            "Jika ada kendala, hubungi:\n\n" .
            "*Toko: {$storeName}*\n" .
            "*WA/Telp: {$storePhone}*\n" .
            "*Alamat: {$storeAddress}*\n\n" .
            "Manajemen {$storeName}. 🙏";

            // Kirim WA via Fonnte Service
            if ($agentWa) {
                FonnteService::sendMessage($agentWa, $messageAgent);
                Log::info('API PPOB: SN sent via WA to Agent.', ['ref_id' => $trx->order_id]);
            }
            if ($customerWa) {
                FonnteService::sendMessage($customerWa, $messageCustomer);
                Log::info('API PPOB: SN sent via WA to Customer.', ['ref_id' => $trx->order_id]);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('API WA Notification SN PPOB Error: ' . $e->getMessage(), ['trx_id' => $trx->id]);
            return false;
        }
    }

    // =================================================================
    // CEK SALDO DIGIFLAZZ (KHUSUS ADMIN)
    // =================================================================
    public function cekSaldo(Request $request)
    {
        Log::info("=== [DEBUG PPOB] Start Cek Saldo Digiflazz ===");

        $user = $request->user();
        if (!$user) {
            Log::warning("[DEBUG PPOB] Cek Saldo: Unauthorized (Token tidak valid/tidak ada)");
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Pastikan hanya role Admin yang bisa akses
        if (strtolower($user->role) !== 'admin') {
            Log::warning("[DEBUG PPOB] Cek Saldo: Forbidden (Akses ditolak untuk role " . $user->role . ")");
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        try {
            // Ambil Kredensial (Gunakan Env jika ada, jika tidak gunakan fallback hardcode)
            $username = trim(env('DIGIFLAZZ_USERNAME', 'mihetiDVGdeW'));
            $apiKey   = trim(env('DIGIFLAZZ_API_KEY', '1f48c69f-8676-5d56-a868-10a46a69f9b7'));

            // Formula cek saldo Digiflazz: md5(username + apikey + "depo")
            $sign = md5($username . $apiKey . "depo");

            $payload = [
                'cmd' => 'deposit',
                'username' => $username,
                'sign' => $sign
            ];

            Log::info("[DEBUG PPOB] Payload request ke Digiflazz:", $payload);

            // Tembak API Digiflazz
            $response = \Illuminate\Support\Facades\Http::post('https://api.digiflazz.com/v1/cek-saldo', $payload);

            Log::info("[DEBUG PPOB] Response Status dari Digiflazz: " . $response->status());
            Log::info("[DEBUG PPOB] Response Body dari Digiflazz: " . $response->body());

            $data = $response->json();

            if (isset($data['data']['deposit'])) {
                $saldo = $data['data']['deposit'];
                return response()->json([
                    'success' => true,
                    'formatted' => 'Rp ' . number_format($saldo, 0, ',', '.'),
                    'saldo' => $saldo
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Format response Digiflazz tidak sesuai',
                'raw_data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error("[DEBUG PPOB] Exception Error Cek Saldo: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Sistem Error: ' . $e->getMessage()], 500);
        }
    }

    // =================================================================
    // CEK ID PLN PRABAYAR (INQUIRY NAMA PELANGGAN)
    // =================================================================
    public function checkPlnPrabayar(Request $request)
    {
        $request->validate(['customer_no' => 'required']);

        try {
            // Tembak API Digiflazz khusus inquiry PLN
            $response = $this->digiflazz->inquiryPln($request->customer_no);

            // Jika RC 00 atau Status Sukses
            if (isset($response['data']) && in_array($response['data']['rc'] ?? '', ['00', 'Sukses', 'Success'])) {
                return response()->json([
                    'success' => true,
                    'name' => $response['data']['name'],
                    'segment_power' => $response['data']['segment_power']
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response['data']['message'] ?? 'ID Pelanggan tidak ditemukan.'
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal koneksi ke provider.'], 500);
        }
    }

    // =================================================================
    // 6. RIWAYAT TRANSAKSI (HISTORY)
    // =================================================================
    public function getHistory(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

        // Inisialisasi query dasar, diurutkan dari yang paling baru
        $query = PpobTransaction::orderBy('created_at', 'desc');

        // Cek apakah user BUKAN admin DAN BUKAN user dengan ID 4
        // Catatan: Gunakan $user->id atau $user->id_pengguna sesuai struktur tabel User kamu
        $isAdmin = strtolower($user->role) === 'admin' || $user->id_pengguna == 4;

        if (!$isAdmin) {
            // Jika BUKAN admin, batasi data hanya untuk user yang sedang login
            $query->where('user_id', $user->id_pengguna);
        }
        // Jika dia admin/ID 4, kode where() di atas dilewati sehingga mengambil semua data transaksi.

        // Eksekusi query
        $history = $query->get();

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }

}
