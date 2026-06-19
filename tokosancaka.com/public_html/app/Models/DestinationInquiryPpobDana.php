<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DestinationInquiryPpobDana extends Model
{
    use HasFactory;

    protected $table = 'destination_inquiries_ppob_dana';
    protected $primaryKey = 'inquiry_id';
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'inquiry_id',
        'request_msg_id',
        'primary_param',
        'status_code',
    ];
}