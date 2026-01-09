<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kontak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KontakController extends Controller
{
    /**
     * Mencari kontak berdasarkan nama atau nomor HP untuk autocomplete.
     * Fungsi ini dirancang untuk bekerja dengan jQuery UI Autocomplete.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        try {
            
            $searchTerm = $request->input('query', $request->input('term'));
            

            // Jangan lakukan pencarian jika query terlalu pendek
            if (strlen($searchTerm) < 2) {
                return response()->json([]);
            }

            $query = Kontak::query();

            // Cari berdasarkan nama (case-insensitive) atau nomor HP
            $query->where(function ($q) use ($searchTerm) {
                $q->where(DB::raw('LOWER(nama)'), 'LIKE', '%' . strtolower($searchTerm) . '%')
                  ->orWhere('no_hp', 'LIKE', "%{$searchTerm}%");
            });
            
            // Ambil 10 hasil teratas untuk ditampilkan di dropdown
            $kontaks = $query->limit(10)->get();

            return response()->json($kontaks);

        } catch (\Exception $e) {
            // Catat galat ke log untuk debugging
            Log::error('Error in KontakController@search: ' . $e->getMessage());
            
            // Kembalikan respons galat dalam format JSON
            return response()->json(['error' => 'Gagal mengambil data kontak dari server.'], 500);
        }
    }
}

