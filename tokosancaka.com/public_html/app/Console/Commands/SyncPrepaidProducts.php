<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DigiflazzService;

class SyncPrepaidProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'digiflazz:sync-prepaid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi daftar produk PRABAYAR (Pulsa, Data, Token) dari Digiflazz ke database.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(DigiflazzService $digiflazzService)
    {
        $this->info('Memulai sinkronisasi produk PRABAYAR Digiflazz...');

        // Panggil metode sinkronisasi prabayar dari service
        $result = $digiflazzService->syncPrepaidProducts();

        if ($result) {
            $this->info('✅ Sinkronisasi produk PRABAYAR berhasil diselesaikan.');
            $this->comment('Proses pembaruan data terjadi di background, dengan cache 5 menit.');
            return Command::SUCCESS;
        }

        $this->error('❌ Sinkronisasi produk PRABAYAR gagal. Periksa log Laravel untuk detail error (koneksi/API).');
        return Command::FAILURE;
    }
}