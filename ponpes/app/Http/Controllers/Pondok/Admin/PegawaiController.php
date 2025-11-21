<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PegawaiController extends Controller
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
        $pegawai = $this->connection->table('pegawai')
            ->leftJoin('jabatan', 'pegawai.jabatan_id', '=', 'jabatan.id')
            ->select('pegawai.*', 'jabatan.nama_jabatan')
            ->paginate(15);
            
        return view('pondok.admin.pegawai.index', compact('pegawai'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $jabatan = $this->connection->table('jabatan')->get();
        return view('pondok.admin.pegawai.create', compact('jabatan'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'jabatan_id' => 'required|integer',
            'nik' => 'nullable|string|max:20',
            'nip' => 'nullable|string|max:30',
            'gender' => 'required|in:L,P',
            'telepon' => 'nullable|string|max:15',
            'alamat' => 'nullable|string',
            'status' => 'required|string',
        ]);

        $this->connection->table('pegawai')->insert($request->except('_token'));
        return redirect()->route('admin.pegawai.index')->with('success', 'Data pegawai berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pegawai = $this->connection->table('pegawai')->find($id);
        return view('pondok.admin.pegawai.show', compact('pegawai'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $pegawai = $this->connection->table('pegawai')->find($id);
        $jabatan = $this->connection->table('jabatan')->get();
        return view('pondok.admin.pegawai.edit', compact('pegawai', 'jabatan'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'jabatan_id' => 'required|integer',
            'nik' => 'nullable|string|max:20',
            'nip' => 'nullable|string|max:30',
            'gender' => 'required|in:L,P',
            'telepon' => 'nullable|string|max:15',
            'alamat' => 'nullable|string',
            'status' => 'required|string',
        ]);

        $this->connection->table('pegawai')->where('id', $id)->update($request->except(['_token', '_method']));
        return redirect()->route('admin.pegawai.index')->with('success', 'Data pegawai berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('pegawai')->where('id', $id)->delete();
        return redirect()->route('admin.pegawai.index')->with('success', 'Data pegawai berhasil dihapus.');
    }
}

