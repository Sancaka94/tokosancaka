<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi melalui mass assignment.
     */
    protected $fillable = [
        'from_id',
        'to_id',
        'message',
        'read_at',
        'product_id',
        'image_url',
        'audio_url'
    ];

    /**
     * Konversi tipe data otomatis.
     * Mengonversi 'read_at' menjadi objek Carbon (datetime).
     */
    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * Relasi: Mengambil user pengirim pesan.
     */
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_id');
    }

    /**
     * Relasi: Mengambil user penerima pesan.
     */
    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_id');
    }

    /**
     * SCOPE: Mempermudah query pesan yang belum dibaca.
     * Penggunaan: Message::unread()->get();
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * SCOPE: Mempermudah query percakapan antara dua user.
     * Penggunaan: Message::betweenUsers($userId1, $userId2)->get();
     */
    public function scopeBetweenUsers($query, $user1, $user2)
    {
        return $query->where(function ($q) use ($user1, $user2) {
            $q->where('from_id', $user1)->where('to_id', $user2);
        })->orWhere(function ($q) use ($user1, $user2) {
            $q->where('from_id', $user2)->where('to_id', $user1);
        });
    }

    /**
     * Helper: Mengetahui apakah pesan sudah dibaca.
     */
    public function isRead()
    {
        return $this->read_at !== null;
    }
}
