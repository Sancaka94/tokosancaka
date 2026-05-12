<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AirBookingPassenger extends Model
{
    protected $table = 'air_booking_passengers';

    // Matikan timestamps karena di tabel SQL kita tidak menggunakan created_at & updated_at untuk anak tabel ini
    public $timestamps = false;

    protected $fillable = [
        'air_booking_id',
        'type',
        'title',
        'first_name',
        'last_name',
        'id_number',
        'birth_date',
        'ticket_number',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    /**
     * Relasi sebaliknya: Penumpang ini MILIK transaksi booking mana?
     */
    public function booking()
    {
        return $this->belongsTo(AirDharmawisata::class, 'air_booking_id', 'id');
    }
}
