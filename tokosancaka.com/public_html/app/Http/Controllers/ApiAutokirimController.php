<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Api;
use App\Models\Order;
use App\Models\User;
use App\Models\Pesanan;

class ApiAutokirimController extends Controller
{
    protected $baseUrl;
    protected $token;

    public function __construct()
    {
        $mode = Api::getValue('AUTOKIRIM_MODE', 'global', 'sandbox');

        // Mengambil Base URL dan Token sesuai mode yang sedang aktif
        $this->baseUrl = Api::getValue('AUTOKIRIM_BASE_URL', $mode, 'https://api-dev.autokirim.com');
        $this->token = Api::getValue('AUTOKIRIM_TOKEN', $mode, '');
    }

    /**
     * GET: Check IP
     * Mengecek IP server kita apakah sudah terhubung dengan Autokirim
     */
    public function checkIp()
    {
        try {
            $response = Http::get("{$this->baseUrl}/api/check");

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'ip' => $response->body(), // Autokirim mengembalikan plain text IP
                    'message' => 'IP berhasil dicek.'
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal terhubung ke server Autokirim.'
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET: Check Balance
     * Mengecek sisa saldo menggunakan Bearer Token
     */
    public function checkBalance()
    {
        try {
            $response = Http::withToken($this->token)
                            ->get("{$this->baseUrl}/api/balance");

            $result = $response->json();

            // Sesuai dokumentasi, rc "00" adalah berhasil
            if ($response->successful() && isset($result['rc']) && $result['rc'] === '00') {
                return response()->json([
                    'success' => true,
                    'data' => $result['data'],
                    'message' => $result['rd']
                ], 200);
            }

            return response()->json([
                'success' => false,
                'rc' => $result['rc'] ?? 'UNKNOWN',
                'message' => $result['rd'] ?? 'Gagal mengambil balance.'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST: Webhook Listener
     * Menerima notifikasi perubahan status transaksi dari Autokirim
     * Catatan: Webhook hanya akan masuk dari environment Production Autokirim
     */
    public function handleWebhook(Request $request)
    {
        // 1. Validasi Request
        $validatedData = $request->validate([
            'transactions_id'    => 'required',
            'awb_number'         => 'nullable|string',
            'ref_id'             => 'required|string', // Ini adalah ID transaksi internal sistem Anda
            'transactions_stats' => 'required|string',
            'transactions_desc'  => 'nullable|string',
            'image'              => 'nullable|string',
        ]);

        // 2. Log incoming webhook untuk keperluan debugging
        Log::info('Autokirim Webhook Received:', $validatedData);

        try {
            // 3. Proses data webhook
            // TODO: Update status pesanan di database Anda menggunakan $validatedData['ref_id']
            // Contoh implementasi (Sesuaikan dengan Model Anda):
            /*
            $order = Order::where('order_id', $validatedData['ref_id'])->first();
            if ($order) {
                $order->resi_number = $validatedData['awb_number'];
                $order->status = $validatedData['transactions_stats'];
                $order->status_description = $validatedData['transactions_desc'];
                $order->save();
            }
            */

            // 4. Berikan response 200 OK ke Autokirim agar mereka tahu webhook sukses diterima
            return response()->json([
                'rc' => '00',
                'rd' => 'Webhook received successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Autokirim Webhook Error: ' . $e->getMessage());

            return response()->json([
                'rc' => '99',
                'rd' => 'Internal server error while processing webhook'
            ], 500);
        }
    }
}
