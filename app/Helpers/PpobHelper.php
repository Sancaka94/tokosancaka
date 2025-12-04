<?php

if (!function_exists('get_ppob_message')) {
    /**
     * Menerjemahkan Response Code (RC) Digiflazz ke pesan user-friendly.
     *
     * @param string $rc Kode respon (00, 01, 44, dll)
     * @return string Pesan untuk user
     */
    function get_ppob_message($rc)
    {
        return match ((string)$rc) {
            '00' => 'Transaksi Berhasil.',
            '03', '99' => 'Transaksi sedang diproses. Mohon tunggu sebentar.',
            '40', '41', '42', '45' => 'Gagal: Terjadi kesalahan konfigurasi sistem.',
            '44' => 'Gagal memproses (Masalah saldo server sedang gangguan).',
            '50', '54', '57' => 'Nomor tujuan salah atau tidak ditemukan.',
            '51', '52', '59' => 'Gagal: Nomor diblokir atau salah operator/wilayah.',
            '60' => 'Tagihan sudah terbayar atau belum tersedia.',
            '53', '55', '62', '68', '71' => 'Maaf, produk sedang gangguan atau stok habis.',
            '72' => 'Gagal: Mohon unreg paket lama terlebih dahulu.',
            '73' => 'Gagal: Limit KWH listrik tercapai.',
            '83', '85', '86' => 'Terlalu banyak permintaan, silakan coba 5 menit lagi.',
            '84' => 'Nominal pembayaran tidak sesuai.',
            default => 'Transaksi Gagal. Silakan coba lagi nanti. (Kode: ' . $rc . ')',
        };
    }
}