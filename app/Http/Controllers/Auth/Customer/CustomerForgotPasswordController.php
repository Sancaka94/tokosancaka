<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;

/**
 * Class CustomerForgotPasswordController
 *
 * Controller ini menangani permintaan reset password untuk Pelanggan.
 */
class CustomerForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    /**
     * Menampilkan form untuk meminta link reset password.
     *
     * @return \Illuminate\View\View
     */
    public function showLinkRequestForm()
    {
        // Anda bisa membuat view khusus jika perlu, atau menggunakan view default Laravel
        return view('auth.passwords.email');
    }
}
