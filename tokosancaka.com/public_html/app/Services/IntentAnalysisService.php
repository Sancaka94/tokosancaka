<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IntentAnalysisService
{
    // Intent analysis customer (classify intent, trigger action)
    protected $baseUrl = 'http://localhost:11434/v1/chat/completions';
    protected $model = 'guoxuter/ov_intent_analysis_sft:v7_q8';

    public function analyze($prompt)
    {
        $messages = [
            ['role' => 'user', 'content' => $prompt],
        ];

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.0,
            'max_tokens' => 200,
        ];

        try {
            $response = Http::timeout(60)
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
            Log::warning("IntentAnalysis Error: " . $errorMessage);
            return 'Gagal: ' . $errorMessage;

        } catch (\Exception $e) {
            Log::error("IntentAnalysis Connection Error: " . $e->getMessage());
            return 'Gagal Koneksi: ' . $e->getMessage();
        }
    }
}
