<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SystemModeUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $mode; // 'staging' atau 'production'

    public function __construct($mode)
    {
        $this->mode = $mode;
    }

    /**
     * Get the channels the event should broadcast on.
     * Menggunakan Channel Publik agar semua user (login/tidak) bisa menerima info ini.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('global-system-channel');
    }
}