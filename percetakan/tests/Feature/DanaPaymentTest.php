<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class DanaPaymentTest extends TestCase
{
    /**
     * Test redirect pembayaran.
     * KITA WAJIB SET APP_URL KE LOCALHOST AGAR ROUTER TIDAK BINGUNG DENGAN SUBFOLDER CPANEL.
     */
    public function test_create_payment_redirects_to_dana_successfully()
    {
        // 1. [FIX MUTLAK] Reset URL ke root standar.
        // Ini HANYA berlaku saat testing, tidak mempengaruhi website asli.
        // Tujuannya agar route('dana.pay') menghasilkan '/dana/pay' yang bersih.
        Config::set('app.url', 'http://localhost');

        // 2. Matikan exception handling agar error 500 terlihat jelas (jika ada)
        $this->withoutExceptionHandling();

        // 3. Setup Mock (Pura-pura jadi server DANA)
        Http::fake([
            'api.sandbox.dana.id/*' => Http::response([
                'responseCode' => '2000000',
                'responseMessage' => 'Success',
                'webRedirectUrl' => 'https://m.sandbox.dana.id/d/portal/cashier/checkout?bizNo=TestTransaction123',
                // Kadang DANA mengembalikan key 'redirectUrl', kita siapkan dua-duanya
                'redirectUrl' => 'https://m.sandbox.dana.id/d/portal/cashier/checkout?bizNo=TestTransaction123'
            ], 200),
        ]);

        // 4. Generate URL Target
        // Dengan config localhost di atas, ini akan jadi: http://localhost/dana/pay
        $targetUrl = route('dana.pay'); 
        
        echo "\n[INFO] Testing URL (Internal): " . $targetUrl . "\n";

        // 5. Eksekusi Request
        $response = $this->get($targetUrl);

        // 6. Cek Hasil
        // Pastikan responnya adalah Redirect (302)
        $response->assertStatus(302);
        $response->assertRedirectContains('dana.id');
    }

    /**
     * Test Handle Error DANA
     */
    public function test_create_payment_handles_dana_error()
    {
        // 1. Reset URL lagi untuk test kedua
        Config::set('app.url', 'http://localhost');
        
        // 2. Mock Error response
        Http::fake([
            'api.sandbox.dana.id/*' => Http::response([
                'responseCode' => '400',
                'responseMessage' => 'Bad Request',
            ], 400),
        ]);

        // 3. Akses
        $response = $this->get(route('dana.pay'));

        // 4. Harusnya 200 (Menampilkan JSON Error), bukan 404
        $response->assertStatus(200);
        
        // Cek isi JSON
        $response->assertJson([
            'responseCode' => '400'
        ]);
    }
    
}