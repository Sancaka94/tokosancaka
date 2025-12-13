<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\FonnteService;
use App\Models\BroadcastHistory;
use Illuminate\Support\Facades\Log;

class SendBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $target;
    protected $message;
    protected $historyId;

    /**
     * Create a new job instance.
     */
    public function __construct($target, $message, $historyId)
    {
        $this->target = $target;
        $this->message = $message;
        $this->historyId = $historyId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // 1. Kirim Pesan via Fonnte
        $response = FonnteService::sendMessage($this->target, $this->message);
        
        // 2. Cek Status
        $status = ($response && $response->successful()) ? 'Terkirim' : 'Gagal';

        // 3. Update Status di Database Riwayat (BroadcastHistory)
        // Jadi user tau pesan ini sudah terkirim atau belum saat delay selesai
        if ($this->historyId) {
            $history = BroadcastHistory::find($this->historyId);
            if ($history) {
                $history->status = $status;
                $history->updated_at = now();
                $history->save();
            }
        }
    }
}