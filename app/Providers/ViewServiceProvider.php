<?php

namespace App\Providers; // <-- Pastikan namespace ini benar

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use App\Http\View\Composers\CustomerTopbarComposer;

// Pastikan nama kelas ini benar
class ViewServiceProvider extends ServiceProvider 
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
        // Ganti 'layouts.admin' jika nama layout Anda berbeda
        View::composer('layouts.admin', function ($view) {
            $pendaftaranBaruCount = DB::table('registration_requests')->count();
            $view->with('pendaftaranBaru', $pendaftaranBaruCount);
        });
        
        // ✅ PERBAIKAN: Mendaftarkan composer ke view topbar customer
        // Ganti path 'layouts.partials.customer.topbar' jika lokasi file topbar Anda berbeda
        View::composer('layouts.partials.customer.topbar', CustomerTopbarComposer::class);
    }
}