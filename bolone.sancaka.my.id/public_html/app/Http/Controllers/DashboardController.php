<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\CityTransaction;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class DashboardController extends Controller
{
    // Fungsi pencari koordinat otomatis
    private function geocode(string $address): ?array
    {
        try {
            $searchQuery = $address . ', Indonesia';
            $response = Http::withHeaders(['User-Agent' => 'SancakaCargo/1.0'])
                ->get("https://nominatim.openstreetmap.org/search", [
                    'q' => $searchQuery,
                    'format' => 'json',
                    'limit' => 1
                ])->json();

            return !empty($response[0]) ? [
                'lat' => (float) $response[0]['lat'], 
                'lng' => (float) $response[0]['lon']
            ] : null;
        } catch (Exception $e) {
            Log::error("Geocoding failed for {$address}: " . $e->getMessage());
            return null;
        }
    }

    // ==============================================================
    // FUNGSI HELPER BARU: Memetakan Kota ke Provinsi (Untuk Highmaps)
    // ==============================================================
    // Catatan: Karena database saat ini sepertinya belum punya relasi ke tabel Provinsi,
    // ini adalah fungsi mapping statis sementara. Idealnya, relasikan tabel kota -> provinsi di database.
    private function mapCityToProvince(string $cityName): array
    {
        $cityName = strtolower(trim($cityName));
        
        // Contoh pemetaan dasar. Silakan lengkapi sesuai data master rute logistik Anda.
        $jatim = ['surabaya', 'malang', 'sidoarjo', 'gresik', 'ngawi', 'madiun', 'kediri'];
        $jabar = ['bandung', 'bogor', 'depok', 'bekasi', 'cirebon', 'garut'];
        $jateng = ['semarang', 'solo', 'surakarta', 'magelang', 'tegal', 'klaten'];
        $dki = ['jakarta', 'jakarta pusat', 'jakarta selatan', 'jakarta timur', 'jakarta barat', 'jakarta utara'];
        $banten = ['tangerang', 'tangerang selatan', 'serang', 'cilegon'];

        if (in_array($cityName, $jatim)) {
            return ['hc-key' => 'id-ji', 'name' => 'Jawa Timur'];
        } elseif (in_array($cityName, $jabar)) {
            return ['hc-key' => 'id-jb', 'name' => 'Jawa Barat'];
        } elseif (in_array($cityName, $jateng)) {
            return ['hc-key' => 'id-jt', 'name' => 'Jawa Tengah'];
        } elseif (in_array($cityName, $dki)) {
            return ['hc-key' => 'id-jk', 'name' => 'DKI Jakarta'];
        } elseif (in_array($cityName, $banten)) {
            return ['hc-key' => 'id-bt', 'name' => 'Banten'];
        }

        // Jika kota belum terdaftar di atas
        return ['hc-key' => 'unknown', 'name' => 'Belum Dipetakan'];
    }

    public function index()
    {
        // ==========================================
        // AUTO-FILL KOORDINAT SEBELUM LOAD DASHBOARD
        // ==========================================
        // Cari kota yang latitude atau longitude-nya masih kosong di database
        $citiesLackingCoords = City::whereNull('latitude')->orWhereNull('longitude')->get();
        
        foreach ($citiesLackingCoords as $city) {
            $koordinat = $this->geocode($city->nama_kota);
            if ($koordinat) {
                // Simpan permanen ke database biar load berikutnya ga lelet
                $city->update([
                    'latitude' => $koordinat['lat'],
                    'longitude' => $koordinat['lng']
                ]);
            }
            // WAJIB JEDA 1 DETIK agar API OpenStreetMap tidak memblokir IP server
            sleep(1); 
        }

        // ==========================================
        // 1. DATA MASTER KOTA (Berdasarkan frekuensi)
        // ==========================================
        $chartData = City::selectRaw('nama_kota, latitude, longitude, COUNT(*) as total')
                         ->groupBy('nama_kota', 'latitude', 'longitude')
                         ->orderBy('total', 'desc')
                         ->get();

        $totalData = City::count();

        // ==========================================
        // 2. DATA TRANSAKSI (Berdasarkan jumlah input)
        // ==========================================
        $totalTransaksi = CityTransaction::sum('jumlah');

        $chartDataTransaksi = CityTransaction::selectRaw('cities.nama_kota, cities.latitude, cities.longitude, SUM(city_transactions.jumlah) as total_jumlah')
            ->join('cities', 'city_transactions.city_id', '=', 'cities.id')
            ->groupBy('cities.nama_kota', 'cities.latitude', 'cities.longitude')
            ->orderBy('total_jumlah', 'desc')
            ->get();

        // ==========================================
        // 3. GROUPING DATA UNTUK PETA CHOROPLETH (BARU)
        // ==========================================
        $provinceGroups = [];

        foreach ($chartData as $city) {
            $provinceInfo = $this->mapCityToProvince($city->nama_kota);
            
            // Lewati jika kota tidak diketahui provinsinya (agar peta tidak error)
            if ($provinceInfo['hc-key'] === 'unknown') continue;

            $hcKey = $provinceInfo['hc-key'];

            // Jika provinsi belum ada di array, inisialisasi dulu
            if (!isset($provinceGroups[$hcKey])) {
                $provinceGroups[$hcKey] = [
                    'hc-key' => $hcKey,
                    'name' => $provinceInfo['name'],
                    'value' => 0, // Total value akan mempengaruhi kepekatan warna provinsi
                    'cities' => [] // Array untuk memuat detail kota saat di-hover
                ];
            }

            // Tambahkan data ke provinsi tersebut
            $provinceGroups[$hcKey]['value'] += $city->total;
            $provinceGroups[$hcKey]['cities'][] = [
                'name' => $city->nama_kota,
                'count' => $city->total
            ];
        }

        // Reset Index Array agar format JSON valid saat dikirim ke Blade
        $chartDataMap = array_values($provinceGroups);

        // Tambahkan $chartDataMap ke function compact
        return view('dashboard', compact('chartData', 'totalData', 'totalTransaksi', 'chartDataTransaksi', 'chartDataMap'));
    }

    public function exportPdf()
    {
        $chartData = City::selectRaw('nama_kota, latitude, longitude, COUNT(*) as total')
                         ->groupBy('nama_kota', 'latitude', 'longitude')
                         ->orderBy('total', 'desc')
                         ->get();

        $totalData = City::count();
        $totalTransaksi = CityTransaction::sum('jumlah');

        $chartDataTransaksi = CityTransaction::selectRaw('cities.nama_kota, cities.latitude, cities.longitude, SUM(city_transactions.jumlah) as total_jumlah')
            ->join('cities', 'city_transactions.city_id', '=', 'cities.id')
            ->groupBy('cities.nama_kota', 'cities.latitude', 'cities.longitude')
            ->orderBy('total_jumlah', 'desc')
            ->get();

        $pdf = Pdf::loadView('dashboard-pdf', compact('chartData', 'totalData', 'totalTransaksi', 'chartDataTransaksi'));
        return $pdf->stream('laporan-data-analitik.pdf');
    }

    // Fungsi untuk menerima update koordinat dari Frontend (Blade)
    public function updateCoordinates(Request $request)
    {
        $request->validate([
            'nama_kota' => 'required|string',
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
        ]);

        // Update latitude dan longitude berdasarkan nama kota
        \App\Models\City::where('nama_kota', $request->nama_kota)->update([
            'latitude' => $request->lat,
            'longitude' => $request->lon
        ]);

        return response()->json(['status' => 'success', 'message' => 'Koordinat tersimpan!']);
    }
}