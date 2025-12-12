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
        // 1. Ambil ID user yang sedang login
        $userId = Auth::id();

        // 2. Query dasar: HANYA data milik user ini
        $query = Kontak::where('user_id', $userId);

        // Logika Pencarian
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%");
            });
        }

        // Logika Filter (Pengirim/Penerima)
        if ($request->filled('filter') && $request->input('filter') !== 'Semua') {
            $query->where('tipe', $request->input('filter'));
        }

        $kontaks = $query->latest()->paginate(10);

        return view('customer.kontak.index', compact('kontaks'));
    }

    /**
     * Menyimpan kontak baru (Otomatis set user_id).
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
            // Validasi unik: No HP harus unik TAPI hanya di antara kontak milik user ini saja
            'no_hp' => 'required|string|max:20', 
            'alamat' => 'required|string',
            'tipe' => 'required|string',
        ]);

        // PENTING: Set user_id sesuai user yang login
        $validatedData['user_id'] = Auth::id();

        Kontak::create($validatedData);

        return redirect()->route('customer.kontak.index')->with('success', 'Kontak baru berhasil disimpan.');
    }

    /**
     * Update data (Hanya jika milik user sendiri).
     */
    public function update(Request $request, $id)
    {
        // Cari kontak yang ID-nya sekian DAN user_id-nya milik yang login
        // Jika bukan miliknya, otomatis 404 Not Found (Aman)
        $kontak = Kontak::where('user_id', Auth::id())->where('id', $id)->firstOrFail();

        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
            'no_hp' => 'required|string|max:20',
            'alamat' => 'required|string',
            'tipe' => 'required|string',
        ]);

        $kontak->update($validatedData);

        return redirect()->route('customer.kontak.index')->with('success', 'Kontak berhasil diperbarui.');
    }

    /**
     * Hapus data (Hanya jika milik user sendiri).
     */
    public function destroy($id)
    {
        // Cari kontak milik user ini saja
        $kontak = Kontak::where('user_id', Auth::id())->where('id', $id)->firstOrFail();
        
        $kontak->delete();

        return redirect()->route('customer.kontak.index')->with('success', 'Kontak berhasil dihapus.');
    }
    
    /**
     * Pencarian AJAX (Hanya milik sendiri).
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        
        if(empty($query)) {
            return response()->json([]);
        }

        // Query dibatasi user_id
        $kontaks = Kontak::where('user_id', Auth::id())
                        ->where(function($sub) use ($query) {
                            $sub->where('nama', 'LIKE', "%{$query}%")
                                ->orWhere('no_hp', 'LIKE', "%{$query}%");
                        })
                        ->limit(10)
                        ->get(['id', 'nama', 'no_hp', 'alamat', 'province', 'regency', 'district', 'village', 'postal_code']);

        return response()->json($kontaks);
    }
    
    // View Modal Edit (Show)
    public function show($id)
    {
         $kontak = Kontak::where('user_id', Auth::id())->where('id', $id)->firstOrFail();
         return response()->json($kontak);
    }
}