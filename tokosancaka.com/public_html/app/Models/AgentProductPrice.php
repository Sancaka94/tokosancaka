<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentProductPrice extends Model
{
    use HasFactory;

    protected $table = 'agent_product_prices';

    protected $fillable = [
        'user_id',
        'product_id',
        'selling_price', // Harga jual settingan agen
    ];

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke Produk Master
    public function product()
    {
        return $this->belongsTo(PpobProduct::class, 'product_id');
    }
}