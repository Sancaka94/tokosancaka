<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UnitPendidikanController extends Controller
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
        $unitPendidikan = $this->connection->table('unit_pendidikan')
            ->leftJoin('pegawai', 'unit_pendidikan.kepala_unit_id', '=', 'pegawai.id')
            ->select('unit_pendidikan.*', 'pegawai.nama_lengkap as nama_kepala_unit')
            ->paginate(15);

        return view('pondok.admin.unit_pendidikan.index', compact('unitPendidikan'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $pegawai = $this->connection->table('pegawai')->get();
        return view('pondok.admin.unit_pendidikan.create', compact('pegawai'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_unit' => 'required|string|max:255',
            'kepala_unit_id' => 'nullable|integer|exists:pondok.pegawai,id',
            'keterangan' => 'nullable|string',
        ]);

        $data = $request->except('_token');

        // --- PERBAIKAN ---
        // Mengubah tenant_id menjadi angka (integer) sesuai permintaan database.
        // Ganti angka 1 jika ID tenant Anda berbeda.
        $data['tenant_id'] = 1;

        $this->connection->table('unit_pendidikan')->insert($data);
        
        return redirect()->route('admin.unit-pendidikan.index')->with('success', 'Unit pendidikan berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $unitPendidikan = $this->connection->table('unit_pendidikan')->find($id);
        return view('pondok.admin.unit_pendidikan.show', compact('unitPendidikan'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $unitPendidikan = $this->connection->table('unit_pendidikan')->find($id);
        $pegawai = $this->connection->table('pegawai')->get();
        return view('pondok.admin.unit_pendidikan.edit', compact('unitPendidikan', 'pegawai'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama_unit' => 'required|string|max:255',
            'kepala_unit_id' => 'nullable|integer|exists:pondok.pegawai,id',
            'keterangan' => 'nullable|string',
        ]);

        $this->connection->table('unit_pendidikan')->where('id', $id)->update($request->except(['_token', '_method']));
        return redirect()->route('admin.unit-pendidikan.index')->with('success', 'Unit pendidikan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('unit_pendidikan')->where('id', $id)->delete();
        return redirect()->route('admin.unit-pendidikan.index')->with('success', 'Unit pendidikan berhasil dihapus.');
    }
}

