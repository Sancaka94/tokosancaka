<?php



namespace App\Http\Middleware;



use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;



class VerifyCsrfToken extends Middleware

{

    protected function inExceptArray($request)

    {

        \Log::info('CSRF check: '.$request->path());

        return parent::inExceptArray($request);

    }



    /**

     * The URIs that should be excluded from CSRF verification.

     *

     * @var array<int, string>

     */

        protected $except = [

            '/xendit/webhook*',

            '/payment/notify*',

            '/callback/tripay',

            '/webhook/kiriminaja*',

            '/webhook/fonnte', // Masukkan URL route Anda di sini

            '/telegram-webhook',

            '/api/webhook/kiriminaja',

            '/api/webhook/doku-jokul', // <--- PASTIIN INI ADA

        ];

}

