<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Transaction (Mutasi Saldo)
 *
 * Mewakili tabel 'transactions' di database.
 * Bertindak sebagai buku besar untuk semua pergerakan uang.
 */
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
     * Atribut yang boleh diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'status',
        'description',
        'reference_id',
    ];

    /**
     * Atribut yang harus di-cast ke tipe data tertentu.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'integer', // Otomatis konversi ke/dari integer
    ];

    /**
     * Mendapatkan data user (pengguna) yang memiliki transaksi ini.
     *
     * Ini adalah relasi "Transaction BELONGS TO User".
     */
    public function user(): BelongsTo
    {
        // Menghubungkan 'user_id' (di tabel transactions) 
        // dengan 'id_pengguna' (primary key di tabel users)
        return $this->belongsTo(User::class, 'user_id', 'id_pengguna');
    }
}