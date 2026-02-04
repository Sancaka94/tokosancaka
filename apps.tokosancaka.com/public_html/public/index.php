<?php

// --- JEBAKAN PUBLIC (START) ---
// Jika URL di browser mengandung "/public", tendang ke URL bersih.
if (strpos($_SERVER['REQUEST_URI'], '/public') === 0) {
    $cleanUrl = str_replace('/public', '', $_SERVER['REQUEST_URI']);
    // Pastikan tidak kosong (kembali ke root)
    $cleanUrl = empty($cleanUrl) ? '/' : $cleanUrl;
    
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: " . $cleanUrl);
    exit();
}
// --- JEBAKAN PUBLIC (END) ---

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

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
