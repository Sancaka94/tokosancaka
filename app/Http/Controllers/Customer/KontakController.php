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
    $user = Auth::user(); 

    // --- 1. ID YANG VALID (Fix Masalah Database) ---
    // Kita ambil 'id_pengguna' karena itu Primary Key di tabel Pengguna bapak.
    // Jangan pakai $user->id kalau modelnya belum disetting primaryKey-nya.
    $userId = $user->id_pengguna; 

    // --- 2. CEK ROLE ---
    $isAdmin = strtolower($user->role) === 'admin';

    // ==========================================================
    // QUERY 1: TABEL UTAMA (Untuk List "Semua Kontak")
    // ==========================================================
    $query = Kontak::query();

    // SECURITY: Kalau bukan admin, filter pakai id_pengguna yang benar
    if (!$isAdmin) { 
        $query->where('user_id', $userId);
    }

    // Filter Pencarian
    if ($request->filled('search')) {
        $search = $request->input('search');
        $query->where(function($q) use ($search) {
            $q->where('nama', 'like', "%{$search}%")
              ->orWhere('no_hp', 'like', "%{$search}%")
              ->orWhere('district', 'like', "%{$search}%");
        });
    }

    // Filter Filter (Penerima/Pengirim)
    if ($request->filled('filter') && $request->input('filter') !== 'Semua') {
        $query->where('tipe', $request->input('filter'));
    }

    $kontaks = $query->latest()->paginate(10);


    // ==========================================================
    // QUERY 2: KHUSUS DATA PENGIRIM (Untuk Tab/Dropdown Pengirim)
    // ==========================================================
    $qPengirim = Kontak::where('tipe', 'Pengirim');
    
    if (!$isAdmin) {
        $qPengirim->where('user_id', $userId);
    }
    
    $dataPengirim = $qPengirim->latest()->get();


    // ==========================================================
    // QUERY 3: KHUSUS DATA PENERIMA (Untuk Tab Penerima)
    // ==========================================================
    $qPenerima = Kontak::where('tipe', 'Penerima');

    if (!$isAdmin) {
        $qPenerima->where('user_id', $userId);
    }

    $dataPenerima = $qPenerima->latest()->get();

    // ==========================================================
    // QUERY KHUSUS DATA PENGIRIM ($pengirims)
    // ==========================================================
    
    // 1. Siapkan Query Dasar
    $qPengirim = Kontak::where('tipe', 'Pengirim');
    
    // 2. Security: Jika bukan Admin, wajib filter ID
    if (!$isAdmin) {
        $qPengirim->where('user_id', $userId);
    }
    
    // 3. Ambil data dari database
    $pengirims = $qPengirim->latest()->get();

    // 4. LOGIKA TAMBAHAN: Masukkan Data Profile Auth ke Paling Atas
    // Agar user bisa memilih dirinya sendiri sebagai pengirim tanpa input ulang
    if (!empty($user->store_name) || !empty($user->nama_lengkap)) {
        
        // Buat object Kontak "Palsu" dari data tabel Pengguna
        $profileSender = new Kontak([
            'id'          => 'profile_auth', // ID Khusus Penanda
            'nama'        => $user->store_name ?? $user->nama_lengkap, // Utamakan Nama Toko
            'no_hp'       => $user->no_wa,
            'alamat'      => $user->address_detail ?? $user->alamat ?? '-', 
            'province'    => $user->province,
            'regency'     => $user->regency,
            'district'    => $user->district,
            'village'     => $user->village,
            'postal_code' => $user->postal_code,
            'tipe'        => 'Pengirim',
            'user_id'     => $userId
        ]);

        // Tempelkan di urutan pertama (prepend)
        $pengirims->prepend($profileSender);
    }

    // ==========================================================

    // Kirim ke View (Variable $pengirims sudah berisi data DB + Profile Auth)
    return view('customer.kontak.index', compact('kontaks', 'pengirims', 'dataPenerima', 'dataPengirim'));
}

public function search(Request $request)
{
    $user = Auth::user();
    
    // --- FIX 1: AMBIL ID EKSPLISIT ---
    // Jangan pakai $user->id, tapi pakai kolom asli 'id_pengguna'
    $userId = $user->id_pengguna;

    $query = Kontak::query();

    // --- FIX 2: LOGIKA SECURITY MATI ---
    $isAdmin = strtolower($user->role) === 'admin';

    if (!$isAdmin) {
        // FILTER WAJIB: Hanya data milik user yang sedang login
        $query->where('user_id', $userId);
    }
    // (Admin bebas melihat semua)

    // --- LOGIKA PENCARIAN (Search Bar) ---
    if ($request->has('q') || $request->has('search')) {
        $keyword = $request->input('q') ?? $request->input('search');
        
        $query->where(function($q) use ($keyword) {
            $q->where('nama', 'LIKE', "%{$keyword}%")
              ->orWhere('no_hp', 'LIKE', "%{$keyword}%")
              // Tambahkan pencarian alamat & wilayah biar user enak carinya
              ->orWhere('alamat', 'LIKE', "%{$keyword}%")
              ->orWhere('district', 'LIKE', "%{$keyword}%")
              ->orWhere('village', 'LIKE', "%{$keyword}%");
        });
    }

    // --- FILTER TIPE (Pengirim/Penerima) ---
    // Penting untuk dropdown di halaman Pesanan
    if ($request->filled('tipe')) {
        $query->where('tipe', $request->input('tipe'));
    }

    // Limit 20 biar ringan
    return response()->json($query->limit(20)->get());
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