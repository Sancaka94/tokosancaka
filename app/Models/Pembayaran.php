<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    use HasFactory;

    /**
     * Menentukan nama tabel database secara eksplisit.
     * @var string
     */
    protected $table = 'Pembayaran';

    /**
     * Menentukan primary key dari tabel.
     * @var string
     */
    protected $primaryKey = 'id_pembayaran';

    /**
     * Menonaktifkan pengelolaan timestamp otomatis (created_at & updated_at).
     * Hapus baris ini jika tabel Anda memiliki kedua kolom tersebut.
     * @var bool
     */
    public $timestamps = false;

    /**
     * Kolom yang boleh diisi secara massal.
     * @var array
     */
    protected $fillable = [
        'nomor_resi_pengiriman',
        'metode_pembayaran',
        'biaya_pengiriman',
        'status_pembayaran',
        'tanggal_pembayaran',
        'id_file_bukti',
    ];

    /**
     * Mendefinisikan relasi bahwa satu Pembayaran dimiliki oleh satu Pengiriman.
     */
    public function pengiriman()
    {
        return $this->belongsTo(Pengiriman::class, 'nomor_resi_pengiriman', 'nomor_resi');
    }
}
