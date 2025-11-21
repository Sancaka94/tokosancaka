<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model ini.
     *
     * @var string
     */
    protected $table = 'transactions';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'description',
    ];

    /**
     * Mendefinisikan relasi "belongsTo" ke model User.
     * Setiap transaksi dimiliki oleh satu pengguna.
     */
    public function user()
    {
        // Pastikan foreign key 'user_id' dan primary key 'id_pengguna' sudah benar
        return $this->belongsTo(User::class, 'user_id', 'id_pengguna');
    }
}

