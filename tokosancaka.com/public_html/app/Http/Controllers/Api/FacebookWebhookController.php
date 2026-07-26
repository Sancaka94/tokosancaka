<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FacebookWebhookController extends Controller
{
    /**
     * Menangani Request GET dari Facebook untuk Verifikasi Awal
     */
    public function verify(Request $request)
    {
        // Ambil token dari file .env
        $verifyToken = env('FACEBOOK_WEBHOOK_VERIFY_TOKEN');

        // Facebook akan mengirimkan parameter ini
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        // Cek apakah request dari Facebook valid
        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('LOG LOG: Facebook Webhook Berhasil Diverifikasi!');
            // Harus me-return nilai hub_challenge dalam bentuk plain text
            return response($challenge, 200);
        }

        Log::warning('LOG LOG: Verifikasi Facebook Webhook Gagal. Token tidak cocok.');
        return response('Forbidden', 403);
    }

    /**
     * Menangani Request POST dari Facebook saat ada pembaruan data (Real-time)
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        // Simpan payload ke file log laravel (storage/logs/laravel.log)
        // untuk melihat bentuk data yang dikirim Facebook
        Log::info('LOG LOG: [Facebook Webhook Payload Masuk]', $payload);

        // Nanti di sini Anda bisa menambahkan logika untuk memproses data
        // Misalnya: update status user, balas pesan otomatis, dll.

        // Facebook mewajibkan kita membalas dengan status 200 OK secara cepat (kurang dari 20 detik)
        return response()->json(['status' => 'success'], 200);
    }
}
