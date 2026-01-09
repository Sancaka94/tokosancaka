<?php

namespace App\Jobs;

use App\Models\KodePos;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ImportKodePosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function handle()
    {
        $filePath = storage_path("app/" . $this->path);

        $reader = IOFactory::createReader('Xlsx');
        $chunkSize = 1000;
        $startRow = 2; // baris pertama dianggap header
        $chunkFilter = new ChunkReadFilter();
        $reader->setReadFilter($chunkFilter);

        while (true) {
            $chunkFilter->setRows($startRow, $chunkSize);
            $spreadsheet = $reader->load($filePath);
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

            if (empty($rows)) {
                break;
            }

            foreach ($rows as $row) {
                if (empty($row['A'])) continue; // skip baris kosong

                KodePos::updateOrCreate([
                    'kode_pos' => $row['E'] ?? null,
                ], [
                    'provinsi' => $row['A'] ?? null,
                    'kota_kabupaten' => $row['B'] ?? null,
                    'kecamatan' => $row['C'] ?? null,
                    'kelurahan_desa' => $row['D'] ?? null,
                ]);
            }

            $startRow += $chunkSize;
        }
    }
}

/**
 * Custom filter untuk baca chunk Excel
 */
class ChunkReadFilter implements IReadFilter
{
    private $startRow;
    private $endRow;

    public function setRows($startRow, $chunkSize)
    {
        $this->startRow = $startRow;
        $this->endRow = $startRow + $chunkSize - 1;
    }

    public function readCell($column, $row, $worksheetName = '')
    {
        return $row >= $this->startRow && $row <= $this->endRow;
    }
}
