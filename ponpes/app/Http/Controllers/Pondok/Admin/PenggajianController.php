<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenggajianController extends Controller
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
        $penggajian = $this->connection->table('penggajian')
            ->leftJoin('pegawai', 'penggajian.pegawai_id', '=', 'pegawai.id')
            ->select('penggajian.*', 'pegawai.nama_lengkap')
            ->paginate(15);

        return view('pondok.admin.penggajian.index', compact('penggajian'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $pegawai = $this->connection->table('pegawai')->get();
        return view('pondok.admin.penggajian.create', compact('pegawai'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'pegawai_id' => 'required|integer',
            'tanggal_gaji' => 'required|date',
            'gaji_pokok' => 'required|numeric|min:0',
            'tunjangan' => 'nullable|numeric|min:0',
            'potongan' => 'nullable|numeric|min:0',
            'total_gaji' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
        ]);

        $this->connection->table('penggajian')->insert($request->except('_token'));
        return redirect()->route('admin.penggajian.index')->with('success', 'Data penggajian berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $penggajian = $this->connection->table('penggajian')->find($id);
        return view('pondok.admin.penggajian.show', compact('penggajian'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $penggajian = $this->connection->table('penggajian')->find($id);
        $pegawai = $this->connection->table('pegawai')->get();
        return view('pondok.admin.penggajian.edit', compact('penggajian', 'pegawai'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'pegawai_id' => 'required|integer',
            'tanggal_gaji' => 'required|date',
            'gaji_pokok' => 'required|numeric|min:0',
            'tunjangan' => 'nullable|numeric|min:0',
            'potongan' => 'nullable|numeric|min:0',
            'total_gaji' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
        ]);

        $this->connection->table('penggajian')->where('id', $id)->update($request->except(['_token', '_method']));
        return redirect()->route('admin.penggajian.index')->with('success', 'Data penggajian berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('penggajian')->where('id', $id)->delete();
        return redirect()->route('admin.penggajian.index')->with('success', 'Data penggajian berhasil dihapus.');
    }
}

