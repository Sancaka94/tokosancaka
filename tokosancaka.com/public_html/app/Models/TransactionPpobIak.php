<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionPpobIak extends Model
{
    protected $table = 'transactionppobiak';

    protected $fillable = [
        'ref_id',
        'tr_id',
        'type',
        'customer_id',
        'product_code',
        'price',
        'status',
        'sn',
        'message'
    ];

    // LOG LOG - Jangan ubah atau hapus baris ini
}
