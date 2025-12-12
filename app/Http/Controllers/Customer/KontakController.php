<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Kontak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\KiriminAjaService;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class KontakController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user(); // Ambil object user lengkap untuk cek role

        // --- QUERY 1: Tabel Utama (Bisa difilter/search) ---
        $query = Kontak::query();

        // LOGIKA KHUSUS: Cek apakah user adalah Admin
        // Jika BUKAN Admin, batasi query hanya milik user tersebut.
        // Jika Admin, lewati blok ini (artinya ambil semua data).
        if ($user->role !== 'admin') { 
            $query->where('user_id', $user->id);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%")
                  ->orWhere('district', 'like', "%{$search}%");
            });
        }

        if ($request->filled('filter') && $request->input('filter') !== 'Semua') {
            $query->where('tipe', $request->input('filter'));
        }

        $kontaks = $query->latest()->paginate(10);

        // --- QUERY 2: Tabel Khusus Pengirim (Selalu Tampil di Bawah) ---
        $queryPengirim = Kontak::where('tipe', 'Pengirim');

        // Terapkan logika yang sama untuk list Pengirim
        if ($user->role !== 'admin') {
            $queryPengirim->where('user_id', $user->id);
        }

        $pengirims = $queryPengirim->latest()->get();

        return view('customer.kontak.index', compact('kontaks', 'pengirims'));
    }

    /**
     * Menyimpan kontak baru sesuai inputan Blade.
     */
    public function store(Request $request)
    {
        // 1. Validasi Sesuai Name di Form Blade
        $validatedData = $request->validate([
            'nama'      => 'required|string|max:255',
            'no_hp'     => 'required|string|max:20',
            'alamat'    => 'required|string',
            'tipe'      => 'nullable|string',
            'province'    => 'required|string',
            'regency'     => 'required|string',
            'district'    => 'required|string',
            'village'     => 'required|string',
            'postal_code' => 'required|string',
            'district_id'    => 'nullable|string',
            'subdistrict_id' => 'nullable|string',
            'lat'            => 'nullable|string',
            'lng'            => 'nullable|string',
        ]);

        // 2. Set User ID & Default Tipe
        // Jika Admin yang input, tetap tersimpan atas nama Admin (Auth::id())
        // Kecuali Anda ingin Admin bisa memilih pemilik kontak (butuh input tambahan user_id)
        $validatedData['user_id'] = Auth::id();
        
        if (empty($validatedData['tipe'])) {
            $validatedData['tipe'] = 'Penerima';
        }

        // 3. Simpan ke Database
        Kontak::create($validatedData);

        return redirect()->route('customer.kontak.index')->with('success', 'Kontak baru berhasil disimpan.');
    }

    /**
     * Mengambil data untuk Modal Edit (AJAX).
     */
    public function edit($id)
    {
        $user = Auth::user();
        $query = Kontak::where('id', $id);

        // Validasi kepemilikan hanya jika BUKAN Admin
        if ($user->role !== 'Admin') {
            $query->where('user_id', $user->id);
        }

        $kontak = $query->firstOrFail();
        return response()->json($kontak);
    }

    /**
     * Update data kontak.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $query = Kontak::where('id', $id);

        // Validasi kepemilikan hanya jika BUKAN Admin
        if ($user->role !== 'Admin') {
            $query->where('user_id', $user->id);
        }

        $kontak = $query->firstOrFail();

        $validatedData = $request->validate([
            'nama'      => 'required|string|max:255',
            'no_hp'     => 'required|string|max:20',
            'alamat'    => 'required|string',
            'tipe'      => 'nullable|string',
            'province'    => 'required|string',
            'regency'     => 'required|string',
            'district'    => 'required|string',
            'village'     => 'required|string',
            'postal_code' => 'required|string',
            'district_id'    => 'nullable|string',
            'subdistrict_id' => 'nullable|string',
            'lat'            => 'nullable|string',
            'lng'            => 'nullable|string',
        ]);

        $kontak->update($validatedData);

        return redirect()->route('customer.kontak.index')->with('success', 'Kontak berhasil diperbarui.');
    }

    /**
     * Hapus kontak.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $query = Kontak::where('id', $id);

        // Validasi kepemilikan hanya jika BUKAN Admin
        if ($user->role !== 'Admin') {
            $query->where('user_id', $user->id);
        }

        $kontak = $query->firstOrFail();
        $kontak->delete();

        return redirect()->route('customer.kontak.index')->with('success', 'Kontak berhasil dihapus.');
    }

   /**
     * Pencarian Kontak (REVISI: SECURITY FIRST)
     */
    public function search(Request $request)
    {
        try {
            $keyword = $request->input('search') ?? $request->input('query');
            $tipe = $request->input('tipe'); 
            $scope = $request->input('scope'); // Flag dari JS Admin
            
            $user = Auth::user();
            $query = Kontak::query();

            // =========================================================
            // 1. LOGIKA USER_ID (DEFAULT: KETAT BERDASARKAN AUTH)
            // =========================================================
            
            // Cek apakah Admin meminta akses Global?
            $isAdminGlobal = ($user->role === 'admin' && $scope === 'global');

            if ($isAdminGlobal) {
                // HANYA ADMIN dengan flag 'global' yang bisa liat semua data.
                // (Tidak ada filter user_id)
            } else {
                // SELAIN ITU (Customer / Admin mode biasa) -> WAJIB DATA SENDIRI
                $query->where('user_id', $user->id);
            }

            // =========================================================
            // 2. LOGIKA TIPE (PENGIRIM / PENERIMA)
            // =========================================================
            
            if ($user->role === 'admin') {
                // ADMIN: Bebas Tipe.
                // Supaya Admin bisa cari data 'Pengirim' saat input di kolom 'Penerima'
                // (Tidak ada filter tipe)
            } else {
                // CUSTOMER: Ketat Tipe.
                // Jika cari 'Penerima', yang keluar harus data bertipe 'Penerima'
                if ($request->filled('tipe')) {
                    $query->where('tipe', $request->input('tipe'));
                }
            }

            // =========================================================
            // 3. PENCARIAN TEXT
            // =========================================================
            if ($keyword) {
                $query->where(function($sub) use ($keyword) {
                    $sub->where('nama', 'LIKE', "%{$keyword}%")
                        ->orWhere('no_hp', 'LIKE', "%{$keyword}%")
                        ->orWhere('alamat', 'LIKE', "%{$keyword}%");
                });
            }

            $kontaks = $query->latest()->limit(15)->get();

            return response()->json($kontaks);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error', 'error' => $e->getMessage()], 500);
        }
    }

    // ... method searchAddressApi tetap sama (tidak perlu diubah) ...
    public function searchAddressApi(Request $request, KiriminAjaService $kirimaja)
    {
        $keyword = $request->input('q') ?? $request->input('search');

        if (!$keyword || strlen($keyword) < 3) {
            return response()->json([]);
        }

        try {
            $response = $kirimaja->searchAddress($keyword);

            if (empty($response) || empty($response['data'])) {
                return response()->json([]);
            }

            $formatted = collect($response['data'])->map(function ($item) {
                $fullAddress = $item['full_address'] ?? $item['address'] ?? $item['text'] ?? '';
                $parts = array_map('trim', explode(',', $fullAddress));
                $padded = array_pad($parts, 5, ''); 

                $village     = $padded[0]; 
                $district    = $padded[1]; 
                $regency     = $padded[2]; 
                $province    = $padded[3]; 
                $postalCode  = $padded[4]; 

                if ((empty($postalCode) || !is_numeric($postalCode)) && preg_match('/\d{5}/', $fullAddress, $matches)) {
                    $postalCode = $matches[0];
                }

                return [
                    'label' => $fullAddress, 
                    'value' => $fullAddress, 
                    'data_lengkap' => [
                        'village'        => $village,
                        'district'       => $district,
                        'regency'        => $regency,
                        'province'       => $province,
                        'postal_code'    => $postalCode,
                        'district_id'    => $item['district_id'] ?? null,
                        'subdistrict_id' => $item['subdistrict_id'] ?? null,
                    ]
                ];
            });

            return response()->json($formatted);

        } catch (Exception $e) {
            Log::error('Search Address Error: ' . $e->getMessage());
            return response()->json([]);
        }
    }
}