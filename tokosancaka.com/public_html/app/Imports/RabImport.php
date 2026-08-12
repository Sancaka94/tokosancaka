<?php

namespace App\Imports;

use App\Models\RabItem;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RabImport implements ToModel, WithHeadingRow
{
    // LOG LOG
    public function model(array $row)
    {
        $volume = isset($row['volume']) ? (float) $row['volume'] : 0;
        $harga = isset($row['harga_satuan']) ? (float) $row['harga_satuan'] : 0;

        return new RabItem([
            'kategori'         => $row['kategori'] ?? null,
            'uraian_pekerjaan' => $row['uraian_pekerjaan'] ?? 'Tanpa Nama',
            'volume'           => $volume,
            'satuan'           => $row['satuan'] ?? '-',
            'harga_satuan'     => $harga,
            'total'            => $volume * $harga,
        ]);
    }
}