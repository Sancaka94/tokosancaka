<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderAttachment extends Model
{
    use HasFactory;
    protected $fillable = ['order_id', 'file_path', 'file_name', 'file_type'];
}