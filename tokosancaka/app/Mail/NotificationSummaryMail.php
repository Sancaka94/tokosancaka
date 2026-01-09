<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class NotificationSummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Koleksi notifikasi yang akan dikirim.
     *
     * @var \Illuminate\Support\Collection
     */
    public $notifications;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Collection $notifications)
    {
        $this->notifications = $notifications;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.notification-summary')
                    ->subject('Rekapan Notifikasi Harian - Toko Sancaka');
    }
}
