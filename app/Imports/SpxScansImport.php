<?php

namespace App\Imports;

use App\Models\SpxScan;
use App\Models\Kontak;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class SpxScansImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        // Cari kontak berdasarkan ID, jika tidak ada, skip baris ini.
        $kontak = Kontak::find($row['id_kontak']);
        if (!$kontak) {
            return null;
        }

        return new SpxScan([
            'resi'      => $row['resi'],
            'kontak_id' => $kontak->id,
            'status'    => $row['status'] ?? 'Proses Pickup',
        ]);
    }

    public function rules(): array
    {
        return [
            '*.resi' => 'required|string|unique:spx_scans,resi',
            '*.id_kontak' => 'required|integer|exists:kontaks,id',
        ];
    }
}
