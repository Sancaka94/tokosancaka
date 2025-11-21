<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SantriController extends Controller
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
        $santri = $this->connection->table('santri')
            ->leftJoin('kelas', 'santri.kelas_id', '=', 'kelas.id')
            ->leftJoin('kamar', 'santri.kamar_id', '=', 'kamar.id')
            ->select('santri.*', 'kelas.nama_kelas', 'kamar.nama_kamar')
            ->paginate(15);

        return view('pondok.admin.santri.index', compact('santri'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $kelas = $this->connection->table('kelas')->get();
        $kamar = $this->connection->table('kamar')->get();
        return view('pondok.admin.santri.create', compact('kelas', 'kamar'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'nis' => 'required|string|max:50|unique:pondok.santri',
            'jenis_kelamin' => 'required|string',
            'tempat_lahir' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
            'alamat' => 'nullable|string',
            'nama_wali' => 'nullable|string|max:255',
            'telp_wali' => 'nullable|string|max:20',
            'kelas_id' => 'required|integer',
            'kamar_id' => 'required|integer',
        ]);

        $this->connection->table('santri')->insert($request->except('_token'));
        return redirect()->route('admin.santri.index')->with('success', 'Data santri berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $santri = $this->connection->table('santri')->find($id);
        return view('pondok.admin.santri.show', compact('santri'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $santri = $this->connection->table('santri')->find($id);
        $kelas = $this->connection->table('kelas')->get();
        $kamar = $this->connection->table('kamar')->get();
        return view('pondok.admin.santri.edit', compact('santri', 'kelas', 'kamar'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'nis' => 'required|string|max:50|unique:pondok.santri,nis,' . $id,
            'jenis_kelamin' => 'required|string',
            'tempat_lahir' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
            'alamat' => 'nullable|string',
            'nama_wali' => 'nullable|string|max:255',
            'telp_wali' => 'nullable|string|max:20',
            'kelas_id' => 'required|integer',
            'kamar_id' => 'required|integer',
        ]);

        $this->connection->table('santri')->where('id', $id)->update($request->except(['_token', '_method']));
        return redirect()->route('admin.santri.index')->with('success', 'Data santri berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('santri')->where('id', $id)->delete();
        return redirect()->route('admin.santri.index')->with('success', 'Data santri berhasil dihapus.');
    }
}

