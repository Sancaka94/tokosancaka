<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // Pakai Now biar cepat
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BarcodeScanned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $barcode;
    public $qty;
    public $image; // <--- PASTIKAN INI ADA

    // KONSTRUKTOR HARUS TERIMA 3 DATA (Default null jika gambar tidak dikirim)
    public function __construct($barcode, $qty, $image = null)
    {
        $this->barcode = $barcode;
        $this->qty = $qty;
        $this->image = $image; // <--- PASTIKAN INI DI-ASSIGN
    }

    public function broadcastOn()
    {
        return new Channel('pos-channel');
    }

    public function broadcastAs()
    {
        return 'scanned';
    }

    // DATA YANG DIKIRIM KE JS
    public function broadcastWith()
    {
        return [
            'barcode' => $this->barcode,
            'qty'     => $this->qty,
            'image'   => $this->image // <--- JANGAN LUPA INI
        ];
    }
}
