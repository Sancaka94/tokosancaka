<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Draft pesanan WhatsApp yang diisi bertahap oleh customer.
 *
 * Satu baris = satu nomor WhatsApp yang sedang mengisi data.
 * Data dihapus setelah pesanan resmi (invoice) terbentuk.
 */
class BotOrderDraft extends Model
{
    protected $table = 'bot_order_drafts';

    protected $fillable = [
        'phone_number',
        'session_token',
        'step',
        'courier_code',
        'courier_service',
        'shipping_cost',
        'weight',
        'origin_text',
        'destination_text',
        'sender_name',
        'sender_phone',
        'sender_address',
        'receiver_name',
        'receiver_phone',
        'receiver_address',
        'item_description',
        'item_value',
        'raw_payload',
        'invoice_number',
        'payment_url',
    ];

    protected $casts = [
        'raw_payload'   => 'array',
        'shipping_cost' => 'integer',
        'weight'        => 'integer',
        'item_value'    => 'integer',
    ];

    /**
     * Urutan langkah pengisian data.
     * Alamat dipisah: wilayah (kelurahan/kecamatan/kota) divalidasi ke
     * KiriminAja lebih dulu, baru detail jalan — karena API alamat hanya
     * mengenali nama wilayah, bukan alamat jalan.
     */
    public const STEPS = [
        'AWAITING_SENDER_NAME',
        'AWAITING_SENDER_PHONE',
        'AWAITING_SENDER_AREA',
        'AWAITING_SENDER_ADDRESS',
        'AWAITING_RECEIVER_NAME',
        'AWAITING_RECEIVER_PHONE',
        'AWAITING_RECEIVER_AREA',
        'AWAITING_RECEIVER_ADDRESS',
        'AWAITING_ITEM_DESC',
        'AWAITING_ITEM_VALUE',
        'AWAITING_CONFIRMATION',
    ];

    /** Langkah berikutnya setelah $step, atau AWAITING_CONFIRMATION bila sudah habis. */
    public static function nextStep(string $step): string
    {
        $index = array_search($step, self::STEPS, true);

        if ($index === false) {
            return 'AWAITING_SENDER_NAME';
        }

        return self::STEPS[$index + 1] ?? 'AWAITING_CONFIRMATION';
    }
}
