<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Mobile\EmailController;

// Pastikan dibungkus middleware auth:sanctum (atau middleware token mobile Anda)
Route::middleware(['auth:sanctum'])->prefix('mobile/email')->group(function () {
    Route::get('/status', [EmailController::class, 'status']);
    Route::get('/fetch', [EmailController::class, 'fetch']);
    Route::get('/search-users', [EmailController::class, 'searchUsers']);
    Route::get('/{id}', [EmailController::class, 'show']);
    Route::post('/send', [EmailController::class, 'send']);
    Route::put('/{id}', [EmailController::class, 'update']);
    Route::post('/delete', [EmailController::class, 'destroy']); // Menggunakan POST agar aman menerima array `ids` dari React Native
});
