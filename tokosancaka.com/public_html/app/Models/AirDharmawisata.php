<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AirDharmawisata extends Model
{
    use HasFactory;

    protected $table = 'air_bookings';

    protected $fillable = [
        'user_id',
        'booking_code',
        'booking_code_airline',
        'airline_id',
        'trip_type',
        'origin',
        'destination',
        'depart_date',
        'return_date',
        'ticket_price',
        'sales_price',
        'margin',
        'contact_name',
        'contact_email',
        'contact_phone',
        'time_limit',
        'status',
    ];

    protected $casts = [
        'depart_date' => 'date',
        'return_date' => 'date',
        'time_limit'  => 'datetime',
        'ticket_price'=> 'decimal:2',
        'sales_price' => 'decimal:2',
        'margin'      => 'decimal:2',
    ];

    /**
     * Relasi ke model User (Pemilik Pesanan / Agen)
     */
    public function user()
    {
        // Sesuaikan 'User::class' dengan nama model pengguna Anda jika berbeda
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Relasi: Satu Booking memiliki BANYAK Penumpang (1-to-Many)
     */
    public function passengers()
    {
        return $this->hasMany(AirBookingPassenger::class, 'air_booking_id', 'id');
    }

    /**
     * Relasi: Satu Booking memiliki BANYAK Segmen Penerbangan (1-to-Many)
     */
    public function flights()
    {
        return $this->hasMany(AirBookingFlight::class, 'air_booking_id', 'id');
    }
}
