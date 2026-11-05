<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use App\Services\IntentAnalysisService;
use Illuminate\Http\Request;

class MessageRouterController extends Controller
{
    protected $geminiService;
    protected $intentAnalysisService;

    public function __construct(GeminiService $geminiService, IntentAnalysisService $intentAnalysisService)
    {
        $this->geminiService = $geminiService;
        $this->intentAnalysisService = $intentAnalysisService;
    }

    /**
     * Handle incoming message (chat/PM/helpdesk)
     * Routing:
     *   - General question -> GeminiService (deepseek-coder:latest)
     *   - Intent trigger -> auto-action (IntentAnalysisService)
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'context' => 'array',
        ]);

        $text = trim($request->input('message', ''));
        $context = $request->input('context', []);

        if (empty($text)) {
            return response()->json(['error' => 'Pesan tidak boleh kosong'], 400);
        }

        // 1. Intent analysis (guoxuter/ov_intent_analysis_sft:v7_q8)
        $intentResult = $this->intentAnalysisService->analyze($text);
        $intent = $this->extractIntent($intentResult);

        // 2. Route berdasarkan intent
        if ($this->isActionIntent($intent)) {
            // Action intent -> auto-action atau trigger
            return $this->handleActionIntent($intent, $text, $context);
        }

        // 3. General chat -> GeminiService (deepseek-coder:latest)
        $response = $this->geminiService->generateText($text);

        return response()->json([
            'intent' => $intent,
            'action' => 'chat',
            'model' => 'deepseek-coder:latest',
            'response' => $response,
        ]);
    }

    /**
     * Handle action intent (e.g., cek ongkir, booking, invoice)
     */
    protected function handleActionIntent($intent, $text, $context)
    {
        // Intent analysis result - show as structured
        return response()->json([
            'intent' => $intent,
            'action' => 'auto',
            'model' => 'guoxuter/ov_intent_analysis_sft:v7_q8',
            'action_result' => [
                'message' => $text,
                'intent' => $intent,
                // TODO: trigger action here (e.g., cek ongkir, booking, invoice)
                'note' => 'Intent terdeteksi: ' . $intent . '. Action akan diproses di sisi aplikasi.',
            ],
        ]);
    }

    /**
     * Extract intent from analysis result (simple approach)
     */
    protected function extractIntent($result)
    {
        $text = strtolower(trim($result ?? ''));
        $keywords = [
            'ongkir' => 'ongkir',
            'shipping' => 'ongkir',
            'shipping' => 'shipping',
            'pengiriman' => 'ongkir',
            'pengiriman' => 'ongkir',
            'paket' => 'ongkir',
            'ekspedisi' => 'ongkir',
            'invoice' => 'invoice',
            'tagihan' => 'tagihan',
            'bayar' => 'bayar',
            'payment' => 'bayar',
            'pembayaran' => 'bayar',
            'booking' => 'booking',
            'reservation' => 'booking',
            'reservasi' => 'booking',
            'order' => 'booking',
            'pesanan' => 'booking',
            'order' => 'booking',
            'lacak' => 'lacak',
            'tracking' => 'lacak',
            'status' => 'lacak',
            'status' => 'status',
            'kelengkapan' => 'lacak',
            'keranjang' => 'keranjang',
            'checkout' => 'keranjang',
            'checkout' => 'checkout',
            'bantuan' => 'bantuan',
            'help' => 'bantuan',
            'help' => 'help',
            'support' => 'bantuan',
            'pertanyaan' => 'bantuan',
            'question' => 'bantuan',
            'tanya' => 'bantuan',
            'siap' => 'siap',
            'siap' => 'siap',
            'tunggu' => 'tunggu',
            'await' => 'tunggu',
            'process' => 'process',
            'processing' => 'process',
        ];

        foreach ($keywords as $word => $intent) {
            if (stripos($text, $word) !== false) {
                return $intent;
            }
        }

        return 'chat';
    }

    /**
     * Check if intent is action type
     */
    protected function isActionIntent($intent)
    {
        $actionIntents = ['ongkir', 'invoice', 'bayar', 'booking', 'lacak', 'keranjang', 'siap', 'tunggu', 'process'];
        return in_array($intent, $actionIntents);
    }
}
