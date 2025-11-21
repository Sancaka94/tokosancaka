<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SaldoUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $newSaldo;

    /**
     * Create a new event instance.
     *
     * @param int $userId
     * @param float $newSaldo
     */
    public function __construct($userId, $newSaldo)
    {
        $this->userId = $userId;
        $this->newSaldo = $newSaldo;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // Mengirim ke channel privat yang spesifik untuk setiap user
        // Contoh nama channel: customer-saldo.4
        return new PrivateChannel('customer-saldo.' . $this->userId);
    }

    /**
     * Menentukan nama event yang akan dikirim.
     */
    public function broadcastAs()
    {
        return 'SaldoUpdated';
    }

    /**
     * Data yang akan dikirim bersama event.
     */
    public function broadcastWith()
    {
        return [
            'newSaldo' => $this->newSaldo,
            'formattedSaldo' => 'Rp ' . number_format($this->newSaldo, 0, ',', '.')
        ];
    }
}
