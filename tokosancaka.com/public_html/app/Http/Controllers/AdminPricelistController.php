<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log; // LOG LOG
use App\Models\IakPricelistPrepaid;

class AdminPricelistController extends Controller
{
    public function index()
    {
        return view('admin.pricelist_upload');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
            'type' => 'required|string'
        ]);

        try {
            $data = Excel::toArray([], $request->file('file'));
            $sheet = $data[0];

            $count = 0;

            foreach ($sheet as $key => $row) {
                // Lewati baris pertama (header)
                if ($key == 0) {
                    continue;
                }

                // ========================================================
                // LOGIKA UNTUK PASCABAYAR (Berdasarkan format gambar)
                // ========================================================
                if ($request->type === 'pasca') {
                    // Cek jika Product Code (Kolom B / Index 1) kosong, maka lewati
                    if (empty($row[1]) || $row[1] == 'Product Code') {
                        continue;
                    }

                    $code = $row[1];
                    $operator = 'Pascabayar'; // Set default operator untuk Pasca
                    $description = $row[2]; // Product Name (Kolom C)

                    // Bersihkan Fee (Kolom D / Index 3) dari "Rp" dan titik
                    $rawPrice = $row[3] ?? '0';
                    $cleanPrice = preg_replace('/[^0-9]/', '', $rawPrice);
                    if (empty($cleanPrice)) $cleanPrice = 0; // Fallback jika kosong

                    $status = $row[5] ?? 'Active'; // Kolom F

                }
                // ========================================================
                // LOGIKA UNTUK PRABAYAR (Sesuai format lama)
                // ========================================================
                else {
                    // Cek jika Kode (Kolom C / Index 2) kosong, maka lewati
                    if (empty($row[2]) || $row[1] == 'Operator') {
                        continue;
                    }

                    $code = $row[2];
                    $operator = $row[1]; // Operator (Kolom B)

                    // Bersihkan Harga (Kolom F / Index 5)
                    $rawPrice = $row[5] ?? '0';
                    $cleanPrice = preg_replace('/[^0-9]/', '', $rawPrice);
                    if (empty($cleanPrice)) $cleanPrice = 0; // Fallback jika kosong

                    $nominal = $row[3] ?? '';
                    $detail = $row[4] ?? '';
                    $description = ($detail != '' && $detail != '-') ? $detail : $nominal;

                    $status = $row[6] ?? 'Active'; // Kolom G
                }

                // ========================================================
                // INSERT ATAU UPDATE KE DATABASE
                // ========================================================
                IakPricelistPrepaid::updateOrCreate(
                    ['code' => $code], // Patokan update adalah kode produk
                    [
                        'operator'    => $operator,
                        'description' => $description,
                        'price'       => $cleanPrice,
                        'status'      => $status,
                        'type'        => $request->type
                    ]
                );

                $count++;
            }

            Log::info('LOG LOG - Upload Excel Sukses', ['type' => $request->type, 'total_baris' => $count]); // LOG LOG
            return back()->with('success', "$count data pricelist berhasil diupload dan disimpan!");

        } catch (\Exception $e) {
            Log::error('LOG LOG - Error Upload Excel', ['error' => $e->getMessage()]); // LOG LOG
            return back()->with('error', 'Gagal mengolah file Excel: ' . $e->getMessage());
        }
    }
}
