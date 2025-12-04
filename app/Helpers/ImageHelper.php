<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

if (!function_exists('get_operator_logo')) {
    /**
     * Helper untuk mendeteksi logo operator PPOB secara cerdas.
     * Mencakup Produk Prabayar, Pascabayar, Omni, dan Internasional.
     */
    function get_operator_logo($brandName, $productName = null)
    {
        // 1. Normalisasi Input (Lowercase & Bersihkan)
        $inputBrand   = $brandName ? strtolower(trim($brandName)) : '';
        $inputProduct = $productName ? strtolower(trim($productName)) : '';

        // 2. DAFTAR KATA KUNCI (KEYWORD MAPPING)
        // Kiri: Kata kunci yang dicari di nama produk/brand
        // Kanan: Nama file gambar (tanpa .png) yang harus ada di folder storage
        $keywordMapping = [
            // --- OPERATOR SELULER & OMNI ---
            'telkomsel' => 'telkomsel',
            'simpati'   => 'telkomsel',
            'as'        => 'telkomsel',
            'loop'      => 'telkomsel',
            'by.u'      => 'byu',
            'omni'      => 'telkomsel', // Telkomsel Omni
            'indosat'   => 'indosat',
            'im3'       => 'indosat',
            'mentari'   => 'indosat',
            'only4u'    => 'indosat',   // Indosat Only4u
            'tri'       => 'tri',
            'three'     => 'tri',
            'cuanmax'   => 'tri',       // Tri CuanMax
            'xl'        => 'xl',
            'axis'      => 'axis',
            'cuanku'    => 'xl',        // XL/Axis Cuanku (bisa disesuaikan jika ingin icon axis)
            'smartfren' => 'smartfren',
            'esim'      => 'esim',      // eSIM

            // --- LISTRIK & GAS ---
            'pln'       => 'pln',
            'listrik'   => 'pln',
            'token'     => 'pln',
            'nontaglis' => 'pln',       // PLN Nontaglis
            'gas'       => 'pgn',       // Gas Negara
            'pgn'       => 'pgn',

            // --- AIR & PAJAK & NEGARA ---
            'pdam'      => 'pdam',
            'pbb'       => 'pbb',       // Pajak PBB
            'samsat'    => 'samsat',    // SAMSAT

            // --- BPJS ---
            'bpjs'      => 'bpjs',
            'kesehatan' => 'bpjs',
            'ketenagakerjaan' => 'bpjs-tk', // Opsional, atau pakai 'bpjs' saja

            // --- E-WALLET & E-MONEY ---
            'dana'      => 'dana',
            'ovo'       => 'ovo',
            'gopay'     => 'gopay',
            'shopeepay' => 'shopeepay',
            'linkaja'   => 'linkaja',
            'e-money'   => 'emoney',
            'emoney'    => 'emoney',
            'tapcash'   => 'tapcash',
            'brizzi'    => 'brizzi',

            // --- HIBURAN & MEDIA ---
            'games'     => 'game',      // Icon Joystick
            'voucher'   => 'voucher',
            'streaming' => 'streaming', // Icon Play Button
            'tv'        => 'tv',        // TV / TV Pascabayar
            'vision'    => 'tv',
            'indihome'  => 'telkom',
            'media sosial' => 'medsos', // Media Sosial

            // --- KEUANGAN ---
            'multifinance' => 'multifinance', // Cicilan/Leasing

            // --- INTERNASIONAL (Negara) ---
            'china'       => 'flag-china',
            'malaysia'    => 'flag-malaysia',
            'philippines' => 'flag-philippines',
            'singapore'   => 'flag-singapore',
            'thailand'    => 'flag-thailand',
            'vietnam'     => 'flag-vietnam',
            
            // --- UMUM / LAINNYA ---
            'paket sms'   => 'sms-telpon', // Paket SMS & Telpon
            'telpon'      => 'sms-telpon',
            'masa aktif'  => 'masa-aktif', // Masa Aktif
            'bundling'    => 'bundling',   // Bundling
            'perdana'     => 'perdana',    // Aktivasi Perdana
            'aktivasi'    => 'voucher',    // Aktivasi Voucher
            'data'        => 'data',       // Icon Kuota/Data
            'pulsa'       => 'pulsa',      // Icon Pulsa
        ];

        $detectedImage = null;

        // LOGIKA 1: Cek Kolom Brand (Prioritas Utama)
        // Mencocokkan input brand persis dengan key di array mapping
        if (!empty($inputBrand)) {
            foreach ($keywordMapping as $key => $filename) {
                if (str_contains($inputBrand, $key)) {
                    $detectedImage = $filename;
                    break; 
                }
            }
            // Jika brand ada tapi tidak ketemu di mapping, gunakan nama brand itu sendiri sebagai nama file
            if (!$detectedImage) {
                $detectedImage = Str::slug($inputBrand); 
            }
        }

        // LOGIKA 2: Jika Brand kosong/tidak ketemu, Cek Nama Produk
        if (empty($detectedImage) && !empty($inputProduct)) {
            foreach ($keywordMapping as $key => $filename) {
                if (str_contains($inputProduct, $key)) {
                    $detectedImage = $filename;
                    break;
                }
            }
        }

        // 3. Fallback Terakhir (Default)
        $finalFilename = $detectedImage ?? 'default';

        // 4. Cek File di Storage
        // Lokasi: storage/app/public/logo-ppob/
        $path = 'logo-ppob/' . $finalFilename . '.png';

        if (Storage::disk('public')->exists($path)) {
            return asset('public/storage/' . $path);
        }

        // Jika file spesifik tidak ada (misal: flag-china.png belum diupload),
        // kembali ke default.png
        return asset('public/storage/logo-ppob/default.png');
    }
}