<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'file_path',
        'file_name',
        'file_type',
        // Tambahkan 3 kolom ini:
        'color_mode',
        'paper_size',
        'quantity'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}