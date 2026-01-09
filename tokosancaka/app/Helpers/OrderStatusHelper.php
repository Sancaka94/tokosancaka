<?php

// Pastikan namespace sesuai dengan lokasi folder (app/Helpers)
namespace App\Helpers;

class OrderStatusHelper
{
    /**
     * Mendapatkan teks status yang lebih ramah pengguna.
     *
     * @param string|null $status Status dari database (cth: 'pending', 'paid').
     * @return string Teks status yang akan ditampilkan.
     */
    public static function getStatusText(?string $status): string
    {
        // Jika status null atau kosong
        if (empty($status)) {
            return 'Status Tidak Ada';
        }

        // Mapping dari status database ke teks tampilan
        // !! Sesuaikan 'case' ini dengan nilai status AKTUAL di tabel 'orders' Anda !!
        switch (strtolower($status)) { // Gunakan strtolower untuk konsistensi
            case 'pending': return 'Menunggu Pembayaran';
            case 'paid': return 'Menunggu Pickup'; // Atau 'Dibayar'
            case 'processing': return 'Diproses'; // Atau 'Sedang Dikemas' / 'Menunggu Pickup'
            case 'shipping': return 'Dikirim';
            case 'delivered': return 'Terkirim'; // Atau 'Sampai Tujuan'
            case 'completed': return 'Selesai';
            case 'cancelled': return 'Dibatalkan';
            case 'failed': return 'Gagal';
            case 'rejected': return 'Ditolak';
            // Tambahkan status lain jika ada
            default: return 'Barang Anda (' . ucfirst($status) . ')'; // Tampilkan status asli jika tidak ada di map
        }
    }

    /**
     * Mendapatkan kelas CSS (Tailwind/Bootstrap) untuk badge status.
     *
     * @param string|null $status Status dari database.
     * @return string Kelas CSS untuk badge.
     */
    public static function getStatusBadgeClass(?string $status): string
    {
         // Jika status null atau kosong, gunakan kelas default
         if (empty($status)) {
            return 'bg-gray-100 text-gray-800'; // Default: Abu-abu (Tailwind)
            // return 'bg-secondary'; // Default Bootstrap
        }

        // Mapping dari status database ke kelas CSS Tailwind
        // Sesuaikan kelas ini jika Anda menggunakan Bootstrap
         switch (strtolower($status)) {
            case 'pending': return 'bg-yellow-100 text-yellow-800'; // Kuning
            case 'paid': return 'bg-blue-100 text-blue-800';       // Biru muda
            case 'processing': return 'bg-indigo-100 text-indigo-800'; // Indigo
            case 'shipping': return 'bg-cyan-100 text-cyan-800';     // Cyan
            case 'delivered': return 'bg-green-100 text-green-800';   // Hijau
            case 'completed': return 'bg-emerald-100 text-emerald-800'; // Emerald (Hijau lebih tua)
            case 'cancelled':
            case 'failed':
            case 'rejected': return 'bg-red-100 text-red-800';       // Merah
            default: return 'bg-gray-100 text-gray-800';          // Abu-abu
        }
        // Contoh untuk Bootstrap:
        /*
         switch (strtolower($status)) {
            case 'pending': return 'bg-warning text-dark';
            case 'paid': return 'bg-info text-dark';
            case 'processing': return 'bg-primary';
            case 'shipping':
            case 'delivered':
            case 'completed': return 'bg-success';
            case 'cancelled':
            case 'failed':
            case 'rejected': return 'bg-danger';
            default: return 'bg-secondary';
        }
        */
    }
}