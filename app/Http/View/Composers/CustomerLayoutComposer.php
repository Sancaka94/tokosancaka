<?php

// ✅ 1. Namespace ini sekarang sudah pasti benar sesuai lokasi file.
namespace App\Http\View\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class CustomerLayoutComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view)
    {
        if (Auth::check() && Auth::user()->role !== 'Admin') {
            $user = Auth::user();
            $saldo = $user->saldo ?? 0;
            // Asumsi: Anda menggunakan sistem notifikasi bawaan Laravel.
            $notifications = $user->unreadNotifications ?? collect();

            $view->with([
                'saldo' => $saldo,
                'notifications' => $notifications
            ]);
        } else {
             $view->with([
                'saldo' => 0,
                'notifications' => collect()
            ]);
        }
    }
}

