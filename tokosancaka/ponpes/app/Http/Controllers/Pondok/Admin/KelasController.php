<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KelasController extends Controller
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
        $kelas = $this->connection->table('kelas')
            ->leftJoin('unit_pendidikan', 'kelas.unit_id', '=', 'unit_pendidikan.id')
            ->select('kelas.*', 'unit_pendidikan.nama_unit')
            ->paginate(15);
            
        return view('pondok.admin.kelas.index', compact('kelas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $unitPendidikan = $this->connection->table('unit_pendidikan')->get();
        return view('pondok.admin.kelas.create', compact('unitPendidikan'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_kelas' => 'required|string|max:255',
            'unit_id' => 'required|integer',
            'keterangan' => 'nullable|string',
        ]);

        $data = $request->except('_token');
        $data['tenant_id'] = 1;

        $this->connection->table('kelas')->insert($data);
        return redirect()->route('admin.kelas.index')->with('success', 'Data kelas berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $kelas = $this->connection->table('kelas')->find($id);
        return view('pondok.admin.kelas.show', compact('kelas'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $kelas = $this->connection->table('kelas')->find($id);
        $unitPendidikan = $this->connection->table('unit_pendidikan')->get();
        return view('pondok.admin.kelas.edit', compact('kelas', 'unitPendidikan'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama_kelas' => 'required|string|max:255',
            'unit_id' => 'required|integer',
            'keterangan' => 'nullable|string',
        ]);

        $this->connection->table('kelas')->where('id', $id)->update($request->except(['_token', '_method']));
        return redirect()->route('admin.kelas.index')->with('success', 'Data kelas berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('kelas')->where('id', $id)->delete();
        return redirect()->route('admin.kelas.index')->with('success', 'Data kelas berhasil dihapus.');
    }
}

