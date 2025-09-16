<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Maintenance mode
if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Composer autoload
require __DIR__.'/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';

// **Ambil HTTP Kernel**
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Tangkap request
$request = Request::capture();

// Handle request dan kirim response
$response = $kernel->handle($request);
$response->send();

// Terminate kernel (important untuk middleware terminate)
$kernel->terminate($request, $response);
