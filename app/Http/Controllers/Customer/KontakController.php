<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Kontak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KontakController extends Controller
{
    /**
     * Menampilkan daftar kontak KHUSUS milik user yang login.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $query = Kontak::where('user_id', $userId);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%");
            });
        }

        if ($request->filled('filter') && $request->input('filter') !== 'Semua') {
            $query->where('tipe', $request->input('filter'));
        }

        $kontaks = $query->latest()->paginate(10);

        return view('customer.kontak.index', compact('kontaks'));
    }

    /**
     * Menyimpan kontak baru.
     */
    public function store(Request $request)
    {
        // 1. Hapus validasi 'tipe' => 'required' agar tidak error
        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
            'no_hp' => 'required|string|max:20', 
            'alamat' => 'required|string',
            // 'tipe' => 'required|string', <--- INI SAYA HAPUS/KOMENTARI
        ]);

        // 2. Set user_id otomatis
        $validatedData['user_id'] = Auth::id();

        // 3. Cek apakah ada input tipe dari form? Jika tidak ada, set default 'Penerima'
        $validatedData['tipe'] = $request->input('tipe', 'Penerima'); 

        Kontak::create($validatedData);

        return redirect()->route('customer.kontak.index')->with('success', 'Kontak baru berhasil disimpan.');
    }

    /**
     * Update data.
     */
    public function update(Request $request, $id)
    {
        $kontak = Kontak::where('user_id', Auth::id())->where('id', $id)->firstOrFail();

        // Hapus validasi 'tipe' => 'required' di sini juga
        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
            'no_hp' => 'required|string|max:20',
            'alamat' => 'required|string',
             // 'tipe' => 'required|string', <--- INI SAYA HAPUS/KOMENTARI
        ]);

        // Jika form mengirim tipe, pakai itu. Jika tidak, pakai tipe yang lama (jangan diubah)
        if ($request->has('tipe')) {
            $validatedData['tipe'] = $request->input('tipe');
        }

        $kontak->update($validatedData);

        return redirect()->route('customer.kontak.index')->with('success', 'Kontak berhasil diperbarui.');
    }

    /**
     * Hapus data.
     */
    public function destroy($id)
    {
        $kontak = Kontak::where('user_id', Auth::id())->where('id', $id)->firstOrFail();
        $kontak->delete();

        return redirect()->route('customer.kontak.index')->with('success', 'Kontak berhasil dihapus.');
    }
    
    public function search(Request $request)
    {
        $query = $request->input('query');
        
        if(empty($query)) {
            return response()->json([]);
        }

        $kontaks = Kontak::where('user_id', Auth::id())
                        ->where(function($sub) use ($query) {
                            $sub->where('nama', 'LIKE', "%{$query}%")
                                ->orWhere('no_hp', 'LIKE', "%{$query}%");
                        })
                        ->limit(10)
                        ->get(['id', 'nama', 'no_hp', 'alamat', 'province', 'regency', 'district', 'village', 'postal_code']);

        return response()->json($kontaks);
    }
    
    public function show($id)
    {
         $kontak = Kontak::where('user_id', Auth::id())->where('id', $id)->firstOrFail();
         return response()->json($kontak);
    }
}