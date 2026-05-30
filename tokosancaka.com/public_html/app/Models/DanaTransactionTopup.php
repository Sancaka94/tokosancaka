<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DanaTransactionTopup extends Model
{
    use HasFactory;

    /**
     * Mendefinisikan nama tabel secara eksplisit
     * karena nama tabel tidak menggunakan plural bahasa Inggris standar
     */
    protected $table = 'dana_transaction_topup';

    /**
     * Atribut yang diizinkan untuk mass-assignment
     */
    protected $fillable = [
        'user_id',
        'reference_id',
        'target_phone',
        'amount',
        'payment_method',
        'status',
    ];

    /**
     * Relasi ke Model User / Pengguna
     */
    public function user()
    {
        // Parameter: (NamaModelTarget, foreign_key_di_tabel_ini, owner_key_di_tabel_target)
        // Sesuaikan 'id_pengguna' dengan nama primary key di tabel User/Pengguna kamu
        return $this->belongsTo(User::class, 'user_id', 'id_pengguna');
    }
}