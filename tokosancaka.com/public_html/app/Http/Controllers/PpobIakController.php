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

class PpobIakController extends Controller
{
    private $prepaidBaseUrl;
    private $postpaidBaseUrl;
    private $username;
    private $apiKey;

    public function __construct()
    {
        $this->prepaidBaseUrl = 'https://prepaid.iak.dev';
        // Base URL Postpaid Development sesuai dokumentasi awal
        $this->postpaidBaseUrl = 'https://testpostpaid.mobilepulsa.net';

        $this->username = env('IAK_USERNAME');
        $this->apiKey = env('IAK_API_KEY');
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

        if ($request->type === 'pascabayar') {
            return $this->inquiryPostpaid($request);
        }

        // --- TAMBAHAN BARU: Cek Saldo IAK di Backend ---
        $user = auth()->user();
        $product = IakPricelistPrepaid::where('code', $request->product_code)->first();

        if (!$product) {
            return back()->with('error', 'Produk tidak ditemukan di database.');
        }

        if ($user->balance_iak < $product->price) {
            return back()->with('error', 'Maaf, saldo IAK Anda tidak mencukupi untuk transaksi ini.');
        }
        // ------------------------------------------------

        // --- LOGIKA PRABAYAR ---
        $refId = 'TRX-' . time() . '-' . rand(100, 999);
        $sign = md5($this->username . $this->apiKey . $refId);

        $transaction = TransactionPpobIak::create([
            'ref_id'       => $refId,
            'type'         => 'prabayar',
            'customer_id'  => $request->customer_id,
            'product_code' => $request->product_code,
            'status'       => 'PROCESS',
        ]);

        Log::info('LOG LOG - Prepaid Request', ['ref_id' => $refId, 'customer_id' => $request->customer_id, 'product' => $request->product_code]);

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
                // Ambil kode response dari IAK
                $apiCode = $result['data']['rc'] ?? ($result['data']['message'] == 'PROCESS' ? '39' : null);
                $codeInfo = IakPrepaidResponseCode::where('code', $apiCode)->first();

                $statusMap = [0 => 'PROCESS', 1 => 'SUCCESS', 2 => 'FAILED'];
                $finalStatus = $codeInfo ? strtoupper($codeInfo->status) : ($statusMap[$result['data']['status']] ?? 'PROCESS');
                $finalMessage = $codeInfo ? $codeInfo->description . ' - ' . $codeInfo->solution : ($result['data']['message'] ?? 'Request Terkirim');

                $transaction->update([
                    'status'  => $finalStatus,
                    'price'   => $product->price, // Menggunakan harga dari database
                    'sn'      => $result['data']['sn'] ?? null,
                    'message' => $finalMessage
                ]);

                if ($finalStatus == 'FAILED') {
                    Log::error('LOG LOG - Prepaid Failed Status from API', ['ref_id' => $refId, 'message' => $transaction->message]);
                    return back()->with('error', 'Transaksi prabayar gagal: ' . $transaction->message);
                }

                // --- TAMBAHAN BARU: Potong saldo jika transaksi Proses/Sukses ---
                if ($finalStatus == 'PROCESS' || $finalStatus == 'SUCCESS') {
                    $user->balance_iak -= $product->price;
                    $user->save();
                }

                Log::info('LOG LOG - Prepaid Processed', ['ref_id' => $refId, 'status' => $finalStatus]);

                // --- KODE REDIRECT KE INVOICE ---
                return redirect()->route('ppob.invoice', ['ref_id' => $transaction->ref_id])
                                 ->with('success', 'Transaksi berhasil diproses.');
            }

            Log::error('LOG LOG - Prepaid API Error / Invalid Response Format', ['response' => $result]);
            $transaction->update(['status' => 'FAILED', 'message' => $result['data']['message'] ?? 'API Error']);
            return back()->with('error', 'Terjadi kesalahan sistem prabayar: ' . ($result['data']['message'] ?? 'Unknown'));

        } catch (\Exception $e) {
            Log::error('LOG LOG - Prepaid Exception', ['ref_id' => $refId, 'error' => $e->getMessage()]);
            $transaction->update(['status' => 'FAILED', 'message' => $e->getMessage()]);
            return back()->with('error', 'Gagal menghubungi server: ' . $e->getMessage());
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

    // --- ALUR 1: INQUIRY POSTPAID ---
    private function inquiryPostpaid(Request $request)
    {
        $refId = 'INQ-' . time() . '-' . rand(100, 999);
        $sign = md5($this->username . $this->apiKey . $refId);

        Log::info('LOG LOG - Inquiry Postpaid Request', ['ref_id' => $refId, 'customer_id' => $request->customer_id, 'product' => $request->product_code]); // LOG LOG

        try {
            $response = Http::post($this->postpaidBaseUrl . '/api/v1/bill/check', [
                'commands' => 'inq-pasca',
                'username' => $this->username,
                'code'     => $request->product_code,
                'hp'       => $request->customer_id,
                'ref_id'   => $refId,
                'sign'     => $sign
            ]);

            $result = $response->json();

            // Jika Inquiry Sukses (Biasanya response code 00 untuk IAK Postpaid)
            if ($response->successful() && isset($result['data']) && $result['data']['response_code'] === '00') {

                Log::info('LOG LOG - Inquiry Postpaid Success', ['tr_id' => $result['data']['tr_id']]); // LOG LOG

                // Simpan data inquiry ke DB dengan status PENDING INQUIRY
                $transaction = TransactionPpobIak::create([
                    'ref_id'       => $refId,
                    'tr_id'        => $result['data']['tr_id'], // Penting untuk payment
                    'type'         => 'pascabayar',
                    'customer_id'  => $request->customer_id,
                    'product_code' => $request->product_code,
                    'price'        => $result['data']['price'], // Total tagihan
                    'status'       => 'PROCESS',
                    'message'      => $result['data']['desc']['detail'] ?? 'Inquiry Sukses'
                ]);

                // Lempar ke view dengan membawa data tagihan untuk dikonfirmasi
                return view('ppob.inquiry', compact('transaction', 'result'));
            }

            Log::error('LOG LOG - Inquiry Postpaid Failed Response', ['response' => $result]); // LOG LOG
            return back()->with('error', 'Inquiry Gagal: ' . ($result['data']['message'] ?? 'Tagihan tidak ditemukan/sudah lunas.'));

        } catch (\Exception $e) {
            Log::error('LOG LOG - Inquiry Postpaid Exception', ['error' => $e->getMessage()]); // LOG LOG
            return back()->with('error', 'Gagal melakukan inquiry: ' . $e->getMessage());
        }
    }

    // --- ALUR 2: PAYMENT POSTPAID ---
    public function payPostpaid(Request $request)
    {
        $transaction = TransactionPpobIak::where('tr_id', $request->tr_id)->firstOrFail();

        // Sign untuk payment pascabayar biasanya menggunakan tr_id
        $sign = md5($this->username . $this->apiKey . $transaction->tr_id);

        Log::info('LOG LOG - Payment Postpaid Request', ['tr_id' => $transaction->tr_id]); // LOG LOG

        try {
            $response = Http::post($this->postpaidBaseUrl . '/api/v1/bill/check', [
                'commands' => 'pay-pasca',
                'username' => $this->username,
                'tr_id'    => $transaction->tr_id,
                'sign'     => $sign
            ]);

            $result = $response->json();

            if ($response->successful() && isset($result['data'])) {
                $status = $result['data']['response_code'] === '00' ? 'SUCCESS' :
                          ($result['data']['response_code'] === '39' ? 'PROCESS' : 'FAILED');

                $transaction->update([
                    'status'  => $status,
                    'sn'      => $result['data']['noref'] ?? null,
                    'message' => $result['data']['message'] ?? 'Payment response received'
                ]);

                if ($status == 'FAILED') {
                    Log::error('LOG LOG - Payment Postpaid Failed Status', ['tr_id' => $transaction->tr_id, 'message' => $transaction->message]); // LOG LOG
                    return redirect()->route('ppob.index')->with('error', 'Pembayaran gagal: ' . $transaction->message);
                }

                Log::info('LOG LOG - Payment Postpaid Success/Process', ['tr_id' => $transaction->tr_id, 'status' => $status]); // LOG LOG
                // Redirect menuju halaman invoice
                return redirect()->route('ppob.invoice', ['ref_id' => $transaction->ref_id])
                                 ->with('success', 'Pembayaran Tagihan Berhasil diproses!');

            }

            // PERBAIKAN: Jika format response salah / tidak ada 'data'
            Log::error('LOG LOG - Payment Postpaid Invalid Response', ['response' => $result]); // LOG LOG
            $transaction->update(['message' => 'Invalid API Response Format']);
            return redirect()->route('ppob.index')->with('error', 'Gagal memproses pembayaran. Response API tidak sesuai.');

        } catch (\Exception $e) {
            // ALUR 3: REQUEST PAYMENT NOT RECEIVED / TIMEOUT
            // Biarkan status tetap PROCESS, dan panggil check status (opsional bisa dilakukan via cronjob/tombol manual)
            Log::error('LOG LOG - Payment Postpaid Exception (Timeout/Connection)', ['tr_id' => $transaction->tr_id, 'error' => $e->getMessage()]); // LOG LOG

            $transaction->update(['message' => 'Timeout: ' . $e->getMessage()]);
            return redirect()->route('ppob.index')->with('error', 'Koneksi terputus. Sistem akan melakukan pengecekan status otomatis.');
        }
    }

    // --- ALUR 3 & 4: CHECK STATUS POSTPAID (Jika Timeout) ---
    public function checkStatusPostpaid($tr_id)
    {
        $transaction = TransactionPpobIak::where('tr_id', $tr_id)->firstOrFail();
        $sign = md5($this->username . $this->apiKey . $transaction->ref_id);

        Log::info('LOG LOG - Check Status Request', ['tr_id' => $tr_id, 'ref_id' => $transaction->ref_id]); // LOG LOG

        try {
            $response = Http::post($this->postpaidBaseUrl . '/api/v1/bill/check', [
                'commands' => 'cs-pasca',
                'username' => $this->username,
                'ref_id'   => $transaction->ref_id,
                'sign'     => $sign
            ]);

            $result = $response->json();

            if ($response->successful() && isset($result['data'])) {
                $apiCode = $result['data']['response_code']; // Ambil kode dari IAK (misal: "00", "39", "106")

                // Cek ke tabel response code yang baru kita buat
                $codeInfo = IakResponseCode::where('code', $apiCode)->first();

                // Jika kode ditemukan di database, gunakan status dan pesannya. Jika tidak, gunakan default.
                $finalStatus  = $codeInfo ? strtoupper($codeInfo->status) : 'PROCESS';
                $finalMessage = $codeInfo ? $codeInfo->description . ' - ' . $codeInfo->solution : ($result['data']['message'] ?? 'Status update');

                $transaction->update([
                    'status'  => $finalStatus,
                    'message' => $finalMessage
                ]);

                Log::info('LOG LOG - Check Status Result', ['ref_id' => $transaction->ref_id, 'apiCode' => $apiCode, 'finalStatus' => $finalStatus]); // LOG LOG
                return redirect()->back()->with('success', 'Status tagihan berhasil di-refresh: ' . ($codeInfo->description ?? $finalMessage));
            }

            // PERBAIKAN: Tangkap respon aneh
            Log::error('LOG LOG - Check Status Invalid Response', ['response' => $result]); // LOG LOG
            return redirect()->back()->with('error', 'Gagal mengecek status. Response API tidak sesuai.');

        } catch (\Exception $e) {
            Log::error('LOG LOG - Check Status Exception', ['error' => $e->getMessage()]); // LOG LOG
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
        if ($sign && $sign !== $expectedSign) {
            return response()->json(['message' => 'Invalid signature'], 403);
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

        $transaction->update([
            'status'  => $finalStatus,
            'sn'      => $sn ?: $transaction->sn,
            'price'   => $price > 0 ? $price : $transaction->price,
            'message' => $finalMessage
        ]);

        Log::info('LOG LOG - Webhook Processed Successfully', ['ref_id' => $refId, 'finalStatus' => $finalStatus, 'sn' => $sn]); // LOG LOG
        return response()->json(['message' => 'Callback received successfully'], 200);
    }

    // --- FUNGSI UNTUK MENAMPILKAN INVOICE ---
    public function invoice($ref_id)
    {
        // Tarik data transaksi berdasarkan ref_id
        $transaction = TransactionPpobIak::where('ref_id', $ref_id)->firstOrFail();

        return view('ppob.invoice', compact('transaction'));
    }
}
