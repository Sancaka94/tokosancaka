<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// DIUBAH KEMBALI: Menggunakan Http facade sesuai permintaan untuk mengambil data dari API eksternal
use Illuminate\Support\Facades\Http;
// DIHAPUS: DB facade tidak lagi digunakan untuk menghindari error database
// use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    // Definisikan base URL API eksternal di satu tempat agar mudah diubah
    private $apiBaseUrl = 'https://www.emsifa.com/api-wilayah-indonesia/api';

    /**
     * DIUBAH: Mengambil data provinsi dari API eksternal.
     */
    public function getProvinces()
    {
        $response = Http::get("{$this->apiBaseUrl}/provinces.json");

        if ($response->successful()) {
            // Jika berhasil, kembalikan data JSON dari API
            return $response->json();
        }

        // Jika gagal, kembalikan response error
        return response()->json(['error' => 'Gagal mengambil data provinsi dari sumber eksternal.'], 502); // 502 Bad Gateway
    }

    /**
     * DIUBAH: Mengambil data kabupaten dari API eksternal berdasarkan ID provinsi.
     *
     * @param string $province_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRegencies($province_id)
    {
        $response = Http::get("{$this->apiBaseUrl}/regencies/{$province_id}.json");

        if ($response->successful()) {
            return $response->json();
        }

        return response()->json(['error' => 'Gagal mengambil data kabupaten dari sumber eksternal.'], 502);
    }

    /**
     * DIUBAH: Mengambil data kecamatan dari API eksternal berdasarkan ID kabupaten.
     *
     * @param string $regency_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDistricts($regency_id)
    {
        $response = Http::get("{$this->apiBaseUrl}/districts/{$regency_id}.json");

        if ($response->successful()) {
            return $response->json();
        }

        return response()->json(['error' => 'Gagal mengambil data kecamatan dari sumber eksternal.'], 502);
    }

    /**
     * DIUBAH: Mengambil data desa dari API eksternal berdasarkan ID kecamatan.
     *
     * @param string $district_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVillages($district_id)
    {
        $response = Http::get("{$this->apiBaseUrl}/villages/{$district_id}.json");

        if ($response->successful()) {
            return $response->json();
        }

        return response()->json(['error' => 'Gagal mengambil data desa dari sumber eksternal.'], 502);
    }
}
