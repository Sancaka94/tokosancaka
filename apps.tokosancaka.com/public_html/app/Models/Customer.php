<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant; // Pastikan Trait ini sudah dibuat untuk filter tenant_id otomatis

class Customer extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $table = 'customers';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'user_id',           // Admin/User yang menginput
        'subdomain',         // Subdomain saat input
        'name',
        'whatsapp',
        'address_detail',    // [FIX] Menggunakan address_detail agar konsisten dengan API
        'province',          // Nama Provinsi
        'regency',           // Nama Kota/Kabupaten
        'district',          // Nama Kecamatan
        'village',           // Nama Kelurahan/Desa
        'province_id',       // ID dari API
        'city_id',           // ID dari API
        'district_id',       // ID Kecamatan (Penting untuk KiriminAja)
        'subdistrict_id',    // ID Kelurahan (Penting untuk KiriminAja)
        'postal_code',
        'latitude',
        'longitude',
        'assigned_coupon',   // Kupon langganan customer
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'user_id' => 'integer',
        'province_id' => 'integer',
        'city_id' => 'integer',
        'district_id' => 'integer',
        'subdistrict_id' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke User (Admin yang menginput data ini)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke Tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Helper: Mendapatkan alamat lengkap dalam satu baris teks
     */
    public function getFullAddressAttribute()
    {
        return implode(', ', array_filter([
            $this->address_detail,
            $this->village,
            $this->district,
            $this->regency,
            $this->province,
            $this->postal_code
        ]));
    }
}
