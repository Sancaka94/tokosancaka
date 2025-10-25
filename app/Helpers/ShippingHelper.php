<?php

// Pastikan namespace sesuai dengan lokasi folder (app/Helpers)
namespace App\Helpers;

// Import Log facade untuk mencatat error jika ada
use Illuminate\Support\Facades\Log;

class ShippingHelper
{
    /**
     * Mem-parsing string metode pengiriman dari format KiriminAja/Checkout.
     * Contoh input: "express-sicepat-REG-10000-0" atau "instant-gojek-INSTANT-15000-0"
     *
     * @param string|null $methodString String metode pengiriman.
     * @return array Array berisi detail pengiriman yang sudah diparsing.
     */
    public static function parseShippingMethod(?string $methodString): array
    {
        // Nilai default jika input kosong atau format salah
        $default = [
            'type' => 'N/A',            // Tipe pengiriman (EXPRESS, INSTANT, N/A)
            'courier_code' => '',       // Kode kurir (lowercase, cth: sicepat)
            'courier_name' => 'N/A',    // Nama kurir (ditampilkan, cth: SiCepat)
            'service_name' => '',       // Nama layanan (cth: REG)
            'cost' => 0,                // Biaya ongkir (integer)
            'cost_formatted' => 'Rp0',  // Biaya ongkir terformat (string)
            'insurance_cost' => 0,      // Biaya asuransi (integer)
            'name' => 'N/A',            // Nama gabungan (Kurir - Layanan)
            'logo' => null              // Nama file logo (cth: sicepat.png)
        ];

        // Jika input kosong, kembalikan default
        if (empty($methodString)) {
            return $default;
        }

        try {
            // Pecah string berdasarkan tanda '-'
            $parts = explode('-', $methodString);

            // Perlu minimal 4 bagian: type-courier-service-cost
            if (count($parts) < 4) {
                // Catat warning jika format tidak sesuai
                Log::warning("Format shipping_method tidak lengkap: '{$methodString}'");
                return $default; // Kembalikan default jika format salah
            }

            // Ambil bagian-bagian dari array $parts
            $type = strtolower($parts[0]); // cth: express, instant
            $courierCode = strtolower($parts[1]); // cth: sicepat, jne, gojek
            $service = $parts[2]; // cth: REG, INSTANT, SAMEDAY
            $cost = (int) $parts[3]; // Biaya ongkir
            $insuranceCost = isset($parts[4]) ? (int) $parts[4] : 0; // Biaya asuransi (jika ada)

            // Mapping dari kode kurir ke nama tampilan dan nama file logo
            // Simpan file logo di public/storage/logo-ekspedisi/ (buat folder jika belum ada)
            // Jalankan 'php artisan storage:link' jika belum
            $courierNames = [
                'jne' => ['name' => 'JNE', 'logo' => 'jne.png'],
                'tiki' => ['name' => 'TIKI', 'logo' => 'tiki.png'],
                'posindonesia' => ['name' => 'POS Indonesia', 'logo' => 'pos.png'], // sesuaikan nama file
                'sicepat' => ['name' => 'SiCepat', 'logo' => 'sicepat.png'],
                'sap' => ['name' => 'SAP Express', 'logo' => 'sap.png'],
                'ncs' => ['name' => 'NCS', 'logo' => 'ncs.png'],
                'idx' => ['name' => 'ID Express', 'logo' => 'idexpress.png'], // sesuaikan nama file
                'gojek' => ['name' => 'GoSend', 'logo' => 'gojek.png'], // Instant
                'grab' => ['name' => 'GrabExpress', 'logo' => 'grab.png'], // Instant
                // Tambahkan kurir lain sesuai kebutuhan
            ];

            // Ambil info kurir dari map, atau gunakan kode sebagai fallback
            $courierInfo = $courierNames[$courierCode] ?? ['name' => strtoupper($courierCode), 'logo' => null];
            $courierName = $courierInfo['name'];
            $logoFile = $courierInfo['logo'];

            // Format nama layanan agar lebih rapi
            $serviceName = $service ?: ($type == 'instant' ? 'INSTANT' : 'REGULAR'); // Beri nama default jika kosong

            // Kembalikan array hasil parsing
            return [
                'type' => strtoupper($type), // EXPRESS / INSTANT
                'courier_code' => $courierCode,
                'courier_name' => $courierName,
                'service_name' => $serviceName,
                'cost' => $cost,
                'cost_formatted' => 'Rp' . number_format($cost, 0, ',', '.'),
                'insurance_cost' => $insuranceCost,
                'name' => $courierName . ' - ' . $serviceName, // Nama gabungan
                'logo' => $logoFile // Nama file logo
            ];

        } catch (Exception $e) {
            // Tangani jika terjadi error saat parsing (seharusnya jarang terjadi)
            Log::error("Exception while parsing shipping_method '{$methodString}': " . $e->getMessage());
            return $default; // Kembalikan default jika error
        }
    }
}
