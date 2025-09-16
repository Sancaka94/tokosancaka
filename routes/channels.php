<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    // PERBAIKAN FINAL: Membandingkan dengan 'id_pengguna'
    return (int) $user->id_pengguna === (int) $id;
});

Broadcast::channel('admin-notifications', function ($user) {
    return strtolower($user->role) === 'admin';
});

// ✅ Tambahkan ini: Otorisasi untuk channel saldo pelanggan
Broadcast::channel('customer-saldo.{userId}', function ($user, $userId) {
    // Pastikan ID pengguna yang sedang login sama dengan ID di nama channel.
    // Sesuaikan 'id_pengguna' jika nama primary key di model User Anda berbeda.
    return (int) $user->id_pengguna === (int) $userId;

});

Broadcast::channel('surat-jalan-created', function () {
    return true; // Channel publik, semua orang bisa mendengarkan

});