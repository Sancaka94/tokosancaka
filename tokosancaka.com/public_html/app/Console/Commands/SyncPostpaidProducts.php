<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DigiflazzService;

class SyncPostpaidProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'digiflazz:sync-postpaid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi daftar produk PASCABAYAR (PLN, PDAM, dll.) dari Digiflazz ke database.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(DigiflazzService $digiflazzService)
    {
        $this->info('Memulai sinkronisasi produk PASCABAYAR Digiflazz...');
        
        // Panggil metode sinkronisasi pascabayar dari service
        $result = $digiflazzService->syncPostpaidProducts();

        if ($result) {
            $this->info('✅ Sinkronisasi produk PASCABAYAR berhasil diselesaikan.');
            $this->comment('Penting: Sinkronisasi pascabayar ini dijadwalkan 5 menit setelah prabayar.');
            return Command::SUCCESS;
        }

        $this->error('❌ Sinkronisasi produk PASCABAYAR gagal. Ini mungkin karena Limitasi API Digiflazz (rc: 83) atau masalah koneksi/data. Periksa log Laravel.');
        return Command::FAILURE;
    }
}