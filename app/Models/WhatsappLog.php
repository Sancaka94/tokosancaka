<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappLog extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_logs';

    // Pastikan media_url ada di sini agar tidak Error 500
    protected $fillable = [
        'sender_number',
        'sender_name',
        'message',
        'media_url',
        'type',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Helper untuk cek apakah ada media
    public function hasMedia()
    {
        return !empty($this->media_url);
    }
}