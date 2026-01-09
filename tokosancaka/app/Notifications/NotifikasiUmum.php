<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Support\Carbon;

/**
 * Class NotifikasiUmum (Versi Fleksibel)
 *
 * Notifikasi ini dirancang untuk menjadi "pembawa data" generik.
 * Controller akan "merakit" data notifikasi, dan class ini
 * akan mengirimkannya ke database dan broadcast.
 */
class NotifikasiUmum extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Data payload yang akan dikirim.
     * @var array
     */
    protected $data;

    /**
     * Buat instance notifikasi baru.
     *
     * @param array $data Data notifikasi yang sudah dirakit
     */
    public function __construct(array $data)
    {
        // Kita tambahkan timestamp universal di sini
        $this->data = array_merge($data, [
            'waktu_masuk_iso' => now()->toIso8601String(),
            'waktu_masuk_human' => now()->format('d M Y, H:i')
        ]);
    }

    /**
     * Tentukan channel pengiriman notifikasi.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // Kirim ke 'database' (tabel notifications) DAN
        // kirim ke 'broadcast' (Laravel Echo/Reverb)
        return ['database', 'broadcast'];
    }

    /**
     * Data yang akan disimpan di kolom 'data' (JSON) pada tabel 'notifications'.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        // Cukup kembalikan semua data yang sudah dirakit
        return $this->data;
    }

    /**
     * Data yang akan dikirim secara real-time ke frontend (Laravel Echo).
     *
     * @param  mixed  $notifiable Ini adalah instance model User ($pengguna)
     * @return \Illuminate\Notifications\Messages\BroadcastMessage
     */
    public function toBroadcast($notifiable)
    {
        $newUnreadCount = $notifiable->unreadNotifications()->count() + 1;

        // Data inilah yang akan diterima oleh JavaScript di frontend
        return new BroadcastMessage([
            // 'data' akan berisi semua data yang kita rakit
            'data' => $this->data,
            'unread_count' => $newUnreadCount,
        ]);
    }
}