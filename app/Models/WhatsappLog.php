<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappLog extends Model
{
    use HasFactory;

    /**
     * Nama tabel di database.
     */
    protected $table = 'whatsapp_logs';

    /**
     * Kolom yang boleh diisi secara massal (Mass Assignment).
     * Penting agar fungsi ::create() berjalan lancar.
     */
    protected $fillable = [
        'sender_number',
        'sender_name',
        'message',
        'media_url', // Kolom baru untuk menampung gambar/file
        'type',      // 'incoming' atau 'outgoing'
        'status',
    ];

    /**
     * Konversi otomatis tipe data.
     * Memastikan created_at terbaca sebagai objek Carbon (Date).
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * (Opsional) Helper untuk mengecek apakah pesan gambar/file
     */
    public function hasMedia()
    {
        return !empty($this->media_url);
    }
}