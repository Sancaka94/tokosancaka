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
                // Lewati baris pertama (header) atau jika Kode kosong
                if ($key == 0 || empty($row[2]) || $row[1] == 'Operator') {
                    continue;
                }

                // Bersihkan teks "Rp " dan titik "." dari Kolom F (Harga)
                $rawPrice = $row[5] ?? '0';
                $cleanPrice = preg_replace('/[^0-9]/', '', $rawPrice);

                // Logika Deskripsi: Jika Kolom E (Index 4) isinya "-", kita pakai nominal di Kolom D (Index 3) saja.
                // Jika Kolom E ada teks penjelasannya, kita gunakan teks dari Kolom E tersebut.
                $nominal = $row[3] ?? '';
                $detail = $row[4] ?? '';
                $finalDescription = ($detail != '' && $detail != '-') ? $detail : $nominal;

                // Insert atau Update ke Database
                IakPricelistPrepaid::updateOrCreate(
                    ['code' => $row[2]], // Patokan update adalah kode produk
                    [
                        'operator'    => $row[1],
                        'description' => $finalDescription,
                        'price'       => $cleanPrice,
                        'status'      => $row[6] ?? 'Active',
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
