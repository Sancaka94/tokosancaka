<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Koli extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     * @var string
     */
    protected $table = 'kolis';

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     * Kolom volumetrik tidak dimasukkan karena dihitung oleh API KIRIMINaja.
     * @var array<int, string>
     */
    protected $fillable = [
        'pesanan_id',       // Kunci asing (merujuk ke id_pesanan di tabel induk)
        'item_description',
        'item_price',       // Nilai barang per koli (Rupiah)
        'weight',           // Berat per koli (gram)
        'length',           // Panjang per koli (cm)
        'width',            // Lebar per koli (cm)
        'height',           // Tinggi per koli (cm)
        'resi_koli',        // Opsional: Resi individual jika ada
    ];

    /**
     * Atribut yang harus di-casting.
     * @var array
     */
    protected $casts = [
        'item_price' => 'integer',
        'weight' => 'integer',
        'length' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    /**
     * Relasi: Koli dimiliki oleh satu Pesanan.
     * Menggunakan kunci non-standar 'id_pesanan' dari tabel induk.
     */
    public function pesanan(): BelongsTo
    {
        // Parameter:
        // 1. Pesanan::class (Model Induk)
        // 2. 'pesanan_id' (Kunci asing di tabel kolis ini)
        // 3. 'id_pesanan' (Kunci lokal di tabel pesanans)
        return $this->belongsTo(Pesanan::class, 'pesanan_id', 'id_pesanan');
    }
}