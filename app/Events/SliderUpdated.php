<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SliderUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $slides;

    /**
     * Create a new event instance.
     *
     * @param array $slides
     */
    public function __construct(array $slides)
    {
        $this->slides = $slides;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // This is a public channel that anyone can listen to
        return new Channel('site-updates');
    }

    /**
     * The name of the event to broadcast.
     */
    public function broadcastAs()
    {
        return 'SliderUpdated';
    }
}
