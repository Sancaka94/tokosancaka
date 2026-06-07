<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RsudOrderObat extends Model
{
    use HasFactory;

    protected $table = 'rsud_order_obat';

    /**
     * PERBAIKAN: Gunakan $fillable eksplisit, bukan $guarded.
     * $guarded = ['id'] saja tidak cukup jika kolom-kolomnya belum terdaftar
     * atau ada typo nama kolom antara controller dan tabel database.
     */
    protected $fillable = [
        // Identitas
        'kode_booking',
        'nomor_rm',

        // Pengirim
        'sender_name',
        'sender_phone',
        'sender_address',
        'sender_province',
        'sender_regency',
        'sender_district',
        'sender_village',
        'sender_postal_code',
        'sender_district_id',
        'sender_subdistrict_id',
        'sender_lat',
        'sender_lng',

        // Penerima
        'receiver_name',
        'receiver_phone',
        'receiver_address',
        'receiver_province',
        'receiver_regency',
        'receiver_district',
        'receiver_village',
        'receiver_postal_code',
        'receiver_district_id',
        'receiver_subdistrict_id',
        'receiver_lat',
        'receiver_lng',

        // Detail Paket
        'item_description',
        'item_type',
        'weight',
        'length',
        'width',
        'height',
        'item_price',

        // Biaya
        'shipping_cost',
        'insurance_cost',
        'cod_fee',
        'total_price',

        // Ekspedisi
        'expedition',
        'service_type',

        // Pembayaran
        'payment_method',
        'payment_status',
        'payment_url',

        // Status Apotek
        'status_racik',
        'resi',
    ];

    protected $casts = [
        'item_price'    => 'integer',
        'shipping_cost' => 'integer',
        'insurance_cost'=> 'integer',
        'cod_fee'       => 'integer',
        'total_price'   => 'integer',
        'weight'        => 'integer',
    ];
}
