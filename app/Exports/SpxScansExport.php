<?php

namespace App\Exports;

use App\Models\SpxScan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SpxScansExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return SpxScan::with('kontak')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Resi',
            'ID Kontak',
            'Nama Pengirim',
            'Status',
            'Tanggal Scan',
        ];
    }

    public function map($scan): array
    {
        return [
            $scan->id,
            $scan->resi,
            $scan->kontak_id,
            $scan->kontak->nama ?? 'N/A',
            $scan->status,
            $scan->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
