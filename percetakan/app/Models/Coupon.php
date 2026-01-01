<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    // Jika tabel SQL Anda sudah memiliki created_at secara default
    public $timestamps = false; 

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'start_date',
        'expiry_date',
        'usage_limit',
        'used_count',
        'is_active'
    ];

    /**
     * Helper untuk cek apakah kupon masih bisa digunakan
     */
    public function isValid($totalOrder)
    {
        $today = date('Y-m-d');
        
        return $this->is_active &&
               $today >= $this->start_date &&
               $today <= $this->expiry_date &&
               $totalOrder >= $this->min_order_amount &&
               ($this->usage_limit === null || $this->used_count < $this->usage_limit);
    }
}