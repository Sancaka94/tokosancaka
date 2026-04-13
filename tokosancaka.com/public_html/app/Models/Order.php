<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes; // Pastikan Anda menggunakan soft deletes jika perlu

use Illuminate\Support\Str; // <-- TAMBAHKAN INI



class Order extends Model

{

    use HasFactory, SoftDeletes; // Hapus SoftDeletes jika tidak digunakan





    protected $table = 'orders'; // Eksplisit mendefinisikan nama tabel



    /**

     * The attributes that are mass assignable.

     *

     * @var array<int, string>

     */

    protected $guarded = ['id'];



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


    // =============================================
    // TAMBAHKAN TIGA FUNGSI HELPER DI BAWAH INI
    // =============================================

    /**
     * Helper untuk mengambil nama Kurir dari string shipping_method
     * Cth: 'express-sicepat-REG-...' akan mengembalikan 'sicepat'
     */
    public function getShippingCourierAttribute(): string
    {
        $parts = explode('-', $this->shipping_method);
        return ucfirst($parts[1] ?? 'Tidak Diketahui'); // 'sicepat'
    }

    /**
     * Helper untuk mengambil nama Service dari string shipping_method
     * Cth: 'express-sicepat-REG-...' akan mengembalikan 'REG'
     */
    public function getShippingServiceAttribute(): string
    {
        $parts = explode('-', $this->shipping_method);
        return strtoupper($parts[2] ?? '-'); // 'REG'
    }

    /**
     * Helper untuk mendapatkan kelas badge Tailwind berdasarkan status
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match (strtolower($this->status)) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'paid', 'processing' => 'bg-blue-100 text-blue-800',
            'shipped' => 'bg-indigo-100 text-indigo-800',
            'completed' => 'bg-green-100 text-green-800',
            'cancelled', 'failed', 'expired' => 'bg-red-100 text-red-800',
             default => 'bg-gray-100 text-gray-800',
        };
    }

}

