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
        // Mengosongkan isi file log setiap hari pada tengah malam (00:00)
    $schedule->exec('truncate -s 0 ' . storage_path('logs/laravel.log'))
             ->daily();
             
    // Alternatif (Lebih sederhana, jika truncate tidak tersedia):
    // $schedule->exec('> ' . storage_path('logs/laravel.log'))
    //          ->daily();

    // Atau, jika ingin memastikan log dirotasi (opsi yang lebih disarankan untuk produksi):
    // $schedule->exec('php artisan log:clear')
    //          ->daily();


        // ✅ TAMBAHKAN INI:

        // Menjalankan perintah untuk mengirim rekapan notifikasi

        // setiap hari pada pukul 08:00 pagi.

        $schedule->command('summary:send-notifications')->dailyAt('08:00');

       // 1. Sinkronisasi Prabayar (Mulai jam 08:00, setiap jam)
    // Akan dicek oleh Cache 5 menit di dalam fungsi
    $schedule->command('digiflazz:sync-prepaid')
             ->hourlyAt(0) // Setiap jam di menit ke-0
             ->runInBackground();

    // 2. Sinkronisasi Pascabayar (Mulai jam 08:05, 5 menit setelah prabayar)
    // Waktu ini dijamin 5 menit setelah panggilan prepaid yang sukses
    $schedule->command('digiflazz:sync-postpaid')
             ->hourlyAt(5) // Setiap jam di menit ke-5
             ->runInBackground();

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

