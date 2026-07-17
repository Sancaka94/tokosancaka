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
        // Jika brand_logistik atau service kosong di baris excel, abaikan (skip)
        if (!isset($row['brand_logistik']) || !isset($row['service'])) {
            return null;
        }

        // Simpan / update data (bisa disesuaikan logic-nya apakah ingin update or create)
        return new DataAutoKirim([
            // $row['nama_kolom'] otomatis me-lowercase header Excel dan mengganti spasi menjadi underscore
            // Contoh Header Excel: "Brand Logistik" -> akan dibaca $row['brand_logistik']
            
            'brand_logistik' => $row['brand_logistik'],
            'service'        => $row['service'],
            'satuan'         => $row['satuan'] ?? '%',
            'cashback'       => isset($row['cashback']) ? (float) $row['cashback'] : 0,
            'admin_cod'      => isset($row['admin_cod']) ? (float) $row['admin_cod'] : 0,
            
            // Kolom Dinamis Komisi Agen Sancaka
            // Jika ada di excel akan diambil, jika tidak ada default ke 0
            'komisi_agen'    => isset($row['komisi_agen']) ? (float) $row['komisi_agen'] : 0, 
        ]);
    }
}