<?php

use Illuminate\Support\Facades\Route;
// Pastikan baris ini ada di atas
use App\Http\Controllers\Pondok\Admin\SantriController;

/* ... komentar lain ... */

Route::get('/', function () {
    return redirect()->route('admin.santri.index');
});

// Grup untuk semua rute admin
// Prefix 'admin' membuat URL menjadi /admin/...
// Name 'admin.' membuat nama rute menjadi admin....
Route::prefix('admin')->name('admin.')->group(function() {

    // Rute untuk resource santri (sudah ada)
    Route::resource('santri', SantriController::class);

    // TAMBAHKAN RUTE INI
    // Nama 'santri.updateStatus' akan otomatis menjadi 'admin.santri.updateStatus'
    // karena berada di dalam grup 'admin.'
    Route::patch('/santri/{id}/status', [SantriController::class, 'updateStatus'])
         ->name('santri.updateStatus');

});


require __DIR__.'/web/pondok.php';
