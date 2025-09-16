<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopUp extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang digunakan oleh model.
     *
     * @var string
     */
    protected $table = 'top_ups';

    /**
     * Atribut yang boleh diisi secara massal.
     *
     * @var array
     */
    protected $fillable = [
        'customer_id',
        'user_id', // Disertakan untuk keamanan
        'transaction_id',
        'amount',
        'status',
        'payment_method',
    ];

    /**
     * Mendefinisikan relasi "dimiliki oleh" (belongsTo) ke model User.
     * Satu top up hanya dimiliki oleh satu pengguna.
     */
    public function user()
    {
        // Menghubungkan melalui foreign key 'customer_id'
        return $this->belongsTo(User::class, 'customer_id', 'id_pengguna');
    }
    
    public function getRouteKeyName()
{
    return 'transaction_id';
}

 public function customer()
    {
        // Menghubungkan kolom 'customer_id' di tabel ini ke primary key di tabel 'users'.
        return $this->belongsTo(User::class, 'customer_id', 'id_pengguna', 'nama_lengkap');
    }

}
