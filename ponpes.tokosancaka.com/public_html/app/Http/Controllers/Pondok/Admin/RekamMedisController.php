<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RekamMedisController extends Controller
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
        $rekamMedis = $this->connection->table('rekam_medis')
            ->leftJoin('santri', 'rekam_medis.santri_id', '=', 'santri.id')
            ->select('rekam_medis.*', 'santri.nama_lengkap')
            ->paginate(15);

        return view('pondok.admin.rekam_medis.index', compact('rekamMedis'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $santri = $this->connection->table('santri')->get();
        return view('pondok.admin.rekam_medis.create', compact('santri'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'santri_id' => 'required|integer',
            'tanggal_periksa' => 'required|date',
            'keluhan' => 'required|string',
            'diagnosa' => 'nullable|string',
            'tindakan' => 'nullable|string',
            'obat' => 'nullable|string',
        ]);

        $this->connection->table('rekam_medis')->insert($request->except('_token'));
        return redirect()->route('admin.rekam-medis.index')->with('success', 'Data rekam medis berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $rekamMedis = $this->connection->table('rekam_medis')->find($id);
        return view('pondok.admin.rekam_medis.show', compact('rekamMedis'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $rekamMedis = $this->connection->table('rekam_medis')->find($id);
        $santri = $this->connection->table('santri')->get();
        return view('pondok.admin.rekam_medis.edit', compact('rekamMedis', 'santri'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'santri_id' => 'required|integer',
            'tanggal_periksa' => 'required|date',
            'keluhan' => 'required|string',
            'diagnosa' => 'nullable|string',
            'tindakan' => 'nullable|string',
            'obat' => 'nullable|string',
        ]);

        $this->connection->table('rekam_medis')->where('id', $id)->update($request->except(['_token', '_method']));
        return redirect()->route('admin.rekam-medis.index')->with('success', 'Data rekam medis berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('rekam_medis')->where('id', $id)->delete();
        return redirect()->route('admin.rekam-medis.index')->with('success', 'Data rekam medis berhasil dihapus.');
    }
}

