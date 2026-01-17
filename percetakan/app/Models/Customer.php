<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';

    protected $fillable = [
        'name',
        'whatsapp',
        'address',           // Alamat detail jalan
        'province_id',       // Tambahan
        'city_id',           // Tambahan
        'district_id',       // Penting untuk API (Kecamatan)
        'subdistrict_id',    // Penting untuk API (Kelurahan)
        'province_name',
        'city_name',
        'district_name',
        'subdistrict_name',
        'postal_code',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'province_id' => 'integer',
        'city_id' => 'integer',
        'district_id' => 'integer',
        'subdistrict_id' => 'integer',
    ];
}
