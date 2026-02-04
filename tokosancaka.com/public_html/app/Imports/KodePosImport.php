<?php

namespace App\Imports;

use App\Models\KodePos;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection; // <-- Menggunakan ToCollection, bukan ToModel
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

// Kita tidak lagi menggunakan WithBatchInserts karena kita menangani penyisipan secara manual
class KodePosImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    /**
     * Metode ini akan dipanggil untuk setiap "chunk" (potongan) data.
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        $dataToInsert = [];
        foreach ($rows as $row) {
            // Kita tidak membuat objek model di sini, hanya array sederhana.
            // Ini jauh lebih ringan untuk memori.
            // Kita juga menambahkan timestamp secara manual.
            $dataToInsert[] = [
                'provinsi'        => $row['province_name'],
                'kota_kabupaten'  => $row['city_name'],
                'kecamatan'       => $row['district_name'],
                'kelurahan_desa'  => $row['subdistrict_name'],
                'kode_pos'        => $row['zip_code'],
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }

        // Hanya jalankan satu query INSERT untuk seluruh potongan data (chunk).
        if (!empty($dataToInsert)) {
            KodePos::insert($dataToInsert);
        }
    }

    /**
     * Tentukan seberapa besar setiap potongan data yang akan diproses.
     */
    public function chunkSize(): int
    {
        return 5000; // Kita tingkatkan menjadi 1000 karena metode ini lebih efisien
    }
}

