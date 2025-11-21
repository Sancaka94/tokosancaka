<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\Admin\AdminLoginController;
use App\Http\Controllers\Auth\Customer\CustomerLoginController;
use App\Http\Controllers\Auth\Customer\CustomerRegisterController;
use App\Http\Controllers\Auth\Customer\CustomerForgotPasswordController;
use App\Http\Controllers\Auth\Customer\CustomerResetPasswordController;


    

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
    
// // Customer Auth
// Route::prefix('customer')->name('customer.')->group(function () {


//     Route::get('password/reset/success', function () {
//         if (session('status')) {
//             return view('auth.passwords.success');
//         }
//         return redirect()->route('customer.login');
//     })->name('password.reset.success')->middleware('auth');
// });

// // Password Reset Bridge (Workaround)
// Route::get('password/reset/{token}', function (Request $request, $token) {
//     return redirect()->route('customer.password.reset', [
//         'token' => $token,
//         'email' => $request->query('email'),
//     ]);
// })->name('password.reset');


