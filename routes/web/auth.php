<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\Admin\AdminLoginController;
use App\Http\Controllers\Auth\Customer\CustomerLoginController;
use App\Http\Controllers\Auth\Customer\CustomerRegisterController;
use App\Http\Controllers\Auth\Customer\CustomerForgotPasswordController;
use App\Http\Controllers\Auth\Customer\CustomerResetPasswordController;


// PERBAIKAN: Ganti 'guest' dengan 'auth.redirect'. 
// Middleware ini menjalankan logika role-based di RouteServiceProvider::HOME().
Route::middleware('guest')->group(function () { 

    Route::get('/login', [CustomerLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [CustomerLoginController::class, 'login']);

    Route::get('/register', [CustomerRegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [CustomerRegisterController::class, 'register']);
    Route::get('register/verify/{token}', [CustomerRegisterController::class, 'verify'])->name('register.verify');

    // Forgot password
    Route::get('/password-reset', [CustomerForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password-email', [CustomerForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    
    // Reset password
    Route::get('/password-reset/{token}', [CustomerResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password-reset', [CustomerResetPasswordController::class, 'reset'])->name('password.update');
});


 Route::post('/logout', [CustomerLoginController::class, 'logout'])->name('logout')->middleware('auth');