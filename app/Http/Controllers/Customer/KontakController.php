<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Kontak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KontakController extends Controller
{
    /**
     * Menampilkan daftar kontak.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $query = Kontak::where('user_id', $userId);

        // Fitur Pencarian di Index
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%")
                  ->orWhere('district', 'like', "%{$search}%"); // Tambah cari kecamatan
            });
        }

        $kontaks = $query->latest()->paginate(10);

        return view('customer.kontak.index', compact('kontaks'));
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
}