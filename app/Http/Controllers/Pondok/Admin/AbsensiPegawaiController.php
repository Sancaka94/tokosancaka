<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AbsensiPegawaiController extends Controller
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
        $absensi = $this->connection->table('absensi_pegawai')->paginate(10);
        return view('pondok.admin.absensi_pegawai.index', compact('absensi'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Anda mungkin perlu mengambil daftar pegawai untuk dropdown
        $pegawai = $this->connection->table('pegawai')->get();
        return view('pondok.admin.absensi_pegawai.create', compact('pegawai'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // TODO: Tambahkan validasi
        $request->validate([
            'pegawai_id' => 'required|integer',
            'status_kehadiran' => 'required|string',
            'tanggal' => 'required|date',
        ]);

        $this->connection->table('absensi_pegawai')->insert($request->except('_token'));
        return redirect()->route('admin.absensi-pegawai.index')->with('success', 'Data absensi pegawai berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $absensi = $this->connection->table('absensi_pegawai')->find($id);
        return view('pondok.admin.absensi_pegawai.show', compact('absensi'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $absensi = $this->connection->table('absensi_pegawai')->find($id);
        $pegawai = $this->connection->table('pegawai')->get();
        return view('pondok.admin.absensi_pegawai.edit', compact('absensi', 'pegawai'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // TODO: Tambahkan validasi
        $request->validate([
            'pegawai_id' => 'required|integer',
            'status_kehadiran' => 'required|string',
            'tanggal' => 'required|date',
        ]);
        
        $this->connection->table('absensi_pegawai')->where('id', $id)->update($request->except(['_token', '_method']));
        return redirect()->route('admin.absensi-pegawai.index')->with('success', 'Data absensi pegawai berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('absensi_pegawai')->where('id', $id)->delete();
        return redirect()->route('admin.absensi-pegawai.index')->with('success', 'Data absensi pegawai berhasil dihapus.');
    }
}

