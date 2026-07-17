<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DataAutoKirimTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function headings(): array
    {
        // Header persis seperti yang dibutuhkan untuk Import
        return [
            'brand_logistik',
            'service',
            'satuan',
            'cashback',
            'admin_cod',
            'komisi_agen'
        ];
    }

    public function array(): array
    {
        // Contoh data agar Anda tahu cara isinya
        return [
            ['AnterAja', 'anteraja cod nextday', '%', '5', '3', '1.5'],
            ['JNE Express', 'jne reg', '%', '15', '0', '2.5']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}