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

class KontakController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        // --- QUERY 1: Tabel Utama (Bisa difilter/search) ---
        $query = Kontak::where('user_id', $userId);

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
        // Data ini khusus tipe 'Pengirim' milik user ini, tanpa paginasi (ambil semua atau limit 5)
        $pengirims = Kontak::where('user_id', $userId)
                           ->where('tipe', 'Pengirim')
                           ->latest()
                           ->get();

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
            'alamat'    => 'required|string', // Detail Alamat
            'tipe'      => 'nullable|string', // Hidden field di blade

            // Data Wilayah (Readonly di blade, tapi wajib ada isinya)
            'province'    => 'required|string',
            'regency'     => 'required|string',
            'district'    => 'required|string',
            'village'     => 'required|string',
            'postal_code' => 'required|string',

            // Data Hidden (Penting untuk Ongkir & Peta)
            'district_id'    => 'nullable|string', // ID Kecamatan (KiriminAja/RajaOngkir)
            'subdistrict_id' => 'nullable|string', // ID Kelurahan
            'lat'            => 'nullable|string',
            'lng'            => 'nullable|string',
        ]);

        // 2. Set User ID & Default Tipe
        $validatedData['user_id'] = Auth::id();
        
        // Jika tipe kosong, default ke Penerima
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
        // Pastikan hanya bisa edit punya sendiri
        $kontak = Kontak::where('user_id', Auth::id())->where('id', $id)->firstOrFail();
        return response()->json($kontak);
    }

    /**
     * Update data kontak.
     */
    public function update(Request $request, $id)
    {
        $kontak = Kontak::where('user_id', Auth::id())->where('id', $id)->firstOrFail();

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
        $kontak = Kontak::where('user_id', Auth::id())->where('id', $id)->firstOrFail();
        $kontak->delete();

        return redirect()->route('customer.kontak.index')->with('success', 'Kontak berhasil dihapus.');
    }

    /**
     * Pencarian AJAX (Versi Debug & Fix).
     */
    public function search(Request $request)
    {
        try {
            // 1. FIX: Ambil input 'search' (karena JS Anda mengirim ?search=...)
            // Kita dukung 'search' ATAU 'query' biar aman
            $keyword = $request->input('search') ?? $request->input('query');
            
            // Tangkap filter tipe (Pengirim/Penerima)
            $tipe = $request->input('tipe'); 

            // 2. Query Dasar
            $query = Kontak::where('user_id', Auth::id());

            // 3. Filter Keyword
            if ($keyword) {
                $query->where(function($sub) use ($keyword) {
                    $sub->where('nama', 'LIKE', "%{$keyword}%")
                        ->orWhere('no_hp', 'LIKE', "%{$keyword}%");
                });
            }

            // 4. Filter Tipe (Jika ada di URL)
            if ($tipe) {
                $query->where('tipe', $tipe);
            }

            // 5. Ambil Data
            // SAYA GANTI KE get() TANPA PILIH KOLOM DULU
            // Ini untuk mencegah error jika kolom 'province' dll belum ada di database
            $kontaks = $query->limit(10)->get();

            return response()->json($kontaks);

        } catch (\Exception $e) {
            // JIKA ERROR, TAMPILKAN PESAN ASLINYA
            // Ini akan membantu kita melihat kenapa server error 500
            return response()->json([
                'message' => 'Server Error',
                'error_detail' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

/**
     * Endpoint API untuk pencarian alamat (Digunakan oleh Autocomplete)
     */
    public function searchAddressApi(Request $request, KiriminAjaService $kirimaja)
    {
        // Ambil query dari parameter 'q' (jQuery UI) atau 'search' (standar)
        $keyword = $request->input('q') ?? $request->input('search');

        // Validasi minimal karakter
        if (!$keyword || strlen($keyword) < 3) {
            return response()->json([]);
        }

        try {
            // Panggil API KiriminAja
            $response = $kirimaja->searchAddress($keyword);

            // Cek jika data kosong
            if (empty($response) || empty($response['data'])) {
                return response()->json([]);
            }

            // Format data agar aman dari error index
            $formatted = collect($response['data'])->map(function ($item) {
                
                // 1. Ambil string alamat
                $fullAddress = $item['full_address'] ?? $item['address'] ?? $item['text'] ?? '';
                
                // 2. Pecah string menjadi array
                // Format Standar: "Kelurahan, Kecamatan, Kota, Provinsi, KodePos"
                $parts = array_map('trim', explode(',', $fullAddress));
                
                // 3. [TRIK AMAN] Pad array dengan string kosong sampai 5 elemen
                // Ini mencegah error "Undefined array key" jika format alamat dari API tidak lengkap
                $padded = array_pad($parts, 5, ''); 

                // 4. Mapping ke variabel (Asumsi urutan standar KiriminAja)
                $village     = $padded[0]; // Kelurahan
                $district    = $padded[1]; // Kecamatan
                $regency     = $padded[2]; // Kota/Kab
                $province    = $padded[3]; // Provinsi
                $postalCode  = $padded[4]; // Kode Pos

                // 5. Pembersihan Data Kodepos (Opsional)
                // Jika kodepos kosong tapi ada angka 5 digit di string alamat, ambil angka tersebut
                if ((empty($postalCode) || !is_numeric($postalCode)) && preg_match('/\d{5}/', $fullAddress, $matches)) {
                    $postalCode = $matches[0];
                }

                // Return format JSON yang dibutuhkan Frontend
                return [
                    'label' => $fullAddress, // Teks yang muncul di dropdown
                    'value' => $fullAddress, // Teks yang masuk ke input saat dipilih
                    
                    // Data detail untuk autofill input lain
                    'data_lengkap' => [
                        'village'        => $village,
                        'district'       => $district,
                        'regency'        => $regency,
                        'province'       => $province,
                        'postal_code'    => $postalCode,
                        
                        // ID Wilayah (Penting untuk Cek Ongkir)
                        'district_id'    => $item['district_id'] ?? null,
                        'subdistrict_id' => $item['subdistrict_id'] ?? null,
                    ]
                ];
            });

            return response()->json($formatted);

        } catch (Exception $e) {
            Log::error('Search Address Error (KontakController): ' . $e->getMessage());
            // Return array kosong agar frontend tidak error
            return response()->json([]);
        }
    }
}