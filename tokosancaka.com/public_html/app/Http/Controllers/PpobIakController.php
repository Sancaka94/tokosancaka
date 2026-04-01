<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\TransactionPpobIak;
use App\Models\IakResponseCode; // Jangan lupa import model di atas
use App\Models\IakPricelistPostpaid; // Import di bagian atas

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
        // Ambil data pricelist dari DB untuk ditampilkan di Blade
        $pricelist = IakPricelistPostpaid::where('status', 1)->orderBy('type')->get();

        return view('ppob.iak', compact('transactions', 'pricelist'));
    }

    /**
     * Fungsi untuk sinkronisasi Pricelist dari IAK ke Database
     */
    public function syncPricelist()
    {
        // Sign untuk pricelist pasca: md5(username + api_key + 'pl')
        $sign = md5($this->username . $this->apiKey . 'pl');

        try {
            $response = Http::post($this->postpaidBaseUrl . '/api/v1/bill/check', [
                'commands' => 'pricelist-pasca',
                'username' => $this->username,
                'sign'     => $sign,
                'status'   => 'all'
            ]);

            $result = $response->json();

            if ($response->successful() && isset($result['data']['pasca'])) {
                foreach ($result['data']['pasca'] as $item) {
                    IakPricelistPostpaid::updateOrCreate(
                        ['code' => $item['code']], // Cek berdasarkan kode produk
                        [
                            'name'     => $item['name'],
                            'status'   => $item['status'],
                            'fee'      => $item['fee'],
                            'komisi'   => $item['komisi'],
                            'type'     => $item['type'],
                            'category' => $item['category'],
                            'province' => $item['province'] ?? null,
                        ]
                    );
                }
                return back()->with('success', 'Pricelist berhasil diperbarui dari server IAK.');
            }

            return back()->with('error', 'Gagal sinkronisasi: ' . ($result['message'] ?? 'Unknown Error'));

        } catch (\Exception $e) {
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

        // --- LOGIKA PRABAYAR (Tetap sama seperti sebelumnya) ---
        $refId = 'TRX-' . time() . '-' . rand(100, 999);
        $sign = md5($this->username . $this->apiKey . $refId);

        $transaction = TransactionPpobIak::create([
            'ref_id'       => $refId,
            'type'         => 'prabayar',
            'customer_id'  => $request->customer_id,
            'product_code' => $request->product_code,
            'status'       => 'PROCESS',
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
                $statusMap = [0 => 'PROCESS', 1 => 'SUCCESS', 2 => 'FAILED'];
                $finalStatus = $statusMap[$result['data']['status']] ?? 'PROCESS';

                $transaction->update([
                    'status'  => $finalStatus,
                    'price'   => $result['data']['price'] ?? 0,
                    'sn'      => $result['data']['sn'] ?? null,
                    'message' => $result['data']['message'] ?? 'Request Terkirim'
                ]);

                if ($finalStatus == 'FAILED') {
                    return back()->with('error', 'Transaksi prabayar gagal: ' . $transaction->message);
                }
                return back()->with('success', 'Transaksi prabayar diproses. Status: ' . $finalStatus);
            }

            $transaction->update(['status' => 'FAILED', 'message' => $result['data']['message'] ?? 'API Error']);
            return back()->with('error', 'Terjadi kesalahan sistem prabayar.');

        } catch (\Exception $e) {
            $transaction->update(['status' => 'FAILED', 'message' => $e->getMessage()]);
            return back()->with('error', 'Gagal menghubungi server: ' . $e->getMessage());
        }
    }

    // --- ALUR 1: INQUIRY POSTPAID ---
    private function inquiryPostpaid(Request $request)
    {
        $refId = 'INQ-' . time() . '-' . rand(100, 999);
        $sign = md5($this->username . $this->apiKey . $refId);

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

            return back()->with('error', 'Inquiry Gagal: ' . ($result['data']['message'] ?? 'Tagihan tidak ditemukan/sudah lunas.'));

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal melakukan inquiry: ' . $e->getMessage());
        }
    }

    // --- ALUR 2: PAYMENT POSTPAID ---
    public function payPostpaid(Request $request)
    {
        $transaction = TransactionPpobIak::where('tr_id', $request->tr_id)->firstOrFail();

        // Sign untuk payment pascabayar biasanya menggunakan tr_id
        $sign = md5($this->username . $this->apiKey . $transaction->tr_id);

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
                    return redirect()->route('ppob.index')->with('error', 'Pembayaran gagal: ' . $transaction->message);
                }

                return redirect()->route('ppob.index')->with('success', 'Pembayaran Tagihan Berhasil!');
            }

        } catch (\Exception $e) {
            // ALUR 3: REQUEST PAYMENT NOT RECEIVED / TIMEOUT
            // Biarkan status tetap PROCESS, dan panggil check status (opsional bisa dilakukan via cronjob/tombol manual)
            $transaction->update(['message' => 'Timeout: ' . $e->getMessage()]);
            return redirect()->route('ppob.index')->with('error', 'Koneksi terputus. Sistem akan melakukan pengecekan status otomatis.');
        }
    }

    // --- ALUR 3 & 4: CHECK STATUS POSTPAID (Jika Timeout) ---
    public function checkStatusPostpaid($tr_id)
    {
        $transaction = TransactionPpobIak::where('tr_id', $tr_id)->firstOrFail();
        $sign = md5($this->username . $this->apiKey . $transaction->ref_id);

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

            return redirect()->back()->with('success', 'Status tagihan berhasil di-refresh: ' . $codeInfo->description);
        }

        return redirect()->back()->with('error', 'Gagal mengecek status.');
    }

    /**
     * Handle Webhook / Callback dari IAK
     */
    public function webhook(Request $request)
    {
        // LOG LOG
        // IAK mengirimkan callback dalam format JSON di dalam object "data"
        $data = $request->input('data');

        if (!$data || !isset($data['ref_id'])) {
            return response()->json(['message' => 'Invalid payload format'], 400);
        }

        $refId  = $data['ref_id'];
        $status = $data['status']; // 0 = process, 1 = success, 2 = failed
        $sn     = $data['sn'] ?? null;
        $price  = $data['price'] ?? 0;
        $sign   = $data['sign'] ?? null;

        // 1. Validasi Signature (Keamanan)
        // Sign callback IAK = md5(username + api_key + ref_id)
        $expectedSign = md5($this->username . $this->apiKey . $refId);

        if ($sign && $sign !== $expectedSign) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // 2. Cari transaksi berdasarkan ref_id di database
        $transaction = TransactionPpobIak::where('ref_id', $refId)->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        // 3. Konversi status IAK ke format string kita
        $statusMap = [
            0 => 'PROCESS',
            1 => 'SUCCESS',
            2 => 'FAILED'
        ];
        $finalStatus = $statusMap[$status] ?? 'PROCESS';

        // 4. Update status transaksi di database
        $transaction->update([
            'status'  => $finalStatus,
            'sn'      => $sn ?: $transaction->sn,
            'price'   => $price > 0 ? $price : $transaction->price,
            'message' => $data['message'] ?? 'Status updated by Webhook'
        ]);

        // 5. Berikan response 200 OK agar IAK tahu webhook berhasil diterima
        return response()->json(['message' => 'Callback received successfully'], 200);
    }

}
