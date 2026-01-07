<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class DanaPaymentTest extends TestCase
{
    /**
     * Setup: Dijalankan otomatis sebelum SETIAP test.
     * Kita paksa aplikasi 'lupa' kalau dia ada di subfolder cPanel.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. Paksa URL Root ke Localhost. 
        // Ini agar Router Laravel hanya melihat '/dana/pay', bukan '/percetakan/public/dana/pay'
        Config::set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');
    }

    public function test_create_payment_redirects_to_dana_successfully()
    {
        // 2. Setup Mock (Pura-pura jadi server DANA)
        Http::fake([
            'api.sandbox.dana.id/*' => Http::response([
                'responseCode' => '2000000',
                'responseMessage' => 'Success',
                'webRedirectUrl' => 'https://m.sandbox.dana.id/d/portal/cashier/checkout?bizNo=Test123',
                'redirectUrl' => 'https://m.sandbox.dana.id/d/portal/cashier/checkout?bizNo=Test123'
            ], 200),
        ]);

        // 3. Tentukan URL Target
        // Karena config sudah di-reset di setUp(), route() akan menghasilkan 'http://localhost/dana/pay'
        // Ini SANGAT AMAN untuk testing internal.
        $targetUrl = route('dana.pay'); 
        
        echo "\n[INFO] Internal Test URL: " . $targetUrl . "\n";

        // 4. Eksekusi Request
        $response = $this->get($targetUrl);

        // Debug jika masih error
        if ($response->status() !== 302) {
             echo "\n[ERROR HTML] " . substr($response->getContent(), 0, 200) . "...\n";
        }

        // 5. Cek Hasil Redirect
        $response->assertStatus(302);
        $response->assertRedirectContains('dana.id');
    }

    public function test_create_payment_handles_dana_error()
    {
        // Mock Error
        Http::fake([
            'api.sandbox.dana.id/*' => Http::response([
                'responseCode' => '500', 
                'responseMessage' => 'System Error'
            ], 500),
        ]);

        // Akses Route
        $response = $this->get(route('dana.pay'));

        // Harusnya 200 OK (Return JSON Error), bukan 404/500 Crash
        $response->assertStatus(200);
        $response->assertJson(['responseCode' => '500']);
    }
}