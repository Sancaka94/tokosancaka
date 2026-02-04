<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWhatsappJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $target;
    protected $message;

    /**
     * Terima data nomor hp dan pesan dari Controller
     */
    public function __construct($target, $message)
    {
        $this->target = $target;
        $this->message = $message;
    }

    /**
     * Eksekusi kirim WA (Berjalan di Background)
     */
    public function handle()
    {
        // 1. Ambil Token (Prioritas dari .env, kalau gagal pakai hardcode)
        $token = env('FONNTE_API_KEY', 'ynMyPswSKr14wdtXMJF7');

        try {
            // 2. Kirim Request ke Fonnte
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->retry(3, 1000) // Coba ulang 3x jika gagal, jeda 1 detik
              ->timeout(30)    // Batas waktu koneksi 30 detik (karena di background, boleh lama)
              ->post('https://api.fonnte.com/send', [
                'target' => $this->target,
                'message' => $this->message,
            ]);

            // 3. Cek Hasil & Catat Log
            if ($response->successful()) {
                Log::info("✅ [JOB WA] Berhasil kirim ke: " . $this->target);
            } else {
                Log::error("❌ [JOB WA] Gagal Fonnte: " . $response->body());
            }

        } catch (\Exception $e) {
            Log::error("❌ [JOB WA] Error Koneksi: " . $e->getMessage());
        }
    }
}