<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasFactory;

    protected $fillable = [
        'license_code',
        'tenant_id',
        'package_type',
        'max_devices',
        'max_ips',
        'duration_days',
        'status',
        'used_at',
        'expires_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Relasi ke tabel Tenant
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}