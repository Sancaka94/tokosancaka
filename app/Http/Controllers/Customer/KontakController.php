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
     * Integrasi Pencarian Alamat menggunakan Service Anda yang sudah ada.
     */
    public function searchAddressApi(Request $request, KiriminAjaService $kirimaja)
    {
        // 1. Ambil input query
        $keyword = $request->input('q') ?? $request->input('search');

        if (!$keyword || strlen($keyword) < 3) {
            return response()->json([]);
        }

        try {
            // 2. PANGGIL SERVICE ANDA APA ADANYA
            // Asumsi: Service mengembalikan array ['status' => true, 'data' => [...]]
            $response = $kirimaja->searchAddress($keyword);

            if (empty($response) || empty($response['data'])) {
                return response()->json([]);
            }

            // 3. Mapping Data (Penting!)
            // Kita ubah key dari API (Bahasa Indo) ke nama yang dipakai di Database/Blade (Bahasa Inggris)
            $formatted = collect($response['data'])->map(function ($item) {
                return [
                    // Ini untuk tampilan label di dropdown
                    'label' => $item['address'] ?? $item['text'], 
                    'value' => $item['address'] ?? $item['text'],

                    // Ini data lengkap untuk mengisi input form
                    'data_lengkap' => [
                        // Mapping Key API -> Key Form Blade
                        'village'        => $item['kelurahan'] ?? '',
                        'district'       => $item['kecamatan'] ?? '',
                        'regency'        => $item['kabupaten'] ?? '', 
                        'province'       => $item['provinsi'] ?? '',
                        'postal_code'    => $item['kodepos'] ?? '',
                        
                        // ID untuk Ongkir (Sangat Penting)
                        'district_id'    => $item['kecamatan_id'] ?? null,
                        'subdistrict_id' => $item['kelurahan_id'] ?? null,
                    ]
                ];
            });

            return response()->json($formatted);

        } catch (\Exception $e) {
            Log::error('Search Address Error: ' . $e->getMessage());
            return response()->json([]);
        }
    }
}