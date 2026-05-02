<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CityTransaction extends Model
{
    use HasFactory;

    protected $fillable = ['city_id', 'jumlah', 'tanggal'];

    // Relasi ke model City
    public function city()
    {
        return $this->belongsTo(City::class);
    }
}