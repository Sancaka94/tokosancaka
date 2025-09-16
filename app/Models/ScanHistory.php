<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScanHistory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'scan_histories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'spx_scan_id', // Pastikan kolom ini ada di tabel Anda
        'pesanan_id',  // Jika Anda masih menggunakan ini juga
        'status',
        'lokasi',
        'keterangan',
        'user_id',
    ];

    /**
     * Mendapatkan data SpxScan yang terkait dengan riwayat ini.
     */
    public function spxScan()
    {
        return $this->belongsTo(SpxScan::class, 'spx_scan_id');
    }

    /**
     * Mendapatkan data Pesanan yang terkait dengan riwayat ini.
     */
    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }
    
      public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
