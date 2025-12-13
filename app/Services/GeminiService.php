<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        // Menggunakan Gemini 1.5 Flash sebagai default karena lebih cepat
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';
    }

    public function generateText($prompt)
    {
        if (empty($this->apiKey)) {
            return 'Error: API Key Gemini belum diisi di file .env';
        }

        try {
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

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, AI tidak memberikan respons.';
            }

            // Menangkap pesan error spesifik dari Google
            $errorBody = $response->json();
            $errorMessage = $errorBody['error']['message'] ?? 'Unknown Error';
            
            // Log error untuk developer (opsional)
            // \Log::error('Gemini API Error: ' . $errorMessage);

            return 'Gagal (Google): ' . $errorMessage;

        } catch (\Exception $e) {
            return 'Terjadi kesalahan sistem AI: ' . $e->getMessage();
        }
    }
}