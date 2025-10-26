<?php

// Pastikan namespace sesuai dengan lokasi folder (app/Helpers)
namespace App\Helpers;

// Import Log facade untuk mencatat error jika ada
use Illuminate\Support\Facades\Log;
// Import Exception global
use Exception;

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
            'logo_url' => null          // URL logo (cth: https://...)
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

            // Mapping dari kode kurir ke nama tampilan dan URL logo
            // Menggunakan URL eksternal dari daftar Anda
            $courierNames = [
                // Kurir dari helper asli
                'jne' => ['name' => 'JNE', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/9/92/New_Logo_JNE.png'],
                'tiki' => ['name' => 'TIKI', 'logo_url' => 'https://kiriminaja.com/assets/home-v4/tiki.png'],
                'posindonesia' => ['name' => 'POS Indonesia', 'logo_url' => 'https://kiriminaja.com/assets/home-v4/pos.png'],
                'sicepat' => ['name' => 'SiCepat', 'logo_url' => 'https://kiriminaja.com/assets/home-v4/sicepat.png'],
                'sap' => ['name' => 'SAP Express', 'logo_url' => 'https://kiriminaja.com/assets/home-v4/sap.png'],
                'ncs' => ['name' => 'NCS Kurir', 'logo_url' => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjxj3iyyZEjK2L4A4yCIr_E-4W3hF2lk_yb-t0Oj2oFPErCPCMHie5LHqps02xMb6sNa-Gqz5NSX_P_hzWlYpUpJUlCD4iN6_QxiSG9fzY4bsZ9XvLFDn7HCiORtNvIlPfuQbSSdW96p7x7uN8ek3FWyHW9c2bznrFBQkoLd5A9sVAFVKWLfUhT3Dxh/s320/GKL41_NCS%20Kurir%20-%20Koleksilogo.com.jpg'],
                'idx' => ['name' => 'ID Express', 'logo_url' => 'https://kiriminaja.com/assets/home-v4/id-express.png'],
                'gojek' => ['name' => 'GoSend', 'logo_url' => 'https://kiriminaja.com/assets/home-v4/gosend.png'],
                'grab' => ['name' => 'GrabExpress', 'logo_url' => 'https://kiriminaja.com/assets/home-v4/grab.svg'],

                // Kurir baru dari daftar $partners Anda
                'jnt' => ['name' => 'J&T Express', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/0/01/J%26T_Express_logo.svg'],
                'indah' => ['name' => 'Indah Cargo', 'logo_url' => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEicOAaLoH2eElQ93_gbkzhvk4dRhWVlk5wQsGgilihIB58321aHchlJLdjyz1ToS25P_nWrHJ_E4QBiW_OVlI7tQt7cZ5I0HZqk6StS7jZltLVvDXp2d5ZDLB9yklhV4x6z2iXyURURDv_unhf-U6vyiD_8to9OC4PBwMwyU_5wAqOiCl6tKiaTA-ri1Q/s851/Logo%20Indah%20Logistik%20Cargo@0.5x.png'],
                'jntcargo' => ['name' => 'J&T Cargo', 'logo_url' => 'https://i.pinimg.com/736x/22/cf/92/22cf92368c1f901d17e38e99061f4849.jpg'],
                'lion' => ['name' => 'Lion Parcel', 'logo_url' => 'https://kiriminaja.com/assets/home-v4/lion.png'],
                'spx' => ['name' => 'SPX Express', 'logo_url' => 'https://images.seeklogo.com/logo-png/49/1/spx-express-indonesia-logo-png_seeklogo-499970.png'],
                'ninja' => ['name' => 'Ninja Express', 'logo_url' => 'https://kiriminaja.com/assets/home-v4/ninja.png'],
                'anteraja' => ['name' => 'Anteraja', 'logo_url' => 'https://kiriminaja.com/assets/home-v4/anter-aja.png'],
                'sentral' => ['name' => 'Sentral Cargo', 'logo_url' => 'https://kiriminaja.com/assets/home-v4/central-cargo.png'],
                'borzo' => ['name' => 'Borzo', 'logo_url' => 'https://kiriminaja.com/assets/home-v4/borzo.png'],
            ];

            // Ambil info kurir dari map, atau gunakan kode sebagai fallback
            $courierInfo = $courierNames[$courierCode] ?? ['name' => strtoupper($courierCode), 'logo_url' => null];
            $courierName = $courierInfo['name'];
            $logoUrl = $courierInfo['logo_url'];

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
                'logo_url' => $logoUrl // URL logo
            ];

        } catch (Exception $e) {
            // Tangani jika terjadi error saat parsing (seharusnya jarang terjadi)
            Log::error("Exception while parsing shipping_method '{$methodString}': " . $e->getMessage());
            return $default; // Kembalikan default jika error
        }
    }
}

