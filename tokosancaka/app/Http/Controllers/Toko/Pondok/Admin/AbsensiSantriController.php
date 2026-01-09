<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AbsensiSantriController extends Controller
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
        $absensi = $this->connection->table('absensi_santri')->paginate(10);
        return view('pondok.admin.absensi_santri.index', compact('absensi'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Mengambil daftar santri untuk dropdown
        $santri = $this->connection->table('santri')->get();
        return view('pondok.admin.absensi_santri.create', compact('santri'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'santri_id' => 'required|integer',
            'status_kehadiran' => 'required|string',
            'tanggal' => 'required|date',
        ]);

        $this->connection->table('absensi_santri')->insert($request->except('_token'));
        return redirect()->route('admin.absensi-santri.index')->with('success', 'Data absensi santri berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $absensi = $this->connection->table('absensi_santri')->find($id);
        return view('pondok.admin.absensi_santri.show', compact('absensi'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $absensi = $this->connection->table('absensi_santri')->find($id);
        $santri = $this->connection->table('santri')->get();
        return view('pondok.admin.absensi_santri.edit', compact('absensi', 'santri'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'santri_id' => 'required|integer',
            'status_kehadiran' => 'required|string',
            'tanggal' => 'required|date',
        ]);
        
        $this->connection->table('absensi_santri')->where('id', $id)->update($request->except(['_token', '_method']));
        return redirect()->route('admin.absensi-santri.index')->with('success', 'Data absensi santri berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('absensi_santri')->where('id', $id)->delete();
        return redirect()->route('admin.absensi-santri.index')->with('success', 'Data absensi santri berhasil dihapus.');
    }
}

