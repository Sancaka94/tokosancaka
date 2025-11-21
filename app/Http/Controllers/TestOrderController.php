<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DokuJokulService; // PANGGIL SERVICE JOKUL
use App\Models\Pesanan;             // <-- PENTING: Import model Pesanan
use Illuminate\Support\Facades\Log;

/**
 * Controller Tes BARU untuk DOKU JOKUL (Checkout API)
 * VERSI FINAL: Sekarang membuat pesanan di database
 */
class TestOrderController extends Controller
{
    /**
     * UJI COBA JOKUL CHECKOUT (API MANUAL)
     * Target: /test/doku/simple
     */
    public function testSimplePayment()
    {
        Log::info('--- MEMULAI TEST JOKUL (Checkout API) [Flow Lengkap] ---');

        $invoiceNumber = 'SCK-' . time();
        $amount = 15000; // Harga tes

        try {
            // ==========================================================
            // LANGKAH 1: Buat Pesanan "Menunggu Pembayaran" di Database
            // ==========================================================
            Log::info('Membuat entri Pesanan dummy di database...', ['invoice' => $invoiceNumber]);
            
            // Kita buat pesanan dummy dengan data minimal yang CUKUP
            // agar 'handleDokuCallback' dan '_createKiriminAjaOrder' tidak error.
            $pesanan = Pesanan::create([
                'nomor_invoice' => $invoiceNumber,
                'status' => 'Menunggu Pembayaran',
                'status_pesanan' => 'Menunggu Pembayaran',
                'tanggal_pesanan' => now(),
                'price' => $amount,
                'total_harga_barang' => 1000,
                'shipping_cost' => $amount,
                'insurance_cost' => 0,
                'cod_fee' => 0,
                'payment_method' => 'DOKU_JOKUL',
                
                // Format string ekspedisi (regular-kurir-servis-harga...)
                'expedition' => 'regular-sicepat-REG-15000-0-0',
                'service_type' => 'regular',
                
                // ==========================================================
                // PERBAIKAN: Menggunakan data alamat PENGIRIM yang valid
                // (Karang Tengah, Ngawi)
                // ==========================================================
                'sender_name' => 'Toko Sancaka (TES)',
                'sender_phone' => '08123456789',
                'sender_address' => 'Jl. Karang Tengah, Ngawi', // Alamat teks
                'sender_province' => 'Jawa Timur',
                'sender_regency' => 'Ngawi',
                'sender_district' => 'Ngawi',
                'sender_village' => 'Karang Tengah',
                'sender_postal_code' => '63218',
                'sender_district_id' => 4354, // <-- DATA BARU (Benar)
                'sender_subdistrict_id' => 40338, // <-- DATA BARU (Benar)
                
                // ==========================================================
                // PERBAIKAN: Menggunakan data alamat PENERIMA yang valid
                // (Balas Klumprik, Wiyung, Surabaya)
                // ==========================================================
                'receiver_name' => 'Pelanggan Tes DOKU',
                'receiver_phone' => '08987654321',
                'receiver_address' => 'Jl. Balas Klumprik, Wiyung', // Alamat teks
                'receiver_province' => 'Jawa Timur',
                'receiver_regency' => 'Surabaya',
                'receiver_district' => 'Wiyung',
                'receiver_village' => 'Balas Klumprik',
                'receiver_postal_code' => '60222',
                'receiver_district_id' => 6159, // <-- DATA BARU (Benar)
                'receiver_subdistrict_id' => 69352, // <-- DATA BARU (Benar)

                'item_description' => 'Barang Tes DOKU',
                'item_price' => 1000,
                'weight' => 1000,
                'item_type' => 2, // '2' adalah 'Umum' (asumsi)
                'ansuransi' => 'tidak',
                'length' => 1, 'width' => 1, 'height' => 1,
            ]);
            // ==========================================================

            // 2. Buat instance Service baru
            $dokuService = new DokuJokulService();
            
            // 3. Panggil method createPayment
            $paymentUrl = $dokuService->createPayment($invoiceNumber, $amount);

            if ($paymentUrl) {
                // --- SUKSES ---
                Log::info('Berhasil mendapatkan URL DOKU (Jokul): ' . $paymentUrl);
                // Redirect langsung ke halaman pembayaran
                return redirect()->away($paymentUrl);
            } else {
                // --- GAGAL ---
                $errorMessage = 'DokuJokulService::createPayment() mengembalikan null. Cek log "DOKU Jokul: Create Payment Gagal"';
                Log::error($errorMessage);
                return $this->showTestResult('Gagal (Jokul Payment)', null, null, $errorMessage);
            }

        } catch (\Exception $e) {
            // --- GAGAL (EXCEPTION) ---
            $errorMessage = $e->getMessage();
            Log::error('EXCEPTION di TestOrderController::testSimplePayment: ' . $errorMessage);
            return $this->showTestResult('Gagal Fatal (Exception)', null, null, $errorMessage);
        }
    }

    /**
     * Helper method untuk menampilkan halaman hasil tes
     */
    private function showTestResult(string $status, ?string $url, ?int $amount, string $errorMessage = '')
    {
        $isSuccess = !empty($url);
        if ($isSuccess) {
            return response("<h1>Sukses</h1><p>Silakan redirect ke: <a href='$url'>$url</a></p>");
        } else {
            return response("<h1>Gagal</h1><p>Error: $errorMessage</p><p>Cek log `storage/logs/laravel.log` untuk detail 'DOKU Jokul'.</p>");
        }
    }
}