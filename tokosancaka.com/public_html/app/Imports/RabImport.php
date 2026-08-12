<?php

namespace App\Imports;

use App\Models\RabItem;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class RabImport implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading, SkipsEmptyRows
{
    // LOG LOG
    public function model(array $row)
    {
        // Proteksi ekstra: Lewati jika uraian_pekerjaan kosong
        if (!isset($row['uraian_pekerjaan']) || trim($row['uraian_pekerjaan']) === '') {
            return null;
        }

        $volume = isset($row['volume']) ? (float) $row['volume'] : 0;
        $harga = isset($row['harga_satuan']) ? (float) $row['harga_satuan'] : 0;

        return new RabItem([
            'kategori'         => $row['kategori'] ?? null,
            'uraian_pekerjaan' => $row['uraian_pekerjaan'],
            'volume'           => $volume,
            'satuan'           => $row['satuan'] ?? '-',
            'harga_satuan'     => $harga,
            'total'            => $volume * $harga,
        ]);
    }

    // Insert ke database per 100 baris agar tidak membebani memori
    public function batchSize(): int
    {
        return 100;
    }

    // Baca file per 100 baris
    public function chunkSize(): int
    {
        return 100;
    }
}