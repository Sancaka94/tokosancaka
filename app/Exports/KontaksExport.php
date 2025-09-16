<?php
// File: app/Exports/KontaksExport.php

namespace App\Exports;

use App\Models\Kontak;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class KontaksExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Kontak::select('nama', 'no_hp', 'alamat', 'tipe')->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Nama',
            'No. HP',
            'Alamat',
            'Tipe',
        ];
    }
}