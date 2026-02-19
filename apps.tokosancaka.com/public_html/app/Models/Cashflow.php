<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant; // <-- Pastikan ini di-import


class Cashflow extends Model
{
    use HasFactory;
    use BelongsToTenant; // <-- Pastikan ini dipasang di dalam class

    protected $table = 'cashflows';

    protected $fillable = [
        'name',
        'contact_id', // Tambahan
        'description',
        'type',
        'category', // Tambahan
        'amount',
        'date',
    ];

    public function contact()
    {
        return $this->belongsTo(CashflowContact::class, 'contact_id');
    }
}
