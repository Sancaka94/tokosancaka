<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    
    // ✅ UPDATE TERBARU: Menggunakan Model Gemini 3 Pro Preview
    // Sesuai data yang Anda kirimkan.
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-pro-preview:generateContent';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    public function generateText($prompt)
    {
        if (empty($this->apiKey)) {
            Log::error('Gemini Error: API Key kosong.');
            return 'Error: API Key Gemini belum diisi di file .env';
        }

        try {
            // Setup Request
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

            // Cek Jika Berhasil
            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, AI tidak memberikan respons.';
            }

            // Cek Jika Gagal
            $errorBody = $response->json();
            
            Log::error('Gemini API Error', [
                'status' => $response->status(),
                'model'  => 'gemini-3-pro-preview', 
                'error'  => $errorBody
            ]);
            
            return 'Gagal (Google): ' . ($errorBody['error']['message'] ?? 'Unknown Error');

        } catch (\Exception $e) {
            Log::error('Gemini Exception: ' . $e->getMessage());
            return 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}