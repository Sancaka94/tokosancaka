<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PelanggaranSantriController extends Controller
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
        $pelanggaran = $this->connection->table('pelanggaran_santri')
            ->leftJoin('santri', 'pelanggaran_santri.santri_id', '=', 'santri.id')
            ->select('pelanggaran_santri.*', 'santri.nama_lengkap')
            ->paginate(15);

        return view('pondok.admin.pelanggaran_santri.index', compact('pelanggaran'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $santri = $this->connection->table('santri')->get();
        return view('pondok.admin.pelanggaran_santri.create', compact('santri'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'santri_id' => 'required|integer',
            'tanggal_pelanggaran' => 'required|date',
            'pelanggaran' => 'required|string',
            'tindakan' => 'nullable|string',
            'poin' => 'nullable|integer',
        ]);

        $this->connection->table('pelanggaran_santri')->insert($request->except('_token'));
        return redirect()->route('admin.pelanggaran-santri.index')->with('success', 'Data pelanggaran santri berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pelanggaran = $this->connection->table('pelanggaran_santri')->find($id);
        return view('pondok.admin.pelanggaran_santri.show', compact('pelanggaran'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $pelanggaran = $this->connection->table('pelanggaran_santri')->find($id);
        $santri = $this->connection->table('santri')->get();
        return view('pondok.admin.pelanggaran_santri.edit', compact('pelanggaran', 'santri'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'santri_id' => 'required|integer',
            'tanggal_pelanggaran' => 'required|date',
            'pelanggaran' => 'required|string',
            'tindakan' => 'nullable|string',
            'poin' => 'nullable|integer',
        ]);

        $this->connection->table('pelanggaran_santri')->where('id', $id)->update($request->except(['_token', '_method']));
        return redirect()->route('admin.pelanggaran-santri.index')->with('success', 'Data pelanggaran santri berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('pelanggaran_santri')->where('id', $id)->delete();
        return redirect()->route('admin.pelanggaran-santri.index')->with('success', 'Data pelanggaran santri berhasil dihapus.');
    }
}

