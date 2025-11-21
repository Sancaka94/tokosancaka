<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JadwalPelajaranController extends Controller
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
        $jadwal = $this->connection->table('jadwal_pelajaran')->paginate(15);
        return view('pondok.admin.jadwal_pelajaran.index', compact('jadwal'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $kelas = $this->connection->table('kelas')->get();
        $mataPelajaran = $this->connection->table('mata_pelajaran')->get();
        $pegawai = $this->connection->table('pegawai')->where('jabatan_id', 'guru')->get(); // Asumsi guru punya jabatan_id spesifik
        return view('pondok.admin.jadwal_pelajaran.create', compact('kelas', 'mataPelajaran', 'pegawai'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'kelas_id' => 'required|integer',
            'mata_pelajaran_id' => 'required|integer',
            'pegawai_id' => 'required|integer', // ID Guru
            'hari' => 'required|string',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
        ]);

        $this->connection->table('jadwal_pelajaran')->insert($request->except('_token'));
        return redirect()->route('admin.jadwal-pelajaran.index')->with('success', 'Jadwal pelajaran berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $jadwal = $this->connection->table('jadwal_pelajaran')->find($id);
        return view('pondok.admin.jadwal_pelajaran.show', compact('jadwal'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $jadwal = $this->connection->table('jadwal_pelajaran')->find($id);
        $kelas = $this->connection->table('kelas')->get();
        $mataPelajaran = $this->connection->table('mata_pelajaran')->get();
        $pegawai = $this->connection->table('pegawai')->where('jabatan_id', 'guru')->get();
        return view('pondok.admin.jadwal_pelajaran.edit', compact('jadwal', 'kelas', 'mataPelajaran', 'pegawai'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'kelas_id' => 'required|integer',
            'mata_pelajaran_id' => 'required|integer',
            'pegawai_id' => 'required|integer',
            'hari' => 'required|string',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
        ]);

        $this->connection->table('jadwal_pelajaran')->where('id', $id)->update($request->except(['_token', '_method']));
        return redirect()->route('admin.jadwal-pelajaran.index')->with('success', 'Jadwal pelajaran berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('jadwal_pelajaran')->where('id', $id)->delete();
        return redirect()->route('admin.jadwal-pelajaran.index')->with('success', 'Jadwal pelajaran berhasil dihapus.');
    }
}

