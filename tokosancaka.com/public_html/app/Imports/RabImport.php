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
    protected $proyek_id;

    // Menangkap ID Proyek yang dikirim dari Controller
    public function __construct($proyek_id)
    {
        $this->proyek_id = $proyek_id;
    }

    public function model(array $row)
    {
        // Lewati jika uraian pekerjaan kosong
        if (!isset($row['uraian_pekerjaan']) || trim($row['uraian_pekerjaan']) === '') {
            return null;
        }

        $volume = isset($row['volume']) ? (float) $row['volume'] : 0;
        $harga = isset($row['harga_satuan']) ? (float) $row['harga_satuan'] : 0;

        return new RabItem([
            'proyek_id'        => $this->proyek_id, // Masukkan ID otomatis di sini
            'kategori'         => $row['kategori'] ?? null,
            'uraian_pekerjaan' => $row['uraian_pekerjaan'],
            'volume'           => $volume,
            'satuan'           => $row['satuan'] ?? '-',
            'harga_satuan'     => $harga,
            'total'            => $volume * $harga,
        ]);
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }
}