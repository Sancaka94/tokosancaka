<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Pastikan Anda menggunakan soft deletes jika perlu

class Order extends Model
{
    use HasFactory, SoftDeletes; // Hapus SoftDeletes jika tidak digunakan
    
    
    protected $table = 'orders'; // Eksplisit mendefinisikan nama tabel

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id', // ✅ DITAMBAHKAN: Izinkan user_id untuk diisi secara massal
        'invoice_number',
        'subtotal',
        'shipping_cost',
        'total_amount',
        'shipping_method',
        'payment_method',
        'status',
        'shipping_address',
        'products', // Jika Anda menyimpan detail produk sebagai JSON
        'payment_url',
        'payment_session_id',
        'transaction_id',
        'shipping_reference',
        'shipping_type',
        'cod_fee',
        'store_id',
        'shipped_at',
        'finished_at',
        'rejected_at',
        'returned_at'
    ];

    /**
     * Relasi ke model User.
     */
    public function user()
    {
        // Pastikan foreign key 'user_id' sudah benar
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }
    
    public function store()
    {
        // Pastikan foreign key 'user_id' sudah benar
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Relasi ke model OrderItem.
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
