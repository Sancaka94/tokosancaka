<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPasswordNotification extends ResetPasswordNotification
{
    /**
     * Membuat URL untuk reset password.
     * INI ADALAH FUNGSI KUNCI YANG KITA UBAH.
     *
     * @param  mixed  $notifiable // Ini adalah objek User yang akan dikirimi notifikasi
     * @return string
     */
    protected function resetUrl($notifiable)
    {
        // Cek peran pengguna
        if ($notifiable->role === 'Admin') {
            // Jika Admin, buat URL dengan route admin
            $routeName = 'admin.password.reset';
        } else {
            // Jika bukan Admin (misal: Pelanggan), buat URL dengan route customer
            $routeName = 'customer.password.reset';
        }

        // Membuat URL yang ditandatangani dengan token dan email
        return url(route($routeName, [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }
}
