<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User; 

class ProductReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'rating',
        'comment',
        'reply',     // <--- TAMBAHAN
        'reply_at',  // <--- TAMBAHAN
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id_pengguna');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}