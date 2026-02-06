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
    public function scan()
    {
        $token = env('PUSHWA_TOKEN');

        // Default values
        $isConnected = false;
        $qrImage = null;
        $message = '';

        try {
            // 1. Panggil API PushWA
            $response = Http::post('https://dash.pushwa.com/api/startDevice', [
                'token' => $token
            ]);

            $data = $response->json();

            // 2. Cek Logika Respon
            if ($response->successful()) {

                // KEMUNGKINAN A: Sudah Terhubung
                if (isset($data['message']) && $data['message'] == 'connected') {
                    $isConnected = true;
                    $message = 'WhatsApp Sudah Terhubung';
                }
                // KEMUNGKINAN B: Belum Terhubung (Ada QR)
                elseif (isset($data['qr'])) {
                    $isConnected = false;
                    $qrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($data['qr']);
                    $message = 'Silakan Scan QR Code di bawah ini';
                }
            } else {
                $message = 'Gagal mengambil QR Code. Cek Token Anda.';
            }

        } catch (\Exception $e) {
            $message = 'Error Sistem: ' . $e->getMessage();
        }

        // 3. Kirim data ke View Admin
        return view('admin.pushwa.scan', compact('isConnected', 'qrImage', 'message'));
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


    /**
     * Fungsi Private: Logika inti kirim pesan
     */
    private function kirimPesan($target, $message)
    {
        $token = env('PUSHWA_TOKEN');

        // 1. Auto-Format Nomor (Ubah 08 jadi 628)
        // API WA biasanya wajib format internasional
        $target = $this->formatNomor($target);

        // 2. Kirim Request ke API kirimPesan
        try {
            $response = Http::post('https://dash.pushwa.com/api/kirimPesan', [
                'token'   => $token,
                'target'  => $target,
                'type'    => 'text',   // Sesuai kode curl Anda
                'delay'   => '1',      // Sesuai kode curl Anda
                'message' => $message
            ]);

            return $response->json();

        } catch (\Exception $e) {
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Helper: Format nomor HP Indonesia ke format 62
     */
    private function formatNomor($nomor)
    {
        $nomor = trim($nomor);
        if (substr($nomor, 0, 1) == '0') {
            return '62' . substr($nomor, 1);
        }
        return $nomor;
    }
}
