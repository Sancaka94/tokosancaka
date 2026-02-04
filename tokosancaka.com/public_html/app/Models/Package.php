<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shipping_code',
        'status',
        'courier_id',
        // Tambahkan kolom lain yang relevan di sini
        // 'sender_name',
        // 'sender_address',
        // 'receiver_name',
        // 'receiver_address',
    ];

    /**
     * Mendefinisikan relasi "milik" ke model Courier.
     * Sebuah paket dimiliki oleh satu kurir.
     */
    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    /**
     * Mendefinisikan relasi "memiliki banyak" ke model Scan.
     * Sebuah paket bisa memiliki banyak riwayat scan.
     */
    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }
}
