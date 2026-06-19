<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductDanaPpob extends Model
{
    use HasFactory;

    protected $table = 'products_dana_ppob';
    protected $primaryKey = 'product_id';
    
    // Sangat penting karena primary key kita adalah VARCHAR (String), bukan Integer Auto-Increment
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'product_id',
        'product_type',
        'provider',
        'price_value',
        'price_currency',
        'is_available',
    ];

    public function orders()
    {
        return $this->hasMany(OrderPpobDana::class, 'product_id', 'product_id');
    }
}