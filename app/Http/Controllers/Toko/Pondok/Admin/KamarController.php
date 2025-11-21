<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KamarController extends Controller
{
    protected $connection;

    public function __construct()
    {
        $this->connection = DB::connection('pondok');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $kamar = $this->connection->table('kamar')->paginate(15);
        return view('pondok.admin.kamar.index', compact('kamar'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Jika perlu memilih unit pendidikan saat membuat kamar
        $unitPendidikan = $this->connection->table('unit_pendidikan')->get();
        return view('pondok.admin.kamar.create', compact('unitPendidikan'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_kamar' => 'required|string|max:255',
            'kapasitas' => 'required|integer|min:1',
            'unit_pendidikan_id' => 'nullable|integer', // Sesuaikan jika wajib
        ]);

        $this->connection->table('kamar')->insert($request->except('_token'));
        return redirect()->route('admin.kamar.index')->with('success', 'Data kamar berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $kamar = $this->connection->table('kamar')->find($id);
        return view('pondok.admin.kamar.show', compact('kamar'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $kamar = $this->connection->table('kamar')->find($id);
        $unitPendidikan = $this->connection->table('unit_pendidikan')->get();
        return view('pondok.admin.kamar.edit', compact('kamar', 'unitPendidikan'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama_kamar' => 'required|string|max:255',
            'kapasitas' => 'required|integer|min:1',
            'unit_pendidikan_id' => 'nullable|integer',
        ]);

        $this->connection->table('kamar')->where('id', $id)->update($request->except(['_token', '_method']));
        return redirect()->route('admin.kamar.index')->with('success', 'Data kamar berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('kamar')->where('id', $id)->delete();
        return redirect()->route('admin.kamar.index')->with('success', 'Data kamar berhasil dihapus.');
    }
}

