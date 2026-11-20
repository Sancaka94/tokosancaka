<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// --- TAMBAHAN: AUTO REDIRECT /PUBLIC KE ROOT ---
// Jika URL diawali dengan '/public', hapus dan redirect ke root
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/public') === 0) {
    $newUrl = substr($_SERVER['REQUEST_URI'], 7); // Hapus 7 karakter (/public)
    if (empty($newUrl)) {
        $newUrl = '/';
    }
    header("Location: " . $newUrl, true, 301);
    exit;
}
// -----------------------------------------------

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());