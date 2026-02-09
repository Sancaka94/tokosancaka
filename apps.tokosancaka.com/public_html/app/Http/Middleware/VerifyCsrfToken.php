<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Matikan CSRF untuk Login & Logout
        'login',
        'logout',
        'dana/notify',  // Sesuaikan dengan URL di screenshot

        // Jaga-jaga jika register juga bermasalah
        'register',

        // Wildcard untuk menangani subdomain (misal: sancaka.tokosancaka.com/login)
        '*/login',
        '*/logout',
        'http://*/login',
        'https://*/login',
    ];
}
