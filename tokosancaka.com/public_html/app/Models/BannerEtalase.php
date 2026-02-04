<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BannerEtalase extends Model
{
    use HasFactory;
    
    protected $table = 'banner_estalase';

    protected $fillable = ['image'];

}
