<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Broadcast;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Pastikan baris ini ada dan tidak ada tanda // di depannya
        Broadcast::routes();

        require base_path('routes/channels.php');
    }
}
