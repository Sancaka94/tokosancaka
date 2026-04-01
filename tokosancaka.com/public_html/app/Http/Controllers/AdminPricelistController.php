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
            'type' => 'required|string' // Pulsa, Data, Game, dll
        ]);

        try {
            // Mengubah file excel menjadi array (mengambil Sheet pertama saja)
            $data = Excel::toArray([], $request->file('file'));
            $sheet = $data[0];

            $count = 0;

            foreach ($sheet as $key => $row) {
                // Lewati baris yang kosong atau header yang bukan data
                // Kolom B = index 1 (Operator), Kolom C = index 2 (Kode Produk)
                if (empty($row[1]) || empty($row[2]) || $row[1] == 'Operator') {
                    continue;
                }

                // Kolom F = index 5 (Harga). Bersihkan teks "Rp " dan titik "." agar jadi angka murni
                $rawPrice = $row[5] ?? '0';
                $cleanPrice = preg_replace('/[^0-9]/', '', $rawPrice);

                // Insert atau Update ke Database
                IakPricelistPrepaid::updateOrCreate(
                    ['code' => $row[2]], // Update jika kode sudah ada
                    [
                        'operator'    => $row[1],
                        'description' => $row[3], // Nominal
                        'price'       => $cleanPrice,
                        'status'      => $row[6] ?? 'Active',
                        'type'        => $request->type // Dari input form
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
