<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kontak; // Pastikan model Anda benar
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CustomerKontakController extends Controller
{
    /**
     * Menampilkan daftar kontak milik customer yang sedang login.
     */
    public function index(Request $request)
    {
        // ✅ PERBAIKAN: Menggunakan 'id_pengguna'
        $query = Kontak::where('id_pengguna', Auth::id()) 
                       ->whereIn('tipe', ['Penerima', 'Keduanya']); // Hanya tampilkan penerima

        if ($request->has('search') && $request->search != '') {
            $query->where(function($q) use ($request) {
                $q->where('nama', 'like', '%' . $request->search . '%')
                  ->orWhere('no_hp', 'like', '%' . $request->search . '%');
            });
        }
        
        $kontaks = $query->latest()->paginate(10);

        return view('customer.kontak.index', compact('kontaks'));
    }

    /**
     * Menyimpan kontak baru milik customer.
     */
    public function store(Request $request)
    {
        // ✅ PERBAIKAN: Validasi semua field alamat
        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'no_hp' => [
                'required', 'string', 'min:9', 'max:20',
                Rule::unique('kontaks')->where(function ($query) {
                    return $query->where('id_pengguna', Auth::id());
                })
            ],
            'alamat' => 'required|string|max:500',
            'province' => 'required|string',
            'regency' => 'required|string',
            'district' => 'required|string',
            'village' => 'required|string',
            'postal_code' => 'required|string',
            'district_id' => 'required|integer',
            'subdistrict_id' => 'required|integer',
            'lat' => 'nullable|string',
            'lng' => 'nullable|string',
        ]);

        // ✅ PERBAIKAN: Otomatis set id_pengguna dan tipe
        $data['id_pengguna'] = Auth::id();
        $data['tipe'] = 'Penerima'; // Pelanggan hanya menambah penerima
        // Ganti 'lng' ke 'lon' jika nama kolom Anda 'lon'
        if(isset($data['lng'])) {
            $data['lon'] = $data['lng'];
            unset($data['lng']);
        }

        Kontak::create($data);

        return redirect()->route('customer.kontak.index')->with('success', 'Kontak baru berhasil ditambahkan.');
    }

    /**
     * Mengambil data kontak untuk modal edit.
     */
    public function edit(Kontak $kontak)
    {
        // ✅ PERBAIKAN: Menggunakan 'id_pengguna'
        if ($kontak->id_pengguna !== Auth::id()) {
            abort(403, 'Akses ditolak.');
        }
        return response()->json($kontak);
    }

    /**
     * Update data kontak milik customer.
     */
    public function update(Request $request, Kontak $kontak)
    {
        // ✅ PERBAIKAN: Menggunakan 'id_pengguna'
        if ($kontak->id_pengguna !== Auth::id()) {
            abort(403, 'Akses ditolak.');
        }

        // ✅ PERBAIKAN: Validasi semua field alamat
        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'no_hp' => [
                'required', 'string', 'min:9', 'max:20',
                Rule::unique('kontaks')->where(function ($query) {
                    return $query->where('id_pengguna', Auth::id());
                })->ignore($kontak->id) // Abaikan ID saat ini
            ],
            'alamat' => 'required|string|max:500',
            'province' => 'required|string',
            'regency' => 'required|string',
            'district' => 'required|string',
            'village' => 'required|string',
            'postal_code' => 'required|string',
            'district_id' => 'required|integer',
            'subdistrict_id' => 'required|integer',
            'lat' => 'nullable|string',
            'lng' => 'nullable|string',
        ]);
        
        // Ganti 'lng' ke 'lon' jika nama kolom Anda 'lon'
        if(isset($data['lng'])) {
            $data['lon'] = $data['lng'];
            unset($data['lng']);
        }

        $kontak->update($data);

        return redirect()->route('customer.kontak.index')->with('success', 'Kontak berhasil diperbarui.');
    }

    /**
     * Hapus kontak milik customer.
     */
    public function destroy(Kontak $kontak)
    {
        // ✅ PERBAIKAN: Menggunakan 'id_pengguna'
        if ($kontak->id_pengguna !== Auth::id()) {
            abort(403, 'Akses ditolak.');
        }

        $kontak->delete();
        return redirect()->route('customer.kontak.index')->with('success', 'Kontak berhasil dihapus.');
    }
}