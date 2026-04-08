<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;



class Pesanan extends Model

{

    use HasFactory;



    //======================================================================

    //== PENGATURAN DASAR MODEL

    //======================================================================



    /**

     * Nama tabel database yang terhubung dengan model ini.

     * @var string

     */

    protected $table = 'Pesanan';



    /**

     * Primary key dari tabel.

     * @var string

     */

    protected $primaryKey = 'id_pesanan';



    /**

     * ✅ PERBAIKAN: Mengaktifkan pengelolaan timestamp otomatis (created_at & updated_at)

     * karena tabel Anda memiliki kedua kolom tersebut.

     * @var bool

     */

    public $timestamps = true;



    /**

     * ✅ PERBAIKAN: Menggunakan $guarded untuk keamanan Mass Assignment.

     * Ini berarti semua kolom boleh diisi kecuali 'id_pesanan'.

     * @var array

     */

    protected $guarded = ['id_pesanan'];



    /**

     * Mengubah tipe data kolom tertentu secara otomatis.

     * @var array

     */

    protected $casts = [

        'kelengkapan' => 'array',

        'total_harga_barang' => 'float',

        'tanggal_pesanan' => 'datetime',

        'created_at' => 'datetime',

        'updated_at' => 'datetime',

        'telah_dilihat' => 'boolean',

    ];



    //======================================================================

    //== RELASI DATABASE

    //======================================================================



    /**

     * Relasi ke model User (sebagai pembeli).

     */

   public function pembeli()
{
    // Mengacu pada id_pengguna di tabel Pengguna
    return $this->belongsTo(User::class, 'customer_id', 'id_pengguna');
}



    /**

     * Relasi ke model ScanHistory.

     */

    public function scanHistories()

    {

        return $this->hasMany(ScanHistory::class, 'resi', 'resi')->latest();

    }



    /**

     * Relasi ke model Toko (jika digunakan).

     */

    public function toko()

    {

        return $this->belongsTo(Toko::class, 'id_toko', 'id_toko');

    }



    /**

     * Relasi ke model Kontak (sebagai pengirim).

     */

    public function pengirim()

    {

        return $this->belongsTo(Kontak::class, 'kontak_pengirim_id');

    }



    /**

     * Relasi ke model Kontak (sebagai penerima).

     */

    public function penerima()

    {

        return $this->belongsTo(Kontak::class, 'kontak_penerima_id');

    }

    public function kolis(): HasMany
    {
        return $this->hasMany(Koli::class, 'pesanan_id', 'id_pesanan');
    }

    /**
     * Relasi ke OrderItem (Detail Produk yang dibeli)
     */
    public function items()
    {
        // Sesuaikan 'order_id' dengan nama foreign key yang ada di tabel order_items Anda.
        // Jika di tabel order_items kolomnya bernama 'pesanan_id', ganti 'order_id' menjadi 'pesanan_id'.
        return $this->hasMany(\App\Models\OrderItem::class, 'order_id', 'id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'id_toko', 'id_toko');
    }

}

