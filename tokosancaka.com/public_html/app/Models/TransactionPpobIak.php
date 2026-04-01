<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionPpobIak extends Model
{
    // Mengubah referensi tabel ke transactionppobiak
    protected $table = 'transactionppobiak';

    protected $fillable = [
        'ref_id',
        'customer_id',
        'product_code',
        'price',
        'status',
        'sn',
        'message'
    ];

    // LOG LOG - Pastikan log lama Anda dipertahankan jika ada
}
