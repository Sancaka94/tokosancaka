<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // <--- WAJIB IMPORT INI
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View; // <--- Tambah ini
use Illuminate\Support\Facades\File; // <--- Tambah ini

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useTailwind();

        // Force HTTPS
        if($this->app->environment('production') || $this->app->environment('local')) {
            URL::forceScheme('https');
        }

        // Jurus Subdomain (Biarkan kode yang tadi)
        if (php_sapi_name() !== 'cli') {
            try {
                $host = request()->getHost();
                $parts = explode('.', $host);
                $subdomain = $parts[0] ?? 'admin';
                URL::defaults(['subdomain' => $subdomain]);
            } catch (\Exception $e) {}
        }

        // =================================================================
        // 4. [FITUR BARU] AUTO VERSIONING DARI GIT
        // =================================================================
        // Versi Dasar (Bisa kamu ganti manual jika mau naik ke v2)
        $mainVersion = '1.0.0';
        $buildNumber = '0';
        $lastUpdate = now()->format('d M Y');

        try {
            // Cek apakah folder .git ada?
            if (File::exists(base_path('.git'))) {
                // Hitung total commit (akan naik terus setiap kamu save/commit)
                $buildNumber = trim(exec('git rev-list --count HEAD'));

                // Ambil Hash pendek (misal: a1b2c)
                $hash = trim(exec('git rev-parse --short HEAD'));

                // Ambil tanggal commit terakhir
                $lastUpdate = trim(exec('git log -1 --format=%cd --date=format:"%d %b %Y"'));
            }
        } catch (\Exception $e) {
            // Fallback jika git error
        }

        // Gabungkan: 1.0.0.154 (154 adalah total commit kamu)
        $fullVersion = "{$mainVersion}.{$buildNumber}";

        // Bagikan variable ini ke SEMUA View (Sidebar, Footer, dll)
        View::share('app_version', $fullVersion);
        View::share('app_last_update', $lastUpdate);
    }
}
