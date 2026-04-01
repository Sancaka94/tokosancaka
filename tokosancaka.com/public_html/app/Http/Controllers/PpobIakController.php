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
}
