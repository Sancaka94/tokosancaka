<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AirBookingFlight extends Model
{
    protected $table = 'air_booking_flights';

    // Matikan timestamps karena di tabel SQL kita tidak menggunakan created_at & updated_at
    public $timestamps = false;

    protected $fillable = [
        'air_booking_id',
        'journey_type',
        'flight_number',
        'origin',
        'destination',
        'depart_time',
        'arrival_time',
        'flight_class',
    ];

    protected $casts = [
        'depart_time'  => 'datetime',
        'arrival_time' => 'datetime',
    ];

    /**
     * Relasi sebaliknya: Penerbangan ini MILIK transaksi booking mana?
     */
    public function booking()
    {
        return $this->belongsTo(AirDharmawisata::class, 'air_booking_id', 'id');
    }
}
