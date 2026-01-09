<?php

namespace App\Exports;

use App\Models\Coa;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CoaExport implements FromCollection, WithHeadings, WithMapping
{
    protected $tenantId;

    public function __construct()
    {
        // Mengambil ID tenant dari user yang login secara otomatis
        $this->tenantId = Auth::check() && Auth::user()->tenant_id ? Auth::user()->tenant_id : 1;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Coa::where('tenant_id', $this->tenantId)->orderBy('kode')->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Kode',
            'Nama Akun',
            'Tipe',
        ];
    }

    /**
     * @param mixed $coa
     * @return array
     */
    public function map($coa): array
    {
        return [
            $coa->kode,
            $coa->nama,
            ucfirst($coa->tipe),
        ];
    }
}

