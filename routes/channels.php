<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Di sini Anda dapat mendaftarkan semua channel event broadcasting yang
| didukung oleh aplikasi Anda. Callback otorisasi channel yang diberikan
| digunakan untuk memeriksa apakah pengguna yang diautentikasi dapat 
| mendengarkan channel tersebut.
|
*/

// Channel default untuk notifikasi per-user model.
// Ini memungkinkan notifikasi dikirim ke user tertentu.
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    // Dikembalikan untuk menggunakan 'id_pengguna' secara eksplisit.
    return (int) $user->id_pengguna === (int) $id;
});

Broadcast::channel('admin-notifications', function ($user) {
    // Tambahkan log ini untuk debugging
    \Illuminate\Support\Facades\Log::info('Mencoba otorisasi channel admin untuk user:', $user->toArray());
    
    return isset($user->role) && strtolower($user->role) === 'admin';
});

// Channel privat untuk update saldo spesifik per pelanggan.
// Hanya user yang bersangkutan yang bisa mendengarkan channel ini.
Broadcast::channel('customer-saldo.{userId}', function ($user, $userId) {
    // Memastikan user yang sedang login hanya bisa mengakses channel saldonya sendiri.
    // Dikembalikan untuk menggunakan 'id_pengguna' secara eksplisit.
    return (int) $user->id_pengguna === (int) $userId;
});

// Channel yang dapat diakses oleh semua user yang terotentikasi.
// Berguna untuk notifikasi umum, seperti saat surat jalan baru dibuat.
// Callback yang mengembalikan `true` akan mengizinkan semua user yang login
// untuk mendengarkan channel ini.
Broadcast::channel('surat-jalan-created', function ($user) {
    return true; 
});

