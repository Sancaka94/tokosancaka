<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Api;

class DelivereeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // LOG LOG
        Log::info('Deliveree Webhook Incoming Payload:', $request->all());

        // 1. Tarik ekspektasi API Key dari database untuk validasi keamanan
        $mode = Api::getValue('DELIVEREE_MODE', 'global', 'sandbox');
        $expectedApiKey = Api::getValue('DELIVEREE_API_KEY', $mode);

        // 2. Validasi Header Authorization dari Deliveree
        $authHeader = $request->header('Authorization');

        if ($authHeader !== $expectedApiKey) {
            // Jika token salah, tolak dengan 401 Unauthorized sesuai dokumentasi Deliveree
            // LOG LOG
            Log::warning('Deliveree Webhook - Unauthorized Access Attempt', [
                'provided_auth' => $authHeader
            ]);
            return response()->json(['message' => '401 Unauthorized'], 401);
        }

        // 3. Proses Payload (JSON)
        $payload = $request->all();

        // Di sini Anda bisa menambahkan logika update ke tabel transaksi Sancaka Express
        // Contoh status yang dikirim Deliveree: locating_driver, driver_accept_booking, delivery_in_progress, delivery_complete, canceled
        
        $bookingId = $payload['booking_id'] ?? null;
        $status = $payload['status'] ?? null;

        // LOG LOG
        Log::info('Deliveree Webhook - Processed', [
            'booking_id' => $bookingId,
            'status' => $status
        ]);

        // Berikan respon 200 OK agar Deliveree tahu data sudah diterima
        return response()->json(['message' => 'Webhook received successfully'], 200);
    }
}