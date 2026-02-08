<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant; // [KEMBALIKAN INI]

class PosTopUp extends Model
{
    use HasFactory;
    use BelongsToTenant; // [KEMBALIKAN INI] Agar otomatis filter by tenant

    // Karena ini di-deploy di Aplikasi Kedua (POS), gunakan koneksi default
    protected $table = 'top_ups';

    const STATUS_PENDING = 'PENDING';
    const STATUS_SUCCESS = 'SUCCESS';
    const STATUS_FAILED  = 'FAILED';

    protected $fillable = [
        'tenant_id',      // [KEMBALIKAN INI]
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
