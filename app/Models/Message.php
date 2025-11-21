<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'from_id',
        'to_id',
        'message',
        'read_at',
    ];

    /**
     * Get the user that sent the message.
     */
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_id');
    }

    /**
     * Get the user that received the message.
     */
    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_id');
    }
}
