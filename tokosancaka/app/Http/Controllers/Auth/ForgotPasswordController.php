<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Routing\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;

/**
 * ✅ PERBAIKAN: Nama class diubah menjadi 'ForgotPasswordController'
 * agar sesuai dengan nama file dan tidak bentrok.
 */
class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | Controller ini bertanggung jawab untuk menangani permintaan reset
    | password dan mengirimkan link reset melalui email kepada pengguna.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * Membuat instance controller baru.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }
}
