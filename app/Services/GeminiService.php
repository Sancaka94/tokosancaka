<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; // <--- Wajib ada untuk fitur logging

class GeminiService
{
    protected $apiKey;
    // URL Model Gemini 1.5 Flash (Pastikan tidak ada spasi)
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    public function generateText($prompt)
    {
        // 1. Cek API Key
        if (empty($this->apiKey)) {
            Log::error('Gemini Error: API Key belum diisi di .env');
            return 'Error: API Key Gemini belum diisi di file .env';
        }

        try {
            // Log bahwa kita sedang mencoba mengirim request (Opsional, untuk debug)
            // Log::info('Gemini: Mengirim request ke Google...');

            // 2. Kirim Request
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]);

            // 3. Jika Sukses
            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, AI tidak memberikan respons.';
            }

            // 4. Jika Gagal (Error Handling + Logging)
            $errorBody = $response->json();
            $errorMessage = $errorBody['error']['message'] ?? 'Unknown Error';
            
            // --- INI BAGIAN PENTING: MENCATAT ERROR KE LOG LARAVEL ---
            Log::error('Gemini API Error', [
                'status_code' => $response->status(), // Misal: 404, 400
                'url_used'    => $this->baseUrl,       // URL yang dipakai
                'full_error'  => $errorBody            // Respon lengkap dari Google
            ]);
            // ---------------------------------------------------------

            return 'Gagal (Google): ' . $errorMessage;

        } catch (\Exception $e) {
            // Log jika errornya dari koneksi internet atau codingan kita (bukan dari Google)
            Log::error('Gemini System Exception: ' . $e->getMessage());
            
            return 'Terjadi kesalahan sistem AI: ' . $e->getMessage();
        }
    }
}