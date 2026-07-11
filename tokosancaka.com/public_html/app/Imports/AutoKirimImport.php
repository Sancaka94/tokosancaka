<?php

namespace App\Imports;

use App\Models\AutoKirim;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AutoKirimImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Abaikan baris jika kolom kuncinya kosong
        if (!isset($row['zip']) && !isset($row['district_id'])) {
            return null;
        }

        return new AutoKirim([
            'zip'           => $row['zip'] ?? null,
            'district_id'   => $row['district_id'] ?? null,
            'district_name' => $row['district_name'] ?? null,
            'regency_name'  => $row['regency_name'] ?? null,
            'province_name' => $row['province_name'] ?? null,
        ]);
    }
}