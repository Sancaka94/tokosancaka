<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FonnteController extends Controller
{
    public function scan()
    {
        $token = env('FONNTE_TOKEN');

        $isConnected = false;
        $qrImage = null;
        $message = '';

        try {
            // Request ke API Fonnte
            $response = Http::withHeaders([
                'Authorization' => $token
            ])->post('https://api.fonnte.com/qr', [
                'type' => 'qr',
                // 'whatsapp' => '' // Opsional, hanya jika type='code'
            ]);

            $data = $response->json();

            // LOGIKA RESPON SESUAI DOKUMENTASI

            // 1. Jika device sudah connect
            if (isset($data['status']) && $data['status'] == false && isset($data['reason']) && $data['reason'] == 'device already connect') {
                $isConnected = true;
                $message = 'Device Fonnte Sudah Terhubung';
            }
            // 2. Jika belum connect (Dapat URL Base64)
            elseif (isset($data['status']) && $data['status'] == true && isset($data['url'])) {
                $isConnected = false;
                $qrImage = $data['url']; // Ini string base64
                $message = 'Silakan Scan QR Code Fonnte';
            }
            // 3. Error lain (misal token invalid)
            else {
                $isConnected = false;
                $message = $data['reason'] ?? 'Gagal mengambil data dari Fonnte.';
            }

        } catch (\Exception $e) {
            $message = 'Error Sistem: ' . $e->getMessage();
        }

        return view('admin.fonnte.scan', compact('isConnected', 'qrImage', 'message'));
    }
}
