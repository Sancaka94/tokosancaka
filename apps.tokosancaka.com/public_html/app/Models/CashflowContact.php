<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant; // <-- Pastikan ini di-import


class CashflowContact extends Model
{
    use HasFactory;
    use BelongsToTenant; // <-- Pastikan ini dipasang di dalam class

    protected $fillable = [
        'name', 'store_name', 'address', 'phone', 'balance'
    ];

    // Relasi ke transaksi
    public function cashflows()
    {
        return $this->hasMany(Cashflow::class, 'contact_id');
    }
}
