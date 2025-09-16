<?php

namespace App\Imports;

use App\Models\Coa;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CoaImport implements ToModel, WithHeadingRow, WithValidation
{
    private $tenantId;

    // Menerima tenantId dari controller
    public function __construct(int $tenantId)
    {
        $this->tenantId = $tenantId;
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Coa([
            'tenant_id' => $this->tenantId,
            'kode'      => $row['kode'],
            'nama'      => $row['nama_akun'],
            'tipe'      => strtolower($row['tipe']),
        ]);
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        // Menggunakan tenantId yang diterima untuk validasi unique
        return [
            'kode' => 'required|string|max:10|unique:coas,kode,NULL,id,tenant_id,' . $this->tenantId,
            'nama_akun' => 'required|string|max:255',
            'tipe' => 'required|in:Aset,Kewajiban,Ekuitas,Pendapatan,Beban,aset,kewajiban,ekuitas,pendapatan,beban',
        ];
    }
}

