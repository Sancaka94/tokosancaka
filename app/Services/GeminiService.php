<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    protected $apiKey;
    // UBAH BARIS INI: Ganti 'gemini-1.5-flash' menjadi 'gemini-pro'
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    public function generateText($prompt)
    {
        // Cek jika API Key kosong
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

            // Cek sukses
            if ($response->successful()) {
                $data = $response->json();
                
                // Ambil text (Struktur Gemini Pro)
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, AI tidak memberikan respons.';
            }

            // Jika Gagal, kembalikan pesan error dari Google agar mudah debug
            $errorBody = $response->json();
            $errorMessage = $errorBody['error']['message'] ?? 'Unknown Error';
            
            return 'Gagal (Google): ' . $errorMessage;

        } catch (\Exception $e) {
            return 'Terjadi kesalahan sistem AI: ' . $e->getMessage();
        }
    }
}