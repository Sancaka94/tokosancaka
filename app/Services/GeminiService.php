<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    
    // ✅ PERBAIKAN: Saya tambahkan "-latest" agar dikenali Google
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro-latest:generateContent';

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

            // Log Error Detail
            $errorBody = $response->json();
            
            Log::error('Gemini API Error', [
                'status' => $response->status(),
                'model'  => 'gemini-1.5-pro-latest', 
                'error'  => $errorBody
            ]);
            
            return 'Gagal (Google): ' . ($errorBody['error']['message'] ?? 'Unknown Error');

        } catch (\Exception $e) {
            Log::error('Gemini Exception: ' . $e->getMessage());
            return 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}