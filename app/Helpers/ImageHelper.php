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

    // ==============================
    // 🔥 PULSA & DATA
    // ==============================
    'tri'               => 'tri',
    'three'             => 'tri',
    '3'                 => 'tri',

    'telkomsel'         => 'telkomsel',
    'tsel'              => 'telkomsel',
    'simpati'           => 'telkomsel',
    'as'                => 'telkomsel',
    'loop'              => 'telkomsel',

    'indosat'           => 'indosat',
    'isat'              => 'indosat',
    'im3'               => 'indosat',
    'mentari'           => 'indosat',
    'oredoo'            => 'indosat',

    'xl'                => 'xl',
    'xis'               => 'xl',

    'axis'              => 'axis',
    
    'smartfren'         => 'smartfren',
    'sf'                => 'smartfren',

    // ==============================
    // 🔥 PLN
    // ==============================
    'pln'               => 'pln',
    'pln token'         => 'pln',
    'token listrik'     => 'pln',
    'listrik'           => 'pln',
    'pln prabayar'      => 'pln',
    'pln pascabayar'    => 'pln',

    // ==============================
    // 🔥 E-WALLET
    // ==============================
    'dana'              => 'dana',
    'ovo'               => 'ovo',
    'gopay'             => 'gopay',
    'go pay'            => 'gopay',
    'gopay driver'      => 'gopay',
    'shopeepay'         => 'shopeepay',
    'spay'              => 'shopeepay',
    'linkaja'           => 'linkaja',
    'link aja'          => 'linkaja',
    'isaku'             => 'isaku',
    'sakuku'            => 'sakuku',

    // ==============================
    // 🔥 VOUCHER & STREAMING
    // ==============================
    'viu'               => 'viu',
    'vidio'             => 'vidio',
    'spotify'           => 'spotify',
    'iqiyi'             => 'iqiyi',
    'netflix'           => 'netflix',
    'garena shell'      => 'garena',
    'garena'            => 'garena',

    // ==============================
    // 🔥 GAME TOPUP
    // ==============================
    'mobile legends'    => 'mlbb',
    'mlbb'              => 'mlbb',
    'ml'                => 'mlbb',

    'free fire'         => 'freefire',
    'ff'                => 'freefire',

    'genshin'           => 'genshin',
    'genshin impact'    => 'genshin',

    'pubg'              => 'pubg',
    'pubg mobile'       => 'pubg',

    'valorant'          => 'valorant',
    'valo'              => 'valorant',

    'call of duty'      => 'cod',
    'codm'              => 'cod',

    'arena of valor'    => 'aov',
    'aov'               => 'aov',

    'higgs domino'      => 'higgs',
    'domino'            => 'higgs',
    'chip domino'       => 'higgs',

    'point blank'       => 'pb',
    'pb'                => 'pb',

    'ragnarok'          => 'ragnarok',

    // ==============================
    // 🔥 TV KABEL & INTERNET
    // ==============================
    'indihome'          => 'indihome',
    'first media'       => 'firstmedia',
    'mnc vision'        => 'mncvision',
    'transvision'       => 'transvision',
    'biznet'            => 'biznet',

    // ==============================
    // 🔥 PDAM & AIR
    // ==============================
    'pdam'              => 'pdam',
    'air'               => 'pdam',

    // ==============================
    // 🔥 BPJS
    // ==============================
    'bpjs'              => 'bpjs kesehatan',
    'bpjs kesehatan'    => 'bpjs',

    // ==============================
    // 🔥 TELKOM
    // ==============================
    'telkom'            => 'telkom',
    'speedy'            => 'telkom',
    'indihome telkom'   => 'telkom',

    // ==============================
    // 🔥 LAINNYA (PPOB UMUM)
    // ==============================
    'pgn'               => 'pgn',
    'gas negara'        => 'pgn',
    'multifinance'      => 'multifinance',
    'leasing'           => 'multifinance',

    'kereta api'        => 'kai',
    'kai'               => 'kai',
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