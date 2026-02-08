<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant; // <-- Pastikan ini di-import


class TopUp extends Model
{
    use HasFactory;
    use BelongsToTenant; // <-- Pastikan ini dipasang di dalam class

    protected $table = 'top_ups';

    // Kolom yang boleh diisi
    protected $fillable = [
        'tenant_id',
        'affiliate_id',
        'reference_no',
        'amount',
        'unique_code',
        'total_amount',
        'status', // PENDING, SUCCESS, FAILED, REFUNDED
        'payment_method',
        'response_payload'
    ];

    // Relasi ke Affiliate (Member)
    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class, 'affiliate_id');
    }
}
