<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    protected $apiKey;
    // Kita definisikan URL langsung di sini agar lebih aman
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    public function generateText($prompt)
    {
        // 1. Cek API Key
        if (empty($this->apiKey)) {
            return 'Error: API Key Gemini belum diisi di file .env';
        }

        try {
            // 2. Kirim Request ke Google
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

            // 3. Cek Sukses
            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, AI tidak memberikan respons.';
            }

            // 4. Handle Error dari Google
            $errorBody = $response->json();
            $errorMessage = $errorBody['error']['message'] ?? 'Unknown Error';
            
            return 'Gagal (Google): ' . $errorMessage;

        } catch (\Exception $e) {
            return 'Terjadi kesalahan sistem AI: ' . $e->getMessage();
        }
    }
}