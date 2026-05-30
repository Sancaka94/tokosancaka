<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DanaTransactionTopup extends Model
{
    use HasFactory;

    protected $table = 'dana_transaction_topup';

    protected $fillable = [
        'user_id',
        'reference_id',
        'target_phone',
        'amount',
        'admin_fee',       // <--- TAMBAH INI
        'total_amount',    // <--- TAMBAH INI
        'payment_method',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id_pengguna');
    }
}