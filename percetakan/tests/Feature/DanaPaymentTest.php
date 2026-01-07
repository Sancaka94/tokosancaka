<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class DanaPaymentTest extends TestCase
{
    /**
     * Test apakah halaman pembayaran berhasil redirect ke DANA.
     * Menggunakan Mocking HTTP agar tidak menembak API DANA asli saat testing otomatis.
     */
    public function test_create_payment_redirects_to_dana_successfully()
    {
        // 1. Setup Mock Response dari DANA
        // Kita pura-pura menjadi Server DANA yang membalas request Laravel
        Http::fake([
            'api.sandbox.dana.id/*' => Http::response([
                'responseCode' => '2000000', // Kode Sukses
                'responseMessage' => 'Success',
                'webRedirectUrl' => 'https://m.sandbox.dana.id/d/portal/cashier/checkout?bizNo=20230101',
                'redirectUrl' => 'https://m.sandbox.dana.id/d/portal/cashier/checkout?bizNo=20230101'
            ], 200),
        ]);

        // 2. Akses Route Payment kita
        $response = $this->get(route('dana.pay'));

        // 3. Assertions (Pengecekan)
        
        // Cek apakah statusnya Redirect (302)
        $response->assertStatus(302);
        
        // Cek apakah diarahkan ke URL DANA
        $response->assertRedirectContains('m.sandbox.dana.id');
        
        // (Opsional) Cek apakah Log tercatat
        // Log::shouldReceive('info')->with('========== DANA WIDGET PAYMENT START (ENUM FIX) ==========');
    }

    /**
     * Test jika Server DANA Error (Misal 500 atau 400).
     */
    public function test_create_payment_handles_dana_error()
    {
        // 1. Setup Mock Response Error
        Http::fake([
            'api.sandbox.dana.id/*' => Http::response([
                'responseCode' => '400',
                'responseMessage' => 'Bad Request',
            ], 400),
        ]);

        // 2. Akses Route
        $response = $this->get(route('dana.pay'));

        // 3. Harusnya menampilkan JSON Error (sesuai kodingan controller kita)
        $response->assertStatus(200); // Controller kita return JSON response (bukan throw exception)
        $response->assertJson([
            'responseCode' => '400',
            'responseMessage' => 'Bad Request'
        ]);
    }
}