<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogSessionInfoMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Hanya jalankan jika informasi belum ada di session
        // untuk menghindari panggilan API berulang kali pada setiap request.
        if (!$request->session()->has('session_info')) {
            
            // 1. Dapatkan Alamat IP Pengguna
            $ipAddress = $request->ip();

            // 2. Dapatkan Informasi Lokasi dari IP menggunakan API gratis
            // (Gunakan @ untuk menekan error jika API gagal atau IP adalah localhost)
            $locationData = @json_decode(file_get_contents("http://ip-api.com/json/{$ipAddress}"));

            $locationInfo = 'Unknown';
            if ($locationData && $locationData->status === 'success') {
                $locationInfo = $locationData->city . ', ' . $locationData->country;
            }

            // 3. Dapatkan Informasi Perangkat (Device)
            $userAgent = $request->header('User-Agent');
            
            $sessionInfo = [
                'ip_address' => $ipAddress,
                'location'   => $locationInfo,
                'user_agent' => $userAgent,
                'device'     => $this->getDeviceType($userAgent)
            ];

            // 4. Simpan semua informasi ke dalam session
            $request->session()->put('session_info', $sessionInfo);
        }

        return $next($request);
    }

    /**
     * Fungsi sederhana untuk mendeteksi tipe perangkat dari User Agent.
     */
    private function getDeviceType(string $userAgent): string
    {
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', strtolower($userAgent))) {
            return 'Tablet';
        }
        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', strtolower($userAgent))) {
            return 'Mobile';
        }
        return 'Desktop';
    }
}
