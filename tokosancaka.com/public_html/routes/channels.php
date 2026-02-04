<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use App\Models\User;

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

// Channel privat untuk notifikasi yang hanya ditujukan untuk admin.
Broadcast::channel('admin-notifications', function ($user) {
    // --- LANGKAH DEBUGGING ---
    // Log ini akan mencatat data pengguna yang mencoba terhubung ke channel.
    // Periksa hasilnya di file `storage/logs/laravel.log`.
    Log::info('Percobaan otorisasi channel admin:', [
        'user_id' => $user ? $user->id_pengguna : 'Guest (Tidak Login)',
        'user_role' => $user->role ?? 'Role tidak ditemukan'
    ]);
    // -------------------------

    // Memeriksa apakah kolom 'role' pada user adalah 'admin'.
    // Menggunakan strtolower untuk membuat pengecekan tidak case-sensitive.
    // Ditambahkan pengecekan `isset($user->role)` untuk menghindari error jika kolom role tidak ada.
    return isset($user->role) && strtolower($user->role) === 'admin';
});

// Channel privat untuk update saldo spesifik per pelanggan.
// Hanya user yang bersangkutan yang bisa mendengarkan channel ini.
Broadcast::channel('customer-saldo.{userId}', function ($user, $userId) {
    // Memastikan user yang sedang login hanya bisa mengakses channel saldonya sendiri.
    // Dikembalikan untuk menggunakan 'id_pengguna' secara eksplisit.
    return (int) $user->id_pengguna === (int) $userId;
});


//Broadcast::channel('customer.saldo.{id}', function ($user, $id) {
    // User hanya boleh dengar channel miliknya sendiri
    //return (int) $user->id === (int) $id;
//});
// Channel yang dapat diakses oleh semua user yang terotentikasi.
// Berguna untuk notifikasi umum, seperti saat surat jalan baru dibuat.
// Callback yang mengembalikan `true` akan mengizinkan semua user yang login
// untuk mendengarkan channel ini.
Broadcast::channel('surat-jalan-created', function ($user) {
    return true; 
});

