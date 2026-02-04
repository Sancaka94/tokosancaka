<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\KodePos;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WilayahController extends Controller
{
    /**
     * Menampilkan halaman utama (hanya return view kosong).
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $provinces = Province::query()
            ->when($search, fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('name')
            ->get();

        return view('admin.wilayah.index', compact('provinces', 'search'));
    }
    // --- API UNTUK DROPDOWN DINAMIS ---

    /**
     * Ambil daftar provinsi (support search & paginate).
     */
    public function getProvinces(Request $request)
    {
        $search = $request->input('search');

        $provinces = Province::query()
            ->when($search, fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('name')
            ->paginate(15);

        return response()->json($provinces);
    }

    /**
     * Ambil daftar kabupaten berdasarkan ID provinsi.
     */
    public function getKabupaten(Province $province)
    {
        return response()->json(
            $province->regencies()->select('id', 'name')->orderBy('name')->get()
        );
    }

    /**
     * Ambil daftar kecamatan berdasarkan ID kabupaten.
     */
    public function getKecamatan(Regency $regency)
    {
        return response()->json(
            $regency->districts()->select('id', 'name')->orderBy('name')->get()
        );
    }

    /**
     * =================================================================
     * PERBAIKAN DI SINI (MENAMBAHKAN groupBy UNTUK MENCEGAH DUPLIKAT)
     * =================================================================
     */
    public function getDesa(Request $request, District $district)
    {
        $search = $request->input('search');
        $regencyName = $district->regency->name;

        $cleanRegencyName = trim(str_ireplace(['KABUPATEN', 'KOTA', 'KAB.'], '', $regencyName));

        $villages = KodePos::query()
            ->select([
                'kode_pos.id as id_kodepos',
                DB::raw('MAX(reg_villages.id) as id_wilayah'),      // Ambil satu ID wilayah saja
                'kode_pos.kelurahan_desa as name_kodepos',
                DB::raw('MAX(reg_villages.name) as name_wilayah'),  // Ambil satu nama wilayah saja
                'kode_pos.kode_pos'
            ])
            ->leftJoin('reg_villages', function($join) {
                $join->on(DB::raw('UPPER(kode_pos.kelurahan_desa)'), '=', DB::raw('UPPER(reg_villages.name)'));
            })
            ->whereRaw('UPPER(kode_pos.kecamatan) = UPPER(?)', [$district->name])
            ->whereRaw('UPPER(kode_pos.kota_kabupaten) LIKE UPPER(?)', ['%' . $cleanRegencyName . '%'])
            ->when($search, function($query, $search) {
                return $query->where('kode_pos.kelurahan_desa', 'like', "%{$search}%");
            })
            // [PERUBAHAN] Group by ID dari tabel utama untuk memastikan keunikan
            ->groupBy('kode_pos.id', 'kode_pos.kelurahan_desa', 'kode_pos.kode_pos')
            ->orderBy('name_kodepos', 'asc')
            ->paginate(15);

        return response()->json($villages);
    }
}

