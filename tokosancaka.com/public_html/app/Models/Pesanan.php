<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Pesanan extends Model
{
    use HasFactory;

    //======================================================================
    //== PENGATURAN DASAR MODEL
    //======================================================================

    protected $table = 'Pesanan';

    protected $primaryKey = 'id_pesanan';

    public $timestamps = true;

    protected $guarded = ['id_pesanan'];

    protected $casts = [
        'kelengkapan' => 'array',
        'total_harga_barang' => 'float',
        'tanggal_pesanan' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'telah_dilihat' => 'boolean',
        'is_agreed' => 'boolean',
    ];

    //======================================================================
    //== AUTO NOTIFIKASI EMAIL PESANAN SUKSES KE salafy94@gmail.com
    //======================================================================

    protected static function booted()
    {
        static::created(function ($pesanan) {
            if (self::isPesananSukses($pesanan)) {
                $cacheKey = 'notified_order_email_' . ($pesanan->id_pesanan ?? $pesanan->nomor_invoice);
                if (Cache::add($cacheKey, true, now()->addDays(7))) {
                    \App\Http\Controllers\Admin\EmailController::kirimNotifikasiPesananSukses($pesanan);
                }
            }
        });

        static::updated(function ($pesanan) {
            if (self::isPesananSukses($pesanan)) {
                $cacheKey = 'notified_order_email_' . ($pesanan->id_pesanan ?? $pesanan->nomor_invoice);
                if (Cache::add($cacheKey, true, now()->addDays(7))) {
                    \App\Http\Controllers\Admin\EmailController::kirimNotifikasiPesananSukses($pesanan);
                }
            }
        });
    }

    private static function isPesananSukses($pesanan): bool
    {
        $status = strtolower((string) ($pesanan->status ?? $pesanan->status_pesanan ?? ''));
        $resi = trim((string) ($pesanan->resi ?? ''));

        if (str_contains($status, 'gagal') || str_contains($status, 'batal') || str_contains($status, 'pending') || str_contains($status, 'menunggu')) {
            return false;
        }

        $punyaResiValid = ($resi !== '' && $resi !== ($pesanan->nomor_invoice ?? ''));
        $statusSukses = in_array($status, ['sukses', 'success', 'paid', 'lunas', 'diproses', 'terkirim', 'siap dikirim', 'pickup']);

        return $punyaResiValid || $statusSukses;
    }

    //======================================================================
    //== RELASI DATABASE
    //======================================================================

    public function pembeli()
    {
        return $this->belongsTo(User::class, 'customer_id', 'id_pengguna');
    }

    public function scanHistories()
    {
        return $this->hasMany(ScanHistory::class, 'resi', 'resi')->latest();
    }

    public function toko()
    {
        return $this->belongsTo(Toko::class, 'id_toko', 'id_toko');
    }

    public function pengirim()
    {
        return $this->belongsTo(Kontak::class, 'kontak_pengirim_id');
    }

    public function penerima()
    {
        return $this->belongsTo(Kontak::class, 'kontak_penerima_id');
    }

    public function kolis(): HasMany
    {
        return $this->hasMany(Koli::class, 'pesanan_id', 'id_pesanan');
    }

    public function items()
    {
        return $this->hasMany(\App\Models\OrderItem::class, 'order_id', 'id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
