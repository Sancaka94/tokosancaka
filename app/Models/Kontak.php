<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;



class Kontak extends Model

{

    use HasFactory;



    // app/Models/Kontak.php
protected $fillable = [
    'nama',
    'no_hp',
    'alamat',
    'province',
    'regency',
    'district',
    'village',
    'postal_code',
    'id_pengguna',
    'tipe',
    'user_id',
    
    // PASTIKAN 4 BARIS INI ADA
    'lat',
    'lng',
    'district_id',
    'subdistrict_id',
];

}

