<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IakPrepaidResponseCode extends Model
{
    protected $table = 'iak_prepaid_response_codes';

    protected $fillable = [
        'code', 'description', 'status', 'solution'
    ];
    // LOG LOG
}
