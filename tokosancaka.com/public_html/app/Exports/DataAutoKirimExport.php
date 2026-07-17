<?php

namespace App\Exports;

use App\Models\DataAutoKirim;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DataAutoKirimExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // Mengambil semua data dari database
        return DataAutoKirim::all();
    }

    /**
     * Membuat Heading / Judul Kolom pada baris pertama Excel
     */
    public function headings(): array
    {
        return [
            'ID',
            'Brand Logistik',
            'Service',
            'Satuan',
            'Cashback (%)',
            'Admin COD (%)',
            'Komisi Agen Sancaka (%)',
            'Tanggal Dibuat'
        ];
    }

    /**
     * Memetakan data dari database ke dalam kolom Excel
     */
    public function map($data): array
    {
        return [
            $data->id,
            $data->brand_logistik,
            $data->service,
            $data->satuan,
            $data->cashback,
            $data->admin_cod,
            $data->komisi_agen, // Kolom dinamis agen
            $data->created_at ? $data->created_at->format('Y-m-d H:i') : '-',
        ];
    }

    /**
     * Memberikan styling (misalnya bold) pada baris pertama (Heading)
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true]],
        ];
    }
}