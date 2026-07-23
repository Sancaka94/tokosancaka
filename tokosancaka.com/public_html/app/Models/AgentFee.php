<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentFee extends Model
{
    use HasFactory;

    protected $table = 'agent_fees';
    protected $fillable = [
        'user_id',
        'fee_percentage'
    ];

    // Relasi balik ke tabel User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
