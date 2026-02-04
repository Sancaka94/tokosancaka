<?php
namespace App\Exports;

use App\Models\BroadcastHistory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BroadcastHistoryExport implements FromCollection, WithHeadings
{
    protected $data;
    public function __construct($data) { $this->data = $data; }

    public function collection() { return $this->data; }

    public function headings(): array {
        return ['ID', 'Nama', 'Nomor WA', 'Tipe', 'Pesan', 'Status', 'Waktu Kirim'];
    }
}