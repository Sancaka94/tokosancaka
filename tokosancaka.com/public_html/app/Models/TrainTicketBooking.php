<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainTicketBooking extends Model
{
    use HasFactory;

    // Menentukan nama tabel secara eksplisit
    protected $table = 'train_ticket_booking';

    // Mengizinkan mass-assignment untuk kolom-kolom ini
    protected $fillable = [
        'booking_code',
        'payload',
    ];

    // Jika kamu ingin otomatis mengubah JSON string menjadi array PHP saat dipanggil
    protected $casts = [
        'payload' => 'array',
    ];
}
