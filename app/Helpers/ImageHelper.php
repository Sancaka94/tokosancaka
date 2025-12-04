<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

if (!function_exists('get_operator_logo')) {
    function get_operator_logo($brandName, $productName = null)
    {
        // 1. Bersihkan Input
        $brand   = $brandName ? strtolower(trim($brandName)) : '';
        $product = $productName ? strtolower(trim($productName)) : '';
        $text    = $brand . ' ' . $product;

        // 2. Mapping Nama File (Tanpa Ekstensi)
        // Kiri: Kata Kunci di DB | Kanan: Nama File yang Anda Punya
        $mapping = [
            'pln' => 'pln', 
            'token' => 'pln', 
            'listrik' => 'pln',
            'telkomsel' => 'telkomsel', 
            'simpati' => 'telkomsel', 
            'as' => 'telkomsel', 
            'loop' => 'telkomsel', 
            'omni' => 'telkomsel',
            'indosat' => 'indosat', 
            'im3' => 'indosat', 
            'mentari' => 'indosat',
            'tri' => 'tri', 
            'three' => 'tri',
            'xl' => 'xl', 
            'axis' => 'axis',
            'smartfren' => 'smartfren',
            'dana' => 'dana', 'ovo' => 'ovo', 'gopay' => 'gopay', 'shopeepay' => 'shopeepay',
            'bpjs' => 'bpjs', 'pdam' => 'pdam',
            'game' => 'game', 'voucher' => 'voucher',
        ];

        // 3. Cari Nama File Target
        $filename = null;

        // Cek Mapping Dulu
        foreach ($mapping as $key => $file) {
            if (str_contains($text, $key)) {
                $filename = $file;
                break;
            }
        }

        // Jika tidak ketemu di mapping, gunakan nama brand asli (dibersihkan)
        if (!$filename && $brand) {
            $filename = Str::slug($brand); // Contoh: "Indosat Ooredoo" -> "indosat-ooredoo"
        }

        // Jika masih kosong, coba dari kata pertama nama produk
        if (!$filename && $product) {
            $filename = explode(' ', $product)[0];
        }

        // 4. CEK KEBERADAAN FILE (PENTING!)
        // Kita cek ekstensi .png, .jpg, dan .jpeg
        $extensions = ['png', 'jpg', 'jpeg', 'PNG', 'JPG'];
        
        foreach ($extensions as $ext) {
            $tryFile = $filename . '.' . $ext;

            // CEK LOKASI 1: public/storage/logo-ppob/ (Symlink)
            if (file_exists(public_path('public/storage/logo-ppob/' . $tryFile))) {
                return asset('public/storage/logo-ppob/' . $tryFile);
            }

            // CEK LOKASI 2: public/logo-ppob/ (Folder Biasa)
            if (file_exists(public_path('logo-ppob/' . $tryFile))) {
                return asset('logo-ppob/' . $tryFile);
            }
        }

        // 5. Fallback Terakhir (Default)
        // Pastikan Anda punya file 'default.png' di salah satu folder di atas
        return asset('public/storage/logo-ppob/default.png'); 
    }
}