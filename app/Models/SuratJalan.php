<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratJalan extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'surat_jalans';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'kode_surat_jalan',
        'jumlah_paket',
        'kontak_id'
    ];

    /**
     * ✅ PERBAIKAN: Mendefinisikan relasi ke model User.
     * Setiap Surat Jalan dimiliki oleh satu User (Pelanggan).
     */
    public function user()
    {
        // Menghubungkan 'user_id' di tabel ini ke 'id_pengguna' di tabel 'Pengguna'
        return $this->belongsTo(User::class, 'user_id', 'id_pengguna');
    }

    /**
     * ✅ PERBAIKAN: Mendefinisikan relasi ke model ScannedPackage.
     * Setiap Surat Jalan memiliki banyak paket yang ter-scan.
     */
    public function packages()
    {
        return $this->hasMany(ScannedPackage::class);
    }
    
      public function kontak()
    {
        // Ganti 'App\Models\Kontak' jika path atau nama model Anda berbeda.
        // Ganti 'kontak_id' jika nama foreign key di tabel surat_jalans Anda berbeda.
        return $this->belongsTo(Kontak::class, 'kontak_id');
    }
}
