<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // Pakai 'Now' agar bypass antrian (Instan)
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BarcodeScanned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $barcode;
    public $qty; // <--- TAMBAHAN: Variabel untuk menyimpan jumlah
    public $image; // <--- Variabel baru untuk menyimpan URL gambar

    /**
     * Create a new event instance.
     * Kita tambahkan parameter $qty dengan default 1
     */
    public function __construct($barcode, $qty = 1)
    {
        $this->barcode = $barcode;
        $this->qty = $qty;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        // Nama channel harus SAMA PERSIS dengan di Javascript (Echo.channel('...'))
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

    /**
     * [OPSIONAL TAPI BAGUS]
     * Menentukan data apa saja yang dikirim ke Javascript.
     * Ini membuat payload lebih bersih.
     */
    public function broadcastWith(): array
    {
        return [
            'barcode' => $this->barcode,
            'qty'     => $this->qty,
            'image'   => $this->image, // <--- Kirim ke JS
            'time'    => now()->toTimeString() // Bonus: Kirim waktu scan
        ];
    }
}
