<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaketController extends Controller
{
    protected $connection;

    public function __construct()
    {
        // Pastikan koneksi 'pondok' ada di config/database.php
        // Jika hanya pakai 1 database utama, ganti jadi: $this->connection = DB::connection();
        $this->connection = DB::connection('pondok');
    }

    public function index()
    {
        // GANTI 'created_at' MENJADI 'id'
        $paket = $this->connection->table('paket')
            ->orderBy('id', 'desc') // <-- Perbaikan disini
            ->paginate(15);
            
        return view('pondok.admin.paket.index', compact('paket'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pondok.admin.paket.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_paket'   => 'required|string|max:255',
            'harga'        => 'required|numeric|min:0',
            'periode_hari' => 'required|integer|min:1',
            'deskripsi'    => 'nullable|string',
            'fitur'        => 'nullable|string', // Input dari Textarea
        ]);

        // Proses Fitur: Textarea (String) -> Database (JSON)
        $jsonFitur = json_encode([]);
        if ($request->filled('fitur')) {
            // Memecah teks berdasarkan baris baru (support Windows \r\n dan Linux \n)
            $lines = preg_split('/\r\n|\r|\n/', $request->fitur);
            // Hapus spasi putih dan baris kosong
            $fiturArray = array_values(array_filter(array_map('trim', $lines)));
            $jsonFitur = json_encode($fiturArray);
        }

        $this->connection->table('paket')->insert([
            'nama_paket'   => $request->nama_paket,
            'harga'        => $request->harga,
            'periode_hari' => $request->periode_hari,
            'deskripsi'    => $request->deskripsi,
            'fitur'        => $jsonFitur,
            //'created_at'   => now(),
            //'updated_at'   => now(),
        ]);

        return redirect()->route('admin.paket.index')->with('success', 'Paket berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $paket = $this->connection->table('paket')->find($id);
        
        if (!$paket) {
            return redirect()->route('admin.paket.index')->with('error', 'Data tidak ditemukan');
        }

        return view('pondok.admin.paket.show', compact('paket'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $paket = $this->connection->table('paket')->find($id);

        if (!$paket) {
            return redirect()->route('admin.paket.index')->with('error', 'Data tidak ditemukan');
        }

        // Proses Fitur: Database (JSON) -> Textarea (String)
        // Agar saat diedit, muncul baris per baris di textarea
        if (!empty($paket->fitur)) {
            $fiturArray = json_decode($paket->fitur, true);
            if (is_array($fiturArray)) {
                $paket->fitur = implode("\n", $fiturArray);
            } else {
                $paket->fitur = '';
            }
        }

        return view('pondok.admin.paket.edit', compact('paket'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama_paket'   => 'required|string|max:255',
            'harga'        => 'required|numeric|min:0',
            'periode_hari' => 'required|integer|min:1',
            'deskripsi'    => 'nullable|string',
            'fitur'        => 'nullable|string',
        ]);

        // Proses Fitur: Textarea (String) -> Database (JSON)
        $jsonFitur = json_encode([]);
        if ($request->filled('fitur')) {
            $lines = preg_split('/\r\n|\r|\n/', $request->fitur);
            $fiturArray = array_values(array_filter(array_map('trim', $lines)));
            $jsonFitur = json_encode($fiturArray);
        }

        $this->connection->table('paket')->where('id', $id)->update([
            'nama_paket'   => $request->nama_paket,
            'harga'        => $request->harga,
            'periode_hari' => $request->periode_hari,
            'deskripsi'    => $request->deskripsi,
            'fitur'        => $jsonFitur,
            //'updated_at'   => now(),
        ]);

        return redirect()->route('admin.paket.index')->with('success', 'Paket berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('paket')->where('id', $id)->delete();
        return redirect()->route('admin.paket.index')->with('success', 'Paket berhasil dihapus.');
    }
}