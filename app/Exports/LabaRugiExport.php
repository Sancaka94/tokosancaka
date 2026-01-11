<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LabaRugiExport implements FromView, ShouldAutoSize, WithStyles
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        // Kita gunakan view yang sama dengan yang di web
        // Namun pastikan di view Anda tidak ada tombol "Filter" atau "Cetak" 
        // yang ikut ter-render jika mendeteksi export.
        return view('admin.keuangan.laba_rugi', $this->data);
    }

    // Opsional: Styling Header Excel
    public function styles(Worksheet $sheet)
    {
        return [
            // Bold Baris 1 (Header)
            1 => ['font' => ['bold' => true]],
        ];
    }
}