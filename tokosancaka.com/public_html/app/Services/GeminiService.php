<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    // ✅ DIREKSEKSI KE OLLAMA LOCAL (http://localhost:11434/v1)
    // Ollama menyediakan OpenAI-compatible endpoint /v1/chat/completions
    protected $baseUrl = 'http://localhost:11434/v1/chat/completions';

    // General chat / PM / helpdesk
    protected $model = 'deepseek-coder:latest';

    public function generateText($prompt)
    {
        $messages = [
            ['role' => 'user', 'content' => $prompt],
        ];

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 1024,
        ];

        try {
            $response = Http::timeout(120)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->asJson()
                ->post($this->baseUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['choices'][0]['message']['content'])) {
                    return $data['choices'][0]['message']['content'];
                }
                return 'Maaf, AI tidak memberikan respons.';
            }

            $errorBody = $response->json();
            $errorMessage = $errorBody['error']['message'] ?? 'Unknown API Error';
            Log::warning("Ollama Local Error: " . $errorMessage);
            return 'Gagal: ' . $errorMessage;

        } catch (\Exception $e) {
            Log::error("Ollama Local Connection Error: " . $e->getMessage());
            return 'Gagal Koneksi: ' . $e->getMessage();
        }
    }
}