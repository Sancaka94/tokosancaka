<?php
// Tampilkan error PHP sementara untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load Composer autoload
require __DIR__.'/vendor/autoload.php';

// Load Laravel app
$app = require_once __DIR__.'/bootstrap/app.php';

// Jalankan HTTP kernel
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();
$kernel->terminate($request, $response);
