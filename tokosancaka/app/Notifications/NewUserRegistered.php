<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserRegistered extends Notification
{
    use Queueable;

    protected $newUser;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(User $newUser)
    {
        $this->newUser = $newUser;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database']; // Ini akan menyimpan ke tabel 'notifications'
    }

    /**
     * Get the array representation of the notification.
     * (Ini yang akan dibaca oleh Blade Anda sebagai $notification->data)
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        // Pastikan 'admin.registrations.index' adalah nama route 
        // yang benar untuk halaman Manajemen Pendaftaran Anda
        $url = route('admin.registrations.index');

        return [
            'title'   => 'Pendaftaran Pelanggan Baru',
            'message' => 'Pelanggan baru (' . $this->newUser->nama_lengkap . ') telah mendaftar.',
            'url'     => $url ?? '#', // Fallback jika route tidak ditemukan
        ];
    }
}