<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenilaianAkademikController extends Controller
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
        $penilaian = $this->connection->table('penilaian_akademik')
            ->leftJoin('santri', 'penilaian_akademik.santri_id', '=', 'santri.id')
            ->leftJoin('mata_pelajaran', 'penilaian_akademik.mapel_id', '=', 'mata_pelajaran.id')
            ->select('penilaian_akademik.*', 'santri.nama_lengkap', 'mata_pelajaran.nama_mapel')
            ->paginate(15);

        return view('pondok.admin.penilaian_akademik.index', compact('penilaian'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $santri = $this->connection->table('santri')->get();
        $mataPelajaran = $this->connection->table('mata_pelajaran')->get();
        $pegawai = $this->connection->table('pegawai')->get(); // Diasumsikan penilai adalah pegawai
        return view('pondok.admin.penilaian_akademik.create', compact('santri', 'mataPelajaran', 'pegawai'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'santri_id' => 'required|integer',
            'mapel_id' => 'required|integer',
            'penilai_id' => 'required|integer',
            'tanggal_penilaian' => 'required|date',
            'jenis_penilaian' => 'required|string', // Misal: 'UTS', 'UAS', 'Harian'
            'nilai' => 'required|numeric|min:0|max:100',
            'keterangan' => 'nullable|string',
        ]);

        $this->connection->table('penilaian_akademik')->insert($request->except('_token'));
        return redirect()->route('admin.penilaian-akademik.index')->with('success', 'Data penilaian berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $penilaian = $this->connection->table('penilaian_akademik')->find($id);
        return view('pondok.admin.penilaian_akademik.show', compact('penilaian'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $penilaian = $this->connection->table('penilaian_akademik')->find($id);
        $santri = $this->connection->table('santri')->get();
        $mataPelajaran = $this->connection->table('mata_pelajaran')->get();
        $pegawai = $this->connection->table('pegawai')->get();
        return view('pondok.admin.penilaian_akademik.edit', compact('penilaian', 'santri', 'mataPelajaran', 'pegawai'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'santri_id' => 'required|integer',
            'mapel_id' => 'required|integer',
            'penilai_id' => 'required|integer',
            'tanggal_penilaian' => 'required|date',
            'jenis_penilaian' => 'required|string',
            'nilai' => 'required|numeric|min:0|max:100',
            'keterangan' => 'nullable|string',
        ]);

        $this->connection->table('penilaian_akademik')->where('id', $id)->update($request->except(['_token', '_method']));
        return redirect()->route('admin.penilaian-akademik.index')->with('success', 'Data penilaian berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('penilaian_akademik')->where('id', $id)->delete();
        return redirect()->route('admin.penilaian-akademik.index')->with('success', 'Data penilaian berhasil dihapus.');
    }
}

