<?php

namespace App\Exports;

use App\Models\FinancialReport;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class FinancialExport implements FromView, ShouldAutoSize
{
    protected $bulan;
    protected $tahun;

    public function __construct($bulan, $tahun)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
    }

    public function view(): View
    {
        $query = FinancialReport::orderBy('tanggal', 'asc');

        if ($this->bulan) {
            $query->whereMonth('tanggal', $this->bulan);
        }
        if ($this->tahun) {
            $query->whereYear('tanggal', $this->tahun);
        }

        $reports = $query->get();
        $totalPemasukan = $reports->where('jenis', 'pemasukan')->sum('nominal');
        $totalPengeluaran = $reports->where('jenis', 'pengeluaran')->sum('nominal');

        return view('financial_reports.excel', [
            'reports' => $reports,
            'totalPemasukan' => $totalPemasukan,
            'totalPengeluaran' => $totalPengeluaran,
            'saldo' => $totalPemasukan - $totalPengeluaran
        ]);
    }
}
