<?php

namespace App\Exports;

use App\Models\Pesanan;
use Illuminate\Support\Collection; // <-- Perbarui ini
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PesanansExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Pesanan::all();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'No. Resi',
            'Tanggal',
            'Status',
            'Nama Pengirim',
            'No. HP Pengirim',
            'Alamat Pengirim',
            'Nama Penerima',
            'No. HP Penerima',
            'Alamat Penerima',
            'Ekspedisi',
            'Layanan',
            'Metode Pembayaran',
            'Deskripsi Barang',
            'Berat (gram)',
        ];
    }

    /**
     * @var Pesanan $pesanan
     */
    public function map($pesanan): array
    {
        return [
            $pesanan->resi,
            $pesanan->created_at->format('Y-m-d'),
            $pesanan->status,
            $pesanan->sender_name,
            $pesanan->sender_phone,
            $pesanan->sender_address,
            $pesanan->receiver_name,
            $pesanan->receiver_phone,
            $pesanan->receiver_address,
            $pesanan->expedition,
            $pesanan->service_type,
            $pesanan->payment_method,
            $pesanan->item_description,
            $pesanan->weight,
        ];
    }
}
