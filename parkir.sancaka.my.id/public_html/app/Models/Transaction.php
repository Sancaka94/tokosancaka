<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Transaction extends Model
{
    use BelongsToTenant; // Otomatis filter data berdasarkan Subdomain

    protected $guarded = [];

    // Cast kolom datetime agar mudah diformat di Blade
    protected $casts = [
        'entry_time' => 'datetime',
        'exit_time' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}
