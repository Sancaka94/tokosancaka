<?php

namespace App\Http\Controllers\Customer; // <--- Namespace KHUSUS Customer

use App\Http\Controllers\Controller;
use App\Models\Kontak;
use Illuminate\Http\Request;
use App\Exports\KontaksExport; // Pastikan file ini bisa diakses atau buat khusus customer
use App\Imports\KontaksImport; // Pastikan file ini bisa diakses atau buat khusus customer
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class KontakController extends Controller
{
    /**
     * Menampilkan daftar kontak customer.
     */
    public function index(Request $request)
    {
        // Mulai query
        $query = Kontak::query();

        // [PENTING] Filter agar Customer hanya melihat kontaknya sendiri
        // HAPUS komentar di bawah (//) jika tabel 'kontaks' Anda memiliki kolom 'user_id'
        // $query->where('user_id', Auth::id());

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

        // [PENTING] Mengarahkan ke View Customer (Layout Customer)
        return view('customer.kontak.index', compact('kontaks'));
    }

    /**
     * Menyimpan kontak baru.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
            'no_hp' => 'required|string|max:20', // Hapus unique global jika ingin customer bisa punya no hp sama dengan orang lain
            'alamat' => 'required|string',
            'tipe' => 'required|string',
        ]);

        // Tambahkan user_id otomatis saat create
        // HAPUS komentar jika tabel ada user_id
        // $validatedData['user_id'] = Auth::id();

        Kontak::create($validatedData);

        // Redirect ke route CUSTOMER
        return redirect()->route('customer.kontak.index')->with('success', 'Kontak baru berhasil disimpan.');
    }

    /**
     * Menampilkan data untuk modal edit.
     */
    public function show(Kontak $kontak) // Pastikan Model Binding berfungsi
    {
        // [OPSIONAL] Keamanan: Pastikan kontak ini milik user yang login
        // if ($kontak->user_id !== Auth::id()) { abort(403); }

        return response()->json($kontak);
    }
    
    /**
     * Memperbarui kontak.
     */
    public function update(Request $request, $id)
    {
        $kontak = Kontak::findOrFail($id);

        // [OPSIONAL] Keamanan
        // if ($kontak->user_id !== Auth::id()) { abort(403); }

        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
            'no_hp' => 'required|string|max:20',
            'alamat' => 'required|string',
            'tipe' => 'required|string',
        ]);

        $kontak->update($validatedData);

        // Redirect ke route CUSTOMER
        return redirect()->route('customer.kontak.index')->with('success', 'Kontak berhasil diperbarui.');
    }

    /**
     * Menghapus kontak.
     */
    public function destroy($id)
    {
        $kontak = Kontak::findOrFail($id);

        // [OPSIONAL] Keamanan
        // if ($kontak->user_id !== Auth::id()) { abort(403); }

        $kontak->delete();

        // Redirect ke route CUSTOMER
        return redirect()->route('customer.kontak.index')->with('success', 'Kontak berhasil dihapus.');
    }
    
    /**
     * Pencarian Live (AJAX) untuk Customer.
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        
        if(empty($query)) {
            return response()->json([]);
        }

        $q = Kontak::query();

        // Filter user sendiri
        // $q->where('user_id', Auth::id());

        $kontaks = $q->where(function($sub) use ($query) {
                            $sub->where('nama', 'LIKE', "%{$query}%")
                                ->orWhere('no_hp', 'LIKE', "%{$query}%");
                        })
                        ->limit(10)
                        ->get(['id', 'nama', 'no_hp', 'alamat', 'province', 'regency', 'district', 'village', 'postal_code']);

        return response()->json($kontaks);
    }
}