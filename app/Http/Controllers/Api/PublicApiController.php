<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\KodePos;
use App\Models\Province;
use App\Models\Regency;
use Illuminate\Http\Request;

class PublicApiController extends Controller
{
    /**
     * Mengambil semua data provinsi.
     */
    public function getProvinces()
    {
        $provinces = Province::orderBy('name')->get(['id', 'name']);
        return response()->json($provinces);
    }

    /**
     * Mengambil data kabupaten berdasarkan ID provinsi.
     */
    public function getKabupaten(Province $province)
    {
        return response()->json(
            $province->regencies()->orderBy('name')->get(['id', 'name'])
        );
    }

    /**
     * Mengambil data kecamatan berdasarkan ID kabupaten.
     */
    public function getKecamatan(Regency $regency)
    {
        return response()->json(
            $regency->districts()->orderBy('name')->get(['id', 'name'])
        );
    }

    /**
     * Mengambil data desa/kode pos berdasarkan ID kecamatan.
     */
    public function getDesaByDistrict(District $district)
    {
        $regencyName = $district->regency->name;
        // Membersihkan nama kabupaten untuk pencocokan yang lebih baik
        $cleanRegencyName = trim(str_ireplace(['KABUPATEN', 'KOTA', 'KAB.'], '', $regencyName));

        $results = KodePos::query()
            ->whereRaw('UPPER(kecamatan) = UPPER(?)', [$district->name])
            ->whereRaw('UPPER(kota_kabupaten) LIKE UPPER(?)', ['%' . $cleanRegencyName . '%'])
            ->orderBy('kelurahan_desa', 'asc')
            ->paginate(20); // Menampilkan 20 data per halaman

        return response()->json($results);
    }
    
    /**
     * Menangani pencarian umum berdasarkan kata kunci.
     */
    public function searchKodePos(Request $request)
    {
        $request->validate(['search' => 'required|string|min:3']);
        $search = $request->search;

        $query = KodePos::query();
        
        $query->where(function ($q) use ($search) {
            $q->where('provinsi', 'like', "%{$search}%")
              ->orWhere('kota_kabupaten', 'like', "%{$search}%")
              ->orWhere('kecamatan', 'like', "%{$search}%")
              ->orWhere('kelurahan_desa', 'like', "%{$search}%")
              ->orWhere('kode_pos', 'like', "%{$search}%");
        });

        $results = $query->orderBy('provinsi')
                         ->orderBy('kota_kabupaten')
                         ->orderBy('kecamatan')
                         ->orderBy('kelurahan_desa')
                         ->paginate(20); // Menampilkan 20 data per halaman
        
        return response()->json($results);
    }
}
