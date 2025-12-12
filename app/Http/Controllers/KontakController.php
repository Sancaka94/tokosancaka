<?php

namespace App\Http\Controllers\Customer; // 1. Namespace disesuaikan

use App\Http\Controllers\Controller; // 2. Import Controller utama
use App\Models\Kontak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Tambahkan Auth untuk filter data user
use App\Exports\KontaksExport;
use App\Imports\KontaksImport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class KontakController extends Controller
{
    /**
     * Menampilkan daftar kontak milik pelanggan yang sedang login.
     */
    public function index(Request $request)
    {
        // 3. Filter data agar Pelanggan hanya melihat kontaknya sendiri
        // Asumsi tabel 'kontaks' punya kolom 'user_id'. Jika tidak, hapus bagian ->where('user_id'...)
        $query = Kontak::query();
        
        // Jika Anda ingin membatasi kontak hanya milik user yang login (rekomendasi):
        // $query->where('user_id', Auth::id());

        // Logika Pencarian
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%");
            });
        }

        $kontaks = $query->latest()->paginate(10);

        // 4. Arahkan ke view Customer (pastikan file view-nya ada)
        return view('customer.kontak.index', compact('kontaks'));
    }

    /**
     * Menyimpan kontak baru.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
            'no_hp' => 'required|string|max:20', // Hapus unique global jika ingin tiap user bisa simpan nomor sama
            'alamat' => 'required|string',
            // 'tipe' => 'required|string', // Bisa di-hardcode atau dari input
        ]);

        // Tambahkan ID user yang login
        // $validatedData['user_id'] = Auth::id();
        $validatedData['tipe'] = 'Pelanggan'; // Default tipe

        Kontak::create($validatedData);

        // 5. Redirect ke route Customer
        return redirect()->route('customer.kontak.index')->with('success', 'Kontak baru berhasil disimpan.');
    }

    /**
     * Menampilkan data kontak (untuk modal edit).
     */
    public function show($id)
    {
        // Gunakan findOrFail
        $kontak = Kontak::findOrFail($id);
        return response()->json($kontak);
    }

    public function search(Request $request)
    {
        $query = $request->input('q'); // Sesuaikan dengan request dari Select2/JS (biasanya 'q')
        
        if(empty($query)) {
            return response()->json([]);
        }

        $kontaks = Kontak::where('nama', 'LIKE', "%{$query}%")
                         ->orWhere('no_hp', 'LIKE', "%{$query}%")
                         ->limit(10)
                         ->get(['id', 'nama', 'no_hp', 'alamat']);

        return response()->json($kontaks);
    }

    /**
     * Update kontak.
     */
    public function update(Request $request, $id)
    {
        $kontak = Kontak::findOrFail($id);

        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
            'no_hp' => 'required|string|max:20',
            'alamat' => 'required|string',
        ]);

        $kontak->update($validatedData);

        return redirect()->route('customer.kontak.index')->with('success', 'Kontak berhasil diperbarui.');
    }

    /**
     * Hapus kontak.
     */
    public function destroy($id)
    {
        $kontak = Kontak::findOrFail($id);
        $kontak->delete();

        return redirect()->route('customer.kontak.index')->with('success', 'Kontak berhasil dihapus.');
    }
}