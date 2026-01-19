<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // Penting: Pakai 'Now' agar instan
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BarcodeScanned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $barcode;

    /**
     * Create a new event instance.
     */
    public function __construct($barcode)
    {
        $this->barcode = $barcode;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        // Nama channel harus sama dengan yang ada di Javascript (Echo)
        return [
            new Channel('pos-channel'),
        ];
    }

    /**
     * Nama event yang akan didengar oleh Javascript.
     * Penting: Di JS nanti mendengarnya sebagai '.scanned'
     */
    public function broadcastAs(): string
    {
        return 'scanned';
    }
}