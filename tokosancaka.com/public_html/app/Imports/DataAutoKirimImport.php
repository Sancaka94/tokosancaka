<?php

namespace App\Imports;

use App\Models\DataAutoKirim;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DataAutoKirimImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // LOG LOG - Izinkan null. Hanya skip jika 'service' benar-benar kosong agar tidak insert baris hantu
        if (empty($row['service'])) {
            return null;
        }

        // Simpan data, jika kosong di excel maka otomatis menjadi null / 0
        return new DataAutoKirim([
            'brand_logistik' => $row['brand_logistik'] ?? null,
            'service'        => $row['service'] ?? null,
            'satuan'         => $row['satuan'] ?? '%',
            'cashback'       => isset($row['cashback']) && $row['cashback'] !== '' ? (float) $row['cashback'] : 0,
            'admin_cod'      => isset($row['admin_cod']) && $row['admin_cod'] !== '' ? (float) $row['admin_cod'] : 0,
            'komisi_agen'    => isset($row['komisi_agen']) && $row['komisi_agen'] !== '' ? (float) $row['komisi_agen'] : null, 
        ]);
    }
}