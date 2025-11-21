<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User; // Import model User untuk relasi
use App\Models\Toko;

class Pesanan extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model.
     *
     * @var string
     */
    protected $table = 'pesanan';

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // Foreign key yang menghubungkan ke customer
        'customer_id',
        
        // Informasi Pengirim
        'sender_name',
        'sender_phone',
        'sender_address',
        
        // Informasi Penerima
        'receiver_name',
        'receiver_phone',
        'receiver_address',
        
        // Detail Paket & Layanan
        'service_type',
        'expedition',
        'payment_method',
        'item_description',
        'weight',
        'length',
        'width',
        'height',
        'kelengkapan',
        
        // Data yang di-generate oleh sistem
        'resi',
        'status',
        'tujuan',
        'total_biaya',
    ];

    /**
     * Atribut yang harus di-cast ke tipe data asli.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // 'kelengkapan' akan secara otomatis diubah menjadi array/JSON saat disimpan dan diambil.
        'kelengkapan' => 'array', 
    ];

    /**
     * Mendefinisikan relasi bahwa satu Pesanan dimiliki oleh satu Customer (User).
     */
    public function customer()
    {
        // Relasi ini terhubung ke model User melalui foreign key 'customer_id'
        return $this->belongsTo(User::class, 'customer_id');
    }
}
