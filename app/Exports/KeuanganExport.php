<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class KeuanganExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithColumnFormatting
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'TANGGAL',
            'KATEGORI',
            'INVOICE / REF',
            'KETERANGAN',
            'OMZET (PEMASUKAN)',
            'MODAL (PENGELUARAN)',
            'PROFIT BERSIH',
        ];
    }

    public function map($row): array
    {
        return [
            $row->tanggal,
            $row->kategori,
            $row->nomor_invoice,
            $row->keterangan,
            $row->omzet,
            $row->modal,
            $row->profit,
        ];
    }

    // Styling Header agar terlihat profesional
    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5']]], // Header Biru
        ];
    }

    // Format Rupiah di Excel
    public function columnFormats(): array
    {
        return [
            'E' => '#,##0',
            'F' => '#,##0',
            'G' => '#,##0',
        ];
    }
}