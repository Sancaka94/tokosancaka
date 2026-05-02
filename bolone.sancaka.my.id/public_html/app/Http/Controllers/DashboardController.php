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

        return view('dashboard', compact('chartData', 'totalData', 'totalTransaksi', 'chartDataTransaksi'));
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
}