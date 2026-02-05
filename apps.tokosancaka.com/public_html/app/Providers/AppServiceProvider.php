<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // <--- WAJIB IMPORT INI
use Illuminate\Pagination\Paginator;

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
        // 1. SETUP PAGINATION
        Paginator::useTailwind();

        // 2. FORCE HTTPS (PENTING BUAT CLOUDFLARE/NGROK)
        if($this->app->environment('production') || $this->app->environment('local')) {
            URL::forceScheme('https');
        }

        // =================================================================
        // 3. [JURUS PAMUNGKAS] GLOBAL SUBDOMAIN INJECTION
        // =================================================================
        // Kode ini menyuntikkan parameter 'subdomain' ke SEMUA route (Sidebar, Header, dll)
        // secara otomatis sebelum aplikasi dirender. Obat ampuh untuk error "Missing parameter".

        // Cek: Jangan jalankan saat di Terminal (Artisan), hanya saat di Browser
        if (php_sapi_name() !== 'cli') {
            try {
                $host = request()->getHost();
                $parts = explode('.', $host);

                // Ambil bagian depan (toko1), jika gagal default ke 'admin'
                $subdomain = $parts[0] ?? 'admin';

                // Suntikkan ke seluruh aplikasi
                URL::defaults(['subdomain' => $subdomain]);
            } catch (\Exception $e) {
                // Silent error agar tidak crash saat migration awal
            }
        }
    }
}
