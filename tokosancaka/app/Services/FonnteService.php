<?php



namespace App\Services;



use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\Log;



class FonnteService

{

    public static function sendMessage($target, $message)

    {

        $response = Http::withHeaders([

            'Authorization' => config('services.fonnte.key'),

        ])->post('https://api.fonnte.com/send', [

            'target' => $target,

            'message' => $message,

        ]);



        Log::info('Fonnte API request', [

            'target'   => $target,

            'message'  => $message,

            'status'   => $response->status(),

            'response' => $response->json(),

            'token' => config('services.fonnte.key')

        ]);



        return $response;

    }

}

