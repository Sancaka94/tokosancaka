<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\CityTransaction;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class TransactionController extends Controller
{
    // Menampilkan halaman form input & tabel riwayat hari ini
    public function create()
    {
        // Mengambil data kota untuk dropdown
        $cities = City::select('id', 'nama_kota')->get()->unique('nama_kota'); 
        
        // Mengambil riwayat transaksi khusus HARI INI
        $transactions = CityTransaction::with('city')
                        ->whereDate('created_at', Carbon::today()) // Asumsi menggunakan created_at untuk waktu input
                        ->latest()
                        ->paginate(10);

        return view('transactions.create', compact('cities', 'transactions'));
    }

    // Menyimpan data manual ke database
    public function store(Request $request)
    {
        $request->validate([
            'city_id' => 'required|exists:cities,id',
            'jumlah'  => 'required|integer|min:1',
            'tanggal' => 'required|date',
        ]);

        CityTransaction::create([
            'city_id' => $request->city_id,
            'jumlah'  => $request->jumlah,
            'tanggal' => $request->tanggal,
        ]);

        return redirect()->back()->with('success', 'Data transaksi berhasil disimpan!');
    }

    // Menampilkan form edit untuk satu transaksi
    public function edit($id)
    {
        $transaction = CityTransaction::findOrFail($id);
        $cities = City::select('id', 'nama_kota')->get()->unique('nama_kota');
        
        return view('transactions.edit', compact('transaction', 'cities'));
    }

    // Menyimpan pembaruan data transaksi
    public function update(Request $request, $id)
    {
        $request->validate([
            'city_id' => 'required|exists:cities,id',
            'jumlah'  => 'required|integer|min:1',
            'tanggal' => 'required|date',
        ]);

        $transaction = CityTransaction::findOrFail($id);
        $transaction->update([
            'city_id' => $request->city_id,
            'jumlah'  => $request->jumlah,
            'tanggal' => $request->tanggal,
        ]);

        // Karena kita mengedit dari halaman riwayat (create), kita kembali ke sana
        return redirect()->route('transactions.create')->with('success', 'Data transaksi berhasil diperbarui!');
    }

    // Menghapus satu data transaksi
    public function destroy($id)
    {
        $transaction = CityTransaction::findOrFail($id);
        $transaction->delete();

        return redirect()->back()->with('success', 'Data transaksi berhasil dihapus!');
    }

    // Menghapus banyak data sekaligus (Bulk Delete)
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:city_transactions,id',
        ]);

        CityTransaction::whereIn('id', $request->ids)->delete();

        return redirect()->back()->with('success', count($request->ids) . ' data riwayat berhasil dihapus!');
    }

    // Fungsi mendownload contoh format Excel/CSV Transaksi
    public function downloadExample()
    {
        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="format_upload_transaksi.csv"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            // Header Kolom di Excel
            fputcsv($file, ['Nama Kota', 'Tanggal', 'Jumlah']);
            // Contoh isi data
            fputcsv($file, ['Ngawi', '2026-05-02', '150']);
            fputcsv($file, ['Surabaya', '2026-05-02', '320']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Fungsi memproses file Upload Excel / CSV
    public function import(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        $berhasil = 0;
        $gagal = 0;

        if (in_array($extension, ['csv', 'txt'])) {
            $fileHandle = fopen($file->getPathname(), 'r');
            fgetcsv($fileHandle); // Lewati baris pertama (Header)

            while (($row = fgetcsv($fileHandle, 1000, ',')) !== false) {
                if(!empty($row[0]) && !empty($row[1]) && !empty($row[2])) {
                    // Cari ID kota berdasarkan nama yang diketik di Excel
                    $city = \App\Models\City::where('nama_kota', trim($row[0]))->first();
                    
                    if($city) {
                        \App\Models\CityTransaction::create([
                            'city_id' => $city->id,
                            'tanggal' => date('Y-m-d', strtotime($row[1])),
                            'jumlah'  => (int)$row[2]
                        ]);
                        $berhasil++;
                    } else {
                        $gagal++; // Jika nama kota tidak terdaftar di database Master
                    }
                }
            }
            fclose($fileHandle);
        } else {
            // Jika format .xlsx (Menggunakan library Excel)
            $dataArray = \Maatwebsite\Excel\Facades\Excel::toArray([], $file);

            if (!empty($dataArray) && isset($dataArray[0])) {
                $sheet = $dataArray[0]; 
                array_shift($sheet); // Buang baris pertama (Header)

                foreach ($sheet as $row) {
                    if(!empty($row[0]) && !empty($row[1]) && !empty($row[2])) {
                        $city = \App\Models\City::where('nama_kota', trim($row[0]))->first();
                        
                        if($city) {
                            // Cek jika format tanggal dari Excel berupa angka serial (Excel Date)
                            $tanggal = is_numeric($row[1]) 
                                ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[1])->format('Y-m-d') 
                                : date('Y-m-d', strtotime($row[1]));

                            \App\Models\CityTransaction::create([
                                'city_id' => $city->id,
                                'tanggal' => $tanggal,
                                'jumlah'  => (int)$row[2]
                            ]);
                            $berhasil++;
                        } else {
                            $gagal++;
                        }
                    }
                }
            }
        }

        $pesan = "Berhasil mengimpor $berhasil data input.";
        if ($gagal > 0) {
            $pesan .= " Namun ada $gagal data yang gagal masuk karena Nama Kota tidak ditemukan di database.";
            return redirect()->back()->with('error', $pesan);
        }

        return redirect()->back()->with('success', $pesan);
    }

}