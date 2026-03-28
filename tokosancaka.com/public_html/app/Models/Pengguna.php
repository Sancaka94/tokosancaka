<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Pengguna extends Authenticatable
{
    use Notifiable; // Wajib ada agar fitur $user->notify() di Checkout berjalan lancar

    // 1. Nama tabel sesuai di database (Case Sensitive)
    protected $table = 'Pengguna';

    // 2. Custom Primary Key
    protected $primaryKey = 'id_pengguna';

    // 3. Mengizinkan semua kolom untuk diisi massal (Solusi error Mass Assignment)
    protected $guarded = [];

    // 4. Mematikan fitur otomatis updated_at (Solusi error Unknown column 'updated_at')
    const UPDATED_AT = null;

    // 5. Memberi tahu Laravel bahwa kolom password kita namanya 'password_hash'
    public function getAuthPasswordName()
    {
        return 'password_hash';
    }

    // (Opsional) Fallback bawaan Laravel untuk mengambil nilai password
    public function getAuthPassword()
    {
        return $this->password_hash;
    }
}
