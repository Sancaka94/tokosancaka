<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FixCloudflareHost
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Logika "Membaca Surat Rahasia" dari Cloudflare Worker
        if ($request->hasHeader('X-Original-Host')) {
            $realHost = $request->header('X-Original-Host');

            // Paksa Laravel pakai Host asli (toko1.tokosancaka.com)
            $request->headers->set('HOST', $realHost);
            $request->server->set('HTTP_HOST', $realHost);
        }

        return $next($request);
    }
}
