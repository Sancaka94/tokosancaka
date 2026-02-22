<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant; // <-- Pastikan ini di-import


class License extends Model
{
    use HasFactory;
    use BelongsToTenant; // <-- Pastikan ini dipasang di dalam class

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
        'status',
        'used_by_tenant_id',
        'created_at',
        'updated_at',
        'id', // Jangan lupa id juga, karena kita akan insert data baru dengan ID otomatis
        'user_id' // Tambahkan user_id jika diperlukan untuk relasi dengan user/tenant yang menggunakan lisensi
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
