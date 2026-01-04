<?php 

use App\Http\Controllers\OrderController;

// Webhook KiriminAja (Method POST)
Route::post('/kiriminaja/webhook', [OrderController::class, 'handleWebhook']);

// Endpoint utilitas untuk men-setting URL Callback (Akses sekali saja)
Route::get('/kiriminaja/set-callback', [OrderController::class, 'setCallback']);