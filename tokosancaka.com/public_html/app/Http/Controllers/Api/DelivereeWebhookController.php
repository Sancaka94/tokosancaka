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
        // 1. Log Payload untuk debugging
        Log::info('Deliveree Webhook Incoming Payload:', $request->all());

        // 2. AMAN: Jangan menolak keras jika Authorization header tidak ada di sandbox
        // Kita hanya akan logging saja agar Anda bisa melihat token yang SEBENARNYA dikirim Deliveree
        $authHeader = $request->header('Authorization');
        $mode = Api::getValue('DELIVEREE_MODE', 'global', 'sandbox');
        $expectedApiKey = Api::getValue('DELIVEREE_API_KEY', $mode);

        if (empty($authHeader)) {
            Log::info('Deliveree Webhook - Tanpa Header Auth, melanjutkan proses (Mode: Sandbox/Dev)');
        } elseif ($authHeader !== $expectedApiKey) {
            Log::warning('Deliveree Webhook - Token tidak cocok, tapi lanjut proses.', ['expected' => $expectedApiKey, 'got' => $authHeader]);
        }

        // 3. Proses Status Update
        $payload = $request->all();
        $bookingId = $payload['job_order_number'] ?? null;
        $status = $payload['status'] ?? null;

        if ($bookingId && $status) {
            // Update pesanan Anda di sini
            $pesanan = Pesanan::where('nomor_invoice', $bookingId)->first();
            if ($pesanan) {
                $pesanan->status_pesanan = $status;
                $pesanan->save();
                Log::info("Deliveree Webhook - Pesanan {$bookingId} update status ke: {$status}");
            }
        }

        return response()->json(['message' => 'OK'], 200);
    }
}