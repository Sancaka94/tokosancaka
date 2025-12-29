<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $keys = [];
    
    // Kita tetap pakai 1.5 Flash karena ini yang paling stabil & kuota besar
protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-001:generateContent';
    public function __construct()
    {
        // Masukkan semua key ke dalam array
        // Anda bisa menambah KEY_3, KEY_4, dst jika mau
        $this->keys = [
            env('GEMINI_API_KEY_1'),
            env('GEMINI_API_KEY_2'),
        ];
    }

    public function generateText($prompt)
    {
        // Hapus key yang kosong (jika user lupa isi di .env)
        $availableKeys = array_filter($this->keys);

        if (empty($availableKeys)) {
            return 'Error: Tidak ada API Key Gemini yang ditemukan di file .env';
        }

        $lastError = 'Unknown Error';

        // --- LOOPING PINTAR ---
        // Coba satu per satu key yang ada
        foreach ($availableKeys as $index => $apiKey) {
            
            try {
                // Info log untuk debugging
                $keyNumber = $index + 1;
                
                // Request ke Google
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->post("{$this->baseUrl}?key={$apiKey}", [
                    'contents' => [
                        [
                            'parts' => [['text' => $prompt]]
                        ]
                    ]
                ]);

                // JIKA SUKSES: Langsung kembalikan hasil & BERHENTI looping
                if ($response->successful()) {
                    $data = $response->json();
                    return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, AI tidak memberikan respons.';
                }

                // JIKA GAGAL:
                // Simpan errornya, lalu lanjut ke loop berikutnya (Key selanjutnya)
                $errorBody = $response->json();
                $errorMessage = $errorBody['error']['message'] ?? 'Unknown API Error';
                $lastError = "Key ke-{$keyNumber} Gagal: {$errorMessage}";

                Log::warning("Gemini Key ke-{$keyNumber} gagal. Mencoba key berikutnya...", [
                    'error' => $errorMessage
                ]);

            } catch (\Exception $e) {
                $lastError = "Key ke-{$keyNumber} Error Sistem: " . $e->getMessage();
                Log::error($lastError);
            }
        }

        // Jika sampai sini, berarti SEMUA Key sudah dicoba dan SEMUANYA gagal
        Log::error('Semua API Key Gemini Gagal digunakan.');
        return 'Gagal Total: Semua API Key sedang limit atau bermasalah. Terakhir: ' . $lastError;
    }
}