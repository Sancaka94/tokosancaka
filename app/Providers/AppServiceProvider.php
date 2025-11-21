<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View; // Import the View facade
use App\Http\View\Composers\HeaderComposer; // Import your composer
//use Illuminate\Pagination\Paginator; 

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Tell Laravel to use the HeaderComposer for the header partial
        View::composer('layouts.partials.header', HeaderComposer::class);
        //Paginator::useBootstrapFive();
    }
}
