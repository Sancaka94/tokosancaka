<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DigiflazzService; // Pastikan namespace ini benar

class SyncDigiflazzProducts extends Command
{
    protected $signature = 'digiflazz:sync';
    protected $description = 'Sinkronisasi daftar produk PPOB dari Digiflazz ke database.';

    public function handle(DigiflazzService $digiflazzService)
    {
        $this->info('Memulai sinkronisasi produk Digiflazz...');
        
        $result = $digiflazzService->syncProducts();

        if ($result) {
            $this->info('✅ Sinkronisasi produk Digiflazz berhasil.');
        } else {
            $this->error('❌ Sinkronisasi produk Digiflazz gagal. Cek log untuk detail.');
        }
        
        return $result ? 0 : 1;
    }
}