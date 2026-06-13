<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\Api; // Pastikan model Api di-import

class LalamoveApiController extends Controller
{
    private $apiKey;
    private $apiSecret;
    private $baseUrl;
    private $market;

    public function __construct()
    {
        // 1. Ambil Mode Lalamove yang sedang aktif (sandbox / production)
        $mode = Api::getValue('LALAMOVE_MODE', 'global', 'sandbox');

        // 2. Ambil kredensial secara dinamis berdasarkan mode yang aktif
        $this->apiKey = Api::getValue('LALAMOVE_API_KEY', $mode);
        $this->apiSecret = Api::getValue('LALAMOVE_API_SECRET', $mode);

        // 3. Set Base URL secara otomatis menyesuaikan mode
        $this->baseUrl = ($mode === 'production') 
            ? 'https://rest.lalamove.com' 
            : 'https://rest.sandbox.lalamove.com';

        // 4. Default Market ID (Indonesia), bisa dibuat dinamis ke database jika kelak berekspansi
        $this->market = Api::getValue('LALAMOVE_MARKET', 'global', 'ID');
    }

    /**
     * Generator HMAC SHA256 Signature sesuai standar Lalamove API v3
     */
    private function generateSignature($method, $path, $body, $timestamp)
    {
        $rawSignature = "{$timestamp}\r\n{$method}\r\n{$path}\r\n\r\n{$body}";
        return hash_hmac('sha256', $rawSignature, $this->apiSecret);
    }

    /**
     * Core Request Handler
     */
    private function makeRequest($method, $path, $data = [])
    {
        $timestamp = round(microtime(true) * 1000);
        $bodyStr = empty($data) ? '' : json_encode(['data' => $data]);
        
        $signature = $this->generateSignature($method, $path, $bodyStr, $timestamp);
        $token = "{$this->apiKey}:{$timestamp}:{$signature}";
        $requestId = Str::uuid()->toString();

        $headers = [
            'Authorization' => "hmac {$token}",
            'Market'        => $this->market,
            'Request-ID'    => $requestId,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];

        // LOG LOG - Jangan ubah atau hapus, pencatatan transaksi kritikal
        Log::info("LOG LOG: Lalamove Request [{$method}] {$path}", [
            'request_id' => $requestId,
            'body'       => $data
        ]);

        $url = $this->baseUrl . $path;

        if ($method === 'POST') {
            return Http::withHeaders($headers)->post($url, empty($data) ? [] : ['data' => $data]);
        } elseif ($method === 'GET') {
            return Http::withHeaders($headers)->get($url);
        } elseif ($method === 'PATCH') {
            return Http::withHeaders($headers)->patch($url, empty($data) ? [] : ['data' => $data]);
        } elseif ($method === 'DELETE') {
            return Http::withHeaders($headers)->delete($url, empty($data) ? [] : ['data' => $data]);
        }

        return null;
    }

    /**
     * POST /v3/quotations
     * Meminta Quotation harga sebelum membuat order
     */
    public function getQuotation(Request $request)
    {
        $this->validate($request, [
            'serviceType' => 'required|string',
            'stops'       => 'required|array|min:2|max:16',
            'language'    => 'required|string',
        ]);

        $path = '/v3/quotations';
        $data = $request->only([
            'scheduleAt', 'serviceType', 'specialRequests', 
            'language', 'stops', 'item', 'isRouteOptimized'
        ]);

        $response = $this->makeRequest('POST', $path, $data);

        return response()->json($response->json(), $response->status());
    }

    /**
     * POST /v3/orders
     * Eksekusi pemesanan kurir menggunakan Quotation ID
     */
    public function createOrder(Request $request)
    {
        $this->validate($request, [
            'quotationId'          => 'required|string',
            'sender.stopId'        => 'required|string',
            'sender.name'          => 'required|string',
            'sender.phone'         => 'required|string',
            'recipients'           => 'required|array',
        ]);

        $path = '/v3/orders';
        $data = $request->all();
        
        // Memastikan label partner selalu terisi secara rapi dalam struktur pesanan
        $data['partner'] = $data['partner'] ?? 'Sancaka Express';

        $response = $this->makeRequest('POST', $path, $data);

        return response()->json($response->json(), $response->status());
    }

    /**
     * GET /v3/orders/{orderId}/drivers/{driverId}
     * Melacak detail pengemudi yang bertugas
     */
    public function getDriverDetails($orderId, $driverId)
    {
        $path = "/v3/orders/{$orderId}/drivers/{$driverId}";
        $response = $this->makeRequest('GET', $path);

        return response()->json($response->json(), $response->status());
    }

    /**
     * GET /v3/cities
     * Mengambil informasi daftar kota, service type, dan special requests
     */
    public function getCityInfo()
    {
        $path = '/v3/cities';
        $response = $this->makeRequest('GET', $path);

        return response()->json($response->json(), $response->status());
    }

    /**
     * GET /v3/quotations/{quotationId}
     * Mengambil detail Quotation yang sudah pernah dibuat
     */
    public function getQuotationDetails($quotationId)
    {
        $path = "/v3/quotations/{$quotationId}";
        $response = $this->makeRequest('GET', $path);

        return response()->json($response->json(), $response->status());
    }

    /**
     * GET /v3/orders/{orderId}
     * Mengambil detail Order yang sedang berjalan
     */
    public function getOrderDetails($orderId)
    {
        $path = "/v3/orders/{$orderId}";
        $response = $this->makeRequest('GET', $path);

        return response()->json($response->json(), $response->status());
    }

    /**
     * PATCH /v3/orders/{orderId}
     * Mengedit titik drop-off pada order yang berstatus ON_GOING
     */
    public function editOrder(Request $request, $orderId)
    {
        $this->validate($request, [
            'stops' => 'required|array|min:1',
        ]);

        $path = "/v3/orders/{$orderId}";
        $data = $request->only(['stops']);

        $response = $this->makeRequest('PATCH', $path, $data);

        return response()->json($response->json(), $response->status());
    }

    /**
     * DELETE /v3/orders/{orderId}
     * Membatalkan pesanan (Pastikan status order sesuai dengan regulasi pembatalan)
     */
    public function cancelOrder($orderId)
    {
        $path = "/v3/orders/{$orderId}";
        $response = $this->makeRequest('DELETE', $path);

        // Lalamove merespons 204 No Content jika sukses dibatalkan
        if ($response->status() === 204) {
            return response()->json(['message' => 'Order successfully cancelled'], 200);
        }

        return response()->json($response->json(), $response->status());
    }

    /**
     * DELETE /v3/orders/{orderId}/drivers/{driverId}
     * Mengganti driver jika driver saat ini tidak responsif/bermasalah
     */
    public function changeDriver(Request $request, $orderId, $driverId)
    {
        $this->validate($request, [
            'reason' => 'required|string|in:DRIVER_LATE,DRIVER_ASKED_CHANGE,DRIVER_UNRESPONSIVE,DRIVER_RUDE'
        ]);

        $path = "/v3/orders/{$orderId}/drivers/{$driverId}";
        $data = $request->only(['reason']);

        $response = $this->makeRequest('DELETE', $path, $data);

        if ($response->status() === 204) {
            return response()->json(['message' => 'Driver change requested successfully'], 200);
        }

        return response()->json($response->json(), $response->status());
    }

    /**
     * POST /v3/orders/{orderId}/priority-fee
     * Menambahkan tip (Priority Fee) untuk menarik perhatian driver
     */
    public function addPriorityFee(Request $request, $orderId)
    {
        $this->validate($request, [
            'priorityFee' => 'required|numeric'
        ]);

        $path = "/v3/orders/{$orderId}/priority-fee";
        
        // Memastikan priority fee terkirim dalam format string angka sesuai standar dokumentasi Lalamove
        $data = [
            'priorityFee' => (string) $request->input('priorityFee')
        ];

        $response = $this->makeRequest('POST', $path, $data);

        return response()->json($response->json(), $response->status());
    }

    /**
     * POST /api/webhook/lalamove
     * Menangani pembaruan status dari Lalamove (Webhook)
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();

        // LOG LOG - Jangan ubah atau hapus, pencatatan transaksi kritikal
        Log::info("LOG LOG: Lalamove Webhook Received", [
            'payload' => $payload
        ]);

        // Ekstrak eventType dari payload
        $eventType = $request->input('eventType');
        $orderId   = $request->input('data.order.orderId');

        // Memilah pemrosesan berdasarkan 10 tipe Webhook Lalamove
        switch ($eventType) {
            case 'ORDER_CREATED':
                // Event saat order berhasil dibuat via API
                break;

            case 'ORDER_STATUS_CHANGED':
                // Event perubahan status (ASSIGNING_DRIVER, ON_GOING, PICKED_UP, COMPLETED, dll)
                $status = $request->input('data.order.status');
                // TODO: Update status order di database
                break;

            case 'DRIVER_ASSIGNED':
                // Event saat driver berhasil didapatkan
                $driverId = $request->input('data.order.driverId');
                // TODO: Simpan driverId ke database untuk keperluan tracking
                break;

            case 'ORDER_AMOUNT_CHANGED':
                // Event saat harga berubah (misal: penambahan priority fee / tips)
                break;

            case 'ORDER_REPLACED':
                // Event saat orderID diganti oleh Customer Service Lalamove
                // $newOrderId = $request->input('data.order.orderId');
                break;

            case 'ORDER_EDITED':
                // Event untuk perubahan informasi order via Order Edit API
                break;

            case 'POD_STATUS_CHANGED':
                // Event status Proof of Delivery (PENDING, DELIVERED, SIGNED, FAILED)
                break;

            case 'POP_STATUS_CHANGED':
                // Event status Proof of Pick-up (Foto URL dan waktu pickup)
                break;

            case 'DELIVERY_CODE_STATUS_CHANGED':
                // Event perubahan status kode pengiriman
                break;

            case 'WALLET_BALANCE_CHANGED':
                // Event perubahan saldo wallet (terpotong atau top-up)
                // Sangat berguna untuk trigger notifikasi ke admin jika saldo menipis
                break;

            default:
                // Event tidak dikenali
                Log::warning("Lalamove Webhook: Unknown eventType received: {$eventType}");
                break;
        }

        // Lalamove MEWAJIBKAN balasan HTTP 200 OK agar request dianggap sukses (tidak diulang)
        return response()->json(['message' => 'Webhook received and processed'], 200);
    }
    /**
     * PATCH /v3/webhook
     * Mendaftarkan URL Webhook sistem secara otomatis ke Lalamove
     */
    public function registerWebhook(Request $request)
    {
        // Validasi input URL
        $this->validate($request, [
            'url' => 'required|url'
        ]);

        $path = '/v3/webhook';
        $data = [
            'url' => $request->input('url')
        ];

        // Memanggil fungsi core makeRequest yang sudah kita buat sebelumnya
        $response = $this->makeRequest('PATCH', $path, $data);

        return response()->json($response->json(), $response->status());
    }
}