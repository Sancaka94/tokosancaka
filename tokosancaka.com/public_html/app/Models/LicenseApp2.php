<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicenseApp2 extends Model
{
    // 1. INI KUNCI UTAMANYA: Paksa model ini untuk menggunakan koneksi Aplikasi 2!
    protected $connection = 'mysql_second';

    // 2. Tunjuk nama tabelnya
    protected $table = 'licenses';

    // 3. Masukkan fillable persis seperti di Aplikasi 2
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
        'used_by_tenant_id'
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Catatan: Tidak perlu memasukkan trait BelongsToTenant atau relasi di sini,
    // karena Aplikasi 1 tugasnya HANYA untuk insert data (bikin kode).
}
