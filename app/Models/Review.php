<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'rating',
        'comment',
    ];

    /**
     * Dapatkan produk yang memiliki ulasan ini.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Dapatkan pengguna (user) yang menulis ulasan ini.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
