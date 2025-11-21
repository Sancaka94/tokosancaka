<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Daftarkan command Anda di sini jika perlu,
        // Laravel 9+ biasanya mendeteksinya secara otomatis.
        // \App\Console\Commands\SendNotificationSummary::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Contoh:
        // $schedule->command('inspire')->hourly();

        // ✅ TAMBAHKAN INI:
        // Menjalankan perintah untuk mengirim rekapan notifikasi
        // setiap hari pada pukul 08:00 pagi.
        $schedule->command('summary:send-notifications')->dailyAt('08:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
