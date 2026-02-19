<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cashflow extends Model
{
    use HasFactory;

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
