<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PesananTiket extends Model
{
    use HasFactory;

    // Nama tabel yang disesuaikan dengan Query SQL sebelumnya
    protected $table = 'pesanan_tikets';

    // Daftar kolom yang diizinkan untuk pengisian massal (mass assignment)
    protected $fillable = [
        'user_id',
        'booking_code',
        'booking_date',
        'time_limit',
        'airline_id',
        'origin',
        'destination',
        'depart_date',
        'trip_type',
        'pax_adult',
        'pax_child',
        'pax_infant',
        'contact_name',
        'contact_phone',
        'contact_email',
        'ticket_price',
        'status',
        'flight_detail',
        'pax_detail',
    ];

    /**
     * Konversi otomatis tipe data (Casting)
     * Sangat penting agar JSON otomatis menjadi array PHP.
     */
    protected $casts = [
        'flight_detail' => 'array',
        'pax_detail'    => 'array',
        'ticket_price'  => 'decimal:2',
    ];

    /**
     * Relasi ke User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
