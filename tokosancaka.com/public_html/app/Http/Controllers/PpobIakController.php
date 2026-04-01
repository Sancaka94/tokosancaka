<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
// Ubah pemanggilan model ke yang baru
use App\Models\TransactionPpobIak;

class PpobIakController extends Controller
{
    private $baseUrl;
    private $username;
    private $apiKey;

    public function __construct()
    {
        $this->baseUrl = 'https://prepaid.iak.dev';
        $this->username = env('IAK_USERNAME');
        $this->apiKey = env('IAK_API_KEY');
    }

    public function index()
    {
        $transactions = TransactionPpobIak::latest()->take(5)->get();
        // Ganti dari 'ppob.index' menjadi 'ppob.iak'
        return view('ppob.iak', compact('transactions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|string',
            'product_code' => 'required|string',
        ]);

        $refId = 'TRX-' . time() . '-' . rand(100, 999);
        $sign = md5($this->username . $this->apiKey . $refId);

        // Ganti model yang digunakan
        $transaction = TransactionPpobIak::create([
            'ref_id'       => $refId,
            'customer_id'  => $request->customer_id,
            'product_code' => $request->product_code,
            'status'       => 'PROCESS',
        ]);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])->post($this->baseUrl . '/api/top-up', [
                'username'     => $this->username,
                'customer_id'  => $request->customer_id,
                'product_code' => $request->product_code,
                'ref_id'       => $refId,
                'sign'         => $sign
            ]);

            $result = $response->json();

            if ($response->successful() && isset($result['data'])) {
                $statusMap = [
                    0 => 'PROCESS',
                    1 => 'SUCCESS',
                    2 => 'FAILED'
                ];
                $apiStatus = $result['data']['status'];
                $finalStatus = $statusMap[$apiStatus] ?? 'PROCESS';

                $transaction->update([
                    'status'  => $finalStatus,
                    'price'   => $result['data']['price'] ?? 0,
                    'sn'      => $result['data']['sn'] ?? null,
                    'message' => $result['data']['message'] ?? 'Request Terkirim'
                ]);

                if ($finalStatus == 'FAILED') {
                    return back()->with('error', 'Transaksi gagal: ' . $transaction->message);
                }

                return back()->with('success', 'Transaksi diproses. Status: ' . $finalStatus);
            }

            $transaction->update([
                'status'  => 'FAILED',
                'message' => $result['data']['message'] ?? 'API Response Error'
            ]);

            return back()->with('error', 'Terjadi kesalahan pada sistem PPOB.');

        } catch (\Exception $e) {
            $transaction->update([
                'status'  => 'FAILED',
                'message' => $e->getMessage()
            ]);

            return back()->with('error', 'Gagal menghubungi server IAK: ' . $e->getMessage());
        }
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
