<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramPpobController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Ambil data
        $update = $request->all();
        
        // Log sederhana ke laravel.log
        Log::info('🔥 TEST HIT DARI TELEGRAM:', $update);

        if (isset($update['message']['text'])) {
            $chatId = $update['message']['chat']['id'];
            $text   = $update['message']['text'];

            // 2. Langsung balas tanpa mikir (Bypass database/logika ribet)
            $token = env('TELEGRAM_BOT_TOKEN');
            
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text'    => "✅ BERHASIL! Pesan masuk: " . $text . "\n\nServer: " . url('/'),
            ]);
        }

        return response('OK', 200);
    }
}