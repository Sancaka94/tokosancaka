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
use App\Models\AutoKirim; // <-- Tambahkan model AutoKirim

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

    /**
     * ==============================================================
     * API PENGAMBILAN WILAYAH (LOCAL DATABASE)
     * ==============================================================
     */

    /**
     * GET: Ambil daftar semua Provinsi
     */
    public function getProvinces()
    {
        try {
            $provinces = AutoKirim::select('province_name')
                ->distinct()
                ->whereNotNull('province_name')
                ->orderBy('province_name', 'asc')
                ->pluck('province_name');

            return response()->json([
                'success' => true,
                'data' => $provinces
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data provinsi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET: Ambil daftar Kota/Kabupaten berdasarkan Provinsi
     * Contoh request: /api/wilayah/regencies?province_name=JAWA TIMUR
     */
    public function getRegencies(Request $request)
    {
        try {
            $province = $request->query('province_name');

            if (!$province) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter province_name wajib diisi.'
                ], 400);
            }

            $regencies = AutoKirim::select('regency_name')
                ->distinct()
                ->where('province_name', $province)
                ->whereNotNull('regency_name')
                ->orderBy('regency_name', 'asc')
                ->pluck('regency_name');

            return response()->json([
                'success' => true,
                'data' => $regencies
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data kota/kabupaten: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET: Ambil daftar Kecamatan/Desa, Zip, dan District ID berdasarkan Kota/Kabupaten
     * Contoh request: /api/wilayah/districts?regency_name=KABUPATEN NGAWI
     */
    public function getDistricts(Request $request)
    {
        try {
            $regency = $request->query('regency_name');

            if (!$regency) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter regency_name wajib diisi.'
                ], 400);
            }

            $districts = AutoKirim::select('district_id', 'district_name', 'zip')
                ->where('regency_name', $regency)
                ->orderBy('district_name', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $districts
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data kecamatan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET: Pencarian Global Wilayah (Autocomplete)
     * Contoh request: /api/wilayah/search?q=NGAWI
     */
    public function searchWilayah(Request $request)
    {
        try {
            $keyword = $request->query('q');

            if (!$keyword) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ], 200);
            }

            $data = AutoKirim::where('district_name', 'like', "%{$keyword}%")
                ->orWhere('regency_name', 'like', "%{$keyword}%")
                ->orWhere('province_name', 'like', "%{$keyword}%")
                ->orWhere('zip', 'like', "%{$keyword}%")
                ->limit(50) // Dibatasi agar tidak membebani server
                ->get();

            return response()->json([
                'success' => true,
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan pencarian wilayah: ' . $e->getMessage()
            ], 500);
        }
    }
}
