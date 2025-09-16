<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScannedPackage extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'scanned_packages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'kontak_id',
        'surat_jalan_id',
        'resi_number',
        'status',
    ];

    /**
     * Mendapatkan data kontak (pengguna publik) yang memiliki paket ini.
     */
    public function kontak()
    {
        return $this->belongsTo(Kontak::class);
    }

    /**
     * Mendapatkan data user (pelanggan terdaftar) yang memiliki paket ini.
     */
    public function user()
    {
        // ✅ PERBAIKAN: Menghubungkan 'user_id' di tabel ini ke 'id_pengguna' di tabel 'Pengguna'.
        return $this->belongsTo(User::class, 'user_id', 'id_pengguna');
    }

    /**
     * Mendapatkan data surat jalan yang terkait dengan paket ini.
     */
    public function suratJalan()
    {
        return $this->belongsTo(SuratJalan::class);
    }
}
