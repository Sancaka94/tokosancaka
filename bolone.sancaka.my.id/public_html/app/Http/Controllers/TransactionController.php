<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\CityTransaction;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class TransactionController extends Controller
{
    // Menampilkan SEMUA riwayat transaksi (Halaman Index)
    public function index(Request $request)
    {
        $query = CityTransaction::with('city'); 

        // 1. Filter Pencarian Nama Kota
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('city', function ($q) use ($search) {
                $q->where('nama_kota', 'like', "%{$search}%");
            });
        }

        // 2. Filter Rentang Tanggal (Berdasarkan tanggal transaksi)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->whereDate('tanggal', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->whereDate('tanggal', '<=', $request->end_date);
        }

        // withQueryString() memastikan saat user klik "Page 2", kata kunci filter tetap terbawa
        $transactions = $query->latest()->paginate(10)->withQueryString();

        return view('transactions.index', compact('transactions'));
    }

    // Menampilkan halaman form input & tabel riwayat (khusus hari ini / filter)
    public function create(Request $request)
    {
        // Mengambil data kota untuk dropdown
        $cities = City::select('id', 'nama_kota')->get()->unique('nama_kota'); 
        
        // Query dasar transaksi
        $query = CityTransaction::with('city')->latest();

        // 1. Filter Pencarian Nama Kota
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('city', function ($q) use ($search) {
                $q->where('nama_kota', 'like', "%{$search}%");
            });
        }

        // 2. Filter Rentang Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->whereDate('tanggal', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->whereDate('tanggal', '<=', $request->end_date);
        } else {
            // Jika tidak ada filter yang aktif, tampilkan hanya data input hari ini
            if (!$request->filled('search')) {
                $query->whereDate('created_at', Carbon::today());
            }
        }

        // Jangan lupa tambahkan withQueryString() agar pagination tidak hilang saat di-filter
        $transactions = $query->paginate(10)->withQueryString();

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

        // Kembali ke halaman input (create) setelah berhasil diedit
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
            fputcsv($file, ['Ngawi', date('Y-m-d'), '150']);
            fputcsv($file, ['Surabaya', date('Y-m-d'), '320']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Fungsi memproses file Upload Excel / CSV
    public function import(Request $request)
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
                    $city = City::where('nama_kota', trim($row[0]))->first();
                    
                    if($city) {
                        CityTransaction::create([
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
            $dataArray = Excel::toArray([], $file);

            if (!empty($dataArray) && isset($dataArray[0])) {
                $sheet = $dataArray[0]; 
                array_shift($sheet); // Buang baris pertama (Header)

                foreach ($sheet as $row) {
                    if(!empty($row[0]) && !empty($row[1]) && !empty($row[2])) {
                        $city = City::where('nama_kota', trim($row[0]))->first();
                        
                        if($city) {
                            // Cek jika format tanggal dari Excel berupa angka serial (Excel Date)
                            $tanggal = is_numeric($row[1]) 
                                ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[1])->format('Y-m-d') 
                                : date('Y-m-d', strtotime($row[1]));

                            CityTransaction::create([
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