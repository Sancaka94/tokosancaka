<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View; // Import untuk View Composer
use Illuminate\Support\Facades\Config; // Import untuk Config Injection
use Illuminate\Support\Facades\Schema; // Import untuk Cek Tabel DB
use Illuminate\Support\Facades\DB;     // <--- TAMBAHKAN INI (Wajib)
use App\Models\User;                   // <--- TAMBAHKAN INI (Wajib)
use App\Http\View\Composers\HeaderComposer;
use App\Models\Api; // Import Model API

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // --- TAMBAHKAN KODE INI ---
        // Cek jika file helper ada, lalu muat (require)
        if (file_exists(app_path('Helpers/ImageHelper.php'))) {
            require_once app_path('Helpers/ImageHelper.php');
        }
        // --------------------------
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // ----------------------------------------
        // 1. VIEW COMPOSER
        // ----------------------------------------
        View::composer('layouts.partials.header', HeaderComposer::class);
        
        // In boot()
View::composer('*', function ($view) {
    if (auth()->check()) {
         // Logic similar to DashboardController but without date filters (Global Stats)
         
         // 1. Total Pendapatan
         $pendapatan = \App\Models\TopUp::where('status', 'success')->sum('amount') 
                     + \App\Models\Pesanan::sum('shipping_cost');
         
         // 2. Total Pesanan
         $pesanan = \App\Models\Pesanan::count();
         
         // 3. Jumlah Toko (Seller)
         $toko = \App\Models\User::where('role', 'Seller')->count();
         
         // 4. Pengguna Baru (Pelanggan, < 30 days)
         $userBaru = \App\Models\User::where('role', 'Pelanggan')
                        ->where('created_at', '>=', now()->subDays(30))
                        ->count();
                        
         // Statuses
         $terkirim = \App\Models\Pesanan::where('status_pesanan', 'Selesai')->count();
         $dikirim = \App\Models\Pesanan::whereIn('status_pesanan', ['Sedang Dikirim', 'Dikirim', 'Diproses'])->count();
         $pickup = \App\Models\Pesanan::where('status_pesanan', 'Menunggu Pickup')->count();
         $gagal = \App\Models\Pesanan::whereIn('status_pesanan', ['Batal', 'Gagal', 'Retur', 'Kadaluarsa', 'Dibatalkan'])->count();

         $view->with('totalPendapatan', $pendapatan);
         // ... bind others
    }
}); // <--- PERBAIKAN: Menambahkan penutup kurung kurawal dan kurung biasa
        // ----------------------------------------
        // 2. INJECT CONFIG API DARI DATABASE
        // ----------------------------------------
        
        // PERBAIKAN: "try" dimulai DULUAN sebelum cek database
        try {
            // Cek apakah tabel API ada (dilakukan di dalam blok try agar aman)
            if (Schema::hasTable('API')) { 
                
                // --- A. INJECT KIRIMINAJA ---
                $kaMode = Api::getValue('KIRIMINAJA_MODE', 'global', 'staging');
                $kaToken = Api::getValue('KIRIMINAJA_TOKEN', $kaMode);
                $kaBaseUrl = Api::getValue('KIRIMINAJA_BASE_URL', $kaMode);

                Config::set('services.kiriminaja.token', $kaToken);
                Config::set('services.kiriminaja.base_url', $kaBaseUrl);

                // --- B. INJECT FONNTE ---
                $fonnteKey = Api::getValue('FONNTE_API_KEY', 'global');
                Config::set('services.fonnte.key', $fonnteKey);

                // --- C. INJECT TRIPAY ---
                $tpMode = Api::getValue('TRIPAY_MODE', 'global', 'sandbox');
                Config::set('tripay.api_key', Api::getValue('TRIPAY_API_KEY', $tpMode));
                Config::set('tripay.private_key', Api::getValue('TRIPAY_PRIVATE_KEY', $tpMode));
                Config::set('tripay.merchant_code', Api::getValue('TRIPAY_MERCHANT_CODE', $tpMode));

                // --- D. INJECT DOKU (LENGKAP) ---
                $dokuEnv = Api::getValue('DOKU_ENV', 'global', 'sandbox');
                
                Config::set('doku.mode', $dokuEnv);
                Config::set('doku.client_id', Api::getValue('DOKU_CLIENT_ID', $dokuEnv));
                Config::set('doku.secret_key', Api::getValue('DOKU_SECRET_KEY', $dokuEnv));
                Config::set('doku.sac_client_id', Api::getValue('DOKU_CLIENT_ID', $dokuEnv));
                Config::set('doku.sac_secret_key', Api::getValue('DOKU_SECRET_KEY', $dokuEnv));
                Config::set('doku.main_sac_id', Api::getValue('DOKU_MAIN_SAC_ID', 'global'));

                // Inject Keys
                Config::set('doku.doku_public_key', Api::getValue('DOKU_PUBLIC_KEY', $dokuEnv));
                Config::set('doku.merchant_private_key', Api::getValue('MERCHANT_PRIVATE_KEY', $dokuEnv));
                
                // Set URL sesuai mode
                if ($dokuEnv === 'production') {
                    Config::set('doku.url', 'https://api.doku.com');
                } else {
                    Config::set('doku.url', 'https://api-sandbox.doku.com');
                }
            }
        } catch (\Throwable $e) {
            // Jika koneksi DB gagal, diam saja (jangan crash).
            // Aplikasi akan lanjut jalan pakai settingan dari .env
        }
    }
}