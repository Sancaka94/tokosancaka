<?php

namespace App\Http\View\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class CustomerTopbarComposer
{
    /**
     * Bind data to the view.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $saldo = 0;

        // Hanya ambil saldo jika ada customer yang login
        if (Auth::check() && Auth::user()->role === 'Pelanggan') {
            // Langsung ambil nilai saldo yang tersimpan di database pengguna
            $saldo = Auth::user()->saldo ?? 0;
        }

        // Kirim variabel $saldo ke view yang menggunakan composer ini (topbar)
        $view->with('saldo', $saldo);
    }
}
