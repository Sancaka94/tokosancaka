<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftFlow extends Model
{
    protected $table = 'shift_flows';
    protected $fillable = [
    'shift_id', 
    'type', 
    'nominal', 
    'keterangan',
    'foto',             // Tambahkan ini
    'latitude',         // Tambahkan ini
    'longitude',        // Tambahkan ini
    'waktu_capture'     // Tambahkan ini
];
}