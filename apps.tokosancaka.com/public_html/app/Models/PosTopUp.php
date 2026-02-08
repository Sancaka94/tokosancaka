<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant; // <-- Pastikan ini di-import


class PosTopUp extends Model
{
    use HasFactory;
    use BelongsToTenant; // <-- Pastikan ini dipasang di dalam class

    // KUNCI: Gunakan koneksi database kedua
    protected $connection = 'mysql_second';
    protected $table = 'top_ups';

    const STATUS_PENDING = 'PENDING';
    const STATUS_SUCCESS = 'SUCCESS';
    const STATUS_FAILED  = 'FAILED';

    protected $fillable = [
        'tenant_id',
        'affiliate_id',
        'reference_no',
        'amount',
        'unique_code',
        'total_amount',
        'status',
        'payment_method',
        'response_payload'
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'total_amount'     => 'decimal:2',
        'response_payload' => 'array',
    ];
}
