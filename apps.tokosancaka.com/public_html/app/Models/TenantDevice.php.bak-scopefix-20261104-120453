<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'ip_address',
        'user_agent',
        'last_accessed_at',
    ];

    protected $casts = [
        'last_accessed_at' => 'datetime',
    ];

    // Relasi ke tabel Tenant
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}