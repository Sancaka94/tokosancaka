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
    /**
     * Test redirect pembayaran dengan simulasi URL asli hosting (Subfolder).
     */
    public function test_create_payment_redirects_to_dana_successfully()
    {
        // 1. [CONFIG SESUAI LINK ASLI]
        // Kita set URL aplikasi sama persis dengan link yang Anda kirim.
        // Ini membuat route('dana.pay') otomatis menghasilkan:
        // "https://tokosancaka.com/percetakan/public/dana/pay"
        Config::set('app.url', 'https://tokosancaka.com/percetakan/public');

        // 2. Matikan exception handling biar kalau error kelihatan message aslinya
        $this->withoutExceptionHandling();

        // 3. Setup Mock (Pura-pura jadi server DANA)
        Http::fake([
            // Tangkap semua request ke API Sandbox DANA
            'api.sandbox.dana.id/*' => Http::response([
                'responseCode' => '2000000',
                'responseMessage' => 'Success',
                'webRedirectUrl' => 'https://m.sandbox.dana.id/d/portal/cashier/checkout?bizNo=TestTransaction123',
                'redirectUrl' => 'https://m.sandbox.dana.id/d/portal/cashier/checkout?bizNo=TestTransaction123'
            ], 200),
        ]);

        // 4. Generate URL Target
        $targetUrl = route('dana.pay'); 
        
        // [DEBUG] Tampilkan URL yang sedang di-test di terminal
        echo "\n[INFO] Testing URL: " . $targetUrl . "\n";

        // 5. Eksekusi Request
        $response = $this->get($targetUrl);

        // 6. Cek Hasil
        // Pastikan responnya adalah Redirect (302) ke halaman DANA
        $response->assertStatus(302);
        $response->assertRedirectContains('dana.id');
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