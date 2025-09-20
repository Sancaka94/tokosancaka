<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Http\View\Composers\CustomerLayoutComposer;
use Illuminate\Support\Facades\DB;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        // Tidak perlu ada perubahan di sini.
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Composer untuk Admin Layout (misal: notifikasi pendaftaran baru)
        View::composer('layouts.admin', function ($view) {
            try {
                // Mengambil jumlah pengguna yang statusnya belum aktif
                $pendaftaranBaruCount = DB::table('Pengguna')->where('status', 'Tidak Aktif')->count();
                $view->with('pendaftaranBaruCount', $pendaftaranBaruCount);
            } catch (\Exception $e) {
                // Mencegah error jika tabel belum ada saat migrasi
                $view->with('pendaftaranBaruCount', 0);
            }
        });
        
        // ✅ 2. DI sempurnakan: Menghubungkan composer ke layout customer UTAMA.
        // Ini adalah cara yang paling benar dan akan memperbaiki error 'Undefined variable'.
        // Semua view yang menggunakan 'layouts.customer' akan otomatis menerima data saldo & notifikasi.
        View::composer('layouts.customer', CustomerLayoutComposer::class);
    }
}

