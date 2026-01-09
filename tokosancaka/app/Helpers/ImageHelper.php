<?php

use Illuminate\Support\Facades\Storage;

if (!function_exists('get_operator_logo')) {
    /**
     * Mengambil URL logo operator berdasarkan nama brand.
     * * @param string $brand (Contoh: TELKOMSEL, INDOSAT)
     * @return string URL gambar
     */
    function get_operator_logo($brand)
    {
        // 1. Bersihkan nama brand (lowercase & hapus spasi)
        // Contoh: "TELKOMSEL" -> "telkomsel"
        $cleanBrand = strtolower(trim($brand));
        
        // 2. Mapping khusus (Jika nama file beda dengan nama brand)
        // Sesuaikan bagian kanan dengan nama file gambar yang Anda upload
        $mapping = [
            'tri'         => 'tri',       // Misal file: tri.png
            'three'       => 'tri',       // Jaga-jaga jika brandnya "Three"
            'pln'         => 'pln',       // Misal file: pln.png
            'pln token'   => 'pln',
            'smartfren'   => 'smartfren',
            'telkomsel'   => 'telkomsel',
            'indosat'     => 'indosat',
            'xl'          => 'xl',
            'axis'        => 'axis',
            'dana'        => 'dana',
            'ovo'         => 'ovo',
            'gopay'       => 'gopay',
            'shopeepay'   => 'shopeepay',
            'bpjs'        => 'bpjs',
            // Tambahkan game jika perlu: 'mobile legends' -> 'mlbb', dll
        ];

        // Ambil nama file dari mapping, jika tidak ada pakai nama asli
        $filename = isset($mapping[$cleanBrand]) ? $mapping[$cleanBrand] : $cleanBrand;

        // 3. Tentukan path gambar
        // Asumsi: File ada di storage/app/public/logo-ppob/
        $path = 'logo-ppob/' . $filename . '.png';

        // 4. Cek apakah file ada di storage
        // Penting: Pastikan sudah 'php artisan storage:link'
        if (Storage::disk('public')->exists($path)) {
            return asset('public/storage/' . $path);
        }

        // 5. Fallback: Jika gambar tidak ditemukan, return URL gambar defaul
        // atau return null agar di Blade bisa di-handle error-nya
        return asset('public/storage/logo-ppob/default.png'); 
    }
}