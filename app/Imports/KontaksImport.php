<?php
// File: app/Imports/KontaksImport.php

namespace App\Imports;

use App\Models\Kontak;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class KontaksImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Kontak([
            'nama'   => $row['nama'],
            'no_hp'  => $row['no_hp'],
            'alamat' => $row['alamat'],
            'tipe'   => $row['tipe'],
        ]);
    }
}
