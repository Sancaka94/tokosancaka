<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kode_pos', function (Blueprint $table) {
            $table->id();
            $table->string('provinsi')->nullable();
            $table->string('kota_kabupaten')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kelurahan_desa')->nullable();
            $table->string('kode_pos')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kode_pos');
    }
};
