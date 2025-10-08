<?php

namespace App\Imports;

use App\Models\Pelanggan;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class PelangganImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Mencocokkan nama kolom di file Excel (lowercase, tanpa spasi) dengan field di database
        return new Pelanggan([
            'id_pelanggan'   => $row['id_pelanggan'],
            'nama_pelanggan' => $row['nama_pelanggan'],
            'nomor_wa'       => $row['nomor_wa'],
            'alamat'         => $row['alamat'],
            'keterangan'     => $row['keterangan'],
        ]);
    }

    public function rules(): array
    {
        return [
            'id_pelanggan' => 'required|unique:pelanggans,id_pelanggan',
            'nama_pelanggan' => 'required',
            'alamat' => 'required',
        ];
    }
}
