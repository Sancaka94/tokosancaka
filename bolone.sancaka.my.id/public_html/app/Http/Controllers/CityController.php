<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class CityController extends Controller
{
    /**
     * Fungsi private untuk mencari latitude dan longitude berdasarkan nama kota.
     */
    private function geocode(string $address): ?array
    {
        try {
            // Menambahkan 'Indonesia' agar pencarian lebih akurat
            $searchQuery = $address . ', Indonesia';

            $response = Http::withHeaders([
                'User-Agent' => 'SancakaCargo/1.0' // Ganti dengan nama aplikasi Anda jika perlu
            ])->get("https://nominatim.openstreetmap.org/search", [
                'q' => $searchQuery,
                'format' => 'json',
                'limit' => 1
            ])->json();

            return !empty($response[0]) ? [
                'lat' => (float) $response[0]['lat'], 
                'lng' => (float) $response[0]['lon']
            ] : null;

        } catch (Exception $e) {
            Log::error("Geocoding failed for '{$address}': " . $e->getMessage());
            return null;
        }
    }

    public function index(Request $request)
    {
        // LOG LOG - Inisialisasi Query
        $query = City::query();

        // 1. Filter Pencarian (Nama Kota atau Keterangan)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_kota', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        // 2. Filter Tanggal Mulai
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        // 3. Filter Tanggal Sampai
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // LOG LOG - Paginate 20 Data (di kode sebelumnya tertulis 10, saya biarkan 10 sesuai kode aslinya)
        $cities = $query->orderBy('id', 'desc')->paginate(10);
        
        $cities->appends($request->all());

        return view('cities.index', compact('cities'));
    }

    // --- METODE BARU: create() ---
    // Berfungsi menampilkan form tambah data manual
    public function create()
    {
        return view('cities.create');
    }

    // --- METODE BARU: store() ---
    // Berfungsi menyimpan data manual dari form ke database beserta koordinat otomatis
    public function store(Request $request)
    {
        $request->validate([
            'nama_kota'  => 'required|string|max:255',
            'keterangan' => 'nullable|string'
        ]);

        // Cari koordinat
        $koordinat = $this->geocode($request->nama_kota);

        City::create([
            'nama_kota'  => $request->nama_kota,
            'keterangan' => $request->keterangan,
            'latitude'   => $koordinat ? $koordinat['lat'] : null,
            'longitude'  => $koordinat ? $koordinat['lng'] : null,
        ]);

        return redirect()->route('cities.index')->with('success', 'Data kota berhasil ditambahkan secara manual beserta koordinatnya.');
    }

    public function destroy($id)
    {
        City::findOrFail($id)->delete();
        return redirect()->route('cities.index')->with('success', 'Data berhasil dihapus.');
    }

    public function downloadExample()
    {
        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="contoh_format_kota.csv"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Nama Kota', 'Keterangan']);
            fputcsv($file, ['Jakarta Selatan', 'Area pengiriman VIP']);
            fputcsv($file, ['Surabaya', 'Cabang Jawa Timur']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Berfungsi menyimpan data massal beserta koordinat otomatis
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();

        if (in_array(strtolower($extension), ['csv', 'txt'])) {
            $fileHandle = fopen($file->getPathname(), 'r');
            fgetcsv($fileHandle); 

            while (($row = fgetcsv($fileHandle, 1000, ',')) !== false) {
                if(!empty($row[0]) && !empty($row[1])) {
                    
                    // Cari koordinat untuk setiap baris
                    $koordinat = $this->geocode($row[0]);

                    City::create([
                        'nama_kota'  => $row[0],
                        'keterangan' => $row[1],
                        'latitude'   => $koordinat ? $koordinat['lat'] : null,
                        'longitude'  => $koordinat ? $koordinat['lng'] : null,
                    ]);

                    // WAJIB: Jeda 1 detik agar tidak diblokir oleh OpenStreetMap (Rate Limit)
                    sleep(1);
                }
            }
            fclose($fileHandle);

        } else {
            $dataArray = Excel::toArray([], $file);

            if (!empty($dataArray) && isset($dataArray[0])) {
                $sheet = $dataArray[0]; 
                array_shift($sheet); 

                foreach ($sheet as $row) {
                    if(!empty($row[0]) && !empty($row[1])) {
                        
                        // Cari koordinat untuk setiap baris
                        $koordinat = $this->geocode($row[0]);

                        City::create([
                            'nama_kota'  => $row[0],
                            'keterangan' => $row[1],
                            'latitude'   => $koordinat ? $koordinat['lat'] : null,
                            'longitude'  => $koordinat ? $koordinat['lng'] : null,
                        ]);

                        // WAJIB: Jeda 1 detik agar tidak diblokir oleh OpenStreetMap (Rate Limit)
                        sleep(1);
                    }
                }
            }
        }

        if ($request->ajax()) {
            session()->flash('success', 'File Spreadsheet (CSV/Excel) berhasil diunggah dan diproses beserta koordinatnya!');
            return response()->json(['status' => 'success']);
        }

        return redirect()->route('cities.index')->with('success', 'File Spreadsheet (CSV/Excel) berhasil diunggah dan diproses beserta koordinatnya!');
    }

    // Berfungsi memperbarui data dan mencari ulang koordinat jika nama kota diubah
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_kota'  => 'required|string|max:255',
            'keterangan' => 'nullable|string'
        ]);

        $city = City::findOrFail($id);
        
        // Cek apakah nama kota berubah. Jika ya, cari koordinat baru.
        // Jika tidak berubah, gunakan koordinat yang sudah ada.
        $latitude = $city->latitude;
        $longitude = $city->longitude;

        if ($city->nama_kota !== $request->nama_kota || is_null($latitude)) {
             $koordinat = $this->geocode($request->nama_kota);
             $latitude = $koordinat ? $koordinat['lat'] : $latitude;
             $longitude = $koordinat ? $koordinat['lng'] : $longitude;
        }

        $city->update([
            'nama_kota'  => $request->nama_kota,
            'keterangan' => $request->keterangan,
            'latitude'   => $latitude,
            'longitude'  => $longitude,
        ]);

        return redirect()->route('cities.index')->with('success', 'Data kota dan koordinat berhasil diperbarui.');
    }
}