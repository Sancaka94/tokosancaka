<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User; 
use App\Models\PpobProduct;

class PpobTransaction extends Model
{
    use HasFactory;

    // Nama tabel sesuai yang Anda buat di SQL
    protected $table = 'ppob_transactions';

    // Kolom yang bisa diisi oleh Controller
    protected $fillable = [
        'user_id', 
        'order_id', 
        'buyer_sku_code', 
        'customer_no',
        'price', 
        'selling_price', 
        'profit', 
        'status', 
        'sn', 
        'message'
    ];

    /**
     * Relasi ke User (Pembeli)
     * PENTING: Karena tabel user Anda 'Pengguna' dengan PK 'id_pengguna'
     */
    public function user()
    {
        // belongsTo(Model, Foreign Key di tabel ini, Owner Key di tabel user)
        return $this->belongsTo(User::class, 'user_id', 'id_pengguna');
    }

    /**
     * Relasi ke Produk PPOB
     * Menggunakan buyer_sku_code sebagai penghubung
     */
    public function product()
    {
        return $this->belongsTo(PpobProduct::class, 'buyer_sku_code', 'buyer_sku_code');
    }
}