<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    
    // PERBAIKAN UTAMA:
    // Menggunakan 'gemini-1.5-flash' yang sesuai dokumentasi resmi.
    // Format URL: https://generativelanguage.googleapis.com/v1beta/models/{MODEL}:generateContent
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    public function generateText($prompt)
    {
        // 1. Validasi API Key
        if (empty($this->apiKey)) {
            Log::error('Gemini Error: API Key tidak ditemukan di file .env');
            return 'Error: API Key Gemini belum diisi.';
        }

        try {
            // 2. Mengirim Request ke Google
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

            // 3. Cek Jika Berhasil (Status 200 OK)
            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, AI tidak memberikan respons.';
            }

            // 4. Jika Gagal, Catat Log Detail (Sesuai dokumentasi error structure)
            $errorBody = $response->json();
            $errorMessage = $errorBody['error']['message'] ?? 'Unknown Error';
            
            Log::error('Gemini API Error', [
                'status_code' => $response->status(),
                'model_used'  => 'gemini-1.5-flash',
                'details'     => $errorBody
            ]);

            return 'Gagal (Google): ' . $errorMessage;

        } catch (\Exception $e) {
            // Error koneksi atau kode internal
            Log::error('Gemini System Error: ' . $e->getMessage());
            return 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}