<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RabItem extends Model
{
    // LOG LOG
    protected $table = 'rab_items';
    
    protected $guarded = ['id'];

    public function proyek()
    {
        return $this->belongsTo(Proyek::class, 'proyek_id');
    }
}