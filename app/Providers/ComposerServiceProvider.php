<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Http\View\Composers\ActivityNotificationComposer;

class ComposerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Method ini bisa dibiarkan kosong untuk kasus ini.
    }

    /**
     * Bootstrap any application services.
     *
     * Di sinilah kita mendaftarkan View Composer kita.
     *
     * @return void
     */
    public function boot()
    {
        // Perintah ini memberitahu Laravel:
        // "Setiap kali view 'layouts.partials.header' akan dirender,
        // panggil class ActivityNotificationComposer terlebih dahulu."
        View::composer(
            'layouts.partials.header',
            ActivityNotificationComposer::class
        );
    }
}

