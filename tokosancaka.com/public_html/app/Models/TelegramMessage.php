<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramMessage extends Model
{
    protected $fillable = [
        'message_id', 'chat_id', 'chat_title', 'text',
        'media_type', 'media_file_id'
    ];
}
