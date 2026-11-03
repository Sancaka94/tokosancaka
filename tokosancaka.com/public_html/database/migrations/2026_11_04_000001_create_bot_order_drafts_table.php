<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_order_drafts', function (Blueprint $table) {
            $table->id();

            // Identitas sesi WhatsApp
            $table->string('phone_number', 32)->unique();
            $table->string('session_token', 64)->nullable();

            // Tahap state machine
            // AWAITING_SENDER_NAME | AWAITING_SENDER_PHONE | AWAITING_SENDER_ADDRESS
            // AWAITING_RECEIVER_NAME | AWAITING_RECEIVER_PHONE | AWAITING_RECEIVER_ADDRESS
            // AWAITING_ITEM_DESC | AWAITING_ITEM_VALUE | AWAITING_CONFIRMATION
            $table->string('step', 40)->default('AWAITING_SENDER_NAME');

            // Kurir terpilih
            $table->string('courier_code', 40)->nullable();     // ANTERAJA
            $table->string('courier_service', 60)->nullable();  // FLREG / AnterAja Regular Flat
            $table->integer('shipping_cost')->default(0);
            $table->integer('weight')->default(0);

            // Alamat hasil resolve KiriminAja (teks)
            $table->string('origin_text', 255)->nullable();
            $table->string('destination_text', 255)->nullable();

            // Data pengirim
            $table->string('sender_name', 100)->nullable();
            $table->string('sender_phone', 20)->nullable();
            $table->text('sender_address')->nullable();

            // Data penerima
            $table->string('receiver_name', 100)->nullable();
            $table->string('receiver_phone', 20)->nullable();
            $table->text('receiver_address')->nullable();

            // Detail paket
            $table->string('item_description', 255)->nullable();
            $table->integer('item_value')->default(0);

            // Penyimpanan mentah (quote, pilihan, dsb)
            $table->json('raw_payload')->nullable();

            // Hasil akhir
            $table->string('invoice_number', 60)->nullable();
            $table->string('payment_url', 255)->nullable();

            $table->timestamps();

            $table->index('step');
            $table->index('invoice_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_order_drafts');
    }
};
