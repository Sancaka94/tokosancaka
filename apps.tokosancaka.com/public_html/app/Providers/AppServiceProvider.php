<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View; // <--- Wajib
use Illuminate\Support\Facades\File; // <--- Wajib
use App\Models\User; // <--- Digunakan untuk $tokoAdmin

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
        // 1. Pagination Tailwind
        Paginator::useTailwind();

        // 2. Force HTTPS
        // Catatan: Pastikan environment 'local' Anda mendukung HTTPS. 
        // Jika saat "php artisan serve" terjadi error SSL/koneksi ditolak, hapus "|| $this->app->environment('local')"
        if($this->app->environment('production') || $this->app->environment('local')) {
            URL::forceScheme('https');
        }

        // 3. Logic Subdomain (Anti Error Missing Parameter)
        if (php_sapi_name() !== 'cli') {
            try {
                $host = request()->getHost();
                $parts = explode('.', $host);
                $subdomain = $parts[0] ?? 'admin';
                URL::defaults(['subdomain' => $subdomain]);
            } catch (\Exception $e) {}
        }


        // =================================================================
        // 4. [AUTO VERSIONING] BACA DARI FILE JSON (UNTUK SIDEBAR)
        // =================================================================
        $fullVersion = '1.0.0.0'; // Default jika file json hilang
        $lastUpdate = '-';

        // Cek apakah file version.json ada?
        if (File::exists(base_path('version.json'))) {
            try {
                $jsonData = json_decode(File::get(base_path('version.json')), true);

                // Ambil versi dari file JSON yang kamu upload tadi
                $fullVersion = $jsonData['version'] ?? '1.0.0.0';
                $lastUpdate = $jsonData['last_update'] ?? '-';
            } catch (\Exception $e) {
                // Silent error
            }
        }

        // Bagikan variabel ini ke SEMUA View (termasuk Sidebar)
        View::share('app_version', $fullVersion);
        View::share('app_last_update', $lastUpdate);


        // =================================================================
        // 5. GLOBAL VIEW COMPOSER (SOLUSI ERROR $tokoAdmin)
        // =================================================================
        View::composer('*', function ($view) {
            // Menggunakan static agar query ke database hanya dilakukan 1x per request,
            // sangat menghemat resource jika halaman memuat banyak sub-view/komponen.
            static $tokoAdmin = null;

            if (is_null($tokoAdmin)) {
                $tokoAdmin = User::where('role', 'admin')->first();
            }

            $view->with('tokoAdmin', $tokoAdmin);
        });
    }
}