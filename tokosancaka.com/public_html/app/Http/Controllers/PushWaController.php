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
        $token = env('PUSHWA_TOKEN');

        try {
            $response = Http::post('https://dash.pushwa.com/api/startDevice', [
                'token' => $token
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // KEMUNGKINAN 1: Device Belum Connect -> Ada QR Code
                if (isset($data['qr'])) {
                    $qrImage = 'https://api.qrserver.com/v1/create-qr-code/?data=' . urlencode($data['qr']);
                    return view('pushwa.scan', compact('qrImage'));
                }

                // KEMUNGKINAN 2: Device Sudah Connect -> Status "connected"
                elseif (isset($data['message']) && $data['message'] == 'connected') {
                    // Tampilkan pesan sukses sederhana atau buat view khusus
                    return "<div style='display:flex; justify-content:center; align-items:center; height:100vh; font-family:sans-serif;'>
                                <div style='text-align:center;'>
                                    <h1 style='color:green;'>✅ WhatsApp Sudah Terhubung!</h1>
                                    <p>Server Anda siap mengirim pesan.</p>
                                    <a href='/wa/test-kirim' style='color:blue; text-decoration:underline;'>Coba Kirim Pesan Test</a>
                                </div>
                            </div>";
                }

                // KEMUNGKINAN 3: Respon lain (Error/Unknown)
                else {
                    return response()->json([
                        'message' => 'Respon tidak dikenali dari PushWA.',
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
