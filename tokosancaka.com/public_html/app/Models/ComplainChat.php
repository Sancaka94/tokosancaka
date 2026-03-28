<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplainChat extends Model
{
    use HasFactory;

    protected $table = 'complain_chats';
    protected $guarded = [];

    // Relasi untuk menarik nama pengirim (Customer/User)
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id', 'id_pengguna');
    }
}
