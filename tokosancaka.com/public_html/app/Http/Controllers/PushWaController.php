<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushWaController extends Controller
{
    /**
     * Menampilkan QR Code untuk Scan WA
     */
    public function connect()
    {
        $token = env('PUSHWA_TOKEN'); // Ambil dari .env

        try {
            // 1. Request ke API PushWA
            $response = Http::post('https://dash.pushwa.com/api/startDevice', [
                'token' => $token
            ]);

            // 2. Cek apakah request sukses
            if ($response->successful()) {
                $data = $response->json();

                // 3. Cek apakah ada data QR dalam response
                if (isset($data['qr'])) {
                    // Convert string QR menjadi URL Gambar QR Code
                    $qrImage = 'https://api.qrserver.com/v1/create-qr-code/?data=' . urlencode($data['qr']);

                    // Kirim ke view
                    return view('pushwa.scan', compact('qrImage'));
                } else {
                    // Jika device mungkin sudah terhubung atau error lain
                    return response()->json([
                        'message' => 'QR Code tidak ditemukan. Mungkin device sudah terhubung?',
                        'response' => $data
                    ]);
                }
            } else {
                Log::error('PushWA Error: ' . $response->body());
                return response()->json(['error' => 'Gagal menghubungi server PushWA'], 500);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Contoh Fungsi Kirim Pesan (Bonus)
     */
    public function sendMessage($target, $message)
    {
        $token = env('PUSHWA_TOKEN');

        $response = Http::post('https://dash.pushwa.com/api/sendMessage', [
            'token' => $token,
            'target' => $target, // Contoh: 0812xxxx (sesuaikan format provider)
            'message' => $message,
        ]);

        return $response->json();
    }
}
