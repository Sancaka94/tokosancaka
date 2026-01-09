<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $keys = [];
    
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
    public function __construct()
    {
        $this->keys = [
            env('GEMINI_API_KEY_1'),
            env('GEMINI_API_KEY_2'),
        ];
    }

    public function generateText($prompt)
    {
        $availableKeys = array_filter($this->keys);

        if (empty($availableKeys)) {
            return 'Error: Tidak ada API Key Gemini yang ditemukan di file .env';
        }

        $lastError = 'Unknown Error';

        foreach ($availableKeys as $index => $apiKey) {
            $apiKey = trim($apiKey);
            if (empty($apiKey)) continue;

            try {
                $keyNumber = $index + 1;
                
                // Jeda sedikit saat pindah key
                if ($index > 0) sleep(2);

                // ✅ PERBAIKAN UTAMA: timeout(120)
                // Kita beri waktu 120 detik (2 menit) agar tidak error "cURL timeout"
                $response = Http::timeout(120)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post("{$this->baseUrl}?key={$apiKey}", [
                        'contents' => [
                            [
                                'parts' => [['text' => $prompt]]
                            ]
                        ]
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, AI tidak memberikan respons.';
                }

                $errorBody = $response->json();
                $errorMessage = $errorBody['error']['message'] ?? 'Unknown API Error';
                $lastError = "Key-{$keyNumber}: {$errorMessage}";

                Log::warning("Gemini Key ke-{$keyNumber} Gagal.", ['error' => $errorMessage]);

            } catch (\Exception $e) {
                // Menangkap error timeout
                $lastError = "Key-{$keyNumber} Error Koneksi: " . $e->getMessage();
                Log::error($lastError);
            }
        }

        return 'Gagal Total. Pesan Terakhir: ' . $lastError;
    }
}