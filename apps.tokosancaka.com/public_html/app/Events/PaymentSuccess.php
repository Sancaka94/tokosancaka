<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast; // <--- WAJIB
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentSuccess implements ShouldBroadcast // <--- PASTIKAN IMPLEMENTS INI
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $subdomain;

    public function __construct($subdomain)
    {
        $this->subdomain = $subdomain;
    }

    public function broadcastOn()
    {
        // Channel unik per tenant (misal: tenant.app)
        return new Channel('tenant.' . $this->subdomain);
    }
    
    public function broadcastAs()
    {
        return 'payment.received';
    }
}