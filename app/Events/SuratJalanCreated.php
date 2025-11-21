<?php

namespace App\Events;

use App\Models\SuratJalan;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SuratJalanCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $suratJalan;
    public $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(SuratJalan $suratJalan, User $user)
    {
        $this->suratJalan = $suratJalan;
        $this->user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // Ini adalah nama channel publik yang akan didengarkan
        return new Channel('surat-jalan-created');
    }
}