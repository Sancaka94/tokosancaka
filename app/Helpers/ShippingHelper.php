<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Exception;

class ShippingHelper
{
    /**
     * Parsing string metode pengiriman (Support Format API & Manual).
     * * @param string|null $methodString
     * @return array
     */
    public static function parseShippingMethod(?string $methodString): array
    {
        // 1. Data Default
        $default = [
            'type'           => 'REGULAR',
            'courier_code'   => '',
            'courier_name'   => 'N/A',
            'service_name'   => 'Regular',
            'cost'           => 0,
            'cost_formatted' => 'Rp0',
            'insurance_cost' => 0,
            'name'           => 'N/A',
            'logo_url'       => null // Nanti diisi otomatis
        ];

        if (empty($methodString)) {
            return $default;
        }

        try {
            // --- DATABASE LOGO & NAMA RESMI (Updated) ---
            $courierMap = [
                'jne'           => ['name' => 'JNE', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jne.png'],
                'tiki'          => ['name' => 'TIKI', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/tiki.png'],
                'pos'           => ['name' => 'POS Indonesia', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/posindonesia.png'],
                'posindonesia'  => ['name' => 'POS Indonesia', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/posindonesia.png'],
                'sicepat'       => ['name' => 'SiCepat', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/sicepat.png'],
                'sap'           => ['name' => 'SAP Express', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/sap.png'],
                'ncs'           => ['name' => 'NCS Kurir', 'url' => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjxj3iyyZEjK2L4A4yCIr_E-4W3hF2lk_yb-t0Oj2oFPErCPCMHie5LHqps02xMb6sNa-Gqz5NSX_P_hzWlYpUpJUlCD4iN6_QxiSG9fzY4bsZ9XvLFDn7HCiORtNvIlPfuQbSSdW96p7x7uN8ek3FWyHW9c2bznrFBQkoLd5A9sVAFVKWLfUhT3Dxh/s320/GKL41_NCS%20Kurir%20-%20Koleksilogo.com.jpg'],
                'idx'           => ['name' => 'ID Express', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/idx.png'],
                'idexpress'     => ['name' => 'ID Express', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/idx.png'],
                'gojek'         => ['name' => 'GoSend', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/gosend.png'],
                'gosend'        => ['name' => 'GoSend', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/gosend.png'],
                'grab'          => ['name' => 'GrabExpress', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/grab.png'],
                'jnt'           => ['name' => 'J&T Express', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jnt.png'],
                'j&t'           => ['name' => 'J&T Express', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jnt.png'],
                'indah'         => ['name' => 'Indah Cargo', 'url' => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEicOAaLoH2eElQ93_gbkzhvk4dRhWVlk5wQsGgilihIB58321aHchlJLdjyz1ToS25P_nWrHJ_E4QBiW_OVlI7tQt7cZ5I0HZqk6StS7jZltLVvDXp2d5ZDLB9yklhV4x6z2iXyURURDv_unhf-U6vyiD_8to9OC4PBwMwyU_5wAqOiCl6tKiaTA-ri1Q/s851/Logo%20Indah%20Logistik%20Cargo@0.5x.png'],
                'jtcargo'       => ['name' => 'J&T Cargo', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jtcargo.png'],
                'lion'          => ['name' => 'Lion Parcel', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/lion.png'],
                'spx'           => ['name' => 'SPX Express', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/spx.png'],
                'shopee'        => ['name' => 'SPX Express', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/spx.png'],
                'ninja'         => ['name' => 'Ninja Express', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/ninja.png'],
                'anteraja'      => ['name' => 'Anteraja', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/anteraja.png'],
                'sentral'       => ['name' => 'Sentral Cargo', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/centralcargo.png'],
                'borzo'         => ['name' => 'Borzo', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/borzo.png'],
                'paxel'         => ['name' => 'Paxel', 'url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/paxel.jpg'],
            ];

            // Variabel Penampung Hasil
            $type = 'REGULAR';
            $courierCode = '';
            $courierRawName = '';
            $serviceName = 'Regular';
            $cost = 0;
            $insuranceCost = 0;

            // 2. LOGIKA DETEKSI FORMAT
            $parts = explode('-', $methodString);
            $count = count($parts);

            // SKENARIO A: Format Lengkap API (expedition-sicepat-REG-10000-0)
            // Ciri: minimal 4 bagian dipisah tanda strip '-'
            if ($count >= 4 && is_numeric($parts[$count - 1])) {
                $type           = strtoupper($parts[0]);      // express/instant
                $courierRawName = $parts[1];                  // sicepat
                $serviceName    = strtoupper($parts[2]);      // REG
                $cost           = (int) $parts[3];            // 10000
                $insuranceCost  = isset($parts[4]) ? (int) $parts[4] : 0;
            }
            // SKENARIO B: Format Manual / Sederhana (Contoh: "TIKI", "JNE REG", "J&T Cargo")
            else {
                // Bersihkan input
                $raw = trim($methodString);

                // Cek pemisah " - " (Spasi Strip Spasi)
                if (str_contains($raw, ' - ')) {
                    $subParts = explode(' - ', $raw, 2);
                    $courierRawName = trim($subParts[0]);
                    $serviceName    = isset($subParts[1]) ? strtoupper(trim($subParts[1])) : 'Regular';
                }
                // Cek pemisah Spasi Biasa (untuk kasus "JNE REG")
                elseif (str_contains($raw, ' ')) {
                    $spaceParts = explode(' ', $raw);
                    $lastWord   = end($spaceParts);

                    // Jika kata terakhir pendek (<= 4 huruf), anggap Service (REG, OKE, YES, EZ)
                    if (strlen($lastWord) <= 4 && count($spaceParts) > 1) {
                        $serviceName = strtoupper($lastWord);
                        array_pop($spaceParts); // Hapus service dari nama
                        $courierRawName = implode(' ', $spaceParts);
                    } else {
                        // Kalau panjang (misal "J&T Cargo"), anggap satu nama kurir
                        $courierRawName = $raw;
                        $serviceName = 'Regular';
                    }
                }
                // Jika cuma 1 kata (misal "TIKI")
                else {
                    $courierRawName = $raw;
                    $serviceName = 'Regular';
                }
            }

            // 3. NORMALISASI & SMART MATCHING LOGO
            // Ubah jadi lowercase & hapus spasi untuk pencocokan (sicepat, j&texpress)
            $normalized = strtolower(str_replace(' ', '', $courierRawName));

            $finalCourierName = ucwords($courierRawName); // Default nama (kapital di awal kata)
            $finalLogoUrl     = null;

            // Deteksi Khusus: J&T Cargo vs J&T Express
            if (str_contains($normalized, 'cargo') && (str_contains($normalized, 'j&t') || str_contains($normalized, 'jt'))) {
                $finalCourierName = $courierMap['jtcargo']['name'];
                $finalLogoUrl     = $courierMap['jtcargo']['url'];
                $courierCode      = 'jtcargo';
            } else {
                // Loop standard
                foreach ($courierMap as $key => $data) {
                    if (str_contains($normalized, $key)) {
                        $finalCourierName = $data['name'];
                        $finalLogoUrl     = $data['url'];
                        $courierCode      = $key;
                        break;
                    }
                }
            }

            // Fallback jika logo tidak ketemu di map (pakai asset lokal)
            if (!$finalLogoUrl) {
                $finalLogoUrl = asset('public/storage/logo-ekspedisi/' . $normalized . '.png');
            }

            // 4. Return Hasil Akhir
            return [
                'type'           => $type,
                'courier_code'   => $courierCode ?: $normalized,
                'courier_name'   => $finalCourierName,
                'service_name'   => $serviceName,
                'cost'           => $cost,
                'cost_formatted' => 'Rp ' . number_format($cost, 0, ',', '.'),
                'insurance_cost' => $insuranceCost,
                'name'           => $finalCourierName . ' - ' . $serviceName,
                'logo_url'       => $finalLogoUrl
            ];

        } catch (Exception $e) {
            Log::error("Helper Error parsing '{$methodString}': " . $e->getMessage());
            return $default;
        }
    }
}
