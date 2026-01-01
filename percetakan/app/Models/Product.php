<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // Menonaktifkan timestamps jika Anda tidak membuat kolom created_at/updated_at di SQL manual sebelumnya
    public $timestamps = false; 

    protected $fillable = [
        'category_id',
        'name',
        'base_price',
        'unit',
        'stock_status'
    ];

    // Relasi ke Kategori
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relasi ke OrderDetail
    public function order_details()
    {
        return $this->hasMany(OrderDetail::class);
    }
}