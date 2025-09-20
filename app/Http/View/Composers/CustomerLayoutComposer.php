<?php

namespace App\View\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class CustomerLayoutComposer
{
    /**
     * Bind data to the view.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {
        if (Auth::check() && Auth::user()->role !== 'Admin') {
            $user = Auth::user();

            // 1. Mengambil saldo pengguna
            $saldo = $user->saldo ?? 0;

            // 2. Mengambil notifikasi yang belum dibaca
            $notifications = $user->unreadNotifications ?? collect();

            // Mengirim semua data yang dibutuhkan ke view
            $view->with([
                'saldo' => $saldo,
                'notifications' => $notifications
            ]);

        } else {
             // Sediakan nilai default jika pengguna tidak login atau adalah admin
             $view->with([
                'saldo' => 0,
                'notifications' => collect() // Mengirim koleksi kosong
            ]);
        }
    }
}

